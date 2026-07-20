<?php
declare(strict_types=1);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../panel_cms_2847/db.php';

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
            'url' => BASE_URL . '/college/' . urlencode($r['slug']), 'icon' => 'ph-buildings',
            'title' => $r['name'], 'subtitle' => '', 'badge' => 'College',
            'relevance' => relevanceScore($r['name'], $q),
        ];
    }
} catch (Exception $e) {}

try {
    $stmt = $pdo->prepare("SELECT id, name, slug FROM schools WHERE status='active' AND name LIKE ? ORDER BY is_featured DESC, overall_rating_avg DESC LIMIT 5");
    $stmt->execute([$like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $results[] = [
            'type' => 'school', 'label' => $r['name'],
            'url' => BASE_URL . '/school/' . urlencode($r['slug']), 'icon' => 'ph-graduation-cap',
            'title' => $r['name'], 'subtitle' => '', 'badge' => 'School',
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
            'url' => BASE_URL . '/exam/' . urlencode($r['exam_slug']), 'icon' => 'ph-clipboard-text',
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
            'url' => BASE_URL . '/course/' . urlencode($r['course_slug']), 'icon' => 'ph-books',
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
            'url' => BASE_URL . '/career/' . urlencode($r['slug']), 'icon' => 'ph-briefcase',
            'title' => $r['name'], 'subtitle' => '', 'badge' => 'Career',
            'relevance' => relevanceScore($r['name'], $q),
        ];
    }
} catch (Exception $e) {}

usort($results, fn($a, $b) => ($a['relevance'] ?? 99) - ($b['relevance'] ?? 99));

echo json_encode(['results' => $results]);

// ── Log search to analytics ──
if (mb_strlen($q) >= 2) {
    try {
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $device = 'desktop';
        if (preg_match('/mobile|android|iphone/i', $ua)) $device = 'mobile';
        elseif (preg_match('/tablet|ipad/i', $ua)) $device = 'tablet';

        $ins = $pdo->prepare("INSERT INTO search_queries (id, query_text, results_count, zero_results, device_type, session_id) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([$uuid, $q, count($results), count($results) === 0 ? 1 : 0, $device, session_id() ?: null]);

        $pdo->prepare("INSERT INTO search_trending (query_text, trending_score, trending_period) VALUES (?, 1, 'daily') ON DUPLICATE KEY UPDATE trending_score = trending_score + 1")->execute([$q]);
    } catch (Exception $e) {}
}
