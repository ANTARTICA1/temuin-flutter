<?php
require_once __DIR__ . '/../api/config/database.php';

try {
    $db = (new Database())->getConnection();
    echo "Connected successfully to DB.\n";

    // 1. reward_amount
    $check = $db->query("SHOW COLUMNS FROM items LIKE 'reward_amount'");
    if ($check->rowCount() == 0) {
        $db->exec("ALTER TABLE items ADD COLUMN reward_amount INT DEFAULT 0 AFTER is_premium");
        echo "Column 'reward_amount' added.\n";
    } else {
        echo "Column 'reward_amount' exists.\n";
    }

    // 2. reward_description
    $check = $db->query("SHOW COLUMNS FROM items LIKE 'reward_description'");
    if ($check->rowCount() == 0) {
        $db->exec("ALTER TABLE items ADD COLUMN reward_description VARCHAR(255) NULL AFTER reward_amount");
        echo "Column 'reward_description' added.\n";
    } else {
        echo "Column 'reward_description' exists.\n";
    }

    // 3. last_seen_location
    $check = $db->query("SHOW COLUMNS FROM items LIKE 'last_seen_location'");
    if ($check->rowCount() == 0) {
        $db->exec("ALTER TABLE items ADD COLUMN last_seen_location VARCHAR(255) NULL AFTER reward_description");
        echo "Column 'last_seen_location' added.\n";
    } else {
        echo "Column 'last_seen_location' exists.\n";
    }

    // 4. contact_phone
    $check = $db->query("SHOW COLUMNS FROM items LIKE 'contact_phone'");
    if ($check->rowCount() == 0) {
        $db->exec("ALTER TABLE items ADD COLUMN contact_phone VARCHAR(50) NULL AFTER last_seen_location");
        echo "Column 'contact_phone' added.\n";
    } else {
        echo "Column 'contact_phone' exists.\n";
    }

    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
