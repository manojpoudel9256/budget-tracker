<?php
require 'session_check.php';
require 'db_connect.php';

$user_id = $_SESSION['user_id'];

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'all';
$title_key = ($type == 'all') ? 'all_transactions' : (($type == 'income') ? 'income' : 'expenses');
$title = $lang[$title_key];

// Query Logic
$query = "SELECT * FROM transactions WHERE user_id = ?";
$params = [$user_id];

if ($type == 'income') {
    $query .= " AND type = 'income'";
} elseif ($type == 'expense') {
    $query .= " AND type = 'expense'";
}

$query .= " ORDER BY date DESC, id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Collections for Filters/Modal
$cat_stmt = $pdo->prepare("SELECT DISTINCT category, type FROM transactions WHERE user_id = ?");
$cat_stmt->execute([$user_id]);
$all_cats = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$categories_by_type = ['income' => [], 'expense' => []];
foreach ($all_cats as $c) {
    if ($c['type'] == 'income')
        $categories_by_type['income'][] = $c['category'];
    else
        $categories_by_type['expense'][] = $c['category'];
}
?>

<?php include 'header.php'; ?>
<style>
    /* ... (Styles remain same) ... */
</style>

<div class="container-fluid px-0 px-md-3">
    <div class="glass-card mb-4 mt-0">
        <!-- FILTER TABS (Segmented Control) -->
        <div class="px-3 px-md-0 mb-4 mt-0">
            <div class="d-flex p-1 bg-white rounded-4 border shadow-sm" style="position: relative;">
                <a href="?type=all"
                    class="btn border-0 flex-fill py-2 rounded-3 fw-bold small <?php echo $type == 'all' ? 'shadow-sm text-white' : 'text-muted'; ?>"
                    style="<?php echo $type == 'all' ? 'background: var(--primary-gradient);' : 'background: transparent;'; ?>">
                    <?php echo $lang['all']; ?>
                </a>
                <a href="?type=income"
                    class="btn border-0 flex-fill py-2 rounded-3 fw-bold small <?php echo $type == 'income' ? 'shadow-sm text-white' : 'text-muted'; ?>"
                    style="<?php echo $type == 'income' ? 'background: var(--income-gradient);' : 'background: transparent;'; ?>">
                    <?php echo $lang['income']; ?>
                </a>
                <a href="?type=expense"
                    class="btn border-0 flex-fill py-2 rounded-3 fw-bold small <?php echo $type == 'expense' ? 'shadow-sm text-white' : 'text-muted'; ?>"
                    style="<?php echo $type == 'expense' ? 'background: var(--expense-gradient);' : 'background: transparent;'; ?>">
                    <?php echo $lang['expense']; ?>
                </a>
            </div>
        </div>

        <!-- Desktop Table View -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover mb-0" style="background: transparent;">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th><?php echo $lang['date']; ?></th>
                        <th><?php echo $lang['category']; ?></th>
                        <th><?php echo $lang['description']; ?></th>
                        <th><?php echo $lang['amount']; ?></th>
                        <th class="text-end"><?php echo $lang['actions']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted"><?php echo $lang['no_records_found']; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['date']); ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?php echo htmlspecialchars($t['category']); ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?php echo htmlspecialchars($t['description']); ?></td>
                                <td class="fw-bold <?php echo $t['type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?>
                                    <?php echo number_format($t['amount'], 0); ?>
                                </td>
                                <td class="text-end">
                                    <a href="edit_transaction.php?id=<?php echo $t['id']; ?>"
                                        class="btn btn-sm btn-light border" title="<?php echo $lang['edit']; ?>"><i
                                            class="fas fa-edit"></i></a>
                                    <a href="delete_transaction.php?id=<?php echo $t['id']; ?>"
                                        class="btn btn-sm btn-light border text-danger"
                                        onclick="return confirm('<?php echo $lang['confirm_delete']; ?>');"
                                        title="<?php echo $lang['delete']; ?>"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile List View -->
        <div class="d-md-none">
            <?php if (empty($transactions)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-receipt fa-3x mb-3 opacity-25"></i>
                    <p><?php echo $lang['no_transactions_found']; ?></p>
                </div>
            <?php else: ?>
                <?php
                $delay = 0;
                $currency_symbol = $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency'];
                foreach ($transactions as $t):
                    $delay += 0.05; // Faster stagger
                    $isIncome = strtolower($t['type']) === 'income';
                    $iconBg = $isIncome ? 'var(--income-gradient)' : 'var(--expense-gradient)';
                    $iconClass = $isIncome ? 'fa-arrow-down' : 'fa-arrow-up';

                    // Premium Icon Mapping
                    $catLower = strtolower($t['category']);
                    if (str_contains($catLower, 'food') || str_contains($catLower, 'eat') || str_contains($catLower, 'restaur'))
                        $iconClass = 'fa-utensils';
                    elseif (str_contains($catLower, 'transport') || str_contains($catLower, 'bus') || str_contains($catLower, 'uber') || str_contains($catLower, 'gas'))
                        $iconClass = 'fa-car';
                    elseif (str_contains($catLower, 'shop') || str_contains($catLower, 'store') || str_contains($catLower, 'cloth'))
                        $iconClass = 'fa-shopping-bag';
                    elseif (str_contains($catLower, 'enter') || str_contains($catLower, 'movi') || str_contains($catLower, 'fun'))
                        $iconClass = 'fa-film';
                    elseif (str_contains($catLower, 'bill') || str_contains($catLower, 'util') || str_contains($catLower, 'elect'))
                        $iconClass = 'fa-bolt';
                    elseif (str_contains($catLower, 'medic') || str_contains($catLower, 'health'))
                        $iconClass = 'fa-heartbeat';
                    elseif (str_contains($catLower, 'hous') || str_contains($catLower, 'rent'))
                        $iconClass = 'fa-home';
                    elseif (str_contains($catLower, 'sal') || str_contains($catLower, 'wage') || str_contains($catLower, 'income'))
                        $iconClass = 'fa-wallet';
                    ?>
                    <div class="glass-card mb-3 p-3 fade-in-up position-relative overflow-hidden"
                        style="animation-delay: <?php echo $delay; ?>s; border-radius: 16px; border: 1px solid rgba(255,255,255,0.6); box-shadow: 0 4px 15px rgba(0,0,0,0.03);">

                        <div class="d-flex align-items-center justify-content-between">
                            <!-- Left: Icon & Info -->
                            <div class="d-flex align-items-center gap-3">
                                <!-- Gradient Icon Box -->
                                <div class="d-flex align-items-center justify-content-center text-white shadow-sm"
                                    style="min-width: 50px; width: 50px; height: 50px; border-radius: 14px; background: <?php echo $iconBg; ?>;">
                                    <i class="fas <?php echo $iconClass; ?> fa-lg"></i>
                                </div>

                                <!-- Text Info -->
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($t['category']); ?></h6>
                                    <small class="text-muted" style="font-size: 0.75rem; font-weight: 500;">
                                        <?php echo date('M d, Y', strtotime($t['date'])); ?>
                                    </small>
                                </div>
                            </div>

                            <!-- Right: Amount -->
                            <div class="text-end">
                                <h6 class="fw-bold mb-0 <?php echo $isIncome ? 'text-success' : 'text-danger'; ?>"
                                    style="font-size: 1.1rem;">
                                    <?php echo $isIncome ? '+' : '-'; ?>         <?php echo $currency_symbol; ?>
                                    <?php echo number_format($t['amount'], 0); ?>
                                </h6>
                                <!-- Action Icons -->
                                <div class="d-flex justify-content-end gap-3 mt-2">
                                    <a href="edit_transaction.php?id=<?php echo $t['id']; ?>"
                                        class="text-muted opacity-50 hover-opacity-100 text-decoration-none"
                                        style="transition: opacity 0.2s;">
                                        <i class="fas fa-pencil-alt fa-sm"></i>
                                    </a>
                                    <a href="delete_transaction.php?id=<?php echo $t['id']; ?>"
                                        class="text-muted opacity-50 hover-opacity-100 text-decoration-none"
                                        onclick="return confirm('<?php echo $lang['delete_transaction']; ?>');"
                                        style="transition: opacity 0.2s;">
                                        <i class="fas fa-trash fa-sm"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($t['description'])): ?>
                            <div class="mt-2 pt-2 border-top border-light">
                                <?php if (strlen($t['description']) > 45): ?>
                                    <p class="mb-0 text-muted small fst-italic">
                                        <i class="fas fa-info-circle me-1 small opacity-50"></i>
                                        <span id="note-<?php echo $t['id']; ?>-short">
                                            <?php echo htmlspecialchars(substr($t['description'], 0, 42)) . '...'; ?>
                                            <span class="text-primary fw-bold ms-1" style="cursor: pointer;"
                                                onclick="document.getElementById('note-<?php echo $t['id']; ?>-short').classList.add('d-none'); document.getElementById('note-<?php echo $t['id']; ?>-full').classList.remove('d-none');">
                                                more
                                            </span>
                                        </span>
                                        <span id="note-<?php echo $t['id']; ?>-full" class="d-none animate-enter">
                                            <?php echo htmlspecialchars($t['description']); ?>
                                        </span>
                                    </p>
                                <?php else: ?>
                                    <p class="mb-0 text-muted small fst-italic text-truncate">
                                        <i class="fas fa-info-circle me-1 small opacity-50"></i>
                                        <?php echo htmlspecialchars($t['description']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Floating Action Button -->
    <a href="add_transaction_page.php?redirect_to=view_transactions.php" class="fab-btn d-md-none">
        <i class="fas fa-plus"></i>
    </a>
</div>

<!-- Add Transaction Modal (Premium) -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg">
            <!-- Modal Header with Gradient -->
            <div class="modal-header border-0 text-white py-4"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="w-100 text-center">
                    <div class="mb-2">
                        <span
                            class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25"
                            style="width: 48px; height: 48px;">
                            <i class="fas fa-plus-circle"></i>
                        </span>
                    </div>
                    <h5 class="modal-title fw-bold mb-0">New Transaction</h5>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                    data-bs-dismiss="modal"></button>
            </div>
            <form action="add_transaction.php" method="POST">
                <input type="hidden" name="redirect_to" value="view_transactions.php?type=all">
                <div class="modal-body p-4">
                    <!-- Type Toggle -->
                    <div class="d-flex gap-2 mb-4">
                        <button type="button" class="btn flex-fill py-3 rounded-3 type-btn" data-type="expense"
                            onclick="selectType('expense', this)"
                            style="background: linear-gradient(135deg, #ff5858 0%, #f09819 100%); color: white; border: 2px solid transparent; box-shadow: 0 10px 20px rgba(255, 88, 88, 0.25);">
                            <i class="fas fa-arrow-up me-2"></i> Expense
                        </button>
                        <button type="button" class="btn flex-fill py-3 rounded-3 type-btn" data-type="income"
                            onclick="selectType('income', this)"
                            style="background: white; color: #64748b; border: 2px solid #e2e8f0;">
                            <i class="fas fa-arrow-down me-2"></i> Income
                        </button>
                    </div>
                    <input type="hidden" name="type" id="transactionType" value="expense">

                    <!-- Amount -->
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Amount</label>
                        <div class="input-group input-group-lg">
                            <span
                                class="input-group-text bg-light border-end-0 rounded-start-3"><?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?></span>
                            <input type="number" step="1" name="amount"
                                class="form-control form-control-lg border-start-0 rounded-end-3 fw-bold"
                                placeholder="0" required style="font-size: 1.25rem;">
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">Category</label>
                        <input type="text" name="category" class="form-control form-control-lg rounded-3"
                            list="categoryList" placeholder="e.g. Food, Transport" required autocomplete="off">
                        <datalist id="categoryList">
                            <!-- Options populated by JS -->
                        </datalist>
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">Date</label>
                        <input type="date" name="date" class="form-control form-control-lg rounded-3"
                            value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">Notes (Optional)</label>
                        <textarea name="description" class="form-control rounded-3" rows="2"
                            placeholder="Add a note..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                        <i class="fas fa-check me-2"></i> Add Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    const categoriesByType = <?php echo json_encode($categories_by_type); ?>;

    // Type Toggle for Add Transaction Modal
    function selectType(type, btn) {
        document.getElementById('transactionType').value = type;

        // Update button styles
        document.querySelectorAll('.type-btn').forEach(b => {
            b.style.background = 'white';
            b.style.color = '#64748b';
            b.style.border = '2px solid #e2e8f0';
            b.style.boxShadow = 'none';
        });

        if (type === 'expense') {
            btn.style.background = 'linear-gradient(135deg, #ff5858 0%, #f09819 100%)';
            btn.style.color = 'white';
            btn.style.border = '2px solid transparent';
            btn.style.boxShadow = '0 10px 20px rgba(255, 88, 88, 0.25)';
        } else {
            btn.style.background = 'linear-gradient(135deg, #0ba360 0%, #3cba92 100%)';
            btn.style.color = 'white';
            btn.style.border = '2px solid transparent';
            btn.style.boxShadow = '0 10px 20px rgba(11, 163, 96, 0.25)';
        }

        // Update category list
        updateCategoryOptions();
    }

    function updateCategoryOptions() {
        const typeInput = document.getElementById('transactionType');
        const list = document.getElementById('categoryList');
        if (!typeInput || !list) return;
        const selectedType = typeInput.value;

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
<?php include 'footer.php'; ?>