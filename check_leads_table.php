<?php
require_once 'admin/db.php';
$stmt = $pdo->query('DESCRIBE leads');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
