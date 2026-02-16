<?php
require 'session_check.php';
require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$currency_symbol = $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency'];

// --- DATE LOGIC ---
// --- FILTER LOGIC ---
$report_type = $_GET['report_type'] ?? 'monthly';
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');

// Validate Year (simple range check or just cast)
$selected_year = (int) $selected_year;
if ($selected_year < 2000 || $selected_year > 2100)
    $selected_year = date('Y');

if ($report_type === 'yearly') {
    $view_start = "$selected_year-01-01";
    $view_end = "$selected_year-12-31";
    $view_label = "Year $selected_year";
} else {
    // Monthly
    $view_start = "$selected_year-$selected_month-01";
    $view_end = date('Y-m-t', strtotime($view_start));
    $view_label = date('F Y', strtotime($view_start));
}

// FETCHER
function getSum($pdo, $uid, $type, $start, $end)
{
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = ? AND date BETWEEN ? AND ?");
    $stmt->execute([$uid, $type, $start, $end]);
    return $stmt->fetchColumn() ?: 0;
}

// 1. Core Data
$total_expense = getSum($pdo, $user_id, 'expense', $view_start, $view_end);
$total_income = getSum($pdo, $user_id, 'income', $view_start, $view_end);

// 2. Spending by Category (Pie Chart)
$stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'expense' AND date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC");
$stmt->execute([$user_id, $view_start, $view_end]);
$cat_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cat_labels = [];
$cat_values = [];
$cat_colors = ['#6366f1', '#ec4899', '#10b981', '#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444', '#14b8a6'];

foreach ($cat_data as $row) {
    $cat_labels[] = $row['category'];
    $cat_values[] = $row['total'];
}

// Smart Insight Logic
$insight_html = '';
if ($total_expense > 0 && !empty($cat_data)) {
    $top = $cat_data[0];
    $pct = ($top['total'] / $total_expense) * 100;

    if ($pct > 35) {
        $insight_html = '
        <div class="glass-card-warning p-3 mb-4 d-flex align-items-center fade-in-up">
            <div class="rounded-circle bg-warning bg-opacity-25 p-3 me-3 text-warning">
                <i class="fas fa-exclamation-triangle fa-lg"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-1">' . $lang['high_spending_alert'] . '</h6>
                <p class="text-muted small mb-0">' . sprintf($lang['spending_alert_msg'], htmlspecialchars($top['category']), number_format($pct, 0)) . '</p>
            </div>
        </div>';
    } else {
        $insight_html = '
        <div class="glass-card-success p-3 mb-4 d-flex align-items-center fade-in-up">
            <div class="rounded-circle bg-success bg-opacity-25 p-3 me-3 text-success">
                <i class="fas fa-check-circle fa-lg"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-1">' . $lang['on_track'] . '</h6>
                <p class="text-muted small mb-0">' . $lang['spending_balanced'] . '</p>
            </div>
        </div>';
    }
} else {
    $insight_html = '
        <div class="glass-card p-3 mb-4 d-flex align-items-center fade-in-up">
            <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3 text-secondary">
                <i class="fas fa-wallet fa-lg"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-1">' . $lang['ready_to_track'] . '</h6>
                <p class="text-muted small mb-0">' . $lang['no_expenses_found'] . '</p>
            </div>
        </div>';
}

// 3. Trend Line Chart (Daily or Monthly)
$trend_labels = [];
$trend_values = [];

if ($report_type === 'yearly') {
    // Group by Month (1-12)
    for ($m = 1; $m <= 12; $m++) {
        $trend_labels[] = date('M', mktime(0, 0, 0, $m, 1));
        $trend_values[$m] = 0;
    }

    $stmt = $pdo->prepare("SELECT MONTH(date) as m, SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'expense' AND date BETWEEN ? AND ? GROUP BY m");
    $stmt->execute([$user_id, $view_start, $view_end]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // adjust index (0-11)
        $trend_values[$row['m']] = (float) $row['total'];
    }
    // re-index to 0-based array matching labels
    $trend_values = array_values($trend_values);

} else {
    // Group by Day
    $days_in_month = (int) date('t', strtotime($view_start));
    $daily_map = []; // helper

    for ($d = 1; $d <= $days_in_month; $d++) {
        $daily_map[$d] = 0;
        $trend_labels[] = $d;
    }

    $stmt = $pdo->prepare("SELECT DAY(date) as day, SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'expense' AND date BETWEEN ? AND ? GROUP BY day");
    $stmt->execute([$user_id, $view_start, $view_end]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $daily_map[$row['day']] = (float) $row['total'];
    }
    $trend_values = array_values($daily_map);
}

