<?php
require 'db.php';
$stmt = $pdo->query('DESCRIBE universities');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
