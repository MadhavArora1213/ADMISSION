<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/exam_helpers.php';

$slug = trim($_GET['slug'] ?? '');
$tab  = trim($_GET['tab'] ?? 'info');
$tabs = examTabs();

if ($slug === '') {
    header('Location: exams.php');
    exit;
}
if (!isset($tabs[$tab])) {
    $tab = 'info';
}

$exam = loadExamBySlug($pdo, $slug);
if (!$exam) {
    header('HTTP/1.0 404 Not Found');
    header('Location: exams.php');
    exit;
}

$exam_id = $exam['id'];
$dates = getExamDates($pdo, $exam_id);
$syllabus = getExamSyllabus($pdo, $exam_id);
$cutoffs = getExamCutoffs($pdo, $exam_id);

$pageTitle = $exam['exam_abbreviation'] . ' 2026: Dates, Syllabus, Pattern, Registration';
$metaDesc = 'Complete details for ' . $exam['exam_name'] . ' including exam dates, syllabus, exam pattern, eligibility, and cutoffs.';

$tabIcons = [
    'info'=>'ph-info', 'dates'=>'ph-calendar-blank', 'pattern'=>'ph-grid-four',
    'syllabus'=>'ph-book-open', 'fees'=>'ph-currency-inr', 'cutoffs'=>'ph-scissors'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - AdmissionSeason</title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/college-pages.css?v=<?= time() ?>">
  <style>
    .exam-hero { background: linear-gradient(135deg, var(--cp-blue), var(--cp-blue2)); padding: 60px 0 40px; color: #fff; position: relative; }
    .exam-hero-inner { display: flex; gap: 32px; align-items: flex-start; }
    .exam-hero-logo { width: 120px; height: 120px; border-radius: 20px; background: #fff; padding: 10px; object-fit: contain; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .exam-hero-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2.4rem; font-weight: 800; margin: 0 0 8px 0; }
    .exam-hero-sub { font-size: 1.1rem; color: #cbd5e1; margin-bottom: 16px; }
    .exam-hero-chips { display: flex; flex-wrap: wrap; gap: 12px; }
    .exam-hero-chips span { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 20px; font-size: 0.85rem; font-weight: 600; backdrop-filter: blur(4px); }
    .exam-hero-actions { margin-left: auto; display: flex; flex-direction: column; gap: 12px; }
    .exam-btn-primary { background: #fff; color: var(--cp-blue); padding: 14px 28px; border-radius: 12px; font-weight: 700; text-decoration: none; text-align: center; transition: var(--cp-trans); }
    .exam-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
    .exam-tabs-sticky { position: sticky; top: 0; z-index: 100; background: #fff; border-bottom: 1px solid var(--cp-border); box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .shiksha-tabs-nav ul { display: flex; list-style: none; padding: 0; margin: 0; overflow-x: auto; gap: 30px; }
    .shiksha-tabs-nav li a { display: flex; align-items: center; gap: 8px; padding: 18px 0; color: var(--cp-muted); font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; transition: var(--cp-trans); white-space: nowrap; font-size: 0.95rem; }
    .shiksha-tabs-nav li a:hover { color: var(--cp-blue); }
    .shiksha-tabs-nav li a.active { color: var(--cp-blue); border-bottom-color: var(--cp-blue); }
    .tab-content { padding: 40px 0; min-height: 50vh; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 24px; }
    .info-card { background: #fff; border: 1px solid var(--cp-border); border-radius: 14px; padding: 20px; display: flex; align-items: flex-start; gap: 16px; }
    .info-icon { width: 48px; height: 48px; border-radius: 12px; background: var(--cp-light); display: flex; align-items: center; justify-content: center; color: var(--cp-blue); font-size: 1.5rem; flex-shrink: 0; }
    .info-body h4 { font-size: 0.8rem; color: var(--cp-muted); text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 4px 0; font-weight: 700; }
    .info-body p { font-size: 1.1rem; color: var(--cp-text); font-weight: 600; margin: 0; }
    .timeline { border-left: 2px solid var(--cp-border); padding-left: 24px; margin-top: 30px; }
    .timeline-item { position: relative; margin-bottom: 30px; }
    .timeline-item::before { content: ''; position: absolute; left: -31px; top: 0; width: 14px; height: 14px; border-radius: 50%; background: var(--cp-blue); border: 3px solid #fff; box-shadow: 0 0 0 2px var(--cp-blue); }
    .timeline-date { font-weight: 700; color: var(--cp-blue); font-size: 0.9rem; margin-bottom: 4px; }
    .timeline-event { font-size: 1.1rem; font-weight: 600; color: var(--cp-text); margin: 0; }
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- HERO -->
<div class="exam-hero">
  <div class="container exam-hero-inner">
    <img src="<?= cImg($exam['conducting_body_logo']) ?>" class="exam-hero-logo" alt="<?= htmlspecialchars($exam['exam_abbreviation']) ?>">
    <div>
      <h1 class="exam-hero-title"><?= htmlspecialchars($exam['exam_name']) ?> (<?= htmlspecialchars($exam['exam_abbreviation']) ?>)</h1>
      <p class="exam-hero-sub">Conducted by <?= htmlspecialchars($exam['conducting_body']) ?></p>
      <div class="exam-hero-chips">
        <span><i class="ph ph-bank"></i> <?= ucfirst($exam['exam_level']) ?> Level</span>
        <span><i class="ph ph-laptop"></i> <?= ucfirst($exam['exam_mode']) ?></span>
        <span><i class="ph ph-timer"></i> <?= $exam['duration_minutes'] ?> Mins</span>
        <span><i class="ph ph-users"></i> <?= number_format($exam['applicants_last_year']/100000, 1) ?>L+ Applicants</span>
      </div>
    </div>
    <div class="exam-hero-actions">
      <?php if ($exam['application_url']): ?>
      <a href="<?= htmlspecialchars($exam['application_url']) ?>" target="_blank" class="exam-btn-primary">
        Apply Now <i class="ph-bold ph-arrow-up-right"></i>
      </a>
      <?php endif; ?>
      <?php if ($exam['official_website']): ?>
      <a href="<?= htmlspecialchars($exam['official_website']) ?>" target="_blank" style="color:#fff;text-align:center;text-decoration:underline;font-weight:500;font-size:0.9rem">
        Official Website
      </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- TABS -->
<div class="exam-tabs-sticky shiksha-tabs-nav">
  <div class="container">
    <ul>
      <?php foreach ($tabs as $k => $label): ?>
      <li>
        <a href="<?= examUrl($slug, $k) ?>" class="<?= $tab === $k ? 'active' : '' ?>">
          <i class="ph <?= $tabIcons[$k] ?? 'ph-circle' ?>"></i> <?= htmlspecialchars($label) ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<!-- CONTENT -->
<div class="container tab-content">
  <div class="college-card" style="padding:32px;">
    
    <?php if ($tab === 'info'): ?>
      <h2 style="font-size:1.5rem;font-weight:800;color:var(--cp-blue);margin-bottom:24px">About <?= htmlspecialchars($exam['exam_abbreviation']) ?></h2>
      <p style="font-size:1.05rem;line-height:1.7;color:#334155"><?= nl2br(htmlspecialchars($exam['normalisation_method'] ?? 'No additional details provided.')) ?></p>
      
      <h3 style="margin-top:40px;font-size:1.3rem;font-weight:700">Exam Highlights</h3>
      <div class="info-grid">
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-identification-card"></i></div>
          <div class="info-body"><h4>Exam Name</h4><p><?= htmlspecialchars($exam['exam_name']) ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-bank"></i></div>
          <div class="info-body"><h4>Conducting Body</h4><p><?= htmlspecialchars($exam['conducting_body']) ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-calendar"></i></div>
          <div class="info-body"><h4>Frequency</h4><p><?= ucfirst($exam['exam_frequency']) ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-globe"></i></div>
          <div class="info-body"><h4>Level</h4><p><?= ucfirst($exam['exam_level']) ?></p></div>
        </div>
      </div>

      <h3 style="margin-top:40px;font-size:1.3rem;font-weight:700">Eligibility Criteria</h3>
      <div class="info-grid">
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-user"></i></div>
          <div class="info-body"><h4>Age Limit</h4><p><?= $exam['age_min'] ?> - <?= $exam['age_max'] ?: 'No limit' ?> Years</p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-percent"></i></div>
          <div class="info-body"><h4>Min Percentage</h4><p><?= $exam['min_percentage_required'] ?>% in qualifying exam</p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-graduation-cap"></i></div>
          <div class="info-body"><h4>Qualifying Exam</h4><p><?= htmlspecialchars($exam['qualifying_exam']) ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-flag"></i></div>
          <div class="info-body"><h4>Nationality</h4><p><?= ucfirst($exam['nationality']) ?></p></div>
        </div>
      </div>

    <?php elseif ($tab === 'dates'): ?>
      <h2 style="font-size:1.5rem;font-weight:800;color:var(--cp-blue);margin-bottom:24px">Important Dates</h2>
      <?php if(empty($dates)): ?>
        <p>No dates announced yet.</p>
      <?php else: ?>
        <div class="timeline">
          <?php foreach($dates as $d): ?>
          <div class="timeline-item">
            <div class="timeline-date"><?= date('d F Y', strtotime($d['event_date'])) ?> <?= $d['is_tentative'] ? '<span style="color:#ef4444;font-size:0.75rem">(Tentative)</span>' : '' ?></div>
            <p class="timeline-event"><?= htmlspecialchars($d['event_name']) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php elseif ($tab === 'pattern'): ?>
      <h2 style="font-size:1.5rem;font-weight:800;color:var(--cp-blue);margin-bottom:24px">Exam Pattern</h2>
      <div class="info-grid" style="margin-bottom:40px">
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-laptop"></i></div>
          <div class="info-body"><h4>Mode of Exam</h4><p><?= ucfirst($exam['exam_mode']) ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-timer"></i></div>
          <div class="info-body"><h4>Duration</h4><p><?= $exam['duration_minutes'] ?> Minutes</p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-list-numbers"></i></div>
          <div class="info-body"><h4>Total Questions</h4><p><?= $exam['total_questions'] ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-target"></i></div>
          <div class="info-body"><h4>Total Marks</h4><p><?= $exam['total_marks'] ?></p></div>
        </div>
      </div>

      <h3 style="font-size:1.3rem;font-weight:700;margin-bottom:16px">Marking Scheme</h3>
      <p style="font-size:1.05rem;line-height:1.7;color:#334155"><?= nl2br(htmlspecialchars($exam['marking_scheme'])) ?></p>

    <?php elseif ($tab === 'syllabus'): ?>
      <h2 style="font-size:1.5rem;font-weight:800;color:var(--cp-blue);margin-bottom:24px">Syllabus</h2>
      <?php if(empty($syllabus)): ?>
        <p>Syllabus not available.</p>
      <?php else: ?>
        <?php 
          $bySubject = [];
          foreach($syllabus as $s) $bySubject[$s['subject']][] = $s;
        ?>
        <?php foreach($bySubject as $subject => $topics): ?>
          <h3 style="margin-top:32px;padding:12px 16px;background:var(--cp-light);border-radius:8px;font-size:1.2rem"><?= htmlspecialchars($subject) ?></h3>
          <table style="width:100%;border-collapse:collapse;margin-top:16px">
            <thead>
              <tr style="border-bottom:2px solid var(--cp-border);text-align:left">
                <th style="padding:12px;color:var(--cp-muted)">Topic</th>
                <th style="padding:12px;color:var(--cp-muted);width:150px">Weightage</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($topics as $t): ?>
              <tr style="border-bottom:1px solid var(--cp-border)">
                <td style="padding:16px 12px;font-weight:600"><?= htmlspecialchars($t['topic']) ?>
                  <?php if($t['subtopics']): ?><div style="font-size:0.85rem;color:#64748b;font-weight:400;margin-top:4px"><?= htmlspecialchars($t['subtopics']) ?></div><?php endif; ?>
                </td>
                <td style="padding:16px 12px;color:var(--cp-blue);font-weight:700"><?= $t['weightage_pct'] ?>%</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endforeach; ?>
      <?php endif; ?>

    <?php elseif ($tab === 'fees'): ?>
      <h2 style="font-size:1.5rem;font-weight:800;color:var(--cp-blue);margin-bottom:24px">Application Fees</h2>
      <div class="info-grid">
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-currency-inr"></i></div>
          <div class="info-body"><h4>General Category</h4><p>₹<?= number_format((float)$exam['application_fee_general']) ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-currency-inr"></i></div>
          <div class="info-body"><h4>OBC Category</h4><p>₹<?= number_format((float)$exam['application_fee_obc']) ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-currency-inr"></i></div>
          <div class="info-body"><h4>SC/ST Category</h4><p>₹<?= number_format((float)$exam['application_fee_sc_st']) ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-currency-inr"></i></div>
          <div class="info-body"><h4>Female Candidates</h4><p>₹<?= number_format((float)$exam['application_fee_female']) ?></p></div>
        </div>
      </div>

    <?php elseif ($tab === 'cutoffs'): ?>
      <h2 style="font-size:1.5rem;font-weight:800;color:var(--cp-blue);margin-bottom:24px">Previous Year Cut-Offs</h2>
      <?php if(empty($cutoffs)): ?>
        <p>No cut-off data available yet.</p>
      <?php else: ?>
        <table style="width:100%;border-collapse:collapse;margin-top:16px">
          <thead>
            <tr style="border-bottom:2px solid var(--cp-border);text-align:left;background:var(--cp-light)">
              <th style="padding:12px">College</th>
              <th style="padding:12px">Course</th>
              <th style="padding:12px">Category</th>
              <th style="padding:12px">Opening Rank</th>
              <th style="padding:12px">Closing Rank</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($cutoffs as $c): ?>
            <tr style="border-bottom:1px solid var(--cp-border)">
              <td style="padding:16px 12px;font-weight:600"><a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/college/<?= $c['college_slug'] ?>" style="color:var(--cp-blue);text-decoration:none"><?= htmlspecialchars($c['college_name']) ?></a></td>
              <td style="padding:16px 12px"><?= htmlspecialchars($c['course_name'] ?? '-') ?></td>
              <td style="padding:16px 12px"><?= htmlspecialchars($c['category']) ?></td>
              <td style="padding:16px 12px"><?= $c['opening_rank'] ?></td>
              <td style="padding:16px 12px;font-weight:700;color:#dc2626"><?= $c['closing_rank'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
