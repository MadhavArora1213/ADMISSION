<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/panel_cms_2847/db.php';
require_once __DIR__ . '/includes/exam_helpers.php';
require_once __DIR__ . '/includes/college_helpers.php';

$level = $_GET['level'] ?? 'all';
$mode  = $_GET['mode'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$where = ["status != 'cancelled'"];
$params = [];

if ($level !== 'all') {
    $where[] = "exam_level = ?";
    $params[] = $level;
}
if ($mode !== 'all') {
    $where[] = "exam_mode = ?";
    $params[] = $mode;
}
if ($search !== '') {
    $where[] = "(exam_name LIKE ? OR exam_abbreviation LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM exams WHERE $whereSql ORDER BY applicants_last_year DESC, exam_name ASC");
$stmt->execute($params);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total'    => cCol($pdo, "SELECT COUNT(*) FROM exams WHERE status != 'cancelled'"),
    'national' => cCol($pdo, "SELECT COUNT(*) FROM exams WHERE status != 'cancelled' AND exam_level='national'"),
    'state'    => cCol($pdo, "SELECT COUNT(*) FROM exams WHERE status != 'cancelled' AND exam_level='state'"),
    'online'   => cCol($pdo, "SELECT COUNT(*) FROM exams WHERE status != 'cancelled' AND exam_mode='online'"),
    'offline'  => cCol($pdo, "SELECT COUNT(*) FROM exams WHERE status != 'cancelled' AND exam_mode='offline'"),
];

$levelLabels = ['national'=>'National Level','state'=>'State Level','university'=>'University Level','institute'=>'Institute Level'];

$siteBase = defined('BASE_URL') ? BASE_URL : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$canonicalUrl = $siteBase . '/exams';
if ($level !== 'all') $canonicalUrl .= '?level=' . urlencode($level);
if ($mode !== 'all') $canonicalUrl .= ($level !== 'all' ? '&' : '?') . 'mode=' . urlencode($mode);

$levelLabel = $level !== 'all' ? ($levelLabels[$level] ?? ucfirst($level)) . ' ' : '';
$pageTitle = $levelLabel . 'Top Entrance Exams in India ' . date('Y') . ' - AdmissionSeason';
$metaDesc = 'Explore ' . strtolower($levelLabel) . 'entrance exams in India for ' . date('Y') . '. Check JEE, NEET, CUET, CAT, GATE, CLAT and more. Get exam dates, eligibility, pattern, syllabus and preparation tips.';
$metaKeywords = 'entrance exams india ' . date('Y') . ', JEE exam, NEET exam, CUET exam, CAT exam, GATE exam, CLAT exam, engineering exams, medical exams, management exams, law exams, ' . strtolower($levelLabel) . 'exams, exam dates, exam eligibility, exam pattern';

