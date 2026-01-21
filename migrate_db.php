<?php
require 'db_connect.php';

try {
    echo "Starting Database Migration (Fixing Missing Columns)...<br>";

    // 1. Add columns to 'users' table
    $columns = [
        "currency" => "VARCHAR(10) DEFAULT 'USD'",
        "profile_image" => "VARCHAR(255) DEFAULT 'default.png'"
    ];

    foreach ($columns as $col => $def) {
        // Check if column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
        $stmt->execute([$col]);
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
            echo "Added column '$col' to 'users'.<br>";
        } else {
            echo "Column '$col' already exists in 'users'.<br>";
        }
    }

    // 2. Create 'categories' table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(50) NOT NULL,
        type ENUM('income', 'expense') NOT NULL,
        color VARCHAR(20) DEFAULT '#6c757d',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Checked/Created 'categories' table.<br>";

    // 3. Add 'category_id' to 'transactions' table
    $stmt = $pdo->prepare("SHOW COLUMNS FROM transactions LIKE 'category_id'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN category_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE transactions ADD CONSTRAINT fk_trans_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");
        echo "Added 'category_id' column to 'transactions'.<br>";
    } else {
        echo "'category_id' already exists in 'transactions'.<br>";
    }

    echo "<hr><strong>Migration Completed Successfully!</strong><br>";
    echo "<a href='seed_data.php'>Retry Seeding Data</a><br>";
    echo "<a href='index.php'>Go to Dashboard</a>";

} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage();
}
?>