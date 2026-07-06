<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

require_once __DIR__ . '/../panel_cms_2847/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['college_id']) || empty($input['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing college_id or action']);
    exit;
}

$collegeId  = substr($input['college_id'], 0, 36);
$action     = substr($input['action'], 0, 50);
$pageUrl    = substr($input['url'] ?? '', 0, 500);
$courseId   = !empty($input['course_id']) ? (int)$input['course_id'] : null;
$courseName = substr($input['course_name'] ?? '', 0, 255);
$tabName    = substr($input['tab'] ?? '', 0, 100);
$timeOn     = max(0, (int)($input['time'] ?? 0));
$scroll     = max(0, min(100, (int)($input['scroll'] ?? 0)));
$extra      = $input['extra'] ?? null;
$userId     = $_SESSION['user_id'] ?? null;
$sessionId  = session_id() ?: null;

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$device = 'desktop';
if (preg_match('/mobile|android|iphone|ipod/i', $ua)) $device = 'mobile';
elseif (preg_match('/tablet|ipad/i', $ua)) $device = 'tablet';

$vid = $input['vid'] ?? '';
if (empty($vid)) $vid = md5($ua . ($sessionId ?: 'anon'));

$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

$validActions = ['page_view','course_view','tab_switch','apply_click','brochure_download','call_click','shortlist','share','review_read','faq_toggle'];
if (!in_array($action, $validActions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO college_activity (id, college_id, user_id, visitor_id, session_id, action_type, page_url, course_id, course_name, tab_name, time_on_page, scroll_depth, extra_data, device_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $uuid, $collegeId, $userId, $vid, $sessionId,
        $action, $pageUrl, $courseId, $courseName, $tabName,
        $timeOn, $scroll, $extra ? json_encode($extra) : null, $device
    ]);

    // If user is logged in and action is apply_click, also create a lead
    if ($action === 'apply_click' && $userId) {
        try {
            $userStmt = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            if ($user && !empty($user['phone'])) {
                $leadUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $leadStmt = $pdo->prepare("INSERT INTO leads (id, user_id, lead_type, source_page, college_id, course_id, phone, email, city, lead_status) VALUES (?, ?, 'apply', ?, ?, ?, ?, ?, ?, 'new') ON DUPLICATE KEY UPDATE lead_status = lead_status");
                $leadStmt->execute([
                    $leadUuid, $userId, $pageUrl, $collegeId, $courseId,
                    $user['phone'], $user['email'], ''
                ]);
            }
        } catch (Exception $e) {}
    }

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false]);
}
