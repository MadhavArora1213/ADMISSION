<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/exam_helpers.php';

$filter = $_GET['filter'] ?? 'all';
$title = 'Exam Calendar 2026';
$subtitle = 'All upcoming exam dates, application deadlines and results';

if ($filter === 'application') {
    $title = 'Application Deadlines';
    $subtitle = 'Upcoming registration and application deadlines for top exams';
} elseif ($filter === 'result') {
    $title = 'Result Dates';
    $subtitle = 'Expected result declaration dates for major entrance exams';
}

$pageTitle = $title . ' - AdmissionSeason';

$where = "e.status != 'cancelled'";
if ($filter === 'application') {
    $where .= " AND (LOWER(ed.event_name) LIKE '%application%' OR LOWER(ed.event_name) LIKE '%registration%')";
} elseif ($filter === 'result') {
    $where .= " AND (LOWER(ed.event_name) LIKE '%result%')";
}

$stmt = $pdo->prepare("
    SELECT ed.*, e.exam_name, e.exam_abbreviation, e.exam_slug, e.conducting_body_logo
    FROM exam_dates ed
    JOIN exams e ON e.id = ed.exam_id
    WHERE $where
    ORDER BY ed.event_date ASC
");
$stmt->execute([]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($subtitle) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <style>
    .cal-hero{background:linear-gradient(135deg,#0B2447,#19376D);padding:48px 0 40px;color:#fff;text-align:center}
    .cal-hero h1{font-family:'Plus Jakarta Sans',sans-serif;font-size:2rem;font-weight:800;margin:0 0 8px}
    .cal-hero p{margin:0;color:rgba(255,255,255,.65);font-size:1rem}
    .cal-filters{display:flex;gap:10px;justify-content:center;margin-top:20px;flex-wrap:wrap}
    .cal-filter-btn{padding:8px 20px;border-radius:20px;font-size:.85rem;font-weight:600;text-decoration:none;transition:all .2s;border:1.5px solid rgba(255,255,255,.2);color:rgba(255,255,255,.7);background:transparent}
    .cal-filter-btn:hover{background:rgba(255,255,255,.1);color:#fff}
    .cal-filter-btn.active{background:#fff;color:#0B2447;border-color:#fff}
    .cal-container{max-width:900px;margin:0 auto;padding:36px 20px 60px}
    .cal-month{margin-bottom:32px}
    .cal-month-title{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.1rem;font-weight:800;color:#0B2447;margin:0 0 16px;padding-bottom:10px;border-bottom:2px solid rgba(11,36,71,.08);display:flex;align-items:center;gap:8px}
    .cal-month-title i{color:#2563eb}
    .cal-events{display:flex;flex-direction:column;gap:10px}
    .cal-event{display:flex;align-items:center;gap:16px;padding:16px 20px;background:#fff;border:1.5px solid rgba(15,23,42,.06);border-radius:14px;transition:all .2s}
    .cal-event:hover{border-color:rgba(37,99,235,.2);box-shadow:0 4px 16px rgba(0,0,0,.04)}
    .cal-event.past{opacity:.5}
    .cal-event.today{border-color:rgba(37,99,235,.3);background:rgba(37,99,235,.02)}
    .cal-date-box{width:56px;height:56px;border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff}
    .cal-date-day{font-size:1.2rem;font-weight:800;line-height:1}
    .cal-date-month{font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;opacity:.8}
    .cal-event.past .cal-date-box{background:#94a3b8}
    .cal-event.today .cal-date-box{background:linear-gradient(135deg,#2563eb,#19376D);box-shadow:0 4px 12px rgba(37,99,235,.3)}
    .cal-event-body{flex:1;min-width:0}
    .cal-event-title{font-size:.95rem;font-weight:700;color:#0F172A;margin:0 0 3px}
    .cal-event-title a{color:inherit;text-decoration:none}
    .cal-event-title a:hover{color:#2563eb}
    .cal-event-meta{display:flex;flex-wrap:wrap;gap:10px;align-items:center;font-size:.78rem;color:rgba(15,23,42,.45)}
    .cal-event-meta span{display:inline-flex;align-items:center;gap:4px}
    .cal-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:12px;font-size:.72rem;font-weight:700;white-space:nowrap}
    .cal-badge-tentative{background:rgba(234,179,8,.1);color:#92400e}
    .cal-badge-upcoming{background:rgba(37,99,235,.08);color:#2563eb}
    .cal-badge-past{background:rgba(15,23,42,.06);color:#94a3b8}
    .cal-badge-today{background:rgba(22,163,74,.1);color:#16a34a}
    .cal-empty{text-align:center;padding:60px 20px;color:#94a3b8}
    .cal-empty i{font-size:3rem;margin-bottom:12px;display:block}
    .cal-empty h3{font-size:1.1rem;color:#64748b;margin:0 0 6px}
    @media(max-width:640px){
      .cal-event{flex-direction:column;align-items:flex-start;gap:12px}
      .cal-date-box{flex-direction:row;width:auto;height:auto;padding:6px 14px;gap:6px}
    }
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="cal-hero">
  <div class="container">
    <h1><?= htmlspecialchars($title) ?></h1>
    <p><?= htmlspecialchars($subtitle) ?></p>
    <div class="cal-filters">
      <a href="exam_calendar.php" class="cal-filter-btn <?= $filter==='all'?'active':'' ?>"><i class="ph ph-calendar-blank"></i> All Events</a>
      <a href="exam_calendar.php?filter=application" class="cal-filter-btn <?= $filter==='application'?'active':'' ?>"><i class="ph ph-file-text"></i> Application Deadlines</a>
      <a href="exam_calendar.php?filter=result" class="cal-filter-btn <?= $filter==='result'?'active':'' ?>"><i class="ph ph-chart-line-up"></i> Result Dates</a>
    </div>
  </div>
</div>

<div class="cal-container">
  <?php if (empty($events)): ?>
  <div class="cal-empty">
    <i class="ph ph-calendar-x"></i>
    <h3>No events found</h3>
    <p>No upcoming events for this filter.</p>
  </div>
  <?php else: ?>
    <?php
    $grouped = [];
    foreach ($events as $ev) {
        $monthKey = date('Y-m', strtotime($ev['event_date']));
        $grouped[$monthKey][] = $ev;
    }
    ?>
    <?php foreach ($grouped as $monthKey => $monthEvents): ?>
    <div class="cal-month">
      <h2 class="cal-month-title"><i class="ph ph-calendar"></i> <?= date('F Y', strtotime($monthKey . '-01')) ?></h2>
      <div class="cal-events">
        <?php foreach ($monthEvents as $ev):
          $isPast = $ev['event_date'] < $today;
          $isToday = $ev['event_date'] === $today;
          $statusClass = $isToday ? 'today' : ($isPast ? 'past' : '');
          $eventLabel = strtolower($ev['event_name']);
          $isExam = str_contains($eventLabel, 'exam') && !str_contains($eventLabel, 'result');
          $isApply = str_contains($eventLabel, 'application') || str_contains($eventLabel, 'registration');
          $isResult = str_contains($eventLabel, 'result');
        ?>
        <div class="cal-event <?= $statusClass ?>">
          <div class="cal-date-box">
            <span class="cal-date-day"><?= date('d', strtotime($ev['event_date'])) ?></span>
            <span class="cal-date-month"><?= date('M', strtotime($ev['event_date'])) ?></span>
          </div>
          <div class="cal-event-body">
            <div class="cal-event-title">
              <a href="<?= examUrl($ev['exam_slug']) ?>"><?= htmlspecialchars($ev['event_name']) ?></a>
            </div>
            <div class="cal-event-meta">
              <span><img src="<?= cImg($ev['conducting_body_logo'] ?? '') ?>" style="width:16px;height:16px;border-radius:4px;object-fit:contain" alt=""> <?= htmlspecialchars($ev['exam_abbreviation']) ?></span>
              <span><i class="ph ph-calendar-blank"></i> <?= date('d M Y', strtotime($ev['event_date'])) ?></span>
              <?php if ($ev['is_tentative']): ?>
              <span class="cal-badge cal-badge-tentative"><i class="ph ph-question"></i> Tentative</span>
              <?php endif; ?>
              <?php if ($isToday): ?>
              <span class="cal-badge cal-badge-today"><i class="ph-fill ph-check-circle"></i> Today</span>
              <?php elseif ($isPast): ?>
              <span class="cal-badge cal-badge-past">Completed</span>
              <?php elseif ($isExam): ?>
              <span class="cal-badge cal-badge-upcoming"><i class="ph ph-exam"></i> Exam Day</span>
              <?php elseif ($isApply): ?>
              <span class="cal-badge cal-badge-upcoming"><i class="ph ph-pencil-simple"></i> Register</span>
              <?php elseif ($isResult): ?>
              <span class="cal-badge cal-badge-upcoming"><i class="ph ph-chart-line-up"></i> Result</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
