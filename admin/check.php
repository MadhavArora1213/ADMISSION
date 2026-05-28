<?php
require_once 'db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM universities LIKE 'publish_status'");
echo "Count: " . $stmt->rowCount();
?>
