<?php
require_once 'admin/db.php';
$stmt = $pdo->query('DESCRIBE leads');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
