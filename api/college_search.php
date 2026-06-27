<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../admin/db.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("
  SELECT c.id, c.name, c.slug, c.college_type, c.naac_grade, c.ranking_nirf,
         ci.name AS city_name,
         (SELECT COUNT(*) FROM college_courses WHERE college_id=c.id) AS course_count
  FROM colleges c
  LEFT JOIN cities ci ON c.city_id = ci.id
  WHERE c.status='active' AND (c.name LIKE :q OR ci.name LIKE :q)
  ORDER BY c.ranking_nirf ASC, c.name ASC
  LIMIT 15
");
$stmt->execute(['q' => '%' . $q . '%']);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
