<?php
require 'session_check.php';
require 'db_connect.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $category = trim($_POST['category']);
    $amount = $_POST['amount'];
    $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'index.php';

    if (empty($category) || empty($amount)) {
        $_SESSION['error'] = $lang['category_amount_required'];
    } else {
        try {
            // Check if budget for category already exists
            $stmt = $pdo->prepare("SELECT id FROM budgets WHERE user_id = ? AND category = ?");
            $stmt->execute([$user_id, $category]);

            if ($stmt->rowCount() > 0) {
                // Update existing
                $update = $pdo->prepare("UPDATE budgets SET amount = ? WHERE user_id = ? AND category = ?");
                $update->execute([$amount, $user_id, $category]);
                $_SESSION['success'] = sprintf($lang['budget_updated_msg'], htmlspecialchars($category));
            } else {
                // Insert new
                $insert = $pdo->prepare("INSERT INTO budgets (user_id, category, amount) VALUES (?, ?, ?)");
                $insert->execute([$user_id, $category, $amount]);
                $_SESSION['success'] = sprintf($lang['budget_set_msg'], htmlspecialchars($category));
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = $lang['db_error'] . $e->getMessage();
        }
    }
    header("Location: " . $redirect_to);
    exit;
}
?>