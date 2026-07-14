<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/panel_cms_2847/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/college_helpers.php';
require_once __DIR__ . '/includes/university_helpers.php';
require_once __DIR__ . '/includes/exam_helpers.php';
require_once __DIR__ . '/includes/course_helpers.php';
require_once __DIR__ . '/includes/news_seo_helpers.php';
require_once __DIR__ . '/includes/school_helpers.php';

// ─── SIMPLE ROUTER ───
$route = trim($_GET['url'] ?? '/', '/');

// Strip /ADMISSION/ prefix if present (localhost RewriteBase workaround)
if (stripos($route, 'ADMISSION/') === 0) {
    $route = substr($route, 10);
}

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

if ($routeBase === 'schools') {
    require __DIR__ . '/schools.php';
    exit;
}

if ($routeBase === 'school' && !empty($routeParts[1])) {
    $_GET['slug'] = $routeParts[1];
    if (!empty($routeParts[2])) $_GET['tab'] = $routeParts[2];
    require __DIR__ . '/school.php';
    exit;
}

$totalColleges = cCol($pdo, "SELECT COUNT(*) FROM colleges WHERE status='active'");
$totalReviews  = cCol($pdo, "SELECT COUNT(*) FROM reviews WHERE moderation_status='approved'");
$totalExams    = cCol($pdo, "SELECT COUNT(*) FROM exams");
$totalCourses  = cCol($pdo, "SELECT COUNT(*) FROM courses WHERE status='active'");
$totalStudents = cCol($pdo, "SELECT COALESCE(SUM(total_students),0) FROM colleges WHERE status='active'");

$categories = cAll($pdo, "SELECT id,category_name,category_slug,icon_url FROM course_categories ORDER BY sort_order ASC, category_name ASC LIMIT 6");
if (empty($categories)) {
  $categories = cAll($pdo, "SELECT DISTINCT course_level AS category_name, LOWER(course_level) AS category_slug FROM courses WHERE status='active' LIMIT 6");
}
// Dynamic stream counts
$catFallback = [];
$streamSlugMap = ['engineering'=>['Engineering','IT & Software'],'management'=>['Management','Commerce'],'medical'=>['Medical','Nursing'],'law'=>['Law'],'arts'=>['Arts'],'science'=>['Science'],'commerce'=>['Commerce']];
foreach ($streamSlugMap as $slug => $cats) {
    $ph = implode(',', array_fill(0, count($cats), '?'));
    $cnt = $pdo->prepare("SELECT COUNT(*) as cnt FROM courses WHERE status='active' AND course_category IN ($ph)");
    $cnt->execute($cats);
    $c = $cnt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
    $catFallback[] = ['name'=>ucfirst($slug),'slug'=>$slug,'icon'=>['engineering'=>'ph-laptop','management'=>'ph-briefcase','medical'=>'ph-stethoscope','law'=>'ph-scales','arts'=>'ph-palette','science'=>'ph-flask','commerce'=>'ph-chart-line'][$slug],'count'=>number_format($c).'+'];
}

$sqlC = "SELECT c.id,c.name,c.slug,c.college_type,c.naac_grade,c.ranking_nirf,c.overall_rating_avg,c.total_reviews,
                c.established_year,c.total_students,c.is_featured,c.featured_order,
                s.name AS state_name,ct.name AS city_name,cm.cover_image_url,cm.logo_url,
                (SELECT MAX(avg_package_lpa) FROM college_placements cp WHERE cp.college_id=c.id) AS avg_package,
                (SELECT MAX(highest_package_lpa) FROM college_placements cp WHERE cp.college_id=c.id) AS highest_package,
                (SELECT MIN(annual_fee) FROM college_courses cc WHERE cc.college_id=c.id) AS min_fee
         FROM colleges c LEFT JOIN states s ON c.state_id=s.id LEFT JOIN cities ct ON c.city_id=ct.id
         LEFT JOIN college_media cm ON cm.college_id=c.id AND (cm.image_type IS NULL OR cm.image_type='cover' OR cm.image_type='campus')
         WHERE c.status='active' AND c.is_featured=1 ORDER BY (c.featured_order > 0) DESC, c.featured_order ASC, c.overall_rating_avg DESC LIMIT 6";
