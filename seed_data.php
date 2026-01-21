<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Please <a href='login.php'>login</a> first to seed data for your account.");
}

$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // 1. Create realistic categories
    $categories = [
        ['name' => 'Salary', 'type' => 'income', 'color' => '#2ecc71'],
        ['name' => 'Freelance', 'type' => 'income', 'color' => '#27ae60'],
        ['name' => 'Investments', 'type' => 'income', 'color' => '#1abc9c'],
        ['name' => 'Rent', 'type' => 'expense', 'color' => '#e74c3c'],
        ['name' => 'Groceries', 'type' => 'expense', 'color' => '#e67e22'],
        ['name' => 'Transport', 'type' => 'expense', 'color' => '#f1c40f'],
        ['name' => 'Entertainment', 'type' => 'expense', 'color' => '#9b59b6'],
        ['name' => 'Utilities', 'type' => 'expense', 'color' => '#34495e'],
        ['name' => 'Dining Out', 'type' => 'expense', 'color' => '#d35400'],
        ['name' => 'Shopping', 'type' => 'expense', 'color' => '#c0392b']
    ];

    $cat_map = []; // Name -> ID

    foreach ($categories as $cat) {
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ?");
        $stmt->execute([$user_id, $cat['name']]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $cat_map[$cat['name']] = $existing;
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type, color) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $cat['name'], $cat['type'], $cat['color']]);
            $cat_map[$cat['name']] = $pdo->lastInsertId();
        }
    }

    echo "Categories Synced...<br>";

    // 2. Clear old transactions for this demo (Optional: uncomment to wipe data)
    // $pdo->prepare("DELETE FROM transactions WHERE user_id = ?")->execute([$user_id]);

    // 3. Generate Transactions (This Month and Last Month)
    $transactions = [];

    // Dates
    $dates = [
        'this_month' => date('Y-m'),
        'last_month' => date('Y-m', strtotime('-1 month'))
    ];

    foreach ($dates as $period => $ym) {
        // Income
        $transactions[] = ['cat' => 'Salary', 'amount' => 5000, 'day' => '01', 'desc' => 'Monthly Salary'];
        $transactions[] = ['cat' => 'Freelance', 'amount' => rand(500, 1500), 'day' => '15', 'desc' => 'Web Design Project'];

        // Expenses
        $transactions[] = ['cat' => 'Rent', 'amount' => 1200, 'day' => '01', 'desc' => 'Apartment Rent'];
        $transactions[] = ['cat' => 'Utilities', 'amount' => rand(100, 200), 'day' => '05', 'desc' => 'Electric & Water'];
        $transactions[] = ['cat' => 'Groceries', 'amount' => rand(80, 150), 'day' => '03', 'desc' => 'Weekly Shop'];
        $transactions[] = ['cat' => 'Groceries', 'amount' => rand(80, 150), 'day' => '10', 'desc' => 'Weekly Shop'];
        $transactions[] = ['cat' => 'Groceries', 'amount' => rand(80, 150), 'day' => '17', 'desc' => 'Weekly Shop'];
        $transactions[] = ['cat' => 'Groceries', 'amount' => rand(80, 150), 'day' => '24', 'desc' => 'Weekly Shop'];
        $transactions[] = ['cat' => 'Transport', 'amount' => 50, 'day' => '02', 'desc' => 'Gas Refill'];
        $transactions[] = ['cat' => 'Transport', 'amount' => 50, 'day' => '16', 'desc' => 'Gas Refill'];
        $transactions[] = ['cat' => 'Dining Out', 'amount' => rand(30, 80), 'day' => '07', 'desc' => 'Dinner with friends'];
        $transactions[] = ['cat' => 'Dining Out', 'amount' => rand(30, 80), 'day' => '21', 'desc' => 'Lunch with client'];
        $transactions[] = ['cat' => 'Shopping', 'amount' => rand(50, 200), 'day' => '12', 'desc' => 'New Clothes'];
        $transactions[] = ['cat' => 'Entertainment', 'amount' => 15, 'day' => '14', 'desc' => 'Netflix Subscription'];

        foreach ($transactions as $t) {
            $cat_info = null;
            // Find type from category list
            foreach ($categories as $c) {
                if ($c['name'] === $t['cat']) {
                    $cat_info = $c;
                    break;
                }
            }

            if ($cat_info && isset($cat_map[$t['cat']])) {
                $date = $ym . '-' . $t['day'];
                // Only add if not in future (simple check)
                if ($date <= date('Y-m-d')) {
                    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, category_id, category, type, amount, date, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $user_id,
                        $cat_map[$t['cat']],
                        $t['cat'],
                        $cat_info['type'],
                        $t['amount'],
                        $date,
                        $t['desc']
                    ]);
                }
            }
        }
    }

    $pdo->commit();
    echo "Data Seeding Complete! <a href='index.php'>Go to Dashboard</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>