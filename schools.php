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

$type   = $_GET['type'] ?? 'all';
$board  = $_GET['board'] ?? 'all';
$state  = isset($_GET['state']) ? (int)$_GET['state'] : 0;
$search = trim($_GET['q'] ?? '');
$sort   = $_GET['sort'] ?? 'featured';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$validTypes = ['all', 'govt', 'private', 'aided', 'unaided', 'international', 'boarding'];
if (!in_array($type, $validTypes, true)) $type = 'all';

$validBoards = ['all', 'CBSE', 'ICSE', 'State', 'IB', 'IGCSE', 'NIOS'];
if (!in_array($board, $validBoards, true)) $board = 'all';

$where = ["s.status = 'active'"];
$params = [];

if ($type !== 'all') {
    $where[] = 's.school_type = :type';
    $params['type'] = $type;
}
if ($board !== 'all') {
    $where[] = 's.board_affiliation = :board';
    $params['board'] = $board;
}
if ($state > 0) {
    $where[] = 's.state_id = :state';
    $params['state'] = $state;
}
if ($search !== '') {
    $where[] = '(s.name LIKE :q OR ci.name LIKE :q OR st.name LIKE :q)';
    $params['q'] = '%' . $search . '%';
}

$whereSql = implode(' AND ', $where);

$orderMap = [
    'featured' => 's.is_featured DESC, s.overall_rating_avg DESC, s.name ASC',
    'rating'   => 's.overall_rating_avg DESC, s.name ASC',
    'name'     => 's.name ASC',
    'newest'   => 's.created_at DESC',
];
$orderSql = $orderMap[$sort] ?? $orderMap['featured'];

try {
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM schools s
        LEFT JOIN cities ci ON s.city_id = ci.id
        LEFT JOIN states st ON s.state_id = st.id
        WHERE {$whereSql}
    ");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    error_log("Schools count query failed: " . $e->getMessage());
    $total = 0;
}

$schools = [];
try {
    $sql = "
        SELECT s.id, s.name, s.slug, s.school_type, s.ownership, s.board_affiliation, s.board_state_name,
               s.overall_rating_avg, s.total_reviews, s.established_year,
               s.is_verified, s.is_featured, s.total_students, s.campus_area_acres,
               st.name AS state_name, ci.name AS city_name,
               sm.logo_url, sm.cover_image_url
        FROM schools s
        LEFT JOIN states st ON s.state_id = st.id
        LEFT JOIN cities ci ON s.city_id = ci.id
        LEFT JOIN school_media sm ON sm.school_id = s.id AND sm.image_type IS NULL
        WHERE {$whereSql}
        ORDER BY {$orderSql}
        LIMIT {$perPage} OFFSET {$offset}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Schools query failed: " . $e->getMessage());
}

$states = cAll($pdo, "SELECT st.id, st.name, COUNT(s.id) AS cnt FROM states st LEFT JOIN schools s ON s.state_id = st.id AND s.status='active' GROUP BY st.id, st.name ORDER BY cnt DESC, st.name ASC LIMIT 30");
$totalPages = max(1, (int)ceil($total / $perPage));

$stats = [
    'total'   => cCol($pdo, "SELECT COUNT(*) FROM schools WHERE status='active'"),
    'govt'    => cCol($pdo, "SELECT COUNT(*) FROM schools WHERE status='active' AND school_type='govt'"),
    'private' => cCol($pdo, "SELECT COUNT(*) FROM schools WHERE status='active' AND school_type='private'"),
    'states'  => cCol($pdo, "SELECT COUNT(DISTINCT state_id) FROM schools WHERE status='active'"),
];

$siteBase = getBaseUrl();
$canonicalUrl = $siteBase . '/schools';
$queryParams = [];
if ($type !== 'all') $queryParams['type'] = $type;
if ($board !== 'all') $queryParams['board'] = $board;
if ($state > 0) $queryParams['state'] = $state;
if (!empty($search)) $queryParams['q'] = $search;
if ($sort !== 'featured') $queryParams['sort'] = $sort;
if (!empty($queryParams)) $canonicalUrl .= '?' . http_build_query($queryParams);

// Dynamic SEO labels based on filters
$typeLabels = [
    'govt' => 'Government', 'private' => 'Private', 'aided' => 'Aided',
    'unaided' => 'Unaided', 'international' => 'International', 'boarding' => 'Boarding',
];
$boardLabels = [
    'CBSE' => 'CBSE', 'ICSE' => 'ICSE', 'State' => 'State Board',
    'IB' => 'IB', 'IGCSE' => 'IGCSE', 'NIOS' => 'NIOS',
];
$stateName = ($state > 0 && isset(array_column($states, 'name', 'id')[$state]))
    ? array_column($states, 'name', 'id')[$state] : '';

