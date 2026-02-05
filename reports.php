<?php
require 'session_check.php';
require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$currency_symbol = $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency'];

// --- DATE LOGIC ---
$month_list = [];
for ($i = 0; $i < 12; $i++) {
    $month_list[] = [
        'offset' => -$i,
        'label' => date('M', strtotime("-$i month")),
        'year' => date('Y', strtotime("-$i month")),
        'value' => date('Y-m', strtotime("-$i month"))
    ];
}

$month_offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
$view_start = date('Y-m-01', strtotime("$month_offset month"));
$view_end = date('Y-m-t', strtotime("$month_offset month"));

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
                <h6 class="fw-bold text-dark mb-1">High Spending Alert</h6>
                <p class="text-muted small mb-0"><strong>' . htmlspecialchars($top['category']) . '</strong> is ' . number_format($pct, 0) . '% of expenses.</p>
            </div>
        </div>';
    } else {
        $insight_html = '
        <div class="glass-card-success p-3 mb-4 d-flex align-items-center fade-in-up">
            <div class="rounded-circle bg-success bg-opacity-25 p-3 me-3 text-success">
                <i class="fas fa-check-circle fa-lg"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-1">On Track</h6>
                <p class="text-muted small mb-0">Your spending is balanced.</p>
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
                <h6 class="fw-bold text-dark mb-1">Ready to Track</h6>
                <p class="text-muted small mb-0">No expenses found for ' . date('F', strtotime($view_start)) . ' yet.</p>
            </div>
        </div>';
}

// 3. Daily Spending Trend (Line Chart)
$days_in_month = (int) date('t', strtotime($view_start));
$daily_labels = [];
$daily_values = [];
$daily_map = [];

for ($d = 1; $d <= $days_in_month; $d++) {
    $daily_map[$d] = 0;
    $daily_labels[] = $d;
}

$stmt = $pdo->prepare("SELECT DAY(date) as day, SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'expense' AND date BETWEEN ? AND ? GROUP BY day");
$stmt->execute([$user_id, $view_start, $view_end]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $daily_map[$row['day']] = (float) $row['total'];
}
$daily_values = array_values($daily_map);

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
            Premium Reports</h2>
        <p class="text-muted small">Analyze your financial lifestyle.</p>
    </div>

    <!-- Month Scroller -->
    <div class="px-3 mb-4 fade-in-up delay-1">
        <div class="month-scroller">
            <?php foreach ($month_list as $m):
                $isActive = ($m['offset'] == $month_offset);
                ?>
                <a href="?offset=<?php echo $m['offset']; ?>" class="month-pill <?php echo $isActive ? 'active' : ''; ?>">
                    <small><?php echo $m['year']; ?></small>
                    <span><?php echo $m['label']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
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
                    <p class="text-muted small fw-bold text-uppercase mb-1">Total Spent</p>
                    <h3 class="fw-bold text-dark mb-0">
                        <?php echo $currency_symbol . number_format($total_expense, 0); ?>
                    </h3>
                </div>
                <div class="col-6">
                    <p class="text-muted small fw-bold text-uppercase mb-1">Total Income</p>
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
            <h5 class="fw-bold mb-3">Monthly Comparison</h5>
            <div class="chart-box" style="height: 250px;">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>

    <!-- PIE CHART (Expense Breakdown) -->
    <div class="px-3 mb-4 fade-in-up delay-3">
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-3">Expense Breakdown</h5>

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
                    <p>Start adding expenses to populate the chart.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- LINE CHART (Trend) -->
    <div class="px-3 mb-4 fade-in-up delay-4">
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-3">Daily Spending Flow</h5>
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

    const dailyLabels = <?php echo json_encode($daily_labels); ?>;
    const dailyValues = <?php echo json_encode($daily_values); ?>;

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
                            return 'Day ' + context[0].label;
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