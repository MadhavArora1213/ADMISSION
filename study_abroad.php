<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/panel_cms_2847/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch all study abroad data
try {
    $universities = $pdo->query("SELECT * FROM foreign_universities ORDER BY qs_rank ASC")->fetchAll(PDO::FETCH_ASSOC);
    $visas = $pdo->query("SELECT * FROM visa_guides ORDER BY country ASC")->fetchAll(PDO::FETCH_ASSOC);
    $consultants = $pdo->query("SELECT * FROM consultants ORDER BY consultant_rating DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $universities = [];
    $visas = [];
    $consultants = [];
}

// Get unique countries in our database to populate filters
$countries = array_unique(array_column($universities, 'country'));

$activeTab = $_GET['tab'] ?? 'universities';
if (!in_array($activeTab, ['universities', 'visas', 'consultants'], true)) {
    $activeTab = 'universities';
}

$activeCountry = trim($_GET['country'] ?? '');
if ($activeCountry !== '' && !in_array($activeCountry, $countries, true)) {
    $activeCountry = '';
}

$siteBase = defined('BASE_URL') ? BASE_URL : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$canonicalUrl = $siteBase . '/study-abroad';
if ($activeTab !== 'universities') $canonicalUrl .= '?tab=' . urlencode($activeTab);
if ($activeCountry !== '') $canonicalUrl .= ($activeTab !== 'universities' ? '&' : '?') . 'country=' . urlencode($activeCountry);

$countryLabel = $activeCountry !== '' ? $activeCountry . ' ' : '';
$tabLabels = ['universities'=>'Universities','visas'=>'Visa Guides','consultants'=>'Consultants'];
$pageTitle = $countryLabel . 'Study Abroad ' . ($activeTab !== 'universities' ? '- ' . ($tabLabels[$activeTab] ?? '') . ' ' : '') . date('Y') . ' - AdmissionSeason';
$metaDesc = 'Explore ' . strtolower($countryLabel) . 'study abroad options for ' . date('Y') . '. Find top universities, visa requirements, and consult verified overseas education counselors. ' . count($universities) . '+ universities listed.';
$metaKeywords = 'study abroad ' . date('Y') . ', ' . strtolower($countryLabel) . 'universities, ' . strtolower($countryLabel) . 'visa guide, study in ' . strtolower($activeCountry ?: 'US UK Canada Australia Germany') . ', overseas education, foreign university admission, study abroad consultants';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/favicon.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
  <link rel="canonical" href="<?= $canonicalUrl ?>">
  <meta name="author" content="AdmissionSeason">

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= $canonicalUrl ?>">
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta property="og:image" content="<?= $siteBase ?>/assets/img/logo.png">
  <meta property="og:site_name" content="AdmissionSeason">
  <meta property="og:locale" content="en_IN">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= $canonicalUrl ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="twitter:image" content="<?= $siteBase ?>/assets/img/logo.png">

  <!-- Structured Data: CollectionPage -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'description' => $metaDesc,
    'url' => $canonicalUrl,
    'publisher' => ['@type' => 'Organization', 'name' => 'AdmissionSeason', 'url' => "$siteBase"],
    'isPartOf' => ['@type' => 'WebSite', 'name' => 'AdmissionSeason', 'url' => "$siteBase"],
    'inLanguage' => 'en-IN',
    'mainEntity' => ['@type' => 'ItemList', 'name' => $pageTitle, 'numberOfItems' => count($universities)]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <!-- Structured Data: BreadcrumbList -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => "$siteBase/"],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'Study Abroad', 'item' => "$siteBase/study-abroad"],
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=8">
  
  <style>
    :root {
      --oxford-navy: #0B2447;
      --yale-blue: #19376D;
      --snow-pearl: #F8FAFC;
      --ink-black: #0F172A;
      --border-color-alt: #e2e8f0;
      --text-muted-alt: #64748b;
      --accent-glow: rgba(25, 55, 109, 0.08);
      --card-bg: #ffffff;
    }

    body {
      background-color: var(--snow-pearl);
      color: var(--ink-black);
      font-family: 'Inter', sans-serif;
    }

    /* Hero Banner */
    .abroad-hero {
      background: linear-gradient(135deg, var(--oxford-navy) 0%, var(--yale-blue) 100%);
      color: #fff;
      padding: 60px 0 50px 0;
      position: relative;
      overflow: hidden;
      text-align: center;
    }

    .abroad-hero::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at 80% 20%, rgba(255,255,255,0.08) 0%, transparent 60%);
      pointer-events: none;
    }

    .abroad-hero h1 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 3rem;
      font-weight: 800;
      margin-bottom: 16px;
      line-height: 1.1;
      letter-spacing: -0.5px;
    }

    .abroad-hero p {
      font-size: 1.15rem;
      max-width: 600px;
      margin: 0 auto 32px auto;
      opacity: 0.9;
    }

    /* Quick Country Badges */
    .country-filter-wrap {
      display: flex;
      justify-content: center;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }

    .country-tab-btn {
      background: rgba(255, 255, 255, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
      padding: 10px 20px;
      border-radius: 100px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
    }

    .country-tab-btn:hover, .country-tab-btn.active {
      background: #fff;
      color: var(--oxford-navy);
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transform: translateY(-2px);
    }

    /* Universities List Grid */
    .uni-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
    }

    @media (max-width: 1100px) {
      .uni-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 700px) {
      .uni-grid { grid-template-columns: 1fr; }
    }

    .uni-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color-alt);
      border-radius: 14px;
      padding: 18px;
      display: flex;
      flex-direction: column;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
    }

    .uni-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(11, 36, 71, 0.08);
      border-color: rgba(25, 55, 109, 0.2);
    }

    .uni-badge-qs {
      position: absolute;
      top: 0;
      right: 0;
      background: var(--yale-blue);
      color: #fff;
      padding: 3px 10px;
      border-radius: 0 14px 0 10px;
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.3px;
    }

    .uni-header {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-bottom: 10px;
    }

    .uni-logo {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      object-fit: cover;
      border: 1px solid var(--border-color-alt);
      flex-shrink: 0;
      background: var(--snow-pearl);
    }

    .uni-meta h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 0.92rem;
      font-weight: 700;
      color: var(--oxford-navy);
      margin-bottom: 2px;
      line-height: 1.2;
    }

    .uni-loc {
      font-size: 0.73rem;
      color: var(--text-muted-alt);
      display: flex;
      align-items: center;
      gap: 4px;
      font-weight: 500;
    }

    .uni-quick-stats {
      display: flex;
      gap: 8px;
      margin-top: auto;
      padding-top: 10px;
      border-top: 1px solid var(--border-color-alt);
    }

    .uni-quick-stat {
      flex: 1;
      text-align: center;
    }

    .uni-quick-stat span {
      display: block;
      font-size: 0.62rem;
      color: var(--text-muted-alt);
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: 0.4px;
      margin-bottom: 2px;
    }

    .uni-quick-stat strong {
      color: var(--oxford-navy);
      font-size: 0.82rem;
      font-weight: 700;
    }

    .uni-view-link {
      display: block;
      text-align: center;
      margin-top: 10px;
      color: var(--yale-blue);
      font-size: 0.75rem;
      font-weight: 700;
      text-decoration: none;
    }

    .uni-view-link:hover { text-decoration: underline; }

    /* Visa Cards - Compact Grid */
    .visa-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
    }
    @media (max-width: 1100px) { .visa-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 700px) { .visa-grid { grid-template-columns: 1fr; } }

    .visa-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color-alt);
      border-radius: 16px;
      padding: 0;
      transition: all 0.3s ease;
      overflow: hidden;
      text-decoration: none;
      color: inherit;
      display: flex;
      flex-direction: column;
      cursor: pointer;
    }

    .visa-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(11, 36, 71, 0.08);
      border-color: rgba(25, 55, 109, 0.2);
    }

    .visa-card-top {
      background: linear-gradient(135deg, var(--oxford-navy) 0%, var(--yale-blue) 100%);
      padding: 20px 20px 16px;
      color: #fff;
      position: relative;
    }

    .visa-card-top::after {
      content: '';
      position: absolute;
      top: 0; right: 0;
      width: 80px; height: 80px;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      pointer-events: none;
    }

    .visa-flag-icon {
      font-size: 2rem;
      margin-bottom: 8px;
      display: block;
    }

    .visa-card-country {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.2rem;
      font-weight: 800;
      margin-bottom: 4px;
      line-height: 1.2;
    }

    .visa-card-type {
      font-size: 0.75rem;
      font-weight: 600;
      opacity: 0.85;
      display: inline-block;
      background: rgba(255,255,255,0.15);
      padding: 3px 10px;
      border-radius: 20px;
      margin-top: 4px;
    }

    .visa-card-body {
      padding: 18px 20px 20px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .visa-card-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 16px;
    }

    .visa-stat {
      text-align: center;
      padding: 8px 0;
    }

    .visa-stat span {
      display: block;
      font-size: 0.62rem;
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: 0.4px;
      color: var(--text-muted-alt);
      margin-bottom: 2px;
    }

    .visa-stat strong {
      font-size: 0.95rem;
      font-weight: 800;
      color: var(--oxford-navy);
    }

    .visa-card-tags {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      margin-top: auto;
      padding-top: 12px;
      border-top: 1px solid var(--border-color-alt);
    }

    .visa-tag {
      font-size: 0.65rem;
      font-weight: 600;
      padding: 3px 8px;
      border-radius: 6px;
      background: rgba(25, 55, 109, 0.06);
      color: var(--yale-blue);
    }

    .visa-tag.interview { background: rgba(234, 88, 12, 0.08); color: #c2410c; }
    .visa-tag.work { background: rgba(22, 101, 52, 0.08); color: #166534; }

    .visa-card-link {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 14px;
      padding: 10px;
      border-radius: 10px;
      background: var(--snow-pearl);
      border: 1px solid var(--border-color-alt);
      color: var(--yale-blue);
      font-size: 0.78rem;
      font-weight: 700;
      transition: all 0.2s;
    }

    .visa-card:hover .visa-card-link {
      background: var(--oxford-navy);
      color: #fff;
      border-color: var(--oxford-navy);
    }

    /* Consultants - Compact Card Grid */
    .cons-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
    }
    @media (max-width: 1100px) { .cons-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 700px) { .cons-grid { grid-template-columns: 1fr; } }

    .cons-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color-alt);
      border-radius: 16px;
      overflow: hidden;
      transition: all 0.3s ease;
      text-decoration: none;
      color: inherit;
      display: flex;
      flex-direction: column;
      cursor: pointer;
    }

    .cons-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(11, 36, 71, 0.08);
      border-color: rgba(25, 55, 109, 0.2);
    }

    .cons-card-top {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 20px 20px 0;
    }

    .cons-avatar {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      object-fit: cover;
      border: 2px solid var(--border-color-alt);
      background: var(--snow-pearl);
      flex-shrink: 0;
    }

    .cons-name-wrap {
      flex: 1;
      min-width: 0;
    }

    .cons-name-row {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 3px;
    }

    .cons-name-row h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--oxford-navy);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .cons-verified-icon {
      color: var(--yale-blue);
      font-size: 1rem;
      flex-shrink: 0;
    }

    .cons-rating-pill {
      background: #fef9c3;
      color: #854d0e;
      padding: 2px 7px;
      border-radius: 5px;
      font-size: 0.7rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 3px;
    }

    .cons-card-body {
      padding: 16px 20px 20px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .cons-stats-row {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 8px;
      margin-bottom: 14px;
    }

    .cons-stat {
      text-align: center;
      padding: 8px 4px;
      background: var(--snow-pearl);
      border-radius: 8px;
    }

    .cons-stat span {
      display: block;
      font-size: 0.58rem;
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: 0.4px;
      color: var(--text-muted-alt);
      margin-bottom: 2px;
    }

    .cons-stat strong {
      font-size: 0.82rem;
      font-weight: 800;
      color: var(--oxford-navy);
    }

    .cons-countries-row {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
      margin-bottom: 14px;
    }

    .cons-country-tag {
      background: rgba(25, 55, 109, 0.06);
      color: var(--yale-blue);
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.65rem;
      font-weight: 600;
    }

    .cons-mode-tag {
      font-size: 0.65rem;
      font-weight: 600;
      padding: 3px 8px;
      border-radius: 6px;
      margin-top: auto;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .cons-mode-tag.online { background: rgba(22, 101, 52, 0.08); color: #166534; }
    .cons-mode-tag.offline { background: rgba(234, 88, 12, 0.08); color: #c2410c; }
    .cons-mode-tag.both { background: rgba(25, 55, 109, 0.08); color: var(--yale-blue); }

    .cons-card-link {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 14px;
      padding: 10px;
      border-radius: 10px;
      background: var(--snow-pearl);
      border: 1px solid var(--border-color-alt);
      color: var(--yale-blue);
      font-size: 0.78rem;
      font-weight: 700;
      transition: all 0.2s;
    }

    .cons-card:hover .cons-card-link {
      background: var(--oxford-navy);
      color: #fff;
      border-color: var(--oxford-navy);
    }

    /* Portal CTA block */
    .portal-cta-banner {
      background: linear-gradient(135deg, var(--yale-blue) 0%, var(--oxford-navy) 100%);
      color: #fff;
      border-radius: 24px;
      padding: 50px;
      text-align: center;
      margin-top: 60px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(11,36,71,0.15);
    }

    .portal-cta-banner h2 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 2.2rem;
      font-weight: 800;
      margin-bottom: 14px;
    }

    .portal-cta-banner p {
      font-size: 1.05rem;
      max-width: 600px;
      margin: 0 auto 30px auto;
      opacity: 0.9;
    }

    .cta-btn-alt {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #fff;
      color: var(--oxford-navy);
      padding: 14px 32px;
      border-radius: 12px;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .cta-btn-alt:hover {
      background: var(--snow-pearl);
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(255,255,255,0.2);
    }

    /* JS Controlled displays */
    .portal-section-content {
      display: none !important;
    }

    .portal-section-content.active {
      display: block !important;
    }

    /* Portal Tabs */
    .portal-tabs {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-bottom: 40px;
      border-bottom: 1.5px solid var(--border-color-alt);
      padding-bottom: 0;
      flex-wrap: wrap;
    }

    .portal-tab-btn {
      background: none;
      border: none;
      color: var(--text-muted-alt);
      font-size: 1rem;
      font-weight: 700;
      padding: 12px 28px;
      cursor: pointer;
      position: relative;
      transition: all 0.3s;
      font-family: 'Space Grotesk', sans-serif;
    }

    .portal-tab-btn:hover {
      color: var(--yale-blue);
    }

    .portal-tab-btn.active {
      color: var(--oxford-navy);
    }

    .portal-tab-btn.active::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      right: 0;
      height: 3px;
      background: var(--oxford-navy);
      border-radius: 3px 3px 0 0;
    }

    .abroad-portal-container {
      padding: 40px 0 80px 0;
    }

    @media (max-width: 992px) {
      .abroad-hero { padding: 50px 20px; }
      .abroad-hero h1 { font-size: 2.2rem; }
      .abroad-portal-container { padding: 40px 20px 80px 20px; }
      .uni-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
      .abroad-hero { padding: 40px 16px; }
      .abroad-hero h1 { font-size: 1.7rem; margin-bottom: 10px; }
      .abroad-hero p { font-size: 0.9rem; margin-bottom: 20px; }
      .country-filter-wrap { gap: 8px; flex-wrap: wrap; justify-content: center; }
      .country-tab-btn { padding: 7px 14px; font-size: 0.78rem; }
      .portal-tabs { overflow-x: auto; justify-content: flex-start; padding-bottom: 8px; white-space: nowrap; gap: 6px; -webkit-overflow-scrolling: touch; }
      .portal-tab-btn { font-size: 0.85rem; padding: 8px 14px; flex-shrink: 0; }
      .uni-grid, .cons-grid { grid-template-columns: 1fr; gap: 16px; }
      .uni-card { padding: 14px; }
      .uni-logo { width: 38px; height: 38px; }
      .uni-meta h3 { font-size: 0.85rem; }
      .visa-grid { grid-template-columns: 1fr; }
      .portal-cta-banner { padding: 28px 16px; margin-top: 40px; }
      .portal-cta-banner h2 { font-size: 1.4rem; }
      .portal-cta-banner p { font-size: 0.9rem; }
      .cons-card { padding: 16px; }
    }

    @media (max-width: 480px) {
      .abroad-hero h1 { font-size: 1.45rem; }
      .abroad-hero p { font-size: 0.82rem; }
      .country-tab-btn { padding: 6px 10px; font-size: 0.72rem; }
      .uni-grid { gap: 12px; }
      .uni-quick-stats { gap: 4px; }
      .uni-quick-stat span { font-size: 0.58rem; }
      .uni-quick-stat strong { font-size: 0.75rem; }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Study Abroad Hero -->
<section class="abroad-hero">
  <div class="container">
    <h1>Global Study Abroad Portal 2026</h1>
    <p>Unlock world-class education. Find top-ranked universities, read official visa guides, and consult verified study abroad counselors.</p>
    
    <!-- Filter Badges -->
    <div class="country-filter-wrap">
      <button class="country-tab-btn <?= $activeCountry === '' ? 'active' : '' ?>" onclick="filterCountry('all')">
        <i class="ph ph-globe"></i> All Countries
      </button>
      <?php foreach ($countries as $c): ?>
        <button class="country-tab-btn <?= $activeCountry === $c ? 'active' : '' ?>" onclick="filterCountry('<?= htmlspecialchars($c) ?>')">
          <i class="ph ph-map-pin"></i> <?= htmlspecialchars($c) ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Main Portal Area -->
<section class="abroad-portal-container">
  <div class="container">
    
    <!-- Portal Tabs Menu -->
    <div class="portal-tabs">
      <button class="portal-tab-btn <?= $activeTab === 'universities' ? 'active' : '' ?>" onclick="switchSection('universities', this)">
        <i class="ph ph-graduation-cap"></i> Top Universities
      </button>
      <button class="portal-tab-btn <?= $activeTab === 'visas' ? 'active' : '' ?>" onclick="switchSection('visas', this)">
        <i class="ph ph-file-text"></i> Visa Requirements
      </button>
      <button class="portal-tab-btn <?= $activeTab === 'consultants' ? 'active' : '' ?>" onclick="switchSection('consultants', this)">
        <i class="ph ph-users-three"></i> Overseas Consultants
      </button>
    </div>

    <!-- 1. UNIVERSITIES TAB -->
    <div id="section-universities" class="portal-section-content <?= $activeTab === 'universities' ? 'active' : '' ?>" style="<?= $activeTab === 'universities' ? 'display:block !important' : '' ?>">
      <?php if (empty($universities)): ?>
        <div style="text-align:center; padding: 60px 0; color: var(--text-muted-alt);">
          <i class="ph ph-folder-open" style="font-size:3rem; margin-bottom:12px;"></i>
          <p>No foreign universities database found. Please seed the database first.</p>
        </div>
      <?php else: ?>
        <div class="uni-grid">
          <?php foreach ($universities as $uni): ?>
            <a href="<?= BASE_URL ?>/foreign-university/<?= htmlspecialchars($uni['university_slug'] ?? $uni['id']) ?>" class="uni-card" data-country="<?= htmlspecialchars($uni['country']) ?>">
              <span class="uni-badge-qs">QS #<?= htmlspecialchars((string)$uni['qs_rank']) ?></span>
              <div class="uni-header">
                <img src="<?= htmlspecialchars($uni['logo_url'] ?: 'https://images.unsplash.com/photo-1592280771190-3e2e4d571952?w=100&h=100&fit=crop') ?>" alt="logo" class="uni-logo">
                <div class="uni-meta">
                  <h3><?= htmlspecialchars($uni['university_name']) ?></h3>
                  <div class="uni-loc"><i class="ph ph-map-pin"></i> <?= htmlspecialchars($uni['city'] ? $uni['city'] . ', ' : '') ?><?= htmlspecialchars($uni['country']) ?></div>
                </div>
              </div>
              <div class="uni-quick-stats">
                <div class="uni-quick-stat">
                  <span>Tuition/yr</span>
                  <strong><?= (float)$uni['tuition_usd_annual'] > 0 ? '$' . number_format((float)$uni['tuition_usd_annual'], 0) : 'Free' ?></strong>
                </div>
                <div class="uni-quick-stat">
                  <span>IELTS</span>
                  <strong><?= htmlspecialchars((string)($uni['min_ielts'] ?: '6.5')) ?></strong>
                </div>
                <div class="uni-quick-stat">
                  <span>Accept.</span>
                  <strong><?= htmlspecialchars((string)$uni['acceptance_rate']) ?>%</strong>
                </div>
              </div>
              <div class="uni-view-link">View Details <i class="ph ph-arrow-right"></i></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- 2. VISA GUIDES TAB -->
    <div id="section-visas" class="portal-section-content <?= $activeTab === 'visas' ? 'active' : '' ?>" style="<?= $activeTab === 'visas' ? 'display:block !important' : '' ?>">
      <?php if (empty($visas)): ?>
        <div style="text-align:center; padding: 60px 0; color: var(--text-muted-alt);">
          <i class="ph ph-folder-open" style="font-size:3rem; margin-bottom:12px;"></i>
          <p>No visa guides found in the database.</p>
        </div>
      <?php else: ?>
        <div class="visa-grid">
          <?php 
          $countryFlags = [
            'United States' => '🇺🇸', 'United Kingdom' => '🇬🇧', 'Canada' => '🇨🇦',
            'Australia' => '🇦🇺', 'Germany' => '🇩🇪', 'Singapore' => '🇸🇬',
            'Ireland' => '🇮🇪', 'New Zealand' => '🇳🇿',
          ];
          foreach ($visas as $visa): 
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $visa['country']), '-'));
            $flag = $countryFlags[$visa['country']] ?? '🌍';
            $docs = json_decode($visa['documents_required'] ?? '[]', true);
          ?>
            <a href="<?= BASE_URL ?>/visa-guide/<?= htmlspecialchars($slug) ?>" class="visa-card" data-country="<?= htmlspecialchars($visa['country']) ?>">
              <div class="visa-card-top">
                <span class="visa-flag-icon"><?= $flag ?></span>
                <div class="visa-card-country"><?= htmlspecialchars($visa['country']) ?></div>
                <span class="visa-card-type"><?= htmlspecialchars($visa['visa_type']) ?></span>
              </div>
              <div class="visa-card-body">
                <div class="visa-card-stats">
                  <div class="visa-stat">
                    <span>Processing</span>
                    <strong><?= htmlspecialchars((string)$visa['processing_time_days']) ?>d</strong>
                  </div>
                  <div class="visa-stat">
                    <span>Visa Fee</span>
                    <strong>$<?= number_format((float)$visa['visa_fee_usd'], 0) ?></strong>
                  </div>
                  <div class="visa-stat">
                    <span>Work Visa</span>
                    <strong><?= htmlspecialchars((string)$visa['pswv_duration_months']) ?>mo</strong>
                  </div>
                  <div class="visa-stat">
                    <span>Funds Req.</span>
                    <strong>$<?= number_format((float)$visa['proof_of_funds_usd'], 0) ?></strong>
                  </div>
                </div>
                <div class="visa-card-tags">
                  <?php if ($visa['interview_required']): ?>
                    <span class="visa-tag interview">Interview Required</span>
                  <?php endif; ?>
                  <?php if ($visa['part_time_work_hours']): ?>
                    <span class="visa-tag work"><?= htmlspecialchars((string)$visa['part_time_work_hours']) ?>hrs/wk Work</span>
                  <?php endif; ?>
                  <span class="visa-tag"><?= count($docs) ?> Docs</span>
                </div>
                <div class="visa-card-link">
                  View Full Guide <i class="ph ph-arrow-right"></i>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- 3. CONSULTANTS TAB -->
    <div id="section-consultants" class="portal-section-content <?= $activeTab === 'consultants' ? 'active' : '' ?>" style="<?= $activeTab === 'consultants' ? 'display:block !important' : '' ?>">
      <?php if (empty($consultants)): ?>
        <div style="text-align:center; padding: 60px 0; color: var(--text-muted-alt);">
          <i class="ph ph-folder-open" style="font-size:3rem; margin-bottom:12px;"></i>
          <p>No study abroad consultants registered in the database.</p>
        </div>
      <?php else: ?>
        <div class="cons-grid">
          <?php foreach ($consultants as $con): ?>
            <?php 
              $conCountries = json_decode($con['specialization_countries'] ?? '[]', true);
              $mode = strtolower($con['consultation_mode'] ?? 'both');
            ?>
            <a href="<?= BASE_URL ?>/consultant/<?= (int)$con['id'] ?>" class="cons-card" data-countries='<?= htmlspecialchars(json_encode($conCountries)) ?>'>
              <div class="cons-card-top">
                <img src="<?= htmlspecialchars($con['logo_url'] ?: 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=100&h=100&fit=crop') ?>" alt="logo" class="cons-avatar">
                <div class="cons-name-wrap">
                  <div class="cons-name-row">
                    <h3><?= htmlspecialchars($con['consultant_name']) ?></h3>
                    <?php if($con['verified_consultant']): ?>
                      <i class="ph-fill ph-seal-check cons-verified-icon" title="Verified"></i>
                    <?php endif; ?>
                  </div>
                  <span class="cons-rating-pill">
                    <i class="ph-fill ph-star"></i> <?= number_format((float)$con['consultant_rating'], 1) ?>/5
                  </span>
                </div>
              </div>
              <div class="cons-card-body">
                <div class="cons-stats-row">
                  <div class="cons-stat">
                    <span>Success</span>
                    <strong><?= number_format((float)$con['success_rate_percent'], 0) ?>%</strong>
                  </div>
                  <div class="cons-stat">
                    <span>Experience</span>
                    <strong><?= htmlspecialchars((string)$con['experience_years']) ?>+yr</strong>
                  </div>
                  <div class="cons-stat">
                    <span>Rating</span>
                    <strong><?= number_format((float)$con['consultant_rating'], 1) ?>/5</strong>
                  </div>
                </div>
                <div class="cons-countries-row">
                  <?php foreach(array_slice($conCountries, 0, 4) as $cc): ?>
                    <span class="cons-country-tag"><?= htmlspecialchars($cc) ?></span>
                  <?php endforeach; ?>
                  <?php if(count($conCountries) > 4): ?>
                    <span class="cons-country-tag">+<?= count($conCountries) - 4 ?> more</span>
                  <?php endif; ?>
                </div>
                <span class="cons-mode-tag <?= $mode ?>">
                  <i class="ph ph-<?= $mode === 'online' ? 'monitor' : ($mode === 'offline' ? 'buildings' : 'arrows-clockwise') ?>"></i>
                  <?= htmlspecialchars($con['consultation_mode'] ?? 'Both') ?>
                </span>
                <div class="cons-card-link">
                  View Profile <i class="ph ph-arrow-right"></i>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Prominent CTA Section -->
    <div class="portal-cta-banner">
      <h2>Confused About Choosing Your Country or Course?</h2>
      <p>Speak to our senior educational advisor today. We will evaluate your profile and create a personalized study abroad roadmap for you, free of cost.</p>
      <a href="counselling" class="cta-btn-alt">
        <i class="ph-fill ph-headset"></i> Get Free Abroad Counseling Now
      </a>
    </div>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
  // Tab Switching Functionality
  function switchSection(sectionId, btn) {
    // Hide all section content
    const sections = document.querySelectorAll('.portal-section-content');
    sections.forEach(sec => {
      sec.classList.remove('active');
      sec.style.display = 'none';
    });

    // Remove active state from all tab buttons
    const buttons = document.querySelectorAll('.portal-tab-btn');
    buttons.forEach(b => b.classList.remove('active'));

    // Show selected section and activate current button
    const target = document.getElementById('section-' + sectionId);
    target.classList.add('active');
    target.style.display = 'block';
    btn.classList.add('active');
  }

  // Country Filtering Functionality
  function filterCountry(countryName) {
    // Update active country tab styling
    const btns = document.querySelectorAll('.country-tab-btn');
    btns.forEach(b => {
      if (b.getAttribute('onclick').includes("'" + countryName + "'")) {
        b.classList.add('active');
      } else {
        b.classList.remove('active');
      }
    });

    // 1. Filter Universities Card Grid
    const uniCards = document.querySelectorAll('.uni-card');
    uniCards.forEach(card => {
      const cardCountry = card.getAttribute('data-country');
      if (countryName === 'all' || cardCountry === countryName) {
        card.style.display = 'flex';
      } else {
        card.style.display = 'none';
      }
    });

    // 2. Filter Visa Cards
    const visaCards = document.querySelectorAll('.visa-card');
    visaCards.forEach(card => {
      const cardCountry = card.getAttribute('data-country');
      if (countryName === 'all' || cardCountry === countryName) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });

    // 3. Filter Consultants Cards (based on specialization JSON array)
    const consCards = document.querySelectorAll('.cons-card');
    consCards.forEach(card => {
      const countriesList = JSON.parse(card.getAttribute('data-countries') || '[]');
      if (countryName === 'all' || countriesList.includes(countryName)) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  }

  // Auto-filter by country if URL has ?country=X
  <?php if ($activeCountry !== ''): ?>
  document.addEventListener('DOMContentLoaded', function() {
    filterCountry('<?= htmlspecialchars($activeCountry) ?>');
  });
  <?php endif; ?>
</script>

</body>
</html>