$filterType  = $type !== 'all' ? ($typeLabels[$type] ?? ucfirst($type)) : '';
$filterBoard = $board !== 'all' ? ($boardLabels[$board] ?? $board) : '';
$filterState = $stateName;

// Build dynamic title
$parts = [];
if ($filterBoard) $parts[] = $filterBoard;
if ($filterType) $parts[] = $filterType;
$parts[] = 'Schools';
if ($filterState) $parts[] = 'in ' . $filterState;
else $parts[] = 'in India';
$pageTitle = implode(' ', $parts) . ' ' . date('Y') . ' — Fees, Admissions & Rankings';

// Build dynamic meta description
$descParts = [];
$descParts[] = 'Explore ' . number_format($total) . '+ ' . strtolower(implode(' ', $parts));
if (!$filterBoard && !$filterType) $descParts[] = '— compare CBSE, ICSE, IB board schools';
$descParts[] = '. Check fees, admissions, ratings' . ($filterState ? ' in ' . $filterState : ' across India');
$metaDesc = implode(' ', $descParts) . '. AdmissionSeason — your trusted school directory.';

// Dynamic keywords
$kwBase = ['schools in India ' . date('Y'), 'school admissions', 'school fees', 'best schools India', 'school ratings'];
if ($filterBoard) $kwBase[] = strtolower($filterBoard) . ' schools';
if ($filterType) $kwBase[] = strtolower($filterType) . ' schools';
if ($filterState) { $kwBase[] = 'schools in ' . $filterState; $kwBase[] = strtolower($filterState) . ' schools'; }
$metaKeywords = implode(', ', array_unique($kwBase));

$ogTitle = $pageTitle . ' | AdmissionSeason';
$ogDesc  = $metaDesc;

