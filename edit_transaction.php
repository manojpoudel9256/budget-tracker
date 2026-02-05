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
    <title>Edit Transaction - Budget Tracker Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #0ba360 0%, #3cba92 100%);
            --danger-gradient: linear-gradient(135deg, #ff5858 0%, #f09819 100%);
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .edit-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .edit-card {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .edit-header {
            background: var(--primary-gradient);
            padding: 32px;
            text-align: center;
            color: white;
        }

        .edit-header .icon-circle {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .edit-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .edit-header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .edit-body {
            padding: 32px;
        }

        .form-floating>.form-control,
        .form-floating>.form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            height: auto;
            padding: 1rem;
        }

        .form-floating>.form-control:focus,
        .form-floating>.form-select:focus {
            border-color: #764ba2;
            box-shadow: 0 0 0 4px rgba(118, 75, 162, 0.1);
        }

        .form-floating>label {
            padding: 1rem;
        }

        .type-toggle {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .type-toggle .btn {
            flex: 1;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            border: 2px solid #e2e8f0;
            background: white;
            color: #64748b;
            transition: all 0.3s ease;
        }

        .type-toggle .btn.active-expense {
            background: var(--danger-gradient);
            color: white;
            border-color: transparent;
            box-shadow: 0 10px 20px rgba(255, 88, 88, 0.25);
        }

        .type-toggle .btn.active-income {
            background: var(--success-gradient);
            color: white;
            border-color: transparent;
            box-shadow: 0 10px 20px rgba(11, 163, 96, 0.25);
        }

        .btn-save {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(118, 75, 162, 0.3);
        }

        .btn-cancel {
            border-radius: 12px;
            padding: 14px;
            font-weight: 500;
        }

        @media (max-width: 576px) {
            .edit-container {
                padding: 0;
            }

            .edit-card {
                border-radius: 0;
                min-height: 100vh;
            }

            .edit-header {
                padding: 24px;
            }

            .edit-body {
                padding: 24px;
            }
        }
    </style>
</head>

<body>
    <div class="edit-container">
        <div class="edit-card">
            <div class="edit-header">
                <div class="icon-circle">
                    <i class="fas fa-edit fa-lg"></i>
                </div>
                <h2>Edit Transaction</h2>
                <p>Update your transaction details</p>
            </div>
            <div class="edit-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Type Toggle -->
                    <div class="type-toggle">
                        <button type="button"
                            class="btn <?php echo ($transaction['type'] == 'expense') ? 'active-expense' : ''; ?>"
                            onclick="setType('expense')">
                            <i class="fas fa-arrow-up me-2"></i> Expense
                        </button>
                        <button type="button"
                            class="btn <?php echo ($transaction['type'] == 'income') ? 'active-income' : ''; ?>"
                            onclick="setType('income')">
                            <i class="fas fa-arrow-down me-2"></i> Income
                        </button>
                    </div>
                    <input type="hidden" name="type" id="transactionType" value="<?php echo $transaction['type']; ?>">

                    <!-- Amount - Large Input -->
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">
                            <i class="fas fa-coins me-2"></i> Amount
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0 rounded-start-3"
                                style="border: 2px solid #e2e8f0; border-right: none;">
                                <?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?>
                            </span>
                            <input type="number" step="1" name="amount"
                                class="form-control form-control-lg border-start-0 rounded-end-3"
                                style="border: 2px solid #e2e8f0; border-left: none; font-size: 1.5rem; font-weight: 700;"
                                value="<?php echo number_format($transaction['amount'], 0, '.', ''); ?>" required>
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="form-floating mb-3">
                        <input type="text" name="category" class="form-control" id="category"
                            value="<?php echo htmlspecialchars($transaction['category']); ?>" placeholder="e.g. Food"
                            required>
                        <label for="category"><i class="fas fa-tag me-2 text-muted"></i> Category</label>
                    </div>

                    <!-- Date -->
                    <div class="form-floating mb-3">
                        <input type="date" name="date" class="form-control" id="date"
                            value="<?php echo htmlspecialchars($transaction['date']); ?>" required>
                        <label for="date"><i class="fas fa-calendar me-2 text-muted"></i> Date</label>
                    </div>

                    <!-- Description -->
                    <div class="form-floating mb-4">
                        <textarea name="description" class="form-control" id="description" style="height: 80px"
                            placeholder="Notes"><?php echo htmlspecialchars($transaction['description']); ?></textarea>
                        <label for="description"><i class="fas fa-sticky-note me-2 text-muted"></i> Notes
                            (Optional)</label>
                    </div>

                    <!-- Buttons -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-save">
                            <i class="fas fa-check me-2"></i> Save Changes
                        </button>
                        <a href="view_transactions.php?type=<?php echo $transaction['type']; ?>"
                            class="btn btn-outline-secondary btn-cancel">
                            <i class="fas fa-times me-2"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setType(type) {
            document.getElementById('transactionType').value = type;
            document.querySelectorAll('.type-toggle .btn').forEach(btn => {
                btn.classList.remove('active-expense', 'active-income');
            });
            if (type === 'expense') {
                document.querySelector('.type-toggle .btn:first-child').classList.add('active-expense');
            } else {
                document.querySelector('.type-toggle .btn:last-child').classList.add('active-income');
            }
        }
    </script>
</body>

</html>