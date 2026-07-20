<?php
declare(strict_types=1);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../panel_cms_2847/db.php';

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 1) {
    echo json_encode(['ok' => true, 'results' => [], 'total' => 0]);
    exit;
}

$like = '%' . $q . '%';
$prefix = $q . '%';
$results = [];
$total = 0;

function relevanceScore(string $text, string $query): int {
    $lower = mb_strtolower($text);
    $q = mb_strtolower($query);
    if ($lower === $q) return 1;
    if (mb_strpos($lower, $q) === 0) return 2;
    if (str_contains($lower, $q)) return 3;
    $words = explode(' ', $query);
    foreach ($words as $w) {
        if (mb_strlen($w) >= 2 && str_contains($lower, mb_strtolower($w))) return 4;
    }
    return 5;
}

function logSearchQuery(PDO $pdo, string $query, int $resultCount): void {
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

        $sid = session_id();
        $uid = $_SESSION['user_id'] ?? null;

        $stmt = $pdo->prepare("INSERT INTO search_queries (id, query_text, results_count, zero_results, device_type, session_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$uuid, $query, $resultCount, $resultCount === 0 ? 1 : 0, $device, $sid ?: null, $uid]);

        $pdo->prepare("INSERT INTO search_trending (query_text, trending_score, trending_period) VALUES (?, 1, 'daily') ON DUPLICATE KEY UPDATE trending_score = trending_score + 1")->execute([$query]);
    } catch (Exception $e) {}
}