// 4. Monthly Comparison (Last 6 Months)
$comp_labels = [];
$comp_values = [];
$check_date = $view_start; // Start from view month and go back

for ($i = 5; $i >= 0; $i--) {
    $m_start = date('Y-m-01', strtotime("$check_date -$i month"));
    $m_end = date('Y-m-t', strtotime("$check_date -$i month"));
    $val = getSum($pdo, $user_id, 'expense', $m_start, $m_end);

    $comp_labels[] = date('M', strtotime($m_start));
    $comp_values[] = $val;
}
?>
<?php include 'header.php'; ?>

<style>
    .month-scroller {
        display: flex;
        overflow-x: auto;
        gap: 12px;
        padding: 5px 5px 15px 5px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }

    .month-scroller::-webkit-scrollbar {
        display: none;
    }

    .month-pill {
        min-width: 75px;
        text-align: center;
        padding: 8px 12px;
        border-radius: 14px;
        background: white;
        border: 1px solid #e2e8f0;
        color: var(--text-muted);
        text-decoration: none;
        transition: 0.2s;
        display: flex;
        flex-direction: column;
    }

    .month-pill.active {
        background: var(--primary-gradient);
        color: white;
        border-color: transparent;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .month-pill small {
        font-size: 0.7rem;
        opacity: 0.8;
    }

    .month-pill span {
        font-weight: 700;
        font-size: 0.95rem;
    }

    .glass-card-warning {
        background: rgba(255, 247, 237, 0.95);
        border: 1px solid #fdba74;
        border-radius: 20px;
    }

    .glass-card-success {
        background: rgba(240, 253, 244, 0.95);
        border: 1px solid #86efac;
        border-radius: 20px;
    }

    .chart-box {
        position: relative;
        height: 300px;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<div class="container-fluid px-0 px-md-3 pb-5">

    <!-- Header -->
    <div class="px-3 mt-2 mb-3 fade-in-up">
        <h2 class="fw-bold mb-1"
            style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            <?php echo $lang['premium_reports']; ?>
        </h2>
        <p class="text-muted small"><?php echo $lang['analyze_lifestyle']; ?></p>
    </div>

    <!-- FILTER FORM (Month/Year/Type) -->
    <div class="px-3 mb-4 fade-in-up delay-1">
        <form action="" method="GET" class="glass-card p-3 d-flex flex-wrap align-items-end gap-2">

            <div class="flex-grow-1">
                <label class="form-label small text-muted fw-bold mb-1"><?php echo $lang['type']; ?></label>
                <select name="report_type" class="form-select border-0 bg-light fw-bold" onchange="this.form.submit()">
                    <option value="monthly" <?php echo ($report_type === 'monthly') ? 'selected' : ''; ?>>
                        <?php echo $lang['monthly']; ?>
                    </option>
                    <option value="yearly" <?php echo ($report_type === 'yearly') ? 'selected' : ''; ?>>
                        <?php echo $lang['yearly']; ?>
                    </option>
                </select>
            </div>

            <?php if ($report_type === 'monthly'): ?>
                <div class="flex-grow-1">
                    <label class="form-label small text-muted fw-bold mb-1"><?php echo $lang['month']; ?></label>
                    <select name="month" class="form-select border-0 bg-light fw-bold" onchange="this.form.submit()">
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $mStr = str_pad($m, 2, '0', STR_PAD_LEFT);
                            $mName = date('F', mktime(0, 0, 0, $m, 1));
                            $sel = ($mStr == $selected_month) ? 'selected' : '';
                            echo "<option value='$mStr' $sel>$mName</option>";
                        }
                        ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="flex-grow-1">
                <label class="form-label small text-muted fw-bold mb-1"><?php echo $lang['year']; ?></label>
                <select name="year" class="form-select border-0 bg-light fw-bold" onchange="this.form.submit()">
                    <?php
                    $currentYear = date('Y');
                    for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                        $sel = ($y == $selected_year) ? 'selected' : '';
                        echo "<option value='$y' $sel>$y</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary fw-bold px-4" style="background: var(--primary-gradient);">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Insight Alert -->
    <div class="px-3">
        <?php echo $insight_html; ?>
    </div>

    <!-- Overview Stats -->
    <div class="px-3 mb-4 fade-in-up delay-2">
        <div class="glass-card p-4">
            <div class="row text-center">
                <div class="col-6 border-end">
                    <p class="text-muted small fw-bold text-uppercase mb-1"><?php echo $lang['total_spent']; ?></p>
                    <h3 class="fw-bold text-dark mb-0">
                        <?php echo $currency_symbol . number_format($total_expense, 0); ?>
                    </h3>
                </div>
                <div class="col-6">
                    <p class="text-muted small fw-bold text-uppercase mb-1"><?php echo $lang['total_income']; ?></p>
                    <h3 class="fw-bold text-success mb-0">
                        <?php echo $currency_symbol . number_format($total_income, 0); ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- MONTHLY COMPARISON (NEW) -->
    <div class="px-3 mb-4 fade-in-up delay-3">
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-3"><?php echo $lang['monthly_comparison']; ?></h5>
            <div class="chart-box" style="height: 250px;">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>

    <!-- PIE CHART (Expense Breakdown) -->
    <div class="px-3 mb-4 fade-in-up delay-3">
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-3"><?php echo $lang['expense_breakdown']; ?></h5>

            <div class="chart-box">
                <canvas id="pieChart"></canvas>
            </div>

            <?php if ($total_expense > 0): ?>
                <!-- Legend -->
                <div class="mt-4 row g-2">
                    <?php foreach ($cat_data as $i => $row):
                        if ($i >= 4)
                            break;
                        $color = $cat_colors[$i % count($cat_colors)];
                        ?>
                        <div class="col-6">
                            <div class="d-flex align-items-center small bg-light rounded-3 p-2">
                                <span class="rounded-circle me-2"
                                    style="width: 8px; height: 8px; background: <?php echo $color; ?>;"></span>
                                <span class="text-truncate me-1 text-muted fw-medium"
                                    style="max-width: 60px;"><?php echo htmlspecialchars($row['category']); ?></span>
                                <span
                                    class="fw-bold text-dark ms-auto"><?php echo number_format(($row['total'] / $total_expense) * 100, 0); ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center mt-3 text-muted small">
                    <p><?php echo $lang['start_adding_expenses']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- DETAILED BREAKDOWN TABLE -->
    <div class="px-3 mb-4 fade-in-up delay-3">
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-3"><?php echo $lang['category_details']; ?></h5>
            <?php if (!empty($cat_data)): ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($cat_data as $i => $row):
                        $pct = ($total_expense > 0) ? ($row['total'] / $total_expense) * 100 : 0;
                        $color = $cat_colors[$i % count($cat_colors)];
                        ?>
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-4 bg-light bg-opacity-50">

                            <!-- Left: Icon & Name -->
                            <div class="d-flex align-items-center flex-grow-1 overflow-hidden">
                                <div class="rounded-circle me-2 d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm"
                                    style="width: 38px; height: 38px; background-color: white; color: <?php echo $color; ?>;">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="fw-bold text-dark mb-1 text-wrap" style="font-size: 0.8rem; line-height: 1.1;">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                    </div>
                                    <div class="progress" style="height: 4px; width: 60px; background-color: rgba(0,0,0,0.05);">
                                        <div class="progress-bar rounded-pill"
                                            style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Amount & % -->
                            <div class="text-end ps-2">
                                <div class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">
                                    <?php echo $currency_symbol . number_format($row['total']); ?>
                                </div>
                                <small class="text-muted fw-bold"
                                    style="font-size: 0.7rem;"><?php echo number_format($pct, 1); ?>%</small>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-3 text-muted opacity-25">
                        <i class="fas fa-receipt fa-3x"></i>
                    </div>
                    <h6 class="fw-bold text-muted"><?php echo $lang['no_expenses_found']; ?></h6>
                    <p class="small text-muted mb-0"><?php echo $lang['try_changing_date']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- LINE CHART (Trend) -->
    <div class="px-3 mb-4 fade-in-up delay-4">
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-3">
                <?php echo ($report_type === 'yearly') ? $lang['monthly_trends'] : $lang['daily_spending_flow']; ?>
            </h5>
            <div class="chart-box">
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#94a3b8';

    // Data from PHP
    const catLabels = <?php echo json_encode($cat_labels); ?>;
    const catValues = <?php echo json_encode($cat_values); ?>;
    const catColors = <?php echo json_encode($cat_colors); ?>;

    const dailyLabels = <?php echo json_encode($trend_labels); ?>;
    const dailyValues = <?php echo json_encode($trend_values); ?>;

    const compLabels = <?php echo json_encode($comp_labels); ?>;
    const compValues = <?php echo json_encode($comp_values); ?>;

    const totalExpense = <?php echo $total_expense; ?>;

    // --- BAR CHART (Monthly Comparison) ---
    const ctxBar = document.getElementById('barChart').getContext('2d');
    let barGradient = ctxBar.createLinearGradient(0, 0, 0, 300);
    barGradient.addColorStop(0, '#3b82f6');
    barGradient.addColorStop(1, '#6366f1');

    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: compLabels,
            datasets: [{
                label: 'Total Expenses',
                data: compValues,
                backgroundColor: compLabels.map((l, i) => (i === 5) ? '#6366f1' : 'rgba(203, 213, 225, 0.5)'), // Highlight last (current) bar
                borderRadius: 6,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { display: false, beginAtZero: true },
                x: { grid: { display: false } }
            }
        }
    });

    // --- PIE CHART ---
    const ctxPie = document.getElementById('pieChart').getContext('2d');

    // Check if we have data or need placeholders
    let pieData, pieColors, pieLabels;
    if (totalExpense > 0) {
        pieData = catValues;
        pieColors = catColors;
        pieLabels = catLabels;
    } else {
        // Empty State: Gray Circle
        pieData = [1];
        pieColors = ['#f1f5f9'];
        pieLabels = ['No Data'];
    }

    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: pieColors,
                borderWidth: 0,
                hoverOffset: totalExpense > 0 ? 10 : 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            layout: { padding: 10 },
            plugins: {
                legend: { display: false },
                tooltip: { enabled: totalExpense > 0 }
            }
        }
    });

    // --- LINE CHART (ENHANCED) ---
    const ctxLine = document.getElementById('lineChart').getContext('2d');

    let gradient = ctxLine.createLinearGradient(0, 0, 0, 300);
    let colorPrimary = totalExpense > 0 ? '#6366f1' : '#cbd5e1';
    let gradStart = totalExpense > 0 ? 'rgba(99, 102, 241, 0.4)' : 'rgba(203, 213, 225, 0.4)';

    gradient.addColorStop(0, gradStart);
    gradient.addColorStop(1, 'rgba(255, 255, 255, 0.0)');

    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Spent',
                data: dailyValues,
                borderColor: colorPrimary,
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: (ctx) => {
                    return ctx.raw > 0 ? 4 : 0;
                },
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#6366f1',
                pointBorderWidth: 2,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('en-US', { style: 'currency', currency: '<?php echo $_SESSION['currency']; ?>', maximumFractionDigits: 0 }).format(context.parsed.y);
                            }
                            return label;
                        },
                        title: function (context) {
                            return '<?php echo ($report_type === 'yearly') ? 'Month ' : 'Day '; ?>' + context[0].label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    grid: { display: false },
                    ticks: {
                        maxTicksLimit: 10,
                        font: { size: 10 }
                    }
                },
                y: {
                    display: true,
                    min: 0,
                    grid: { color: 'rgba(0,0,0,0.03)', borderDash: [5, 5] },
                    ticks: {
                        callback: function (value) {
                            return value >= 1000 ? (value / 1000) + 'k' : value;
                        },
                        font: { size: 10 }
                    }
                }
            }
        }
    });
</script>

<?php include 'footer.php'; ?>