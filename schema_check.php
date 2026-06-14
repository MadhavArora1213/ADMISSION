<?php
require_once __DIR__ . '/admin/db.php';
$res = $pdo->query('SHOW CREATE TABLE articles')->fetch(PDO::FETCH_ASSOC);
echo $res['Create Table'];
