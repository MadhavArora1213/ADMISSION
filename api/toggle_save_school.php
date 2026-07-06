<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

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
    echo json_encode(['ok' => false, 'error' => 'login_required', 'message' => 'Please login to save schools.']);
    exit;
}

require_once __DIR__ . '/../panel_cms_2847/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$schoolId = trim($input['school_id'] ?? '');
$userId = $_SESSION['user_id'];

if ($schoolId === '') {
    echo json_encode(['ok' => false, 'error' => 'school_id is required']); exit;
}

// Create saved_schools table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS saved_schools (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    school_id CHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_save (user_id, school_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Check if already saved
$chk = $pdo->prepare("SELECT id FROM saved_schools WHERE user_id = ? AND school_id = ?");
$chk->execute([$userId, $schoolId]);
$existing = $chk->fetch();

if ($existing) {
    // Unsave
    $del = $pdo->prepare("DELETE FROM saved_schools WHERE id = ?");
    $del->execute([$existing['id']]);
    echo json_encode(['ok' => true, 'saved' => false, 'message' => 'School removed from saved list.']);
} else {
    // Save
    $id = sprintf('%08x-%04x-%04x-%04x-%012x',
        mt_rand(0, 0xffffffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffffffffffff));
    $ins = $pdo->prepare("INSERT INTO saved_schools (id, user_id, school_id, created_at) VALUES (?, ?, ?, NOW())");
    $ins->execute([$id, $userId, $schoolId]);
    echo json_encode(['ok' => true, 'saved' => true, 'message' => 'School saved successfully!']);
}
