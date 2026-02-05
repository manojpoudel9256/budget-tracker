<?php
require 'session_check.php';
require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$message = '';

// HANDLER: Add Category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $color = $_POST['color'];

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type, color) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $name, $type, $color])) {
            $message = "Category added successfully!";
            $msg_type = "success";
        } else {
            $message = "Error adding category.";
            $msg_type = "danger";
        }
    }
}

// HANDLER: Delete Category
if (isset($_GET['delete'])) {
    $cat_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$cat_id, $user_id])) {
        $message = "Category deleted.";
        $msg_type = "info";
    }
}

// FETCH CATEGORIES
$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY name ASC");
$stmt->execute([$user_id]);
$all_cats = $stmt->fetchAll();

$income_cats = [];
$expense_cats = [];
foreach ($all_cats as $c) {
    if ($c['type'] == 'income')
        $income_cats[] = $c;
    else
        $expense_cats[] = $c;
}
?>
<?php include 'header.php'; ?>

<div class="container-fluid px-0 px-md-3 pb-5">

    <!-- Title -->
    <div class="px-3 mb-4 mt-2 fade-in-up">
        <h2 class="fw-bold mb-0"
            style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            Manage Categories</h2>
        <p class="text-muted small fw-medium">Customize your financial labels.</p>
    </div>

    <?php if ($message): ?>
        <div class="px-3 mb-3 fade-in-up">
            <div class="alert alert-<?php echo $msg_type; ?> d-flex align-items-center rounded-4 border-0 shadow-sm"
                role="alert">
                <i class="fas fa-info-circle me-2"></i> <?php echo $message; ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="row px-3 g-4">

        <!-- ADD CATEGORY FORM -->
        <div class="col-12 col-lg-4 order-lg-2 mb-4">
            <div class="glass-card p-4 fade-in-up delay-1 sticky-lg-top" style="top: 100px; z-index: 1;">
                <h5 class="fw-bold mb-4"><i class="fas fa-plus-circle me-2 text-primary"></i>Create New</h5>

                <form method="POST">
                    <input type="hidden" name="action" value="add">

                    <!-- Type Toggle -->
                    <div class="bg-light p-1 rounded-3 d-flex mb-3">
                        <input type="radio" class="btn-check" name="type" id="typeExp" value="expense" checked>
                        <label class="btn btn-sm border-0 flex-fill rounded-3 fw-bold text-muted" for="typeExp"
                            onclick="toggleType('expense')">Expense</label>

                        <input type="radio" class="btn-check" name="type" id="typeInc" value="income">
                        <label class="btn btn-sm border-0 flex-fill rounded-3 fw-bold text-muted" for="typeInc"
                            onclick="toggleType('income')">Income</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text" name="name" class="form-control rounded-4 border-0 bg-light" id="catName"
                            placeholder="Name" required>
                        <label for="catName" class="text-muted">Category Name</label>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Color Tag</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" name="color"
                                class="form-control form-control-color border-0 rounded-circle p-0 shadow-sm"
                                value="#6366f1" title="Choose color"
                                style="width: 40px; height: 40px; cursor: pointer;">
                            <span class="text-muted small ms-2">Tap to pick a color</span>
                        </div>
                    </div>

                    <button type="submit" class="btn w-100 text-white fw-bold rounded-4 shadow-sm py-3"
                        style="background: var(--primary-gradient);">
                        <i class="fas fa-check me-2"></i> Save Category
                    </button>
                </form>
            </div>
        </div>

        <!-- CATEGORY LISTS -->
        <div class="col-12 col-lg-8 order-lg-1">

            <!-- EXPENSES -->
            <div class="glass-card p-0 mb-4 fade-in-up delay-2 overflow-hidden">
                <div class="p-4 border-bottom bg-white bg-opacity-50">
                    <h5 class="fw-bold mb-0 text-danger"><i class="fas fa-arrow-up me-2"></i>Expense Categories</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($expense_cats)): ?>
                        <div class="p-4 text-center text-muted">No custom expense categories yet.</div>
                    <?php else:
                        foreach ($expense_cats as $cat): ?>
                            <div
                                class="list-group-item bg-transparent border-bottom p-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle shadow-sm me-3"
                                        style="width: 12px; height: 12px; background-color: <?php echo htmlspecialchars($cat['color']); ?>;">
                                    </div>
                                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></span>
                                </div>
                                <a href="?delete=<?php echo $cat['id']; ?>"
                                    class="btn btn-light btn-sm rounded-circle text-danger shadow-sm"
                                    onclick="return confirm('Delete this category?')"
                                    style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-trash-alt fa-xs"></i>
                                </a>
                            </div>
                        <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- INCOME -->
            <div class="glass-card p-0 mb-4 fade-in-up delay-3 overflow-hidden">
                <div class="p-4 border-bottom bg-white bg-opacity-50">
                    <h5 class="fw-bold mb-0 text-success"><i class="fas fa-arrow-down me-2"></i>Income Categories</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($income_cats)): ?>
                        <div class="p-4 text-center text-muted">No custom income categories yet.</div>
                    <?php else:
                        foreach ($income_cats as $cat): ?>
                            <div
                                class="list-group-item bg-transparent border-bottom p-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle shadow-sm me-3"
                                        style="width: 12px; height: 12px; background-color: <?php echo htmlspecialchars($cat['color']); ?>;">
                                    </div>
                                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></span>
                                </div>
                                <a href="?delete=<?php echo $cat['id']; ?>"
                                    class="btn btn-light btn-sm rounded-circle text-danger shadow-sm"
                                    onclick="return confirm('Delete this category?')"
                                    style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-trash-alt fa-xs"></i>
                                </a>
                            </div>
                        <?php endforeach; endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* Custom Radio Toggle */
    .btn-check:checked+.btn {
        background-color: white;
        color: var(--primary-color);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .btn-check:not(:checked)+.btn:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
</style>

<script>
    function toggleType(type) {
        // Optional visual enhancements
    }
</script>

<?php include 'footer.php'; ?>