<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
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
$canonicalUrl = $siteBase . '/school/' . $slug;
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
    .overview-stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:24px}
    .overview-stat{text-align:center;padding:18px 12px;background:linear-gradient(135deg,#f8fafc,rgba(11,36,71,0.06));border-radius:14px;border:1px solid rgba(37,99,235,.1)}
    .overview-stat-val{font-size:1.4rem;font-weight:800;color:#19376D;font-family:'Plus Jakarta Sans',sans-serif}
    .overview-stat-lbl{font-size:.72rem;color:rgba(15,23,42,0.45);margin-top:4px;text-transform:uppercase;letter-spacing:.4px}
    .tab-empty-state{text-align:center;padding:48px 24px;color:rgba(15,23,42,0.4)}
    .tab-empty-state i{font-size:3rem;display:block;margin-bottom:12px}
    .tab-empty-state p{font-size:.92rem}
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
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tabs Nav -->
<div class="shiksha-tabs-nav college-tabs-sticky">
  <div class="container">
    <div class="college-tabs-wrapper">
      <div class="shiksha-tabs college-detail-tabs" id="collegeTabs">
        <?php foreach ($tabs as $key => $label): ?>
        <a href="<?= schoolUrl($slug, $key) ?>" class="<?= $tab === $key ? 'active' : '' ?>">
          <?= htmlspecialchars($label) ?>
          <?php if ($key === 'reviews' && $reviewCount > 0): ?><span style="background:rgba(11,36,71,0.06);color:#19376D;padding:1px 6px;border-radius:10px;font-size:.7rem;margin-left:3px"><?= $reviewCount ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
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
            <div class="college-prose"><?= nl2br(htmlspecialchars($school['about_text'])) ?></div>
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
            <div class="tab-empty-state"><i class="ph ph-star"></i><p>No reviews yet. Be the first to review!</p></div>
            <?php else: ?>
            <div class="college-reviews-list">
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
              <a href="<?= $siteBase ?>/school/<?= urlencode($slug) ?>/news/<?= urlencode($up['id']) ?>" style="text-decoration:none;color:inherit;display:block">
              <article class="college-review-card" style="cursor:pointer;transition:box-shadow .2s" onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
                <div style="display:flex;gap:16px;align-items:flex-start">
                  <?php if(!empty($up['image_url'])): ?>
                    <?php $imgSrc = str_starts_with($up['image_url'],'http') ? $up['image_url'] : $siteBase.'/'.$up['image_url']; ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>" style="width:120px;height:90px;object-fit:cover;border-radius:10px;flex-shrink:0">
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
    <div style="padding:28px 32px 0;border-bottom:1px solid rgba(15,23,42,0.06);display:flex;align-items:center;justify-content:space-between">
      <h3 style="margin:0;font-size:1.3rem;font-weight:800;color:#0B2447;display:flex;align-items:center;gap:10px"><i class="ph ph-star" style="font-size:1.4rem"></i> Write a Review</h3>
      <button onclick="closeSchoolReviewModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:rgba(15,23,42,0.3);padding:4px"><i class="ph ph-x"></i></button>
    </div>
    <div style="padding:24px 32px 32px">
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
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:24px">
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
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
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
  var el = document.getElementById('collegeTabs');
  if (el) el.scrollBy({ left: dir * 200, behavior: 'smooth' });
}

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
</body>
</html>