// ── Colleges (name, city, state, type) ──
try {
    $stmt = $pdo->prepare("
        SELECT c.name, c.slug, c.college_type, c.naac_grade, c.ranking_nirf, c.id,
               ci.name AS city, s.name AS state
        FROM colleges c
        LEFT JOIN cities ci ON c.city_id = ci.id
        LEFT JOIN states s ON c.state_id = s.id
        WHERE c.status = 'active'
          AND (c.name LIKE ? OR ci.name LIKE ? OR s.name LIKE ? OR c.college_type LIKE ?)
        ORDER BY c.is_featured DESC, c.overall_rating_avg DESC
        LIMIT 6
    ");
    $stmt->execute([$like, $like, $like, $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $meta = $r['city'] ? $r['city'] . ($r['state'] ? ', ' . $r['state'] : '') : ($r['state'] ?? '');
        $badge = $r['naac_grade'] ? 'NAAC ' . $r['naac_grade'] : ($r['ranking_nirf'] ? 'NIRF #' . $r['ranking_nirf'] : ucfirst($r['college_type'] ?? ''));
        $rel = relevanceScore($r['name'], $q);
        $results[] = [
            'type' => 'college',
            'icon' => 'ph-buildings',
            'title' => $r['name'],
            'subtitle' => $meta,
            'badge' => $badge,
            'url' => BASE_URL . '/college/' . urlencode($r['slug']),
            'relevance' => $rel,
        ];
        $total++;
    }
} catch (Exception $e) {}

// ── Exams (name, abbreviation, conducting body) ──
try {
    $stmt = $pdo->prepare("
        SELECT exam_name, exam_abbreviation, exam_slug, exam_level, exam_mode, applicants_last_year, conducting_body
        FROM exams
        WHERE status != 'cancelled'
          AND (exam_name LIKE ? OR exam_abbreviation LIKE ? OR conducting_body LIKE ?)
        ORDER BY applicants_last_year DESC
        LIMIT 5
    ");
    $stmt->execute([$like, $like, $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $badge = strtoupper($r['exam_abbreviation'] ?? mb_substr($r['exam_name'], 0, 4));
        $abbr = $r['exam_abbreviation'] ? ' (' . $r['exam_abbreviation'] . ')' : '';
        $subtitle = ucfirst($r['exam_level'] ?? '') . ' Level';
        $rel = min(relevanceScore($r['exam_name'], $q), $r['exam_abbreviation'] ? relevanceScore($r['exam_abbreviation'], $q) : 99);
        $results[] = [
            'type' => 'exam',
            'icon' => 'ph-clipboard-text',
            'title' => $r['exam_name'] . $abbr,
            'subtitle' => $subtitle,
            'badge' => $badge,
            'url' => BASE_URL . '/exam/' . urlencode($r['exam_slug']),
            'relevance' => $rel,
        ];
        $total++;
    }
} catch (Exception $e) {}

// ── Courses (name, category, level) ──
try {
    $stmt = $pdo->prepare("
        SELECT course_name, course_slug, course_level, course_category, avg_salary_lpa, total_colleges_offering
        FROM courses
        WHERE status = 'active'
          AND (course_name LIKE ? OR course_category LIKE ? OR course_level LIKE ?)
        ORDER BY total_colleges_offering DESC
        LIMIT 5
    ");
    $stmt->execute([$like, $like, $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $badge = $r['course_level'] ?? 'UG';
        $meta = $r['course_category'] ?: ($r['total_colleges_offering'] ? $r['total_colleges_offering'] . '+ colleges' : '');
        $rel = min(relevanceScore($r['course_name'], $q), $r['course_category'] ? relevanceScore($r['course_category'], $q) : 99);
        $results[] = [
            'type' => 'course',
            'icon' => 'ph-books',
            'title' => $r['course_name'],
            'subtitle' => $badge . ($meta ? ' · ' . $meta : ''),
            'badge' => $badge,
            'url' => BASE_URL . '/course/' . urlencode($r['course_slug']),
            'relevance' => $rel,
        ];
        $total++;
    }
} catch (Exception $e) {}

// ── Careers (name, stream, sub_stream) ──
try {
    $stmt = $pdo->prepare("
        SELECT name, slug, stream, sub_stream, salary_range, is_popular
        FROM careers
        WHERE name LIKE ? OR stream LIKE ? OR sub_stream LIKE ? OR skills_required LIKE ?
        ORDER BY is_popular DESC, name ASC
        LIMIT 4
    ");
    $stmt->execute([$like, $like, $like, $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $badge = $r['stream'] ?? '';
        $subtitle = $r['sub_stream'] ?? ($r['salary_range'] ?? '');
        $rel = relevanceScore($r['name'], $q);
        $results[] = [
            'type' => 'career',
            'icon' => 'ph-briefcase',
            'title' => $r['name'],
            'subtitle' => $subtitle,
            'badge' => $badge,
            'url' => BASE_URL . '/career/' . urlencode($r['slug']),
            'relevance' => $rel,
        ];
        $total++;
    }
} catch (Exception $e) {}

// ── Articles / News (title, type) ──
try {
    $stmt = $pdo->prepare("
        SELECT article_title, article_slug, article_type, publish_at
        FROM articles
        WHERE status = 'published'
          AND article_title LIKE ?
        ORDER BY publish_at DESC
        LIMIT 4
    ");
    $stmt->execute([$like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $typeLabel = ucwords(str_replace('_', ' ', $r['article_type']));
        $date = $r['publish_at'] ? date('d M Y', strtotime($r['publish_at'])) : '';
        $rel = relevanceScore($r['article_title'], $q);
        $results[] = [
            'type' => 'article',
            'icon' => 'ph-newspaper',
            'title' => $r['article_title'],
            'subtitle' => $typeLabel . ($date ? ' · ' . $date : ''),
            'badge' => $typeLabel,
            'url' => BASE_URL . '/news_details.php?slug=' . $r['article_slug'],
            'relevance' => $rel,
        ];
        $total++;
    }
} catch (Exception $e) {}

// ── Questions / Q&A (question text, category) ──
try {
    $stmt = $pdo->prepare("
        SELECT question_text, slug, question_category, views, answer_count
        FROM questions
        WHERE status IN ('open','answered')
          AND (question_text LIKE ? OR question_category LIKE ?)
        ORDER BY views DESC
        LIMIT 4
    ");
    $stmt->execute([$like, $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $title = mb_strimwidth($r['question_text'], 0, 80, '...');
        $meta = $r['answer_count'] . ' answers · ' . number_format($r['views']) . ' views';
        $rel = relevanceScore($r['question_text'], $q);
        $results[] = [
            'type' => 'question',
            'icon' => 'ph-chat-circle-question',
            'title' => $title,
            'subtitle' => $meta,
            'badge' => $r['question_category'] ?: 'Q&A',
            'url' => BASE_URL . '/question/' . urlencode($r['slug']),
            'relevance' => $rel,
        ];
        $total++;
    }
} catch (Exception $e) {}

// ── Foreign Universities (name, country) ──
try {
    $stmt = $pdo->prepare("
        SELECT university_name, university_slug, country, qs_rank, city
        FROM foreign_universities
        WHERE university_name LIKE ? OR country LIKE ? OR city LIKE ?
        ORDER BY qs_rank ASC
        LIMIT 3
    ");
    $stmt->execute([$like, $like, $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $badge = $r['qs_rank'] ? 'QS #' . $r['qs_rank'] : '';
        $subtitle = $r['city'] ? $r['city'] . ', ' . $r['country'] : $r['country'];
        $rel = relevanceScore($r['university_name'], $q);
        $results[] = [
            'type' => 'university',
            'icon' => 'ph-globe-hemisphere-west',
            'title' => $r['university_name'],
            'subtitle' => $subtitle,
            'badge' => $badge,
            'url' => BASE_URL . '/foreign-university.php?slug=' . $r['university_slug'],
            'relevance' => $rel,
        ];
        $total++;
    }
} catch (Exception $e) {}

// Sort by relevance
usort($results, function($a, $b) {
    return ($a['relevance'] ?? 99) - ($b['relevance'] ?? 99);
});

logSearchQuery($pdo, $q, $total);

echo json_encode(['ok' => true, 'results' => $results, 'total' => $total]);
