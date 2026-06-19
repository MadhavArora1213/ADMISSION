<?php
require_once 'admin/db.php';
require_once 'includes/course_helpers.php';
$course = loadCourseBySlug($pdo, 'mba');
print_r($course);
