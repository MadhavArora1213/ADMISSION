<?php
require_once 'admin/db.php';
$q = $pdo->query('SHOW COLUMNS FROM college_courses');
foreach($q->fetchAll(PDO::FETCH_ASSOC) as $c) echo $c['Field']."\n";
