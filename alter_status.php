<?php
require_once __DIR__ . '/config/env.php';

try {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance()->getConnection();
    
    $sql = "ALTER TABLE registrations MODIFY COLUMN status ENUM('registered','cancelled','attended','pending_verification') NOT NULL DEFAULT 'registered'";
    $db->exec($sql);
    
    echo "Successfully altered registrations.status ENUM\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
