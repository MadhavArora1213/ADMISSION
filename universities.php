<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';
require_once __DIR__ . '/includes/university_helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$type   = $_GET['type'] ?? 'all';
$sort   = $_GET['sort'] ?? 'featured';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;
$offset = ($page - 1) * $perPage;

$validTypes = ['all', 'govt', 'private', 'deemed', 'autonomous'];
if (!in_array($type, $validTypes, true)) $type = 'all';

$where = ["u.status = 'active'"];
$params = [];

if ($type !== 'all') {
    $where[] = 'u.university_type = :type';
    $params['type'] = $type;
}

$whereSql = implode(' AND ', $where);

$orderMap = [
    'featured'   => 'u.is_featured DESC, u.ranking_nirf ASC, u.overall_rating_avg DESC, u.name ASC',
    'rating'     => 'u.overall_rating_avg DESC, u.name ASC',
    'nirf'       => 'u.ranking_nirf ASC, u.name ASC',
    'name'       => 'u.name ASC',
    'established' => 'u.established_year ASC, u.name ASC',
];
$orderSql = $orderMap[$sort] ?? $orderMap['featured'];

$countSql = "SELECT COUNT(*) FROM universities u WHERE {$whereSql}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = "
    SELECT u.id, u.name, u.slug, u.university_type, u.ownership, u.naac_grade, u.ranking_nirf,
           u.overall_rating_avg, u.total_reviews, u.established_year, u.founded_year,
           u.is_verified, u.is_featured, u.total_students, u.total_faculty, u.campus_area_acres,
           u.ugc_approved, u.aicte_approved, u.nba_approved,
           s.name AS state_name, ci.name AS city_name,
           um.logo_url, um.cover_image_url,
           (SELECT MIN(uc.annual_fee) FROM university_courses uc WHERE uc.university_id = u.id AND uc.annual_fee > 0) AS min_fee,
           (SELECT MAX(up.avg_package_lpa) FROM university_placements up WHERE up.university_id = u.id) AS avg_package,
           (SELECT COUNT(*) FROM university_courses uc WHERE uc.university_id = u.id) AS total_courses
    FROM universities u
    LEFT JOIN states s ON u.state_id = s.id
    LEFT JOIN cities ci ON u.city_id = ci.id
    LEFT JOIN university_media um ON um.university_id = u.id AND um.image_type IS NULL
    WHERE {$whereSql}
    ORDER BY {$orderSql}
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$universities = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = max(1, (int)ceil($total / $perPage));

$stats = [
    'total'   => cCol($pdo, "SELECT COUNT(*) FROM universities WHERE status='active'"),
    'govt'    => cCol($pdo, "SELECT COUNT(*) FROM universities WHERE status='active' AND university_type='govt'"),
    'private' => cCol($pdo, "SELECT COUNT(*) FROM universities WHERE status='active' AND university_type='private'"),
    'states'  => cCol($pdo, "SELECT COUNT(DISTINCT state_id) FROM universities WHERE status='active'"),
];

$navBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$pageTitle = 'Universities in India ' . date('Y') . ' — Courses, Fees, Placements';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> - AdmissionSeason</title>
<meta name="description" content="Explore <?= number_format($total) ?>+ universities in India. Compare fees, rankings, placements and admission details.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link rel="stylesheet" href="<?= $navBase ?>/assets/css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= $navBase ?>/assets/css/college-pages.css?v=<?= time() ?>">
<style>
  .cl-stats-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:24px;position:relative;z-index:1}
  .cl-stat{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:16px 20px;backdrop-filter:blur(10px);text-align:center}
  .cl-stat-val{font-size:1.5rem;font-weight:800;color:#fff;font-family:'Plus Jakarta Sans',sans-serif}
  .cl-stat-lbl{font-size:.75rem;color:rgba(255,255,255,.7);margin-top:2px;text-transform:uppercase;letter-spacing:.5px}
  .sort-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:14px 24px;background:#f8fafc;border-bottom:1px solid rgba(15,23,42,0.08);font-size:.85rem}
  .sort-bar label{color:rgba(15,23,42,0.45);font-weight:500}
  .sort-bar select{padding:6px 12px;border:1.5px solid rgba(15,23,42,0.08);border-radius:8px;font-size:.83rem;background:#fff;cursor:pointer;font-family:inherit}
  .sort-result-count{margin-left:auto;color:rgba(15,23,42,0.4);font-size:.82rem}
  .clc-featured-badge{position:absolute;top:10px;right:10px;background:linear-gradient(135deg,#19376D,#0F172A);color:#fff;font-size:.65rem;font-weight:700;padding:3px 9px;border-radius:6px;text-transform:uppercase;letter-spacing:.5px}
  .col-type-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:0}
  .col-type-btn{padding:8px 20px;border-radius:100px;font-size:.83rem;font-weight:600;text-decoration:none;border:1.5px solid rgba(15,23,42,0.08);color:rgba(15,23,42,0.45);transition:all .2s;white-space:nowrap}
  .col-type-btn:hover,.col-type-btn.active{background:linear-gradient(135deg,#19376D,#19376D);color:#fff;border-color:transparent;box-shadow:0 4px 12px rgba(37,99,235,.3)}
  .col-filter-toggle{display:none;align-items:center;gap:6px;padding:10px 20px;border-radius:12px;border:1.5px solid rgba(15,23,42,.1);background:#fff;font-size:.85rem;font-weight:700;color:#0B2447;cursor:pointer;transition:all .2s}
  .col-filter-toggle:hover{border-color:#2563eb;color:#2563eb}
  .col-filter-close{display:none;position:absolute;top:12px;right:12px;width:32px;height:32px;border-radius:8px;background:rgba(15,23,42,.06);border:none;cursor:pointer;align-items:center;justify-content:center;font-size:1rem;color:#0f172a;z-index:1}
  @media(max-width:768px){
    .cl-stats-bar{grid-template-columns:repeat(2,1fr);gap:10px;margin-top:16px}
    .cl-stat{padding:12px 14px}
    .cl-stat-val{font-size:1.2rem}
    .cl-stat-lbl{font-size:.68rem}
    .sort-result-count{display:none}
    .sort-bar{flex-direction:column;align-items:stretch;gap:8px;padding:12px 16px}
    .sort-result-count{margin-left:0;font-size:.78rem}
    .shiksha-sidebar{display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:200;background:rgba(0,0,0,.5);padding:0;align-items:flex-end}
    .shiksha-sidebar.open{display:flex}
    .shiksha-sidebar .shiksha-widget-wrapper{
      position:static;border-radius:16px 16px 0 0;max-height:85vh;overflow-y:auto;
      width:100%;box-shadow:0 -4px 24px rgba(0,0,0,.2);animation:slideUp .3s ease;
      background:#fff;padding:20px;display:flex;flex-direction:column;gap:16px
    }
    .shiksha-sidebar .shiksha-widget{margin:0;background:#f8fafc}
    .col-filter-close{display:flex}
    .col-filter-toggle{
      display:flex;position:fixed;bottom:20px;right:20px;z-index:150;
      box-shadow:0 4px 20px rgba(0,0,0,.2);border-radius:50px;padding:12px 20px;
    }
    .shiksha-main-wrapper{padding:16px 0 32px}
    .college-pager{padding:16px;gap:4px}
    .pager-link{padding:6px 12px;font-size:.8rem}
    .shiksha-widget{padding:16px}
    .shiksha-widget-title{font-size:.82rem;margin-bottom:10px}
    .shiksha-widget-list li a{padding:8px 8px;font-size:.82rem}
  }
  @media(max-width:480px){
    .cl-stats-bar{grid-template-columns:1fr 1fr;gap:8px}
    .cl-stat{padding:10px 12px}
    .cl-stat-val{font-size:1.1rem}
    .shiksha-tabs a{padding:7px 10px;font-size:.72rem}
    .college-list-card{padding:14px}
    .clc-img{height:150px}
    .clc-body h3{font-size:.92rem}
    .clc-stats div{min-width:calc(50% - 1px);padding:7px 5px}
    .clc-stats strong{font-size:.82rem}
  }
  @keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
</style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Hero Header -->
<div class="shiksha-header">
  <div class="container">
    <div class="shiksha-breadcrumb">
      <a href="<?= $navBase ?>/">Home</a>
      <i class="ph ph-caret-right"></i>
      <span>Universities</span>
    </div>
    <h1 class="shiksha-title">Find Top Universities in India</h1>
    <p class="college-list-sub"><?= number_format($total) ?> universities found</p>
    <div class="cl-stats-bar">
      <div class="cl-stat"><div class="cl-stat-val"><?= number_format($stats['total']) ?>+</div><div class="cl-stat-lbl">Total Universities</div></div>
      <div class="cl-stat"><div class="cl-stat-val"><?= number_format($stats['govt']) ?>+</div><div class="cl-stat-lbl">Government</div></div>
      <div class="cl-stat"><div class="cl-stat-val"><?= number_format($stats['private']) ?>+</div><div class="cl-stat-lbl">Private</div></div>
      <div class="cl-stat"><div class="cl-stat-val"><?= number_format($stats['states']) ?>+</div><div class="cl-stat-lbl">States Covered</div></div>
    </div>
  </div>
</div>

<!-- Type Filter Tabs -->
<div class="shiksha-tabs-nav">
  <div class="container">
    <div class="shiksha-tabs">
      <?php foreach (['all' => 'All Universities', 'govt' => '🏛️ Government', 'private' => '🏢 Private', 'deemed' => '🎓 Deemed', 'autonomous' => '⚙️ Autonomous'] as $k => $label): ?>
      <a href="<?= universitiesUrl(array_filter(['type' => $k !== 'all' ? $k : null, 'sort' => $sort !== 'featured' ? $sort : null])) ?>" class="<?= $type === $k ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="container shiksha-main-wrapper">
  <div class="shiksha-layout">

    <main class="shiksha-content college-list-main">

      <!-- Sort bar -->
      <div class="sort-bar">
        <label>Sort by:</label>
        <select onchange="window.location=this.value">
          <?php foreach (['featured'=>'Featured First','rating'=>'Top Rated','nirf'=>'NIRF Rank','name'=>'Name A-Z','established'=>'Oldest First'] as $sk=>$sl): ?>
          <option value="<?= universitiesUrl(array_filter(['type'=>$type!=='all'?$type:null,'sort'=>$sk,'page'=>null])) ?>" <?= $sort===$sk?'selected':'' ?>><?= $sl ?></option>
          <?php endforeach; ?>
        </select>
        <span class="sort-result-count">Showing <?= (($page-1)*$perPage)+1 ?>–<?= min($page*$perPage,$total) ?> of <?= number_format($total) ?> universities</span>
      </div>

      <!-- University cards -->
      <?php if (empty($universities)): ?>
      <div style="text-align:center;padding:60px 20px;color:#94a3b8">
        <i class="ph ph-graduation-cap" style="font-size:3rem;display:block;margin-bottom:12px;opacity:.15"></i>
        <p>No universities found. Try adjusting your filters.</p>
      </div>
      <?php else: ?>
      <div class="college-grid-list">
        <?php foreach ($universities as $u):
          $uLocation = trim(($u['city_name'] ?? '') . ($u['city_name'] && $u['state_name'] ? ', ' : '') . ($u['state_name'] ?? ''));
          $rating = (float)($u['overall_rating_avg'] ?? 0);
          $uYear = $u['established_year'] ?? $u['founded_year'] ?? '';
          $ownMap = ['central'=>'Central','state'=>'State','private_trust'=>'Trust','minority'=>'Minority'];
          $ownershipLabel = $ownMap[$u['ownership'] ?? ''] ?? '';
        ?>
        <a href="<?= universityUrl($u['slug']) ?>" class="college-list-card">
          <?php if (!empty($u['is_featured'])): ?>
          <span class="clc-featured-badge" style="position:absolute;top:12px;right:12px;z-index:2">⭐ Featured</span>
          <?php endif; ?>

          <div class="clc-img">
            <img src="<?= cImg($u['cover_image_url'] ?? '') ?>" alt="<?= htmlspecialchars($u['name']) ?>" loading="lazy">
            <div class="clc-img-badges">
              <?php if ($u['naac_grade']): ?><span class="clc-badge">NAAC <?= htmlspecialchars($u['naac_grade']) ?></span><?php endif; ?>
              <?php if (!empty($u['is_verified'])): ?><span class="clc-badge clc-badge-verified"><i class="ph-fill ph-seal-check"></i> Verified</span><?php endif; ?>
            </div>
          </div>

          <div class="clc-body">
            <div class="clc-top">
              <?php if (!empty($u['logo_url'])): ?><img src="<?= cImg($u['logo_url']) ?>" class="clc-logo" alt=""><?php endif; ?>
              <div style="flex:1;min-width:0">
                <h3><?= htmlspecialchars($u['name']) ?></h3>
                <div class="clc-meta">
                  <?php if ($uLocation): ?><span><i class="ph ph-map-pin"></i><?= htmlspecialchars($uLocation) ?></span><?php endif; ?>
                  <?php if ($uYear): ?><span><i class="ph ph-calendar"></i>Est. <?= htmlspecialchars((string)$uYear) ?></span><?php endif; ?>
                  <?php if (!empty($u['total_courses'])): ?><span><i class="ph ph-book-open"></i><?= (int)$u['total_courses'] ?> Courses</span><?php endif; ?>
                </div>
                <div class="clc-chips">
                  <span class="clc-chip"><?= htmlspecialchars(universityTypeLabel($u['university_type'], $u['ownership'])) ?></span>
                  <?php if ($ownershipLabel): ?><span class="clc-chip"><?= $ownershipLabel ?></span><?php endif; ?>
                  <?php if (!empty($u['ugc_approved'])): ?><span class="clc-chip chip-green">UGC ✓</span><?php endif; ?>
                  <?php if (!empty($u['aicte_approved'])): ?><span class="clc-chip chip-green">AICTE ✓</span><?php endif; ?>
                  <?php if (!empty($u['nba_approved'])): ?><span class="clc-chip chip-green">NBA ✓</span><?php endif; ?>
                  <?php if (!empty($u['ranking_nirf'])): ?><span class="clc-chip chip-orange">NIRF #<?= (int)$u['ranking_nirf'] ?></span><?php endif; ?>
                </div>
              </div>
            </div>
            <div class="clc-stats">
              <div><strong><?= $rating > 0 ? number_format($rating, 1) . '/5 ★' : '—' ?></strong><span>Rating</span></div>
              <div><strong><?= formatFee(isset($u['min_fee']) ? (float)$u['min_fee'] : null) ?></strong><span>Min Fee/Yr</span></div>
              <div><strong><?= formatLpa(isset($u['avg_package']) ? (float)$u['avg_package'] : null) ?></strong><span>Avg Package</span></div>
              <?php if (!empty($u['total_courses'])): ?>
              <div><strong><?= (int)$u['total_courses'] ?></strong><span>Courses</span></div>
              <?php endif; ?>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
      <div class="college-pager">
        <?php if ($page > 1): ?>
        <a href="<?= universitiesUrl(array_filter(['type'=>$type!=='all'?$type:null,'sort'=>$sort!=='featured'?$sort:null,'page'=>$page-1])) ?>" class="pager-link">← Prev</a>
        <?php endif; ?>
        <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+4); $p++): ?>
        <a href="<?= universitiesUrl(array_filter(['type'=>$type!=='all'?$type:null,'sort'=>$sort!=='featured'?$sort:null,'page'=>$p])) ?>" class="pager-link<?= $p===$page?' active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="<?= universitiesUrl(array_filter(['type'=>$type!=='all'?$type:null,'sort'=>$sort!=='featured'?$sort:null,'page'=>$page+1])) ?>" class="pager-link">Next →</a>
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
          <h4 class="shiksha-widget-title"><i class="ph ph-list-magnifying-glass"></i> Quick Filters</h4>
          <ul class="shiksha-widget-list">
            <li><a href="<?= universitiesUrl(['type'=>'govt']) ?>"><span style="margin-right:auto">Government Universities</span></a></li>
            <li><a href="<?= universitiesUrl(['type'=>'private']) ?>"><span style="margin-right:auto">Private Universities</span></a></li>
            <li><a href="<?= universitiesUrl(['type'=>'deemed']) ?>"><span style="margin-right:auto">Deemed Universities</span></a></li>
            <li><a href="<?= universitiesUrl(['type'=>'autonomous']) ?>"><span style="margin-right:auto">Autonomous Universities</span></a></li>
            <li><a href="<?= universitiesUrl(['sort'=>'rating']) ?>"><span style="margin-right:auto">Top Rated</span></a></li>
            <li><a href="<?= universitiesUrl(['sort'=>'nirf']) ?>"><span style="margin-right:auto">NIRF Rankings</span></a></li>
          </ul>
        </div>

        <div class="shiksha-widget" style="background:linear-gradient(135deg,rgba(11,36,71,0.06),rgba(11,36,71,0.04));border-color:rgba(79,70,229,.2)">
          <h4 class="shiksha-widget-title" style="color:#19376D"><i class="ph ph-headset"></i> Free Counselling</h4>
          <p style="font-size:.85rem;color:rgba(15,23,42,0.65);margin-bottom:12px">Get expert guidance for university admissions.</p>
          <a href="<?= $navBase ?>/counselling" style="display:block;text-align:center;padding:10px;background:linear-gradient(135deg,#19376D,#0B2447);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:.87rem">Create Free Account →</a>
        </div>
      </div>
    </aside>

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
