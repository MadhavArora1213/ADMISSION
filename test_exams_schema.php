<?php
require_once 'admin/db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM exams');
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n--- exam_courses ---\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'exam_%'");
foreach($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
    echo "Table: " . $row[0] . "\n";
    $q = $pdo->query("SHOW COLUMNS FROM " . $row[0]);
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo "  " . $col['Field'] . " - " . $col['Type'] . "\n";
    }
}
