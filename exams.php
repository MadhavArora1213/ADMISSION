<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/exam_helpers.php';

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Top Entrance Exams in India 2026 - AdmissionSeason</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/college-pages.css?v=<?= time() ?>">
  <style>
    .exams-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
    .exam-card { background: var(--cp-card); border-radius: 16px; border: 1px solid var(--cp-border); padding: 24px; transition: var(--cp-trans); display: flex; flex-direction: column; height: 100%; box-shadow: var(--cp-shadow); }
    .exam-card:hover { transform: translateY(-4px); box-shadow: var(--cp-shadow-lg); border-color: var(--cp-blue); }
    .exam-header { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 20px; }
    .exam-logo { width: 64px; height: 64px; border-radius: 12px; object-fit: contain; background: #fff; border: 1px solid rgba(15,23,42,0.08); padding: 4px; flex-shrink: 0; }
    .exam-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--cp-blue); margin: 0 0 4px 0; line-height: 1.3; }
    .exam-abbr { font-size: 0.85rem; color: var(--cp-muted); background: var(--cp-light); padding: 2px 8px; border-radius: 6px; font-weight: 600; }
    .exam-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; flex-grow: 1; }
    .meta-item { display: flex; align-items: center; gap: 8px; font-size: 0.875rem; color: rgba(15,23,42,0.65); }
    .meta-item i { color: var(--cp-blue); font-size: 1.1rem; }
    .exam-footer { border-top: 1px solid var(--cp-border); padding-top: 16px; display: flex; justify-content: space-between; align-items: center; }
    .exam-btn { background: linear-gradient(135deg, var(--cp-blue), var(--cp-blue2)); color: #fff; padding: 10px 20px; border-radius: 10px; font-weight: 600; text-decoration: none; font-size: 0.9rem; transition: var(--cp-trans); }
    .exam-btn:hover { box-shadow: 0 8px 20px rgba(11,36,71,0.2); }
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="shiksha-breadcrumb" style="background:#fff;border-bottom:1px solid rgba(15,23,42,0.08);padding:15px 0">
  <div class="container">
    <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/index.php">Home</a>
    <i class="ph ph-caret-right"></i>
    <span>Entrance Exams</span>
  </div>
</div>

<div class="container" style="padding:40px 0; display:grid; grid-template-columns:260px 1fr; gap:32px;">
  
  <aside class="college-filters">
    <h3 style="font-size:1.1rem;margin-bottom:16px;font-weight:700">Filter Exams</h3>
    
    <div class="filter-group">
      <div class="filter-title">Exam Level</div>
      <ul class="filter-list">
        <li><a href="<?= examsUrl(['level'=>'all','mode'=>$mode,'q'=>$search]) ?>" <?= $level==='all'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>All Levels</a></li>
        <li><a href="<?= examsUrl(['level'=>'national','mode'=>$mode,'q'=>$search]) ?>" <?= $level==='national'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>National Level</a></li>
        <li><a href="<?= examsUrl(['level'=>'state','mode'=>$mode,'q'=>$search]) ?>" <?= $level==='state'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>State Level</a></li>
        <li><a href="<?= examsUrl(['level'=>'university','mode'=>$mode,'q'=>$search]) ?>" <?= $level==='university'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>University Level</a></li>
      </ul>
    </div>

    <div class="filter-group" style="margin-top:24px">
      <div class="filter-title">Exam Mode</div>
      <ul class="filter-list">
        <li><a href="<?= examsUrl(['mode'=>'all','level'=>$level,'q'=>$search]) ?>" <?= $mode==='all'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>All Modes</a></li>
        <li><a href="<?= examsUrl(['mode'=>'online','level'=>$level,'q'=>$search]) ?>" <?= $mode==='online'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>Online (CBT)</a></li>
        <li><a href="<?= examsUrl(['mode'=>'offline','level'=>$level,'q'=>$search]) ?>" <?= $mode==='offline'?'style="font-weight:700;color:var(--cp-blue)"':'' ?>>Offline (Pen-Paper)</a></li>
      </ul>
    </div>
  </aside>

  <main>
    <div style="margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;">
      <h1 style="font-size:1.8rem;font-weight:800;color:var(--cp-blue)">Top Entrance Exams in India</h1>
      <span style="color:var(--cp-muted);font-weight:600"><?= count($exams) ?> Exams Found</span>
    </div>

    <div class="exams-grid">
      <?php foreach ($exams as $ex): ?>
      <div class="exam-card">
        <div class="exam-header">
          <img src="<?= cImg($ex['conducting_body_logo']) ?>" class="exam-logo" alt="<?= htmlspecialchars($ex['exam_abbreviation']) ?>">
          <div>
            <h2 class="exam-title"><?= htmlspecialchars($ex['exam_name']) ?></h2>
            <span class="exam-abbr"><?= htmlspecialchars($ex['exam_abbreviation']) ?></span>
          </div>
        </div>
        <div class="exam-meta">
          <div class="meta-item"><i class="ph ph-bank"></i> <?= ucfirst($ex['exam_level']) ?> Level</div>
          <div class="meta-item"><i class="ph ph-laptop"></i> <?= ucfirst($ex['exam_mode']) ?> Mode</div>
          <?php if($ex['applicants_last_year']): ?>
          <div class="meta-item"><i class="ph ph-users"></i> <?= number_format($ex['applicants_last_year']/100000, 1) ?>L+ Applicants</div>
          <?php endif; ?>
          <div class="meta-item"><i class="ph ph-timer"></i> <?= $ex['duration_minutes'] ?> Mins</div>
        </div>
        <div class="exam-footer">
          <span style="font-size:0.8rem;color:rgba(15,23,42,0.45);font-weight:600">By <?= htmlspecialchars($ex['conducting_body']) ?></span>
          <a href="<?= examUrl($ex['exam_slug']) ?>" class="exam-btn">View Details</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    
    <?php if (empty($exams)): ?>
      <div style="text-align:center;padding:60px 20px;color:var(--cp-muted)">
        <i class="ph ph-magnifying-glass" style="font-size:3rem;margin-bottom:10px"></i>
        <h3>No exams found</h3>
        <p>Try adjusting your filters or search query.</p>
      </div>
    <?php endif; ?>

  </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
