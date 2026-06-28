<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/admin/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Please login first to apply.', 'redirect' => 'login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '/')]);
    exit;
}

$college_id = trim($_POST['college_id'] ?? '');
$name       = trim($_POST['full_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$course_id  = trim($_POST['course_id'] ?? '');
$course_name = trim($_POST['course_name'] ?? '');
$exam_score  = trim($_POST['exam_score'] ?? '');
$target_year = (int)($_POST['target_year'] ?? date('Y'));
$notes       = trim($_POST['notes'] ?? '');

if ($college_id === '' || $name === '' || $email === '' || $phone === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Please fill all required fields (Name, Email, Phone).']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid email address.']);
    exit;
}

// Check if user already applied to this college
$user_id = $_SESSION['user_id'];
$chk = $pdo->prepare("SELECT id, application_number, status FROM applications WHERE user_id = ? AND college_id = ?");
$chk->execute([$user_id, $college_id]);
$existing = $chk->fetch(PDO::FETCH_ASSOC);
if ($existing) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'msg' => 'You have already applied to this college.', 'app_number' => $existing['application_number'], 'status' => $existing['status'], 'already_applied' => true]);
    exit;
}

// Generate application number
$appNumber = 'APP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

try {
    // Generate UUID
    $id = sprintf('%08x-%04x-%04x-%04x-%012x', mt_rand(0, 0xffffffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffffffffffff));

    $courseId = !empty($course_id) ? $course_id : null;

    $remarks = "Course: $course_name\nExam Score: $exam_score\nTarget Year: $target_year\nNotes: $notes";

    $stmt = $pdo->prepare("
        INSERT INTO applications 
        (id, user_id, college_id, course_id, application_number, applied_at, status, payment_status, remarks, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), 'submitted', 'pending', ?, NOW(), NOW())
    ");
    $stmt->execute([$id, $user_id, $college_id, $courseId, $appNumber, $remarks]);
    
    // Also save as a Lead/Enquiry for the college dashboard leads tab
    try {
        $leadId = sprintf('%08x-%04x-%04x-%04x-%012x', mt_rand(0, 0xffffffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffffffffffff));
        $leadStmt = $pdo->prepare("
            INSERT INTO leads 
            (id, user_id, name, lead_type, source_page, college_id, course_id, phone, email, class_12_score, target_year, lead_status, priority, counsellor_notes, created_at, updated_at)
            VALUES (?, ?, ?, 'apply', 'apply_page', ?, ?, ?, ?, ?, ?, 'new', 'high', ?, NOW(), NOW())
        ");
        $leadStmt->execute([
            $leadId,
            $user_id,
            $name,
            $college_id,
            $courseId,
            $phone,
            $email,
            !empty($exam_score) ? (float)$exam_score : null,
            $target_year,
            "Applied via Application Number: $appNumber\nRemarks: $remarks"
        ]);
    } catch (Exception $e) {
        @file_put_contents(__DIR__ . '/scratch/lead_error.log', date('Y-m-d H:i:s') . ' - Error: ' . $e->getMessage() . "\n", FILE_APPEND);
    }

    // Log activity
    try {
        $logStmt = $pdo->prepare("INSERT INTO activity_log (activity_type, actor_id, entity_type, entity_id, meta_json, created_at) VALUES ('create', ?, 'college', ?, ?, NOW())");
        $logStmt->execute([$user_id, $college_id, json_encode(['application_number' => $appNumber, 'student' => $name])]);
    } catch (Exception $e) {}
    
    echo json_encode(['ok' => true, 'msg' => 'Application submitted successfully!', 'app_number' => $appNumber]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Something went wrong. Please try again.']);
}
