<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../panel_cms_2847/db.php';

$id1 = $_GET['id1'] ?? '';
$id2 = $_GET['id2'] ?? '';
$courseId = $_GET['course_id'] ?? '';
if (!$id1 || !$id2) { echo json_encode(['error' => 'Need id1 and id2']); exit; }

function getCollegeFull($pdo, $id, $courseId = '') {
    $stmt = $pdo->prepare("
        SELECT c.*, s.name AS state_name, ci.name AS city_name,
               cm.cover_image_url, cm.logo_url
        FROM colleges c
        LEFT JOIN states s ON c.state_id=s.id
        LEFT JOIN cities ci ON c.city_id=ci.id
        LEFT JOIN college_media cm ON cm.college_id=c.id AND (cm.image_type IS NULL OR cm.image_type='cover')
        WHERE c.id=?
    ");
    $stmt->execute([$id]);
    $college = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$college) return null;

    // Fees — filter by course if selected
    if ($courseId) {
        $fs = $pdo->prepare("SELECT annual_fee, total_fee, course_name FROM college_courses WHERE college_id=? AND id=?");
        $fs->execute([$id, $courseId]);
        $fees = $fs->fetch(PDO::FETCH_ASSOC);
        $college['min_fee'] = $fees['annual_fee'] ?: null;
        $college['max_total_fee'] = $fees['total_fee'] ?: null;
        $college['selected_course'] = $fees['course_name'] ?? null;
    } else {
        $fs = $pdo->prepare("SELECT MIN(annual_fee) AS min_fee, MAX(total_fee) AS max_total_fee FROM college_courses WHERE college_id=? AND (annual_fee>0 OR total_fee>0)");
        $fs->execute([$id]);
        $fees = $fs->fetch(PDO::FETCH_ASSOC);
        $college['min_fee'] = $fees['min_fee'];
        $college['max_total_fee'] = $fees['max_total_fee'];
        $college['selected_course'] = null;
    }

    // Placements (all at once)
    $ps = $pdo->prepare("SELECT MAX(avg_package_lpa) AS avg_package, MAX(highest_package_lpa) AS highest_package, MAX(median_package_lpa) AS median_package, MAX(placement_percentage) AS placement_pct, SUM(students_placed) AS total_placed FROM college_placements WHERE college_id=?");
    $ps->execute([$id]);
    $pl = $ps->fetch(PDO::FETCH_ASSOC);
    $college['avg_package'] = $pl['avg_package'];
    $college['highest_package'] = $pl['highest_package'];
    $college['median_package'] = $pl['median_package'];
    $college['placement_pct'] = $pl['placement_pct'];
    $college['total_placed'] = $pl['total_placed'];

    // Latest top recruiters
    $tr = $pdo->prepare("SELECT top_recruiters FROM college_placements WHERE college_id=? AND top_recruiters IS NOT NULL AND top_recruiters!='' ORDER BY placement_year DESC LIMIT 1");
    $tr->execute([$id]);
    $college['top_recruiters'] = $tr->fetchColumn() ?: null;

    // Courses — if course selected, only show that one; otherwise all
    if ($courseId) {
        $cs = $pdo->prepare("SELECT * FROM college_courses WHERE college_id=? AND id=?");
        $cs->execute([$id, $courseId]);
    } else {
        $cs = $pdo->prepare("SELECT * FROM college_courses WHERE college_id=? ORDER BY course_name ASC");
        $cs->execute([$id]);
    }
    $college['courses'] = $cs->fetchAll(PDO::FETCH_ASSOC);

    // Reviews breakdown
    $rs = $pdo->prepare("SELECT ROUND(AVG(overall_rating),1) AS avg_overall, ROUND(AVG(placements_rating),1) AS avg_placements, ROUND(AVG(infrastructure_rating),1) AS avg_infra, ROUND(AVG(faculty_rating),1) AS avg_faculty, ROUND(AVG(social_life_rating),1) AS avg_social, ROUND(AVG(food_rating),1) AS avg_food, ROUND(AVG(hostel_rating),1) AS avg_hostel, COUNT(*) AS total_reviews FROM reviews WHERE college_id=? AND moderation_status='approved'");
    $rs->execute([$id]);
    $college['review_stats'] = $rs->fetch(PDO::FETCH_ASSOC);

    // Accepted exams
    $ex = $pdo->prepare("SELECT DISTINCT e.exam_name FROM college_cutoffs cc LEFT JOIN exams e ON e.id=cc.exam_id WHERE cc.college_id=? AND e.exam_name IS NOT NULL");
    $ex->execute([$id]);
    $college['accepted_exams'] = $ex->fetchAll(PDO::FETCH_COLUMN);

    // Facilities
    $college['facilities'] = [];
    if (!empty($college['ugc_approved'])) $college['facilities'][] = 'UGC Approved';
    if (!empty($college['aicte_approved'])) $college['facilities'][] = 'AICTE Approved';
    if (!empty($college['nba_approved'])) $college['facilities'][] = 'NBA Accredited';
    if (!empty($college['autonomous'])) $college['facilities'][] = 'Autonomous';

    return $college;
}

$c1 = getCollegeFull($pdo, $id1, $courseId);
$c2 = getCollegeFull($pdo, $id2, $courseId);

if (!$c1 || !$c2) { echo json_encode(['error' => 'College not found']); exit; }

$out = ['college1' => $c1, 'college2' => $c2];
if ($courseId && !empty($c1['selected_course'])) {
    $out['course_name'] = $c1['selected_course'];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
