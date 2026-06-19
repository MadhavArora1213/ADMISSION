<?php
require_once 'admin/db.php';
$q = $pdo->query('SELECT id, course_slug, description FROM courses');
print_r($q->fetchAll(PDO::FETCH_ASSOC));
