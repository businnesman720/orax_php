<?php
include 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name_tr VARCHAR(255) NOT NULL,
        name_en VARCHAR(255) NOT NULL,
        image_path VARCHAR(255),
        instructions_tr TEXT,
        instructions_en TEXT,
        fields JSON,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        method_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        user_data JSON,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    echo "Tables created successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
