<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        // Redirect back to the previous page's referer or default to dashboard
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        // Basic check to prevent open redirect vulnerabilities (optional but good practice)
        if (strpos($redirect, 'view_transactions.php') === false && strpos($redirect, 'index.php') === false) {
            $redirect = 'index.php';
        }

        // Check if query param exists in redirect, append correctly
        if (strpos($redirect, '?') !== false) {
            header("Location: " . $redirect . "&msg=deleted");
        } else {
            header("Location: " . $redirect . "?msg=deleted");
        }
        exit;

    } catch (PDOException $e) {
        header("Location: index.php?msg=error");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>