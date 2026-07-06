<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../panel_cms_2847/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['q'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing query']);
    exit;
}

$query = trim($input['q']);
$type = $input['type'] ?? null;
$slug = $input['slug'] ?? null;

try {
    // Update the most recent matching search_queries row with click info
    $stmt = $pdo->prepare("UPDATE search_queries SET clicked_result_id = ?, clicked_type = ? WHERE query_text = ? ORDER BY search_timestamp DESC LIMIT 1");
    $stmt->execute([$slug, $type, $query]);

    // Bump trending score for clicked query
    $pdo->prepare("INSERT INTO search_trending (query_text, trending_score, trending_period) VALUES (?, 1.5, 'daily') ON DUPLICATE KEY UPDATE trending_score = trending_score + 1.5")->execute([$query]);

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false]);
}
