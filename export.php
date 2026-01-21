<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Access Denied");
}

$user_id = $_SESSION['user_id'];
$start_date = $_GET['start_date'] ?? '1970-01-01';
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$category = $_GET['category'] ?? '';

// Build Query
$sql = "SELECT date, type, category, amount, description FROM transactions WHERE user_id = ? AND date BETWEEN ? AND ?";
$params = [$user_id, $start_date, $end_date];

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

$sql .= " ORDER BY date DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Headers for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transactions_export.csv"');

    $output = fopen('php://output', 'w');

    // CSV Header Row
    fputcsv($output, ['Date', 'Type', 'Category', 'Amount', 'Description']);

    // Data Rows
    foreach ($transactions as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>