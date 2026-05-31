<?php
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_GET['state_id']) || empty($_GET['state_id'])) {
    echo json_encode([]);
    exit;
}

$state_id = $_GET['state_id'];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM cities WHERE state_id = ? ORDER BY name ASC");
    $stmt->execute([$state_id]);
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cities);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
