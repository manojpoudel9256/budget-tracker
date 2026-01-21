<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];
$error = '';

// FETCH EXISTING DATA
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        die("Transaction not found or access denied.");
    }
} else {
    // If no ID is passed, redirect back
    header("Location: index.php");
    exit;
}

// HANDLE UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];
    $category = trim($_POST['category']);
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    $description = trim($_POST['description']);

    if (empty($type) || empty($category) || empty($amount) || empty($date)) {
        $error = "All fields except description are required.";
    } else {
        try {
            $update = $pdo->prepare("UPDATE transactions SET type=?, category=?, amount=?, date=?, description=? WHERE id=? AND user_id=?");
            $update->execute([$type, $category, $amount, $date, $description, $id, $user_id]);
            header("Location: view_transactions.php?type=$type&msg=updated");
            exit;
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction - Budget Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Edit Transaction</h5>
                </div>
                <div class="card-body">
                    <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="expense" <?php echo ($transaction['type'] == 'expense') ? 'selected' : ''; ?>>Expense</option>
                                <option value="income" <?php echo ($transaction['type'] == 'income') ? 'selected' : ''; ?>>Income</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($transaction['category']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo htmlspecialchars($transaction['amount']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($transaction['date']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($transaction['description']); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Update Transaction</button>
                        <a href="view_transactions.php?type=<?php echo $transaction['type']; ?>" class="btn btn-link w-100 text-center mt-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
