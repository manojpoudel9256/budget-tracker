<?php
require 'session_check.php';
require 'db_connect.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'];
    $category = trim($_POST['category']);
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    $description = trim($_POST['description']);

    if (empty($type) || empty($category) || empty($amount) || empty($date)) {
        $_SESSION['error'] = $lang['fill_all_fields'];
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, category, amount, date, description) VALUES (?, ?, ?, ?,
?, ?)");
            if ($stmt->execute([$user_id, $type, $category, $amount, $date, $description])) {
                $_SESSION['success'] = $lang['transaction_added'];
            } else {
                $_SESSION['error'] = $lang['transaction_failed'];
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = $lang['db_error'] . $e->getMessage();
        }
    }

    $redirect_to = $_POST['redirect_to'] ?? 'index.php';
    header("Location: " . $redirect_to);
    exit;
}
?>