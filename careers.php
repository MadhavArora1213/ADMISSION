<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$stream = trim($_GET['stream'] ?? '');
if (!in_array($stream, ['Science', 'Commerce', 'Humanities'])) {
    $stream = '';
}

// Fetch all careers for the selected stream to filter on client-side
$careersJson = '[]';
if ($stream !== '') {
    try {
        $stmt = $pdo->prepare("SELECT name, slug, sub_stream, short_description, salary_range, image_url FROM careers WHERE stream = ? ORDER BY name ASC");
        $stmt->execute([$stream]);
        $careersJson = json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        $careersJson = '[]';
    }
}

// Map sub-streams (interest categories) with details and icons for display
$interests = [];
if ($stream === 'Science') {
    $interests = [
        [
            'key' => 'Aviation & Aerospace',
            'icon' => 'ph ph-airplane',
            'title' => 'Aviation & Aerospace',
            'desc' => 'Designing aircraft, flying passenger planes, space missions, aerodynamics, or atmospheric studies.'
        ],
        [
            'key' => 'Software & IT',
            'icon' => 'ph ph-cpu',
            'title' => 'Software & IT',
            'desc' => 'Writing programming scripts, developing cloud infrastructure, building software applications, or training AI models.'
        ],
        [
            'key' => 'Medical & Health',
            'icon' => 'ph ph-first-aid',
            'title' => 'Medical & Health',
            'desc' => 'Diagnosing illnesses, prescribing treatments, running medical labs, or studying biology and patient care.'
        ]
    ];
} elseif ($stream === 'Commerce') {
    $interests = [
        [
            'key' => 'Finance & Accounting',
            'icon' => 'ph ph-coins',
            'title' => 'Finance & Accounting',
            'desc' => 'Auditing accounts, corporate tax filings, wealth management, asset valuation, or investment banking.'
        ],
        [
            'key' => 'Corporate & Business',
            'icon' => 'ph ph-briefcase-metal',
            'title' => 'Corporate & Business Management',
            'desc' => 'Leading team operations, managing resources, corporate communications, or business development.'
        ]
    ];
} elseif ($stream === 'Humanities') {
    $interests = [
        [
            'key' => 'Legal & Law',
            'icon' => 'ph ph-scales',
            'title' => 'Legal & Law',
            'desc' => 'Drafting commercial contracts, litigation, corporate regulatory compliance, and advisory.'
        ],
        [
            'key' => 'Management',
            'icon' => 'ph ph-shield-star',
            'title' => 'Hospitality & Management',
            'desc' => 'Hotel operations, resort management, catering services, guest relations, and travel logistics.'
        ],
        [
            'key' => 'Creative & Design',
            'icon' => 'ph ph-paint-brush-broad',
            'title' => 'Creative Arts & Design',
            'desc' => 'Developing visual branding, graphic illustrations, typography layouts, user experiences, or animation.'
        ]
    ];
}

$siteBase = defined('BASE_URL') ? BASE_URL : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$canonicalUrl = $siteBase . '/careers';
if ($stream !== '') $canonicalUrl .= '?stream=' . urlencode($stream);

