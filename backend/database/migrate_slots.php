<?php
require_once __DIR__ . '/../api/config/database.php';

try {
    $db = (new Database())->getConnection();
    echo "Connected successfully to DB.\n";
    
    // Check if extra_slots column exists in users table
    $check = $db->query("SHOW COLUMNS FROM users LIKE 'extra_slots'");
    if ($check->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN extra_slots INT DEFAULT 0 AFTER role");
        echo "Column 'extra_slots' added to 'users' table successfully.\n";
    } else {
    }
    // Create transactions table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS transactions (
        order_id VARCHAR(100) PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        amount INT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        metadata TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "Transactions table checked/created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
