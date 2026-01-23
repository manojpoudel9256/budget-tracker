<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'];
    $category = trim($_POST['category']);
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    $description = trim($_POST['description']);

    if (empty($type) || empty($category) || empty($amount) || empty($date)) {
        $_SESSION['error'] = "All fields except description are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, category, amount, date, description) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $type, $category, $amount, $date, $description])) {
                $_SESSION['success'] = "Transaction added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add transaction.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database Error: " . $e->getMessage();
        }
    }

    $redirect_to = $_POST['redirect_to'] ?? 'index.php';
    header("Location: " . $redirect_to);
    exit;
}
?>