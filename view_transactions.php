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

// --- FETCH CATEGORIES FOR AUTOCOMPLETE ---
$cat_list_stmt = $pdo->prepare("SELECT name, type FROM categories WHERE user_id = ? ORDER BY name ASC");
$cat_list_stmt->execute([$user_id]);
$all_categories = $cat_list_stmt->fetchAll(PDO::FETCH_ASSOC);

$categories_by_type = ['income' => [], 'expense' => []];
foreach ($all_categories as $cat) {
    if (isset($categories_by_type[$cat['type']])) {
        $categories_by_type[$cat['type']][] = $cat['name'];
    }
}
?>

<?php include 'header.php'; ?>

<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <?php echo $title; ?>
            </h4>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTransactionModal">+ Add
                New</button>
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

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Transaction</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <form action="add_transaction.php" method="POST">
                <input type="hidden" name="redirect_to" value="view_transactions.php">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Type</label>
                        <select name="type" id="transactionType" class="form-select" required
                            onchange="updateCategoryOptions()">
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-control" list="categoryList" required autocomplete="off">
                        <datalist id="categoryList">
                            <!-- Options populated by JS -->
                        </datalist>
                    </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const categoriesByType = <?php echo json_encode($categories_by_type); ?>;

    function updateCategoryOptions() {
        const typeSelect = document.getElementById('transactionType');
        const list = document.getElementById('categoryList');
        const selectedType = typeSelect.value;

        list.innerHTML = ''; // Clear existing options

        if (categoriesByType[selectedType]) {
            categoriesByType[selectedType].forEach(catName => {
                const option = document.createElement('option');
                option.value = catName;
                list.appendChild(option);
            });
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', updateCategoryOptions);
</script>
</div> <!-- End Main Content -->
</div> <!-- End Flex Wrapper -->
</body>

</html>