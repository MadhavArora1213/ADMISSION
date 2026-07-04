<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../admin/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing action parameter']);
    exit;
}

$action = $input['action'];
$validActions = ['accepted_all', 'rejected_all', 'custom', 'closed'];
if (!in_array($action, $validActions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$pageUrl = $input['page_url'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
$sessionId = session_id() ?: null;
$userId = $_SESSION['user_id'] ?? null;

// Parse user agent
$browser = 'Unknown';
$os = 'Unknown';
$deviceType = 'desktop';

if ($ua) {
    // Browser
    if (stripos($ua, 'Firefox') !== false) $browser = 'Firefox';
    elseif (stripos($ua, 'Edg') !== false) $browser = 'Edge';
    elseif (stripos($ua, 'Chrome') !== false) $browser = 'Chrome';
    elseif (stripos($ua, 'Safari') !== false) $browser = 'Safari';
    elseif (stripos($ua, 'Opera') !== false || stripos($ua, 'OPR') !== false) $browser = 'Opera';

    // OS
    if (stripos($ua, 'Windows') !== false) $os = 'Windows';
    elseif (stripos($ua, 'Mac OS') !== false) $os = 'macOS';
    elseif (stripos($ua, 'Linux') !== false) $os = 'Linux';
    elseif (stripos($ua, 'Android') !== false) $os = 'Android';
    elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) $os = 'iOS';

    // Device
    if (stripos($ua, 'Mobile') !== false || stripos($ua, 'Android') !== false || stripos($ua, 'iPhone') !== false) {
        $deviceType = 'mobile';
    } elseif (stripos($ua, 'iPad') !== false || stripos($ua, 'Tablet') !== false) {
        $deviceType = 'tablet';
    }
}

// Cookie preferences
$necessary = 1;
$analytics = ($action === 'accepted_all' || ($action === 'custom' && !empty($input['analytics']))) ? 1 : 0;
$marketing = ($action === 'accepted_all' || ($action === 'custom' && !empty($input['marketing']))) ? 1 : 0;
$preferences = ($action === 'accepted_all' || ($action === 'custom' && !empty($input['preferences']))) ? 1 : 0;

try {
    $stmt = $pdo->prepare("INSERT INTO cookie_consents 
        (session_id, user_id, ip_address, user_agent, consent_action, necessary, analytics, marketing, preferences, page_url, device_type, browser, os)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $sessionId,
        $userId,
        $ip,
        $ua,
        $action,
        $necessary,
        $analytics,
        $marketing,
        $preferences,
        $pageUrl,
        $deviceType,
        $browser,
        $os
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    error_log("Cookie consent save error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save consent']);
}
