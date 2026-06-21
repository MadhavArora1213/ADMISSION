<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/college_helpers.php';
require_once __DIR__ . '/includes/exam_helpers.php';
require_once __DIR__ . '/includes/course_helpers.php';

// ─── SIMPLE ROUTER ───
$route = trim($_GET['url'] ?? '/', '/');
$routeParts = explode('/', $route);
$routeBase = $routeParts[0] ?? '';

if ($routeBase === 'colleges') {
    $_GET['type']  = $_GET['type'] ?? 'all';
    $_GET['state'] = $_GET['state'] ?? 0;
    require __DIR__ . '/colleges.php';
    exit;
}

if ($routeBase === 'college' && !empty($routeParts[1])) {
    $_GET['slug'] = $routeParts[1];
    require __DIR__ . '/college.php';
    exit;
}

$totalColleges = cCol($pdo, "SELECT COUNT(*) FROM colleges WHERE status='active'", 25);
$totalReviews  = cCol($pdo, "SELECT COUNT(*) FROM reviews WHERE moderation_status='approved'", 100);
$totalExams    = cCol($pdo, "SELECT COUNT(*) FROM exams", 500);
$totalCourses  = cCol($pdo, "SELECT COUNT(*) FROM courses WHERE status='active'", 1000);

$categories = cAll($pdo, "SELECT id,category_name,category_slug,icon_url FROM course_categories ORDER BY sort_order ASC, category_name ASC LIMIT 6");
if (empty($categories)) {
  $categories = cAll($pdo, "SELECT DISTINCT course_level AS category_name, LOWER(course_level) AS category_slug FROM courses WHERE status='active' LIMIT 6");
}
$catFallback = [
    ['name'=>'Engineering','slug'=>'engineering','icon'=>'ph-laptop','count'=>'6,000+'],
    ['name'=>'Management','slug'=>'management','icon'=>'ph-briefcase','count'=>'4,500+'],
    ['name'=>'Medical','slug'=>'medical','icon'=>'ph-stethoscope','count'=>'1,200+'],
    ['name'=>'Commerce','slug'=>'commerce','icon'=>'ph-chart-line','count'=>'3,100+'],
    ['name'=>'Arts & Design','slug'=>'arts','icon'=>'ph-palette','count'=>'2,000+'],
    ['name'=>'Law','slug'=>'law','icon'=>'ph-scales','count'=>'1,100+'],
];

$sqlC = "SELECT c.id,c.name,c.slug,c.college_type,c.naac_grade,c.ranking_nirf,c.overall_rating_avg,c.total_reviews,
                s.name AS state_name,ct.name AS city_name,cm.cover_image_url,cm.logo_url,
                (SELECT MAX(avg_package_lpa) FROM college_placements cp WHERE cp.college_id=c.id) AS avg_package,
                (SELECT MIN(annual_fee) FROM college_courses cc WHERE cc.college_id=c.id) AS min_fee
         FROM colleges c LEFT JOIN states s ON c.state_id=s.id LEFT JOIN cities ct ON c.city_id=ct.id
         LEFT JOIN college_media cm ON cm.college_id=c.id
         WHERE c.status='active' AND c.is_featured=1 ORDER BY c.featured_order ASC,c.ranking_nirf ASC LIMIT 8";
$featuredColleges = cAll($pdo, $sqlC);
if (empty($featuredColleges)) $featuredColleges = cAll($pdo, str_replace("AND c.is_featured=1","AND c.overall_rating_avg>0",$sqlC));

$sqlCourses = "SELECT id,course_name,course_slug,course_level,duration_years,avg_salary_lpa,total_colleges_offering,description FROM courses WHERE status='active' ORDER BY total_colleges_offering DESC LIMIT 8";
$popularCourses = cAll($pdo, str_replace("WHERE status='active'","WHERE status='active' AND is_popular=1",$sqlCourses));
if (empty($popularCourses)) $popularCourses = cAll($pdo, $sqlCourses);

$upcomingExams = cAll($pdo, "SELECT e.id,e.exam_name AS name,e.exam_slug AS slug,e.exam_level AS level,ed.exam_date,ed.application_start,ed.application_end,ed.result_date,ed.event_name FROM exams e LEFT JOIN exam_dates ed ON ed.exam_id=e.id AND (ed.exam_date>=CURDATE() OR ed.application_end>=CURDATE()) GROUP BY e.id ORDER BY ed.exam_date ASC LIMIT 6");

$reviews = cAll($pdo, "SELECT r.overall_rating,r.review_title,r.review_body,r.batch_year,r.created_at,c.name AS college_name,c.slug AS college_slug FROM reviews r JOIN colleges c ON c.id=r.college_id WHERE r.moderation_status='approved' ORDER BY r.helpful_votes DESC,r.created_at DESC LIMIT 6");

