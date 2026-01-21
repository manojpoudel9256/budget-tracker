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
            $message = "Category added!";
        } else {
            $message = "Error adding category.";
        }
    }
}

// HANDLER: Delete Category
if (isset($_GET['delete'])) {
    $cat_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$cat_id, $user_id])) {
        $message = "Category deleted!";
    }
}

// FETCH CATEGORIES
$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY type, name");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll(PDO::FETCH_GROUP); // Groups by 'income'/'expense' if selected as first column? No, fetchAll defaults. 
// Let's re-fetch properly to group manually or just list them.
$income_cats = [];
$expense_cats = [];
foreach ($stmt->fetchAll() as $cat) { // fetchAll again is wrong if already fetched. Fix:
    // Re-execute or just fetch once.
}
// Correct Fetch
$stmt->execute([$user_id]);
$all_cats = $stmt->fetchAll();
foreach ($all_cats as $c) {
    if ($c['type'] == 'income')
        $income_cats[] = $c;
    else
        $expense_cats[] = $c;
}
?>

<?php include 'header.php'; ?>

<div class="container-fluid">
    <h2 class="mb-4">Manage Categories</h2>

    <?php if ($message): ?>
        <div class="alert alert-info">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- FORM -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5>Add New Category</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Color Tag</label>
                            <input type="color" name="color" class="form-control form-control-color" value="#6c757d">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Category</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- LIST -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5>My Categories</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-success border-bottom pb-2">Income Sources</h6>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($income_cats as $cat): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <span class="badge rounded-circle me-2"
                                                style="background-color: <?php echo $cat['color']; ?>; width: 10px; height: 10px; display:inline-block;"></span>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </span>
                                        <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-sm text-danger"
                                            onclick="return confirm('Delete this category?');"><i
                                                class="fas fa-trash"></i></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-danger border-bottom pb-2">Expense Categories</h6>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($expense_cats as $cat): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <span class="badge rounded-circle me-2"
                                                style="background-color: <?php echo $cat['color']; ?>; width: 10px; height: 10px; display:inline-block;"></span>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </span>
                                        <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-sm text-danger"
                                            onclick="return confirm('Delete this category?');"><i
                                                class="fas fa-trash"></i></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- End Main Content Div from Header -->
</div> <!-- End Flex Div from Header -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>