<?php
require_once 'admin/db.php';
$q = $pdo->query('SELECT course_slug, course_name, description, eligibility, career_scope FROM courses');
print_r($q->fetchAll(PDO::FETCH_ASSOC));
