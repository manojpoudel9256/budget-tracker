<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $category = trim($_POST['category']);
    $amount = $_POST['amount'];

    if (empty($category) || empty($amount)) {
        $_SESSION['error'] = "Category and Amount are required.";
    } else {
        try {
            // Check if budget for category already exists
            $stmt = $pdo->prepare("SELECT id FROM budgets WHERE user_id = ? AND category = ?");
            $stmt->execute([$user_id, $category]);

            if ($stmt->rowCount() > 0) {
                // Update existing
                $update = $pdo->prepare("UPDATE budgets SET amount = ? WHERE user_id = ? AND category = ?");
                $update->execute([$amount, $user_id, $category]);
                $_SESSION['success'] = "Budget updated for $category!";
            } else {
                // Insert new
                $insert = $pdo->prepare("INSERT INTO budgets (user_id, category, amount) VALUES (?, ?, ?)");
                $insert->execute([$user_id, $category, $amount]);
                $_SESSION['success'] = "Budget set for $category!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database Error: " . $e->getMessage();
        }
    }
    header("Location: index.php");
    exit;
}
?>