$streamLabel = $stream !== '' ? $stream . ' ' : '';
$pageTitle = $streamLabel . 'Career Options & Paths ' . date('Y') . ' | AdmissionSeason';
$metaDesc = 'Explore ' . strtolower($streamLabel) . 'career options in India for ' . date('Y') . '. Find detailed career paths, salary ranges, required skills, exams, and top colleges for ' . strtolower($streamLabel) . 'students.';
$metaKeywords = strtolower($streamLabel) . 'career options, ' . strtolower($streamLabel) . 'career paths, career after 12th ' . strtolower($stream) . ', jobs after ' . strtolower($stream) . ', salary, skills, top careers india ' . date('Y');

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
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <!-- Structured Data: BreadcrumbList -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => "$siteBase/"],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'Careers', 'item' => "$siteBase/careers"],
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=8">

  <style>
    :root {
      --oxford-navy: #0B2447;
      --yale-blue: #19376D;
      --snow-pearl: #F8FAFC;
      --ink-black: #0F172A;
      --text-muted-alt: #64748b;
      --border-color-alt: #e2e8f0;
      --primary-accent: #2563eb;
    }

    body {
      background-color: var(--snow-pearl);
      color: var(--ink-black);
      font-family: 'Inter', sans-serif;
    }

    .wizard-hero {
      background: linear-gradient(135deg, var(--oxford-navy) 0%, var(--yale-blue) 100%);
      color: #fff;
      padding: 60px 0;
      text-align: center;
      position: relative;
    }

    .wizard-hero h1 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 2.50rem;
      font-weight: 800;
      margin-bottom: 12px;
    }

    .wizard-hero p {
      font-size: 1.1rem;
      opacity: 0.9;
      max-width: 600px;
      margin: 0 auto;
    }

    /* Landing cards */
    .stream-landing-container {
      padding: 80px 0;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
      max-width: 1100px;
      margin: 0 auto;
    }

    .stream-card {
      background: #fff;
      border: 1px solid var(--border-color-alt);
      border-radius: 24px;
      padding: 40px 30px;
      text-align: center;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(11, 36, 71, 0.02);
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .stream-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(11, 36, 71, 0.08);
      border-color: rgba(25, 55, 109, 0.2);
    }

    .stream-icon-container {
      width: 70px;
      height: 70px;
      border-radius: 20px;
      background: rgba(25, 55, 109, 0.06);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--yale-blue);
      font-size: 2.2rem;
      margin-bottom: 24px;
      transition: all 0.3s ease;
    }

    .stream-card:hover .stream-icon-container {
      background: var(--yale-blue);
      color: #fff;
      transform: scale(1.1);
    }

    .stream-card h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--oxford-navy);
      margin-bottom: 12px;
    }

    .stream-card p {
      font-size: 0.92rem;
      color: var(--text-muted-alt);
      line-height: 1.5;
      margin-bottom: 30px;
      flex-grow: 1;
    }

    .stream-btn {
      background: var(--oxford-navy);
      color: #fff;
      border: none;
      padding: 12px 30px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.92rem;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s;
      width: 100%;
      box-sizing: border-box;
    }

    .stream-card:hover .stream-btn {
      background: var(--yale-blue);
    }

    /* Active Wizard Layout */
    .wizard-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 50px 0 100px 0;
      display: grid;
      grid-template-columns: 1.25fr 0.75fr;
      gap: 40px;
      align-items: start;
    }

    .wizard-main {
      background: #fff;
      border: 1px solid var(--border-color-alt);
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 10px 25px rgba(11, 36, 71, 0.02);
    }

    .stream-nav-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1.5px solid var(--border-color-alt);
      padding-bottom: 16px;
      margin-bottom: 30px;
    }

    .stream-badge {
      background: rgba(25, 55, 109, 0.08);
      color: var(--yale-blue);
      font-size: 0.9rem;
      font-weight: 700;
      padding: 6px 16px;
      border-radius: 100px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .change-stream-btn {
      color: var(--text-muted-alt);
      text-decoration: none;
      font-size: 0.88rem;
      font-weight: 700;
      transition: color 0.2s;
    }

    .change-stream-btn:hover {
      color: var(--oxford-navy);
    }

    .wizard-main h2 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--oxford-navy);
      margin-bottom: 12px;
      text-align: left;
    }

    .wizard-main p.instruction {
      font-size: 0.98rem;
      color: var(--text-muted-alt);
      margin-bottom: 30px;
      text-align: left;
    }

    /* Interest Deck Cards */
    .interest-deck {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
    }

    .interest-card {
      background: #fff;
      border: 2px solid var(--border-color-alt);
      border-radius: 16px;
      padding: 24px;
      display: flex;
      gap: 20px;
      cursor: pointer;
      transition: all 0.25s ease;
      text-align: left;
      position: relative;
    }

    .interest-card:hover {
      border-color: rgba(25, 55, 109, 0.3);
      background: var(--snow-pearl);
    }

    .interest-card.selected {
      border-color: var(--yale-blue);
      background: rgba(25, 55, 109, 0.02);
      box-shadow: 0 4px 15px rgba(25, 55, 109, 0.05);
    }

    .interest-checkbox {
      width: 22px;
      height: 22px;
      border-radius: 6px;
      border: 2px solid var(--border-color-alt);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      background: #fff;
      flex-shrink: 0;
      margin-top: 3px;
      transition: all 0.2s;
    }

    .interest-card.selected .interest-checkbox {
      background: var(--yale-blue);
      border-color: var(--yale-blue);
    }

    .interest-checkbox i {
      font-size: 0.9rem;
      font-weight: 800;
    }

    .interest-icon {
      font-size: 2.2rem;
      color: var(--yale-blue);
      flex-shrink: 0;
    }

    .interest-info h4 {
      font-size: 1.1rem;
      font-weight: 750;
      color: var(--oxford-navy);
      margin-bottom: 6px;
    }

    .interest-info p {
      font-size: 0.88rem;
      color: var(--text-muted-alt);
      line-height: 1.45;
    }

    /* Right Selection Sidebar */
    .wizard-sidebar {
      background: #fff;
      border: 1px solid var(--border-color-alt);
      border-radius: 24px;
      padding: 30px;
      box-shadow: 0 10px 25px rgba(11, 36, 71, 0.02);
      position: sticky;
      top: 100px;
      text-align: left;
    }

    .wizard-sidebar h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--oxford-navy);
      margin-bottom: 12px;
    }

    .wizard-sidebar p {
      font-size: 0.82rem;
      color: var(--text-muted-alt);
      line-height: 1.4;
      margin-bottom: 24px;
    }

    .choice-box {
      border: 1.5px dashed var(--border-color-alt);
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.2s;
    }

    .choice-box.active {
      border-style: solid;
      border-color: var(--yale-blue);
      background: rgba(25, 55, 109, 0.02);
    }

    .choice-num {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: var(--border-color-alt);
      color: var(--text-muted-alt);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.76rem;
      font-weight: 700;
      flex-shrink: 0;
    }

    .choice-box.active .choice-num {
      background: var(--yale-blue);
      color: #fff;
    }

    .choice-lbl {
      font-size: 0.88rem;
      font-weight: 650;
      color: var(--text-muted-alt);
    }

    .choice-box.active .choice-lbl {
      color: var(--oxford-navy);
      font-weight: 750;
    }

    .submit-wizard-btn {
      width: 100%;
      background: var(--border-color-alt);
      color: var(--text-muted-alt);
      border: none;
      padding: 14px 20px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.95rem;
      cursor: not-allowed;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.25s ease;
      margin-top: 10px;
    }

    .submit-wizard-btn.ready {
      background: var(--oxford-navy);
      color: #fff;
      cursor: pointer;
    }

    .submit-wizard-btn.ready:hover {
      background: var(--yale-blue);
      transform: translateY(-1px);
    }

    /* Recommended results output */
    .results-section {
      max-width: 1200px;
      margin: 0 auto 100px auto;
      padding: 40px;
      background: #fff;
      border: 1px solid var(--border-color-alt);
      border-radius: 24px;
      box-shadow: 0 10px 30px rgba(11, 36, 71, 0.03);
      display: none;
      text-align: left;
    }

    .results-section h2 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.85rem;
      font-weight: 800;
      color: var(--oxford-navy);
      margin-bottom: 24px;
      border-bottom: 2px solid var(--border-color-alt);
      padding-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .results-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
    }

    .career-card {
      border: 1px solid var(--border-color-alt);
      border-radius: 16px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: all 0.3s;
    }

    .career-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 24px rgba(11, 36, 71, 0.08);
      border-color: rgba(25, 55, 109, 0.15);
    }

    .career-img {
      width: 100%;
      height: 160px;
      object-fit: cover;
    }

    .career-body {
      padding: 20px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      text-align: left;
    }

    .career-body h4 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.15rem;
      font-weight: 750;
      color: var(--oxford-navy);
      margin-bottom: 8px;
    }

    .career-body p {
      font-size: 0.85rem;
      color: var(--text-muted-alt);
      line-height: 1.45;
      margin-bottom: 18px;
      flex-grow: 1;
    }

    .career-meta-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: auto;
      border-top: 1px solid var(--border-color-alt);
      padding-top: 14px;
    }

    .salary-pill {
      background: rgba(16, 185, 129, 0.08);
      color: #10b981;
      font-size: 0.78rem;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 6px;
    }

    .explore-link {
      color: var(--yale-blue);
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .explore-link:hover {
      color: var(--oxford-navy);
    }

    /* Responsive Media Queries */
    @media (max-width: 992px) {
      .wizard-container {
        grid-template-columns: 1fr;
        padding: 40px 20px;
        gap: 30px;
      }
      
      .wizard-sidebar {
        position: static;
        margin-top: 20px;
      }
      
      .results-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .stream-landing-container {
        grid-template-columns: 1fr;
        padding: 40px 20px;
        gap: 24px;
      }
      
      .wizard-hero h1 {
        font-size: 2rem;
      }
      
      .wizard-main {
        padding: 24px;
      }
      
      .interest-card {
        padding: 16px;
        gap: 12px;
      }
      
      .interest-icon {
        font-size: 1.8rem;
      }
      
      .results-section {
        padding: 24px;
      }
      
      .results-section h2 {
        font-size: 1.5rem;
      }
    }

    @media (max-width: 600px) {
      .results-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Hero banner -->
<section class="wizard-hero">
  <div class="container">
    <h1>Explore Career Opportunities</h1>
    <p>Discover educational and professional pathways tailored to your academic streams and interests.</p>
  </div>
</section>

<!-- 1. Stream Selection Panel (If no stream selected) -->
<?php if ($stream === ''): ?>
<div class="container">
  <div class="stream-landing-container">
    
    <div class="stream-card">
      <div class="stream-icon-container">
        <i class="ph ph-atom"></i>
      </div>
      <h3>Science Stream</h3>
      <p>Engineering, Medicine, Computer Science, Aviation, Biotechnology, Physics, and Tech-oriented research paths.</p>
      <a href="<?= BASE_URL ?>/careers?stream=Science" class="stream-btn">Explore Science</a>
    </div>

    <div class="stream-card">
      <div class="stream-icon-container">
        <i class="ph ph-chart-line-up"></i>
      </div>
      <h3>Commerce Stream</h3>
      <p>Finance, Auditing, Investment Banking, Cost Accounting, General Management, Corporate Law, and Marketing.</p>
      <a href="<?= BASE_URL ?>/careers?stream=Commerce" class="stream-btn">Explore Commerce</a>
    </div>

    <div class="stream-card">
      <div class="stream-icon-container">
        <i class="ph ph-palette"></i>
      </div>
      <h3>Humanities & Arts</h3>
      <p>Corporate Law, Hospitality, Hotel Operations, Graphic & Digital Design, Journalism, Media, and Civil Services.</p>
      <a href="<?= BASE_URL ?>/careers?stream=Humanities" class="stream-btn">Explore Humanities</a>
    </div>

  </div>
</div>

<!-- 2. Wizard Selector Interface (If stream selected) -->
<?php else: ?>
<div class="container wizard-container">
  
  <!-- Left Panel: Interest choices -->
  <div class="wizard-main">
    <div class="stream-nav-header">
      <span class="stream-badge"><i class="ph ph-sparkles"></i> <?= htmlspecialchars($stream) ?> Stream</span>
      <a href="<?= BASE_URL ?>/careers" class="change-stream-btn"><i class="ph ph-arrow-left"></i> Change Stream</a>
    </div>

    <h2>What are your interests?</h2>
    <p class="instruction">Choose up to 2 areas of focus below that interest you the most to generate customized career paths.</p>

    <div class="interest-deck">
      <?php foreach ($interests as $item): ?>
        <div class="interest-card" data-key="<?= htmlspecialchars($item['key']) ?>" onclick="toggleInterest(this)">
          <div class="interest-checkbox">
            <i class="ph-bold ph-check"></i>
          </div>
          <div class="interest-icon">
            <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
          </div>
          <div class="interest-info">
            <h4><?= htmlspecialchars($item['title']) ?></h4>
            <p><?= htmlspecialchars($item['desc']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right Panel: Selection Sidebar -->
  <div class="wizard-sidebar">
    <h3>Your Selection</h3>
    <p>Select your top choices from the list. We recommend selecting at least one to get customized results.</p>
    
    <div class="choice-box" id="choiceBox1">
      <span class="choice-num">1</span>
      <span class="choice-lbl" id="choiceLbl1">None Selected</span>
    </div>

    <div class="choice-box" id="choiceBox2">
      <span class="choice-num">2</span>
      <span class="choice-lbl" id="choiceLbl2">None Selected</span>
    </div>

    <button id="submitWizard" class="submit-wizard-btn" onclick="generateRecommendations()">
      <i class="ph ph-compass"></i> Explore Recommended Careers
    </button>
  </div>

</div>

<!-- 3. Recommended Output Panel -->
<section id="resultsSection" class="results-section">
  <h2><i class="ph ph-sparkles" style="color: #fbbf24;"></i> Recommended Career Paths</h2>
  <div class="results-grid" id="resultsGrid">
    <!-- Loaded dynamically by Javascript -->
  </div>
</section>

<script>
  // Careers database passed to client-side JS
  const allCareers = <?= $careersJson ?>;
  let selectedInterests = [];

  function toggleInterest(card) {
    const key = card.getAttribute('data-key');
    const index = selectedInterests.indexOf(key);

    if (index > -1) {
      // Deselect
      selectedInterests.splice(index, 1);
      card.classList.remove('selected');
    } else {
      // Max 2 selections
      if (selectedInterests.length >= 2) {
        // Remove oldest
        const oldestKey = selectedInterests.shift();
        const oldestCard = document.querySelector(`.interest-card[data-key="${oldestKey}"]`);
        if (oldestCard) oldestCard.classList.remove('selected');
      }
      // Add new
      selectedInterests.push(key);
      card.classList.add('selected');
    }

    updateSidebar();
  }

  function updateSidebar() {
    const lbl1 = document.getElementById('choiceLbl1');
    const lbl2 = document.getElementById('choiceLbl2');
    const box1 = document.getElementById('choiceBox1');
    const box2 = document.getElementById('choiceBox2');
    const btn = document.getElementById('submitWizard');

    // Reset
    lbl1.textContent = "None Selected";
    box1.classList.remove('active');
    lbl2.textContent = "None Selected";
    box2.classList.remove('active');
    btn.classList.remove('ready');
    btn.style.cursor = 'not-allowed';

    if (selectedInterests.length >= 1) {
      lbl1.textContent = selectedInterests[0];
      box1.classList.add('active');
      btn.classList.add('ready');
      btn.style.cursor = 'pointer';
    }
    if (selectedInterests.length >= 2) {
      lbl2.textContent = selectedInterests[1];
      box2.classList.add('active');
    }
  }

  function generateRecommendations() {
    if (selectedInterests.length === 0) return;

    const grid = document.getElementById('resultsGrid');
    const section = document.getElementById('resultsSection');
    grid.innerHTML = '';

    // Filter careers by selected sub_streams
    const matched = allCareers.filter(c => selectedInterests.includes(c.sub_stream));

    if (matched.length === 0) {
      grid.innerHTML = `<div style="grid-column: span 3; text-align:center; padding: 40px; color: var(--text-muted-alt);">
                          <p>No specific matching careers seeded for these categories yet. Check another combo or stream!</p>
                        </div>`;
    } else {
      matched.forEach(c => {
        const card = document.createElement('div');
        card.className = 'career-card';
        
        const fallbackImg = 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600&h=400&fit=crop';
        const imgUrl = c.image_url ? c.image_url : fallbackImg;

        card.innerHTML = `
          <img src="${imgUrl}" alt="${c.name}" class="career-img">
          <div class="career-body">
            <h4>${c.name}</h4>
            <p>${c.short_description || ''}</p>
            <div class="career-meta-row">
              <span class="salary-pill"><i class="ph ph-currency-inr"></i> ${c.salary_range || 'N/A'}</span>
              <a href="<?= BASE_URL ?>/career/${c.slug}" class="explore-link">Explore Path <i class="ph ph-arrow-right"></i></a>
            </div>
          </div>
        `;
        grid.appendChild(card);
      });
    }

    // Reveal and scroll smoothly
    section.style.display = 'block';
    section.scrollIntoView({ behavior: 'smooth' });
  }
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
