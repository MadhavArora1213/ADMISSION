<?php
require_once 'db.php';
$stmt = $pdo->prepare("SELECT * FROM colleges WHERE id = 35");
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));
