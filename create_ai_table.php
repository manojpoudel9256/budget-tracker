<?php
require 'db_connect.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS ai_insights (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        insight_text TEXT,
        type VARCHAR(50) DEFAULT 'general',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (created_at)
    )";

    $pdo->exec($sql);
    echo "Table 'ai_insights' created successfully (or already exists).";

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>