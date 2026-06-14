<?php
$pdo = new PDO('mysql:host=localhost;dbname=admission;charset=utf8mb4', 'root', '');
print_r($pdo->query('SHOW COLUMNS FROM articles')->fetchAll(PDO::FETCH_ASSOC));
print_r($pdo->query('SHOW COLUMNS FROM article_categories')->fetchAll(PDO::FETCH_ASSOC));
?>
