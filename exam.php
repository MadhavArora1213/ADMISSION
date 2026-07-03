<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) session_start();

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

$pageTitle = ($exam['exam_abbreviation'] ?: $exam['exam_name']) . ': Dates, Syllabus, Pattern, Registration ' . date('Y');
$metaDesc = 'Get complete details for ' . $exam['exam_name'] . ' (' . ($exam['exam_abbreviation'] ?? '') . ') including exam dates, syllabus, exam pattern, eligibility, application fees and previous year cutoffs. Conducted by ' . ($exam['conducting_body'] ?: 'TBA') . '.';
$metaKeywords = strtolower($exam['exam_name'] ?? '') . ', ' . strtolower($exam['exam_abbreviation'] ?? '') . ', ' . strtolower($exam['exam_name'] ?? '') . ' ' . date('Y') . ', ' . strtolower($exam['exam_name'] ?? '') . ' exam dates, ' . strtolower($exam['exam_name'] ?? '') . ' syllabus, ' . strtolower($exam['exam_name'] ?? '') . ' pattern, ' . strtolower($exam['exam_name'] ?? '') . ' eligibility, ' . strtolower($exam['exam_name'] ?? '') . ' application form, ' . strtolower($exam['exam_name'] ?? '') . ' cutoff';

$siteBase = defined('BASE_URL') ? BASE_URL : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$canonicalUrl = $siteBase . '/exam/' . urlencode($slug);
$ogTitle = $pageTitle;
$ogDesc = $metaDesc;
$ogImage = !empty($exam['conducting_body_logo']) ? cImg($exam['conducting_body_logo']) : ($siteBase . '/assets/img/logo.png');

