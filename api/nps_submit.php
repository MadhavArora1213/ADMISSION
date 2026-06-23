<?php
declare(strict_types=1);
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../admin/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$score = (int)($input['score'] ?? 0);

if ($score < 1 || $score > 10) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Score must be 1-10']);
    exit;
}

$userId      = $_SESSION['user_id'] ?? null;
$articleId   = $input['article_id'] ?? null;
$articleSlug = $input['article_slug'] ?? null;
$pageUrl     = $_SERVER['HTTP_REFERER'] ?? ($input['page_url'] ?? '');
$userAgent   = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
$ipAddress   = $_SERVER['REMOTE_ADDR'] ?? '';

// Check if already submitted (by user_id if logged in, else by IP in last 24h)
if ($userId) {
    $check = $pdo->prepare("SELECT id FROM nps_feedback WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1");
    $check->execute([$userId]);
} else {
    $check = $pdo->prepare("SELECT id FROM nps_feedback WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1");
    $check->execute([$ipAddress]);
}
if ($check->fetch()) {
    echo json_encode(['ok' => true, 'message' => 'Already submitted', 'duplicate' => true]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO nps_feedback (score, user_id, article_id, article_slug, page_url, user_agent, ip_address)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$score, $userId, $articleId, $articleSlug, $pageUrl, $userAgent, $ipAddress]);

echo json_encode(['ok' => true, 'message' => 'Thank you for your feedback!']);
