<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Default to current month stats for specific dashboard view
$start_date = date('Y-m-01');
$end_date = date('Y-m-d');

// --- QUICK STATS ---
$stmt = $pdo->prepare("SELECT type, SUM(amount) as total FROM transactions WHERE user_id = ? GROUP BY type");
$stmt->execute([$user_id]);
$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['income' => 1000, 'expense' => 500]

$total_income = $stats['income'] ?? 0;
$total_expense = $stats['expense'] ?? 0;
$balance = $total_income - $total_expense;

// --- RECENT TRANSACTIONS (Limit 5) ---
$recent_stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC LIMIT 5");
$recent_stmt->execute([$user_id]);
$recent = $recent_stmt->fetchAll();

// --- BUDGETS ---
$budget_stmt = $pdo->prepare("SELECT * FROM budgets WHERE user_id = ?");
$budget_stmt->execute([$user_id]);
$budgets = $budget_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate simplified budget progress (Total Spent by Category Ever vs Limit)
// Note: In a real app, you'd likely sum expenses only for the CURRENT MONTH
$cat_stmt = $pdo->prepare("SELECT category, SUM(amount) as spent FROM transactions WHERE user_id = ? AND type='expense' AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE()) GROUP BY category");
$cat_stmt->execute([$user_id]);
$current_month_spending = $cat_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$budget_alerts = [];
?>

<?php include 'header.php'; ?>

<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-3">Dashboard</h2>
        </div>
        <div class="col-md-4 fade-in-up" style="animation-delay: 0.1s;">
            <div class="glass-card h-100">
                <div class="card-body text-center">
                    <h5>Total Balance</h5>
                    <h2 class="fw-bold">
                        <?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?><?php echo number_format($balance, 2); ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 fade-in-up" style="animation-delay: 0.2s;">
            <div class="glass-card h-100">
                <div class="card-body">
                    <h5 class="text-success">Total Income</h5>
                    <h3><?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?><?php echo number_format($total_income, 2); ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 fade-in-up" style="animation-delay: 0.3s;">
            <div class="glass-card h-100">
                <div class="card-body">
                    <h5 class="text-danger">Total Expense</h5>
                    <h3><?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?><?php echo number_format($total_expense, 2); ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Transactions -->
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Activity</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                        data-bs-target="#addTransactionModal">+ Add New</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $t): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($t['date']); ?></td>
                                    <td><?php echo htmlspecialchars($t['category']); ?></td>
                                    <td><?php echo htmlspecialchars($t['description']); ?></td>
                                    <td
                                        class="text-end <?php echo $t['type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $t['type'] == 'income' ? '+' : '-'; ?>
                                        <?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?>
                                        <?php echo number_format($t['amount'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($recent)): ?>
                        <div class="p-3 text-center text-muted">No recent activity.</div><?php endif; ?>
                </div>
                <div class="card-footer bg-white text-center">
                    <a href="view_transactions.php?type=all" class="text-decoration-none">View All Transactions</a>
                </div>
            </div>
        </div>

        <!-- Monthly Budgets -->
        <div class="col-lg-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Monthly Budgets</h5>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                        data-bs-target="#setBudgetModal">Set Goal</button>
                </div>
                <div class="card-body">
                    <?php if (empty($budgets)): ?>
                        <p class="text-muted text-center">No budgets set.</p>
                    <?php else: ?>
                        <?php foreach ($budgets as $b):
                            $cat = $b['category'];
                            $limit = $b['amount'];
                            $spent = $current_month_spending[$cat] ?? 0;
                            $percent = ($limit > 0) ? ($spent / $limit) * 100 : 0;
                            $color = $percent < 75 ? 'bg-success' : ($percent < 90 ? 'bg-warning' : 'bg-danger');
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo htmlspecialchars($cat); ?></span>
                                    <small><?php echo number_format($spent, 0); ?> /
                                        <?php echo number_format($limit, 0); ?></small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar <?php echo $color; ?>"
                                        style="width: <?php echo min($percent, 100); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Transaction Modal (Same as before) -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Transaction</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <form action="add_transaction.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Type</label><select name="type" class="form-select"
                            required>
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select></div>
                    <div class="mb-3"><label class="form-label">Category</label><input type="text" name="category"
                            class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01"
                            name="amount" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Date</label><input type="date" name="date"
                            class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description"
                            class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Close</button><button type="submit"
                        class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Set Budget Modal -->
<div class="modal fade" id="setBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Budget Goal</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <form action="set_budget.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Category</label><input type="text" name="category"
                            class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Limit ($)</label><input type="number" step="0.01"
                            name="amount" class="form-control" required></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Set
                        Budget</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div> <!-- End Main Content -->
</div> <!-- End Flex Wrapper -->
</body>

</html>