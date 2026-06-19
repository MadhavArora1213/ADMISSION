<?php
require_once 'admin/db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM leads LIKE 'source'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
$stmt = $pdo->query("SHOW COLUMNS FROM leads LIKE 'lead_type'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
