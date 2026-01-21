<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'all';
$title = ($type == 'all') ? 'All Transactions' : ucfirst($type) . 's';

// Build Query
$sql = "SELECT * FROM transactions WHERE user_id = ?";
$params = [$user_id];

if ($type !== 'all') {
    $sql .= " AND type = ?";
    $params[] = $type;
}

$sql .= " ORDER BY date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>

<?php include 'header.php'; ?>

<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <?php echo $title; ?>
            </h4>
            <a href="index.php" class="btn btn-primary btn-sm">+ Add New</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">No records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($t['date']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($t['category']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($t['description']); ?>
                                    </td>
                                    <td class="<?php echo $t['type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?>
                                        <?php echo number_format($t['amount'], 2); ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="edit_transaction.php?id=<?php echo $t['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <a href="delete_transaction.php?id=<?php echo $t['id']; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to delete this?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div> <!-- End Main Content -->
</div> <!-- End Flex Wrapper -->
</body>

</html>