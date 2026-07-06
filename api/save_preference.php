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
    echo json_encode(['ok' => true, 'msg' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid data']);
    exit;
}

$key = trim($data['key'] ?? '');
$value = trim($data['value'] ?? '');

if ($key === '' || $value === '') {
    echo json_encode(['ok' => false, 'msg' => 'Missing key or value']);
    exit;
}

$allowedKeys = ['open_to_private', 'recommendation_feedback'];
if (!in_array($key, $allowedKeys)) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid preference key']);
    exit;
}

try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_preferences'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id CHAR(36) NOT NULL,
            pref_key VARCHAR(50) NOT NULL,
            pref_value VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_pref (user_id, pref_key)
        )");
    }

    $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, pref_key, pref_value) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE pref_value = VALUES(pref_value), updated_at = CURRENT_TIMESTAMP");
    $stmt->execute([$userId, $key, $value]);

    echo json_encode(['ok' => true, 'msg' => 'Preference saved']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'Failed to save preference']);
}
