<?php
require 'db.php';
$cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
