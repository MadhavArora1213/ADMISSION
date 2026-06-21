<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';
require_once __DIR__ . '/includes/course_helpers.php';

$level = $_GET['level'] ?? 'all';
$category = trim($_GET['category'] ?? 'all');
$search = trim($_GET['q'] ?? '');

$where = ["status = 'active'"];
$params = [];

if ($level !== 'all') {
    $where[] = "course_level = ?";
    $params[] = $level;
}
if ($category !== 'all') {
    $where[] = "course_category = ?";
    $params[] = $category;
}
if ($search !== '') {
    $where[] = "(course_name LIKE ? OR course_slug LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM courses WHERE $whereSql ORDER BY is_popular DESC, total_colleges_offering DESC");
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cats = cAll($pdo, "SELECT DISTINCT course_category FROM courses WHERE status = 'active' AND course_category IS NOT NULL ORDER BY course_category");

$levelLabels = ['UG'=>'Undergraduate','PG'=>'Postgraduate','Diploma'=>'Diploma','PhD'=>'Doctorate','Certificate'=>'Certificate','Integrated'=>'Integrated'];

$levelIcons = ['UG'=>'ph-graduation-cap','PG'=>'ph-bookmark','Diploma'=>'ph-certificate','PhD'=>'ph-seal-check','Certificate'=>'ph-badge','Integrated'=>'ph-link'];

