<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/panel_cms_2847/db.php';
require_once __DIR__ . '/includes/school_helpers.php';
require_once __DIR__ . '/includes/college_helpers.php';
require_once __DIR__ . '/includes/news_seo_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$slug = trim($_GET['slug'] ?? '');
$tab  = trim($_GET['tab'] ?? 'overview');
$tabs = schoolTabs();

if ($slug === '') {
    header('Location: schools.php');
    exit;
}
if (!isset($tabs[$tab])) {
    $tab = 'overview';
}

$school = loadSchoolBySlug($pdo, $slug);
if (!$school) {
    header('HTTP/1.0 404 Not Found');
    header('Location: schools.php');
    exit;
}

$sid = $school['id'];
$location = trim(($school['city_name'] ?? '') . ($school['city_name'] && $school['state_name'] ? ', ' : '') . ($school['state_name'] ?? ''));
$typeLabel = schoolTypeLabel($school['school_type']);
$boardLabel = schoolBoardLabel($school['board_affiliation']);
if ($school['board_affiliation'] === 'State' && !empty($school['board_state_name'])) {
    $boardLabel = $school['board_state_name'];
}
$year = $school['established_year'] ?? '';
$highlights = jsonLines($school['highlights_json'] ?? null);
$acceptedExams = jsonLines($school['accepted_exams'] ?? null);
$rating = (float)($school['overall_rating_avg'] ?? 0);
$reviewCount = (int)($school['total_reviews'] ?? 0);

