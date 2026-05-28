<?php
require 'db.php';
$stmt = $pdo->query("SHOW TABLES LIKE '%review%'");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in DB: " . implode(", ", $tables);
?>
