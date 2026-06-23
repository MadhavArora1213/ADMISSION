<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../admin/db.php';

$stateId = (int)($_GET['state_id'] ?? 0);
if ($stateId <= 0) {
    echo json_encode(['cities' => []]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name FROM cities WHERE state_id = ? ORDER BY name ASC");
$stmt->execute([$stateId]);
echo json_encode(['cities' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
