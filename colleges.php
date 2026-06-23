<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$type   = $_GET['type'] ?? 'all';
$state  = isset($_GET['state']) ? (int)$_GET['state'] : 0;
$search = trim($_GET['q'] ?? '');
$course = trim($_GET['course'] ?? '');
$sort   = $_GET['sort'] ?? 'featured';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$validTypes = ['all', 'govt', 'private', 'deemed', 'autonomous'];
if (!in_array($type, $validTypes, true)) $type = 'all';

$where = ["c.status = 'active'"];
$params = [];

if ($type !== 'all') {
    $where[] = 'c.college_type = :type';
    $params['type'] = $type;
}
if ($state > 0) {
    $where[] = 'c.state_id = :state';
    $params['state'] = $state;
}
if ($course !== '') {
    $where[] = 'EXISTS (SELECT 1 FROM college_courses cc WHERE cc.college_id = c.id AND cc.course_name LIKE :course)';
    $params['course'] = '%' . $course . '%';
}
if ($search !== '') {
    $where[] = '(c.name LIKE :q OR c.slug LIKE :q OR ci.name LIKE :q OR s.name LIKE :q)';
    $params['q'] = '%' . $search . '%';
}

$whereSql = implode(' AND ', $where);

$orderMap = [
    'featured' => 'c.is_featured DESC, c.overall_rating_avg DESC, c.ranking_nirf ASC, c.name ASC',
    'rating'   => 'c.overall_rating_avg DESC, c.name ASC',
    'nirf'     => 'c.ranking_nirf ASC, c.name ASC',
    'name'     => 'c.name ASC',
    'newest'   => 'c.created_at DESC',
];
$orderSql = $orderMap[$sort] ?? $orderMap['featured'];

$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM colleges c
    LEFT JOIN cities ci ON c.city_id = ci.id
    LEFT JOIN states s ON c.state_id = s.id
    WHERE {$whereSql}
");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = "
    SELECT c.id, c.name, c.slug, c.college_type, c.ownership, c.naac_grade, c.ranking_nirf,
           c.overall_rating_avg, c.total_reviews, c.established_year, c.founded_year,
           c.is_verified, c.is_featured, c.total_students, c.campus_area_acres,
           c.ugc_approved, c.aicte_approved,
           s.name AS state_name, ci.name AS city_name,
           cm.logo_url, cm.cover_image_url,
           (SELECT MIN(cc.annual_fee) FROM college_courses cc WHERE cc.college_id = c.id AND cc.annual_fee > 0) AS min_fee,
           (SELECT MAX(cp.avg_package_lpa) FROM college_placements cp WHERE cp.college_id = c.id) AS avg_package,
           (SELECT COUNT(*) FROM college_courses cc WHERE cc.college_id = c.id) AS total_courses
    FROM colleges c
    LEFT JOIN states s ON c.state_id = s.id
    LEFT JOIN cities ci ON c.city_id = ci.id
    LEFT JOIN college_media cm ON cm.college_id = c.id AND (cm.image_type IS NULL OR cm.image_type = 'cover')
    WHERE {$whereSql}
    ORDER BY {$orderSql}
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);

$states = cAll($pdo, "SELECT s.id, s.name, COUNT(c.id) AS cnt FROM states s LEFT JOIN colleges c ON c.state_id = s.id AND c.status='active' GROUP BY s.id, s.name ORDER BY cnt DESC, s.name ASC LIMIT 30");
$totalPages = max(1, (int)ceil($total / $perPage));

// Quick stats
$stats = [
    'total'   => cCol($pdo, "SELECT COUNT(*) FROM colleges WHERE status='active'"),
    'govt'    => cCol($pdo, "SELECT COUNT(*) FROM colleges WHERE status='active' AND college_type='govt'"),
    'private' => cCol($pdo, "SELECT COUNT(*) FROM colleges WHERE status='active' AND college_type='private'"),
    'states'  => cCol($pdo, "SELECT COUNT(DISTINCT state_id) FROM colleges WHERE status='active'"),
];

