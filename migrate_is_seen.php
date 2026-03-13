<?php
$host = 'localhost';
$db   = 'orax';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ATTR_ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_MODE_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     $pdo->exec("ALTER TABLE payment_requests ADD COLUMN is_seen TINYINT(1) DEFAULT 0");
     echo "Column 'is_seen' added successfully.";
} catch (\PDOException $e) {
     if ($e->getCode() == '42S21') {
         echo "Column 'is_seen' already exists.";
     } else {
         echo "Error: " . $e->getMessage();
     }
}
