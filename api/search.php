<?php
declare(strict_types=1);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../admin/db.php';

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 1) {
    echo json_encode(['results' => []]);
    exit;
}

$like = '%' . $q . '%';
$results = [];

function relevanceScore(string $text, string $query): int {
    $lower = mb_strtolower($text);
    $q = mb_strtolower($query);
    if ($lower === $q) return 1;
    if (mb_strpos($lower, $q) === 0) return 2;
    if (str_contains($lower, $q)) return 3;
    return 4;
}

try {
    $stmt = $pdo->prepare("SELECT id, name, slug FROM colleges WHERE status='active' AND name LIKE ? ORDER BY is_featured DESC, overall_rating_avg DESC LIMIT 5");
    $stmt->execute([$like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $results[] = [
            'type' => 'college', 'label' => $r['name'],
            'url' => '/ADMISSION/college/' . $r['slug'], 'icon' => 'ph-buildings',
            'title' => $r['name'], 'subtitle' => '', 'badge' => 'College',
            'relevance' => relevanceScore($r['name'], $q),
        ];
    }
} catch (Exception $e) {}

try {
    $stmt = $pdo->prepare("SELECT id, exam_name, exam_slug, exam_abbreviation FROM exams WHERE status='active' AND exam_name LIKE ? ORDER BY applicants_last_year DESC LIMIT 5");
    $stmt->execute([$like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $abbr = $r['exam_abbreviation'] ? ' (' . $r['exam_abbreviation'] . ')' : '';
        $results[] = [
            'type' => 'exam', 'label' => $r['exam_name'] . $abbr,
            'url' => '/ADMISSION/exam/' . $r['exam_slug'], 'icon' => 'ph-clipboard-text',
            'title' => $r['exam_name'] . $abbr, 'subtitle' => '', 'badge' => $r['exam_abbreviation'] ?: 'Exam',
            'relevance' => relevanceScore($r['exam_name'], $q),
        ];
    }
} catch (Exception $e) {}

try {
    $stmt = $pdo->prepare("SELECT id, course_name, course_slug FROM courses WHERE status='active' AND course_name LIKE ? ORDER BY total_colleges_offering DESC LIMIT 5");
    $stmt->execute([$like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $results[] = [
            'type' => 'course', 'label' => $r['course_name'],
            'url' => '/ADMISSION/course/' . $r['course_slug'], 'icon' => 'ph-books',
            'title' => $r['course_name'], 'subtitle' => '', 'badge' => 'Course',
            'relevance' => relevanceScore($r['course_name'], $q),
        ];
    }
} catch (Exception $e) {}

try {
    $stmt = $pdo->prepare("SELECT id, name, slug FROM careers WHERE name LIKE ? ORDER BY is_popular DESC, name ASC LIMIT 5");
    $stmt->execute([$like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $results[] = [
            'type' => 'career', 'label' => $r['name'],
            'url' => '/ADMISSION/career/' . $r['slug'], 'icon' => 'ph-briefcase',
            'title' => $r['name'], 'subtitle' => '', 'badge' => 'Career',
            'relevance' => relevanceScore($r['name'], $q),
        ];
    }
} catch (Exception $e) {}

usort($results, fn($a, $b) => ($a['relevance'] ?? 99) - ($b['relevance'] ?? 99));

echo json_encode(['results' => $results]);