// Fetch reviews
$reviews = [];
try {
    $s = $pdo->prepare("SELECT r.*, u.full_name AS user_name FROM reviews r LEFT JOIN users u ON u.id = r.user_id WHERE r.school_id = ? AND r.moderation_status = 'approved' ORDER BY r.created_at DESC LIMIT 30");
    $s->execute([$sid]);
    $reviews = $s->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

// Fetch courses
$schoolCourses = [];
try {
    $s = $pdo->prepare("SELECT * FROM school_courses WHERE school_id = ? AND is_active = 1 ORDER BY sort_order ASC, class_name ASC");
    $s->execute([$sid]);
    $schoolCourses = $s->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

// Fetch news
$updates = [];
try {
    $s = $pdo->prepare("SELECT * FROM school_news WHERE school_id = ? AND status='published' ORDER BY event_date DESC, created_at DESC LIMIT 20");
    $s->execute([$sid]);
    $updates = $s->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

$siteBase = getBaseUrl();
$canonicalUrl = $siteBase . '/school/' . urlencode($slug);
if ($tab !== 'overview') $canonicalUrl .= '/' . $tab;

$pageTitle = $school['meta_title'] ?: ($school['name'] . ': Fees, Admission ' . date('Y') . ', Courses, Reviews');
$metaDesc = $school['meta_description'] ?: ('Explore ' . $school['name'] . ' — courses, fees, admissions, infrastructure, reviews and school details.');

$schoolImage = '';
if (!empty($school['cover_image_url'])) {
    $schoolImage = $school['cover_image_url'];
} elseif (!empty($school['logo_url'])) {
    $schoolImage = $school['logo_url'];
}
if (!empty($schoolImage) && !str_starts_with($schoolImage, 'http')) {
    $schoolImage = $siteBase . '/' . ltrim($schoolImage, '/');
} elseif (empty($schoolImage)) {
    $schoolImage = $siteBase . '/assets/img/logo.png';
}

$tabIcons = [
    'overview' => 'ph-info', 'courses' => 'ph-book-open', 'admissions' => 'ph-paper-plane-tilt',
    'infrastructure' => 'ph-buildings', 'reviews' => 'ph-star', 'news' => 'ph-newspaper',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/favicon.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - AdmissionSeason</title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($school['name']) ?>, <?= htmlspecialchars($typeLabel) ?>, <?= htmlspecialchars($location) ?>, school fees, admissions, rating">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
  <link rel="canonical" href="<?= $canonicalUrl ?>">

  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= $canonicalUrl ?>">
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta property="og:image" content="<?= $schoolImage ?>">
  <meta property="og:site_name" content="AdmissionSeason">
  <meta property="og:locale" content="en_IN">

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="twitter:image" content="<?= $schoolImage ?>">

  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'School',
    'name' => $school['name'],
    'url' => $canonicalUrl,
    'description' => $metaDesc,
    'image' => $schoolImage,
    'address' => !empty($location) ? [
      '@type' => 'PostalAddress',
      'addressLocality' => $school['city_name'] ?? '',
      'addressRegion' => $school['state_name'] ?? '',
      'addressCountry' => 'IN'
    ] : null,
    'telephone' => $school['phone'] ?? null,
    'email' => $school['email'] ?? null,
    'foundingDate' => $year ? (string)$year : null,
    'numberOfStudents' => !empty($school['total_students']) ? $school['total_students'] : null,
    'aggregateRating' => $rating > 0 ? [
      '@type' => 'AggregateRating',
      'ratingValue' => number_format($rating, 1),
      'bestRating' => '5',
      'ratingCount' => $reviewCount,
    ] : null,
    'publisher' => [
      '@type' => 'Organization',
      'name' => 'AdmissionSeason',
      'url' => $siteBase,
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => "$siteBase/"],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'Schools', 'item' => "$siteBase/schools"],
      ['@type' => 'ListItem', 'position' => 3, 'name' => $school['name']],
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/college-pages.css?v=<?= time() ?>">
  <style>
    /* ── Applied Badge ──────────────────────────────────────────────── */
    .college-applied-badge{display:inline-flex;align-items:center;gap:6px;padding:10px 14px;background:rgba(5,150,105,0.08);border:1.5px solid rgba(5,150,105,0.25);border-radius:10px;font-size:.85rem;font-weight:600;color:#059669;white-space:nowrap}

    /* ── Tab Arrows ──────────────────────────────────────────────────── */
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

    /* ── Overview Stats ─────────────────────────────────────────────── */
    .overview-stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:24px}
    .overview-stat{text-align:center;padding:18px 12px;background:linear-gradient(135deg,#f8fafc,rgba(11,36,71,0.06));border-radius:14px;border:1px solid rgba(37,99,235,.1)}
    .overview-stat-val{font-size:1.4rem;font-weight:800;color:#19376D;font-family:'Plus Jakarta Sans',sans-serif}
    .overview-stat-lbl{font-size:.72rem;color:rgba(15,23,42,0.45);margin-top:4px;text-transform:uppercase;letter-spacing:.4px}

    /* ── Empty State ────────────────────────────────────────────────── */
    .tab-empty-state{text-align:center;padding:48px 24px;color:rgba(15,23,42,0.4)}
    .tab-empty-state i{font-size:3rem;display:block;margin-bottom:12px}
    .tab-empty-state p{font-size:.92rem}

    /* ── Infrastructure Grid ────────────────────────────────────────── */
    .infrastructure-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-top:16px}
    .infra-item{display:flex;align-items:center;gap:10px;padding:14px 16px;background:#f8fafc;border-radius:12px;border:1px solid rgba(15,23,42,0.06)}
    .infra-item .infra-icon{width:36px;height:36px;border-radius:10px;background:rgba(11,36,71,0.06);display:flex;align-items:center;justify-content:center;color:#19376D;font-size:1.1rem;flex-shrink:0}
    .infra-item.available .infra-icon{background:rgba(22,163,74,0.1);color:#16a34a}
    .infra-item span{font-size:.88rem;font-weight:500;color:rgba(15,23,42,0.7)}
    .infra-item.available span{color:#0f172a;font-weight:600}
    .infra-item .infra-check{margin-left:auto;color:#16a34a;font-size:1.1rem;flex-shrink:0}
    .infra-item:not(.available){opacity:.45}
    .infra-item:not(.available) .infra-icon{background:rgba(15,23,42,0.04);color:rgba(15,23,42,0.25)}
    .infra-item:not(.available) span{color:rgba(15,23,42,0.35);font-weight:400}

    /* ── Courses Table Card (mobile) ────────────────────────────────── */
    .courses-table-mobile{display:none}

    /* ── News card mobile ───────────────────────────────────────────── */
    .news-card-flex{display:flex;gap:16px;align-items:flex-start}
    .news-card-thumb{width:120px;height:90px;object-fit:cover;border-radius:10px;flex-shrink:0}

    /* ── Review modal responsive ────────────────────────────────────── */
    .review-modal-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .review-modal-double{display:grid;grid-template-columns:1fr 1fr;gap:14px}

    /* ══════════════════════════════════════════════════════════════════
       RESPONSIVE — Tablet (≤ 1024px)
       ══════════════════════════════════════════════════════════════════ */
    @media(max-width:1024px){
      .overview-stat-grid{grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px}
      .infrastructure-grid{grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px}
    }

    /* ══════════════════════════════════════════════════════════════════
       RESPONSIVE — Mobile landscape (≤ 768px)
       ══════════════════════════════════════════════════════════════════ */
    @media(max-width:768px){
      /* Hero */
      .college-hero{min-height:auto}
      .college-hero-inner{padding:16px 0 20px}
      .college-hero-main{flex-direction:column;gap:12px}
      .college-hero-logo{width:60px;height:60px;border-radius:12px}
      .college-hero-title{font-size:1.3rem;line-height:1.3}
      .college-hero-sub{font-size:.82rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
      .college-hero-chips{gap:6px}
      .college-hero-chips span{padding:4px 10px;font-size:.72rem}
      .college-hero-actions{flex-direction:column;gap:8px}
      .college-hero-actions .college-btn-primary,
      .college-hero-actions .college-btn-outline{width:100%;justify-content:center}

      /* Breadcrumb */
      .college-breadcrumb{font-size:.75rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

      /* Tabs */
      .college-detail-tabs{gap:0;overflow-x:auto;scrollbar-width:none}
      .college-detail-tabs::-webkit-scrollbar{display:none}
      .college-detail-tabs a{padding:12px 12px;font-size:.8rem;white-space:nowrap}

      /* Content */
      .college-tab-content{padding:18px}
      .college-section{margin-bottom:24px}
      .college-section h2{font-size:1rem;margin-bottom:12px;padding-bottom:8px}
      .college-prose{font-size:.85rem;line-height:1.7}

      /* Stats */
      .overview-stat-grid{grid-template-columns:repeat(2,1fr);gap:8px}
      .overview-stat{padding:14px 10px}
      .overview-stat-val{font-size:1.15rem}

      /* Infrastructure */
      .infrastructure-grid{grid-template-columns:1fr 1fr;gap:8px}
      .infra-item{padding:10px 12px;gap:8px}
      .infra-item .infra-icon{width:30px;height:30px;font-size:.95rem}
      .infra-item span{font-size:.8rem}

      /* Courses — hide table, show cards */
      .college-table-wrap{display:none !important}
      .courses-table-mobile{display:flex !important;flex-direction:column;gap:12px}
      .course-mobile-card{background:#fff;border:1px solid rgba(15,23,42,0.08);border-radius:12px;padding:16px}
      .course-mobile-card .cmc-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
      .course-mobile-card .cmc-name{font-size:1rem;font-weight:700;color:#0B2447}
      .course-mobile-card .cmc-level{font-size:.75rem;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(11,36,71,0.06);color:#19376D;text-transform:capitalize}
      .course-mobile-card .cmc-details{display:grid;grid-template-columns:1fr 1fr;gap:8px}
      .course-mobile-card .cmc-detail{display:flex;flex-direction:column;gap:2px}
      .course-mobile-card .cmc-detail .cmc-label{font-size:.68rem;font-weight:600;text-transform:uppercase;color:rgba(15,23,42,0.4);letter-spacing:.3px}
      .course-mobile-card .cmc-detail .cmc-value{font-size:.88rem;font-weight:600;color:#0f172a}

      /* News */
      .news-card-flex{flex-direction:column;gap:10px}
      .news-card-thumb{width:100%;height:160px}

      /* Reviews */
      .college-review-card{padding:14px}
      .college-review-card h4{font-size:.88rem}
      .college-review-card p{font-size:.82rem;line-height:1.6}

      /* Info grid */
      .college-info-grid{grid-template-columns:1fr 1fr;gap:10px}
      .college-info-grid>div{padding:12px}

      /* Contact */
      .college-contact-grid p{padding:10px 12px;font-size:.82rem}

      /* Tags */
      .college-tag{padding:4px 10px;font-size:.72rem}

      /* Sidebar — bottom sheet */
      .shiksha-sidebar{display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:200;background:rgba(0,0,0,.5);padding:0;align-items:flex-end}
      .shiksha-sidebar.open{display:flex}
      .shiksha-sidebar .shiksha-widget-wrapper{
        position:static;border-radius:16px 16px 0 0;max-height:85vh;overflow-y:auto;
        width:100%;box-shadow:0 -4px 24px rgba(0,0,0,.2);
        background:#fff;padding:20px;display:flex;flex-direction:column;gap:16px
      }
      .shiksha-sidebar .shiksha-widget{margin:0;background:#f8fafc}

      /* Review modal */
      .review-modal-grid{grid-template-columns:1fr;gap:10px}
      .review-modal-double{grid-template-columns:1fr;gap:10px}
      #schoolReviewModal > div{margin:10px;max-height:95vh}
      #schoolReviewModal .review-modal-header{padding:20px 20px 0}
      #schoolReviewModal .review-modal-body{padding:20px}
    }

    /* ══════════════════════════════════════════════════════════════════
       RESPONSIVE — Mobile portrait (≤ 480px)
       ══════════════════════════════════════════════════════════════════ */
    @media(max-width:480px){
      .college-hero-inner{padding:12px 0 16px}
      .college-hero-logo{width:48px;height:48px;border-radius:10px}
      .college-hero-title{font-size:1.1rem;margin-bottom:4px}
      .college-hero-sub{font-size:.75rem;margin-bottom:8px}
      .college-hero-chips{gap:4px}
      .college-hero-chips span{padding:3px 7px;font-size:.65rem}

      .college-tab-content{padding:14px}
      .college-detail-tabs a{padding:10px 8px;font-size:.72rem}

      .overview-stat-grid{grid-template-columns:1fr 1fr;gap:6px}
      .overview-stat{padding:10px 8px;border-radius:10px}
      .overview-stat-val{font-size:1rem}
      .overview-stat-lbl{font-size:.6rem}

      .infrastructure-grid{grid-template-columns:1fr 1fr;gap:6px}
      .infra-item{padding:8px 10px;gap:6px;border-radius:8px}
      .infra-item .infra-icon{width:26px;height:26px;font-size:.85rem;border-radius:8px}
      .infra-item span{font-size:.72rem}

      .course-mobile-card{padding:14px}
      .course-mobile-card .cmc-name{font-size:.92rem}
      .course-mobile-card .cmc-details{grid-template-columns:1fr 1fr;gap:6px}
      .course-mobile-card .cmc-detail .cmc-value{font-size:.82rem}

      .college-section{margin-bottom:18px}
      .college-section h2{font-size:.92rem;margin-bottom:10px;padding-bottom:6px}

      .college-info-grid{grid-template-columns:1fr;gap:6px}
      .college-info-grid>div{padding:10px 12px}
      .college-info-grid strong{font-size:.62rem}
      .college-info-grid p{font-size:.78rem}

      .college-contact-grid p{padding:8px 10px;font-size:.78rem}

      .college-tag{padding:3px 8px;font-size:.68rem}

      .college-review-card{padding:12px}
      .college-review-card h4{font-size:.82rem}
      .college-review-card p{font-size:.78rem;line-height:1.55}

      .news-card-thumb{height:140px}

      .tab-empty-state{padding:32px 16px}
      .tab-empty-state i{font-size:2.2rem}
      .tab-empty-state p{font-size:.82rem}
    }
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Hero -->
<div class="college-hero" style="background-image:url('<?= cImg($school['cover_image_url']) ?>')">
  <div class="college-hero-overlay"></div>
  <div class="container college-hero-inner">
    <div class="shiksha-breadcrumb college-breadcrumb">
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/index.php">Home</a>
      <i class="ph ph-caret-right"></i>
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/schools.php">Schools</a>
      <i class="ph ph-caret-right"></i>
      <span><?= htmlspecialchars($school['name']) ?></span>
    </div>
    <div class="college-hero-card">
      <div class="college-hero-main">
        <?php if ($school['logo_url']): ?>
        <img src="<?= cImg($school['logo_url']) ?>" class="college-hero-logo" alt="<?= htmlspecialchars($school['name']) ?>">
        <?php endif; ?>
        <div>
          <h1 class="college-hero-title"><?= htmlspecialchars($school['name']) ?></h1>
          <p class="college-hero-sub"><?= htmlspecialchars($school['name']) ?>: Fees, Admission <?= date('Y') ?>, Courses, Reviews</p>
          <div class="college-hero-chips">
            <?php if ($location): ?><span><i class="ph ph-map-pin"></i> <?= htmlspecialchars($location) ?></span><?php endif; ?>
            <?php if ($rating > 0): ?>
            <span><i class="ph ph-star-fill" style="color:#19376D"></i> <?= number_format($rating, 1) ?> / 5</span>
            <span><?= $reviewCount ?> Reviews</span>
            <?php endif; ?>
            <span><i class="ph ph-graduation-cap"></i> <?= htmlspecialchars($typeLabel) ?></span>
            <?php if ($boardLabel): ?><span><i class="ph ph-bookmark"></i> <?= htmlspecialchars($boardLabel) ?></span><?php endif; ?>
            <?php if ($year): ?><span><i class="ph ph-calendar"></i> Estd <?= htmlspecialchars((string)$year) ?></span><?php endif; ?>
            <?php if (!empty($school['is_verified'])): ?><span style="background:rgba(22,163,74,.35);border-color:rgba(22,163,74,.5)"><i class="ph-fill ph-seal-check"></i> Verified</span><?php endif; ?>
          </div>

          <!-- Save & Apply Buttons -->
          <div class="college-hero-actions">
            <?php
            $isSaved = false;
            $hasApplied = false;
            $userId = $_SESSION['user_id'] ?? null;
            if ($userId) {
                try {
                    $saveChk = $pdo->prepare("SELECT id FROM saved_schools WHERE user_id = ? AND school_id = ?");
                    $saveChk->execute([$userId, $sid]);
                    $isSaved = (bool)$saveChk->fetch();
                } catch(Exception $e) {}
                try {
                    $applyChk = $pdo->prepare("SELECT id, created_at FROM leads WHERE user_id = ? AND school_id = ? AND lead_type = 'apply' ORDER BY created_at DESC LIMIT 1");
                    $applyChk->execute([$userId, $sid]);
                    $appliedRow = $applyChk->fetch();
                    $hasApplied = (bool)$appliedRow;
                    $appliedDate = $hasApplied ? date('d M Y', strtotime($appliedRow['created_at'])) : '';
                } catch(Exception $e) {}
            }
            ?>
            <button class="college-btn-outline" id="saveSchoolBtn" onclick="toggleSaveSchool()" style="display:flex;align-items:center;gap:6px">
              <i class="<?= $isSaved ? 'ph-fill ph-heart-break' : 'ph ph-heart' ?>" id="saveIcon"></i>
              <span id="saveLabel"><?= $isSaved ? 'Saved' : 'Save' ?></span>
            </button>
            <?php if ($hasApplied): ?>
            <div class="college-applied-badge" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:rgba(5,150,105,0.08);border:1.5px solid rgba(5,150,105,0.25);border-radius:10px;font-size:.87rem;font-weight:600;color:#059669">
              <i class="ph-fill ph-check-circle" style="font-size:1.1rem"></i>
              <span>Already Applied<?= $appliedDate ? ' on ' . $appliedDate : '' ?></span>
            </div>
            <?php else: ?>
            <button class="college-btn-primary" onclick="openApplyModal()" style="display:flex;align-items:center;gap:6px">
              <i class="ph ph-paper-plane-tilt"></i> Apply Now
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tabs Nav -->
<div class="shiksha-tabs-nav college-tabs-sticky">
  <div class="container">
    <div class="college-tabs-wrapper">
      <button class="tab-arrow tab-arrow-left" onclick="scrollTabs(-1)" aria-label="Scroll tabs left"><i class="ph ph-caret-left"></i></button>
      <div class="shiksha-tabs college-detail-tabs" id="collegeTabs">
        <?php foreach ($tabs as $key => $label): ?>
        <a href="<?= schoolUrl($slug, $key) ?>" class="<?= $tab === $key ? 'active' : '' ?>">
          <?= htmlspecialchars($label) ?>
          <?php if ($key === 'reviews' && $reviewCount > 0): ?><span style="background:rgba(11,36,71,0.06);color:#19376D;padding:1px 6px;border-radius:10px;font-size:.7rem;margin-left:3px"><?= $reviewCount ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
      <button class="tab-arrow tab-arrow-right" onclick="scrollTabs(1)" aria-label="Scroll tabs right"><i class="ph ph-caret-right"></i></button>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="container shiksha-main-wrapper college-detail-wrap">
  <div class="shiksha-layout">

    <main class="shiksha-content">
      <div class="college-tab-content">

        <!-- OVERVIEW TAB -->
        <?php if ($tab === 'overview'): ?>
          <p class="college-updated"><i class="ph ph-clock"></i> Last updated on <?= date('d M \'y') ?></p>

          <div class="overview-stat-grid">
            <?php if ($year): ?><div class="overview-stat"><div class="overview-stat-val"><?= htmlspecialchars((string)$year) ?></div><div class="overview-stat-lbl">Established</div></div><?php endif; ?>
            <?php if (!empty($school['total_students'])): ?><div class="overview-stat"><div class="overview-stat-val"><?= number_format((int)$school['total_students']) ?>+</div><div class="overview-stat-lbl">Students</div></div><?php endif; ?>
            <?php if (!empty($school['total_faculty'])): ?><div class="overview-stat"><div class="overview-stat-val"><?= (int)$school['total_faculty'] ?>+</div><div class="overview-stat-lbl">Faculty</div></div><?php endif; ?>
            <?php if (!empty($school['campus_area_acres'])): ?><div class="overview-stat"><div class="overview-stat-val"><?= (float)$school['campus_area_acres'] ?></div><div class="overview-stat-lbl">Acres Campus</div></div><?php endif; ?>
            <?php if ($boardLabel): ?><div class="overview-stat"><div class="overview-stat-val"><?= htmlspecialchars($boardLabel) ?></div><div class="overview-stat-lbl">Board</div></div><?php endif; ?>
          </div>

          <?php if (!empty($school['about_text'])): ?>
          <section class="college-section">
            <h2>About <?= htmlspecialchars($school['name']) ?></h2>
            <div class="college-prose"><?= $school['about_text'] ?></div>
          </section>
          <?php endif; ?>

          <?php if (!empty($highlights)): ?>
          <section class="college-section">
            <div class="college-highlights-card">
              <div class="college-highlights-header">
                <?php if ($school['logo_url']): ?>
                <img src="<?= cImg($school['logo_url']) ?>" alt="" class="college-highlights-logo">
                <?php else: ?>
                <div class="college-highlights-logo college-highlights-logo-placeholder"><i class="ph ph-graduation-cap"></i></div>
                <?php endif; ?>
                <div>
                  <h2>School Highlights</h2>
                  <p class="college-highlights-sub">Why <?= htmlspecialchars($school['name']) ?> stands out</p>
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

          <section class="college-section">
            <h2>Contact & Location</h2>
            <div class="college-contact-grid">
              <?php if ($school['address']): ?><p><i class="ph ph-map-pin"></i> <?= htmlspecialchars($school['address']) ?><?= $school['pincode'] ? ', ' . htmlspecialchars($school['pincode']) : '' ?></p><?php endif; ?>
              <?php if ($school['phone']): ?><p><i class="ph ph-phone"></i> <a href="tel:<?= htmlspecialchars($school['phone']) ?>"><?= htmlspecialchars($school['phone']) ?></a></p><?php endif; ?>
              <?php if ($school['email']): ?><p><i class="ph ph-envelope"></i> <a href="mailto:<?= htmlspecialchars($school['email']) ?>"><?= htmlspecialchars($school['email']) ?></a></p><?php endif; ?>
              <?php if ($school['website_url']): ?><p><i class="ph ph-globe"></i> <a href="<?= htmlspecialchars($school['website_url']) ?>" target="_blank" rel="noopener noreferrer">Official Website ↗</a></p><?php endif; ?>
            </div>
          </section>

        <!-- COURSES & FEES TAB -->
        <?php elseif ($tab === 'courses'): ?>
          <section class="college-section">
            <h2>Classes & Fee Structure</h2>
            <?php if (empty($schoolCourses)): ?>
            <div class="tab-empty-state"><i class="ph ph-book-open"></i><p>Class details coming soon.</p></div>
            <?php else: ?>
            <!-- Desktop table -->
            <div class="college-table-wrap">
              <table class="college-data-table">
                <thead><tr><th>Class</th><th>Level</th><th>Annual Fee</th><th>Semester Fee</th><th>Total Fee</th><th>Seats</th></tr></thead>
                <tbody>
                <?php foreach ($schoolCourses as $sc): ?>
                <tr>
                  <td data-label="Class"><strong><?= htmlspecialchars($sc['class_name']) ?></strong></td>
                  <td data-label="Level" style="text-transform:capitalize"><?= htmlspecialchars($sc['class_level'] ?? '—') ?></td>
                  <td data-label="Annual Fee"><strong style="color:#0B2447"><?= formatFee(isset($sc['annual_fee']) ? (float)$sc['annual_fee'] : null) ?></strong></td>
                  <td data-label="Sem Fee"><?= formatFee(isset($sc['semester_fee']) ? (float)$sc['semester_fee'] : null) ?></td>
                  <td data-label="Total Fee"><?= formatFee(isset($sc['total_fee']) ? (float)$sc['total_fee'] : null) ?></td>
                  <td data-label="Seats"><?= htmlspecialchars((string)($sc['seats_available'] ?? '—')) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <!-- Mobile card layout -->
            <div class="courses-table-mobile">
              <?php foreach ($schoolCourses as $sc): ?>
              <div class="course-mobile-card">
                <div class="cmc-header">
                  <span class="cmc-name"><?= htmlspecialchars($sc['class_name']) ?></span>
                  <?php if (!empty($sc['class_level'])): ?><span class="cmc-level"><?= htmlspecialchars($sc['class_level']) ?></span><?php endif; ?>
                </div>
                <div class="cmc-details">
                  <div class="cmc-detail">
                    <span class="cmc-label">Annual Fee</span>
                    <span class="cmc-value" style="color:#0B2447"><?= formatFee(isset($sc['annual_fee']) ? (float)$sc['annual_fee'] : null) ?></span>
                  </div>
                  <div class="cmc-detail">
                    <span class="cmc-label">Semester Fee</span>
                    <span class="cmc-value"><?= formatFee(isset($sc['semester_fee']) ? (float)$sc['semester_fee'] : null) ?></span>
                  </div>
                  <div class="cmc-detail">
                    <span class="cmc-label">Total Fee</span>
                    <span class="cmc-value"><?= formatFee(isset($sc['total_fee']) ? (float)$sc['total_fee'] : null) ?></span>
                  </div>
                  <div class="cmc-detail">
                    <span class="cmc-label">Seats</span>
                    <span class="cmc-value"><?= htmlspecialchars((string)($sc['seats_available'] ?? '—')) ?></span>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($school['admission_process'])): ?>
            <h3 style="margin-top:24px;font-size:1.1rem;color:#0B2447">Admission Process</h3>
            <div class="college-prose"><?= $school['admission_process'] ?></div>
            <?php endif; ?>
          </section>

        <!-- ADMISSIONS TAB -->
        <?php elseif ($tab === 'admissions'): ?>
          <section class="college-section">
            <h2>Admission Process</h2>
            <?php if (empty($school['admission_process'])): ?>
            <div class="tab-empty-state"><i class="ph ph-paper-plane-tilt"></i><p>Admission details coming soon.</p></div>
            <?php else: ?>
            <div class="college-prose"><?= $school['admission_process'] ?></div>
            <?php endif; ?>

            <div class="college-info-grid" style="margin-top:20px">
              <?php if (!empty($acceptedExams)): ?>
              <div style="grid-column:1/-1">
                <strong>Accepted Entrance Exams</strong>
                <div class="college-tag-row" style="margin-top:8px">
                  <?php foreach ($acceptedExams as $ex): ?><span class="college-tag"><?= htmlspecialchars(trim((string)$ex)) ?></span><?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>
              <?php if ($school['admission_start_date']): ?><div><strong>Application Start</strong><p><?= date('d M Y', strtotime($school['admission_start_date'])) ?></p></div><?php endif; ?>
              <?php if ($school['admission_end_date']): ?><div><strong>Application End</strong><p><?= date('d M Y', strtotime($school['admission_end_date'])) ?></p></div><?php endif; ?>
            </div>
          </section>

        <!-- INFRASTRUCTURE TAB -->
        <?php elseif ($tab === 'infrastructure'): ?>
          <section class="college-section">
            <h2>Infrastructure & Facilities</h2>
            <div class="infrastructure-grid">
              <?php
              $infraItems = [
                ['key' => 'library', 'label' => 'Library', 'icon' => 'ph-books'],
                ['key' => 'auditorium', 'label' => 'Auditorium', 'icon' => 'ph-megaphone'],
                ['key' => 'cafeteria', 'label' => 'Cafeteria', 'icon' => 'ph-coffee'],
                ['key' => 'wifi', 'label' => 'WiFi Campus', 'icon' => 'ph-wifi-high'],
                ['key' => 'medical_facility', 'label' => 'Medical Facility', 'icon' => 'ph-heart-pulse'],
                ['key' => 'transport', 'label' => 'Transport', 'icon' => 'ph-bus'],
                ['key' => 'playground', 'label' => 'Playground', 'icon' => 'ph-soccer-ball'],
                ['key' => 'swimming_pool', 'label' => 'Swimming Pool', 'icon' => 'ph-swimming-pool'],
                ['key' => 'labs', 'label' => 'Science Labs', 'icon' => 'ph-flask'],
                ['key' => 'smart_classrooms', 'label' => 'Smart Classrooms', 'icon' => 'ph-monitor'],
              ];
              foreach ($infraItems as $item):
                $available = !empty($school[$item['key']]);
              ?>
              <div class="infra-item <?= $available ? 'available' : '' ?>">
                <div class="infra-icon"><i class="ph <?= $item['icon'] ?>"></i></div>
                <span><?= $item['label'] ?></span>
                <?php if ($available): ?><i class="ph ph-check-circle infra-check"></i><?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
          </section>

        <!-- REVIEWS TAB -->
        <?php elseif ($tab === 'reviews'): ?>
          <section class="college-section">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
              <h2 style="margin:0">Student Reviews <span class="college-count">(<?= $reviewCount ?>)</span></h2>
              <button onclick="openSchoolReviewModal()" style="padding:10px 22px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;border:none;border-radius:10px;font-size:.88rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px"><i class="ph ph-pencil-simple"></i> Write a Review</button>
            </div>

            <div id="schoolReviewSuccess" style="display:none;margin-top:16px;padding:14px 20px;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:12px;color:#059669;font-weight:600;font-size:.9rem">
              <i class="ph ph-check-circle"></i> Review submitted successfully! It will appear after moderation.
            </div>

            <?php if (empty($reviews)): ?>
            <div class="tab-empty-state" style="margin-top:20px"><i class="ph ph-star"></i><p>No reviews yet. Be the first to review!</p></div>
            <?php else: ?>
            <div class="college-reviews-list" style="margin-top:20px">
              <?php foreach ($reviews as $rev): ?>
              <article class="college-review-card">
                <div class="cr-head">
                  <strong><?= htmlspecialchars($rev['user_name'] ?? 'Student') ?></strong>
                  <span class="cr-rating"><i class="ph ph-star-fill"></i> <?= number_format((float)$rev['overall_rating'], 1) ?></span>
                </div>
                <?php if ($rev['review_title']): ?><h4><?= htmlspecialchars($rev['review_title']) ?></h4><?php endif; ?>
                <?php if ($rev['review_body']): ?><p><?= nl2br(htmlspecialchars($rev['review_body'])) ?></p><?php endif; ?>
              </article>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <!-- NEWS TAB -->
        <?php elseif ($tab === 'news'): ?>
          <section class="college-section">
            <h2>News & Updates</h2>
            <?php if (empty($updates)): ?>
            <div class="tab-empty-state"><i class="ph ph-newspaper"></i><p>No news or updates available yet.</p></div>
            <?php else: ?>
            <div class="college-reviews-list">
              <?php foreach ($updates as $up): ?>
              <a href="<?= $siteBase ?>/school/<?= urlencode($slug) ?>/news/<?= $up['id'] ?>" style="text-decoration:none;color:inherit;display:block">
              <article class="college-review-card" style="cursor:pointer;transition:box-shadow .2s" onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
                <div class="news-card-flex">
                  <?php if(!empty($up['image_url'])): ?>
                    <?php $imgSrc = str_starts_with($up['image_url'],'http') ? $up['image_url'] : $siteBase.'/'.$up['image_url']; ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>" class="news-card-thumb" alt="">
                  <?php endif; ?>
                  <div style="flex:1;min-width:0">
                    <div class="cr-head">
                      <strong><?= htmlspecialchars($up['title']) ?></strong>
                      <?php if (!empty($up['event_date'])): ?><span style="font-size:.82rem;color:rgba(15,23,42,0.45)"><?= date('d M Y', strtotime($up['event_date'])) ?></span><?php endif; ?>
                    </div>
                    <?php if (!empty($up['excerpt'])): ?>
                    <p><?= nl2br(htmlspecialchars($up['excerpt'])) ?></p>
                    <?php endif; ?>
                    <span style="font-size:.82rem;color:#19376D;font-weight:600">Read more <i class="ph ph-arrow-right"></i></span>
                  </div>
                </div>
              </article>
              </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <?php endif; ?>
      </div>
    </main>

    <!-- Sidebar -->
    <aside class="shiksha-sidebar">
      <div class="shiksha-widget-wrapper">
        <div class="shiksha-widget">
          <h4 class="shiksha-widget-title">Quick Facts</h4>
          <ul class="shiksha-widget-list">
            <?php if ($typeLabel): ?><li><a href="#"><span style="margin-right:auto">Type</span> <strong><?= htmlspecialchars($typeLabel) ?></strong></a></li><?php endif; ?>
            <?php if ($boardLabel): ?><li><a href="#"><span style="margin-right:auto">Board</span> <strong><?= htmlspecialchars($boardLabel) ?></strong></a></li><?php endif; ?>
            <?php if ($year): ?><li><a href="#"><span style="margin-right:auto">Established</span> <strong><?= htmlspecialchars((string)$year) ?></strong></a></li><?php endif; ?>
            <?php if (!empty($school['total_students'])): ?><li><a href="#"><span style="margin-right:auto">Students</span> <strong><?= number_format((int)$school['total_students']) ?></strong></a></li><?php endif; ?>
            <?php if (!empty($school['total_faculty'])): ?><li><a href="#"><span style="margin-right:auto">Faculty</span> <strong><?= (int)$school['total_faculty'] ?></strong></a></li><?php endif; ?>
            <?php if ($rating > 0): ?><li><a href="#"><span style="margin-right:auto">Rating</span> <strong><?= number_format($rating, 1) ?>/5</strong></a></li><?php endif; ?>
          </ul>
        </div>

        <div class="shiksha-widget" style="background:linear-gradient(135deg,rgba(11,36,71,0.06),rgba(11,36,71,0.04));border-color:rgba(79,70,229,.2)">
          <h4 class="shiksha-widget-title" style="color:#19376D">Free Counselling</h4>
          <p style="font-size:.85rem;color:rgba(15,23,42,0.65);margin-bottom:12px">Get expert guidance for school admissions.</p>
          <a href="<?= $siteBase ?>/counselling" style="display:block;text-align:center;padding:10px;background:linear-gradient(135deg,#19376D,#0B2447);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:.87rem">Get Free Help</a>
        </div>
      </div>
    </aside>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- School Review Modal -->
<div id="schoolReviewModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)closeSchoolReviewModal()">
  <div style="background:#fff;border-radius:20px;max-width:600px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,0.2);position:relative">
    <div class="review-modal-header" style="padding:28px 32px 0;border-bottom:1px solid rgba(15,23,42,0.06);display:flex;align-items:center;justify-content:space-between">
      <h3 style="margin:0;font-size:1.3rem;font-weight:800;color:#0B2447;display:flex;align-items:center;gap:10px"><i class="ph ph-star" style="font-size:1.4rem"></i> Write a Review</h3>
      <button onclick="closeSchoolReviewModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:rgba(15,23,42,0.3);padding:4px"><i class="ph ph-x"></i></button>
    </div>
    <div class="review-modal-body" style="padding:24px 32px 32px">
      <div style="text-align:center;margin-bottom:24px;padding:20px;background:linear-gradient(135deg,rgba(11,36,71,0.03),rgba(11,36,71,0.06));border-radius:14px">
        <div style="font-size:.85rem;color:rgba(15,23,42,0.5);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Overall Rating</div>
        <div id="school-stars-overall" class="star-rating-group" data-category="overall" data-value="0" style="display:flex;gap:6px;justify-content:center;margin-bottom:4px">
          <i class="ph ph-star" style="font-size:2rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
          <i class="ph ph-star" style="font-size:2rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
          <i class="ph ph-star" style="font-size:2rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
          <i class="ph ph-star" style="font-size:2rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
          <i class="ph ph-star" style="font-size:2rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
        </div>
        <div style="font-size:1.2rem;font-weight:800;color:#0B2447"><span id="schoolOverallAvg">0.0</span>/5</div>
      </div>
      <div class="review-modal-grid" style="margin-bottom:24px">
        <?php foreach([
          ['key'=>'academics','label'=>'Teaching Quality'],
          ['key'=>'faculty','label'=>'Faculty & Staff'],
          ['key'=>'placements','label'=>'Safety & Discipline'],
          ['key'=>'infrastructure','label'=>'Infrastructure'],
          ['key'=>'campus_life','label'=>'Activities & Sports'],
          ['key'=>'food','label'=>'Library & Resources'],
        ] as $cat): ?>
        <div style="padding:14px;background:#f8fafc;border-radius:12px;border:1px solid rgba(15,23,42,0.06)">
          <div style="font-size:.8rem;font-weight:600;color:rgba(15,23,42,0.5);margin-bottom:8px"><?= $cat['label'] ?></div>
          <div id="school-stars-<?= $cat['key'] ?>" class="star-rating-group" data-category="<?= $cat['key'] ?>" data-value="0" style="display:flex;gap:4px">
            <i class="ph ph-star" style="font-size:1.3rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
            <i class="ph ph-star" style="font-size:1.3rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
            <i class="ph ph-star" style="font-size:1.3rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
            <i class="ph ph-star" style="font-size:1.3rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
            <i class="ph ph-star" style="font-size:1.3rem;cursor:pointer;color:rgba(15,23,42,0.15);transition:color .15s"></i>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div style="margin-bottom:16px">
        <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Review Title</label>
        <input type="text" id="schoolReviewTitle" placeholder="Summarize your experience" maxlength="200" style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.95rem;font-family:inherit;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'">
      </div>
      <div style="margin-bottom:16px">
        <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Your Review *</label>
        <textarea id="schoolReviewBody" rows="4" placeholder="Tell us about your experience at this school..." style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.95rem;font-family:inherit;resize:vertical;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'"></textarea>
      </div>
      <div class="review-modal-double" style="margin-bottom:16px">
        <div>
          <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Pros</label>
          <textarea id="schoolReviewPros" rows="2" placeholder="What did you like?" style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.9rem;font-family:inherit;resize:vertical;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'"></textarea>
        </div>
        <div>
          <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Cons</label>
          <textarea id="schoolReviewCons" rows="2" placeholder="What could be improved?" style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.9rem;font-family:inherit;resize:vertical;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'"></textarea>
        </div>
      </div>
      <div style="margin-bottom:24px">
        <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Batch Year</label>
        <select id="schoolReviewBatch" style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.95rem;font-family:inherit;background:#fff;box-sizing:border-box;outline:none">
          <option value="0">Select year</option>
          <?php for ($y = (int)date('Y'); $y >= 1990; $y--): ?>
          <option value="<?= $y ?>"><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <button type="button" id="schoolReviewSubmitBtn" onclick="submitSchoolReview()" style="width:100%;padding:14px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:8px">
        <i class="ph ph-check-circle"></i> Submit Review
      </button>
    </div>
  </div>
</div>

<script>
function scrollTabs(dir) {
  var tabs = document.getElementById('collegeTabs');
  if (!tabs) return;
  tabs.scrollBy({ left: dir * 200, behavior: 'smooth' });
}
function updateTabArrows() {
  var tabs = document.getElementById('collegeTabs');
  if (!tabs) return;
  var wrapper = tabs.closest('.college-tabs-wrapper');
  var left = document.querySelector('.tab-arrow-left');
  var right = document.querySelector('.tab-arrow-right');

  var canScroll = tabs.scrollWidth > tabs.clientWidth;
  if (wrapper) wrapper.classList.toggle('has-scroll', canScroll);

  if (left) {
    var atStart = !canScroll || tabs.scrollLeft <= 5;
    left.classList.toggle('hidden', window.innerWidth > 768 && atStart);
  }
  if (right) {
    var atEnd = !canScroll || tabs.scrollLeft + tabs.clientWidth >= tabs.scrollWidth - 5;
    right.classList.toggle('hidden', window.innerWidth > 768 && atEnd);
  }
  if (wrapper) wrapper.classList.toggle('scroll-end', canScroll && tabs.scrollLeft + tabs.clientWidth >= tabs.scrollWidth - 5);
}
document.addEventListener('DOMContentLoaded', function() {
  var tabs = document.getElementById('collegeTabs');
  if (tabs) {
    tabs.addEventListener('scroll', updateTabArrows);
    updateTabArrows();
  }
  window.addEventListener('resize', updateTabArrows);
});

function openSchoolReviewModal() {
  var m = document.getElementById('schoolReviewModal');
  if (m) m.style.display = 'flex';
}
function closeSchoolReviewModal() {
  var m = document.getElementById('schoolReviewModal');
  if (m) m.style.display = 'none';
}

document.querySelectorAll('#schoolReviewModal .star-rating-group').forEach(function(group) {
  var stars = group.querySelectorAll('i');
  stars.forEach(function(star, idx) {
    star.addEventListener('click', function() {
      group.dataset.value = idx + 1;
      stars.forEach(function(s, i) {
        s.className = (i <= idx) ? 'ph-fill ph-star' : 'ph ph-star';
        s.style.color = (i <= idx) ? '#f59e0b' : 'rgba(15,23,42,0.15)';
      });
      if (group.dataset.category === 'overall') {
        var avgEl = document.getElementById('schoolOverallAvg');
        if (avgEl) avgEl.textContent = (idx + 1).toFixed(1);
      }
    });
    star.addEventListener('mouseenter', function() {
      stars.forEach(function(s, i) {
        s.style.color = (i <= idx) ? '#f59e0b' : 'rgba(15,23,42,0.15)';
      });
    });
    star.addEventListener('mouseleave', function() {
      var val = parseInt(group.dataset.value) || 0;
      stars.forEach(function(s, i) {
        s.style.color = (i < val) ? '#f59e0b' : 'rgba(15,23,42,0.15)';
      });
    });
  });
});

function submitSchoolReview() {
  var btn = document.getElementById('schoolReviewSubmitBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner" style="animation:spin 1s linear infinite"></i> Submitting...'; }

  var overall = parseInt(document.getElementById('school-stars-overall').dataset.value) || 0;
  if (overall < 1) { alert('Please select an overall rating.'); if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ph ph-check-circle"></i> Submit Review'; } return; }

  var payload = {
    school_id: '<?= $sid ?>',
    overall_rating: overall,
    academics_rating: parseInt(document.getElementById('school-stars-academics').dataset.value) || 0,
    faculty_rating: parseInt(document.getElementById('school-stars-faculty').dataset.value) || 0,
    placements_rating: parseInt(document.getElementById('school-stars-placements').dataset.value) || 0,
    infrastructure_rating: parseInt(document.getElementById('school-stars-infrastructure').dataset.value) || 0,
    campus_life_rating: parseInt(document.getElementById('school-stars-campus_life').dataset.value) || 0,
    food_rating: parseInt(document.getElementById('school-stars-food').dataset.value) || 0,
    review_title: document.getElementById('schoolReviewTitle').value.trim(),
    review_body: document.getElementById('schoolReviewBody').value.trim(),
    pros: document.getElementById('schoolReviewPros').value.trim(),
    cons: document.getElementById('schoolReviewCons').value.trim(),
    batch_year: parseInt(document.getElementById('schoolReviewBatch').value) || 0
  };

  fetch('<?= $siteBase ?>/api/submit_school_review.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(payload)
  })
  .then(function(r) { return r.text().then(function(t) { try { return { ok: r.ok, data: JSON.parse(t) }; } catch(e) { return { ok: false, data: { error: 'Server error' } }; } }); })
  .then(function(result) {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ph ph-check-circle"></i> Submit Review'; }
    var data = result.data;
    if (data.ok) {
      closeSchoolReviewModal();
      var msg = document.getElementById('schoolReviewSuccess');
      if (msg) { msg.style.display = 'block'; setTimeout(function(){ msg.style.display = 'none'; }, 5000); }
    } else if (data.error === 'login_required') {
      window.location.href = '<?= $siteBase ?>/login.php?redirect=' + encodeURIComponent(window.location.href);
    } else {
      alert(data.message || data.error || 'Failed to submit review.');
    }
  })
  .catch(function(err) {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ph ph-check-circle"></i> Submit Review'; }
    alert('Network error. Please try again.');
  });
}
</script>

<!-- Apply Now Modal -->
<div id="applyModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)closeApplyModal()">
  <div style="background:#fff;border-radius:20px;max-width:520px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,0.2);position:relative">
    <div style="padding:28px 32px 0;border-bottom:1px solid rgba(15,23,42,0.06);display:flex;align-items:center;justify-content:space-between">
      <h3 style="margin:0;font-size:1.2rem;font-weight:800;color:#0B2447;display:flex;align-items:center;gap:10px"><i class="ph ph-paper-plane-tilt" style="font-size:1.3rem"></i> Apply to <?= htmlspecialchars($school['name']) ?></h3>
      <button onclick="closeApplyModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:rgba(15,23,42,0.3);padding:4px"><i class="ph ph-x"></i></button>
    </div>
    <div style="padding:24px 32px 32px">
      <div id="applySuccess" style="display:none;padding:16px 20px;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:12px;color:#059669;font-weight:600;font-size:.9rem;margin-bottom:20px">
        <i class="ph ph-check-circle"></i> <span id="applySuccessMsg">Application submitted successfully!</span>
      </div>
      <form id="applyForm" onsubmit="submitSchoolApplication(event)">
        <input type="hidden" name="school_id" value="<?= htmlspecialchars($sid) ?>">
        <div style="margin-bottom:14px">
          <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Full Name *</label>
          <input type="text" name="name" required placeholder="Enter your full name" style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.95rem;font-family:inherit;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div>
            <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Phone *</label>
            <input type="tel" name="phone" required placeholder="10-digit mobile" pattern="[0-9]{10}" maxlength="10" style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.95rem;font-family:inherit;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'">
          </div>
          <div>
            <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Email</label>
            <input type="email" name="email" placeholder="your@email.com" style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.95rem;font-family:inherit;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div>
            <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">City</label>
            <input type="text" name="city" placeholder="Your city" style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.95rem;font-family:inherit;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'">
          </div>
          <div>
            <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">State</label>
            <input type="text" name="state" placeholder="Your state" style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.95rem;font-family:inherit;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'">
          </div>
        </div>
        <div style="margin-bottom:20px">
          <label style="display:block;font-size:.85rem;font-weight:700;color:rgba(15,23,42,0.6);margin-bottom:6px">Message (Optional)</label>
          <textarea name="message" rows="3" placeholder="Any specific questions or requirements..." style="width:100%;padding:12px 16px;border:1.5px solid rgba(15,23,42,0.1);border-radius:10px;font-size:.95rem;font-family:inherit;resize:vertical;box-sizing:border-box;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='#19376D'" onblur="this.style.borderColor='rgba(15,23,42,0.1)'"></textarea>
        </div>
        <button type="submit" id="applySubmitBtn" style="width:100%;padding:14px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:8px">
          <i class="ph ph-paper-plane-tilt"></i> Submit Application
        </button>
        <p style="text-align:center;font-size:.78rem;color:rgba(15,23,42,0.4);margin-top:12px;margin-bottom:0">By submitting, you agree to be contacted by the school.</p>
      </form>
    </div>
  </div>
</div>

<script>
/* ── Save School ── */
function toggleSaveSchool() {
  var userId = '<?= $_SESSION["user_id"] ?? "" ?>';
  if (!userId) {
    window.location.href = '<?= $siteBase ?>/login.php?redirect=' + encodeURIComponent(window.location.href);
    return;
  }
  var btn = document.getElementById('saveSchoolBtn');
  var icon = document.getElementById('saveIcon');
  var label = document.getElementById('saveLabel');

  fetch('<?= $siteBase ?>/api/toggle_save_school.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify({ school_id: '<?= $sid ?>' })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.ok) {
      if (data.saved) {
        icon.className = 'ph-fill ph-heart-break';
        label.textContent = 'Saved';
      } else {
        icon.className = 'ph ph-heart';
        label.textContent = 'Save';
      }
    } else if (data.error === 'login_required') {
      window.location.href = '<?= $siteBase ?>/login.php?redirect=' + encodeURIComponent(window.location.href);
    } else {
      alert(data.message || data.error || 'Failed to save.');
    }
  })
  .catch(function() { alert('Network error. Please try again.'); });
}

/* ── Apply Now Modal ── */
function openApplyModal() {
  document.getElementById('applyModal').style.display = 'flex';
  document.getElementById('applySuccess').style.display = 'none';
  document.getElementById('applyForm').style.display = 'block';
}
function closeApplyModal() {
  document.getElementById('applyModal').style.display = 'none';
}

function submitSchoolApplication(e) {
  e.preventDefault();
  var btn = document.getElementById('applySubmitBtn');
  var form = document.getElementById('applyForm');
  btn.disabled = true;
  btn.innerHTML = '<i class="ph ph-spinner" style="animation:spin 1s linear infinite"></i> Submitting...';

  var fd = new FormData(form);
  var payload = {};
  fd.forEach(function(v, k) { payload[k] = v; });

  fetch('<?= $siteBase ?>/api/submit_school_application.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(payload)
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    btn.disabled = false;
    btn.innerHTML = '<i class="ph ph-paper-plane-tilt"></i> Submit Application';
    if (data.ok) {
      form.style.display = 'none';
      document.getElementById('applySuccess').style.display = 'block';
      document.getElementById('applySuccessMsg').textContent = data.message || 'Application submitted successfully!';
      setTimeout(closeApplyModal, 4000);
    } else {
      alert(data.message || data.error || 'Failed to submit application.');
    }
  })
  .catch(function() {
    btn.disabled = false;
    btn.innerHTML = '<i class="ph ph-paper-plane-tilt"></i> Submit Application';
    alert('Network error. Please try again.');
  });
}
</script>
</body>
</html>
