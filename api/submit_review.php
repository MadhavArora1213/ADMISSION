<?php
declare(strict_types=1);

error_reporting(0);
ini_set('display_errors', '0');

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Server error', 'detail' => $err['message']]);
    }
});

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_secure', '0');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'login_required', 'message' => 'Please login to write a review.']);
    exit;
}

require_once __DIR__ . '/../admin/db.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !is_array($input)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid request body']);
    exit;
}

$collegeId      = trim($input['college_id'] ?? '');
$overall        = (float)($input['overall_rating'] ?? 0);
$academics      = (float)($input['academics_rating'] ?? 0);
$faculty        = (float)($input['faculty_rating'] ?? 0);
$placements     = (float)($input['placements_rating'] ?? 0);
$infrastructure = (float)($input['infrastructure_rating'] ?? 0);
$campusLife     = (float)($input['campus_life_rating'] ?? 0);
$food           = (float)($input['food_rating'] ?? 0);
$title          = trim($input['review_title'] ?? '');
$body           = trim($input['review_body'] ?? '');
$pros           = trim($input['pros'] ?? '');
$cons           = trim($input['cons'] ?? '');
$batchYear      = (int)($input['batch_year'] ?? 0);
$courseId       = trim($input['course_id'] ?? '');

if ($collegeId === '') {
    echo json_encode(['ok' => false, 'error' => 'college_id is required']); exit;
}
if ($overall < 1 || $overall > 5) {
    echo json_encode(['ok' => false, 'error' => 'Overall rating must be between 1 and 5']); exit;
}
if ($title === '' && $body === '') {
    echo json_encode(['ok' => false, 'error' => 'Please provide a review title or body.']); exit;
}

$chk = $pdo->prepare("SELECT id FROM colleges WHERE id = ? AND status = 'active'");
$chk->execute([$collegeId]);
if (!$chk->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'College not found']); exit;
}

$userId = $_SESSION['user_id'];
$dup = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND college_id = ?");
$dup->execute([$userId, $collegeId]);
if ($dup->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'You have already reviewed this college.']); exit;
}

if ($courseId !== '') {
    $courseChk = $pdo->prepare("SELECT id FROM courses WHERE id = ?");
    $courseChk->execute([$courseId]);
    if (!$courseChk->fetch()) {
        $courseId = '';
    }
}

function uuid(): string {
    return sprintf('%08x-%04x-%04x-%04x-%012x',
        mt_rand(0, 0xffffffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffffffffffff));
}

function clampRating(float $v): float {
    return max(1.0, min(5.0, $v));
}

$reviewId = uuid();
$stmt = $pdo->prepare("
    INSERT INTO reviews (id, user_id, college_id, overall_rating, academics_rating, faculty_rating, placements_rating, infrastructure_rating, social_life_rating, food_rating, review_title, review_body, pros, cons, batch_year, course_id, moderation_status, helpful_votes, reported_count, fraud_flag, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, 0, 0, NOW(), NOW())
");
$stmt->execute([
    $reviewId,
    $userId,
    $collegeId,
    clampRating($overall),
    clampRating($academics),
    clampRating($faculty),
    clampRating($placements),
    clampRating($infrastructure),
    clampRating($campusLife),
    clampRating($food),
    $title !== '' ? $title : null,
    $body !== '' ? $body : null,
    $pros !== '' ? $pros : null,
    $cons !== '' ? $cons : null,
    $batchYear > 1950 && $batchYear < 2030 ? $batchYear : null,
    $courseId !== '' ? $courseId : null,
]);

try {
    $priority = 'medium';
    if ($overall <= 1.0) $priority = 'high';
    if ($body && preg_match('/(scam|fake|fraud|idiot|stupid|worthless)/i', $body)) $priority = 'critical';
    $slaHours = ['critical' => 4, 'high' => 12, 'medium' => 24, 'low' => 48];
    $slaDue = date('Y-m-d H:i:s', time() + ($slaHours[$priority] ?? 24) * 3600);
    $pdo->prepare("INSERT INTO moderation_queue (id, entity_type, entity_id, status, priority, flagged_reason, reporter_id, sla_due_at, created_at) VALUES (UUID(), 'review', ?, 'pending', ?, 'new_review', ?, ?, NOW())")
         ->execute([$reviewId, $priority, $userId, $slaDue]);
} catch (Exception $e) {}

echo json_encode([
    'ok' => true,
    'message' => 'Review submitted successfully! It will appear after moderation.',
    'review_id' => $reviewId,
]);
