<?php
// This script is intended to be run daily via a Cron Job or Task Scheduler
// Example: 0 0 * * * /usr/bin/php /path/to/cron_recurring.php

require 'db_connect.php';

$today_day = date('j'); // 1 to 31
$today_date = date('Y-m-d');

echo "Running Recurring Transactions Check for Date: $today_date (Day: $today_day)\n";

try {
    // 1. Fetch recurring transactions that match today's day AND haven't been processed today
    // We check if last_processed is NULL or not equal to today
    $stmt = $pdo->prepare("SELECT * FROM recurring_transactions WHERE day_of_month = ? AND (last_processed IS NULL OR last_processed != ?)");
    $stmt->execute([$today_day, $today_date]);
    $recurrings = $stmt->fetchAll();

    $count = 0;
    foreach ($recurrings as $rec) {
        $pdo->beginTransaction();

        try {
            // Insert into transactions table
            $ins = $pdo->prepare("INSERT INTO transactions (user_id, type, category, amount, date, description) VALUES (?, ?, ?, ?, ?, ?)");
            $description = $rec['description'] . " (Recurring)";
            $ins->execute([$rec['user_id'], $rec['type'], $rec['category'], $rec['amount'], $today_date, $description]);

            // Update last_processed
            $upd = $pdo->prepare("UPDATE recurring_transactions SET last_processed = ? WHERE id = ?");
            $upd->execute([$today_date, $rec['id']]);

            $pdo->commit();
            $count++;
            echo "Processed ID: " . $rec['id'] . " - " . $rec['description'] . "\n";
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "Failed ID: " . $rec['id'] . " - Error: " . $e->getMessage() . "\n";
        }
    }

    echo "Completed. Total processed: $count\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>