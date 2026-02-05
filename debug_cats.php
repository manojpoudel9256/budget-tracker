<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$user_id = $_SESSION['user_id'];
echo "<h1>Debug Category Fetching</h1>";

$sql = "
    SELECT DISTINCT name, type FROM (
        SELECT name, type FROM categories WHERE user_id = ?
        UNION
        SELECT category as name, type FROM transactions WHERE user_id = ?
    ) as combined_categories
    ORDER BY type, name
";

$cat_stmt = $pdo->prepare($sql);
$cat_stmt->execute([$user_id, $user_id]);
$all_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Raw Data:</h3>";
echo "<pre>";
print_r($all_categories);
echo "</pre>";

$categories_by_type = ['income' => [], 'expense' => []];
foreach ($all_categories as $cat) {
    // Current logic (simulated)
    $categories_by_type[$cat['type']][] = $cat['name'];
}

echo "<h3>Grouped Data (Current Logic):</h3>";
echo "<pre>";
print_r($categories_by_type);
echo "</pre>";

echo "<h3>Normalized Logic (Proposed Fix):</h3>";
$normalized = ['income' => [], 'expense' => []];
foreach ($all_categories as $cat) {
    if (empty($cat['name']))
        continue;
    $type = strtolower($cat['type']);
    $normalized[$type][] = $cat['name'];
}
print_r($normalized);
?>