$tabIcons = [
    'info'=>'ph-info', 'dates'=>'ph-calendar-blank', 'pattern'=>'ph-grid-four',
    'syllabus'=>'ph-book-open', 'fees'=>'ph-currency-inr', 'cutoffs'=>'ph-scissors'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/favicon.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - AdmissionSeason</title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
  <link rel="canonical" href="<?= $canonicalUrl ?>">
  <meta name="author" content="AdmissionSeason">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= $canonicalUrl ?>">
  <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($ogDesc) ?>">
  <meta property="og:image" content="<?= $ogImage ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:site_name" content="AdmissionSeason">
  <meta property="og:locale" content="en_IN">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= $canonicalUrl ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($ogDesc) ?>">
  <meta name="twitter:image" content="<?= $ogImage ?>">
  <meta name="twitter:site" content="@AdmissionSeason">
  <meta name="twitter:creator" content="@AdmissionSeason">

  <!-- Structured Data: EducationalOccupationalProgram -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'EducationalOccupationalProgram',
    'name' => $exam['exam_name'],
    'alternateName' => $exam['exam_abbreviation'] ?? null,
    'url' => $canonicalUrl,
    'description' => mb_strimwidth(strip_tags($metaDesc), 0, 300, '...'),
    'educationalLevel' => ucfirst($exam['exam_level'] ?? 'National'),
    'provider' => [
      '@type' => 'Organization',
      'name' => $exam['conducting_body'] ?? 'TBA',
    ],
    'timeRequired' => !empty($exam['duration_minutes']) ? $exam['duration_minutes'] . 'M' : null,
    'occupationalCategory' => 'Entrance Examination',
    'image' => !empty($exam['conducting_body_logo']) ? cImg($exam['conducting_body_logo']) : null,
    'sameAs' => array_filter([
      $exam['official_website'] ?? null,
      $exam['application_url'] ?? null,
    ]),
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
      ['@type' => 'ListItem', 'position' => 3, 'name' => $exam['exam_name'], 'item' => $canonicalUrl],
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/college-pages.css?v=<?= time() ?>">
  <style>
    .exam-hero { background: linear-gradient(135deg, var(--cp-blue), var(--cp-blue2)); padding: 60px 0 40px; color: #fff; position: relative; overflow: visible; }
    .exam-hero-inner { display: flex; gap: 32px; align-items: flex-start; }
    .exam-hero-logo { width: 120px; height: 120px; border-radius: 20px; background: #fff; padding: 10px; object-fit: contain; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .exam-hero-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2.4rem; font-weight: 800; margin: 0 0 8px 0; }
    .exam-hero-sub { font-size: 1.1rem; color: rgba(255,255,255,0.85); margin-bottom: 16px; }
    .exam-hero-chips { display: flex; flex-wrap: wrap; gap: 12px; }
    .exam-hero-chips span { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 20px; font-size: 0.85rem; font-weight: 600; backdrop-filter: blur(4px); }
    .exam-hero-actions { margin-left: auto; display: flex; flex-direction: column; gap: 12px; }
    .exam-btn-primary { background: #fff; color: var(--cp-blue); padding: 14px 28px; border-radius: 12px; font-weight: 700; text-decoration: none; text-align: center; transition: var(--cp-trans); }
    .exam-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
    .exam-tabs-sticky { position: sticky; top: 0; z-index: 100; background: #fff; border-bottom: 1px solid var(--cp-border); box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .shiksha-tabs-nav ul { display: flex; list-style: none; padding: 0; margin: 0; overflow-x: auto; gap: 30px; scrollbar-width: none; -webkit-overflow-scrolling: touch; width: 100%; max-width: 100%; }
    .shiksha-tabs-nav ul::-webkit-scrollbar { display: none; }
    .shiksha-tabs-nav li a { display: flex; align-items: center; gap: 8px; padding: 18px 0; color: var(--cp-muted); font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; transition: var(--cp-trans); white-space: nowrap; font-size: 0.95rem; }
    .shiksha-tabs-nav li a:hover { color: var(--cp-blue); }
    .shiksha-tabs-nav li a.active { color: var(--cp-blue); border-bottom-color: var(--cp-blue); }
    .tab-content { padding-top: 40px; padding-bottom: 40px; min-height: 50vh; }
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
    @media(max-width:768px){
      .container { padding-left: 16px !important; padding-right: 16px !important; }
      .exam-hero{padding:50px 0 30px}
      .exam-hero-inner{flex-direction:column;gap:16px}
      .exam-hero-title{font-size:1.5rem}
      .exam-hero-logo{width:80px;height:80px}
      .exam-hero-actions{margin-left:0;width:100%}
      .exam-btn-primary{width:100%}
      .exam-hero-chips{gap:8px}
      .exam-hero-chips span{padding:5px 10px;font-size:.78rem}
      .info-grid{grid-template-columns:1fr}
      .shiksha-tabs-nav ul{gap:0}
      .shiksha-tabs-nav li a{padding:14px 12px;font-size:.82rem}
      .exam-hero a[style*="position:absolute"]{position:relative!important;top:auto!important;left:auto!important;margin-bottom:8px;display:inline-flex!important}
    }
    @media(max-width:480px){
      .exam-hero-chips{flex-wrap:wrap}
      .info-grid{grid-template-columns:1fr}
    }
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- HERO -->
<div class="exam-hero">
  <div class="container exam-hero-inner">
    <a href="<?= BASE_URL ?>/exams" style="position:absolute; top:18px; left:20px; color:rgba(255,255,255,0.85); text-decoration:none; font-size:0.88rem; font-weight:600; display:inline-flex; align-items:center; gap:5px; background:rgba(255,255,255,0.1); padding:7px 16px; border-radius:100px; border:1px solid rgba(255,255,255,0.2); transition:all 0.2s; z-index:2;"><i class="ph ph-arrow-left"></i> Back to Exams</a>
    <img src="<?= cImg($exam['conducting_body_logo']) ?>" class="exam-hero-logo" alt="<?= htmlspecialchars($exam['exam_abbreviation'] ?? $exam['exam_name'] ?? '') ?>">
    <div>
      <h1 class="exam-hero-title"><?= htmlspecialchars($exam['exam_name']) ?><?= !empty($exam['exam_abbreviation']) ? ' (' . htmlspecialchars($exam['exam_abbreviation']) . ')' : '' ?></h1>
      <p class="exam-hero-sub">Conducted by <?= htmlspecialchars($exam['conducting_body'] ?: 'TBA') ?></p>
      <div class="exam-hero-chips">
        <span><i class="ph ph-bank"></i> <?= ucfirst($exam['exam_level'] ?? 'National') ?> Level</span>
        <span><i class="ph ph-laptop"></i> <?= ucfirst($exam['exam_mode'] ?? 'Online') ?></span>
        <?php if ($exam['duration_minutes']): ?>
        <span><i class="ph ph-timer"></i> <?= (int)$exam['duration_minutes'] ?> Mins</span>
        <?php endif; ?>
        <?php if ($exam['applicants_last_year']): ?>
        <span><i class="ph ph-users"></i> <?= number_format((int)$exam['applicants_last_year']/100000, 1) ?>L+ Applicants</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="exam-hero-actions">
      <?php if ($exam['application_url']): ?>
      <a href="<?= htmlspecialchars($exam['application_url']) ?>" target="_blank" class="exam-btn-primary">
        Apply Now <i class="ph-bold ph-arrow-up-right"></i>
      </a>
      <?php endif; ?>
      <?php if ($exam['official_website'] && ($exam['official_website'] !== ($exam['application_url'] ?? ''))): ?>
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
      <h2 style="font-size:1.5rem;font-weight:800;color:var(--cp-blue);margin-bottom:24px">About <?= htmlspecialchars($exam['exam_abbreviation'] ?? $exam['exam_name']) ?></h2>
      <p style="font-size:1.05rem;line-height:1.7;color:rgba(15,23,42,0.8)"><?= nl2br(htmlspecialchars($exam['normalisation_method'] ?: $exam['exam_name'] . ' is a ' . ($exam['exam_level'] ?? 'national') . ' level ' . ($exam['exam_mode'] ?? 'online') . ' examination conducted by ' . ($exam['conducting_body'] ?: 'the relevant authority') . '. It is held ' . ($exam['exam_frequency'] ?: 'annually') . ' and attracts ' . number_format((int)($exam['applicants_last_year'] ?? 0)) . '+ applicants every year.')) ?></p>
      
      <h3 style="margin-top:40px;font-size:1.3rem;font-weight:700">Exam Highlights</h3>
      <div class="info-grid">
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-identification-card"></i></div>
          <div class="info-body"><h4>Exam Name</h4><p><?= htmlspecialchars($exam['exam_name']) ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-bank"></i></div>
          <div class="info-body"><h4>Conducting Body</h4><p><?= htmlspecialchars($exam['conducting_body'] ?: 'TBA') ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-calendar"></i></div>
          <div class="info-body"><h4>Frequency</h4><p><?= ucfirst($exam['exam_frequency'] ?? 'Annual') ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-globe"></i></div>
          <div class="info-body"><h4>Level</h4><p><?= ucfirst($exam['exam_level'] ?? 'National') ?></p></div>
        </div>
      </div>

      <h3 style="margin-top:40px;font-size:1.3rem;font-weight:700">Eligibility Criteria</h3>
      <div class="info-grid">
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-user"></i></div>
          <div class="info-body"><h4>Age Limit</h4><p><?= $exam['age_min'] ? $exam['age_min'] . ' - ' . ($exam['age_max'] ?: 'No limit') . ' Years' : 'Check official website' ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-percent"></i></div>
          <div class="info-body"><h4>Min Percentage</h4><p><?= $exam['min_percentage_required'] ? $exam['min_percentage_required'] . '% in qualifying exam' : 'Check official notification' ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-graduation-cap"></i></div>
          <div class="info-body"><h4>Qualifying Exam</h4><p><?= htmlspecialchars($exam['qualifying_exam'] ?: 'Check official notification') ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-flag"></i></div>
          <div class="info-body"><h4>Nationality</h4><p><?= ucfirst($exam['nationality'] ?: 'Indian') ?></p></div>
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
            <div class="timeline-date"><?= date('d F Y', strtotime($d['event_date'])) ?> <?= $d['is_tentative'] ? '<span style="color:#0F172A;font-size:0.75rem">(Tentative)</span>' : '' ?></div>
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
          <div class="info-body"><h4>Mode of Exam</h4><p><?= ucfirst($exam['exam_mode'] ?? 'Online') ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-timer"></i></div>
          <div class="info-body"><h4>Duration</h4><p><?= $exam['duration_minutes'] ? $exam['duration_minutes'] . ' Minutes' : 'TBA' ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-list-numbers"></i></div>
          <div class="info-body"><h4>Total Questions</h4><p><?= $exam['total_questions'] ?: 'TBA' ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-target"></i></div>
          <div class="info-body"><h4>Total Marks</h4><p><?= $exam['total_marks'] ?: 'TBA' ?></p></div>
        </div>
      </div>

      <h3 style="font-size:1.3rem;font-weight:700;margin-bottom:16px">Marking Scheme</h3>
      <?php
        $msData = json_decode($exam['marking_scheme'] ?? '', true);
        if (is_array($msData)):
      ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-bottom:32px">
        <?php foreach ($msData as $action => $label):
          $actStr = (string)$action;
          $isPositive = (strpos($actStr, '+') === 0);
          $bgColor = $isPositive ? 'rgba(22,163,74,0.06)' : 'rgba(220,38,38,0.06)';
          $borderColor = $isPositive ? 'rgba(22,163,74,0.15)' : 'rgba(220,38,38,0.15)';
          $textColor = $isPositive ? '#16a34a' : '#dc2626';
          $desc = $isPositive ? 'Correct answer' : (stripos($label, 'unattempt') !== false ? 'Unattempted' : 'Incorrect answer');
        ?>
        <div style="display:flex;align-items:center;gap:14px;padding:18px 20px;background:<?= $bgColor ?>;border:1.5px solid <?= $borderColor ?>;border-radius:14px">
          <div style="width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;font-weight:800;color:<?= $textColor ?>;background:#fff;flex-shrink:0;border:1.5px solid <?= $borderColor ?>">
            <?= htmlspecialchars($actStr) ?>
          </div>
          <div>
            <div style="font-size:.92rem;font-weight:700;color:#0F172A"><?= htmlspecialchars($label) ?></div>
            <div style="font-size:.78rem;color:rgba(15,23,42,.5)"><?= $desc ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p style="font-size:1.05rem;line-height:1.7;color:rgba(15,23,42,0.8);margin-bottom:32px"><?= nl2br(htmlspecialchars($exam['marking_scheme'] ?: 'Marking scheme details will be updated soon.')) ?></p>
      <?php endif; ?>

      <?php
        $sections = json_decode($exam['sections'] ?? '', true);
        if (is_array($sections) && count($sections) > 0):
      ?>
      <h3 style="font-size:1.3rem;font-weight:700;margin-bottom:16px">Sections</h3>
      <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:32px">
        <?php foreach ($sections as $i => $sec): ?>
        <div style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:rgba(37,99,235,.06);border:1.5px solid rgba(37,99,235,.12);border-radius:10px;font-size:.88rem;font-weight:600;color:#19376D">
          <span style="width:24px;height:24px;border-radius:6px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;flex-shrink:0"><?= $i + 1 ?></span>
          <?= htmlspecialchars($sec) ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

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
                  <?php if($t['subtopics']): ?>
                    <?php
                      $subtopics = json_decode($t['subtopics'], true);
                      if (is_array($subtopics)):
                    ?>
                    <div style="font-size:0.85rem;color:rgba(15,23,42,0.6);font-weight:400;margin-top:6px;line-height:1.7">
                      <?php foreach($subtopics as $st): ?>
                        <span style="display:inline-block;background:var(--cp-light);border:1px solid var(--cp-border);padding:2px 10px;border-radius:12px;margin:2px 4px 2px 0;font-size:0.8rem"><?= htmlspecialchars($st) ?></span>
                      <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                      <div style="font-size:0.85rem;color:rgba(15,23,42,0.6);font-weight:400;margin-top:4px"><?= htmlspecialchars($t['subtopics']) ?></div>
                    <?php endif; ?>
                  <?php endif; ?>
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
          <div class="info-body"><h4>General Category</h4><p>₹<?= number_format((float)($exam['application_fee_general'] ?? 0)) ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-currency-inr"></i></div>
          <div class="info-body"><h4>OBC Category</h4><p>₹<?= number_format((float)($exam['application_fee_obc'] ?? 0)) ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-currency-inr"></i></div>
          <div class="info-body"><h4>SC/ST Category</h4><p>₹<?= number_format((float)($exam['application_fee_sc_st'] ?? 0)) ?></p></div>
        </div>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-currency-inr"></i></div>
          <div class="info-body"><h4>Female Candidates</h4><p>₹<?= number_format((float)($exam['application_fee_female'] ?? 0)) ?></p></div>
        </div>
        <?php if (!empty($exam['application_fee_pwd'])): ?>
        <div class="info-card">
          <div class="info-icon"><i class="ph ph-currency-inr"></i></div>
          <div class="info-body"><h4>PwD Category</h4><p>₹<?= number_format((float)($exam['application_fee_pwd'] ?? 0)) ?></p></div>
        </div>
        <?php endif; ?>
      </div>

    <?php elseif ($tab === 'cutoffs'): ?>
      <h2 style="font-size:1.5rem;font-weight:800;color:var(--cp-blue);margin-bottom:24px">Previous Year Cut-Offs</h2>
      <?php if(empty($cutoffs)): ?>
        <p>No cut-off data available yet.</p>
      <?php else: ?>
        <table style="width:100%;border-collapse:collapse;margin-top:16px">
          <thead>
            <tr style="border-bottom:2px solid var(--cp-border);text-align:left;background:var(--cp-light)">
              <th style="padding:12px">Year</th>
              <th style="padding:12px">Category</th>
              <th style="padding:12px">Opening Rank</th>
              <th style="padding:12px">Closing Rank</th>
              <th style="padding:12px">Round</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($cutoffs as $c): ?>
            <tr style="border-bottom:1px solid var(--cp-border)">
              <td style="padding:16px 12px;font-weight:600"><?= (int)$c['year'] ?></td>
              <td style="padding:16px 12px"><span style="display:inline-block;padding:2px 10px;border-radius:12px;font-size:0.8rem;font-weight:600;background:<?= $c['category']==='General' ? '#e0f2fe' : ($c['category']==='OBC' ? '#fef3c7' : ($c['category']==='SC' ? '#ede9fe' : ($c['category']==='ST' ? '#fce7f3' : '#d1fae5'))) ?>;color:<?= $c['category']==='General' ? '#0369a1' : ($c['category']==='OBC' ? '#92400e' : ($c['category']==='SC' ? '#6b21a8' : ($c['category']==='ST' ? '#9d174d' : '#065f46'))) ?>"><?= htmlspecialchars($c['category']) ?></span></td>
              <td style="padding:16px 12px"><?= number_format((int)$c['opening_rank']) ?></td>
              <td style="padding:16px 12px;font-weight:700;color:#0F172A"><?= number_format((int)$c['closing_rank']) ?></td>
              <td style="padding:16px 12px"><?= $c['round'] == 0 ? 'Prelims' : 'Final' ?></td>
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