// Dynamic GEO meta based on state filter
$geoMeta = '';
$geoJsonLd = null;
if ($filterState) {
    $geoData = getGeoLocations();
    $geoKey = strtolower($filterState);
    if (isset($geoData[$geoKey])) {
        $g = $geoData[$geoKey];
        $geoMeta = '<meta name="geo.region" content="' . $g['region'] . '"><meta name="geo.placename" content="' . htmlspecialchars($g['placename']) . '"><meta name="geo.position" content="' . $g['lat'] . ';' . $g['lng'] . '"><meta name="ICBM" content="' . $g['lat'] . ', ' . $g['lng'] . '">';
        $geoJsonLd = ['@type' => 'Place', 'name' => $g['placename'], 'geo' => ['@type' => 'GeoCoordinates', 'latitude' => (float)$g['lat'], 'longitude' => (float)$g['lng']]];
    }
}
if (!$geoMeta) {
    $geoMeta = '<meta name="geo.region" content="IN"><meta name="geo.placename" content="India"><meta name="geo.position" content="20.5937;78.9629"><meta name="ICBM" content="20.5937, 78.9629">';
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
  <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
  <meta name="googlebot" content="index, follow">
  <link rel="canonical" href="<?= $canonicalUrl ?>">
  <meta name="author" content="AdmissionSeason">
  <meta name="revisit-after" content="3 days">

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= $canonicalUrl ?>">
  <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($ogDesc) ?>">
  <meta property="og:image" content="<?= $siteBase ?>/assets/img/logo.png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:site_name" content="AdmissionSeason">
  <meta property="og:locale" content="en_IN">
  <?php if ($filterState): ?>
  <meta property="og:locale:alternate" content="en_IN">
  <?php endif; ?>

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= $canonicalUrl ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($ogDesc) ?>">
  <meta name="twitter:image" content="<?= $siteBase ?>/assets/img/logo.png">

  <!-- Dynamic GEO Meta Tags -->
  <?= $geoMeta ?>
  <meta name="language" content="English">
  <link rel="alternate" hreflang="en-in" href="<?= $canonicalUrl ?>">
  <?php if ($filterState): ?>
  <link rel="alternate" hreflang="en-in" href="<?= $siteBase ?>/schools?state=<?= $state ?>" title="Schools in <?= htmlspecialchars($filterState) ?>">
  <?php endif; ?>

  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'description' => $metaDesc,
    'url' => $canonicalUrl,
    'inLanguage' => 'en-IN',
    'publisher' => [
      '@type' => 'Organization',
      'name' => 'AdmissionSeason',
      'url' => $siteBase,
      'logo' => ['@type' => 'ImageObject', 'url' => "$siteBase/assets/img/logo.png", 'width' => 600, 'height' => 60],
      'sameAs' => [
        'https://www.facebook.com/admissionseason',
        'https://www.twitter.com/admissionseason',
        'https://www.instagram.com/admissionseason',
      ],
    ],
    'mainEntity' => [
      '@type' => 'ItemList',
      'name' => ($filterState ? "Schools in $filterState" : 'Schools in India') . ($filterBoard ? " — $filterBoard Board" : '') . ($filterType ? " — $filterType" : ''),
      'numberOfItems' => $total,
      'itemListElement' => array_values(array_filter(array_map(function($s, $i) use ($siteBase) {
        if (empty($s['slug'])) return null;
        return [
          '@type' => 'ListItem',
          'position' => $i + 1,
          'item' => [
            '@type' => 'SchoolOrAcademicOrganization',
            'name' => $s['name'],
            'url' => schoolUrl($s['slug']),
            'image' => cImg($s['cover_image_url']),
            'address' => [
              '@type' => 'PostalAddress',
              'addressLocality' => $s['city_name'] ?? '',
              'addressRegion' => $s['state_name'] ?? '',
              'addressCountry' => 'IN',
            ],
            'geo' => ($s['latitude'] && $s['longitude']) ? [
              '@type' => 'GeoCoordinates',
              'latitude' => (float)$s['latitude'],
              'longitude' => (float)$s['longitude'],
            ] : null,
            'aggregateRating' => ($s['overall_rating_avg'] > 0) ? [
              '@type' => 'AggregateRating',
              'ratingValue' => round((float)$s['overall_rating_avg'], 1),
              'bestRating' => 5,
              'reviewCount' => (int)($s['total_reviews'] ?? 0),
            ] : null,
            'foundingDate' => !empty($s['established_year']) ? (string)$s['established_year'] : null,
          ],
        ];
      }, array_slice($schools, 0, 20), range(0, min(19, count($schools) - 1))))),
    ],
    ($geoJsonLd ? ['contentLocation' => $geoJsonLd] : []),
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => "$siteBase/"],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'Schools', 'item' => $canonicalUrl],
      ...($filterState ? [['@type' => 'ListItem', 'position' => 3, 'name' => $filterState, 'item' => $canonicalUrl]] : []),
      ...($filterBoard ? [['@type' => 'ListItem', 'position' => ($filterState ? 4 : 3), 'name' => $filterBoard . ' Schools', 'item' => $canonicalUrl]] : []),
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'AdmissionSeason',
    'url' => $siteBase,
    'potentialAction' => [
      '@type' => 'SearchAction',
      'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $siteBase . '/schools?q={search_term_string}'],
      'query-input' => 'required name=search_term_string',
    ],
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/college-pages.css?v=<?= time() ?>">
  <style>
    /* ── Stats Bar ──────────────────────────────────────────────────── */
    .cl-stats-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:24px;position:relative;z-index:1}
    .cl-stat{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:16px 20px;backdrop-filter:blur(10px);text-align:center}
    .cl-stat-val{font-size:1.5rem;font-weight:800;color:#fff;font-family:'Plus Jakarta Sans',sans-serif}
    .cl-stat-lbl{font-size:.75rem;color:rgba(255,255,255,.7);margin-top:2px;text-transform:uppercase;letter-spacing:.5px}

    /* ── Sort Bar ───────────────────────────────────────────────────── */
    .sort-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:14px 24px;background:#f8fafc;border-bottom:1px solid rgba(15,23,42,0.08);font-size:.85rem}
    .sort-bar label{color:rgba(15,23,42,0.45);font-weight:500}
    .sort-bar select{padding:6px 12px;border:1.5px solid rgba(15,23,42,0.08);border-radius:8px;font-size:.83rem;background:#fff;cursor:pointer;font-family:inherit}
    .sort-result-count{margin-left:auto;color:rgba(15,23,42,0.4);font-size:.82rem}

    /* ── Featured Badge ─────────────────────────────────────────────── */
    .clc-featured-badge{position:absolute;top:10px;right:10px;background:linear-gradient(135deg,#19376D,#0F172A);color:#fff;font-size:.65rem;font-weight:700;padding:3px 9px;border-radius:6px;text-transform:uppercase;letter-spacing:.5px;z-index:2}

    /* ── Filter Toggle (mobile) ─────────────────────────────────────── */
    .col-filter-toggle{display:none;align-items:center;gap:6px;padding:10px 20px;border-radius:12px;border:1.5px solid rgba(15,23,42,.1);background:#fff;font-size:.85rem;font-weight:700;color:#0B2447;cursor:pointer;transition:all .2s}
    .col-filter-toggle:hover{border-color:#2563eb;color:#2563eb}
    .col-filter-close{display:none;position:absolute;top:12px;right:12px;width:32px;height:32px;border-radius:8px;background:rgba(15,23,42,.06);border:none;cursor:pointer;align-items:center;justify-content:center;font-size:1rem;color:#0f172a;z-index:1}

    /* ── Board filter pills ─────────────────────────────────────────── */
    .board-filter-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .board-pill{padding:6px 16px;border-radius:100px;font-size:.8rem;font-weight:600;text-decoration:none;border:1.5px solid rgba(15,23,42,0.08);color:rgba(15,23,42,0.45);background:transparent;transition:all .2s;white-space:nowrap}
    .board-pill:hover{border-color:#19376D;color:#19376D}
    .board-pill.active{color:#fff;background:linear-gradient(135deg,#19376D,#19376D);border-color:transparent;box-shadow:0 4px 12px rgba(37,99,235,.3)}

    /* ── SEO Content Section ────────────────────────────────────────── */
    .seo-content-section{background:#fff;border-radius:14px;border:1px solid rgba(15,23,42,0.08);padding:28px;margin-top:28px}
    .seo-content-section h2{font-size:1.15rem;font-weight:700;color:#0F172A;margin-bottom:14px;display:flex;align-items:center;gap:8px}
    .seo-content-section h2::before{content:'';width:4px;height:20px;border-radius:2px;background:linear-gradient(180deg,#0B2447,#19376D);flex-shrink:0}
    .seo-content-section p{color:rgba(15,23,42,0.6);font-size:.9rem;line-height:1.75;margin-bottom:14px}
    .seo-content-section p:last-child{margin-bottom:0}
    .seo-content-section a{color:#19376D;font-weight:600;text-decoration:none}
    .seo-content-section a:hover{text-decoration:underline}
    .seo-faq-toggle{cursor:pointer;user-select:none}

    @keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}

    /* ══════════════════════════════════════════════════════════════════
       RESPONSIVE — Tablet (≤ 1024px)
       ══════════════════════════════════════════════════════════════════ */
    @media(max-width:1024px){
      .cl-stats-bar{grid-template-columns:repeat(2,1fr);gap:12px}
      .sort-result-count{display:none}
      .col-filter-toggle{display:flex}
      .shiksha-sidebar{display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:200;background:rgba(0,0,0,.4);padding:0}
      .shiksha-sidebar.open{display:flex;align-items:flex-end}
      .shiksha-sidebar .shiksha-widget{position:static;border-radius:16px 16px 0 0;max-height:80vh;overflow-y:auto;width:100%;box-shadow:0 -4px 24px rgba(0,0,0,.15);animation:slideUp .3s ease}
      .shiksha-sidebar .shiksha-widget-wrapper{display:flex;flex-direction:column;gap:0;background:#fff;padding:20px;border-radius:16px 16px 0 0}
      .col-filter-close{display:flex}
      .board-filter-row{overflow-x:auto;flex-wrap:nowrap;scrollbar-width:none;-webkit-overflow-scrolling:touch;padding-bottom:4px}
      .board-filter-row::-webkit-scrollbar{display:none}
    }

    /* ══════════════════════════════════════════════════════════════════
       RESPONSIVE — Mobile landscape (≤ 768px)
       ══════════════════════════════════════════════════════════════════ */
    @media(max-width:768px){
      .cl-stats-bar{grid-template-columns:repeat(2,1fr);gap:10px;margin-top:16px}
      .cl-stat{padding:12px 14px}
      .cl-stat-val{font-size:1.2rem}
      .cl-stat-lbl{font-size:.68rem}

      .shiksha-tabs{gap:2px;padding:4px 0}
      .shiksha-tabs a{padding:8px 12px;font-size:.78rem}

      .board-filter-row{gap:6px}
      .board-pill{padding:5px 12px;font-size:.75rem}

      .college-filter-bar{padding:12px 16px}
      .college-search-form{flex-direction:column;gap:8px}
      .college-search-form input[type="text"]{min-width:0;width:100%}
      .college-search-form select{width:100%;min-width:0}
      .college-search-form button{width:100%;justify-content:center}

      .college-list-card{flex-direction:column;gap:12px;padding:16px}
      .clc-img{width:100%;height:160px;border-radius:10px}
      .clc-body h3{font-size:1rem}
      .clc-meta{gap:8px;font-size:.78rem}
      .clc-chips{gap:4px}
      .clc-chip{font-size:.65rem;padding:2px 7px}
      .clc-stats{flex-wrap:wrap}
      .clc-stats div{min-width:calc(50% - 1px);font-size:.85rem;padding:8px 6px}
      .clc-stats strong{font-size:.9rem}

      .sort-bar{flex-direction:column;align-items:stretch;gap:8px;padding:12px 16px}
      .sort-result-count{margin-left:0;font-size:.78rem}

      .college-pager{padding:16px;gap:4px}
      .pager-link{padding:6px 12px;font-size:.8rem}

      .shiksha-widget{padding:16px}
      .shiksha-widget-title{font-size:.82rem;margin-bottom:10px}
      .shiksha-widget-list li a{padding:8px 8px;font-size:.82rem}

      .col-filter-toggle{
        display:flex;position:fixed;bottom:20px;right:20px;z-index:150;
        box-shadow:0 4px 20px rgba(0,0,0,.2);border-radius:50px;padding:12px 20px;
      }
      .clc-featured-badge{font-size:.6rem;padding:2px 7px;top:8px;right:8px}

      .seo-content-section{padding:18px;margin-top:20px}
      .seo-content-section h2{font-size:1rem;margin-bottom:10px}
      .seo-content-section p{font-size:.84rem;line-height:1.65}
    }

    /* ══════════════════════════════════════════════════════════════════
       RESPONSIVE — Mobile portrait (≤ 480px)
       ══════════════════════════════════════════════════════════════════ */
    @media(max-width:480px){
      .cl-stats-bar{grid-template-columns:1fr 1fr;gap:8px}
      .cl-stat{padding:10px 12px}
      .cl-stat-val{font-size:1.1rem}

      .shiksha-tabs a{padding:7px 10px;font-size:.72rem}
      .clc-featured-badge{font-size:.58rem;padding:2px 7px;top:8px;right:8px}
      .col-filter-toggle{padding:10px 16px;font-size:.8rem}

      .board-filter-row{gap:5px}
      .board-pill{padding:4px 10px;font-size:.7rem}

      .college-search-form{gap:6px}
      .college-search-form input[type="text"]{padding:9px 12px;font-size:.85rem}
      .college-search-form select{padding:9px 12px;font-size:.82rem}
      .college-search-form button{padding:9px 16px;font-size:.82rem}

      .shiksha-widget-wrapper{padding:16px 12px}
      .shiksha-widget{padding:14px}

      .container{padding:0 16px}
      .shiksha-main-wrapper{padding:12px 0 24px}

      .seo-content-section{padding:14px;margin-top:16px;border-radius:10px}
      .seo-content-section h2{font-size:.92rem;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid rgba(15,23,42,0.08)}
      .seo-content-section p{font-size:.8rem;line-height:1.6}
    }
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Hero Header -->
<div class="shiksha-header">
  <div class="container">
    <div class="shiksha-breadcrumb">
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/index.php">Home</a>
      <i class="ph ph-caret-right"></i>
      <span>Schools</span>
    </div>
    <h1 class="shiksha-title">Find the Best Schools in India</h1>
    <p class="college-list-sub"><?= number_format($total) ?> <?= strtolower(implode(' ', $parts)) ?> found<?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?></p>
    <div class="cl-stats-bar">
      <div class="cl-stat">
        <div class="cl-stat-val"><?= number_format($stats['total']) ?>+</div>
        <div class="cl-stat-lbl">Total Schools</div>
      </div>
      <div class="cl-stat">
        <div class="cl-stat-val"><?= number_format($stats['govt']) ?>+</div>
        <div class="cl-stat-lbl">Government</div>
      </div>
      <div class="cl-stat">
        <div class="cl-stat-val"><?= number_format($stats['private']) ?>+</div>
        <div class="cl-stat-lbl">Private</div>
      </div>
      <div class="cl-stat">
        <div class="cl-stat-val"><?= number_format($stats['states']) ?>+</div>
        <div class="cl-stat-lbl">States Covered</div>
      </div>
    </div>
  </div>
</div>

<!-- Type Filter Tabs -->
<div class="shiksha-tabs-nav">
  <div class="container">
    <div class="shiksha-tabs">
      <?php foreach (['all' => 'All Schools', 'govt' => '🏛️ Government', 'private' => '🏢 Private', 'aided' => '🤝 Aided', 'international' => '🌍 International', 'boarding' => '🏫 Boarding'] as $k => $label): ?>
      <a href="<?= schoolsUrl(array_filter(['type' => $k !== 'all' ? $k : null, 'board' => $board !== 'all' ? $board : null, 'state' => $state ?: null, 'q' => $search ?: null, 'sort' => $sort !== 'featured' ? $sort : null])) ?>"
         class="<?= $type === $k ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Board Filter Tabs -->
<div style="background:#fff;border-bottom:1px solid rgba(15,23,42,0.08);">
  <div class="container" style="padding-top:12px;padding-bottom:12px;">
    <div class="board-filter-row">
      <span style="font-size:.82rem;font-weight:600;color:rgba(15,23,42,0.45);margin-right:4px;flex-shrink:0;">Board:</span>
      <?php foreach (['all' => 'All', 'CBSE' => 'CBSE', 'ICSE' => 'ICSE', 'State' => 'State Board', 'IB' => 'IB', 'IGCSE' => 'IGCSE'] as $bk => $bl): ?>
      <a href="<?= schoolsUrl(array_filter(['type' => $type !== 'all' ? $type : null, 'board' => $bk !== 'all' ? $bk : null, 'state' => $state ?: null, 'q' => $search ?: null, 'sort' => $sort !== 'featured' ? $sort : null])) ?>"
         class="board-pill <?= $board === $bk ? 'active' : '' ?>"><?= htmlspecialchars($bl) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="container shiksha-main-wrapper">
  <div class="shiksha-layout">

    <main class="shiksha-content college-list-main">

      <!-- Search + Filter Bar -->
      <div class="college-filter-bar">
        <form method="get" class="college-search-form">
          <?php if ($type !== 'all'): ?><input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>"><?php endif; ?>
          <?php if ($board !== 'all'): ?><input type="hidden" name="board" value="<?= htmlspecialchars($board) ?>"><?php endif; ?>
          <?php if ($sort !== 'featured'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search school, city or state…">
          <select name="state">
            <option value="">All States</option>
            <?php foreach ($states as $st): ?>
            <option value="<?= (int)$st['id'] ?>" <?= $state === (int)$st['id'] ? 'selected' : '' ?>><?= htmlspecialchars($st['name']) ?> (<?= (int)$st['cnt'] ?>)</option>
            <?php endforeach; ?>
          </select>
          <button type="submit"><i class="ph ph-magnifying-glass"></i> Search</button>
        </form>
      </div>

      <!-- Sort bar -->
      <div class="sort-bar">
        <label>Sort by:</label>
        <select onchange="window.location=this.value">
          <?php foreach (['featured'=>'Featured First','rating'=>'Top Rated','name'=>'Name A-Z','newest'=>'Newest'] as $sk=>$sl): ?>
          <option value="<?= schoolsUrl(array_filter(['type'=>$type!=='all'?$type:null,'board'=>$board!=='all'?$board:null,'state'=>$state?:null,'q'=>$search?:null,'sort'=>$sk,'page'=>null])) ?>" <?= $sort===$sk?'selected':'' ?>><?= $sl ?></option>
          <?php endforeach; ?>
        </select>
        <span class="sort-result-count">Showing <?= (($page-1)*$perPage)+1 ?>–<?= min($page*$perPage,$total) ?> of <?= number_format($total) ?> schools</span>
      </div>

      <!-- School cards -->
      <?php if (empty($schools)): ?>
        <div class="shiksha-empty">
          <i class="ph ph-graduation-cap" style="font-size:3rem;color:rgba(15,23,42,0.08);display:block;margin-bottom:12px"></i>
          <p>No schools found. Try adjusting your filters.</p>
        </div>
      <?php else: ?>
        <div class="college-grid-list">
          <?php foreach ($schools as $sch):
            $year = $sch['established_year'] ?? '';
            $rating = (float)($sch['overall_rating_avg'] ?? 0);
          ?>
          <a href="<?= schoolUrl($sch['slug']) ?>" class="college-list-card">
            <?php if (!empty($sch['is_featured'])): ?>
            <span class="clc-featured-badge" style="position:absolute;top:12px;right:12px;z-index:2">Featured</span>
            <?php endif; ?>

            <div class="clc-img">
              <img src="<?= cImg($sch['cover_image_url']) ?>" alt="<?= htmlspecialchars($sch['name']) ?>" loading="lazy">
              <div class="clc-img-badges">
                <?php if ($sch['board_affiliation']): ?><span class="clc-badge"><?= htmlspecialchars($sch['board_affiliation'] === 'State' && !empty($sch['board_state_name']) ? $sch['board_state_name'] : $sch['board_affiliation']) ?></span><?php endif; ?>
                <?php if (!empty($sch['is_verified'])): ?><span class="clc-badge clc-badge-verified"><i class="ph-fill ph-seal-check"></i> Verified</span><?php endif; ?>
              </div>
            </div>

            <div class="clc-body">
              <div class="clc-top">
                <?php if ($sch['logo_url']): ?><img src="<?= cImg($sch['logo_url']) ?>" class="clc-logo" alt=""><?php endif; ?>
                <div style="flex:1;min-width:0">
                  <h3><?= htmlspecialchars($sch['name']) ?></h3>
                  <div class="clc-meta">
                    <?php if ($sch['city_name'] || $sch['state_name']): ?>
                    <span><i class="ph ph-map-pin"></i><?= htmlspecialchars(trim(($sch['city_name'] ?? '') . ($sch['city_name'] && $sch['state_name'] ? ', ' : '') . ($sch['state_name'] ?? ''))) ?></span>
                    <?php endif; ?>
                    <?php if ($year): ?><span><i class="ph ph-calendar"></i>Est. <?= htmlspecialchars((string)$year) ?></span><?php endif; ?>
                    <?php if (!empty($sch['total_students'])): ?><span><i class="ph ph-users"></i><?= number_format((int)$sch['total_students']) ?> Students</span><?php endif; ?>
                  </div>
                  <div class="clc-chips">
                    <span class="clc-chip"><?= htmlspecialchars(schoolTypeLabel($sch['school_type'])) ?></span>
                    <?php if ($sch['board_affiliation']): ?><span class="clc-chip"><?= htmlspecialchars($sch['board_affiliation'] === 'State' && !empty($sch['board_state_name']) ? $sch['board_state_name'] : schoolBoardLabel($sch['board_affiliation'])) ?></span><?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="clc-stats">
                <div>
                  <strong><?= $rating > 0 ? number_format($rating, 1) . '/5 ★' : '—' ?></strong>
                  <span>Rating</span>
                </div>
                <div>
                  <strong><?= !empty($sch['total_students']) ? number_format((int)$sch['total_students']) : '—' ?></strong>
                  <span>Students</span>
                </div>
                <?php if (!empty($sch['campus_area_acres'])): ?>
                <div>
                  <strong><?= (float)$sch['campus_area_acres'] ?> acres</strong>
                  <span>Campus</span>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="college-pager">
          <?php if ($page > 1): ?>
          <a href="<?= schoolsUrl(array_filter(['type'=>$type!=='all'?$type:null,'board'=>$board!=='all'?$board:null,'state'=>$state?:null,'q'=>$search?:null,'sort'=>$sort!=='featured'?$sort:null,'page'=>$page-1])) ?>" class="pager-link">&laquo; Prev</a>
          <?php endif; ?>
          <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+4); $p++): ?>
          <a href="<?= schoolsUrl(array_filter(['type'=>$type!=='all'?$type:null,'board'=>$board!=='all'?$board:null,'state'=>$state?:null,'q'=>$search?:null,'sort'=>$sort!=='featured'?$sort:null,'page'=>$p])) ?>" class="pager-link<?= $p===$page?' active':'' ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
          <a href="<?= schoolsUrl(array_filter(['type'=>$type!=='all'?$type:null,'board'=>$board!=='all'?$board:null,'state'=>$state?:null,'q'=>$search?:null,'sort'=>$sort!=='featured'?$sort:null,'page'=>$page+1])) ?>" class="pager-link">Next &raquo;</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </main>

    <button class="col-filter-toggle" onclick="document.querySelector('.shiksha-sidebar').classList.toggle('open')"><i class="ph ph-funnel"></i> Filters</button>

    <!-- Sidebar -->
    <aside class="shiksha-sidebar">
      <div class="shiksha-widget-wrapper">
        <button class="col-filter-close" onclick="this.closest('.shiksha-sidebar').classList.remove('open')"><i class="ph ph-x"></i></button>
      <div class="shiksha-widget">
        <h4 class="shiksha-widget-title">Browse by State</h4>
        <ul class="shiksha-widget-list">
          <?php foreach (array_slice($states, 0, 14) as $st): ?>
          <li><a href="<?= schoolsUrl(['state' => $st['id']]) ?>">
            <?= htmlspecialchars($st['name']) ?>
            <span><?= (int)$st['cnt'] ?></span>
          </a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="shiksha-widget">
        <h4 class="shiksha-widget-title">Quick Filters</h4>
        <ul class="shiksha-widget-list">
          <li><a href="<?= schoolsUrl(['type' => 'govt']) ?>"><span style="margin-right:auto">Government Schools</span></a></li>
          <li><a href="<?= schoolsUrl(['type' => 'private']) ?>"><span style="margin-right:auto">Private Schools</span></a></li>
          <li><a href="<?= schoolsUrl(['type' => 'international']) ?>"><span style="margin-right:auto">International Schools</span></a></li>
          <li><a href="<?= schoolsUrl(['board' => 'CBSE']) ?>"><span style="margin-right:auto">CBSE Schools</span></a></li>
          <li><a href="<?= schoolsUrl(['board' => 'ICSE']) ?>"><span style="margin-right:auto">ICSE Schools</span></a></li>
          <li><a href="<?= schoolsUrl(['sort' => 'rating']) ?>"><span style="margin-right:auto">Top Rated Schools</span></a></li>
        </ul>
      </div>

      <div class="shiksha-widget" style="background:linear-gradient(135deg,rgba(11,36,71,0.06),rgba(11,36,71,0.04));border-color:rgba(79,70,229,.2)">
        <h4 class="shiksha-widget-title" style="color:#19376D">Get Admission Alerts</h4>
        <p style="font-size:.85rem;color:rgba(15,23,42,0.65);margin-bottom:12px">Stay updated with school admissions, exam dates & results.</p>
        <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/signup.php" style="display:block;text-align:center;padding:10px;background:linear-gradient(135deg,#19376D,#0B2447);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:.87rem">
          Create Free Account
        </a>
      </div>
      </div>
    </aside>

  </div>
</div>

<!-- Dynamic SEO Content Section -->
<?php
$seoH2 = 'Schools in India — Find the Best School for Your Child';
$seoText = 'AdmissionSeason helps parents and students discover the <strong>best schools in India</strong>. Whether you are looking for a <strong>CBSE school</strong>, <strong>ICSE school</strong>, <strong>IB school</strong>, or a <strong>state board school</strong>, our comprehensive directory covers government, private, aided, international, and boarding schools across all states and union territories.';
if ($filterState) {
    $seoH2 = htmlspecialchars($filterState) . ' — Top Schools, Fees & Admissions';
    $seoText = "Looking for the best schools in <strong>" . htmlspecialchars($filterState) . "</strong>? AdmissionSeason lists " . number_format($total) . "+ verified schools in " . htmlspecialchars($filterState) . " — compare fees, admission processes, board affiliations, ratings, and campus facilities. Find government, private, CBSE, ICSE, and international schools in " . htmlspecialchars($filterState) . ".";
} elseif ($filterBoard) {
    $seoH2 = htmlspecialchars($filterBoard) . ' Schools in India — Fees, Admissions & Rankings';
    $seoText = "Discover " . number_format($total) . "+ <strong>" . htmlspecialchars($filterBoard) . " schools</strong> across India. AdmissionSeason provides detailed information on " . htmlspecialchars($filterBoard) . " board schools including fees, admission deadlines, student reviews, and school ratings. Find the right " . htmlspecialchars($filterBoard) . " school near you.";
} elseif ($filterType) {
    $seoH2 = ucfirst($filterType) . ' Schools in India — Complete Directory';
    $seoText = "Browse " . number_format($total) . "+ <strong>" . strtolower($filterType) . " schools in India</strong>. AdmissionSeason lists verified " . strtolower($filterType) . " schools with details on fees, admissions, board affiliations, infrastructure, and reviews. Compare and shortlist the best " . strtolower($filterType) . " schools near you.";
}
$faqItems = [
    ['q' => 'How many schools are listed on AdmissionSeason?', 'a' => 'AdmissionSeason currently lists ' . number_format($stats['total']) . '+ verified schools across India, covering government, private, aided, international, and boarding schools.'],
    ['q' => 'Can I filter schools by board?', 'a' => 'Yes. You can filter schools by CBSE, ICSE, State Board, IB, IGCSE, and NIOS boards. Use the board filter tabs above to narrow your search.'],
    ['q' => 'Are the school fees listed on the website accurate?', 'a' => 'We strive to keep fee information up to date. However, fees may vary and should be confirmed directly with the school.'],
    ['q' => 'How do I apply to a school listed on AdmissionSeason?', 'a' => 'Click on any school listing to view detailed admission information, including the admission process, important dates, and contact details. You can reach out to the school directly through their contact page.'],
];
if ($filterState) {
    $faqItems[] = ['q' => "What are the top schools in {$filterState}?", 'a' => "AdmissionSeason lists " . number_format($total) . "+ verified schools in {$filterState}. Use our filters to find the best schools by type, board, and ratings."];
    $faqItems[] = ['q' => "How many CBSE schools are there in {$filterState}?", 'a' => "You can filter by CBSE board on this page to see all CBSE-affiliated schools in {$filterState}. Our directory is regularly updated with new schools."];
}
?>
<div class="container">
  <div class="seo-content-section" itemscope itemtype="https://schema.org/FAQPage">
    <h2><?= $seoH2 ?></h2>
    <p><?= $seoText ?></p>
    <p>Explore schools by <a href="<?= schoolsUrl(['type' => 'govt']) ?>">government schools</a>, <a href="<?= schoolsUrl(['type' => 'private']) ?>">private schools</a>, <a href="<?= schoolsUrl(['type' => 'international']) ?>">international schools</a>, or <a href="<?= schoolsUrl(['type' => 'boarding']) ?>">boarding schools</a>. Filter by <a href="<?= schoolsUrl(['board' => 'CBSE']) ?>">CBSE</a>, <a href="<?= schoolsUrl(['board' => 'ICSE']) ?>">ICSE</a>, or <a href="<?= schoolsUrl(['board' => 'IB']) ?>">IB</a> boards to find the perfect match.</p>

    <h2 style="margin-top:24px;">Frequently Asked Questions</h2>
    <div class="college-faq-list">
      <?php foreach ($faqItems as $faq): ?>
      <details class="college-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
        <summary class="seo-faq-toggle" itemprop="name"><?= htmlspecialchars($faq['q']) ?></summary>
        <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
          <p itemprop="text"><?= $faq['a'] ?></p>
        </div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
document.querySelector('.shiksha-sidebar')?.addEventListener('click', function(e) {
  if (e.target === this) this.classList.remove('open');
});
</script>
</body>
</html>
