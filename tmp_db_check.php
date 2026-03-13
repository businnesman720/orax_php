<?php
include 'includes/db.php';
$stmt = $pdo->query('SHOW TABLES');
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $table = $row[0];
    echo "Table: $table\n";
    $cols = $pdo->query("SHOW COLUMNS FROM $table");
    while ($col = $cols->fetch(PDO::FETCH_ASSOC)) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}
?>
