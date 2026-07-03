<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';
require_once __DIR__ . '/includes/news_seo_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$loginUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']);

$slug = trim($_GET['slug'] ?? '');
$tab  = trim($_GET['tab'] ?? 'info');
$tabs = collegeTabs();

if ($slug === '') {
    header('Location: colleges.php');
    exit;
}
if (!isset($tabs[$tab])) {
    $tab = 'info';
}

$college = loadCollegeBySlug($pdo, $slug);
if (!$college) {
    header('HTTP/1.0 404 Not Found');
    header('Location: colleges.php');
    exit;
}

$cid = $college['id'];
$ratings = collegeRatingBreakdown($pdo, $cid);
$overallRating = $ratings['overall'] ?? $college['overall_rating_avg'] ?? 0;
$reviewCount = (int)($ratings['count'] ?? $college['total_reviews'] ?? 0);

$courses = $placements = $cutoffs = $rankings = $gallery = $faculty = $faqs = $qnaList = $updates = $reviews = [];

try { $s=$pdo->prepare("SELECT * FROM college_courses WHERE college_id=? ORDER BY course_name ASC"); $s->execute([$cid]); $courses=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT * FROM college_placements WHERE college_id=? ORDER BY placement_year DESC"); $s->execute([$cid]); $placements=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT cc.*, cc.year AS cutoff_year, e.exam_name, cc2.course_name FROM college_cutoffs cc LEFT JOIN exams e ON e.id=cc.exam_id LEFT JOIN college_courses cc2 ON cc2.id=cc.course_id WHERE cc.college_id=? ORDER BY cc.year DESC, e.exam_name ASC"); $s->execute([$cid]); $cutoffs=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT * FROM rankings WHERE college_id=? ORDER BY ranking_year DESC,rank_position ASC"); $s->execute([$cid]); $rankings=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT id,college_id,image_url,video_url,video_type,caption,image_type,document_url,document_type,logo_url,cover_image_url,`360_tour_url`,virtual_tour_enabled FROM college_media WHERE college_id=? ORDER BY sort_order ASC"); $s->execute([$cid]); $gallery=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT * FROM college_faculty WHERE college_id=? ORDER BY faculty_name ASC"); $s->execute([$cid]); $faculty=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT * FROM college_faqs WHERE college_id=? AND is_active=1 ORDER BY sort_order ASC"); $s->execute([$cid]); $faqs=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT * FROM college_qna WHERE college_id=? AND status='approved' ORDER BY created_at DESC LIMIT 50"); $s->execute([$cid]); $qnaList=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT * FROM college_updates WHERE college_id=? AND status='published' ORDER BY event_date DESC,created_at DESC LIMIT 30"); $s->execute([$cid]); $updates=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $cn=$college['name']; $sl=$slug; $s=$pdo->prepare("SELECT *, article_title AS title, excerpt AS description, featured_image_url AS image_url, publish_at AS event_date, article_type AS update_type FROM articles WHERE status='published' AND (article_title LIKE ? OR article_title LIKE ? OR tags LIKE ? OR tags LIKE ?) ORDER BY publish_at DESC LIMIT 10"); $s->execute(["%$cn%","%$sl%","%$cn%","%$sl%"]); $articles=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
$updates = array_merge($updates, $articles);
usort($updates, function($a,$b){ return strtotime($b['event_date']??'0') - strtotime($a['event_date']??'0'); });
try { $s=$pdo->prepare("SELECT r.*,u.full_name AS user_name FROM reviews r LEFT JOIN users u ON u.id=r.user_id WHERE r.college_id=? AND (r.moderation_status='approved' OR (r.moderation_status='pending' AND r.user_id=?)) ORDER BY FIELD(r.moderation_status,'pending','approved'), r.created_at DESC LIMIT 30"); $s->execute([$cid, $_SESSION['user_id'] ?? '']); $reviews=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
$seatMatrix = [];
try { $s=$pdo->prepare("SELECT sm.*, cc.course_name FROM seat_matrix sm JOIN college_courses cc ON cc.id = sm.course_id WHERE sm.college_id = ? ORDER BY cc.course_name, FIELD(sm.category, 'General','OBC','SC','ST','EWS','PwD','NRI','Mgmt')"); $s->execute([$cid]); $seatMatrix=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}

$qnaCount = count($faqs) + count($qnaList);
$year = $college['established_year'] ?? $college['founded_year'] ?? '';
$location = trim(($college['city_name'] ?? '') . ($college['city_name'] && $college['state_name'] ? ', ' : '') . ($college['state_name'] ?? ''));
$typeLabel = collegeTypeLabel($college['college_type'], $college['ownership']);
$highlights = jsonLines($college['highlights_json'] ?? null);
$accreditations = jsonLines($college['accreditations_json'] ?? null);

$pageTitle = $college['meta_title'] ?: ($college['name'] . ': Fees, Admission ' . date('Y') . ', Courses, Placements, Ranking');
$metaDesc = $college['meta_description'] ?: ('Explore ' . $college['name'] . ' — courses, fees, placements, cutoffs, reviews and admission details.');

$ratingItems = [
    ['key'=>'placements',     'label'=>'Placements',       'icon'=>'ph-briefcase',     'val'=>$ratings['placements'] ?? 0],
    ['key'=>'infrastructure', 'label'=>'Infrastructure',   'icon'=>'ph-buildings',     'val'=>$ratings['infrastructure'] ?? 0],
    ['key'=>'faculty',        'label'=>'Faculty & Course', 'icon'=>'ph-book-open',     'val'=>$ratings['faculty'] ?? 0],
    ['key'=>'campus_life',    'label'=>'Campus Life',      'icon'=>'ph-users-three',   'val'=>$ratings['campus_life'] ?? 0],
    ['key'=>'value_money',    'label'=>'Value for Money',  'icon'=>'ph-currency-inr',  'val'=>$ratings['value_money'] ?? 0],
];

$brochureUrl = '';
$prospectusUrl = '';
foreach ($gallery as $m) {
    if (!empty($m['document_url'])) {
        $docType = $m['document_type'] ?? '';
        if ($docType === 'brochure' && !$brochureUrl) {
            $brochureUrl = $m['document_url'];
        } elseif ($docType === 'prospectus' && !$prospectusUrl) {
            $prospectusUrl = $m['document_url'];
        }
    }
}

// Icons for tabs
$tabIcons = [
    'info'=>'ph-info','courses'=>'ph-book-open','fees'=>'ph-currency-inr',
    'reviews'=>'ph-star','admissions'=>'ph-paper-plane-tilt','placements'=>'ph-briefcase',
    'cutoffs'=>'ph-scissors','seat_matrix'=>'ph-table','rankings'=>'ph-trophy','gallery'=>'ph-images',
    'infrastructure'=>'ph-buildings','faculty'=>'ph-chalkboard-teacher',
    'compare'=>'ph-scales','qna'=>'ph-chat-circle','news'=>'ph-newspaper',
];

// Check if logged-in user already applied to this college
$userAlreadyApplied = null;
$userSavedThisCollege = false;
if (isset($_SESSION['user_id'])) {
    try {
        $ua = $pdo->prepare("SELECT application_number, status FROM applications WHERE user_id = ? AND college_id = ? LIMIT 1");
        $ua->execute([$_SESSION['user_id'], $cid]);
        $userAlreadyApplied = $ua->fetch(PDO::FETCH_ASSOC) ?: null;
        
        $us = $pdo->prepare("SELECT id FROM saved_colleges WHERE user_id = ? AND college_id = ? LIMIT 1");
        $us->execute([$_SESSION['user_id'], $cid]);
        $userSavedThisCollege = (bool)$us->fetch();
    } catch(Exception $e) {}
}

$siteBase = getBaseUrl();
$canonicalUrl = $siteBase . '/college/' . $slug;

// College image for OG
$collegeImage = '';
foreach ($gallery as $m) {
    if (!empty($m['cover_image_url'])) { $collegeImage = $m['cover_image_url']; break; }
    if (!empty($m['logo_url'])) { $collegeImage = $m['logo_url']; break; }
}
if (!empty($collegeImage) && !str_starts_with($collegeImage, 'http')) {
    $collegeImage = $siteBase . '/' . ltrim($collegeImage, '/');
} elseif (empty($collegeImage)) {
    $collegeImage = $siteBase . '/assets/img/logo.png';
}

$minFee = '';
foreach ($courses as $co) {
    if (!empty($co['annual_fee']) && $co['annual_fee'] > 0) {
        $minFee = '₹' . number_format($co['annual_fee']) . '/year';
        break;
    }
}
$maxPackage = '';
foreach ($placements as $p) {
    if (!empty($p['avg_package_lpa']) && $p['avg_package_lpa'] > 0) {
        $maxPackage = '₹' . $p['avg_package_lpa'] . ' LPA';
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/favicon.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - AdmissionSeason</title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($college['name']) ?>, <?= htmlspecialchars($typeLabel) ?>, <?= htmlspecialchars($location) ?>, college fees, placements, ranking, admission <?= date('Y') ?>">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
  <meta name="googlebot" content="index, follow">
  <link rel="canonical" href="<?= $canonicalUrl ?>">
  <meta name="author" content="AdmissionSeason">
  <meta name="revisit-after" content="7 days">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="university">
  <meta property="og:url" content="<?= $canonicalUrl ?>">
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta property="og:image" content="<?= $collegeImage ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:site_name" content="AdmissionSeason">
  <meta property="og:locale" content="en_IN">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= $canonicalUrl ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="twitter:image" content="<?= $collegeImage ?>">
  <meta name="twitter:site" content="@AdmissionSeason">
  <meta name="twitter:creator" content="@AdmissionSeason">

  <!-- GEO Meta Tags -->
  <meta name="geo.region" content="IN">
  <meta name="geo.placename" content="<?= htmlspecialchars($location ?: 'India') ?>">
  <meta name="language" content="English">
  <link rel="alternate" hreflang="en-in" href="<?= $canonicalUrl ?>">

  <!-- Structured Data: CollegeOrUniversity -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollegeOrUniversity',
    'name' => $college['name'],
    'url' => $canonicalUrl,
    'description' => $metaDesc,
    'image' => $collegeImage,
    'address' => !empty($location) ? [
      '@type' => 'PostalAddress',
      'addressLocality' => $college['city_name'] ?? '',
      'addressRegion' => $college['state_name'] ?? '',
      'addressCountry' => 'IN'
    ] : null,
    'telephone' => $college['phone'] ?? null,
    'email' => $college['email'] ?? null,
    'foundingDate' => $college['established_year'] ? (string)$college['established_year'] : null,
    'numberOfStudents' => !empty($college['total_students']) ? $college['total_students'] : null,
    'hasCredential' => !empty($college['naac_grade']) ? 'NAAC ' . $college['naac_grade'] : null,
    'aggregateRating' => $overallRating > 0 ? [
      '@type' => 'AggregateRating',
      'ratingValue' => number_format($overallRating, 1),
      'bestRating' => '5',
      'ratingCount' => $reviewCount,
      'reviewCount' => $reviewCount,
    ] : null,
    'offers' => !empty($minFee) ? [
      '@type' => 'Offer',
      'price' => preg_replace('/[^0-9]/', '', $minFee),
      'priceCurrency' => 'INR',
      'description' => 'Annual Tuition Fee'
    ] : null,
    'sameAs' => array_filter([
      $college['facebook_url'] ?? null,
      $college['twitter_url'] ?? null,
      $college['linkedin_url'] ?? null,
      $college['youtube_url'] ?? null,
      $college['instagram_url'] ?? null,
    ]),
    'publisher' => [
      '@type' => 'Organization',
      'name' => 'AdmissionSeason',
      'url' => $siteBase,
      'logo' => ['@type' => 'ImageObject', 'url' => "$siteBase/assets/img/logo.png", 'width' => 600, 'height' => 60]
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <!-- Structured Data: BreadcrumbList -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => array_values(array_filter([
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => "$siteBase/"],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'Colleges', 'item' => "$siteBase/colleges"],
      !empty($college['state_name']) ? ['@type' => 'ListItem', 'position' => 3, 'name' => $college['state_name'], 'item' => "$siteBase/colleges?state=" . urlencode($college['state_id'] ?? '')] : null,
      ['@type' => 'ListItem', 'position' => !empty($college['state_name']) ? 4 : 3, 'name' => $college['name']],
    ]))
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <!-- Structured Data: FAQPage -->
  <?php if (!empty($faqs)): ?>
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(function($faq) {
        return [
          '@type' => 'Question',
          'name' => $faq['question'],
          'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $faq['answer']
          ]
        ];
    }, array_slice($faqs, 0, 10))
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>
  <?php endif; ?>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/college-pages.css?v=<?= time() ?>">
  <style>
    /* Detail page extras */
    .cr-sub-ratings{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:12px}
    .cr-sub-item{display:flex;align-items:center;justify-content:space-between;
      font-size:.8rem;color:rgba(15,23,42,0.65);gap:8px}
    .cr-sub-bar{flex:1;height:4px;background:rgba(15,23,42,0.08);border-radius:2px;overflow:hidden}
    .cr-sub-fill{height:100%;background:linear-gradient(90deg,#19376D,#19376D);border-radius:2px}
    .cr-sub-val{font-weight:700;color:#0F172A;min-width:24px;text-align:right}
    .overview-stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:24px}
    .overview-stat{text-align:center;padding:18px 12px;background:linear-gradient(135deg,#f8fafc,rgba(11,36,71,0.06));
      border-radius:14px;border:1px solid rgba(37,99,235,.1)}
    .overview-stat-val{font-size:1.4rem;font-weight:800;color:#19376D;font-family:'Plus Jakarta Sans',sans-serif}
    .overview-stat-lbl{font-size:.72rem;color:rgba(15,23,42,0.45);margin-top:4px;text-transform:uppercase;letter-spacing:.4px}
    .course-level-badge{display:inline-block;padding:2px 9px;border-radius:20px;font-size:.7rem;font-weight:700;text-transform:uppercase}
    .level-ug{background:rgba(11,36,71,0.04);color:#0B2447}
    .level-pg{background:rgba(11,36,71,0.06);color:#19376D}
    .level-phd{background:rgba(11,36,71,0.04);color:#0B2447}
    .level-diploma{background:rgba(11,36,71,0.04);color:#0B2447}
    .level-certificate{background:rgba(11,36,71,0.06);color:#0F172A}
    .placement-highlight{
      display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:20px;
    }
    .ph-stat{padding:20px;background:linear-gradient(135deg,rgba(11,36,71,0.06),rgba(11,36,71,0.06));
      border-radius:14px;border:1px solid rgba(37,99,235,.15);text-align:center}
    .ph-stat strong{display:block;font-size:1.3rem;font-weight:800;color:#19376D}
    .ph-stat span{font-size:.75rem;color:rgba(15,23,42,0.45);text-transform:uppercase;letter-spacing:.4px}
    .compare-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-top:20px}
    .compare-card{padding:20px;background:var(--cp-light,#f8fafc);border-radius:14px;border:1.5px solid rgba(15,23,42,0.08);text-align:center}
    .compare-card strong{display:block;font-size:1.35rem;font-weight:800;color:#19376D;margin-bottom:4px}
    .compare-card span{font-size:.78rem;color:rgba(15,23,42,0.45);text-transform:uppercase;letter-spacing:.4px}
    .tab-empty-state{text-align:center;padding:48px 24px;color:rgba(15,23,42,0.4)}
    .tab-empty-state i{font-size:3rem;display:block;margin-bottom:12px}
    .tab-empty-state p{font-size:.92rem}
    .news-type-badge{font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;
      letter-spacing:.4px;background:rgba(11,36,71,0.06);color:#19376D;display:inline-block}

    /* Tab scroll arrows */
    .college-tabs-wrapper {
      position: relative;
      display: block;
      width: 100%;
      max-width: 100%;
      overflow: hidden;
    }
    .college-tabs-wrapper .shiksha-tabs {
      display: flex;
      gap: 4px;
      overflow-x: auto;
      scrollbar-width: none;
      scroll-behavior: smooth;
      padding: 8px 0;
    }
    @media (min-width: 769px) {
      .college-tabs-wrapper.has-scroll .shiksha-tabs {
        padding: 8px 48px;
      }
    }
    .college-tabs-wrapper .shiksha-tabs::-webkit-scrollbar {
      display: none;
    }
    /* Right-edge scroll hint */
    .college-tabs-wrapper.has-scroll::after {
      content: '';
      position: absolute;
      top: 0; bottom: 0;
      right: 0;
      width: 48px;
      background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.95) 50%, #fff 100%);
      pointer-events: none;
      z-index: 5;
      transition: opacity 0.3s;
    }
    .college-tabs-wrapper.scroll-end::after {
      opacity: 0;
    }
    @media (max-width: 768px) {
      .college-tabs-wrapper.has-scroll .tab-arrow { display: flex !important; }
      .college-tabs-wrapper .tab-arrow { opacity: 1 !important; pointer-events: auto !important; }
      .college-tabs-wrapper .tab-arrow-left { padding-left: 4px; }
      .college-tabs-wrapper .tab-arrow-right { padding-right: 4px; }
      .college-tabs-wrapper .tab-arrow i { width: 26px; height: 26px; font-size: .85rem; }
      .college-tabs-wrapper .shiksha-tabs { padding: 8px 36px; }
      .college-tabs-wrapper.has-scroll::after { display: none; }
    }

    /* Review modal responsive */
    @media (max-width: 480px) {
      #reviewModal > div { border-radius: 16px; margin: 0 8px; }
      #reviewModal > div > div:first-child { padding: 16px 18px 0; }
      #reviewModal > div > div:first-child h3 { font-size: 1.05rem; gap: 6px; }
      #reviewModal > div > div:last-child { padding: 16px 18px 24px; }
      #reviewModal .star-rating-group i { font-size: 1.5rem !important; }
      #reviewModal [style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; gap: 10px !important; }
    }
    .tab-arrow {
      position: absolute;
      top: 0;
      bottom: 0;
      width: 64px;
      border: none;
      background: none;
      padding: 0;
      margin: 0;
      outline: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      z-index: 10;
      transition: all .25s ease;
    }
    .tab-arrow-left {
      left: 0;
      background: linear-gradient(90deg, #ffffff 50%, rgba(255,255,255,0));
      justify-content: flex-start;
      padding-left: 8px;
    }
    .tab-arrow-right {
      right: 0;
      background: linear-gradient(270deg, #ffffff 50%, rgba(255,255,255,0));
      justify-content: flex-end;
      padding-right: 8px;
    }
    .tab-arrow i {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #ffffff;
      border: 1px solid rgba(15,23,42,0.12);
      box-shadow: 0 4px 10px rgba(11,36,71,0.12);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      color: #19376D;
      transition: all 0.2s ease;
    }
    .tab-arrow:hover i {
      color: #ffffff;
      background: #19376D;
      border-color: #19376D;
      transform: scale(1.1);
    }
    .tab-arrow.hidden {
      opacity: 0;
      pointer-events: none;
    }
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ══════════════════════════════════════════════════════════════════
     HERO
     ══════════════════════════════════════════════════════════════════ -->
<div class="college-hero" style="background-image:url('<?= cImg($college['cover_image_url']) ?>')">
  <div class="college-hero-overlay"></div>
  <div class="container college-hero-inner">
    <div class="shiksha-breadcrumb college-breadcrumb">
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/index.php">Home</a>
      <i class="ph ph-caret-right"></i>
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/colleges.php">Colleges</a>
      <i class="ph ph-caret-right"></i>
      <span><?= htmlspecialchars($college['name']) ?></span>
    </div>
    <div class="college-hero-card">
      <div class="college-hero-main">
        <?php if ($college['logo_url']): ?>
        <img src="<?= cImg($college['logo_url']) ?>" class="college-hero-logo" alt="<?= htmlspecialchars($college['name']) ?>">
        <?php endif; ?>
        <div>
          <h1 class="college-hero-title"><?= htmlspecialchars($college['name']) ?></h1>
          <p class="college-hero-sub"><?= htmlspecialchars($college['name']) ?>: Fees, Admission <?= date('Y') ?>, Courses, Placements<?= !empty($college['ranking_nirf']) ? ', Ranking, Cutoff' : '' ?></p>
          <div class="college-hero-chips">
            <?php if ($location): ?><span><i class="ph ph-map-pin"></i> <?= htmlspecialchars($location) ?></span><?php endif; ?>
            <?php if ($overallRating > 0): ?>
            <span><i class="ph ph-star-fill" style="color:#19376D"></i> <?= number_format((float)$overallRating,1) ?> / 5</span>
            <span><?= $reviewCount ?> Reviews</span>
            <?php endif; ?>
            <?php if ($qnaCount > 0): ?><span><i class="ph ph-chat-circle"></i> Student Q&A</span><?php endif; ?>
            <span><i class="ph ph-buildings"></i> <?= htmlspecialchars($typeLabel) ?></span>
            <?php if ($year): ?><span><i class="ph ph-calendar"></i> Estd <?= htmlspecialchars((string)$year) ?></span><?php endif; ?>
            <?php if (!empty($college['is_verified'])): ?><span style="background:rgba(22,163,74,.35);border-color:rgba(22,163,74,.5)"><i class="ph-fill ph-seal-check"></i> Verified</span><?php endif; ?>
            <?php if (!empty($college['ranking_nirf'])): ?><span><i class="ph ph-trophy"></i> NIRF #<?= (int)$college['ranking_nirf'] ?></span><?php endif; ?>
            <?php if (!empty($college['naac_grade'])): ?><span>NAAC <?= htmlspecialchars($college['naac_grade']) ?></span><?php endif; ?>
            <?php if (!empty($college['ugc_approved'])): ?><span>UGC ✓</span><?php endif; ?>
            <?php if (!empty($college['aicte_approved'])): ?><span>AICTE ✓</span><?php endif; ?>
          </div>
        </div>
      </div>
      <div class="college-hero-actions">
        <button type="button" class="college-btn-outline" id="saveCollegeBtn" title="Save to wishlist" onclick="toggleSaveCollege()">
          <?php if ($userSavedThisCollege): ?>
            <i class="ph-fill ph-heart" style="color:#e11d48"></i> Saved
          <?php else: ?>
            <i class="ph ph-heart"></i> Save
          <?php endif; ?>
        </button>
        <?php if ($brochureUrl): ?>
        <?php if ($isLoggedIn): ?>
        <button type="button" class="college-btn-primary" onclick="sendBrochure()" id="brochureBtnHero">
          <i class="ph ph-download-simple"></i> Brochure
        </button>
        <?php else: ?>
        <button type="button" class="college-btn-primary" onclick="openLoginPrompt()" id="brochureBtnHero">
          <i class="ph ph-download-simple"></i> Brochure
        </button>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($prospectusUrl): ?>
        <a href="<?= htmlspecialchars($prospectusUrl) ?>" target="_blank" class="college-btn-primary">
          <i class="ph ph-file-text"></i> Prospectus
        </a>
        <?php endif; ?>
        <?php if (!empty($courses)): ?>
        <?php if ($isLoggedIn): ?>
        <button type="button" class="college-btn-primary" onclick="sendCourseList()" id="courseListBtnHero">
          <i class="ph ph-files"></i> Course List
        </button>
        <?php else: ?>
        <button type="button" class="college-btn-primary" onclick="openLoginPrompt()">
          <i class="ph ph-files"></i> Course List
        </button>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($userAlreadyApplied): ?>
        <div class="college-applied-badge">
          <i class="ph-fill ph-check-circle"></i>
          <span>Already Applied</span>
          <span class="college-applied-appno"><?= htmlspecialchars($userAlreadyApplied['application_number']) ?></span>
        </div>
        <?php elseif ($isLoggedIn): ?>
        <button type="button" class="college-btn-primary" onclick="openApplyModal()">
          <i class="ph ph-paper-plane-tilt"></i> Apply Now
        </button>
        <?php else: ?>
        <button type="button" class="college-btn-primary" onclick="openLoginPrompt()">
          <i class="ph ph-paper-plane-tilt"></i> Apply Now
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     TABS NAV
     ══════════════════════════════════════════════════════════════════ -->
<div class="shiksha-tabs-nav college-tabs-sticky">
  <div class="container">
    <div class="college-tabs-wrapper">
      <button class="tab-arrow tab-arrow-left" onclick="scrollTabs(-1)" aria-label="Scroll left"><i class="ph ph-caret-left"></i></button>
      <div class="shiksha-tabs college-detail-tabs" id="collegeTabs">
        <?php foreach ($tabs as $key => $label): ?>
        <a href="<?= collegeUrl($slug, $key) ?>" class="<?= $tab === $key ? 'active' : '' ?>">
          <?php if (isset($tabIcons[$key])): ?><i class="ph <?= $tabIcons[$key] ?>"></i> <?php endif; ?>
          <?= htmlspecialchars($label) ?>
          <?php if ($key === 'reviews' && $reviewCount > 0): ?><span style="background:rgba(11,36,71,0.06);color:#19376D;padding:1px 6px;border-radius:10px;font-size:.7rem;margin-left:3px"><?= $reviewCount ?></span><?php endif; ?>
          <?php if ($key === 'qna' && $qnaCount > 0): ?><span style="background:rgba(11,36,71,0.06);color:#19376D;padding:1px 6px;border-radius:10px;font-size:.7rem;margin-left:3px"><?= $qnaCount ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
      <button class="tab-arrow tab-arrow-right" onclick="scrollTabs(1)" aria-label="Scroll right"><i class="ph ph-caret-right"></i></button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     MAIN CONTENT
     ══════════════════════════════════════════════════════════════════ -->
<div class="container shiksha-main-wrapper college-detail-wrap">
  <div class="shiksha-layout">

    <main class="shiksha-content">
      <div class="college-tab-content">

        <!-- ── COLLEGE INFO ──────────────────────────────────────── -->
        <?php if ($tab === 'info'): ?>
          <p class="college-updated"><i class="ph ph-clock"></i> Last updated on <?= date('d M \'y') ?></p>

          <!-- Rating row -->
          <?php if (array_filter(array_column($ratingItems, 'val'))): ?>
          <div class="college-rating-row">
            <?php foreach ($ratingItems as $ri): if ((float)$ri['val'] <= 0) continue; ?>
            <div class="college-rating-pill">
              <i class="ph <?= $ri['icon'] ?>"></i>
              <div>
                <strong><?= number_format((float)$ri['val'], 1) ?></strong>
                <span><?= htmlspecialchars($ri['label']) ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Quick overview stats -->
          <div class="overview-stat-grid">
            <?php if ($year): ?><div class="overview-stat"><div class="overview-stat-val"><?= htmlspecialchars((string)$year) ?></div><div class="overview-stat-lbl">Established</div></div><?php endif; ?>
            <?php if (!empty($college['total_students'])): ?><div class="overview-stat"><div class="overview-stat-val"><?= number_format((int)$college['total_students']) ?>+</div><div class="overview-stat-lbl">Students</div></div><?php endif; ?>
            <?php if (!empty($college['total_faculty'])): ?><div class="overview-stat"><div class="overview-stat-val"><?= (int)$college['total_faculty'] ?>+</div><div class="overview-stat-lbl">Faculty</div></div><?php endif; ?>
            <?php if (!empty($college['campus_area_acres'])): ?><div class="overview-stat"><div class="overview-stat-val"><?= (float)$college['campus_area_acres'] ?></div><div class="overview-stat-lbl">Acres Campus</div></div><?php endif; ?>
            <?php if (!empty($college['ranking_nirf'])): ?><div class="overview-stat"><div class="overview-stat-val">#<?= (int)$college['ranking_nirf'] ?></div><div class="overview-stat-lbl">NIRF Rank</div></div><?php endif; ?>
            <div class="overview-stat"><div class="overview-stat-val"><?= count($courses) ?></div><div class="overview-stat-lbl">Courses</div></div>
          </div>

          <?php if (!empty($college['about_text'])): ?>
          <section class="college-section">
            <h2>About <?= htmlspecialchars($college['name']) ?></h2>
            <div class="college-prose"><?= nl2br(htmlspecialchars($college['about_text'])) ?></div>
          </section>
          <?php endif; ?>

          <?php if (!empty($highlights)): ?>
          <section class="college-section">
            <div class="college-highlights-card">
              <div class="college-highlights-header">
                <?php if ($college['logo_url']): ?>
                <img src="<?= cImg($college['logo_url']) ?>" alt="<?= htmlspecialchars($college['name']) ?>" class="college-highlights-logo">
                <?php else: ?>
                <div class="college-highlights-logo college-highlights-logo-placeholder"><i class="ph ph-graduation-cap"></i></div>
                <?php endif; ?>
                <div>
                  <h2>College Highlights</h2>
                  <p class="college-highlights-sub">Why <?= htmlspecialchars($college['name']) ?> stands out</p>
                </div>
              </div>
              <div class="college-highlight-grid">
                <?php foreach ($highlights as $i => $h):
                  $text = htmlspecialchars(is_array($h) ? ($h['text'] ?? json_encode($h)) : (string)$h);
                  $icons = ['ph-trophy','ph-star','ph-users','ph-map-pin','ph-medal','ph-books','ph-flask','ph-buildings','ph-globe-simple','ph-chart-line-up'];
                  $icon = $icons[$i % count($icons)];
                ?>
                <div class="college-highlight-chip">
                  <i class="ph <?= $icon ?>"></i>
                  <span><?= $text ?></span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </section>
          <?php endif; ?>

          <?php if (!empty($accreditations)): ?>
          <section class="college-section">
            <h2>Accreditations & Approvals</h2>
            <div class="college-tag-row">
              <?php foreach ($accreditations as $a): ?>
              <span class="college-tag"><?= htmlspecialchars(is_array($a) ? ($a['name'] ?? json_encode($a)) : (string)$a) ?></span>
              <?php endforeach; ?>
              <?php if ($college['naac_grade']): ?><span class="college-tag">NAAC <?= htmlspecialchars($college['naac_grade']) ?></span><?php endif; ?>
              <?php if (!empty($college['ugc_approved'])): ?><span class="college-tag">UGC Approved</span><?php endif; ?>
              <?php if (!empty($college['aicte_approved'])): ?><span class="college-tag">AICTE Approved</span><?php endif; ?>
            </div>
          </section>
          <?php endif; ?>

          <section class="college-section">
            <h2>Contact & Location</h2>
            <div class="college-contact-grid">
              <?php if ($college['address']): ?><p><i class="ph ph-map-pin"></i> <?= htmlspecialchars($college['address']) ?><?= $college['pincode'] ? ', ' . htmlspecialchars($college['pincode']) : '' ?></p><?php endif; ?>
              <?php if ($college['phone']): ?><p><i class="ph ph-phone"></i> <a href="tel:<?= htmlspecialchars($college['phone']) ?>"><?= htmlspecialchars($college['phone']) ?></a></p><?php endif; ?>
              <?php if ($college['email']): ?><p><i class="ph ph-envelope"></i> <a href="mailto:<?= htmlspecialchars($college['email']) ?>"><?= htmlspecialchars($college['email']) ?></a></p><?php endif; ?>
              <?php if ($college['website_url']): ?><p><i class="ph ph-globe"></i> <a href="<?= htmlspecialchars($college['website_url']) ?>" target="_blank" rel="noopener noreferrer">Official Website ↗</a></p><?php endif; ?>
            </div>
          </section>

        <!-- ── COURSES ───────────────────────────────────────────── -->
        <?php elseif ($tab === 'courses'): ?>
          <section class="college-section">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
              <h2 style="margin:0">Courses Offered <span class="college-count">(<?= count($courses) ?>)</span></h2>
              <?php if (!empty($courses)): ?>
              <?php if ($isLoggedIn): ?>
              <button type="button" onclick="sendCourseList()" id="courseListBtn" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#fff;color:#0B2447;border:2px solid #0B2447;border-radius:10px;font-size:.85rem;font-weight:700;cursor:pointer;transition:all .25s">
                <i class="ph ph-files"></i> Course List
              </button>
              <?php else: ?>
              <button type="button" onclick="openLoginPrompt()" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#fff;color:#0B2447;border:2px solid #0B2447;border-radius:10px;font-size:.85rem;font-weight:700;cursor:pointer;transition:all .25s">
                <i class="ph ph-files"></i> Course List
              </button>
              <?php endif; ?>
              <?php endif; ?>
            </div>
            <?php if (empty($courses)): ?>
            <div class="tab-empty-state"><i class="ph ph-book-open"></i><p>No courses listed yet for this college.</p></div>
            <?php else: ?>
            <div class="college-table-wrap">
              <table class="college-data-table courses-table">
                <thead><tr><th>Course Name</th><th>Level</th><th>Duration</th><th>Seats</th><th>Annual Fee</th><th>EMI</th></tr></thead>
                <tbody>
                <?php foreach ($courses as $co):
                  $levelMap = ['ug'=>'level-ug','pg'=>'level-pg','phd'=>'level-phd','diploma'=>'level-diploma','certificate'=>'level-certificate'];
                  $lvl = strtolower($co['course_level'] ?? '');
                  $levelClass = $levelMap[$lvl] ?? 'level-ug';
                ?>
                <tr>
                  <td data-label="Course">
                    <strong><?= htmlspecialchars($co['course_name'] ?? '—') ?></strong>
                    <?php if (!empty($co['specializations'])): ?>
                      <br><small style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px"><?php
                        $specs = is_string($co['specializations']) ? json_decode($co['specializations'], true) : $co['specializations'];
                        if (is_array($specs)) {
                          foreach ($specs as $sp) {
                            echo '<span style="display:inline-block;padding:2px 8px;border-radius:10px;background:rgba(37,99,235,0.08);color:#2563eb;font-size:11px;white-space:nowrap">' . htmlspecialchars($sp) . '</span>';
                          }
                        } else {
                          echo '<span style="display:inline-block;padding:2px 8px;border-radius:10px;background:rgba(37,99,235,0.08);color:#2563eb;font-size:11px;white-space:nowrap">' . htmlspecialchars($co['specializations']) . '</span>';
                        }
                      ?></small>
                    <?php endif; ?>
                    <?php if (!empty($co['eligibility_criteria'])): ?><br><small style="color:rgba(15,23,42,0.4)">Eligibility: <?= htmlspecialchars($co['eligibility_criteria']) ?></small><?php endif; ?>
                  </td>
                  <td data-label="Level"><span class="course-level-badge <?= $levelClass ?>"><?= htmlspecialchars($co['course_level'] ?? '—') ?></span></td>
                  <td data-label="Duration"><?= $co['duration_years'] ? htmlspecialchars((string)$co['duration_years']) . ' yrs' : '—' ?></td>
                  <td data-label="Seats"><?= htmlspecialchars((string)($co['seats_available'] ?? $co['seats'] ?? '—')) ?></td>
                  <td data-label="Fee"><strong style="color:#0B2447"><?= formatFee(isset($co['annual_fee']) ? (float)$co['annual_fee'] : null) ?></strong></td>
                  <td data-label="EMI"><?= !empty($co['emi_available']) ? '<span style="color:#0B2447;font-weight:700">✓ EMI</span>' : '<span style="color:rgba(15,23,42,0.4)">—</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── FEES ──────────────────────────────────────────────── -->
        <?php elseif ($tab === 'fees'): ?>
          <section class="college-section">
            <h2>Fee Structure</h2>
            <?php if (empty($courses)): ?>
            <div class="tab-empty-state"><i class="ph ph-currency-inr"></i><p>Fee details not available.</p></div>
            <?php else: ?>
            <div class="college-table-wrap">
              <table class="college-data-table fees-table">
                <thead><tr><th>Course</th><th>Annual Fee</th><th>Semester Fee</th><th>Total Fee</th><th>Application Fee</th></tr></thead>
                <tbody>
                <?php foreach ($courses as $co): ?>
                <tr>
                  <td data-label="Course"><strong><?= htmlspecialchars($co['course_name'] ?? '—') ?></strong></td>
                  <td data-label="Annual Fee"><strong style="color:#0B2447"><?= formatFee(isset($co['annual_fee']) ? (float)$co['annual_fee'] : null) ?></strong></td>
                  <td data-label="Sem Fee"><?= formatFee(isset($co['semester_fee']) ? (float)$co['semester_fee'] : null) ?></td>
                  <td data-label="Total Fee"><?= formatFee(isset($co['total_fee']) ? (float)$co['total_fee'] : null) ?></td>
                  <td data-label="App Fee"><?= formatFee(isset($co['application_fee']) ? (float)$co['application_fee'] : null) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── REVIEWS ───────────────────────────────────────────── -->
        <?php elseif ($tab === 'reviews'): ?>
          <section class="college-section">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
              <h2 style="margin:0">Student Reviews <span class="college-count">(<?= $reviewCount ?>)</span></h2>
              <?php if ($isLoggedIn): ?>
              <button type="button" onclick="openReviewModal()" style="display:inline-flex;align-items:center;gap:8px;padding:10px 24px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;border:none;border-radius:10px;font-size:.9rem;font-weight:700;cursor:pointer;transition:all .25s">
                <i class="ph ph-pencil-simple"></i> Write a Review
              </button>
              <?php else: ?>
              <button type="button" onclick="openLoginPrompt()" style="display:inline-flex;align-items:center;gap:8px;padding:10px 24px;background:#fff;color:#0B2447;border:2px solid #0B2447;border-radius:10px;font-size:.9rem;font-weight:700;cursor:pointer;transition:all .25s">
                <i class="ph ph-pencil-simple"></i> Write a Review
              </button>
              <?php endif; ?>
            </div>
            <?php if (array_filter(array_column($ratingItems, 'val'))): ?>
            <div class="college-rating-row" style="margin-bottom:24px">
              <?php foreach ($ratingItems as $ri): if ((float)$ri['val'] <= 0) continue; ?>
              <div class="college-rating-pill">
                <i class="ph <?= $ri['icon'] ?>"></i>
                <div><strong><?= number_format((float)$ri['val'], 1) ?></strong><span><?= htmlspecialchars($ri['label']) ?></span></div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (empty($reviews)): ?>
            <div class="tab-empty-state"><i class="ph ph-star"></i><p>No reviews yet. Be the first to review!</p></div>
            <?php else: ?>
            <div class="college-reviews-list">
              <?php foreach ($reviews as $rev): ?>
              <article class="college-review-card" style="<?= ($rev['moderation_status'] ?? '') === 'pending' ? 'opacity:.65;border:1px dashed rgba(251,191,36,.5)' : '' ?>">
                <div class="cr-head">
                  <strong><?= htmlspecialchars($rev['user_name'] ?? 'Student') ?></strong>
                  <span class="cr-rating"><i class="ph ph-star-fill"></i> <?= number_format((float)$rev['overall_rating'], 1) ?></span>
                  <?php if (($rev['moderation_status'] ?? '') === 'pending'): ?><span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;background:rgba(251,191,36,.12);color:#B45309;border-radius:6px;font-size:.72rem;font-weight:700"><i class="ph ph-clock"></i> Pending</span><?php endif; ?>
                  <?php if ($rev['batch_year']): ?><span class="cr-batch">Batch <?= htmlspecialchars((string)$rev['batch_year']) ?></span><?php endif; ?>
                </div>
                <?php if ($rev['review_title']): ?><h4><?= htmlspecialchars($rev['review_title']) ?></h4><?php endif; ?>
                <?php if ($rev['review_body']): ?><p><?= nl2br(htmlspecialchars($rev['review_body'])) ?></p><?php endif; ?>
                <?php if ($rev['pros']): ?><p class="cr-pros"><strong><i class="ph ph-thumbs-up"></i> Pros:</strong> <?= htmlspecialchars($rev['pros']) ?></p><?php endif; ?>
                <?php if ($rev['cons']): ?><p class="cr-cons"><strong><i class="ph ph-thumbs-down"></i> Cons:</strong> <?= htmlspecialchars($rev['cons']) ?></p><?php endif; ?>
                <div style="margin-top:10px; display:flex; align-items:center; gap:12px;">
                  <button onclick="reportReview('<?= $rev['id'] ?>', '<?= $college['id'] ?>')" style="background:none; border:none; color:var(--text-muted); font-size:.78rem; cursor:pointer; display:inline-flex; align-items:center; gap:4px; padding:4px 0;"><i class="ph ph-flag"></i> Report</button>
                </div>
              </article>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── ADMISSIONS ────────────────────────────────────────── -->
        <?php elseif ($tab === 'admissions'): ?>
          <section class="college-section">
            <h2>Admission Process</h2>
            <?php if (empty($college['admission_process'])): ?>
            <div class="tab-empty-state"><i class="ph ph-paper-plane-tilt"></i><p>Admission details coming soon.</p></div>
            <?php else: ?>
            <div class="college-prose"><?= nl2br(htmlspecialchars($college['admission_process'])) ?></div>
            <?php endif; ?>
            <div class="college-info-grid">
              <?php if ($college['accepted_exams']):
                $exams = json_decode($college['accepted_exams'], true);
                if (!is_array($exams)) $exams = array_filter(explode(',', $college['accepted_exams']));
              ?>
              <div style="grid-column:1/-1">
                <strong>Accepted Exams</strong>
                <div class="college-tag-row" style="margin-top:8px">
                  <?php foreach ($exams as $ex): ?><span class="college-tag"><?= htmlspecialchars(trim((string)$ex)) ?></span><?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>
              <?php if ($college['admission_start_date']): ?><div><strong>Application Start</strong><p><?= date('d M Y', strtotime($college['admission_start_date'])) ?></p></div><?php endif; ?>
              <?php if ($college['admission_end_date']): ?><div><strong>Application End</strong><p><?= date('d M Y', strtotime($college['admission_end_date'])) ?></p></div><?php endif; ?>
              <?php if ($college['application_mode']): ?><div><strong>Application Mode</strong><p><?= htmlspecialchars($college['application_mode']) ?></p></div><?php endif; ?>
              <?php if ($college['selection_criteria']): ?><div><strong>Selection Criteria</strong><p><?= htmlspecialchars($college['selection_criteria']) ?></p></div><?php endif; ?>
              <?php if ($college['management_quota_seats']): ?><div><strong>Management Quota</strong><p><?= (int)$college['management_quota_seats'] ?> seats</p></div><?php endif; ?>
              <?php if (!empty($college['merit_based'])): ?><div><strong>Merit Based</strong><p>Yes</p></div><?php endif; ?>
              <?php if (!empty($college['lateral_entry_available'])): ?><div><strong>Lateral Entry</strong><p>Available</p></div><?php endif; ?>
            </div>
          </section>

        <!-- ── PLACEMENTS ────────────────────────────────────────── -->
        <?php elseif ($tab === 'placements'): ?>
          <section class="college-section">
            <h2>Placement Statistics</h2>
            <?php if (empty($placements)): ?>
            <div class="tab-empty-state"><i class="ph ph-briefcase"></i><p>Placement data not available.</p></div>
            <?php else: ?>
            <?php
            $best = array_reduce($placements, function($carry,$pl){ return ($pl['avg_package_lpa'] > ($carry['avg_package_lpa'] ?? 0)) ? $pl : $carry; }, null);
            if ($best): ?>
            <div class="placement-highlight">
              <div class="ph-stat"><strong><?= formatLpa((float)($best['avg_package_lpa'] ?? 0)) ?></strong><span>Avg Package</span></div>
              <div class="ph-stat"><strong><?= formatLpa((float)($best['highest_package_lpa'] ?? 0)) ?></strong><span>Highest Package</span></div>
              <div class="ph-stat"><strong><?= $best['placement_percentage'] ? number_format((float)$best['placement_percentage'],1).'%' : '—' ?></strong><span>Placement Rate</span></div>
              <div class="ph-stat"><strong><?= htmlspecialchars((string)($best['students_placed'] ?? '—')) ?></strong><span>Students Placed</span></div>
            </div>
            <?php endif; ?>
            <div class="college-table-wrap">
              <table class="college-data-table placements-table">
                <thead><tr><th>Year</th><th>Avg Package</th><th>Highest</th><th>Median</th><th>Placed %</th><th>Students Placed</th></tr></thead>
                <tbody>
                <?php foreach ($placements as $pl): ?>
                <tr>
                  <td data-label="Year"><strong><?= htmlspecialchars((string)($pl['placement_year'] ?? '—')) ?></strong></td>
                  <td data-label="Avg Package"><?= formatLpa(isset($pl['avg_package_lpa']) ? (float)$pl['avg_package_lpa'] : null) ?></td>
                  <td data-label="Highest"><?= formatLpa(isset($pl['highest_package_lpa']) ? (float)$pl['highest_package_lpa'] : null) ?></td>
                  <td data-label="Median"><?= formatLpa(isset($pl['median_package_lpa']) ? (float)$pl['median_package_lpa'] : null) ?></td>
                  <td data-label="Placed %"><?= !empty($pl['placement_percentage']) ? number_format((float)$pl['placement_percentage'],1).'%' : '—' ?></td>
                  <td data-label="Students"><?= htmlspecialchars((string)($pl['students_placed'] ?? '—')) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php foreach ($placements as $pl): if (empty($pl['top_recruiters'])) continue;
              $rec = json_decode($pl['top_recruiters'], true);
              if (!is_array($rec)) $rec = array_filter(explode(',', $pl['top_recruiters']));
            ?>
            <div style="margin-top:20px">
              <h3>Top Recruiters (<?= htmlspecialchars((string)($pl['placement_year'] ?? '')) ?>)</h3>
              <div class="college-tag-row"><?php foreach ($rec as $r): ?><span class="college-tag"><?= htmlspecialchars((string)$r) ?></span><?php endforeach; ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </section>

        <!-- ── CUTOFFS ───────────────────────────────────────────── -->
        <?php elseif ($tab === 'cutoffs'): ?>
          <section class="college-section">
            <h2>Cut-Off Data</h2>
            <?php if (empty($cutoffs)): ?>
            <div class="tab-empty-state"><i class="ph ph-scissors"></i><p>Cutoff data not available for this college.</p></div>
            <?php else: ?>
            <div class="college-table-wrap">
              <table class="college-data-table cutoffs-table">
                <thead><tr><th>Exam</th><th>Course</th><th>Year</th><th>Category</th><th>Round</th><th>Opening Rank</th><th>Closing Rank</th></tr></thead>
                <tbody>
                <?php foreach ($cutoffs as $cu): ?>
                <tr>
                  <td data-label="Exam"><strong><?= htmlspecialchars($cu['exam_name'] ?? '—') ?></strong></td>
                  <td data-label="Course"><?= htmlspecialchars($cu['course_name'] ?? '—') ?></td>
                  <td data-label="Year"><?= htmlspecialchars((string)($cu['cutoff_year'] ?? '—')) ?></td>
                  <td data-label="Category"><span class="college-tag" style="font-size:.72rem"><?= htmlspecialchars($cu['category'] ?? '—') ?></span></td>
                  <td data-label="Round"><?= htmlspecialchars((string)($cu['round_number'] ?? '—')) ?></td>
                  <td data-label="Opening"><?= htmlspecialchars((string)($cu['opening_rank'] ?? '—')) ?></td>
                  <td data-label="Closing"><strong><?= htmlspecialchars((string)($cu['closing_rank'] ?? '—')) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── RANKINGS ──────────────────────────────────────────── -->
        <?php elseif ($tab === 'rankings'): ?>
          <section class="college-section">
            <h2>Rankings</h2>
            <?php if (!empty($college['ranking_nirf'])): ?>
            <p class="college-nirf-badge"><i class="ph ph-trophy"></i> NIRF Rank: <strong>#<?= (int)$college['ranking_nirf'] ?></strong></p>
            <?php endif; ?>
            <?php if (empty($rankings)): ?>
            <div class="tab-empty-state"><i class="ph ph-trophy"></i><p>Detailed ranking history not available.</p></div>
            <?php else: ?>
            <div class="college-table-wrap">
              <table class="college-data-table">
                <thead><tr><th>Ranking Body</th><th>Year</th><th>Category</th><th>Rank</th><th>Score</th></tr></thead>
                <tbody>
                <?php foreach ($rankings as $rk): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($rk['ranking_body'] ?? '—') ?></strong></td>
                  <td><?= htmlspecialchars((string)($rk['ranking_year'] ?? '—')) ?></td>
                  <td><?= htmlspecialchars($rk['category'] ?? '—') ?></td>
                  <td><span class="college-nirf-badge" style="display:inline;padding:4px 10px;font-size:.82rem">#<?= htmlspecialchars((string)($rk['rank_position'] ?? $rk['rank_band'] ?? '—')) ?></span></td>
                  <td><?= $rk['score'] ? number_format((float)$rk['score'], 2) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── GALLERY ───────────────────────────────────────────── -->
        <?php elseif ($tab === 'gallery'):
            $gImages = []; $gVideos = []; $gDocs = []; $gTour360 = null;
            foreach ($gallery as $m) {
                if (!empty($m['image_url'])) $gImages[] = $m;
                elseif (!empty($m['video_url'])) $gVideos[] = $m;
                elseif (!empty($m['document_url'])) $gDocs[] = $m;
                if (!empty($m['360_tour_url']) && empty($m['image_url']) && empty($m['video_url']) && empty($m['document_url'])) $gTour360 = $m;
            }
            $hasAny = count($gImages) || count($gVideos) || count($gDocs) || $gTour360;
        ?>
          <section class="college-section">
            <h2>Media & Gallery</h2>

            <?php if (!$hasAny): ?>
            <div class="tab-empty-state"><i class="ph ph-images"></i><p>No media content uploaded yet.</p></div>
            <?php else: ?>

            <!-- Filter Tabs -->
            <div class="gallery-filter-tabs" id="galleryTabs">
              <button class="g-tab active" data-filter="all">All</button>
              <?php if (count($gImages)): ?><button class="g-tab" data-filter="images">Images (<?= count($gImages) ?>)</button><?php endif; ?>
              <?php if (count($gVideos)): ?><button class="g-tab" data-filter="videos">Videos (<?= count($gVideos) ?>)</button><?php endif; ?>
              <?php if (count($gDocs)): ?><button class="g-tab" data-filter="documents">Documents (<?= count($gDocs) ?>)</button><?php endif; ?>
              <?php if ($gTour360): ?><button class="g-tab" data-filter="tour360">360° Tour</button><?php endif; ?>
            </div>

            <!-- IMAGES -->
            <?php if (count($gImages)):
                $imgSubTypes = ['campus'=>'Campus','lab'=>'Lab','hostel'=>'Hostel','event'=>'Event','classroom'=>'Classroom'];
                $grouped = [];
                foreach ($gImages as $m) { $k = $m['image_type'] ?: 'other'; $grouped[$k][] = $m; }
            ?>
            <div class="gallery-section" data-gtype="images">
              <h3 class="gallery-section-title"><i class="ph ph-image"></i> Images</h3>
              <?php foreach ($grouped as $subType => $items): ?>
              <h4 class="gallery-sub-title"><?= $imgSubTypes[$subType] ?? ucfirst($subType) ?></h4>
              <div class="college-gallery-grid">
                <?php foreach ($items as $m):
                    $url = $m['image_url'] ?: ($m['cover_image_url'] ?: '');
                    if (!$url) continue;
                ?>
                <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="college-gallery-item" title="<?= htmlspecialchars($m['caption'] ?: '') ?>">
                  <img src="<?= cImg($url) ?>" alt="<?= htmlspecialchars($m['caption'] ?? $college['name']) ?>" loading="lazy">
                  <?php if ($m['caption']): ?><span><?= htmlspecialchars($m['caption']) ?></span><?php endif; ?>
                </a>
                <?php endforeach; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- VIDEOS -->
            <?php if (count($gVideos)):
                $vidSubTypes = ['tour'=>'Campus Tour','placement'=>'Placement','event'=>'Event','alumni_talk'=>'Alumni Talk'];
            ?>
            <div class="gallery-section" data-gtype="videos">
              <h3 class="gallery-section-title"><i class="ph ph-video-camera"></i> Videos</h3>
              <?php foreach ($gVideos as $m):
                $vUrl = $m['video_url'];
                $vSub = $m['video_type'] ?: 'other';
                $embedUrl = $vUrl;
                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]+)/', $vUrl, $yt)) {
                    $embedUrl = 'https://www.youtube.com/embed/' . $yt[1];
                } elseif (preg_match('/vimeo\.com\/(\d+)/', $vUrl, $vm)) {
                    $embedUrl = 'https://player.vimeo.com/video/' . $vm[1];
                }
                $isEmbed = (strpos($embedUrl, 'youtube.com/embed') !== false || strpos($embedUrl, 'player.vimeo.com') !== false || strpos($embedUrl, 'matterport') !== false);
              ?>
              <div class="gallery-video-card">
                <div class="gallery-video-badge"><?= $vidSubTypes[$vSub] ?? ucfirst(str_replace('_',' ',$vSub)) ?></div>
                <?php if ($isEmbed): ?>
                <div class="gallery-video-wrap">
                  <iframe src="<?= htmlspecialchars($embedUrl) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                </div>
                <?php else: ?>
                <a href="<?= htmlspecialchars($vUrl) ?>" target="_blank" class="gallery-video-link">
                  <div class="gallery-video-thumb"><i class="ph ph-play-circle" style="font-size:3rem;color:#fff"></i></div>
                  <span>Watch Video <i class="ph ph-arrow-square-out"></i></span>
                </a>
                <?php endif; ?>
                <?php if ($m['caption']): ?><p class="gallery-video-caption"><?= htmlspecialchars($m['caption']) ?></p><?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- DOCUMENTS -->
            <?php if (count($gDocs)):
                $docSubTypes = ['brochure'=>'Brochure','prospectus'=>'Prospectus','annual_report'=>'Annual Report','ranking_cert'=>'Ranking Certificate'];
                $docIcons = ['brochure'=>'ph-book-open','prospectus'=>'ph-notebook','annual_report'=>'ph-file-text','ranking_cert'=>'ph-medal'];
            ?>
            <div class="gallery-section" data-gtype="documents">
              <h3 class="gallery-section-title"><i class="ph ph-files"></i> Documents</h3>
              <div class="gallery-doc-list">
              <?php foreach ($gDocs as $m):
                $dUrl = $m['document_url'];
                $dType = $m['document_type'] ?: 'document';
                $isPdf = (stripos($dUrl, '.pdf') !== false);
                $displayUrl = cImg($dUrl);
              ?>
              <a href="<?= htmlspecialchars($displayUrl) ?>" target="_blank" class="gallery-doc-card">
                <div class="gallery-doc-icon">
                  <i class="ph <?= $isPdf ? 'ph-file-pdf' : ($docIcons[$dType] ?? 'ph-file') ?>"></i>
                </div>
                <div class="gallery-doc-info">
                  <span class="gallery-doc-name"><?= $docSubTypes[$dType] ?? ucfirst(str_replace('_',' ',$dType)) ?></span>
                  <?php if ($m['caption']): ?><span class="gallery-doc-cap"><?= htmlspecialchars($m['caption']) ?></span><?php endif; ?>
                </div>
                <span class="gallery-doc-dl"><i class="ph ph-download-simple"></i> Download</span>
              </a>
              <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- 360 TOUR -->
            <?php if ($gTour360): ?>
            <div class="gallery-section" data-gtype="tour360">
              <h3 class="gallery-section-title"><i class="ph ph-compass"></i> Virtual Tour (360°)</h3>
              <?php $tourUrl = $gTour360['360_tour_url']; ?>
              <div class="gallery-360-wrap">
                <iframe src="<?= htmlspecialchars($tourUrl) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
              </div>
              <p class="gallery-360-hint">Drag to look around. Scroll to zoom. Best viewed in fullscreen.</p>
            </div>
            <?php endif; ?>

            <?php endif; ?>
          </section>

          <script>
          document.addEventListener('DOMContentLoaded', function() {
              var tabs = document.getElementById('galleryTabs');
              if (!tabs) return;
              tabs.addEventListener('click', function(e) {
                  var btn = e.target.closest('.g-tab');
                  if (!btn) return;
                  tabs.querySelectorAll('.g-tab').forEach(function(b){ b.classList.remove('active'); });
                  btn.classList.add('active');
                  var f = btn.getAttribute('data-filter');
                  document.querySelectorAll('.gallery-section').forEach(function(sec) {
                      if (f === 'all' || sec.getAttribute('data-gtype') === f) sec.style.display = '';
                      else sec.style.display = 'none';
                  });
              });
          });
          </script>

        <!-- ── INFRASTRUCTURE ────────────────────────────────────── -->
        <?php elseif ($tab === 'infrastructure'): ?>
          <section class="college-section">
            <h2>Infrastructure & Facilities</h2>
            <div class="college-facility-grid">
              <?php
              $facilities = [
                ['library','Library','ph-books'],['labs','Laboratories','ph-flask'],
                ['sports_facilities','Sports','ph-football'],['auditorium','Auditorium','ph-presentation'],
                ['cafeteria','Cafeteria','ph-coffee'],['wifi','Wi-Fi Campus','ph-wifi-high'],
                ['medical_facility','Medical Center','ph-first-aid'],['transport','Transport','ph-bus'],
                ['ev_charging','EV Charging','ph-lightning'],['solar_power','Solar Power','ph-sun'],
              ];
              foreach ($facilities as $f):
                if (empty($college[$f[0]])) continue;
              ?>
              <div class="college-facility-item"><i class="ph <?= $f[2] ?>"></i><span><?= $f[1] ?></span></div>
              <?php endforeach; ?>
            </div>
            <?php if ($college['total_students'] || $college['total_faculty'] || $college['campus_area_acres']): ?>
            <h3 style="margin-top:28px">Campus Statistics</h3>
            <div class="college-info-grid">
              <?php if ($college['total_students']): ?><div><strong>Total Students</strong><p><?= number_format((int)$college['total_students']) ?></p></div><?php endif; ?>
              <?php if ($college['total_faculty']): ?><div><strong>Total Faculty</strong><p><?= (int)$college['total_faculty'] ?></p></div><?php endif; ?>
              <?php if ($college['campus_area_acres']): ?><div><strong>Campus Area</strong><p><?= (float)$college['campus_area_acres'] ?> Acres</p></div><?php endif; ?>
            </div>
            <?php
            $sportsList = jsonLines($college['sports_facilities'] ?? '');
            $labsList   = jsonLines($college['labs'] ?? '');
            if (!empty($sportsList)): ?>
            <div class="infra-section-card">
              <div class="infra-section-header">
                <div class="infra-section-icon"><i class="ph ph-football"></i></div>
                <h3>Sports Facilities</h3>
              </div>
              <div class="infra-chip-grid">
                <?php
                $sportIcons = ['cricket'=>'ph-baseball','football'=>'ph-football','basketball'=>'ph-basketball','tennis'=>'ph-paddle','swimming'=>'ph-waves','gym'=>'ph-barbell','badminton'=>'ph-paddle','volleyball'=>'ph-volleyball','squash'=>'ph-paddle','track'=>'ph-sneaker-move','athletic'=>'ph-sneaker-move','pool'=>'ph-waves','court'=>'ph-paddle'];
                foreach ($sportsList as $sp):
                  $spLower = strtolower((string)$sp);
                  $spIcon = 'ph-star';
                  foreach ($sportIcons as $k=>$v) { if (strpos($spLower, $k) !== false) { $spIcon = $v; break; } }
                ?>
                <div class="infra-chip">
                  <i class="ph <?= $spIcon ?>"></i>
                  <span><?= htmlspecialchars((string)$sp) ?></span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($labsList)): ?>
            <div class="infra-section-card">
              <div class="infra-section-header">
                <div class="infra-section-icon"><i class="ph ph-flask"></i></div>
                <h3>Laboratories</h3>
              </div>
              <div class="infra-chip-grid">
                <?php foreach ($labsList as $lb): ?>
                <div class="infra-chip">
                  <i class="ph ph-flask"></i>
                  <span><?= htmlspecialchars((string)$lb) ?></span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($college['hostel_available']): ?>
            <h3 style="margin-top:28px">Hostel Details</h3>
            <div class="college-info-grid">
              <?php if ($college['hostel_type']): ?><div><strong>Hostel Type</strong><p><?= htmlspecialchars(ucfirst($college['hostel_type'])) ?></p></div><?php endif; ?>
              <?php if ($college['hostel_capacity']): ?><div><strong>Capacity</strong><p><?= number_format((int)$college['hostel_capacity']) ?></p></div><?php endif; ?>
              <?php if ($college['hostel_fee_annual']): ?><div><strong>Annual Fee</strong><p><?= formatFee((float)$college['hostel_fee_annual']) ?></p></div><?php endif; ?>
              <?php if ($college['mess_available']): ?><div><strong>Mess</strong><p><?= htmlspecialchars(ucfirst($college['mess_type'] ?? 'Available')) ?></p></div><?php endif; ?>
              <?php if (isset($college['ac_available'])): ?><div><strong>AC Rooms</strong><p><?= $college['ac_available'] ? '✓ Available' : '✗ Not Available' ?></p></div><?php endif; ?>
              <?php if (isset($college['laundry_available'])): ?><div><strong>Laundry</strong><p><?= $college['laundry_available'] ? '✓ Available' : '✗ Not Available' ?></p></div><?php endif; ?>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── FACULTY ───────────────────────────────────────────── -->
        <?php elseif ($tab === 'faculty'): ?>
          <section class="college-section">
            <h2>Faculty <span class="college-count">(<?= count($faculty) ?>)</span></h2>
            <?php if (empty($faculty)): ?>
            <div class="tab-empty-state"><i class="ph ph-chalkboard-teacher"></i><p>Faculty profiles not listed yet.</p></div>
            <?php else: ?>
            <div class="college-faculty-grid">
              <?php foreach ($faculty as $fc): ?>
              <div class="college-faculty-card" onclick="openFacultyModal(this.dataset)" style="cursor:pointer;"
                data-faculty_name="<?= htmlspecialchars($fc['faculty_name']) ?>"
                data-designation="<?= htmlspecialchars($fc['designation'] ?? '') ?>"
                data-department="<?= htmlspecialchars($fc['department'] ?? '') ?>"
                data-qualification="<?= htmlspecialchars($fc['qualification'] ?? '') ?>"
                data-specialization="<?= htmlspecialchars($fc['specialization'] ?? '') ?>"
                data-phd_from="<?= htmlspecialchars($fc['phd_from'] ?? '') ?>"
                data-experience_years="<?= htmlspecialchars((string)($fc['experience_years'] ?? '')) ?>"
                data-research_papers="<?= htmlspecialchars((string)($fc['research_papers'] ?? '')) ?>"
                data-photo_url="<?= htmlspecialchars(cImg($fc['photo_url'] ?? '')) ?>"
                data-linkedin_url="<?= htmlspecialchars($fc['linkedin_url'] ?? '') ?>">
                <?php if ($fc['photo_url']): ?><img src="<?= cImg($fc['photo_url']) ?>" alt="<?= htmlspecialchars($fc['faculty_name']) ?>">
                <?php else: ?><div class="cf-avatar"><i class="ph ph-user"></i></div><?php endif; ?>
                <div>
                  <strong><?= htmlspecialchars($fc['faculty_name']) ?></strong>
                  <span><?= htmlspecialchars($fc['designation'] ?? '') ?></span>
                  <?php if ($fc['department']): ?><small><?= htmlspecialchars($fc['department']) ?></small><?php endif; ?>
                  <?php if ($fc['qualification']): ?><small><?= htmlspecialchars($fc['qualification']) ?></small><?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

<!-- Faculty Detail Modal -->
<div id="facultyModal" style="display:none;position:fixed;inset:0;z-index:10002;background:rgba(15,23,42,0.55);backdrop-filter:blur(4px);">
  <div id="facultyModalBox" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:16px;max-width:440px;width:calc(100% - 32px);max-height:88vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,0.25);box-sizing:border-box;">
    <div id="facultyModalBody"></div>
  </div>
</div>
<script>
function openFacultyModal(ds) {
  var photo = ds.photo_url
    ? '<img src="' + ds.photo_url + '" style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:4px solid #fff;box-shadow:0 4px 20px rgba(0,0,0,.15);">'
    : '<div style="width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,#e0e7ff,#c7d2fe);display:flex;align-items:center;justify-content:center;color:#4f46e5;font-size:2rem;border:4px solid #fff;box-shadow:0 4px 20px rgba(0,0,0,.1);"><i class="ph ph-user"></i></div>';

  var html = ''
    // Cover banner
    + '<div style="background:linear-gradient(135deg,#0B2447 0%,#19376D 60%,#1e40af 100%);height:100px;border-radius:16px 16px 0 0;position:relative;">'
    + '<button onclick="closeFacultyModal()" style="position:absolute;top:12px;right:12px;background:rgba(255,255,255,0.15);border:none;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:.85rem;backdrop-filter:blur(4px);" onmouseover="this.style.background=\'rgba(255,255,255,0.25)\'" onmouseout="this.style.background=\'rgba(255,255,255,0.15)\'"><i class="ph ph-x"></i></button>'
    + '<div style="position:absolute;bottom:-40px;left:50%;transform:translateX(-50%);">' + photo + '</div>'
    + '</div>'

    // Name + designation
    + '<div style="text-align:center;padding:52px 24px 16px;">'
    + '<h3 style="margin:0;font-size:18px;font-weight:800;color:#0f172a;">' + (ds.faculty_name || '') + '</h3>'
    + (ds.designation ? '<div style="font-size:13px;color:#19376D;font-weight:600;margin-top:4px;">' + ds.designation + '</div>' : '')
    + (ds.department ? '<div style="font-size:12px;color:#64748b;margin-top:2px;"><i class="ph ph-buildings" style="font-size:.75rem;"></i> ' + ds.department + '</div>' : '')
    + '</div>'

    // Stats row
    + '<div style="display:flex;justify-content:center;gap:0;margin:0 24px 16px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">';

  var stats = [];
  if (ds.experience_years) stats.push([ds.experience_years, 'Exp (Yrs)', '#0B2447']);
  if (ds.research_papers && ds.research_papers !== '0') stats.push([ds.research_papers, 'Papers', '#19376D']);
  if (ds.qualification) stats.push([ds.qualification.split(',')[0].trim(), 'Degree', '#0f172a']);

  stats.forEach(function(s, i) {
    html += '<div style="flex:1;text-align:center;padding:12px 8px;' + (i > 0 ? 'border-left:1px solid #e2e8f0;' : '') + '">'
      + '<div style="font-size:15px;font-weight:800;color:' + s[2] + ';">' + s[0] + '</div>'
      + '<div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-top:2px;">' + s[1] + '</div>'
      + '</div>';
  });

  html += '</div>';

  // Details list
  var details = [
    ['ph-medal', 'Specialization', ds.specialization],
    ['ph-read-cv-logo', 'PhD From', ds.phd_from],
  ];

  var hasDetails = details.some(function(d) { return d[2]; });
  if (hasDetails) {
    html += '<div style="margin:0 24px 16px;padding:16px;background:#f8fafc;border-radius:12px;border:1px solid #f1f5f9;">';
    details.forEach(function(d) {
      if (d[2]) {
        html += '<div style="display:flex;align-items:center;gap:10px;' + (d !== details[details.length - 1] ? 'padding-bottom:10px;margin-bottom:10px;border-bottom:1px solid #e2e8f0;' : '') + '">'
          + '<div style="width:32px;height:32px;border-radius:8px;background:#fff;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
          + '<i class="ph ' + d[0] + '" style="font-size:.9rem;color:#19376D;"></i></div>'
          + '<div><div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;font-weight:600;">' + d[1] + '</div>'
          + '<div style="font-size:13px;color:#0f172a;font-weight:600;margin-top:1px;">' + d[2] + '</div></div></div>';
      }
    });
    html += '</div>';
  }

  // LinkedIn button
  if (ds.linkedin_url) {
    html += '<div style="margin:0 24px 24px;">'
      + '<a href="' + ds.linkedin_url + '" target="_blank" style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:10px 16px;background:#0077b5;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;transition:all .2s;" onmouseover="this.style.background=\'#006097\'" onmouseout="this.style.background=\'#0077b5\'">'
      + '<i class="ph ph-linkedin-logo" style="font-size:1rem;"></i> View LinkedIn Profile</a></div>';
  }

  document.getElementById('facultyModalBody').innerHTML = html;
  document.getElementById('facultyModal').style.display = 'block';
  document.body.style.overflow = 'hidden';
}
function closeFacultyModal() {
  document.getElementById('facultyModal').style.display = 'none';
  document.body.style.overflow = '';
}
document.getElementById('facultyModal')?.addEventListener('click', function(e) {
  if (e.target === this) closeFacultyModal();
});
</script>

        <!-- ── COMPARE ───────────────────────────────────────────── -->
        <?php elseif ($tab === 'compare'): ?>
          <section class="college-section">
            <h2>Compare <?= htmlspecialchars($college['name']) ?></h2>
            <p style="color:rgba(15,23,42,0.45);margin-bottom:20px">Compare on the basis of fees, placements, rankings, reviews and more.</p>
            <div class="compare-grid">
              <?php $mf=null; foreach($courses as $co){if($co['annual_fee']>0&&($mf===null||$co['annual_fee']<$mf))$mf=(float)$co['annual_fee'];} ?>
              <div class="compare-card"><strong><?= formatFee($mf) ?></strong><span>Min Annual Fee</span></div>
              <?php $ap=null; foreach($placements as $pl){if($pl['avg_package_lpa']>0&&($ap===null||$pl['avg_package_lpa']>$ap))$ap=(float)$pl['avg_package_lpa'];} ?>
              <div class="compare-card"><strong><?= formatLpa($ap) ?></strong><span>Avg Package</span></div>
              <div class="compare-card"><strong><?= $college['ranking_nirf'] ? '#'.(int)$college['ranking_nirf'] : '—' ?></strong><span>NIRF Rank</span></div>
              <div class="compare-card"><strong><?= $overallRating > 0 ? number_format((float)$overallRating,1).'/5 ★' : '—' ?></strong><span>Rating</span></div>
              <div class="compare-card"><strong><?= count($courses) ?></strong><span>Courses</span></div>
              <div class="compare-card"><strong><?= $college['naac_grade'] ? htmlspecialchars($college['naac_grade']) : '—' ?></strong><span>NAAC Grade</span></div>
            </div>
            <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/colleges.php" class="college-btn-primary" style="display:inline-flex;margin-top:24px"><i class="ph ph-scales"></i> Browse Colleges to Compare</a>
          </section>

        <!-- ── SEAT MATRIX ─────────────────────────────────────── -->
        <?php elseif ($tab === 'seat_matrix'): ?>
          <section class="college-section">
            <h2>Seat Matrix <?= $seatMatrix ? '<span class="college-count">(' . htmlspecialchars((string)($seatMatrix[0]['year'] ?? date('Y'))) . ')</span>' : '' ?></h2>
            <?php if (empty($seatMatrix)): ?>
            <div class="tab-empty-state"><i class="ph ph-table"></i><p>No seat matrix data available for this college yet.</p></div>
            <?php else: ?>
            <?php
              $coursesSeats = [];
              foreach($seatMatrix as $row) {
                $cn = $row['course_name'];
                if (!isset($coursesSeats[$cn])) $coursesSeats[$cn] = ['year'=>$row['year'],'source'=>$row['source'],'rows'=>[]];
                $coursesSeats[$cn]['rows'][] = $row;
              }
            ?>
            <?php foreach($coursesSeats as $courseName => $data): ?>
            <div style="background:#fff;border:1px solid rgba(15,23,42,0.06);border-radius:16px;overflow:hidden;margin-bottom:24px">
              <div style="padding:20px 24px;border-bottom:1px solid rgba(15,23,42,0.06);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
                <h3 style="margin:0;font-size:1.1rem;font-weight:800;color:#0B2447"><i class="ph ph-graduation-cap" style="margin-right:6px"></i><?= htmlspecialchars($courseName) ?></h3>
                <span style="font-size:.78rem;color:rgba(15,23,42,0.4);background:rgba(11,36,71,0.04);padding:4px 12px;border-radius:20px"><?= htmlspecialchars((string)$data['source']) ?></span>
              </div>
              <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.9rem">
                  <thead>
                    <tr style="background:#f8fafc">
                      <th style="padding:12px 20px;text-align:left;font-weight:700;color:rgba(15,23,42,0.5);font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">Category</th>
                      <th style="padding:12px 20px;text-align:center;font-weight:700;color:rgba(15,23,42,0.5);font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">Total Seats</th>
                      <th style="padding:12px 20px;text-align:center;font-weight:700;color:rgba(15,23,42,0.5);font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">Filled</th>
                      <th style="padding:12px 20px;text-align:center;font-weight:700;color:rgba(15,23,42,0.5);font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">Vacant</th>
                      <th style="padding:12px 20px;text-align:left;font-weight:700;color:rgba(15,23,42,0.5);font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">Occupancy</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $totalSeats = 0; $totalFilled = 0;
                      foreach($data['rows'] as $row):
                        $vacant = max(0, (int)$row['total_seats'] - (int)$row['filled_seats']);
                        $pct = (int)$row['total_seats'] > 0 ? round(((int)$row['filled_seats'] / (int)$row['total_seats']) * 100) : 0;
                        $totalSeats += (int)$row['total_seats'];
                        $totalFilled += (int)$row['filled_seats'];
                        $catColors = ['General'=>'#0B2447','OBC'=>'#19376D','SC'=>'#7c3aed','ST'=>'#dc2626','EWS'=>'#059669','PwD'=>'#d97706','NRI'=>'#2563eb','Mgmt'=>'#64748b'];
                        $color = $catColors[$row['category']] ?? '#64748b';
                    ?>
                    <tr style="border-bottom:1px solid rgba(15,23,42,0.04)">
                      <td style="padding:12px 20px;font-weight:600;color:#0f172a">
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $color ?>;margin-right:8px"></span><?= htmlspecialchars($row['category']) ?>
                      </td>
                      <td style="padding:12px 20px;text-align:center;font-weight:700;color:#0f172a"><?= (int)$row['total_seats'] ?></td>
                      <td style="padding:12px 20px;text-align:center;color:rgba(15,23,42,0.6)"><?= (int)$row['filled_seats'] ?></td>
                      <td style="padding:12px 20px;text-align:center;color:<?= $vacant > 0 ? '#059669' : '#DC2626' ?>;font-weight:600"><?= $vacant ?></td>
                      <td style="padding:12px 20px">
                        <div style="display:flex;align-items:center;gap:10px">
                          <div style="flex:1;height:8px;background:rgba(15,23,42,0.06);border-radius:8px;overflow:hidden;min-width:80px">
                            <div style="height:100%;width:<?= $pct ?>%;background:<?= $pct >= 95 ? '#DC2626' : ($pct >= 80 ? '#d97706' : '#059669') ?>;border-radius:8px;transition:width .5s"></div>
                          </div>
                          <span style="font-size:.82rem;font-weight:700;color:<?= $pct >= 95 ? '#DC2626' : ($pct >= 80 ? '#d97706' : '#059669') ?>;min-width:36px"><?= $pct ?>%</span>
                        </div>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php
                      $totalVacant = $totalSeats - $totalFilled;
                      $totalPct = $totalSeats > 0 ? round(($totalFilled / $totalSeats) * 100) : 0;
                    ?>
                    <tr style="background:#f8fafc;font-weight:700">
                      <td style="padding:14px 20px;color:#0B2447"><i class="ph ph-calculator" style="margin-right:6px"></i>Total</td>
                      <td style="padding:14px 20px;text-align:center;color:#0B2447;font-size:1rem"><?= $totalSeats ?></td>
                      <td style="padding:14px 20px;text-align:center;color:#0B2447"><?= $totalFilled ?></td>
                      <td style="padding:14px 20px;text-align:center;color:#059669"><?= $totalVacant ?></td>
                      <td style="padding:14px 20px">
                        <div style="display:flex;align-items:center;gap:10px">
                          <div style="flex:1;height:8px;background:rgba(15,23,42,0.06);border-radius:8px;overflow:hidden;min-width:80px">
                            <div style="height:100%;width:<?= $totalPct ?>%;background:linear-gradient(90deg,#0B2447,#19376D);border-radius:8px"></div>
                          </div>
                          <span style="font-size:.82rem;font-weight:700;color:#0B2447;min-width:36px"><?= $totalPct ?>%</span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </section>

        <!-- ── Q&A ───────────────────────────────────────────────── -->
        <?php elseif ($tab === 'qna'): ?>
          <section class="college-section">
            <h2>Student Q&A <span class="college-count">(<?= $qnaCount ?>)</span></h2>
            <?php if (empty($faqs) && empty($qnaList)): ?>
            <div class="tab-empty-state"><i class="ph ph-chat-circle"></i><p>No questions yet. Ask the first question!</p></div>
            <?php else: ?>
            <div class="college-faq-list">
              <?php foreach ($faqs as $fq): ?>
              <details class="college-faq-item" open>
                <summary><?= htmlspecialchars($fq['question_text']) ?></summary>
                <p><?= nl2br(htmlspecialchars($fq['answer_text'] ?? '')) ?></p>
              </details>
              <?php endforeach; ?>
              <?php foreach ($qnaList as $qn): ?>
              <details class="college-faq-item">
                <summary><?= htmlspecialchars($qn['question_text']) ?></summary>
                <p><?= nl2br(htmlspecialchars($qn['answer_text'] ?? 'Awaiting answer from expert.')) ?></p>
              </details>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── NEWS ─────────────────────────────────────────────── -->
        <?php elseif ($tab === 'news'): ?>
          <section class="college-section">
            <h2>News & Updates</h2>
            <?php if (empty($updates)): ?>
            <div class="tab-empty-state"><i class="ph ph-newspaper"></i><p>No updates for this college yet.</p></div>
            <?php else: ?>
            <div class="college-news-list">
              <?php foreach ($updates as $up): ?>
              <article class="college-news-item">
                <?php if (!empty($up['image_url']) || !empty($up['article_slug'])): ?>
                <div class="cn-thumb">
                  <img src="<?= cImg($up['image_url'] ?? $up['featured_image_url'] ?? '') ?>" alt="<?= htmlspecialchars($up['title']) ?>">
                </div>
                <?php endif; ?>
                <div class="cn-body">
                  <div class="cn-meta">
                    <span class="news-type-badge"><?= htmlspecialchars(ucwords(str_replace('_',' ',$up['update_type']??'news'))) ?></span>
                    <?php if ($up['event_date']): ?><span><i class="ph ph-calendar"></i> <?= date('d M Y', strtotime($up['event_date'])) ?></span><?php endif; ?>
                  </div>
                  <h4>
                    <?php if (!empty($up['article_slug'])): ?>
                      <a href="<?= BASE_URL ?>/news_details.php?slug=<?= urlencode($up['article_slug']) ?>"><?= htmlspecialchars($up['title']) ?></a>
                    <?php elseif (!empty($up['action_url'])): ?>
                      <a href="<?= htmlspecialchars($up['action_url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($up['title']) ?></a>
                    <?php elseif (!empty($up['slug'])): ?>
                      <a href="<?= BASE_URL ?>/news/<?= urlencode($up['slug']) ?>"><?= htmlspecialchars($up['title']) ?></a>
                    <?php elseif (!empty($up['id'])): ?>
                      <a href="<?= BASE_URL ?>/news/<?= urlencode($up['id']) ?>"><?= htmlspecialchars($up['title']) ?></a>
                    <?php else: ?>
                      <?= htmlspecialchars($up['title']) ?>
                    <?php endif; ?>
                  </h4>
                  <?php if ($up['description']): ?><p><?= nl2br(htmlspecialchars(mb_strimwidth($up['description'], 0, 180, '...'))) ?></p><?php endif; ?>
                  <?php if (!empty($up['article_slug'])): ?>
                    <a href="<?= BASE_URL ?>/news_details.php?slug=<?= urlencode($up['article_slug']) ?>">Read more <i class="ph ph-arrow-right"></i></a>
                  <?php elseif (!empty($up['action_url'])): ?>
                    <a href="<?= htmlspecialchars($up['action_url']) ?>" target="_blank" rel="noopener">Read more <i class="ph ph-arrow-right"></i></a>
                  <?php elseif (!empty($up['slug'])): ?>
                    <a href="<?= BASE_URL ?>/news/<?= urlencode($up['slug']) ?>">Read more <i class="ph ph-arrow-right"></i></a>
                  <?php elseif (!empty($up['id'])): ?>
                    <a href="<?= BASE_URL ?>/news/<?= urlencode($up['id']) ?>">Read more <i class="ph ph-arrow-right"></i></a>
                  <?php endif; ?>
                </div>
              </article>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <?php endif; ?>

      </div><!-- /.college-tab-content -->
    </main>

    <!-- ── Sidebar ───────────────────────────────────────────────── -->
    <aside class="shiksha-sidebar">
      <!-- Notification widget -->
      <div class="shiksha-widget college-notify-widget" id="apply">
        <h4 class="shiksha-widget-title"><i class="ph ph-bell-ringing"></i> <?= htmlspecialchars($college['name']) ?> Alerts</h4>
        <?php if (!empty($updates)): ?>
        <ul class="college-notify-list">
          <?php foreach (array_slice($updates, 0, 4) as $up): ?>
          <li><a href="<?= !empty($up['article_slug']) ? BASE_URL . '/news_details.php?slug='.urlencode($up['article_slug']) : (!empty($up['slug']) ? BASE_URL . '/news/'.urlencode($up['slug']) : (!empty($up['id']) ? BASE_URL . '/news/'.urlencode($up['id']) : collegeUrl($slug, 'news'))) ?>"><?= htmlspecialchars($up['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p>Get admission alerts, exam dates and cutoff updates directly in your inbox.</p>
        <?php endif; ?>
        <?php if ($userAlreadyApplied): ?>
        <div class="college-applied-badge">
          <i class="ph-fill ph-check-circle"></i>
          <span>Already Applied</span>
          <span class="college-applied-appno"><?= htmlspecialchars($userAlreadyApplied['application_number']) ?></span>
        </div>
        <?php elseif ($isLoggedIn): ?>
        <button type="button" class="college-btn-primary college-widget-btn" onclick="openApplyModal()">
          <i class="ph ph-paper-plane-tilt"></i> Apply Now
        </button>
        <?php else: ?>
        <button type="button" class="college-btn-primary college-widget-btn" onclick="openLoginPrompt()">
          <i class="ph ph-paper-plane-tilt"></i> Apply Now
        </button>
        <?php endif; ?>
      </div>

      <!-- Rating widget -->
      <?php if ($overallRating > 0): ?>
      <div class="shiksha-widget">
        <h4 class="shiksha-widget-title">⭐ Overall Rating</h4>
        <div style="text-align:center;padding:10px 0">
          <div style="font-size:2.5rem;font-weight:800;color:#19376D;font-family:'Plus Jakarta Sans',sans-serif"><?= number_format((float)$overallRating, 1) ?>/5</div>
          <div style="color:#19376D;font-size:1.2rem;margin:4px 0">★★★★<?= $overallRating >= 4.5 ? '★' : '☆' ?></div>
          <div style="font-size:.8rem;color:rgba(15,23,42,0.4)">Based on <?= $reviewCount ?> reviews</div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Popular courses -->
      <?php if (!empty($courses)): ?>
      <div class="shiksha-widget">
        <h4 class="shiksha-widget-title"><i class="ph ph-book-bookmark"></i> Popular Courses</h4>
        <ul class="shiksha-widget-list">
          <?php foreach (array_slice($courses, 0, 6) as $co): ?>
          <li><a href="<?= collegeUrl($slug, 'courses') ?>"><?= htmlspecialchars($co['course_name'] ?? '') ?><span><?= formatFee(isset($co['annual_fee']) ? (float)$co['annual_fee'] : null) ?></span></a></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($isLoggedIn): ?>
        <button type="button" onclick="sendCourseList()" id="courseListBtnSidebar" style="width:100%;margin-top:12px;padding:10px;background:#fff;color:#0B2447;border:2px solid #0B2447;border-radius:10px;font-size:.85rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;transition:all .25s">
          <i class="ph ph-files"></i> Course List
        </button>
        <?php else: ?>
        <button type="button" onclick="openLoginPrompt()" style="width:100%;margin-top:12px;padding:10px;background:#fff;color:#0B2447;border:2px solid #0B2447;border-radius:10px;font-size:.85rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;transition:all .25s">
          <i class="ph ph-files"></i> Course List
        </button>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Quick links -->
      <div class="shiksha-widget">
        <h4 class="shiksha-widget-title"><i class="ph ph-link"></i> Quick Links</h4>
        <ul class="shiksha-widget-list">
          <li><a href="<?= collegeUrl($slug, 'fees') ?>">Fee Structure</a></li>
          <li><a href="<?= collegeUrl($slug, 'placements') ?>">Placements</a></li>
          <li><a href="<?= collegeUrl($slug, 'cutoffs') ?>">Cut-Offs</a></li>
          <li><a href="<?= collegeUrl($slug, 'rankings') ?>">Rankings</a></li>
          <li><a href="<?= collegeUrl($slug, 'gallery') ?>">Gallery</a></li>
          <li><a href="<?= collegeUrl($slug, 'reviews') ?>">Student Reviews</a></li>
        </ul>
      </div>
    </aside>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/js/main.js"></script>
<script>
// Smooth tab scroll on mobile
document.querySelectorAll('.college-detail-tabs a.active').forEach(el => {
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
});

// Tab scroll arrows
function scrollTabs(dir) {
  const tabs = document.getElementById('collegeTabs');
  if (!tabs) return;
  const scrollAmt = 200;
  tabs.scrollBy({ left: dir * scrollAmt, behavior: 'smooth' });
}
function updateTabArrows() {
  const tabs = document.getElementById('collegeTabs');
  if (!tabs) return;
  const wrapper = tabs.closest('.college-tabs-wrapper');
  const left = document.querySelector('.tab-arrow-left');
  const right = document.querySelector('.tab-arrow-right');
  
  const canScroll = tabs.scrollWidth > tabs.clientWidth;
  if (wrapper) {
    wrapper.classList.toggle('has-scroll', canScroll);
  }
  
    if (left) {
      const atStart = !canScroll || tabs.scrollLeft <= 5;
      left.classList.toggle('hidden', window.innerWidth > 768 && atStart);
    }
    if (right) {
      const atEnd = !canScroll || tabs.scrollLeft + tabs.clientWidth >= tabs.scrollWidth - 5;
      right.classList.toggle('hidden', window.innerWidth > 768 && atEnd);
    }
    if (wrapper) wrapper.classList.toggle('scroll-end', canScroll && tabs.scrollLeft + tabs.clientWidth >= tabs.scrollWidth - 5);
}
document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.getElementById('collegeTabs');
  if (tabs) {
    updateTabArrows();
    tabs.addEventListener('scroll', updateTabArrows);
    window.addEventListener('resize', updateTabArrows);
  }
});

let userSavedThisCollege = <?= $userSavedThisCollege ? 'true' : 'false' ?>;
const collegeId = '<?= htmlspecialchars((string)$cid) ?>';

function toggleSaveCollege() {
  const btn = document.getElementById('saveCollegeBtn');
  if (!btn) return;
  
  btn.disabled = true;
  const action = userSavedThisCollege ? 'unsave' : 'save';
  
  fetch('<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/api/save_college.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      college_id: collegeId,
      action: action
    })
  })
  .then(res => res.json())
  .then(data => {
    btn.disabled = false;
    if (data.ok) {
      userSavedThisCollege = data.saved;
      if (userSavedThisCollege) {
        btn.innerHTML = '<i class="ph-fill ph-heart" style="color:#e11d48"></i> Saved';
      } else {
        btn.innerHTML = '<i class="ph ph-heart"></i> Save';
      }
    } else if (data.error === 'login_required') {
      openLoginPrompt();
    } else {
      alert(data.msg || 'Something went wrong.');
    }
  })
  .catch(err => {
    btn.disabled = false;
    console.error(err);
  });
}
</script>

<!-- Login Required Prompt Modal -->
<div id="loginPromptModal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:20px;max-width:400px;width:90%;box-shadow:0 25px 60px rgba(0,0,0,0.2);position:relative;overflow:hidden;">
    <button onclick="closeLoginPrompt()" style="position:absolute;top:14px;right:14px;background:none;border:none;font-size:1.3rem;cursor:pointer;color:rgba(15,23,42,0.4);z-index:1;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all .2s;" onmouseover="this.style.background='rgba(15,23,42,0.06)'" onmouseout="this.style.background='none'"><i class="ph ph-x"></i></button>
    <div style="padding:36px 32px 28px;text-align:center;">
      <div style="width:64px;height:64px;border-radius:50%;background:rgba(11,36,71,0.06);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <i class="ph-fill ph-lock" style="font-size:1.8rem;color:#19376D;"></i>
      </div>
      <h3 style="font-size:1.15rem;font-weight:800;color:#0f172a;margin:0 0 8px;">Login Required</h3>
      <p style="font-size:.88rem;color:rgba(15,23,42,0.5);margin:0 0 24px;line-height:1.6;">You need to login first to apply for admission. It only takes a minute!</p>
      <a href="<?= $loginUrl ?>" style="display:inline-flex;align-items:center;gap:8px;padding:13px 32px;background:#0B2447;color:#fff;border:none;border-radius:12px;font-size:.95rem;font-weight:700;cursor:pointer;text-decoration:none;transition:all .25s;width:100%;justify-content:center;box-sizing:border-box;" onmouseover="this.style.background='#19376D'" onmouseout="this.style.background='#0B2447'">
        <i class="ph ph-arrow-right"></i> Login to Apply
      </a>
      <p style="font-size:.78rem;color:rgba(15,23,42,0.35);margin-top:16px;">Don't have an account? <a href="<?= $loginUrl ?>&mode=register" style="color:#19376D;font-weight:600;text-decoration:none;">Sign up free</a></p>
    </div>
  </div>
</div>

<!-- Apply Now Modal -->
<div id="applyModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:20px;max-width:520px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,0.2);position:relative;">
    <button onclick="closeApplyModal()" style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:1.5rem;cursor:pointer;color:rgba(15,23,42,0.4);z-index:1;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all .2s;" onmouseover="this.style.background='rgba(15,23,42,0.06)'" onmouseout="this.style.background='none'"><i class="ph ph-x"></i></button>
    
    <div style="padding:32px 32px 24px;">
      <div style="text-align:center;margin-bottom:24px;">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(11,36,71,0.06);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:1.5rem;color:#19376D;"><i class="ph-fill ph-paper-plane-tilt"></i></div>
        <h2 style="font-size:1.3rem;font-weight:800;color:#0f172a;margin:0 0 4px;">Apply to <?= htmlspecialchars($college['name']) ?></h2>
        <p style="font-size:.85rem;color:rgba(15,23,42,0.5);margin:0;">Fill in your details to submit your application</p>
      </div>
      
      <form id="applyForm" onsubmit="submitApplication(event)">
        <input type="hidden" name="college_id" value="<?= htmlspecialchars($cid) ?>">
        
        <div style="margin-bottom:16px;">
          <label style="display:block;font-size:.8rem;font-weight:600;color:rgba(15,23,42,0.6);margin-bottom:6px;">Full Name *</label>
          <input type="text" name="full_name" required placeholder="Enter your full name" style="width:100%;padding:12px 14px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.9rem;outline:none;transition:border .2s;box-sizing:border-box;" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'">
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
          <div>
            <label style="display:block;font-size:.8rem;font-weight:600;color:rgba(15,23,42,0.6);margin-bottom:6px;">Email *</label>
            <input type="email" name="email" required placeholder="you@example.com" style="width:100%;padding:12px 14px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.9rem;outline:none;transition:border .2s;box-sizing:border-box;" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'">
          </div>
          <div>
            <label style="display:block;font-size:.8rem;font-weight:600;color:rgba(15,23,42,0.6);margin-bottom:6px;">Phone *</label>
            <input type="tel" name="phone" required placeholder="+91 XXXXX XXXXX" style="width:100%;padding:12px 14px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.9rem;outline:none;transition:border .2s;box-sizing:border-box;" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'">
          </div>
        </div>
        
        <div style="margin-bottom:16px;">
          <label style="display:block;font-size:.8rem;font-weight:600;color:rgba(15,23,42,0.6);margin-bottom:6px;">Course Interested In</label>
          <select name="course_id" style="width:100%;padding:12px 14px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.9rem;outline:none;background:#fff;cursor:pointer;box-sizing:border-box;" onchange="document.getElementById('courseNameField').value=this.options[this.selectedIndex].text">
            <option value="">Select a course</option>
            <?php foreach($courses as $co): ?>
            <option value="<?= htmlspecialchars($co['id'] ?? '') ?>"><?= htmlspecialchars($co['course_name'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="course_name" id="courseNameField" value="">
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
          <div>
            <label style="display:block;font-size:.8rem;font-weight:600;color:rgba(15,23,42,0.6);margin-bottom:6px;">Exam Score (if any)</label>
            <input type="text" name="exam_score" placeholder="e.g. JEE: 98.5" style="width:100%;padding:12px 14px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.9rem;outline:none;transition:border .2s;box-sizing:border-box;" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'">
          </div>
          <div>
            <label style="display:block;font-size:.8rem;font-weight:600;color:rgba(15,23,42,0.6);margin-bottom:6px;">Target Year</label>
            <select name="target_year" style="width:100%;padding:12px 14px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.9rem;outline:none;background:#fff;cursor:pointer;box-sizing:border-box;">
              <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
              <option value="<?= date('Y')+1 ?>"><?= date('Y')+1 ?></option>
            </select>
          </div>
        </div>
        
        <div style="margin-bottom:20px;">
          <label style="display:block;font-size:.8rem;font-weight:600;color:rgba(15,23,42,0.6);margin-bottom:6px;">Additional Notes</label>
          <textarea name="notes" rows="3" placeholder="Any specific queries or information..." style="width:100%;padding:12px 14px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.9rem;outline:none;resize:vertical;transition:border .2s;box-sizing:border-box;font-family:inherit;" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'"></textarea>
        </div>
        
        <button type="submit" id="applySubmitBtn" style="width:100%;padding:14px;background:#0B2447;color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:8px;">
          <i class="ph ph-paper-plane-tilt"></i> Submit Application
        </button>
        <p id="applyMsg" style="text-align:center;margin-top:12px;font-size:.85rem;display:none;"></p>
      </form>
    </div>
  </div>
</div>

<script>
function openLoginPrompt() {
  const m = document.getElementById('loginPromptModal');
  if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeLoginPrompt() {
  const m = document.getElementById('loginPromptModal');
  if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}
document.getElementById('loginPromptModal')?.addEventListener('click', function(e) {
  if (e.target === this) closeLoginPrompt();
});

function openApplyModal() {
  const m = document.getElementById('applyModal');
  if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeApplyModal() {
  const m = document.getElementById('applyModal');
  if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}
document.getElementById('applyModal')?.addEventListener('click', function(e) {
  if (e.target === this) closeApplyModal();
});

function submitApplication(e) {
  e.preventDefault();
  const form = document.getElementById('applyForm');
  const btn = document.getElementById('applySubmitBtn');
  const msg = document.getElementById('applyMsg');
  
  btn.disabled = true;
  btn.innerHTML = '<i class="ph ph-spinner" style="animation:spin 1s linear infinite"></i> Submitting...';
  
  fetch('<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/apply.php', {
    method: 'POST',
    body: new FormData(form)
  })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      msg.style.color = '#0B2447';
      msg.style.background = 'rgba(11,36,71,0.06)';
      msg.style.padding = '12px';
      msg.style.borderRadius = '10px';
      msg.innerHTML = '<i class="ph-fill ph-check-circle" style="font-size:1.2rem;vertical-align:middle;margin-right:4px;"></i> ' + data.msg + '<br><strong style="font-size:.9rem;">Application No: ' + data.app_number + '</strong>';
      msg.style.display = 'block';
      btn.innerHTML = '<i class="ph ph-check"></i> Submitted!';
      btn.style.background = '#059669';
      form.reset();
    } else {
      if (data.already_applied) {
        msg.style.color = '#92400e';
        msg.style.background = 'rgba(251,191,36,0.1)';
        msg.style.border = '1px solid rgba(251,191,36,0.3)';
        msg.innerHTML = '<i class="ph-fill ph-warning-circle" style="font-size:1.2rem;vertical-align:middle;margin-right:4px;"></i> ' + data.msg + '<br><span style="font-size:.85rem;">Application No: <strong>' + data.app_number + '</strong> &middot; Status: ' + data.status + '</span>';
      } else {
        msg.style.color = '#dc2626';
        msg.style.background = 'rgba(220,38,38,0.06)';
        msg.style.border = 'none';
        msg.innerHTML = data.msg;
      }
      msg.style.padding = '12px';
      msg.style.borderRadius = '10px';
      msg.style.display = 'block';
      if (data.redirect) {
        setTimeout(() => { window.location.href = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/' + data.redirect; }, 1500);
      } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="ph ph-paper-plane-tilt"></i> Submit Application';
      }
    }
  })
  .catch(() => {
    msg.style.color = '#dc2626';
    msg.innerHTML = 'Network error. Please try again.';
    msg.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<i class="ph ph-paper-plane-tilt"></i> Submit Application';
  });
}
</script>

<!-- Shared Modal -->
<div id="courseListModal" style="display:none;position:fixed;inset:0;z-index:10001;background:rgba(15,23,42,0.55);backdrop-filter:blur(4px);">
  <div id="courseListModalInner" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:16px;max-width:540px;width:calc(100% - 32px);max-height:80vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,0.25);box-sizing:border-box;">
    <div id="clModalHeader" style="background:linear-gradient(135deg,#0B2447,#19376D);padding:16px 20px;border-radius:16px 16px 0 0;display:flex;align-items:center;justify-content:space-between;">
      <div>
        <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:2px;">AdmissionSeason</div>
        <h3 style="margin:0;color:#fff;font-size:15px;font-weight:700;" id="clModalTitle">Emailed!</h3>
      </div>
      <button onclick="closeCourseListModal()" style="background:rgba(255,255,255,0.12);border:none;width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:1rem;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.12)'"><i class="ph ph-x"></i></button>
    </div>
    <div style="padding:20px 20px 14px;">
      <div style="display:flex;align-items:center;gap:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 14px;margin-bottom:16px;">
        <div style="width:32px;height:32px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="ph-fill ph-check-circle" style="font-size:1.1rem;color:#16a34a;"></i>
        </div>
        <div style="min-width:0;">
          <div style="font-size:13px;font-weight:700;color:#0f172a;" id="clModalSuccessMsg"></div>
          <div style="font-size:11px;color:#64748b;margin-top:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" id="clModalEmail"></div>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
        <div style="width:20px;height:20px;border-radius:50%;background:#0B2447;color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;">1</div>
        <span style="font-size:13px;font-weight:700;color:#0f172a;">You may also be interested in</span>
      </div>
    </div>
    <div id="clModalColleges" style="padding:0 20px 12px;display:grid;grid-template-columns:1fr;gap:10px;">
      <div style="grid-column:1/-1;text-align:center;padding:32px;color:#94a3b8;">
        <i class="ph ph-spinner" style="font-size:1.5rem;animation:spin 1s linear infinite;display:block;margin-bottom:8px;"></i>
        <span style="font-size:13px;">Finding similar colleges...</span>
      </div>
    </div>
    <div id="clModalPrefSection" style="padding:12px 20px;display:none;">
      <div style="background:linear-gradient(135deg,#fefce8,#fef9c3);border:1px solid #fde68a;border-radius:10px;padding:14px 16px;text-align:center;">
        <div style="font-size:13px;font-weight:700;color:#92400e;margin-bottom:8px;">Are you open to private colleges?</div>
        <div style="display:flex;gap:8px;justify-content:center;" id="clPrefBtns">
          <button onclick="savePref('open_to_private','yes')" style="padding:7px 24px;border:2px solid #92400e;background:#fff;color:#92400e;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;" onmouseover="this.style.background='#92400e';this.style.color='#fff'" onmouseout="this.style.background='#fff';this.style.color='#92400e'">Yes</button>
          <button onclick="savePref('open_to_private','no')" style="padding:7px 24px;border:2px solid #92400e;background:#fff;color:#92400e;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;" onmouseover="this.style.background='#92400e';this.style.color='#fff'" onmouseout="this.style.background='#fff';this.style.color='#92400e'">No</button>
        </div>
      </div>
    </div>
    <div id="clModalFeedback" style="padding:0 20px 18px;display:none;">
      <div style="text-align:center;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
        <span style="font-size:12px;color:#475569;font-weight:600;">Is this recommendation relevant? </span>
        <button onclick="savePref('recommendation_feedback','helpful')" style="background:none;border:1.5px solid #d1d5db;border-radius:6px;padding:5px 8px;cursor:pointer;margin-left:4px;color:#16a34a;" onmouseover="this.style.borderColor='#16a34a'" onmouseout="this.style.borderColor='#d1d5db'"><i class="ph-fill ph-thumbs-up"></i></button>
        <button onclick="savePref('recommendation_feedback','not_helpful')" style="background:none;border:1.5px solid #d1d5db;border-radius:6px;padding:5px 8px;cursor:pointer;margin-left:3px;color:#dc2626;" onmouseover="this.style.borderColor='#dc2626'" onmouseout="this.style.borderColor='#d1d5db'"><i class="ph-fill ph-thumbs-down"></i></button>
      </div>
    </div>
  </div>
</div>

<script>
var clModalMode = 'course_list';

function closeCourseListModal() {
  var m = document.getElementById('courseListModal');
  if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
  clModalMode = 'course_list';
}
document.getElementById('courseListModal')?.addEventListener('click', function(e) {
  if (e.target === this) closeCourseListModal();
});

function openModal(title, successMsg, email, collegeId) {
  document.getElementById('clModalTitle').textContent = title;
  document.getElementById('clModalSuccessMsg').textContent = successMsg;
  document.getElementById('clModalEmail').textContent = email || '';
  document.getElementById('courseListModal').style.display = 'block';
  document.body.style.overflow = 'hidden';
  document.getElementById('clModalColleges').innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:24px;color:#94a3b8;"><i class="ph ph-spinner" style="font-size:1.2rem;animation:spin 1s linear infinite;display:block;margin-bottom:6px;"></i><span style="font-size:12px;">Finding similar colleges...</span></div>';
  document.getElementById('clModalPrefSection').style.display = 'none';
  document.getElementById('clModalFeedback').style.display = 'none';
  document.getElementById('clPrefBtns').innerHTML = '<button onclick="savePref(\'open_to_private\',\'yes\')" style="padding:7px 24px;border:2px solid #92400e;background:#fff;color:#92400e;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;" onmouseover="this.style.background=\'#92400e\';this.style.color=\'#fff\'" onmouseout="this.style.background=\'#fff\';this.style.color=\'#92400e\'">Yes</button><button onclick="savePref(\'open_to_private\',\'no\')" style="padding:7px 24px;border:2px solid #92400e;background:#fff;color:#92400e;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;" onmouseover="this.style.background=\'#92400e\';this.style.color=\'#fff\'" onmouseout="this.style.background=\'#fff\';this.style.color=\'#92400e\'">No</button>';
  var fb = document.getElementById('clModalFeedback');
  if (fb) fb.querySelector('div').innerHTML = '<span style="font-size:12px;color:#475569;font-weight:600;">Is this recommendation relevant? </span><button onclick="savePref(\'recommendation_feedback\',\'helpful\')" style="background:none;border:1.5px solid #d1d5db;border-radius:6px;padding:5px 8px;cursor:pointer;margin-left:4px;color:#16a34a;" onmouseover="this.style.borderColor=\'#16a34a\'" onmouseout="this.style.borderColor=\'#d1d5db\'"><i class="ph-fill ph-thumbs-up"></i></button><button onclick="savePref(\'recommendation_feedback\',\'not_helpful\')" style="background:none;border:1.5px solid #d1d5db;border-radius:6px;padding:5px 8px;cursor:pointer;margin-left:3px;color:#dc2626;" onmouseover="this.style.borderColor=\'#dc2626\'" onmouseout="this.style.borderColor=\'#d1d5db\'"><i class="ph-fill ph-thumbs-down"></i></button>';
  loadSimilarColleges(collegeId);
}

function renderSimilarColleges(colleges) {
  var container = document.getElementById('clModalColleges');
  if (!colleges || colleges.length === 0) {
    container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:24px;color:#94a3b8;font-size:13px;">No similar colleges found.</div>';
    document.getElementById('clModalPrefSection').style.display = 'block';
    document.getElementById('clModalFeedback').style.display = 'block';
    return;
  }
  var btnLabel = clModalMode === 'brochure' ? 'Brochure' : 'Course List';
  var btnIcon = clModalMode === 'brochure' ? 'ph-download-simple' : 'ph-download-simple';
  var btnFn = clModalMode === 'brochure' ? 'sendBrochureFromModal' : 'sendCourseListFromModal';
  var html = '';
  colleges.forEach(function(c) {
    var rating = c.overall_rating_avg ? parseFloat(c.overall_rating_avg).toFixed(1) : null;
    var minFee = c.min_fee ? formatFeeNum(c.min_fee) : '\u2014';
    var maxFee = c.max_fee ? formatFeeNum(c.max_fee) : '';
    var feeRange = maxFee ? minFee + ' - ' + maxFee : minFee;
    var location = [c.city_name, c.state_name].filter(Boolean).join(', ');
    var logo = c.logo_url ? c.logo_url : '';
    var verified = c.is_verified ? ' <i class="ph-fill ph-seal-check" style="color:#16a34a;font-size:.75rem;"></i>' : '';
    html += '<div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:12px;box-sizing:border-box;overflow:hidden;">'
      + '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">'
      + (logo ? '<img src="' + logo + '" style="width:32px;height:32px;border-radius:6px;object-fit:cover;border:1px solid #e2e8f0;flex-shrink:0;" onerror="this.style.display=\'none\'">' : '<div style="width:32px;height:32px;border-radius:6px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:.8rem;flex-shrink:0;"><i class="ph ph-graduation-cap"></i></div>')
      + '<div style="flex:1;min-width:0;">'
      + '<a href="<?= rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") ?>/college/' + c.slug + '" target="_blank" style="font-size:12px;font-weight:700;color:#0f172a;text-decoration:none;line-height:1.3;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + c.name + verified + '</a>'
      + '<div style="font-size:10px;color:#94a3b8;margin-top:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><i class="ph ph-map-pin" style="font-size:.65rem;"></i> ' + location + '</div>'
      + '</div></div>'
      + '<div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;font-size:11px;color:#64748b;">'
      + '<span>Courses <strong style="color:#19376D;">' + (c.course_count || 0) + '</strong></span>'
      + (rating ? '<span><i class="ph-fill ph-star" style="color:#eab308;font-size:.65rem;"></i> <strong style="color:#0f172a;">' + rating + '</strong></span>' : '')
      + '<span style="margin-left:auto;">Fees <strong style="color:#0B2447;">' + feeRange + '</strong></span>'
      + '</div>'
      + '<button onclick="' + btnFn + '(\'' + c.id + '\', this)" style="width:100%;padding:8px 12px;background:#0B2447;color:#fff;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;box-sizing:border-box;" onmouseover="this.style.background=\'#19376D\'" onmouseout="this.style.background=\'#0B2447\'">'
      + '<i class="ph ' + btnIcon + '"></i> ' + btnLabel + '</button>'
      + '</div>';
  });
  container.innerHTML = html;
  document.getElementById('clModalPrefSection').style.display = 'block';
  document.getElementById('clModalFeedback').style.display = 'block';
}

function formatFeeNum(val) {
  if (!val) return '\u2014';
  val = parseFloat(val);
  if (val >= 10000000) return '\u20B9' + (val / 10000000).toFixed(1) + ' Cr';
  if (val >= 100000) return '\u20B9' + (val / 100000).toFixed(1) + ' L';
  if (val >= 1000) return '\u20B9' + (val / 1000).toFixed(0) + 'K';
  return '\u20B9' + val.toFixed(0);
}

function sendCourseListFromModal(collegeId, btn) {
  btn.disabled = true;
  btn.innerHTML = '<i class="ph ph-spinner" style="animation:spin 1s linear infinite"></i> Sending...';
  var fd = new FormData();
  fd.append('college_id', collegeId);
  fetch('<?= rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") ?>/send_brochure.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); }).then(function(data) {
      btn.disabled = false;
      if (data.ok) { btn.innerHTML = '<i class="ph-fill ph-check-circle"></i> Sent!'; btn.style.background = '#16a34a'; }
      else { btn.innerHTML = '<i class="ph ph-download-simple"></i> Course List'; alert(data.msg || 'Failed.'); }
    }).catch(function() { btn.disabled = false; btn.innerHTML = '<i class="ph ph-download-simple"></i> Course List'; alert('Network error.'); });
}

function sendBrochureFromModal(collegeId, btn) {
  btn.disabled = true;
  btn.innerHTML = '<i class="ph ph-spinner" style="animation:spin 1s linear infinite"></i> Sending...';
  var fd = new FormData();
  fd.append('college_id', collegeId);
  fetch('<?= rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") ?>/send_brochure_email.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); }).then(function(data) {
      btn.disabled = false;
      if (data.ok) { btn.innerHTML = '<i class="ph-fill ph-check-circle"></i> Sent!'; btn.style.background = '#16a34a'; }
      else { btn.innerHTML = '<i class="ph ph-download-simple"></i> Brochure'; alert(data.msg || 'Failed.'); }
    }).catch(function() { btn.disabled = false; btn.innerHTML = '<i class="ph ph-download-simple"></i> Brochure'; alert('Network error.'); });
}

function savePref(key, value) {
  fetch('<?= rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") ?>/api/save_preference.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ key: key, value: value })
  }).then(function(r){ return r.json(); }).then(function(data) {
    if (key === 'open_to_private') {
      var sec = document.getElementById('clPrefBtns');
      if (sec) sec.innerHTML = '<span style="font-size:13px;color:#16a34a;font-weight:600;"><i class="ph-fill ph-check-circle"></i> Thanks! We\'ll keep that in mind.</span>';
    }
    if (key === 'recommendation_feedback') {
      var fb = document.getElementById('clModalFeedback');
      if (fb) fb.querySelector('div').innerHTML = '<span style="font-size:13px;color:#16a34a;font-weight:600;"><i class="ph-fill ph-check-circle"></i> Thank you for your feedback!</span>';
    }
  }).catch(function(){});
}

function reportReview(reviewId, collegeId) {
  <?php if (!$isLoggedIn): ?>
  openLoginPrompt();
  return;
  <?php endif; ?>
  var reasons = ['spam','offensive','wrong_info','duplicate','other'];
  var reasonLabels = ['Spam','Offensive/Abusive','Wrong Information','Duplicate','Other'];
  var html = '<div id="reviewReportModal" style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.45);backdrop-filter:blur(3px)"><div style="background:#fff;border-radius:14px;padding:28px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.25)"><h3 style="margin:0 0 14px;font-size:1.1rem;font-weight:700">Report this review</h3><p style="font-size:.85rem;color:#64748b;margin-bottom:16px">Select a reason:</p><div style="display:flex;flex-direction:column;gap:8px">';
  reasons.forEach(function(r,i){ html += '<label style="display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;font-size:.88rem"><input type="radio" name="reportReason" value="'+r+'"> '+reasonLabels[i]+'</label>'; });
  html += '</div><div style="margin-top:12px"><textarea id="reportOtherText" placeholder="Additional details (optional)" style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:.85rem;resize:vertical;min-height:50px"></textarea></div><div style="display:flex;gap:8px;margin-top:16px;justify-content:flex-end"><button onclick="document.getElementById(\'reviewReportModal\').remove()" style="padding:8px 18px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-size:.88rem">Cancel</button><button onclick="submitReviewReport(\''+reviewId+'\')" style="padding:8px 18px;border:none;border-radius:8px;background:var(--primary);color:#fff;cursor:pointer;font-size:.88rem;font-weight:600">Submit</button></div></div></div>';
  document.body.insertAdjacentHTML('beforeend', html);
}

function submitReviewReport(reviewId) {
  var sel = document.querySelector('input[name="reportReason"]:checked');
  if (!sel) { alert('Please select a reason.'); return; }
  var otherText = document.getElementById('reportOtherText').value.trim();
  fetch('<?= rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") ?>/api/report_review.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ review_id: reviewId, reason: sel.value, other_text: otherText })
  }).then(function(r){ return r.json(); }).then(function(data) {
    document.getElementById('reviewReportModal').remove();
    if (data.ok) { alert(data.message || 'Report submitted.'); }
    else { alert(data.message || 'Failed to submit report.'); }
  }).catch(function(){ alert('Network error.'); });
}

function sendCourseList() {
  <?php if (!$isLoggedIn): ?>
  openLoginPrompt();
  return;
  <?php endif; ?>
  var btn = document.getElementById('courseListBtnHero') || document.getElementById('courseListBtn') || document.getElementById('courseListBtnSidebar');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner" style="animation:spin 1s linear infinite"></i> Sending...'; }
  var formData = new FormData();
  formData.append('college_id', '<?= htmlspecialchars($cid) ?>');
  clModalMode = 'course_list';
  fetch('<?= rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") ?>/send_brochure.php', { method: 'POST', body: formData })
  .then(function(r){ return r.json(); })
  .then(function(data) {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ph ph-files"></i> Course List'; }
    if (data.ok) {
      openModal('Course List Emailed', 'Course list has been emailed to ' + (data.email || 'your address.') , data.email, '<?= htmlspecialchars($cid) ?>');
    } else if (data.redirect) { window.location.href = data.redirect; }
    else { alert(data.msg || 'Failed to send email.'); }
  })
  .catch(function() {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ph ph-files"></i> Course List'; }
    alert('Network error.');
  });
}

function sendBrochure() {
  <?php if (!$isLoggedIn): ?>
  openLoginPrompt();
  return;
  <?php endif; ?>
  var btn = document.getElementById('brochureBtnHero');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner" style="animation:spin 1s linear infinite"></i> Sending...'; }
  var formData = new FormData();
  formData.append('college_id', '<?= htmlspecialchars($cid) ?>');
  clModalMode = 'brochure';
  fetch('<?= rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") ?>/send_brochure_email.php', { method: 'POST', body: formData })
  .then(function(r){ return r.json(); })
  .then(function(data) {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ph ph-download-simple"></i> Brochure'; }
    if (data.ok) {
      openModal('Brochure Emailed', 'Brochure has been emailed to ' + (data.email || 'your address.'), data.email, '<?= htmlspecialchars($cid) ?>');
    } else if (data.redirect) { window.location.href = data.redirect; }
    else { alert(data.msg || 'Failed to send email.'); }
  })
  .catch(function() {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ph ph-download-simple"></i> Brochure'; }
    alert('Network error.');
  });
}

function loadSimilarColleges(collegeId) {
  fetch('<?= rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") ?>/api/get_similar_colleges.php?college_id=' + encodeURIComponent(collegeId))
    .then(function(r){ return r.json(); })
    .then(function(data) {
      if (data.ok) renderSimilarColleges(data.colleges);
      else renderSimilarColleges([]);
    }).catch(function(){ renderSimilarColleges([]); });
}

/* ── Review Modal ── */
function openReviewModal() {
  document.getElementById('reviewModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeReviewModal() {
  document.getElementById('reviewModal').style.display = 'none';
  document.body.style.overflow = '';
}

function setStarRating(category, value) {
  var container = document.getElementById('stars-' + category);
  if (!container) return;
  container.dataset.value = value;
  var stars = container.querySelectorAll('i');
  stars.forEach(function(s, idx) {
    if (idx < value) { s.className = 'ph ph-star-fill'; s.style.color = '#f59e0b'; }
    else { s.className = 'ph ph-star'; s.style.color = 'rgba(15,23,42,0.15)'; }
  });
  var avg = document.getElementById('overall_avg_display');
  if (avg && category === 'overall') avg.textContent = value + '.0';
}

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.star-rating-group').forEach(function(group) {
    var cat = group.dataset.category;
    var stars = group.querySelectorAll('i');
    stars.forEach(function(star, idx) {
      star.addEventListener('click', function() { setStarRating(cat, idx + 1); });
      star.addEventListener('mouseenter', function() {
        stars.forEach(function(s, i) {
          if (i <= idx) { s.style.color = '#f59e0b'; } else { s.style.color = 'rgba(15,23,42,0.15)'; }
        });
      });
      star.addEventListener('mouseleave', function() {
        var val = parseInt(group.dataset.value) || 0;
        stars.forEach(function(s, i) {
          if (i < val) { s.style.color = '#f59e0b'; } else { s.style.color = 'rgba(15,23,42,0.15)'; }
        });
      });
    });
  });
});

function submitReview() {
  var btn = document.getElementById('reviewSubmitBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner" style="animation:spin 1s linear infinite"></i> Submitting...'; }

  var overall = parseInt(document.getElementById('stars-overall').dataset.value) || 0;
  if (overall < 1) { alert('Please select an overall rating.'); if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ph ph-check-circle"></i> Submit Review'; } return; }

  var payload = {
    college_id: '<?= htmlspecialchars($cid) ?>',
    overall_rating: overall,
    academics_rating: parseInt(document.getElementById('stars-value_money').dataset.value) || 0,
    faculty_rating: parseInt(document.getElementById('stars-faculty').dataset.value) || 0,
    placements_rating: parseInt(document.getElementById('stars-placements').dataset.value) || 0,
    infrastructure_rating: parseInt(document.getElementById('stars-infrastructure').dataset.value) || 0,
    campus_life_rating: parseInt(document.getElementById('stars-campus_life').dataset.value) || 0,
    food_rating: overall,
    review_title: document.getElementById('reviewTitle').value.trim(),
    review_body: document.getElementById('reviewBody').value.trim(),
    pros: document.getElementById('reviewPros').value.trim(),
    cons: document.getElementById('reviewCons').value.trim(),
    batch_year: parseInt(document.getElementById('reviewBatch').value) || 0,
    course_id: document.getElementById('reviewCourse').value
  };

  fetch(BASE_URL + '/api/submit_review.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(payload)
  })
  .then(function(r) { return r.text().then(function(t) { try { return { ok: r.ok, status: r.status, data: JSON.parse(t) }; } catch(e) { return { ok: false, status: r.status, data: { error: 'Server returned invalid response', raw: t.substring(0, 500) } }; } }); })
  .then(function(result) {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ph ph-check-circle"></i> Submit Review'; }
    var data = result.data;
    if (data.ok) {
      closeReviewModal();
      var msg = document.getElementById('reviewSuccessMsg');
      if (msg) { msg.style.display = 'block'; setTimeout(function(){ msg.style.display = 'none'; }, 5000); }
    } else if (data.error === 'login_required') {
      openLoginPrompt();
    } else {
      alert(data.message || data.error || 'Failed to submit review.');
    }
  })
  .catch(function(err) {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ph ph-check-circle"></i> Submit Review'; }
    alert('Network error: ' + (err.message || 'Please try again.'));
  });
}
</script>

<!-- Review Modal -->
<div id="reviewModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)closeReviewModal()">
  <div style="background:#fff;border-radius:20px;max-width:600px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,0.2);position:relative">
    <div style="padding:28px 32px 0;border-bottom:1px solid rgba(15,23,42,0.06);display:flex;align-items:center;justify-content:space-between">
      <h3 style="margin:0;font-size:1.3rem;font-weight:800;color:#0B2447;display:flex;align-items:center;gap:10px"><i class="ph ph-star" style="font-size:1.4rem"></i> Write a Review</h3>
      <button onclick="closeReviewModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:rgba(15,23,42,0.3);padding:4px"><i class="ph ph-x"></i></button>
    </div>

    <div id="reviewSuccessMsg" style="display:none;margin:16px 20px 0;padding:14px 20px;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:12px;color:#059669;font-weight:600;font-size:.9rem">
      <i class="ph ph-check-circle"></i> Review submitted! It will appear after moderation.
    </div>

    <div style="padding:24px 32px 32px">
      <!-- Overall Rating -->
      <div style="text-align:center;margin-bottom:24px;padding:20px;background:linear-gradient(135deg,rgba(11,36,71,0.03),rgba(11,36,71,0.06));border-radius:14px">
        <div style="font-size:.85rem;color:rgba(15,23,42,0.5);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Overall Rating</div>
        <div id="stars-overall" class="star-rating-group" data-category="overall" data-value="0" style="display:flex;gap:6px;justify-content:center;margin-bottom:4px">
          <i class="ph ph-star" style="font-size:2rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
          <i class="ph ph-star" style="font-size:2rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
          <i class="ph ph-star" style="font-size:2rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
          <i class="ph ph-star" style="font-size:2rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
          <i class="ph ph-star" style="font-size:2rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
        </div>
        <div style="font-size:1.2rem;font-weight:800;color:#0B2447"><span id="overall_avg_display">0.0</span>/5</div>
      </div>

      <!-- Category Ratings -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:24px">
        <?php foreach([
          ['key'=>'placements','label'=>'Placements'],
          ['key'=>'faculty','label'=>'Faculty'],
          ['key'=>'infrastructure','label'=>'Infrastructure'],
          ['key'=>'campus_life','label'=>'Campus Life'],
          ['key'=>'value_money','label'=>'Value for Money'],
        ] as $cat): ?>
        <div style="padding:14px;background:#f8fafc;border-radius:12px;border:1px solid rgba(15,23,42,0.06)">
          <div style="font-size:.8rem;font-weight:600;color:rgba(15,23,42,0.5);margin-bottom:8px"><?= $cat['label'] ?></div>
          <div id="stars-<?= $cat['key'] ?>" class="star-rating-group" data-category="<?= $cat['key'] ?>" data-value="0" style="display:flex;gap:4px">
            <i class="ph ph-star" style="font-size:1.3rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
            <i class="ph ph-star" style="font-size:1.3rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
            <i class="ph ph-star" style="font-size:1.3rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
            <i class="ph ph-star" style="font-size:1.3rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
            <i class="ph ph-star" style="font-size:1.3rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Review Title -->
      <div style="margin-bottom:16px">
        <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Review Title</label>
        <input type="text" id="reviewTitle" placeholder="Summarize your experience" maxlength="200" style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.95rem;font-family:inherit;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'">
      </div>

      <!-- Review Body -->
      <div style="margin-bottom:16px">
        <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Your Review *</label>
        <textarea id="reviewBody" rows="4" placeholder="Tell us about your experience at this college..." style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.95rem;font-family:inherit;resize:vertical;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'"></textarea>
      </div>

      <!-- Pros & Cons -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
        <div>
          <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px"><i class="ph ph-thumbs-up" style="color:#059669"></i> Pros</label>
          <textarea id="reviewPros" rows="2" placeholder="What did you like?" style="width:100%;padding:10px 14px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.88rem;font-family:inherit;resize:vertical;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'"></textarea>
        </div>
        <div>
          <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px"><i class="ph ph-thumbs-down" style="color:#DC2626"></i> Cons</label>
          <textarea id="reviewCons" rows="2" placeholder="What could be improved?" style="width:100%;padding:10px 14px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.88rem;font-family:inherit;resize:vertical;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'"></textarea>
        </div>
      </div>

      <!-- Batch Year & Course -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:24px">
        <div>
          <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Batch Year</label>
          <select id="reviewBatch" style="width:100%;padding:10px 14px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.88rem;font-family:inherit;background:#fff;box-sizing:border-box">
            <option value="">Select Year</option>
            <?php for($y = date('Y'); $y >= 2010; $y--): ?>
            <option value="<?= $y ?>"><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div>
          <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Course</label>
          <select id="reviewCourse" style="width:100%;padding:10px 14px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.88rem;font-family:inherit;background:#fff;box-sizing:border-box">
            <option value="">Select Course</option>
            <?php foreach($courses as $co): ?>
            <option value="<?= htmlspecialchars($co['id'] ?? '') ?>"><?= htmlspecialchars($co['course_name'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Submit -->
      <button type="button" id="reviewSubmitBtn" onclick="submitReview()" style="width:100%;padding:14px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:8px">
        <i class="ph ph-check-circle"></i> Submit Review
      </button>
    </div>
  </div>
</div>

<style>
@keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}
@media(min-width:480px){
  #clModalColleges{grid-template-columns:1fr 1fr !important;}
}
@media(max-width:479px){
  #courseListModalInner{max-height:90vh;}
}

/* Floating Write Review button - mobile only */
.cl-mobile-review-btn {
  display: none;
  position: fixed;
  bottom: 20px;
  right: 20px;
  left: 20px;
  z-index: 500;
  padding: 14px 24px;
  background: linear-gradient(135deg, #0B2447, #19376D);
  color: #fff;
  border: none;
  border-radius: 14px;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 8px 30px rgba(11, 36, 71, 0.35);
  font-family: var(--font2);
  transition: transform 0.2s;
}
.cl-mobile-review-btn:active { transform: scale(0.97); }
@media(max-width: 768px) {
  .cl-mobile-review-btn { display: flex; }
  .college-tab-content { padding-bottom: 80px !important; }
}
</style>
<?php if ($tab === 'reviews'): ?>
<?php if ($isLoggedIn): ?>
<button type="button" class="cl-mobile-review-btn" onclick="openReviewModal()"><i class="ph ph-pencil-simple"></i> Write a Review</button>
<?php else: ?>
<button type="button" class="cl-mobile-review-btn" onclick="openLoginPrompt()"><i class="ph ph-pencil-simple"></i> Write a Review</button>
<?php endif; ?>
<?php endif; ?>
</body>
</html>
