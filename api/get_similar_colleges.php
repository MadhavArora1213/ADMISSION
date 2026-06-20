<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json');

require_once __DIR__ . '/../admin/db.php';
require_once __DIR__ . '/../includes/college_helpers.php';

$collegeId = trim($_GET['college_id'] ?? '');
if ($collegeId === '') {
    echo json_encode(['ok' => false, 'msg' => 'Missing college_id']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, slug, city_id, state_id, college_type, ownership, overall_rating_avg, total_reviews, ranking_nirf, naac_grade, is_verified FROM colleges WHERE id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$collegeId]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$current) {
    echo json_encode(['ok' => false, 'msg' => 'College not found']);
    exit;
}

$cityId = $current['city_id'];
$stateId = $current['state_id'];
$collegeType = $current['college_type'];

$selectCols = "c.id, c.name, c.slug, c.college_type, c.overall_rating_avg, c.total_reviews, c.ranking_nirf, c.naac_grade, c.is_verified,
    (SELECT MIN(annual_fee) FROM college_courses WHERE college_id=c.id AND annual_fee>0) AS min_fee,
    (SELECT MAX(annual_fee) FROM college_courses WHERE college_id=c.id AND annual_fee>0) AS max_fee,
    (SELECT COUNT(*) FROM college_courses WHERE college_id=c.id) AS course_count,
    (SELECT ml.logo_url FROM college_media ml WHERE ml.college_id=c.id AND ml.logo_url IS NOT NULL LIMIT 1) AS logo_url,
    ct.name AS city_name, st.name AS state_name";
$fromJoins = "FROM colleges c LEFT JOIN cities ct ON ct.id=c.city_id LEFT JOIN states st ON st.id=c.state_id";
$baseWhere = "c.status='active'";

$similar = [];
$excludeIds = [$collegeId];
$needed = 6;

function fetchSimilar($pdo, $selectCols, $fromJoins, $whereClause, $params, $limit) {
    $sql = "SELECT $selectCols $fromJoins WHERE $whereClause ORDER BY c.overall_rating_avg DESC, c.ranking_nirf ASC, c.total_reviews DESC LIMIT $limit";
    $s = $pdo->prepare($sql);
    $s->execute($params);
    return $s->fetchAll(PDO::FETCH_ASSOC);
}

function excludeClause($excludeIds) {
    $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
    return "c.id NOT IN ($placeholders)";
}

// Tier 1: Same city
if ($cityId) {
    $results = fetchSimilar($pdo, $selectCols, $fromJoins,
        "$baseWhere AND " . excludeClause($excludeIds) . " AND c.city_id = ?",
        array_merge($excludeIds, [$cityId]), $needed);
    $similar = array_merge($similar, $results);
    $excludeIds = array_merge($excludeIds, array_column($results, 'id'));
}

// Tier 2: Same state
if (count($similar) < $needed && $stateId) {
    $results = fetchSimilar($pdo, $selectCols, $fromJoins,
        "$baseWhere AND " . excludeClause($excludeIds) . " AND c.state_id = ?",
        array_merge($excludeIds, [$stateId]), $needed - count($similar));
    $similar = array_merge($similar, $results);
    $excludeIds = array_merge($excludeIds, array_column($results, 'id'));
}

// Tier 3: Same college_type (govt/private/deemed/autonomous) across all
if (count($similar) < $needed && $collegeType) {
    $results = fetchSimilar($pdo, $selectCols, $fromJoins,
        "$baseWhere AND " . excludeClause($excludeIds) . " AND c.college_type = ?",
        array_merge($excludeIds, [$collegeType]), $needed - count($similar));
    $similar = array_merge($similar, $results);
    $excludeIds = array_merge($excludeIds, array_column($results, 'id'));
}

// Tier 4: Top rated across all (last resort)
if (count($similar) < $needed) {
    $results = fetchSimilar($pdo, $selectCols, $fromJoins,
        "$baseWhere AND " . excludeClause($excludeIds),
        $excludeIds, $needed - count($similar));
    $similar = array_merge($similar, $results);
}

echo json_encode(['ok' => true, 'colleges' => array_slice($similar, 0, $needed)]);
