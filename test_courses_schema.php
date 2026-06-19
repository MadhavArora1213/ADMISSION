<?php
require_once 'admin/db.php';
$stmt = $pdo->query("SHOW TABLES LIKE 'course%'");
foreach($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
    echo "Table: " . $row[0] . "\n";
    $q = $pdo->query("SHOW COLUMNS FROM " . $row[0]);
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo "  " . $col['Field'] . " - " . $col['Type'] . "\n";
    }
}
