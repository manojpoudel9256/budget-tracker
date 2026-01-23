<?php
require 'session_check.php';
require 'db_connect.php';

$user_id = $_SESSION['user_id'];

// DATE LOGIC
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');
$last_month_start = date('Y-m-01', strtotime('-1 month'));
$last_month_end = date('Y-m-t', strtotime('-1 month'));

// FETCH SPENDING (EXPENSE ONLY for comparison)
function getSpending($pdo, $uid, $start, $end)
{
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'expense' AND date BETWEEN ? AND ?");
    $stmt->execute([$uid, $start, $end]);
    return $stmt->fetchColumn() ?: 0;
}

$this_month_spending = getSpending($pdo, $user_id, $current_month_start, $current_month_end);
$last_month_spending = getSpending($pdo, $user_id, $last_month_start, $last_month_end);

// CALC DIFFERENCE
$diff = $this_month_spending - $last_month_spending;
$percent_change = ($last_month_spending > 0) ? ($diff / $last_month_spending) * 100 : 0;
$status_color = ($diff < 0) ? 'text-success' : 'text-danger'; // Less spending is good
$status_icon = ($diff < 0) ? 'fa-arrow-down' : 'fa-arrow-up';

// --- CHART DATA PREPARATION ---
// 1. Expense by Category
$cat_chart_stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM transactions WHERE user_id = ? AND type='expense' GROUP BY category");
$cat_chart_stmt->execute([$user_id]);
$cat_chart_data = $cat_chart_stmt->fetchAll(PDO::FETCH_ASSOC);

$cat_labels = [];
$cat_values = [];
foreach ($cat_chart_data as $row) {
    $cat_labels[] = $row['category'];
    $cat_values[] = $row['total'];
}

// 2. Monthly Spending Trend (Last 6 Months)
$trend_stmt = $pdo->prepare("
    SELECT DATE_FORMAT(date, '%Y-%m') as month, SUM(amount) as total 
    FROM transactions 
    WHERE user_id = ? AND type='expense' AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
    GROUP BY month 
    ORDER BY month ASC
");
$trend_stmt->execute([$user_id]);
$trend_data = $trend_stmt->fetchAll(PDO::FETCH_ASSOC);

$trend_labels = [];
$trend_values = [];
foreach ($trend_data as $row) {
    $trend_labels[] = date('M Y', strtotime($row['month'] . '-01'));
    $trend_values[] = $row['total'];
}
?>

<?php include 'header.php'; ?>

<div class="container-fluid">
    <h2 class="mb-4">Monthly Financial Report</h2>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5>Spending Comparison</h5>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted mb-1">Compared to Last Month</p>
                    <h3 class="<?php echo $status_color; ?>">
                        <i class="fas <?php echo $status_icon; ?>"></i>
                        <?php echo number_format(abs($percent_change), 1); ?>%
                    </h3>

                    <div class="d-flex justify-content-around mt-4">
                        <div>
                            <h6>This Month</h6>
                            <h4><?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?><?php echo number_format($this_month_spending, 2); ?>
                            </h4>
                        </div>
                        <div class="border-start"></div>
                        <div>
                            <h6>Last Month</h6>
                            <h4 class="text-muted">
                                <?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?><?php echo number_format($last_month_spending, 2); ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-6 fade-in-up" style="animation-delay: 0.1s;">
            <div class="glass-card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Expense Breakdown</h5>
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 fade-in-up" style="animation-delay: 0.2s;">
            <div class="glass-card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Monthly Spending Trend</h5>
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Existing Filters Section could be added here similar to previous reports.php -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> Use the sidebar "Transactions" tab to filter and export specific data
        ranges.
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Data from PHP
    const catLabels = <?php echo json_encode($cat_labels); ?>;
    const catValues = <?php echo json_encode($cat_values); ?>;
    const trendLabels = <?php echo json_encode($trend_labels); ?>;
    const trendValues = <?php echo json_encode($trend_values); ?>;

    // --- Category Pie Chart ---
    const ctxCat = document.getElementById('categoryChart');
    if (ctxCat) {
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catValues,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E7E9ED', '#8AC926'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }

    // --- Trend Bar Chart ---
    const ctxTrend = document.getElementById('trendChart');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'bar',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Total Expenses',
                    data: trendValues,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
</script>
</div>
</body>

</html>