$pageTitle = 'Colleges in India ' . date('Y') . ' — Fees, Rankings, Admissions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - AdmissionSeason</title>
  <meta name="description" content="Explore <?= number_format($total) ?>+ colleges in India. Compare fees, rankings, placements and admission details.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/college-pages.css?v=<?= time() ?>">
  <style>
    /* Colleges-page extras */
    .cl-stats-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:24px;position:relative;z-index:1}
    .cl-stat{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
      border-radius:12px;padding:16px 20px;backdrop-filter:blur(10px);text-align:center}
    .cl-stat-val{font-size:1.5rem;font-weight:800;color:#fff;font-family:'Plus Jakarta Sans',sans-serif}
    .cl-stat-lbl{font-size:.75rem;color:rgba(255,255,255,.7);margin-top:2px;text-transform:uppercase;letter-spacing:.5px}
    .sort-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;
      padding:14px 24px;background:#f8fafc;border-bottom:1px solid rgba(15,23,42,0.08);font-size:.85rem}
    .sort-bar label{color:rgba(15,23,42,0.45);font-weight:500}
    .sort-bar select{padding:6px 12px;border:1.5px solid rgba(15,23,42,0.08);border-radius:8px;
      font-size:.83rem;background:#fff;cursor:pointer;font-family:inherit}
    .sort-result-count{margin-left:auto;color:rgba(15,23,42,0.4);font-size:.82rem}
    .clc-featured-badge{
      position:absolute;top:10px;right:10px;
      background:linear-gradient(135deg,#19376D,#0F172A);
      color:#fff;font-size:.65rem;font-weight:700;
      padding:3px 9px;border-radius:6px;text-transform:uppercase;letter-spacing:.5px;
    }
    .col-type-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:0}
    .col-type-btn{
      padding:8px 20px;border-radius:100px;font-size:.83rem;font-weight:600;
      text-decoration:none;border:1.5px solid rgba(15,23,42,0.08);color:rgba(15,23,42,0.45);
      transition:all .2s;white-space:nowrap;
    }
    .col-type-btn:hover,.col-type-btn.active{
      background:linear-gradient(135deg,#19376D,#19376D);color:#fff;border-color:transparent;
      box-shadow:0 4px 12px rgba(37,99,235,.3);
    }
    @media(max-width:768px){
      .cl-stats-bar{grid-template-columns:repeat(2,1fr)}
      .sort-result-count{display:none}
      .col-filter-toggle{display:flex}
      .shiksha-sidebar{display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:200;background:rgba(0,0,0,.4);padding:0}
      .shiksha-sidebar.open{display:flex;align-items:flex-end}
      .shiksha-sidebar .shiksha-widget{position:static;border-radius:16px 16px 0 0;max-height:80vh;overflow-y:auto;width:100%;box-shadow:0 -4px 24px rgba(0,0,0,.15);animation:slideUp .3s ease}
      .shiksha-sidebar .shiksha-widget-wrapper{display:flex;flex-direction:column;gap:0;background:#fff;padding:20px;border-radius:16px 16px 0 0}
      .col-filter-close{display:flex}
      .college-filter-bar .college-search-form{flex-direction:column}
      .college-filter-bar .college-search-form select,
      .college-filter-bar .college-search-form button{width:100%}
    }
    @media(max-width:480px){.cl-stats-bar{grid-template-columns:1fr 1fr}}
    .col-filter-toggle{
      display:none;align-items:center;gap:6px;padding:10px 20px;
      border-radius:12px;border:1.5px solid rgba(15,23,42,.1);background:#fff;
      font-size:.85rem;font-weight:700;color:#0B2447;cursor:pointer;transition:all .2s
    }
    .col-filter-toggle:hover{border-color:#2563eb;color:#2563eb}
    .col-filter-close{
      display:none;position:absolute;top:12px;right:12px;width:32px;height:32px;
      border-radius:8px;background:rgba(15,23,42,.06);border:none;cursor:pointer;
      align-items:center;justify-content:center;font-size:1rem;color:#0f172a;z-index:1
    }
    @keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Hero Header ───────────────────────────────────────────────── -->
<div class="shiksha-header">
  <div class="container">
    <div class="shiksha-breadcrumb">
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/index.php">Home</a>
      <i class="ph ph-caret-right"></i>
      <span>Colleges</span>
    </div>
    <h1 class="shiksha-title">Find Your Dream College in India</h1>
    <p class="college-list-sub"><?= number_format($total) ?> colleges found<?= $course ? ' offering <strong>' . htmlspecialchars($course) . '</strong>' : '' ?><?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?><?= $state > 0 ? ' in ' . htmlspecialchars(array_column($states, 'name', 'id')[$state] ?? '') : '' ?></p>
    <!-- Quick stats -->
    <div class="cl-stats-bar">
      <div class="cl-stat">
        <div class="cl-stat-val"><?= number_format($stats['total']) ?>+</div>
        <div class="cl-stat-lbl">Total Colleges</div>
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

<!-- ── Type Filter Tabs ──────────────────────────────────────────── -->
<div class="shiksha-tabs-nav">
  <div class="container">
    <div class="shiksha-tabs">
      <?php foreach (['all' => 'All Colleges', 'govt' => '🏛️ Government', 'private' => '🏢 Private', 'deemed' => '🎓 Deemed', 'autonomous' => '⚙️ Autonomous'] as $k => $label): ?>
      <a href="<?= collegesUrl(array_filter(['type' => $k !== 'all' ? $k : null, 'state' => $state ?: null, 'q' => $search ?: null, 'course' => $course ?: null, 'sort' => $sort !== 'featured' ? $sort : null])) ?>"
         class="<?= $type === $k ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ── Main Content ──────────────────────────────────────────────── -->
<div class="container shiksha-main-wrapper">
  <div class="shiksha-layout">

    <!-- Content -->
    <main class="shiksha-content college-list-main">

      <!-- Search + Filter Bar -->
      <div class="college-filter-bar">
        <form method="get" class="college-search-form">
          <?php if ($type !== 'all'): ?><input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>"><?php endif; ?>
          <?php if ($course !== ''): ?><input type="hidden" name="course" value="<?= htmlspecialchars($course) ?>"><?php endif; ?>
          <?php if ($sort !== 'featured'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search college, city or state…" id="college-search-input">
          <select name="state">
            <option value="">All States</option>
            <?php foreach ($states as $st): ?>
            <option value="<?= (int)$st['id'] ?>" <?= $state === (int)$st['id'] ? 'selected' : '' ?>><?= htmlspecialchars($st['name']) ?> (<?= (int)$st['cnt'] ?>)</option>
            <?php endforeach; ?>
          </select>
          <button type="submit"><i class="ph ph-magnifying-glass"></i> Search</button>
        </form>
      </div>

      <?php if ($course !== ''): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:12px 24px;background:#fff;border-bottom:1px solid rgba(15,23,42,0.06);">
        <span style="font-size:.85rem;color:rgba(15,23,42,0.5);font-weight:600;">Filtered by course:</span>
        <span style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;padding:6px 16px;border-radius:20px;font-size:.85rem;font-weight:700;">
          <i class="ph ph-graduation-cap"></i> <?= htmlspecialchars($course) ?>
          <a href="<?= collegesUrl(array_filter(['type'=>$type!=='all'?$type:null,'state'=>$state?:null,'q'=>$search?:null,'sort'=>$sort!=='featured'?$sort:null])) ?>" style="color:#fff;text-decoration:none;margin-left:4px;opacity:.8;" title="Remove filter"><i class="ph ph-x-circle"></i></a>
        </span>
      </div>
      <?php endif; ?>

      <!-- Sort bar -->
      <div class="sort-bar">
        <label>Sort by:</label>
        <select onchange="window.location=this.value" id="sort-select">
          <?php foreach (['featured'=>'Featured First','rating'=>'Top Rated','nirf'=>'NIRF Rank','name'=>'Name A-Z','newest'=>'Newest'] as $sk=>$sl): ?>
          <option value="<?= collegesUrl(array_filter(['type'=>$type!=='all'?$type:null,'state'=>$state?:null,'q'=>$search?:null,'course'=>$course?:null,'sort'=>$sk,'page'=>null])) ?>" <?= $sort===$sk?'selected':'' ?>><?= $sl ?></option>
          <?php endforeach; ?>
        </select>
        <span class="sort-result-count">Showing <?= (($page-1)*$perPage)+1 ?>–<?= min($page*$perPage,$total) ?> of <?= number_format($total) ?> colleges</span>
      </div>

      <!-- College cards -->
      <?php if (empty($colleges)): ?>
        <div class="shiksha-empty">
          <i class="ph ph-buildings" style="font-size:3rem;color:rgba(15,23,42,0.08);display:block;margin-bottom:12px"></i>
          <p>No colleges found. Try adjusting your filters.</p>
        </div>
      <?php else: ?>
        <div class="college-grid-list">
          <?php foreach ($colleges as $cl):
            $year = $cl['established_year'] ?? $cl['founded_year'] ?? '';
            $rating = (float)($cl['overall_rating_avg'] ?? 0);
            $ownMap = ['central'=>'Central','state'=>'State Govt','private_trust'=>'Trust','minority'=>'Minority'];
            $ownershipLabel = $ownMap[$cl['ownership'] ?? ''] ?? '';
          ?>
          <a href="<?= collegeUrl($cl['slug']) ?>" class="college-list-card">
            <!-- Featured badge -->
            <?php if (!empty($cl['is_featured'])): ?>
            <span class="clc-featured-badge" style="position:absolute;top:12px;right:12px;z-index:2">⭐ Featured</span>
            <?php endif; ?>

            <!-- Image -->
            <div class="clc-img">
              <img src="<?= cImg($cl['cover_image_url']) ?>" alt="<?= htmlspecialchars($cl['name']) ?>" loading="lazy">
              <div class="clc-img-badges">
                <?php if ($cl['naac_grade']): ?><span class="clc-badge">NAAC <?= htmlspecialchars($cl['naac_grade']) ?></span><?php endif; ?>
                <?php if (!empty($cl['is_verified'])): ?><span class="clc-badge clc-badge-verified"><i class="ph-fill ph-seal-check"></i> Verified</span><?php endif; ?>
              </div>
            </div>

            <!-- Body -->
            <div class="clc-body">
              <div class="clc-top">
                <?php if ($cl['logo_url']): ?><img src="<?= cImg($cl['logo_url']) ?>" class="clc-logo" alt=""><?php endif; ?>
                <div style="flex:1;min-width:0">
                  <h3><?= htmlspecialchars($cl['name']) ?></h3>
                  <div class="clc-meta">
                    <?php if ($cl['city_name'] || $cl['state_name']): ?>
                    <span><i class="ph ph-map-pin"></i><?= htmlspecialchars(trim(($cl['city_name'] ?? '') . ($cl['city_name'] && $cl['state_name'] ? ', ' : '') . ($cl['state_name'] ?? ''))) ?></span>
                    <?php endif; ?>
                    <?php if ($year): ?><span><i class="ph ph-calendar"></i>Est. <?= htmlspecialchars((string)$year) ?></span><?php endif; ?>
                    <?php if (!empty($cl['total_courses'])): ?><span><i class="ph ph-book-open"></i><?= (int)$cl['total_courses'] ?> Courses</span><?php endif; ?>
                  </div>
                  <div class="clc-chips">
                    <span class="clc-chip"><?= htmlspecialchars(collegeTypeLabel($cl['college_type'], $cl['ownership'])) ?></span>
                    <?php if ($ownershipLabel): ?><span class="clc-chip"><?= $ownershipLabel ?></span><?php endif; ?>
                    <?php if (!empty($cl['ugc_approved'])): ?><span class="clc-chip chip-green">UGC ✓</span><?php endif; ?>
                    <?php if (!empty($cl['aicte_approved'])): ?><span class="clc-chip chip-green">AICTE ✓</span><?php endif; ?>
                    <?php if (!empty($cl['ranking_nirf'])): ?><span class="clc-chip chip-orange">NIRF #<?= (int)$cl['ranking_nirf'] ?></span><?php endif; ?>
                  </div>
                </div>
              </div>
              <!-- Stats row -->
              <div class="clc-stats">
                <div>
                  <strong><?= $rating > 0 ? number_format($rating, 1) . '/5 ★' : '—' ?></strong>
                  <span>Rating</span>
                </div>
                <div>
                  <strong><?= formatFee(isset($cl['min_fee']) ? (float)$cl['min_fee'] : null) ?></strong>
                  <span>Min Fee/Yr</span>
                </div>
                <div>
                  <strong><?= formatLpa(isset($cl['avg_package']) ? (float)$cl['avg_package'] : null) ?></strong>
                  <span>Avg Package</span>
                </div>
                <?php if (!empty($cl['total_courses'])): ?>
                <div>
                  <strong><?= (int)$cl['total_courses'] ?></strong>
                  <span>Courses</span>
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
          <a href="<?= collegesUrl(array_filter(['type'=>$type!=='all'?$type:null,'state'=>$state?:null,'q'=>$search?:null,'sort'=>$sort!=='featured'?$sort:null,'page'=>$page-1])) ?>" class="pager-link">← Prev</a>
          <?php endif; ?>
          <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+4); $p++): ?>
          <a href="<?= collegesUrl(array_filter(['type'=>$type!=='all'?$type:null,'state'=>$state?:null,'q'=>$search?:null,'sort'=>$sort!=='featured'?$sort:null,'page'=>$p])) ?>" class="pager-link<?= $p===$page?' active':'' ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
          <a href="<?= collegesUrl(array_filter(['type'=>$type!=='all'?$type:null,'state'=>$state?:null,'q'=>$search?:null,'sort'=>$sort!=='featured'?$sort:null,'page'=>$page+1])) ?>" class="pager-link">Next →</a>
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
        <h4 class="shiksha-widget-title">🗺️ Browse by State</h4>
        <ul class="shiksha-widget-list">
          <?php foreach (array_slice($states, 0, 14) as $st): ?>
          <li><a href="<?= collegesUrl(['state' => $st['id']]) ?>">
            <?= htmlspecialchars($st['name']) ?>
            <span><?= (int)$st['cnt'] ?></span>
          </a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="shiksha-widget">
        <h4 class="shiksha-widget-title">🔗 Quick Filters</h4>
        <ul class="shiksha-widget-list">
          <li><a href="<?= collegesUrl(['type' => 'govt']) ?>"><span style="margin-right:auto">Government Colleges</span></a></li>
          <li><a href="<?= collegesUrl(['type' => 'private']) ?>"><span style="margin-right:auto">Private Colleges</span></a></li>
          <li><a href="<?= collegesUrl(['type' => 'deemed']) ?>"><span style="margin-right:auto">Deemed Universities</span></a></li>
          <li><a href="<?= collegesUrl(['sort' => 'rating']) ?>"><span style="margin-right:auto">Top Rated Colleges</span></a></li>
          <li><a href="<?= collegesUrl(['sort' => 'nirf']) ?>"><span style="margin-right:auto">NIRF Rankings</span></a></li>
          <li><a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/news.php"><span style="margin-right:auto">College News</span></a></li>
        </ul>
      </div>

      <div class="shiksha-widget" style="background:linear-gradient(135deg,rgba(11,36,71,0.06),rgba(11,36,71,0.04));border-color:rgba(79,70,229,.2)">
        <h4 class="shiksha-widget-title" style="color:#19376D">📬 Get Admission Alerts</h4>
        <p style="font-size:.85rem;color:rgba(15,23,42,0.65);margin-bottom:12px">Stay updated with college deadlines, exam dates &amp; cutoffs.</p>
        <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/signup.php" style="display:block;text-align:center;padding:10px;background:linear-gradient(135deg,#19376D,#0B2447);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:.87rem">
          Create Free Account →
        </a>
      </div>
      </div><!-- /shiksha-widget-wrapper -->
    </aside>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/js/main.js"></script>
<script>
document.querySelector('.shiksha-sidebar')?.addEventListener('click', function(e) {
  if (e.target === this) this.classList.remove('open');
});
</script>
</body>
</html>