if ($level !== 'all' || $mode !== 'all') {
    $pageTitle = $levelLabel . ($mode !== 'all' ? ucfirst($mode) . ' ' : '') . 'Entrance Exams in India ' . date('Y') . ' - AdmissionSeason';
    $metaDesc = 'Browse ' . strtolower($levelLabel) . ($mode !== 'all' ? strtolower($mode) . ' ' : '') . 'entrance exams in India. ' . count($exams) . ' exams listed with dates, eligibility, pattern and syllabus.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
  <link rel="canonical" href="<?= $canonicalUrl ?>">
  <meta name="author" content="AdmissionSeason">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= $canonicalUrl ?>">
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta property="og:image" content="<?= $siteBase ?>/assets/img/logo.png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:site_name" content="AdmissionSeason">
  <meta property="og:locale" content="en_IN">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= $canonicalUrl ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="twitter:image" content="<?= $siteBase ?>/assets/img/logo.png">
  <meta name="twitter:site" content="@AdmissionSeason">
  <meta name="twitter:creator" content="@AdmissionSeason">

  <!-- Structured Data: CollectionPage -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'description' => $metaDesc,
    'url' => $canonicalUrl,
    'publisher' => [
      '@type' => 'Organization',
      'name' => 'AdmissionSeason',
      'url' => "$siteBase",
      'logo' => [
        '@type' => 'ImageObject',
        'url' => "$siteBase/assets/img/logo.png",
        'width' => 600,
        'height' => 60
      ]
    ],
    'isPartOf' => [
      '@type' => 'WebSite',
      'name' => 'AdmissionSeason',
      'url' => "$siteBase"
    ],
    'inLanguage' => 'en-IN',
    'mainEntity' => [
      '@type' => 'ItemList',
      'name' => $pageTitle,
      'numberOfItems' => count($exams),
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <!-- Structured Data: BreadcrumbList -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => "$siteBase/"],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'Exams', 'item' => "$siteBase/exams"],
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <style>
    .exams-hero{background-color:#0B2447;background-size:cover;background-position:center;padding:72px 0 48px;color:#fff;position:relative;overflow:hidden}
    .exams-hero::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(11,36,71,0.65),rgba(25,55,109,0.6));pointer-events:none}
    .exams-hero::after{content:'';position:absolute;bottom:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#0B2447,#19376D,#0B2447)}
    .exams-hero .container{position:relative;z-index:2}
    .exams-breadcrumb{display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:.85rem;color:rgba(255,255,255,.5)}
    .exams-breadcrumb a{color:rgba(255,255,255,.6);text-decoration:none;transition:color .2s}
    .exams-breadcrumb a:hover{color:#fff}
    .exams-breadcrumb i{font-size:.7rem}
    .exams-hero h1{font-family:'Plus Jakarta Sans',sans-serif;font-size:2.5rem;font-weight:800;margin:0 0 10px;line-height:1.2;text-shadow:0 2px 20px rgba(0,0,0,.2)}
    .exams-hero-sub{margin:0 0 28px;color:rgba(255,255,255,.7);font-size:1.08rem;max-width:600px}
    .exams-hero-sub strong{color:#fff;font-weight:700}
    .exams-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;position:relative;z-index:1}
    .exam-stat{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:14px;padding:18px 16px;text-align:center;backdrop-filter:blur(8px);transition:all .3s;text-decoration:none;color:inherit}
    .exam-stat:hover{background:rgba(255,255,255,.16);transform:translateY(-2px)}
    .exam-stat-val{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.6rem;font-weight:800;color:#fff;display:block;line-height:1}
    .exam-stat-lbl{font-size:.72rem;color:rgba(255,255,255,.6);margin-top:4px;text-transform:uppercase;letter-spacing:.5px;font-weight:600}
    .exam-stat i{font-size:1.4rem;color:rgba(255,255,255,.5);margin-bottom:6px;display:block}
    @media(max-width:768px){
      .exams-hero{padding:60px 0 36px}
      .exams-hero h1{font-size:1.6rem}
      .exams-hero-sub{font-size:.95rem}
      .exams-stats{grid-template-columns:repeat(3,1fr);gap:10px}
      .exam-stat{padding:14px 10px}
      .exam-stat-val{font-size:1.3rem}
    }
    @media(max-width:480px){
      .exams-stats{grid-template-columns:repeat(2,1fr)}
    }
    .exams-layout{display:grid;grid-template-columns:260px 1fr;gap:28px;padding:36px 0 60px}
    .exams-main{min-width:0}
    .exams-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:8px}
    .exams-head h2{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.35rem;font-weight:800;color:#0B2447;margin:0}
    .exams-count{background:rgba(11,36,71,.06);color:#0B2447;padding:5px 14px;border-radius:20px;font-size:.82rem;font-weight:700;white-space:nowrap}
    .exams-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px}
    .exam-card{background:#fff;border-radius:16px;border:1.5px solid rgba(15,23,42,.06);padding:0;transition:all .25s;overflow:hidden;display:flex;flex-direction:column}
    .exam-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.08);border-color:rgba(37,99,235,.2)}
    .exam-card-top{padding:24px 24px 16px;display:flex;align-items:flex-start;gap:14px}
    .exam-logo{width:60px;height:60px;border-radius:14px;object-fit:contain;background:#f8fafc;border:1.5px solid rgba(15,23,42,.06);padding:6px;flex-shrink:0}
    .exam-info{flex:1;min-width:0}
    .exam-name{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.05rem;font-weight:800;color:#0B2447;margin:0 0 4px;line-height:1.3}
    .exam-name a{color:inherit;text-decoration:none}
    .exam-name a:hover{color:#2563eb}
    .exam-abbr-tag{display:inline-block;font-size:.75rem;color:#64748b;background:#f1f5f9;padding:2px 10px;border-radius:6px;font-weight:600}
    .exam-meta{display:grid;grid-template-columns:1fr 1fr;gap:10px 16px;padding:0 24px 20px;flex:1}
    .exam-meta-item{display:flex;align-items:center;gap:7px;font-size:.83rem;color:rgba(15,23,42,.6)}
    .exam-meta-item i{color:#2563eb;font-size:1rem;flex-shrink:0}
    .exam-footer{border-top:1.5px solid rgba(15,23,42,.05);padding:14px 24px;display:flex;justify-content:space-between;align-items:center;background:#fafbfc}
    .exam-by{font-size:.78rem;color:rgba(15,23,42,.4);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px}
    .exam-detail-btn{display:inline-flex;align-items:center;gap:5px;padding:9px 20px;border-radius:10px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .2s;white-space:nowrap}
    .exam-detail-btn:hover{box-shadow:0 6px 16px rgba(11,36,71,.25);transform:translateY(-1px)}

    .exams-empty{text-align:center;padding:60px 20px;color:#94a3b8}
    .exams-empty i{font-size:3rem;margin-bottom:12px;display:block}
    .exams-empty h3{font-size:1.1rem;color:#64748b;margin:0 0 6px}
    .exams-empty p{font-size:.9rem;margin:0}

    /* Sidebar filters */
    .exam-filter-card{background:#fff;border-radius:14px;border:1.5px solid rgba(15,23,42,.06);padding:20px;position:sticky;top:100px}
    .exam-filter-card h3{font-size:1rem;font-weight:800;color:#0B2447;margin:0 0 16px;display:flex;align-items:center;gap:8px}
    .exam-filter-group{margin-bottom:20px}
    .exam-filter-group:last-child{margin-bottom:0}
    .exam-filter-title{font-size:.78rem;font-weight:700;color:rgba(15,23,42,.45);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px}
    .exam-filter-list{list-style:none;padding:0;margin:0}
    .exam-filter-list li{margin:0}
    .exam-filter-list a{display:flex;align-items:center;gap:8px;padding:9px 14px;border-radius:10px;font-size:.88rem;color:rgba(15,23,42,.65);text-decoration:none;font-weight:500;transition:all .2s}
    .exam-filter-list a:hover{background:rgba(37,99,235,.05);color:#2563eb}
    .exam-filter-list a.active{background:rgba(37,99,235,.08);color:#2563eb;font-weight:700}
    .exam-filter-list a i{font-size:1rem;opacity:.5}
    .exam-filter-list a.active i{opacity:1}

    @media(max-width:768px){
      .exams-layout{grid-template-columns:1fr;gap:0}
      .exams-grid{grid-template-columns:1fr}
      .exams-head{flex-direction:column;align-items:flex-start}
      .exams-hero h1{font-size:1.5rem}
      .exams-hero-sub{font-size:.92rem}
      .exams-stats{grid-template-columns:repeat(2,1fr);gap:8px}
      .exam-stat{padding:12px 10px}
      .exam-stat-val{font-size:1.2rem}
      .exams-filter-toggle{display:flex}
      aside{display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:200;background:rgba(0,0,0,.4);padding:0}
      aside.open{display:flex;align-items:flex-end}
      aside .exam-filter-card{position:static;border-radius:16px 16px 0 0;max-height:80vh;overflow-y:auto;width:100%;box-shadow:0 -4px 24px rgba(0,0,0,.15);animation:slideUp .3s ease}
      .filter-close{display:flex}
    }
    @media(max-width:480px){
      .exams-stats{grid-template-columns:repeat(2,1fr)}
      .exam-stat-val{font-size:1rem}
      .exam-stat-lbl{font-size:.65rem}
    }
    .exams-filter-toggle{
      display:none;align-items:center;gap:6px;padding:10px 20px;
      border-radius:12px;border:1.5px solid rgba(15,23,42,.1);background:#fff;
      font-size:.85rem;font-weight:700;color:#0B2447;cursor:pointer;
      transition:all .2s
    }
    .exams-filter-toggle:hover{border-color:#2563eb;color:#2563eb}
    .filter-close{
      display:none;position:absolute;top:12px;right:12px;width:32px;height:32px;
      border-radius:8px;background:rgba(15,23,42,.06);border:none;cursor:pointer;
      align-items:center;justify-content:center;font-size:1rem;color:#0f172a;z-index:1
    }
    @keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Hero -->
<div class="exams-hero" style="background-image:url('https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=1920&q=80')">
  <div class="container">
    <div class="exams-breadcrumb">
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/index.php">Home</a>
      <i class="ph ph-caret-right"></i>
      <span>Exams</span>
    </div>
    <h1>Top Entrance Exams in India</h1>
    <p class="exams-hero-sub">Explore engineering, medical, management, law & other entrance exams<?= $level !== 'all' ? ' — showing <strong>' . htmlspecialchars($levelLabels[$level] ?? $level) . '</strong> exams' : '' ?></p>
    <div class="exams-stats">
      <a href="<?= examsUrl() ?>" class="exam-stat">
        <i class="ph ph-stack"></i>
        <span class="exam-stat-val"><?= number_format($stats['total']) ?>+</span>
        <span class="exam-stat-lbl">Total Exams</span>
      </a>
      <a href="<?= examsUrl(['level'=>'national']) ?>" class="exam-stat">
        <i class="ph ph-flag"></i>
        <span class="exam-stat-val"><?= number_format($stats['national']) ?>+</span>
        <span class="exam-stat-lbl">National Level</span>
      </a>
      <a href="<?= examsUrl(['level'=>'state']) ?>" class="exam-stat">
        <i class="ph ph-map-pin"></i>
        <span class="exam-stat-val"><?= number_format($stats['state']) ?>+</span>
        <span class="exam-stat-lbl">State Level</span>
      </a>
      <a href="<?= examsUrl(['mode'=>'online']) ?>" class="exam-stat">
        <i class="ph ph-monitor"></i>
        <span class="exam-stat-val"><?= number_format($stats['online']) ?>+</span>
        <span class="exam-stat-lbl">Online Exams</span>
      </a>
      <a href="<?= examsUrl(['mode'=>'offline']) ?>" class="exam-stat">
        <i class="ph ph-building-office"></i>
        <span class="exam-stat-val"><?= number_format($stats['offline']) ?>+</span>
        <span class="exam-stat-lbl">Offline Exams</span>
      </a>
    </div>
  </div>
</div>

<div class="container">
  <div class="exams-layout">

    <button class="exams-filter-toggle" onclick="document.querySelector('aside').classList.toggle('open')"><i class="ph ph-funnel"></i> Filters</button>

    <!-- Sidebar -->
    <aside>
      <div class="exam-filter-card">
        <button class="filter-close" onclick="this.closest('aside').classList.remove('open')"><i class="ph ph-x"></i></button>
        <h3><i class="ph ph-funnel"></i> Filter Exams</h3>

        <div class="exam-filter-group">
          <div class="exam-filter-title">Exam Level</div>
          <ul class="exam-filter-list">
            <li><a href="<?= examsUrl(['level'=>'all','mode'=>$mode,'q'=>$search]) ?>" class="<?= $level==='all'?'active':'' ?>"><i class="ph ph-stack"></i> All Levels</a></li>
            <li><a href="<?= examsUrl(['level'=>'national','mode'=>$mode,'q'=>$search]) ?>" class="<?= $level==='national'?'active':'' ?>"><i class="ph ph-flag"></i> National Level</a></li>
            <li><a href="<?= examsUrl(['level'=>'state','mode'=>$mode,'q'=>$search]) ?>" class="<?= $level==='state'?'active':'' ?>"><i class="ph ph-map-pin"></i> State Level</a></li>
            <li><a href="<?= examsUrl(['level'=>'university','mode'=>$mode,'q'=>$search]) ?>" class="<?= $level==='university'?'active':'' ?>"><i class="ph ph-buildings"></i> University Level</a></li>
          </ul>
        </div>

        <div class="exam-filter-group">
          <div class="exam-filter-title">Exam Mode</div>
          <ul class="exam-filter-list">
            <li><a href="<?= examsUrl(['mode'=>'all','level'=>$level,'q'=>$search]) ?>" class="<?= $mode==='all'?'active':'' ?>"><i class="ph ph-squares-four"></i> All Modes</a></li>
            <li><a href="<?= examsUrl(['mode'=>'online','level'=>$level,'q'=>$search]) ?>" class="<?= $mode==='online'?'active':'' ?>"><i class="ph ph-laptop"></i> Online (CBT)</a></li>
            <li><a href="<?= examsUrl(['mode'=>'offline','level'=>$level,'q'=>$search]) ?>" class="<?= $mode==='offline'?'active':'' ?>"><i class="ph ph-pencil-simple"></i> Offline (Pen-Paper)</a></li>
          </ul>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <main class="exams-main">
      <div class="exams-head">
        <h2>Showing <?= count($exams) ?> Exam<?= count($exams) !== 1 ? 's' : '' ?></h2>
        <?php if ($level !== 'all' || $mode !== 'all' || $search !== ''): ?>
        <a href="exams.php" style="font-size:.82rem;color:#2563eb;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:4px"><i class="ph ph-x-circle"></i> Clear Filters</a>
        <?php endif; ?>
      </div>

      <?php if (!empty($exams)): ?>
      <div class="exams-grid">
        <?php foreach ($exams as $ex): ?>
        <div class="exam-card">
          <div class="exam-card-top">
            <img src="<?= cImg($ex['conducting_body_logo']) ?>" class="exam-logo" alt="<?= htmlspecialchars($ex['exam_abbreviation'] ?? $ex['exam_name'] ?? '') ?>">
            <div class="exam-info">
              <h3 class="exam-name"><a href="<?= examUrl($ex['exam_slug']) ?>"><?= htmlspecialchars($ex['exam_name']) ?></a></h3>
              <span class="exam-abbr-tag"><?= htmlspecialchars($ex['exam_abbreviation'] ?? '') ?></span>
            </div>
          </div>
          <div class="exam-meta">
            <div class="exam-meta-item"><i class="ph ph-bank"></i> <?= ucfirst($ex['exam_level']) ?> Level</div>
            <div class="exam-meta-item"><i class="ph ph-laptop"></i> <?= ucfirst($ex['exam_mode']) ?> Mode</div>
            <?php if ($ex['applicants_last_year']): ?>
            <div class="exam-meta-item"><i class="ph ph-users"></i> <?= number_format($ex['applicants_last_year']/100000, 1) ?>L+ Applicants</div>
            <?php endif; ?>
            <?php if ($ex['duration_minutes']): ?>
            <div class="exam-meta-item"><i class="ph ph-timer"></i> <?= $ex['duration_minutes'] ?> Mins</div>
            <?php endif; ?>
          </div>
          <div class="exam-footer">
            <span class="exam-by">By <?= htmlspecialchars($ex['conducting_body'] ?? 'N/A') ?></span>
            <a href="<?= examUrl($ex['exam_slug']) ?>" class="exam-detail-btn">View Details <i class="ph ph-arrow-right"></i></a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="exams-empty">
        <i class="ph ph-magnifying-glass"></i>
        <h3>No exams found</h3>
        <p>Try adjusting your filters or search query.</p>
      </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/js/main.js"></script>
<script>
document.querySelector('aside')?.addEventListener('click', function(e) {
  if (e.target === this) this.classList.remove('open');
});
</script>
</body>
</html>
