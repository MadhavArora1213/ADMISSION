<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../admin/db.php';
require_once __DIR__ . '/spam_detector.php';

$input = json_decode(file_get_contents('php://input'), true);
$reviewId = trim($input['review_id'] ?? '');
$reason = trim($input['reason'] ?? '');
$otherText = trim($input['other_text'] ?? '');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'Please login to report.']);
    exit;
}
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$userId = $_SESSION['user_id'];
$blCheck = checkBlacklist($pdo, $ip, null, $userId);
if ($blCheck['blocked']) {
    echo json_encode(['ok' => false, 'message' => 'Action blocked: ' . $blCheck['reason']]);
    exit;
}
if (empty($reviewId) || empty($reason)) {
    echo json_encode(['ok' => false, 'message' => 'Missing report details.']);
    exit;
}

$userId = $_SESSION['user_id'];
$reasonText = $reason;
if ($otherText) $reasonText .= ' - ' . $otherText;

try {
    $stmt = $pdo->prepare("UPDATE reviews SET reported_count = reported_count + 1 WHERE id = ?");
    $stmt->execute([$reviewId]);

    $priority = 'medium';
    $reasonPriority = ['spam' => 'medium', 'offensive' => 'critical', 'wrong_info' => 'medium', 'duplicate' => 'low', 'other' => 'medium'];
    $priority = $reasonPriority[$reason] ?? 'medium';

    $cntStmt = $pdo->prepare("SELECT reported_count FROM reviews WHERE id = ?");
    $cntStmt->execute([$reviewId]);
    $reportCount = (int)$cntStmt->fetchColumn();
    if ($reportCount >= 3) $priority = 'high';
    if ($reportCount >= 5) $priority = 'critical';

    $reasonMap = ['spam' => 'spam', 'offensive' => 'offensive', 'wrong_info' => 'wrong_info', 'duplicate' => 'duplicate', 'other' => 'other'];
    $modFlagReason = $reasonMap[$reason] ?? 'spam';

    $existing = $pdo->prepare("SELECT id FROM moderation_queue WHERE entity_type = 'review' AND entity_id = ? AND status IN ('pending','in_progress')");
    $existing->execute([$reviewId]);
    if ($existing->fetch()) {
        $upd = $pdo->prepare("UPDATE moderation_queue SET priority = ? WHERE entity_type = 'review' AND entity_id = ? AND status IN ('pending','in_progress')");
        $upd->execute([$priority, $reviewId]);
    } else {
        $slaHours = ['critical' => 4, 'high' => 12, 'medium' => 24, 'low' => 48];
        $slaDue = date('Y-m-d H:i:s', time() + ($slaHours[$priority] ?? 24) * 3600);
        $pdo->prepare("INSERT INTO moderation_queue (id, entity_type, entity_id, status, priority, flagged_reason, reporter_id, sla_due_at, created_at) VALUES (UUID(), 'review', ?, 'pending', ?, ?, ?, ?, NOW())")
             ->execute([$reviewId, $priority, $modFlagReason, $userId, $slaDue]);
    }

    if ($reportCount >= 3) {
        $pdo->prepare("UPDATE reviews SET moderation_status = 'rejected' WHERE id = ? AND moderation_status = 'pending'")->execute([$reviewId]);
    }

    try { detectSpam($pdo, $reasonText, $ip, $userId, null, 'review_report'); } catch (Exception $e) {}

    echo json_encode(['ok' => true, 'message' => 'Report submitted. Thank you for helping keep the platform safe!']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Failed to submit report.']);
}
