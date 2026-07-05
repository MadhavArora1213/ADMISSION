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

require_once __DIR__ . '/../admin/db.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !is_array($input)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid request body']);
    exit;
}

$schoolId = trim($input['school_id'] ?? '');
$name     = trim($input['name'] ?? '');
$email    = trim($input['email'] ?? '');
$phone    = trim($input['phone'] ?? '');
$city     = trim($input['city'] ?? '');
$state    = trim($input['state'] ?? '');
$message  = trim($input['message'] ?? '');

// Validate
if ($schoolId === '') {
    echo json_encode(['ok' => false, 'error' => 'school_id is required']); exit;
}
if ($name === '' || $phone === '') {
    echo json_encode(['ok' => false, 'error' => 'Name and phone are required']); exit;
}
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid 10-digit phone number']); exit;
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid email address']); exit;
}

// Verify school exists
$chk = $pdo->prepare("SELECT id, name FROM schools WHERE id = ? AND status = 'active'");
$chk->execute([$schoolId]);
$school = $chk->fetch();
if (!$school) {
    echo json_encode(['ok' => false, 'error' => 'School not found']); exit;
}

// Duplicate check - same phone + school in last 24 hours
$dup = $pdo->prepare("SELECT id FROM leads WHERE school_id = ? AND phone = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1");
$dup->execute([$schoolId, $phone]);
if ($dup->fetch()) {
    echo json_encode(['ok' => true, 'message' => 'Your application has already been submitted. Our team will contact you soon.']); exit;
}

// Also check if logged-in user already applied to this school
$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    $userDup = $pdo->prepare("SELECT id FROM leads WHERE school_id = ? AND user_id = ? AND lead_type = 'apply' LIMIT 1");
    $userDup->execute([$schoolId, $userId]);
    if ($userDup->fetch()) {
        echo json_encode(['ok' => true, 'message' => 'You have already applied to this school. Our team will contact you soon.']); exit;
    }
}

function uuid(): string {
    return sprintf('%08x-%04x-%04x-%04x-%012x',
        mt_rand(0, 0xffffffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffffffffffff));
}

$leadId = uuid();
$userId = $_SESSION['user_id'] ?? null;
$sourcePage = $_SERVER['HTTP_REFERER'] ?? '/school/' . $school['name'];

$stmt = $pdo->prepare("
    INSERT INTO leads (
        id, user_id, lead_type, source_page, school_id,
        name, phone, email, city, state,
        lead_status, priority, delivery_status,
        counsellor_notes, created_at, updated_at
    ) VALUES (?, ?, 'apply', ?, ?, ?, ?, ?, ?, ?, 'new', 'medium', 'pending', ?, NOW(), NOW())
");
$stmt->execute([
    $leadId,
    $userId,
    $sourcePage,
    $schoolId,
    $name,
    $phone,
    $email !== '' ? $email : null,
    $city !== '' ? $city : null,
    $state !== '' ? $state : null,
    $message !== '' ? $message : null,
]);

echo json_encode([
    'ok' => true,
    'message' => 'Application submitted successfully! ' . htmlspecialchars($school['name']) . ' will contact you soon.',
    'lead_id' => $leadId,
]);
