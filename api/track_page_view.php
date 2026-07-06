<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

require_once __DIR__ . '/../panel_cms_2847/db.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['url'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing url']);
    exit;
}

$pageUrl   = substr($input['url'], 0, 500);
$pageTitle = substr($input['title'] ?? '', 0, 500);
$referrer  = substr($input['referrer'] ?? $_SERVER['HTTP_REFERER'] ?? '', 0, 500);
$visitorId = substr($input['vid'] ?? '', 0, 64);
$sessionId = session_id() ?: null;
$userId    = $_SESSION['user_id'] ?? null;
$timeOn    = max(0, (int)($input['time'] ?? 0));
$scroll    = max(0, min(100, (int)($input['scroll'] ?? 0)));
$ua        = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Device detection
$device = 'desktop';
if (preg_match('/mobile|android|iphone|ipod/i', $ua)) $device = 'mobile';
elseif (preg_match('/tablet|ipad/i', $ua)) $device = 'tablet';

// Browser detection
$browser = 'Other';
if (preg_match('/Edg\//i', $ua)) $browser = 'Edge';
elseif (preg_match('/Chrome/i', $ua)) $browser = 'Chrome';
elseif (preg_match('/Firefox/i', $ua)) $browser = 'Firefox';
elseif (preg_match('/Safari/i', $ua)) $browser = 'Safari';

// OS detection
$os = 'Other';
if (preg_match('/Windows/i', $ua)) $os = 'Windows';
elseif (preg_match('/Mac OS/i', $ua)) $os = 'macOS';
elseif (preg_match('/Linux/i', $ua)) $os = 'Linux';
elseif (preg_match('/Android/i', $ua)) $os = 'Android';
elseif (preg_match('/iPhone|iPad/i', $ua)) $os = 'iOS';

if (empty($visitorId)) {
    $visitorId = md5($ua . ($sessionId ?: 'anon'));
}

$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

try {
    $stmt = $pdo->prepare("INSERT INTO page_views (id, page_url, page_title, referrer, visitor_id, session_id, user_id, device_type, browser, os, time_on_page, scroll_depth) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$uuid, $pageUrl, $pageTitle, $referrer, $visitorId, $sessionId, $userId, $device, $browser, $os, $timeOn, $scroll]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false]);
}
