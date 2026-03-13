<?php
include 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE videos ADD COLUMN quality VARCHAR(20) DEFAULT 'HD'");
    echo "Column 'quality' added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
