<?php
require_once 'admin/db.php';
$q = $pdo->query('SELECT course_name FROM college_courses LIMIT 5');
print_r($q->fetchAll(PDO::FETCH_ASSOC));