$featuredColleges = cAll($pdo, $sqlC);
if (empty($featuredColleges)) $featuredColleges = cAll($pdo, str_replace("AND c.is_featured=1","AND c.overall_rating_avg>0",$sqlC));
// Prefer colleges with actual data for rankings display
$rankedColleges = array_filter($featuredColleges, fn($c) => !empty($c['avg_package']) || !empty($c['overall_rating_avg']));
if (count($rankedColleges) < 5) $rankedColleges = $featuredColleges;

$sqlCourses = "SELECT id,course_name,course_slug,course_level,duration_years,avg_salary_lpa,total_colleges_offering,description FROM courses WHERE status='active' ORDER BY total_colleges_offering DESC LIMIT 8";
$popularCourses = cAll($pdo, str_replace("WHERE status='active'","WHERE status='active' AND is_popular=1",$sqlCourses));
if (empty($popularCourses)) $popularCourses = cAll($pdo, $sqlCourses);

$upcomingExams = cAll($pdo, "SELECT e.id,e.exam_name AS name,e.exam_slug AS slug,e.exam_abbreviation AS abbr,e.exam_level AS level,e.participating_colleges_count AS colleges,e.applicants_last_year AS applicants,MIN(ed.exam_date) AS exam_date,MIN(ed.application_end) AS app_end,MIN(ed.application_start) AS app_start FROM exams e LEFT JOIN exam_dates ed ON ed.exam_id=e.id AND (ed.exam_date>=CURDATE() OR ed.application_end>=CURDATE()) WHERE e.status='active' GROUP BY e.id ORDER BY e.applicants_last_year DESC LIMIT 8");

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

// Featured schools
$featuredSchools = cAll($pdo, "SELECT s.id, s.name, s.slug, s.school_type, s.board_affiliation,
    s.overall_rating_avg, s.total_reviews, s.established_year, s.total_students,
    st.name AS state_name, ci.name AS city_name,
    sm.logo_url, sm.cover_image_url
    FROM schools s
    LEFT JOIN states st ON s.state_id = st.id
    LEFT JOIN cities ci ON s.city_id = ci.id
    LEFT JOIN school_media sm ON sm.school_id = s.id AND (sm.image_type='cover' OR sm.image_type IS NULL)
    WHERE s.status = 'active' AND s.is_featured = 1
    ORDER BY (s.featured_order > 0) DESC, s.featured_order ASC, s.overall_rating_avg DESC, s.name ASC LIMIT 6");
if (empty($featuredSchools)) {
    $featuredSchools = cAll($pdo, "SELECT s.id, s.name, s.slug, s.school_type, s.board_affiliation,
        s.overall_rating_avg, s.total_reviews, s.established_year, s.total_students,
        st.name AS state_name, ci.name AS city_name,
        sm.logo_url, sm.cover_image_url
        FROM schools s
        LEFT JOIN states st ON s.state_id = st.id
        LEFT JOIN cities ci ON s.city_id = ci.id
        LEFT JOIN school_media sm ON sm.school_id = s.id AND (sm.image_type='cover' OR sm.image_type IS NULL)
        WHERE s.status = 'active' AND s.overall_rating_avg > 0
        ORDER BY s.overall_rating_avg DESC, s.name ASC LIMIT 6");
}

$newsItems = cAll($pdo, "SELECT a.article_slug, a.article_title as title, a.featured_image_url as img, a.publish_at as date, c.category_name as cat FROM articles a LEFT JOIN article_categories c ON a.category_id=c.id WHERE a.status='published' ORDER BY a.publish_at DESC LIMIT 4");

$siteBase = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en" prefix="og: https://ogp.me/ns#">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<!-- Primary SEO -->
<title>AdmissionSeason – Top Colleges, Exams & Courses in India 2026</title>
<meta name="description" content="India's leading college discovery platform. Find 500+ top colleges, 100+ courses, 20+ entrance exams with fees, rankings, placements & admission details. Compare and shortlist your dream college.">
<meta name="keywords" content="colleges in India, top colleges, engineering colleges, medical colleges, MBA colleges, entrance exams, JEE, NEET, CUET, college fees, college rankings, NIRF rankings, college admissions, course comparison, college predictor">
<meta name="author" content="AdmissionSeason">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="googlebot" content="index, follow">
<link rel="canonical" href="<?= $siteBase ?>/">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $siteBase ?>/">
<meta property="og:title" content="AdmissionSeason – Top Colleges, Exams & Courses in India 2026">
<meta property="og:description" content="India's leading college discovery platform. Find 500+ top colleges, 100+ courses, 20+ entrance exams with fees, rankings, placements & admission details.">
<meta property="og:image" content="<?= $siteBase ?>/assets/img/logo.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="AdmissionSeason - Find Your Dream College in India">
<meta property="og:site_name" content="AdmissionSeason">
<meta property="og:locale" content="en_IN">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?= $siteBase ?>/">
<meta name="twitter:title" content="AdmissionSeason – Top Colleges, Exams & Courses in India 2026">
<meta name="twitter:description" content="India's leading college discovery platform. Find 500+ top colleges, 100+ courses, 20+ entrance exams with fees, rankings, placements & admission details.">
<meta name="twitter:image" content="<?= $siteBase ?>/assets/img/logo.png">
<meta name="twitter:image:alt" content="AdmissionSeason - Find Your Dream College in India">
<meta name="twitter:site" content="@AdmissionSeason">
<meta name="twitter:creator" content="@AdmissionSeason">

<!-- Additional Meta -->
<meta name="theme-color" content="#0B2447">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="alternate" hreflang="en-in" href="<?= $siteBase ?>/">

<!-- Structured Data - WebSite -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "AdmissionSeason",
  "url": "<?= $siteBase ?>/",
  "description": "India's leading college discovery platform. Find top colleges, exams, courses, fees, rankings, and admission updates.",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "<?= $siteBase ?>/colleges.php?q={search_term_string}"
    },
    "query-input": "required name=search_term_string"
  }
}
</script>

