<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';
require_once __DIR__ . '/includes/course_helpers.php';

$slug = trim($_GET['slug'] ?? '');
$tab  = trim($_GET['tab'] ?? 'basic');
$tabs = courseTabs();

if ($slug === '') {
    header('Location: courses.php');
    exit;
}
if (!isset($tabs[$tab])) {
    $tab = 'basic';
}

$course = loadCourseBySlug($pdo, $slug);
if (!$course) {
    header('HTTP/1.0 404 Not Found');
    header('Location: courses.php');
    exit;
}

$course_id = $course['id'];
$specs = getCourseSpecializations($pdo, $course_id);
$careers = getCourseCareers($pdo, $course_id);

$pageTitle = $course['course_name'] . ' 2026: Scope, Fees, Specializations, Jobs & Top Colleges';
$metaDesc = 'Details about ' . $course['course_name'] . ' including average salary, eligibility, specializations, career paths and top colleges in India.';

$tabIcons = [
    'basic'           => 'ph-info',
    'scope'           => 'ph-compass',
    'salary'          => 'ph-money',
    'specializations' => 'ph-git-branch',
    'careers'         => 'ph-briefcase',
];

function recruiterInitials(string $name): string {
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

$recruiters = [];
if (!empty($course['top_recruiters'])) {
    $recruiters = json_decode($course['top_recruiters'], true) ?: [];
}

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/favicon.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - AdmissionSeason</title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/college-pages.css?v=<?= time() ?>">
  <style>
    .course-hero { background: linear-gradient(135deg, #0B2447 0%, #19376D 100%), url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M54.627 0l.83.83-49.12 49.12L5.5 49.12 54.627 0zM0 54.627l.83.83L5.5 54.627 0 49.12v5.507z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E"); padding: 80px 0 60px; color: #fff; position: relative; overflow: hidden; }
    .course-hero::after { content:''; position:absolute; bottom:0; left:0; right:0; height:40px; background:linear-gradient(to top, rgba(255,255,255,0.1), transparent); pointer-events:none; }
    .course-hero-inner { display: flex; gap: 32px; align-items: flex-start; position: relative; z-index: 2; }
    .course-hero-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2.2rem; font-weight: 800; margin: 0 0 16px 0; line-height: 1.2; text-shadow: 0 2px 10px rgba(0,0,0,0.2); }
    .course-hero-chips { display: flex; flex-wrap: wrap; gap: 12px; }
    .course-hero-chips span { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); border-radius: 30px; font-size: 0.82rem; font-weight: 600; backdrop-filter: blur(8px); }
    .course-hero-chips span i { font-size: 1rem; }
    .course-hero-actions { margin-left: auto; display: flex; flex-direction: column; gap: 12px; }
    .course-btn-primary { background: #fff; color: var(--cp-blue); padding: 12px 24px; border-radius: 50px; font-weight: 700; text-decoration: none; text-align: center; transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(0,0,0,0.12); font-size: 0.9rem; display: inline-flex; align-items: center; gap: 6px; }
    .course-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.2); background: #f8fafc; color: #0f172a; }

    .course-tabs-sticky { position: sticky; top: 0; z-index: 100; background: rgba(255,255,255,0.97); border-bottom: 1px solid var(--cp-border); box-shadow: 0 4px 20px rgba(0,0,0,0.04); backdrop-filter: blur(10px); }
    .shiksha-tabs-nav ul { display: flex; list-style: none; padding: 0; margin: 0; overflow-x: auto; gap: 0; scrollbar-width: none; -webkit-overflow-scrolling: touch; width: 100%; max-width: 100%; }
    .shiksha-tabs-nav ul::-webkit-scrollbar { display: none; }
    .shiksha-tabs-nav li a { display: flex; align-items: center; gap: 6px; padding: 14px 18px; color: rgba(15,23,42,0.45); font-weight: 700; text-decoration: none; border-bottom: 2px solid transparent; transition: all 0.3s ease; white-space: nowrap; font-size: 0.85rem; }
    .shiksha-tabs-nav li a:hover { color: var(--cp-blue); background: rgba(11,36,71,0.02); }
    .shiksha-tabs-nav li a.active { color: var(--cp-blue); border-bottom-color: var(--cp-blue); }

    .tab-content { padding-top: 32px; padding-bottom: 32px; min-height: 40vh; }

    .info-card { background: #fff; border-radius: 14px; padding: 24px; border: 1px solid var(--cp-border); box-shadow: 0 4px 20px rgba(0,0,0,0.02); margin-bottom: 20px; position: relative; overflow: hidden; }
    .info-card::before { content:''; position:absolute; left:0; top:0; width:4px; height:100%; background: linear-gradient(to bottom, var(--cp-blue), #19376D); border-radius: 14px 0 0 14px; }
    .info-card-title { font-size: 1.1rem; font-weight: 800; color: var(--cp-blue); margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
    .info-card-title i { color: #19376D; background: rgba(11,36,71,0.06); padding: 7px; border-radius: 10px; font-size: 1.1rem; }
    .info-card-content { font-size: 0.9rem; line-height: 1.7; color: rgba(15,23,42,0.65); }

    .highlight-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 20px; }
    .highlight-box { background: #fff; border: 1px solid var(--cp-border); border-radius: 12px; padding: 18px; text-align: center; transition: all 0.3s ease; }
    .highlight-box:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.05); }
    .highlight-box i { font-size: 1.4rem; color: var(--cp-blue); margin-bottom: 6px; display: block; }
    .highlight-box .hl-val { font-size: 1.1rem; font-weight: 800; color: #0f172a; display: block; margin-bottom: 2px; }
    .highlight-box .hl-label { font-size: 0.72rem; color: rgba(15,23,42,0.45); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

    .recruiter-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
    .recruiter-item { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid var(--cp-border); border-radius: 10px; padding: 10px 14px; transition: all 0.3s ease; flex: 0 0 auto; }
    .recruiter-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: var(--cp-blue); transform: translateY(-1px); }
    .recruiter-logo { width: 36px; height: 36px; border-radius: 8px; object-fit: cover; background: var(--cp-blue); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 0.75rem; flex-shrink: 0; overflow: hidden; }
    .recruiter-logo img { width: 100%; height: 100%; object-fit: cover; }
    .recruiter-name { font-weight: 600; color: #0f172a; font-size: 0.82rem; white-space: nowrap; }

    .salary-overview { background: #fff; border-radius: 14px; border: 1px solid var(--cp-border); overflow: hidden; margin-bottom: 20px; }
    .salary-overview-header { background: linear-gradient(135deg, #0B2447, #19376D); padding: 20px 28px; color: #fff; }
    .salary-overview-header h3 { margin: 0; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 8px; }
    .salary-range-row { display: flex; align-items: center; gap: 16px; padding: 16px 28px; border-bottom: 1px solid rgba(15,23,42,0.06); }
    .salary-range-row:last-child { border-bottom: none; }
    .salary-range-label { font-weight: 700; color: #0f172a; font-size: 0.88rem; min-width: 100px; }
    .salary-range-bar { flex: 1; height: 8px; background: rgba(11,36,71,0.06); border-radius: 8px; position: relative; overflow: hidden; }
    .salary-range-fill { height: 100%; border-radius: 8px; background: linear-gradient(90deg, #0B2447, #19376D); transition: width 1s ease; }
    .salary-range-val { font-weight: 800; color: var(--cp-blue); font-size: 0.95rem; min-width: 100px; text-align: right; }

    .specs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-top: 20px; }
    .spec-card { background: #fff; border: 1px solid var(--cp-border); border-radius: 14px; padding: 20px; transition: all 0.3s ease; display: flex; flex-direction: column; gap: 8px; position: relative; overflow: hidden; }
    .spec-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(11,36,71,0.06); border-color: var(--cp-blue); }
    .spec-card-icon { width: 40px; height: 40px; background: rgba(11,36,71,0.06); color: var(--cp-blue); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; transition: all 0.3s ease; }
    .spec-card:hover .spec-card-icon { background: var(--cp-blue); color: #fff; transform: scale(1.05); }
    .spec-card h4 { font-size: 0.95rem; color: #0f172a; margin: 0; font-weight: 700; }
    .spec-card p { font-size: 0.82rem; color: rgba(15,23,42,0.5); margin: 0; line-height: 1.5; }
    .spec-popular-badge { position: absolute; top: 12px; right: 12px; background: linear-gradient(135deg, #0B2447, #19376D); color: #fff; padding: 3px 10px; border-radius: 14px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }

    .career-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; margin-top: 20px; }
    .career-card { background: #fff; border-radius: 14px; padding: 0; border: 1px solid var(--cp-border); box-shadow: 0 4px 16px rgba(0,0,0,0.02); transition: all 0.3s ease; overflow: hidden; }
    .career-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(11,36,71,0.06); border-color: var(--cp-blue); }
    .career-card-top { padding: 18px 18px 0; }
    .career-role { font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
    .career-role i { color: #fff; font-size: 0.9rem; background: linear-gradient(135deg, #0B2447, #19376D); padding: 7px; border-radius: 10px; }
    .career-growth { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 14px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; margin-top: 6px; }
    .career-growth.high { background: rgba(16,185,129,0.1); color: #059669; }
    .career-growth.medium { background: rgba(245,158,11,0.1); color: #D97706; }
    .career-growth.low { background: rgba(239,68,68,0.1); color: #DC2626; }
    .career-salary-box { display: flex; margin: 14px 0 0; background: #f8fafc; border-top: 1px solid rgba(15,23,42,0.06); border-bottom: 1px solid rgba(15,23,42,0.06); }
    .career-salary-item { flex: 1; text-align: center; padding: 12px 10px; }
    .career-salary-item:first-child { border-right: 1px solid rgba(15,23,42,0.06); }
    .career-salary-label { font-size: 0.68rem; color: rgba(15,23,42,0.45); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
    .career-salary-val { font-size: 1rem; font-weight: 800; color: #0B2447; }
    .career-card-body { padding: 14px 18px 18px; }
    .career-companies-label, .career-skills-label { font-size: 0.75rem; color: rgba(15,23,42,0.45); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: flex; align-items: center; gap: 5px; }
    .career-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
    .career-tag { background: rgba(11,36,71,0.05); color: var(--cp-blue); padding: 4px 10px; border-radius: 14px; font-size: 0.75rem; font-weight: 600; }
    .career-skills { display: flex; flex-wrap: wrap; gap: 5px; }
    .career-skill { background: rgba(16,185,129,0.08); color: #059669; padding: 3px 10px; border-radius: 14px; font-size: 0.72rem; font-weight: 600; }

    .section-header { margin-bottom: 6px; }
    .section-header h2 { font-size: 1.3rem; font-weight: 800; color: var(--cp-blue); margin: 0 0 6px 0; }
    .section-header p { color: rgba(15,23,42,0.45); font-size: 0.88rem; margin: 0; }

    .empty-state { background: #fff; padding: 40px 16px; text-align: center; border-radius: 14px; border: 1px dashed rgba(15,23,42,0.15); }
    .empty-state i { font-size: 2rem; color: rgba(15,23,42,0.2); margin-bottom: 10px; display: block; }
    .empty-state p { font-size: 0.9rem; color: rgba(15,23,42,0.4); }

    @media (max-width: 768px) {
      .container { padding-left: 16px !important; padding-right: 16px !important; }
      .course-hero { padding: 50px 0 30px; }
      .course-hero-inner { flex-direction: column; gap: 16px; }
      .course-hero-title { font-size: 1.5rem; }
      .course-hero-actions { margin-left: 0; width: 100%; }
      .course-btn-primary { width: 100%; justify-content: center; }
      .shiksha-tabs-nav ul { gap: 0; }
      .shiksha-tabs-nav li a { padding: 12px 12px; font-size: 0.78rem; gap: 4px; }
      .shiksha-tabs-nav li a i { font-size: 0.95rem; }
      .tab-content { padding-top: 20px; padding-bottom: 20px; }
      .info-card { padding: 18px; }
      .highlight-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
      .highlight-box { padding: 14px; }
      .highlight-box i { font-size: 1.2rem; }
      .highlight-box .hl-val { font-size: 0.95rem; }
      .specs-grid { grid-template-columns: 1fr; }
      .career-grid { grid-template-columns: 1fr; }
      .salary-range-row { flex-direction: column; align-items: flex-start; gap: 6px; padding: 14px 18px; }
      .salary-range-val { text-align: left; }
      .recruiter-item { flex: 1 1 calc(50% - 5px); min-width: 0; }
    }
    @media (max-width: 480px) {
      .highlight-grid { grid-template-columns: 1fr; }
      .recruiter-item { flex: 1 1 100%; }
      .info-card-title { font-size: 0.95rem; }
      .info-card-content { font-size: 0.82rem; }
    }
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- HERO -->
<div class="course-hero">
  <div class="container course-hero-inner">
    <div>
      <h1 class="course-hero-title"><?= htmlspecialchars($course['course_name'] ?? '') ?></h1>
      <div class="course-hero-chips">
        <?php if(!empty($course['course_level'])): ?>
        <span><i class="ph ph-graduation-cap"></i> <?= htmlspecialchars((string)$course['course_level']) ?> Level</span>
        <?php endif; ?>
        <?php if(!empty($course['duration_years'])): ?>
        <span><i class="ph ph-clock"></i> <?= htmlspecialchars((string)$course['duration_years']) ?> Years</span>
        <?php endif; ?>
        <?php if(!empty($course['total_colleges_offering'])): ?>
        <span><i class="ph ph-buildings"></i> <?= number_format((int)$course['total_colleges_offering']) ?>+ Colleges</span>
        <?php endif; ?>
        <?php if(!empty($course['avg_salary_lpa'])): ?>
        <span><i class="ph ph-currency-inr"></i> ₹<?= htmlspecialchars((string)$course['avg_salary_lpa']) ?> LPA Avg</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="course-hero-actions">
      <a href="<?= $basePath ?>/colleges?course=<?= urlencode((string)($course['course_name'] ?? '')) ?>" class="course-btn-primary">
        Browse Colleges <i class="ph ph-arrow-right"></i>
      </a>
    </div>
  </div>
</div>

<!-- TABS -->
<div class="course-tabs-sticky shiksha-tabs-nav">
  <div class="container">
    <ul>
      <?php foreach ($tabs as $k => $label): ?>
      <li>
        <a href="<?= courseUrl($slug, $k) ?>" class="<?= $tab === $k ? 'active' : '' ?>">
          <i class="ph <?= $tabIcons[$k] ?? 'ph-circle' ?>"></i> <?= htmlspecialchars($label) ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<!-- CONTENT -->
<div class="container tab-content">

  <?php if ($tab === 'basic'): ?>

    <div class="highlight-grid">
      <div class="highlight-box">
        <i class="ph ph-graduation-cap"></i>
        <span class="hl-val"><?= htmlspecialchars((string)($course['course_level'] ?? 'N/A')) ?></span>
        <span class="hl-label">Course Level</span>
      </div>
      <div class="highlight-box">
        <i class="ph ph-clock"></i>
        <span class="hl-val"><?= htmlspecialchars((string)($course['duration_years'] ?? 'N/A')) ?> Year<?= ((int)($course['duration_years'] ?? 0)) > 1 ? 's' : '' ?></span>
        <span class="hl-label">Duration</span>
      </div>
      <div class="highlight-box">
        <i class="ph ph-currency-inr"></i>
        <span class="hl-val">₹<?= htmlspecialchars((string)($course['avg_salary_lpa'] ?? 'N/A')) ?> LPA</span>
        <span class="hl-label">Avg Salary</span>
      </div>
      <div class="highlight-box">
        <i class="ph ph-buildings"></i>
        <span class="hl-val"><?= number_format((int)($course['total_colleges_offering'] ?? 0)) ?>+</span>
        <span class="hl-label">Colleges Offering</span>
      </div>
    </div>

    <div class="info-card">
      <h2 class="info-card-title"><i class="ph ph-info"></i> About <?= htmlspecialchars((string)($course['course_name'] ?? '')) ?></h2>
      <div class="info-card-content">
        <?= nl2br(htmlspecialchars((string)($course['description'] ?? 'Details not available.'))) ?>
      </div>
    </div>

    <div class="info-card">
      <h2 class="info-card-title"><i class="ph ph-check-circle"></i> Eligibility Criteria</h2>
      <div class="info-card-content">
        <?= nl2br(htmlspecialchars((string)($course['eligibility'] ?? 'Details not available.'))) ?>
      </div>
    </div>

    <?php if (!empty($recruiters)): ?>
    <div class="info-card">
      <h2 class="info-card-title"><i class="ph ph-buildings"></i> Top Recruiters</h2>
      <div class="recruiter-grid">
        <?php foreach($recruiters as $tr): ?>
          <?php
            $rName = is_array($tr) ? ($tr['name'] ?? '') : (string)$tr;
            $rLogo = is_array($tr) ? ($tr['logo'] ?? '') : '';
          ?>
          <div class="recruiter-item">
            <div class="recruiter-logo">
              <?php if (!empty($rLogo) && file_exists(__DIR__ . '/' . $rLogo)): ?>
                <img src="<?= $basePath . '/' . htmlspecialchars($rLogo) ?>" alt="<?= htmlspecialchars($rName) ?>">
              <?php else: ?>
                <?= recruiterInitials($rName) ?>
              <?php endif; ?>
            </div>
            <span class="recruiter-name"><?= htmlspecialchars($rName) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  <?php elseif ($tab === 'scope'): ?>

    <div class="info-card">
      <h2 class="info-card-title"><i class="ph ph-book-open"></i> About <?= htmlspecialchars((string)($course['course_name'] ?? '')) ?></h2>
      <div class="info-card-content">
        <?= nl2br(htmlspecialchars((string)($course['description'] ?? 'Details not available.'))) ?>
      </div>
    </div>

    <div class="info-card">
      <h2 class="info-card-title"><i class="ph ph-rocket"></i> Career Scope & Future</h2>
      <div class="info-card-content">
        <?= nl2br(htmlspecialchars((string)($course['career_scope'] ?? 'Details not available.'))) ?>
      </div>
    </div>

    <?php if (!empty($careers)): ?>
    <div class="info-card">
      <h2 class="info-card-title"><i class="ph ph-chart-line-up"></i> Growth Outlook</h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
        <?php
          $high = 0; $medium = 0; $low = 0;
          foreach($careers as $c) {
            $g = $c['growth_outlook'] ?? 'medium';
            if ($g === 'high') $high++;
            elseif ($g === 'low') $low++;
            else $medium++;
          }
        ?>
        <div style="background:rgba(16,185,129,0.08);border-radius:14px;padding:20px;text-align:center;">
          <div style="font-size:2rem;font-weight:800;color:#059669;"><?= $high ?></div>
          <div style="font-size:0.85rem;color:#059669;font-weight:700;text-transform:uppercase;">High Growth</div>
        </div>
        <div style="background:rgba(245,158,11,0.08);border-radius:14px;padding:20px;text-align:center;">
          <div style="font-size:2rem;font-weight:800;color:#D97706;"><?= $medium ?></div>
          <div style="font-size:0.85rem;color:#D97706;font-weight:700;text-transform:uppercase;">Medium Growth</div>
        </div>
        <div style="background:rgba(239,68,68,0.08);border-radius:14px;padding:20px;text-align:center;">
          <div style="font-size:2rem;font-weight:800;color:#DC2626;"><?= $low ?></div>
          <div style="font-size:0.85rem;color:#DC2626;font-weight:700;text-transform:uppercase;">Low Growth</div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  <?php elseif ($tab === 'salary'): ?>

    <div class="salary-overview">
      <div class="salary-overview-header">
        <h3><i class="ph ph-chart-line-up"></i> Salary Overview — <?= htmlspecialchars((string)($course['course_name'] ?? '')) ?></h3>
      </div>
      <?php if (!empty($course['salary_range_min']) && !empty($course['salary_range_max'])): ?>
        <?php
          $min = (float)$course['salary_range_min'];
          $max = (float)$course['salary_range_max'];
          $avg = (float)($course['avg_salary_lpa'] ?? 0);
          $maxRef = max($max, 1);
        ?>
        <div class="salary-range-row">
          <span class="salary-range-label">Minimum</span>
          <div class="salary-range-bar"><div class="salary-range-fill" style="width:<?= round($min/$maxRef*100) ?>%"></div></div>
          <span class="salary-range-val">₹<?= number_format($min, 1) ?> LPA</span>
        </div>
        <div class="salary-range-row">
          <span class="salary-range-label">Average</span>
          <div class="salary-range-bar"><div class="salary-range-fill" style="width:<?= round($avg/$maxRef*100) ?>%"></div></div>
          <span class="salary-range-val">₹<?= number_format($avg, 1) ?> LPA</span>
        </div>
        <div class="salary-range-row">
          <span class="salary-range-label">Maximum</span>
          <div class="salary-range-bar"><div class="salary-range-fill" style="width:100%"></div></div>
          <span class="salary-range-val">₹<?= number_format($max, 1) ?> LPA</span>
        </div>
      <?php else: ?>
        <div class="salary-range-row">
          <span class="salary-range-label">Average Salary</span>
          <div class="salary-range-bar"><div class="salary-range-fill" style="width:50%"></div></div>
          <span class="salary-range-val">₹<?= htmlspecialchars((string)($course['avg_salary_lpa'] ?? 'N/A')) ?> LPA</span>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($recruiters)): ?>
    <div class="info-card">
      <h2 class="info-card-title"><i class="ph ph-buildings"></i> Top Recruiters</h2>
      <div class="recruiter-grid">
        <?php foreach($recruiters as $tr): ?>
          <?php
            $rName = is_array($tr) ? ($tr['name'] ?? '') : (string)$tr;
            $rLogo = is_array($tr) ? ($tr['logo'] ?? '') : '';
          ?>
          <div class="recruiter-item">
            <div class="recruiter-logo">
              <?php if (!empty($rLogo) && file_exists(__DIR__ . '/' . $rLogo)): ?>
                <img src="<?= $basePath . '/' . htmlspecialchars($rLogo) ?>" alt="<?= htmlspecialchars($rName) ?>">
              <?php else: ?>
                <?= recruiterInitials($rName) ?>
              <?php endif; ?>
            </div>
            <span class="recruiter-name"><?= htmlspecialchars($rName) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($careers)): ?>
    <div class="section-header" style="margin-top:32px;">
      <h2><i class="ph ph-money" style="color:var(--cp-blue)"></i> Salary by Role</h2>
      <p>Expected salary range for different career roles after <?= htmlspecialchars((string)($course['course_name'] ?? '')) ?>.</p>
    </div>
    <div class="career-grid">
      <?php foreach($careers as $cr): ?>
      <div class="career-card">
        <div class="career-card-top">
          <div class="career-role">
            <i class="ph ph-briefcase"></i>
            <?= htmlspecialchars((string)$cr['job_role']) ?>
          </div>
          <span class="career-growth <?= htmlspecialchars((string)($cr['growth_outlook'] ?? 'medium')) ?>">
            <i class="ph ph-trend-up"></i> <?= ucfirst(htmlspecialchars((string)($cr['growth_outlook'] ?? 'medium'))) ?> Growth
          </span>
        </div>
        <div class="career-salary-box">
          <div class="career-salary-item">
            <div class="career-salary-label">Fresher</div>
            <div class="career-salary-val">₹<?= htmlspecialchars((string)$cr['fresher_salary_lpa']) ?>L</div>
          </div>
          <div class="career-salary-item">
            <div class="career-salary-label">Experienced</div>
            <div class="career-salary-val">₹<?= htmlspecialchars((string)$cr['experienced_salary_lpa']) ?>L</div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  <?php elseif ($tab === 'specializations'): ?>

    <div class="section-header">
      <h2>Popular Specializations</h2>
      <p>Explore the various branches and specialized fields within <?= htmlspecialchars((string)($course['course_name'] ?? '')) ?>.</p>
    </div>

    <?php if(empty($specs)): ?>
      <div class="empty-state">
        <i class="ph ph-empty"></i>
        <p>No specializations listed for this course yet.</p>
      </div>
    <?php else: ?>
      <div class="specs-grid">
        <?php foreach($specs as $s): ?>
        <div class="spec-card">
          <?php if (!empty($s['is_popular'])): ?>
            <span class="spec-popular-badge">Popular</span>
          <?php endif; ?>
          <div class="spec-card-icon"><i class="ph ph-git-branch"></i></div>
          <h4><?= htmlspecialchars((string)$s['specialization_name']) ?></h4>
          <p><?= htmlspecialchars((string)$s['description']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php elseif ($tab === 'careers'): ?>

    <div class="section-header">
      <h2>Career Paths & Opportunities</h2>
      <p>Discover the top roles, salary expectations, and skills needed after <?= htmlspecialchars((string)($course['course_name'] ?? '')) ?>.</p>
    </div>

    <?php if(empty($careers)): ?>
      <div class="empty-state">
        <i class="ph ph-empty"></i>
        <p>No career data available right now.</p>
      </div>
    <?php else: ?>
      <div class="career-grid">
        <?php foreach($careers as $cr): ?>
        <div class="career-card">
          <div class="career-card-top">
            <div class="career-role">
              <i class="ph ph-briefcase"></i>
              <?= htmlspecialchars((string)$cr['job_role']) ?>
            </div>
            <span class="career-growth <?= htmlspecialchars((string)($cr['growth_outlook'] ?? 'medium')) ?>">
              <i class="ph ph-trend-up"></i> <?= ucfirst(htmlspecialchars((string)($cr['growth_outlook'] ?? 'medium'))) ?> Growth
            </span>
          </div>
          <div class="career-salary-box">
            <div class="career-salary-item">
              <div class="career-salary-label">Fresher</div>
              <div class="career-salary-val">₹<?= htmlspecialchars((string)$cr['fresher_salary_lpa']) ?>L</div>
            </div>
            <div class="career-salary-item">
              <div class="career-salary-label">Experienced</div>
              <div class="career-salary-val">₹<?= htmlspecialchars((string)$cr['experienced_salary_lpa']) ?>L</div>
            </div>
          </div>
          <div class="career-card-body">
            <?php
              $comps = json_decode($cr['top_companies'] ?? '[]', true);
              if (!is_array($comps)) $comps = [];
            ?>
            <?php if (!empty($comps)): ?>
            <div class="career-companies-label"><i class="ph ph-buildings"></i> Top Hiring Companies</div>
            <div class="career-tags">
              <?php foreach($comps as $comp): ?>
                <span class="career-tag"><?= htmlspecialchars((string)$comp) ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php
              $skills = json_decode($cr['skills_required'] ?? '[]', true);
              if (!is_array($skills)) $skills = [];
            ?>
            <?php if (!empty($skills)): ?>
            <div class="career-skills-label"><i class="ph ph-lightning"></i> Skills Required</div>
            <div class="career-skills">
              <?php foreach($skills as $skill): ?>
                <span class="career-skill"><?= htmlspecialchars((string)$skill) ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
