<?php
require_once 'admin/db.php';
$q = $pdo->query('SELECT id, course_name, course_slug, course_level, duration_years, avg_salary_lpa, total_colleges_offering FROM courses');
print_r($q->fetchAll(PDO::FETCH_ASSOC));
