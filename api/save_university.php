<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../panel_cms_2847/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['ok' => false, 'error' => 'login_required', 'msg' => 'Please login first.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid data']);
    exit;
}

$universityId = trim((string)($data['university_id'] ?? ''));
$action = trim((string)($data['action'] ?? 'save'));

if ($universityId === '') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid university ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM universities WHERE id = ? LIMIT 1");
    $stmt->execute([$universityId]);
    if (!$stmt->fetch()) {
        echo json_encode(['ok' => false, 'msg' => 'University not found']);
        exit;
    }

    if ($action === 'save') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO saved_colleges (user_id, university_id) VALUES (?, ?)");
        $stmt->execute([$userId, $universityId]);
        echo json_encode(['ok' => true, 'saved' => true]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM saved_colleges WHERE user_id = ? AND university_id = ?");
        $stmt->execute([$userId, $universityId]);
        echo json_encode(['ok' => true, 'saved' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
}