$states = cAll($pdo, "SELECT id,name FROM states ORDER BY name ASC");

// Featured exams from DB
$examsFeatured = cAll($pdo, "SELECT e.exam_name AS name,e.exam_slug AS slug,e.exam_level AS level,e.exam_mode,ed.exam_date,ed.application_start,ed.application_end,em.image_url AS img FROM exams e LEFT JOIN exam_dates ed ON ed.exam_id=e.id LEFT JOIN exam_media em ON em.exam_id=e.id AND em.type='thumbnail' WHERE e.status='active' GROUP BY e.id ORDER BY e.applicants_last_year DESC LIMIT 4");
if (empty($examsFeatured)) {
    $examsFeatured = [
        ['name'=>'JEE Main','level'=>'National','date'=>'02 Apr 2026','type'=>'Online','img'=>'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=400&q=80'],
        ['name'=>'NEET','level'=>'National','date'=>'03 May 2026','type'=>'Offline','img'=>'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=400&q=80'],
        ['name'=>'CUET','level'=>'National','date'=>'11 May 2026','type'=>'Offline','img'=>'https://images.unsplash.com/photo-1452860606245-08a5d1cb3b9e?w=400&q=80'],
        ['name'=>'CAT','level'=>'National','date'=>'30 Nov 2025','type'=>'Online','img'=>'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=400&q=80'],
    ];
}

// Marquee data: top colleges by rating
$marqueeColleges = cAll($pdo, "SELECT name,overall_rating_avg FROM colleges WHERE status='active' ORDER BY overall_rating_avg DESC,ranking_nirf ASC LIMIT 10");

// Courses with level counts for streams fallback
$courseCounts = cAll($pdo, "SELECT course_level, COUNT(*) AS cnt FROM courses WHERE status='active' GROUP BY course_level");

// Minimal fallback arrays (used only if DB returns empty)
$fColleges = [
    ['name'=>'IIT Delhi','loc'=>'New Delhi','type'=>'Public','rating'=>'4.8','fee'=>'₹2.5L','pkg'=>'₹21.5L','img'=>'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80'],
    ['name'=>'IIM Ahmedabad','loc'=>'Ahmedabad','type'=>'Public','rating'=>'4.7','fee'=>'₹27.5L','pkg'=>'₹33.8L','img'=>'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=800&q=80'],
];
$fCourses = [
    ['icon'=>'ph-laptop','level'=>'UG','name'=>'B.Tech','dur'=>'4 yrs','cols'=>'4,500+'],
    ['icon'=>'ph-briefcase','level'=>'PG','name'=>'MBA','dur'=>'2 yrs','cols'=>'3,200+'],
];
$fExams = [
    ['name'=>'JEE Main','level'=>'National','date'=>'24 Jan 2026','last'=>'15 Dec 2025','cols'=>'2,046'],
    ['name'=>'NEET','level'=>'National','date'=>'04 May 2026','last'=>'15 Mar 2026','cols'=>'1,374'],
];

$newsItems = cAll($pdo, "SELECT a.article_slug, a.article_title as title, a.featured_image_url as img, a.publish_at as date, c.category_name as cat FROM articles a LEFT JOIN article_categories c ON a.category_id=c.id WHERE a.status='published' ORDER BY a.publish_at DESC LIMIT 4");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>AdmissionSeason – Top Colleges, Exams & Courses in India 2026</title>
<meta name="description" content="India's leading college discovery platform. Find top colleges, exams, courses, fees, rankings, and admission updates.">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=8">
</head>
<body>

<!-- ═══ BG CANVAS ═══ -->
<div class="bg-canvas">
  <div class="floating-shape"></div>
  <div class="floating-shape"></div>
  <div class="floating-shape"></div>
  <div class="floating-shape"></div>
  <div class="floating-shape"></div>
</div>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/hero.php'; ?>
<?php include 'includes/marquee.php'; ?>
<?php include 'includes/streams.php'; ?>
<?php include 'includes/top_ranked.php'; ?>
<?php include 'includes/featured_colleges.php'; ?>
<?php include 'includes/tools.php'; ?>
<?php include 'includes/exams.php'; ?>
<?php include 'includes/featured_exams.php'; ?>
<?php include 'includes/courses.php'; ?>
<?php include 'includes/reviews.php'; ?>
<?php include 'includes/news.php'; ?>
<?php include 'includes/stats.php'; ?>
<?php include 'includes/newsletter.php'; ?>

<!-- ═══ FOOTER ═══ -->
<?php include 'includes/footer.php'; ?>

<button class="scroll-top" id="scrollTop"><i class="ph ph-arrow-up"></i></button>
<script src="assets/js/main.js?v=6"></script>
</body>
</html>
