<?php
declare(strict_types=1);
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

// Fetch categories for filter
$cats = cAll($pdo, "SELECT DISTINCT course_category FROM courses WHERE status = 'active' AND course_category IS NOT NULL ORDER BY course_category");

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Top Courses in India 2026 - AdmissionSeason</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/college-pages.css?v=<?= time() ?>">
  <style>
    .courses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
    .course-card { background: #fff; border-radius: 20px; border: 1px solid #e2e8f0; padding: 28px; transition: all 0.3s ease; display: flex; flex-direction: column; height: 100%; box-shadow: 0 8px 25px rgba(0,0,0,0.02); }
    .course-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(11,36,71,0.08); border-color: var(--cp-blue); }
    .course-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0 0 20px 0; line-height: 1.4; transition: color 0.3s ease; }
    .course-card:hover .course-title { color: var(--cp-blue); }
    .course-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 28px; flex-grow: 1; }
    .meta-item { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #475569; font-weight: 500; }
    .meta-item i { color: var(--cp-blue); font-size: 1.2rem; }
    .course-footer { border-top: 1px solid #f1f5f9; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; }
    .course-btn { background: transparent; color: var(--cp-blue); border: 2px solid var(--cp-blue); padding: 12px 20px; border-radius: 50px; font-weight: 700; text-decoration: none; text-align: center; transition: all 0.3s ease; width: 100%; display: block; font-size: 0.95rem; }
    .course-btn:hover { background: var(--cp-blue); color: #fff; box-shadow: 0 10px 20px rgba(11,36,71,0.15); }
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="shiksha-breadcrumb" style="background:#fff;border-bottom:1px solid #e2e8f0;padding:15px 0">
  <div class="container">
    <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/index.php">Home</a>
    <i class="ph ph-caret-right"></i>
    <span>Courses</span>
  </div>
</div>

<div class="container" style="padding:40px 0; display:grid; grid-template-columns:260px 1fr; gap:32px;">
  
  <aside class="college-filters">
    <h3 style="font-size:1.1rem;margin-bottom:16px;font-weight:700">Filter Courses</h3>
    
    <div class="filter-group">
      <div class="filter-title">Course Level</div>
      <ul class="filter-list">
        <li><a href="<?= coursesUrl(['level'=>'all','category'=>$category,'q'=>$search]) ?>" <?= $level==='all'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>All Levels</a></li>
        <li><a href="<?= coursesUrl(['level'=>'UG','category'=>$category,'q'=>$search]) ?>" <?= $level==='UG'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>Undergraduate (UG)</a></li>
        <li><a href="<?= coursesUrl(['level'=>'PG','category'=>$category,'q'=>$search]) ?>" <?= $level==='PG'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>Postgraduate (PG)</a></li>
        <li><a href="<?= coursesUrl(['level'=>'Diploma','category'=>$category,'q'=>$search]) ?>" <?= $level==='Diploma'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>Diploma</a></li>
        <li><a href="<?= coursesUrl(['level'=>'PhD','category'=>$category,'q'=>$search]) ?>" <?= $level==='PhD'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>Doctorate (PhD)</a></li>
      </ul>
    </div>

    <div class="filter-group" style="margin-top:24px">
      <div class="filter-title">Category</div>
      <ul class="filter-list">
        <li><a href="<?= coursesUrl(['category'=>'all','level'=>$level,'q'=>$search]) ?>" <?= $category==='all'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>All Categories</a></li>
        <?php foreach($cats as $c): ?>
        <li><a href="<?= coursesUrl(['category'=>$c['course_category'],'level'=>$level,'q'=>$search]) ?>" <?= $category===$c['course_category']?'style="font-weight:700;color:var(--cp-blue)"':'' ?>><?= htmlspecialchars($c['course_category']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </aside>

  <main>
    <div style="margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;">
      <h1 style="font-size:1.8rem;font-weight:800;color:var(--cp-blue)">Top Courses in India</h1>
      <span style="color:var(--cp-muted);font-weight:600"><?= count($courses) ?> Courses Found</span>
    </div>

    <div class="courses-grid">
      <?php foreach ($courses as $c): ?>
      <div class="course-card">
        <h2 class="course-title"><?= htmlspecialchars((string)($c['course_name'] ?? '')) ?></h2>
        <div class="course-meta">
          <div class="meta-item"><i class="ph ph-graduation-cap"></i> <?= htmlspecialchars((string)($c['course_level'] ?? '')) ?> Level</div>
          <div class="meta-item"><i class="ph ph-clock"></i> <?= htmlspecialchars((string)($c['duration_years'] ?? '')) ?> Years</div>
          <div class="meta-item" style="grid-column: span 2"><i class="ph ph-buildings"></i> <?= htmlspecialchars((string)($c['total_colleges_offering'] ?? '0')) ?> Colleges offering this</div>
          <?php if(!empty($c['avg_salary_lpa'])): ?>
          <div class="meta-item" style="grid-column: span 2"><i class="ph ph-currency-inr"></i> ₹<?= htmlspecialchars((string)$c['avg_salary_lpa']) ?> LPA (Avg Salary)</div>
          <?php endif; ?>
        </div>
        <div class="course-footer">
          <a href="<?= courseUrl($c['course_slug']) ?>" class="course-btn">View Details</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    
    <?php if (empty($courses)): ?>
      <div style="text-align:center;padding:60px 20px;color:var(--cp-muted)">
        <i class="ph ph-magnifying-glass" style="font-size:3rem;margin-bottom:10px"></i>
        <h3>No courses found</h3>
        <p>Try adjusting your filters or search query.</p>
      </div>
    <?php endif; ?>

  </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
