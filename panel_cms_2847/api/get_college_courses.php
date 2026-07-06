<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}
require_once '../db.php';

header('Content-Type: application/json');

$college_id = $_GET['college_id'] ?? null;
if (!$college_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, course_name as name 
        FROM college_courses
        WHERE college_id = ?
        ORDER BY course_name ASC
    ");
    $stmt->execute([$college_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($courses);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