$stats = [
    'total'   => cCol($pdo, "SELECT COUNT(*) FROM courses WHERE status='active'"),
    'ug'      => cCol($pdo, "SELECT COUNT(*) FROM courses WHERE status='active' AND course_level='UG'"),
    'pg'      => cCol($pdo, "SELECT COUNT(*) FROM courses WHERE status='active' AND course_level='PG'"),
    'diploma' => cCol($pdo, "SELECT COUNT(*) FROM courses WHERE status='active' AND course_level='Diploma'"),
    'phd'     => cCol($pdo, "SELECT COUNT(*) FROM courses WHERE status='active' AND course_level='PhD'"),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Courses in India 2026 - AdmissionSeason</title>
  <meta name="description" content="Explore top UG, PG, Diploma and PhD courses in India. Compare fees, duration, eligibility and career scope.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <style>
    .courses-hero{background:linear-gradient(135deg,#0B2447 0%,#19376D 50%,#0B2447 100%),url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M54.627 0l.83.83-49.12 49.12L5.5 49.12 54.627 0zM0 54.627l.83.83L5.5 54.627 0 49.12v5.507z' fill='%23ffffff' fill-opacity='0.04' fill-rule='evenodd'/%3E%3C/svg%3E");padding:72px 0 48px;color:#fff;position:relative;overflow:hidden}
    .courses-hero::before{content:'';position:absolute;top:-50%;right:-20%;width:600px;height:600px;background:radial-gradient(circle,rgba(255,255,255,.06) 0%,transparent 70%);pointer-events:none}
    .courses-hero::after{content:'';position:absolute;bottom:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#0B2447,#19376D,#0B2447)}
    .courses-hero .container{position:relative;z-index:2}
    .courses-breadcrumb{display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:.85rem;color:rgba(255,255,255,.5)}
    .courses-breadcrumb a{color:rgba(255,255,255,.6);text-decoration:none;transition:color .2s}
    .courses-breadcrumb a:hover{color:#fff}
    .courses-breadcrumb i{font-size:.7rem}
    .courses-hero h1{font-family:'Plus Jakarta Sans',sans-serif;font-size:2.5rem;font-weight:800;margin:0 0 10px;line-height:1.2;text-shadow:0 2px 20px rgba(0,0,0,.2)}
    .courses-hero-sub{margin:0 0 28px;color:rgba(255,255,255,.7);font-size:1.08rem;max-width:600px}
    .courses-hero-sub strong{color:#fff;font-weight:700}
    .courses-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:14px}
    .course-stat{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:14px;padding:18px 16px;text-align:center;backdrop-filter:blur(8px);transition:all .3s}
    .course-stat:hover{background:rgba(255,255,255,.16);transform:translateY(-2px)}
    .course-stat-val{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.6rem;font-weight:800;color:#fff;display:block;line-height:1}
    .course-stat-lbl{font-size:.72rem;color:rgba(255,255,255,.6);margin-top:4px;text-transform:uppercase;letter-spacing:.5px;font-weight:600}
    .course-stat i{font-size:1.4rem;color:rgba(255,255,255,.5);margin-bottom:6px;display:block}
    @media(max-width:768px){
      .courses-hero{padding:60px 0 36px}
      .courses-hero h1{font-size:1.6rem}
      .courses-hero-sub{font-size:.95rem}
      .courses-stats{grid-template-columns:repeat(3,1fr);gap:10px}
      .course-stat{padding:14px 10px}
      .course-stat-val{font-size:1.3rem}
    }
    @media(max-width:480px){
      .courses-stats{grid-template-columns:repeat(2,1fr)}
    }
    .courses-layout{display:grid;grid-template-columns:260px 1fr;gap:28px;padding:36px 0 60px}
    .courses-main{min-width:0}
    .courses-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:8px}
    .courses-head h2{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.35rem;font-weight:800;color:#0B2447;margin:0}
    .courses-count{background:rgba(11,36,71,.06);color:#0B2447;padding:5px 14px;border-radius:20px;font-size:.82rem;font-weight:700;white-space:nowrap}
    .courses-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px}
    .course-card{background:#fff;border-radius:16px;border:1.5px solid rgba(15,23,42,.06);padding:0;transition:all .25s;overflow:hidden;display:flex;flex-direction:column}
    .course-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.08);border-color:rgba(37,99,235,.2)}
    .course-card-top{padding:24px 24px 16px;flex:1}
    .course-level-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:8px;font-size:.72rem;font-weight:700;margin-bottom:10px;background:rgba(37,99,235,.06);color:#2563eb}
    .course-name{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.15rem;font-weight:800;color:#0B2447;margin:0 0 12px;line-height:1.3}
    .course-name a{color:inherit;text-decoration:none}
    .course-name a:hover{color:#2563eb}
    .course-meta{display:grid;grid-template-columns:1fr 1fr;gap:10px 16px;margin-bottom:4px}
    .cm{display:flex;align-items:center;gap:7px;font-size:.83rem;color:rgba(15,23,42,.6)}
    .cm i{color:#2563eb;font-size:1rem;flex-shrink:0}
    .cm-val{font-weight:600;color:#0F172A}
    .course-card-bottom{border-top:1.5px solid rgba(15,23,42,.05);padding:14px 24px;display:flex;justify-content:space-between;align-items:center;background:#fafbfc}
    .course-salary{font-size:.82rem;font-weight:700;color:#16a34a;display:flex;align-items:center;gap:4px}
    .course-detail-btn{display:inline-flex;align-items:center;gap:5px;padding:9px 20px;border-radius:10px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .2s;white-space:nowrap}
    .course-detail-btn:hover{box-shadow:0 6px 16px rgba(11,36,71,.25);transform:translateY(-1px)}
    .courses-empty{text-align:center;padding:60px 20px;color:#94a3b8}
    .courses-empty i{font-size:3rem;margin-bottom:12px;display:block}
    .courses-empty h3{font-size:1.1rem;color:#64748b;margin:0 0 6px}

    .course-filter-card{background:#fff;border-radius:14px;border:1.5px solid rgba(15,23,42,.06);padding:20px;position:sticky;top:100px}
    .course-filter-card h3{font-size:1rem;font-weight:800;color:#0B2447;margin:0 0 16px;display:flex;align-items:center;gap:8px}
    .course-filter-group{margin-bottom:20px}
    .course-filter-group:last-child{margin-bottom:0}
    .course-filter-title{font-size:.78rem;font-weight:700;color:rgba(15,23,42,.45);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px}
    .course-filter-list{list-style:none;padding:0;margin:0}
    .course-filter-list a{display:flex;align-items:center;gap:8px;padding:9px 14px;border-radius:10px;font-size:.88rem;color:rgba(15,23,42,.65);text-decoration:none;font-weight:500;transition:all .2s}
    .course-filter-list a:hover{background:rgba(37,99,235,.05);color:#2563eb}
    .course-filter-list a.active{background:rgba(37,99,235,.08);color:#2563eb;font-weight:700}
    .course-filter-list a i{font-size:1rem;opacity:.5}
    .course-filter-list a.active i{opacity:1}

    @media(max-width:768px){
      .courses-layout{grid-template-columns:1fr}
      .courses-grid{grid-template-columns:1fr}
      .course-filter-card{position:static}
      .courses-hero h1{font-size:1.5rem}
    }
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="courses-hero">
  <div class="container">
    <div class="courses-breadcrumb">
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/index.php">Home</a>
      <i class="ph ph-caret-right"></i>
      <span>Courses</span>
    </div>
    <h1>Courses in India</h1>
    <p class="courses-hero-sub">Explore UG, PG, Diploma and Doctorate courses with fees, eligibility & career scope<?= $level !== 'all' ? ' — showing <strong>' . htmlspecialchars($levelLabels[$level] ?? $level) . '</strong> courses' : '' ?></p>
    <div class="courses-stats">
      <a href="<?= coursesUrl() ?>" class="course-stat" style="text-decoration:none;color:inherit">
        <i class="ph ph-stack"></i>
        <span class="course-stat-val"><?= number_format($stats['total']) ?>+</span>
        <span class="course-stat-lbl">Total Courses</span>
      </a>
      <a href="<?= coursesUrl(['level'=>'UG']) ?>" class="course-stat" style="text-decoration:none;color:inherit">
        <i class="ph ph-graduation-cap"></i>
        <span class="course-stat-val"><?= number_format($stats['ug']) ?>+</span>
        <span class="course-stat-lbl">UG Courses</span>
      </a>
      <a href="<?= coursesUrl(['level'=>'PG']) ?>" class="course-stat" style="text-decoration:none;color:inherit">
        <i class="ph ph-bookmark"></i>
        <span class="course-stat-val"><?= number_format($stats['pg']) ?>+</span>
        <span class="course-stat-lbl">PG Courses</span>
      </a>
      <a href="<?= coursesUrl(['level'=>'Diploma']) ?>" class="course-stat" style="text-decoration:none;color:inherit">
        <i class="ph ph-certificate"></i>
        <span class="course-stat-val"><?= number_format($stats['diploma']) ?>+</span>
        <span class="course-stat-lbl">Diploma</span>
      </a>
      <a href="<?= coursesUrl(['level'=>'PhD']) ?>" class="course-stat" style="text-decoration:none;color:inherit">
        <i class="ph ph-seal-check"></i>
        <span class="course-stat-val"><?= number_format($stats['phd']) ?>+</span>
        <span class="course-stat-lbl">Doctorate</span>
      </a>
    </div>
  </div>
</div>

<div class="container">
  <div class="courses-layout">

    <aside>
      <div class="course-filter-card">
        <h3><i class="ph ph-funnel"></i> Filter Courses</h3>

        <div class="course-filter-group">
          <div class="course-filter-title">Course Level</div>
          <ul class="course-filter-list">
            <li><a href="<?= coursesUrl(['level'=>'all','category'=>$category,'q'=>$search]) ?>" class="<?= $level==='all'?'active':'' ?>"><i class="ph ph-stack"></i> All Levels</a></li>
            <li><a href="<?= coursesUrl(['level'=>'UG','category'=>$category,'q'=>$search]) ?>" class="<?= $level==='UG'?'active':'' ?>"><i class="ph ph-graduation-cap"></i> Undergraduate (UG)</a></li>
            <li><a href="<?= coursesUrl(['level'=>'PG','category'=>$category,'q'=>$search]) ?>" class="<?= $level==='PG'?'active':'' ?>"><i class="ph ph-bookmark"></i> Postgraduate (PG)</a></li>
            <li><a href="<?= coursesUrl(['level'=>'Diploma','category'=>$category,'q'=>$search]) ?>" class="<?= $level==='Diploma'?'active':'' ?>"><i class="ph ph-certificate"></i> Diploma</a></li>
            <li><a href="<?= coursesUrl(['level'=>'PhD','category'=>$category,'q'=>$search]) ?>" class="<?= $level==='PhD'?'active':'' ?>"><i class="ph ph-seal-check"></i> Doctorate (PhD)</a></li>
          </ul>
        </div>

        <?php if (!empty($cats)): ?>
        <div class="course-filter-group">
          <div class="course-filter-title">Category</div>
          <ul class="course-filter-list">
            <li><a href="<?= coursesUrl(['category'=>'all','level'=>$level,'q'=>$search]) ?>" class="<?= $category==='all'?'active':'' ?>"><i class="ph ph-squares-four"></i> All Categories</a></li>
            <?php foreach($cats as $c): ?>
            <li><a href="<?= coursesUrl(['category'=>$c['course_category'],'level'=>$level,'q'=>$search]) ?>" class="<?= $category===$c['course_category']?'active':'' ?>"><?= htmlspecialchars($c['course_category']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
      </div>
    </aside>

    <main class="courses-main">
      <div class="courses-head">
        <h2>Showing <?= count($courses) ?> Course<?= count($courses) !== 1 ? 's' : '' ?></h2>
        <?php if ($level !== 'all' || $category !== 'all' || $search !== ''): ?>
        <a href="courses.php" style="font-size:.82rem;color:#2563eb;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:4px"><i class="ph ph-x-circle"></i> Clear Filters</a>
        <?php endif; ?>
      </div>

      <?php if (!empty($courses)): ?>
      <div class="courses-grid">
        <?php foreach ($courses as $c):
          $lvl = $c['course_level'] ?? 'UG';
          $icon = $levelIcons[$lvl] ?? 'ph-books';
          $label = $levelLabels[$lvl] ?? $lvl;
          $dur = $c['duration_years'] ?? 0;
          $sal = $c['avg_salary_lpa'] ?? 0;
          $clg = $c['total_colleges_offering'] ?? 0;
        ?>
        <div class="course-card">
          <div class="course-card-top">
            <div class="course-level-badge"><i class="ph <?= $icon ?>"></i> <?= $label ?></div>
            <h3 class="course-name"><a href="<?= courseUrl($c['course_slug']) ?>"><?= htmlspecialchars($c['course_name']) ?></a></h3>
            <div class="course-meta">
              <div class="cm"><i class="ph ph-clock"></i> <span class="cm-val"><?= $dur ?> <?= $dur == 1 ? 'Year' : 'Years' ?></span></div>
              <div class="cm"><i class="ph ph-buildings"></i> <span class="cm-val"><?= number_format($clg) ?></span> Colleges</div>
              <?php if ($c['eligibility']): ?>
              <div class="cm" style="grid-column:1/-1"><i class="ph ph-identification-card"></i> <?= htmlspecialchars(mb_strimwidth($c['eligibility'], 0, 60, '...')) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="course-card-bottom">
            <?php if ($sal > 0): ?>
            <span class="course-salary"><i class="ph ph-trend-up"></i> ₹<?= number_format((float)$sal, 1) ?> LPA avg</span>
            <?php else: ?>
            <span></span>
            <?php endif; ?>
            <a href="<?= courseUrl($c['course_slug']) ?>" class="course-detail-btn">View Details <i class="ph ph-arrow-right"></i></a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="courses-empty">
        <i class="ph ph-magnifying-glass"></i>
        <h3>No courses found</h3>
        <p>Try adjusting your filters or search query.</p>
      </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
