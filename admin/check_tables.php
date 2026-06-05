<?php
require_once 'db.php';
$stmt = $pdo->query('SHOW TABLES');
while($r = $stmt->fetch(PDO::FETCH_NUM)){ echo $r[0]."\n"; }
