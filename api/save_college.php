<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../admin/db.php';

@file_put_contents(__DIR__ . '/../scratch/save_college_debug.log', date('Y-m-d H:i:s') . " - Method: " . $_SERVER['REQUEST_METHOD'] . " - Input: " . file_get_contents('php://input') . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['ok' => false, 'error' => 'login_required', 'msg' => 'Please login first to save colleges.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid data']);
    exit;
}

$collegeId = isset($data['college_id']) ? trim((string)$data['college_id']) : '';
$action = isset($data['action']) ? trim($data['action']) : 'save';

if ($collegeId === '') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid college ID']);
    exit;
}

try {
    // Verify college exists
    $stmt = $pdo->prepare("SELECT id FROM colleges WHERE id = ? LIMIT 1");
    $stmt->execute([$collegeId]);
    if (!$stmt->fetch()) {
        echo json_encode(['ok' => false, 'msg' => 'College does not exist']);
        exit;
    }

    if ($action === 'save') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO saved_colleges (user_id, college_id) VALUES (?, ?)");
        $stmt->execute([$userId, $collegeId]);
        echo json_encode(['ok' => true, 'saved' => true, 'msg' => 'College saved successfully.']);
    } else {
        $stmt = $pdo->prepare("DELETE FROM saved_colleges WHERE user_id = ? AND college_id = ?");
        $stmt->execute([$userId, $collegeId]);
        echo json_encode(['ok' => true, 'saved' => false, 'msg' => 'College removed from saved list.']);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'Failed to process request: ' . $e->getMessage()]);
}
