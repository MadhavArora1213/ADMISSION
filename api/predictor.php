<?php
header('Content-Type: application/json');
error_reporting(0);
require_once __DIR__ . '/../admin/db.php';

$examId = $_GET['exam_id'] ?? '';
$rank = intval($_GET['rank'] ?? 0);
$level = $_GET['level'] ?? '';
$category = $_GET['category'] ?? 'General';
$maxFee = floatval($_GET['max_fee'] ?? 0);
$stateId = intval($_GET['state_id'] ?? 0);
$collegeType = $_GET['college_type'] ?? '';
$naacMin = $_GET['naac_min'] ?? '';

if (!$examId || $rank <= 0) {
    echo json_encode(['error' => 'Please select an exam and enter your rank']);
    exit;
}

$naacOrder = ['A++'=>7,'A+'=>6,'A'=>5,'B++'=>4,'B+'=>3,'B'=>2,'C'=>1];
$naacRank = $naacOrder[$naacMin] ?? 0;

// Step 1: Get eligible colleges via cutoffs
$cutoffColleges = [];
$stmt = $pdo->prepare("
    SELECT DISTINCT c.id, cc.opening_rank, cc.closing_rank
    FROM college_cutoffs cc
    JOIN colleges c ON c.id=cc.college_id
    WHERE cc.exam_id=? AND cc.category=? AND cc.closing_rank IS NOT NULL
    AND c.status='active' AND c.publish_status='published'
");
$stmt->execute([$examId, $category]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cutoffColleges[$row['id']] = [
        'opening' => $row['opening_rank'],
        'closing' => $row['closing_rank'],
    ];
}

// Step 2: Also include colleges with course_level match even without cutoffs
$allQuery = "
    SELECT c.*, s.name AS state_name, ci.name AS city_name,
           cm.logo_url, cm.cover_image_url,
           (SELECT MIN(annual_fee) FROM college_courses WHERE college_id=c.id AND annual_fee>0) AS min_fee,
           (SELECT MAX(total_fee) FROM college_courses WHERE college_id=c.id AND total_fee>0) AS max_total_fee,
           MAX(CASE WHEN cp.placement_year=(SELECT MAX(placement_year) FROM college_placements WHERE college_id=c.id) THEN cp.avg_package_lpa END) AS avg_package,
           MAX(CASE WHEN cp.placement_year=(SELECT MAX(placement_year) FROM college_placements WHERE college_id=c.id) THEN cp.highest_package_lpa END) AS highest_package,
           MAX(cp.placement_percentage) AS placement_pct,
           MAX(cp.students_placed) AS total_placed
    FROM colleges c
    LEFT JOIN states s ON c.state_id=s.id
    LEFT JOIN cities ci ON c.city_id=ci.id
    LEFT JOIN college_media cm ON cm.college_id=c.id AND (cm.image_type IS NULL OR cm.image_type='cover')
    LEFT JOIN college_placements cp ON cp.college_id=c.id
    WHERE c.status='active' AND c.publish_status='published'
";
$params = [];

if ($level) {
    $allQuery .= " AND c.id IN (SELECT college_id FROM college_courses WHERE course_level=?)";
    $params[] = $level;
}
if ($collegeType) {
    $allQuery .= " AND c.college_type=?";
    $params[] = $collegeType;
}
if ($stateId) {
    $allQuery .= " AND c.state_id=?";
    $params[] = $stateId;
}
if ($naacRank > 0) {
    $allQuery .= " AND c.naac_grade IS NOT NULL";
    $naacGrades = array_keys(array_filter($naacOrder, fn($v) => $v >= $naacRank));
    $placeholders = implode(',', array_fill(0, count($naacGrades), '?'));
    $allQuery .= " AND c.naac_grade IN ($placeholders)";
    $params = array_merge($params, $naacGrades);
}
if ($maxFee > 0) {
    $allQuery .= " AND (c.id IN (SELECT college_id FROM college_courses WHERE annual_fee<=? AND annual_fee>0) OR NOT EXISTS (SELECT 1 FROM college_courses WHERE college_id=c.id AND annual_fee>0))";
    $params[] = $maxFee;
}

$allQuery .= " GROUP BY c.id ORDER BY c.ranking_nirf ASC, c.overall_rating_avg DESC LIMIT 50";

$stmt = $pdo->prepare($allQuery);
$stmt->execute($params);
$colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$colleges) {
    echo json_encode(['colleges' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

// Step 3: Score each college
$scored = [];
foreach ($colleges as $c) {
    $score = 0;
    $hasCutoff = isset($cutoffColleges[$c['id']]);

    // Cutoff match (40 points max)
    if ($hasCutoff) {
        $cc = $cutoffColleges[$c['id']];
        if ($rank <= $cc['closing']) {
            $score += 40;
            $matchCutoff = true;
        } elseif ($rank <= $cc['closing'] * 1.2) {
            $score += 20;
            $matchCutoff = true;
        } else {
            $score += 5;
            $matchCutoff = false;
        }
    } else {
        $score += 15;
        $matchCutoff = false;
    }

    // NIRF ranking (20 points max)
    if ($c['ranking_nirf']) {
        if ($c['ranking_nirf'] <= 10) $score += 20;
        elseif ($c['ranking_nirf'] <= 25) $score += 16;
        elseif ($c['ranking_nirf'] <= 50) $score += 12;
        elseif ($c['ranking_nirf'] <= 100) $score += 8;
        else $score += 4;
    }

    // NAAC grade (15 points max)
    $cNaac = $naacOrder[$c['naac_grade'] ?? ''] ?? 0;
    if ($cNaac >= 7) $score += 15;
    elseif ($cNaac >= 6) $score += 12;
    elseif ($cNaac >= 5) $score += 9;
    elseif ($cNaac >= 4) $score += 6;
    elseif ($cNaac > 0) $score += 3;

    // Placements (15 points max)
    $pct = floatval($c['placement_pct'] ?? 0);
    if ($pct >= 90) $score += 15;
    elseif ($pct >= 75) $score += 12;
    elseif ($pct >= 60) $score += 9;
    elseif ($pct >= 40) $score += 6;
    elseif ($pct > 0) $score += 3;

    // Fee fit (10 points max)
    $matchFee = false;
    if ($maxFee > 0 && $c['min_fee']) {
        if ($c['min_fee'] <= $maxFee) {
            $score += 10;
            $matchFee = true;
        } elseif ($c['min_fee'] <= $maxFee * 1.5) {
            $score += 4;
        }
    } else {
        $score += 7;
    }

    // Location match (bonus)
    $matchLocation = false;
    if ($stateId && $c['state_id'] == $stateId) {
        $score += 5;
        $matchLocation = true;
    }

    // NAAC match
    $matchNaac = ($naacRank > 0 && $cNaac >= $naacRank);

    // Cap at 100
    $score = min(100, $score);

    $scored[] = [
        'id' => $c['id'],
        'name' => $c['name'],
        'slug' => $c['slug'],
        'college_type' => $c['college_type'],
        'naac_grade' => $c['naac_grade'],
        'ranking_nirf' => $c['ranking_nirf'],
        'city_name' => $c['city_name'],
        'state_name' => $c['state_name'],
        'min_fee' => $c['min_fee'],
        'max_total_fee' => $c['max_total_fee'],
        'avg_package' => $c['avg_package'],
        'highest_package' => $c['highest_package'],
        'placement_pct' => $c['placement_pct'],
        'total_students' => $c['total_students'],
        'total_placed' => $c['total_placed'],
        'logo_url' => $c['logo_url'],
        'score' => $score,
        'match_cutoff' => $matchCutoff,
        'match_fee' => $matchFee,
        'match_location' => $matchLocation,
        'match_naac' => $matchNaac,
        'match_nirf' => !empty($c['ranking_nirf']),
    ];
}

// Sort by score desc
usort($scored, function ($a, $b) {
    return $b['score'] <=> $a['score'];
});

// Return top 20
echo json_encode(['colleges' => array_slice($scored, 0, 20)], JSON_UNESCAPED_UNICODE);
