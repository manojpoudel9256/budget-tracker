<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get existing categories for datalist
$cat_stmt = $pdo->prepare("SELECT DISTINCT category FROM transactions WHERE user_id = ? AND type = 'expense'");
$cat_stmt->execute([$user_id]);
$expense_categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get existing budgets
$budget_stmt = $pdo->prepare("SELECT category, amount FROM budgets WHERE user_id = ?");
$budget_stmt->execute([$user_id]);
$existing_budgets = $budget_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Budget Goal - Budget Tracker Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #0ba360 0%, #3cba92 100%);
        }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .budget-card {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .budget-header {
            background: var(--success-gradient);
            padding: 32px;
            text-align: center;
            color: white;
        }
        
        .budget-header .icon-circle {
            width: 72px;
            height: 72px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        
        .budget-header h2 { margin: 0; font-weight: 700; font-size: 1.5rem; }
        .budget-header p { margin: 8px 0 0; opacity: 0.9; font-size: 0.9rem; }
        
        .budget-body { padding: 32px; }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 14px 16px;
            font-size: 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #0ba360;
            box-shadow: 0 0 0 4px rgba(11, 163, 96, 0.1);
        }
        
        .form-control-lg {
            font-size: 1.25rem;
            padding: 16px;
        }
        
        .amount-input {
            font-size: 2rem !important;
            font-weight: 700;
            text-align: center;
        }
        
        .btn-save {
            background: var(--success-gradient);
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(11, 163, 96, 0.3);
        }
        
        .btn-cancel {
            border-radius: 12px;
            padding: 14px;
            font-weight: 500;
        }
        
        .existing-budgets {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .budget-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .budget-item:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 576px) {
            .page-container { padding: 0; }
            .budget-card { border-radius: 0; min-height: 100vh; }
            .budget-header { padding: 24px; }
            .budget-body { padding: 24px; }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="budget-card">
            <div class="budget-header">
                <div class="icon-circle">
                    <i class="fas fa-bullseye fa-2x"></i>
                </div>
                <h2>Set Budget Goal</h2>
                <p>Control your monthly spending</p>
            </div>
            <div class="budget-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($existing_budgets)): ?>
                <div class="existing-budgets">
                    <h6 class="text-muted small fw-bold text-uppercase mb-3">
                        <i class="fas fa-list me-2"></i>Current Budgets
                    </h6>
                    <?php foreach ($existing_budgets as $b): ?>
                    <div class="budget-item">
                        <span class="fw-medium"><?php echo htmlspecialchars($b['category']); ?></span>
                        <span class="fw-bold text-success">
                            <?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?><?php echo number_format($b['amount']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <form action="set_budget.php" method="POST">
                    <input type="hidden" name="redirect_to" value="set_budget_page.php">
                    
                    <!-- Category -->
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">
                            <i class="fas fa-tag me-1"></i> Category
                        </label>
                        <input type="text" name="category" class="form-control form-control-lg" 
                            list="categoryList" placeholder="e.g. Food, Entertainment" required autocomplete="off">
                        <datalist id="categoryList">
                            <?php foreach ($expense_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <!-- Amount -->
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">
                            <i class="fas fa-coins me-1"></i> Monthly Limit
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light" style="border: 2px solid #e2e8f0; border-right: 0; border-radius: 12px 0 0 12px; font-size: 1.5rem; font-weight: 600;">
                                <?php echo $_SESSION['currency'] == 'USD' ? '$' : $_SESSION['currency']; ?>
                            </span>
                            <input type="number" step="1" name="amount" class="form-control amount-input" 
                                placeholder="10000" required style="border: 2px solid #e2e8f0; border-left: 0; border-radius: 0 12px 12px 0;">
                        </div>
                        <small class="text-muted mt-2 d-block">Set a spending limit for this category</small>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-save text-white">
                            <i class="fas fa-check me-2"></i> Set Budget
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary btn-cancel">
                            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
