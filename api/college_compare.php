<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../admin/db.php';

$id1 = $_GET['id1'] ?? '';
$id2 = $_GET['id2'] ?? '';
if (!$id1 || !$id2) { echo json_encode(['error' => 'Need id1 and id2']); exit; }

function getCollegeFull($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT c.*, s.name AS state_name, ci.name AS city_name,
               cm.cover_image_url, cm.logo_url,
               (SELECT MIN(annual_fee) FROM college_courses WHERE college_id=c.id AND annual_fee>0) AS min_fee,
               (SELECT MAX(total_fee) FROM college_courses WHERE college_id=c.id AND total_fee>0) AS max_total_fee,
               (SELECT MAX(avg_package_lpa) FROM college_placements WHERE college_id=c.id) AS avg_package,
               (SELECT MAX(highest_package_lpa) FROM college_placements WHERE college_id=c.id) AS highest_package,
               (SELECT MAX(median_package_lpa) FROM college_placements WHERE college_id=c.id) AS median_package,
               (SELECT MAX(placement_percentage) FROM college_placements WHERE college_id=c.id) AS placement_pct,
               (SELECT SUM(students_placed) FROM college_placements WHERE college_id=c.id) AS total_placed
        FROM colleges c
        LEFT JOIN states s ON c.state_id=s.id
        LEFT JOIN cities ci ON c.city_id=ci.id
        LEFT JOIN college_media cm ON cm.college_id=c.id AND (cm.image_type IS NULL OR cm.image_type='cover')
        WHERE c.id=?
    ");
    $stmt->execute([$id]);
    $college = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$college) return null;

    // Courses
    $cs = $pdo->prepare("SELECT * FROM college_courses WHERE college_id=? ORDER BY course_name ASC");
    $cs->execute([$id]);
    $college['courses'] = $cs->fetchAll(PDO::FETCH_ASSOC);

    // Placements
    $ps = $pdo->prepare("SELECT * FROM college_placements WHERE college_id=? ORDER BY placement_year DESC");
    $ps->execute([$id]);
    $college['placements'] = $ps->fetchAll(PDO::FETCH_ASSOC);

    // Reviews breakdown
    $rs = $pdo->prepare("
        SELECT 
            ROUND(AVG(overall_rating),1) AS avg_overall,
            ROUND(AVG(placements_rating),1) AS avg_placements,
            ROUND(AVG(infrastructure_rating),1) AS avg_infra,
            ROUND(AVG(faculty_rating),1) AS avg_faculty,
            ROUND(AVG(social_life_rating),1) AS avg_social,
            ROUND(AVG(food_rating),1) AS avg_food,
            ROUND(AVG(hostel_rating),1) AS avg_hostel,
            COUNT(*) AS total_reviews
        FROM reviews WHERE college_id=? AND moderation_status='approved'
    ");
    $rs->execute([$id]);
    $college['review_stats'] = $rs->fetch(PDO::FETCH_ASSOC);

    // Rankings
    $college['rankings'] = [
        'nirf' => $college['ranking_nirf'] ?? null,
        'qs' => $college['ranking_qs'] ?? null,
        'times' => $college['ranking_times'] ?? null,
    ];

    // Accepted exams (from cutoffs)
    $ex = $pdo->prepare("SELECT DISTINCT e.exam_name FROM college_cutoffs cc LEFT JOIN exams e ON e.id=cc.exam_id WHERE cc.college_id=? AND e.exam_name IS NOT NULL");
    $ex->execute([$id]);
    $college['accepted_exams'] = $ex->fetchAll(PDO::FETCH_COLUMN);

    // Top recruiters from latest placement record
    if (!empty($college['placements'][0]['top_recruiters'])) {
        $college['top_recruiters'] = $college['placements'][0]['top_recruiters'];
    }

    // Facilities (from college_courses specializations as proxy)
    $college['facilities'] = [];
    if (!empty($college['ugc_approved'])) $college['facilities'][] = 'UGC Approved';
    if (!empty($college['aicte_approved'])) $college['facilities'][] = 'AICTE Approved';
    if (!empty($college['nba_approved'])) $college['facilities'][] = 'NBA Accredited';
    if (!empty($college['autonomous'])) $college['facilities'][] = 'Autonomous';

    return $college;
}

$c1 = getCollegeFull($pdo, $id1);
$c2 = getCollegeFull($pdo, $id2);

if (!$c1 || !$c2) { echo json_encode(['error' => 'College not found']); exit; }

echo json_encode(['college1' => $c1, 'college2' => $c2], JSON_UNESCAPED_UNICODE);
