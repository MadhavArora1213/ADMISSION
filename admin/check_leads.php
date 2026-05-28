<?php
require 'db.php';
$stmt = $pdo->query("SHOW TABLES LIKE '%lead%'");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Lead tables: " . implode(", ", $tables);
?>
