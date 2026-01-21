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

    <!-- Existing Filters Section could be added here similar to previous reports.php -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> Use the sidebar "Transactions" tab to filter and export specific data
        ranges.
    </div>

</div>
</div>
</body>

</html>