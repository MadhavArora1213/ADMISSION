<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../admin/db.php';

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$term = '%' . $q . '%';
$results = [];

// Colleges
$clgStmt = $pdo->prepare("SELECT id, name, slug FROM colleges WHERE status='active' AND name LIKE ? ORDER BY is_featured DESC, overall_rating_avg DESC LIMIT 5");
$clgStmt->execute([$term]);
foreach ($clgStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $results[] = [
        'type'  => 'college',
        'label' => $r['name'],
        'url'   => '/ADMISSION/college/' . $r['slug'],
        'icon'  => 'ph-buildings'
    ];
}

// Exams
$exmStmt = $pdo->prepare("SELECT id, exam_name, exam_slug FROM exams WHERE status='active' AND exam_name LIKE ? ORDER BY applicants_last_year DESC LIMIT 5");
$exmStmt->execute([$term]);
foreach ($exmStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $results[] = [
        'type'  => 'exam',
        'label' => $r['exam_name'],
        'url'   => '/ADMISSION/exam/' . $r['exam_slug'],
        'icon'  => 'ph-graduation-cap'
    ];
}

// Courses
$crsStmt = $pdo->prepare("SELECT id, course_name, course_slug FROM courses WHERE status='active' AND course_name LIKE ? ORDER BY total_colleges_offering DESC LIMIT 5");
$crsStmt->execute([$term]);
foreach ($crsStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $results[] = [
        'type'  => 'course',
        'label' => $r['course_name'],
        'url'   => '/ADMISSION/course/' . $r['course_slug'],
        'icon'  => 'ph-book-open-text'
    ];
}

// Careers
$carStmt = $pdo->prepare("SELECT id, name, slug FROM careers WHERE name LIKE ? ORDER BY is_popular DESC, name ASC LIMIT 5");
$carStmt->execute([$term]);
foreach ($carStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $results[] = [
        'type'  => 'career',
        'label' => $r['name'],
        'url'   => '/ADMISSION/career/' . $r['slug'],
        'icon'  => 'ph-briefcase'
    ];
}

echo json_encode(['results' => $results]);