<!-- Structured Data - Organization -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "EducationalOrganization",
  "@id": "<?= $siteBase ?>/#organization",
  "name": "AdmissionSeason",
  "url": "<?= $siteBase ?>/",
  "logo": "<?= $siteBase ?>/assets/img/logo.png",
  "description": "India's leading college discovery platform helping students find the right college, course, and career path.",
  "foundingDate": "2024",
  "areaServed": {
    "@type": "Country",
    "name": "India"
  },
  "sameAs": [
    "https://facebook.com/admissionseason",
    "https://twitter.com/admissionseason",
    "https://instagram.com/admissionseason",
    "https://linkedin.com/company/admissionseason",
    "https://youtube.com/@admissionseason"
  ]
}
</script>

<!-- Structured Data - BreadcrumbList -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [{
    "@type": "ListItem",
    "position": 1,
    "name": "Home",
    "item": "<?= $siteBase ?>/"
  }]
}
</script>

<!-- Performance Hints -->
<?php include __DIR__ . '/includes/favicon.php'; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="dns-prefetch" href="https://unpkg.com">

<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=<?= time() ?>">
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
<?php include 'includes/stats.php'; ?>
<?php include 'includes/marquee.php'; ?>
<?php include 'includes/streams.php'; ?>
<?php include 'includes/featured_colleges.php'; ?>
<?php include 'includes/featured_schools.php'; ?>
<?php include 'includes/top_ranked.php'; ?>
<?php include 'includes/tools.php'; ?>
<?php include 'includes/exams.php'; ?>
<?php include 'includes/courses.php'; ?>
<?php include 'includes/reviews.php'; ?>
<?php include 'includes/news.php'; ?>

<!-- ═══ FOOTER ═══ -->
<?php include 'includes/footer.php'; ?>

<button class="scroll-top" id="scrollTop"><i class="ph ph-arrow-up"></i></button>
<script src="assets/js/main.js?v=7"></script>
</body>
</html>
