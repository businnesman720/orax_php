<?php
include 'includes/db.php';
try {
    // Add missing account_details columns
    $pdo->exec("ALTER TABLE payment_methods ADD COLUMN IF NOT EXISTS account_details_tr TEXT AFTER instructions_en");
    $pdo->exec("ALTER TABLE payment_methods ADD COLUMN IF NOT EXISTS account_details_en TEXT AFTER account_details_tr");
    
    // Check if status exists, if not rename is_active or add status
    $stmt = $pdo->query("DESCRIBE payment_methods");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('status', $cols)) {
        if (in_array('is_active', $cols)) {
            $pdo->exec("ALTER TABLE payment_methods CHANGE COLUMN is_active status TINYINT(1) DEFAULT 1");
        } else {
            $pdo->exec("ALTER TABLE payment_methods ADD COLUMN status TINYINT(1) DEFAULT 1");
        }
    }
    
    echo "DB Schema updated successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
