<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/university_helpers.php';
require_once __DIR__ . '/includes/college_helpers.php';

$slug = $_GET['slug'] ?? '';
$tabParam = $_GET['tab'] ?? '';
if (!$slug && preg_match('#/university/([^/]+)(?:/([^/]+))?#', $_SERVER['REQUEST_URI'] ?? '', $m)) {
    $slug = urldecode($m[1]);
    $tabParam = $m[2] ?? '';
}

$tabs = universityTabs();
$tab = isset($tabs[$tabParam]) ? $tabParam : 'info';
$university = $slug ? loadUniversityBySlug($pdo, $slug) : null;

if (!$university) {
    http_response_code(404);
    $navBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>University Not Found</title>
<link rel="stylesheet" href="<?= $navBase ?>/assets/css/style.css?v=<?= time() ?>">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="bg-light">
<?php include __DIR__ . '/includes/navbar.php'; ?>
<div class="container" style="padding:80px 20px;text-align:center">
  <i class="ph ph-graduation-cap" style="font-size:4rem;color:rgba(15,23,42,.1);display:block;margin-bottom:16px"></i>
  <h1 style="font-size:1.5rem;color:#64748b;margin-bottom:8px">University Not Found</h1>
  <p style="color:#94a3b8;margin-bottom:20px">The university you're looking for doesn't exist or is no longer active.</p>
  <a href="<?= $navBase ?>/universities" class="college-btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:#0B2447;color:#fff;border-radius:10px;text-decoration:none;font-weight:600">Browse Universities</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>
<?php
    exit;
}

$uid = $university['id'];
$universityName = htmlspecialchars($university['name']);
$year = $university['established_year'] ?: $university['founded_year'] ?? null;
$location = trim(($university['city_name'] ?? '') . ($university['state_name'] ? ', ' . $university['state_name'] : ''));
$typeLabel = universityTypeLabel($university['university_type'] ?? '', $university['ownership'] ?? '');
$overallRating = (float)($university['overall_rating_avg'] ?? 0);
$reviewCount = (int)($university['total_reviews'] ?? 0);

$tabIcons = [
    'info'=>'ph-info','courses'=>'ph-book-open','placements'=>'ph-briefcase',
    'cutoffs'=>'ph-scissors','admissions'=>'ph-graduation-cap','infrastructure'=>'ph-buildings',
    'faculty'=>'ph-users-three','scholarships'=>'ph-medal','gallery'=>'ph-images','faqs'=>'ph-question',
];

$tryQuery = function($sql, $params = []) use ($pdo) {
    try { $s = $pdo->prepare($sql); $s->execute($params); return $s->fetchAll(PDO::FETCH_ASSOC); }
    catch(Exception $e) { return []; }
};

$courses = $tryQuery("SELECT * FROM university_courses WHERE university_id=? ORDER BY course_level, course_name", [$uid]);
$placements = $tryQuery("SELECT * FROM university_placements WHERE university_id=? ORDER BY placement_year DESC", [$uid]);
$cutoffs = $tryQuery("SELECT cu.*, e.exam_name, uc2.course_name FROM university_cutoffs cu LEFT JOIN exams e ON e.id=cu.exam_id LEFT JOIN university_courses uc2 ON uc2.id=cu.course_id WHERE cu.university_id=? ORDER BY cu.year DESC", [$uid]);
$faculty = $tryQuery("SELECT * FROM university_faculty WHERE university_id=? ORDER BY faculty_name", [$uid]);
$faqs = $tryQuery("SELECT * FROM university_faqs WHERE university_id=? AND is_active=1 ORDER BY sort_order", [$uid]);
$scholarships = $tryQuery("SELECT * FROM university_scholarships WHERE university_id=?", [$uid]);
$gallery = $tryQuery("SELECT * FROM university_media WHERE university_id=? ORDER BY sort_order", [$uid]);

$sessions = session_status() === PHP_SESSION_NONE;
if ($sessions) session_start();
$loginUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '');
$isLoggedIn = !empty($_SESSION['user_id']);
$navBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

$pageTitle = $university['meta_title'] ?? ($universityName . ': Courses, Fees, Placements ' . date('Y'));
$metaDesc = $university['meta_description'] ?? ('Explore ' . $universityName . ' — courses, fees, placements, cutoffs and admission details.');

$best = null;
if ($placements) {
    $best = array_reduce($placements, fn($c,$p) => ($p['avg_package_lpa'] > ($c['avg_package_lpa'] ?? 0)) ? $p : $c, null);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
<link rel="stylesheet" href="<?= $navBase ?>/assets/css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= $navBase ?>/assets/css/college-pages.css?v=<?= time() ?>">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  .college-tabs-wrapper .tab-arrow { position:absolute;top:0;bottom:0;width:64px;border:none;background:none;padding:0;margin:0;outline:none;cursor:pointer;display:flex;align-items:center;z-index:10;transition:all .25s ease }
  .tab-arrow-left { left:0;background:linear-gradient(90deg,#fff 50%,rgba(255,255,255,0));justify-content:flex-start;padding-left:8px }
  .tab-arrow-right { right:0;background:linear-gradient(270deg,#fff 50%,rgba(255,255,255,0));justify-content:flex-end;padding-right:8px }
  .tab-arrow i { width:32px;height:32px;border-radius:50%;background:#fff;border:1px solid rgba(15,23,42,.12);box-shadow:0 4px 10px rgba(11,36,71,.12);display:flex;align-items:center;justify-content:center;font-size:1rem;color:#19376D;transition:all .2s ease }
  .tab-arrow:hover i { color:#fff;background:#19376D;border-color:#19376D;transform:scale(1.1) }
  .tab-arrow.hidden { opacity:0;pointer-events:none }
  @media(max-width:768px) {
    .college-tabs-wrapper .tab-arrow { width:40px }
    .college-tabs-wrapper .tab-arrow i { width:26px;height:26px;font-size:.85rem }
    .college-tabs-wrapper .shiksha-tabs { padding:8px 36px }
    .college-tabs-wrapper.has-scroll::after { display:none }
  }
  @media(max-width:768px) {
    .college-hero{padding:80px 0 0;min-height:auto}
    .college-hero-inner{padding:16px 0}
    .college-hero-card{display:flex;flex-direction:column;align-items:stretch}
    .college-hero-main{flex-direction:column;gap:12px}
    .college-hero-actions{justify-content:flex-start;border-top:none;padding-top:0;margin-top:12px}
    .college-hero-actions .college-btn-primary,.college-hero-actions .college-btn-outline{width:100%;justify-content:center}
    .college-hero-title{font-size:1.35rem}
    .college-hero-sub{font-size:.82rem}
    .college-hero-logo{width:56px;height:56px;border-radius:12px}
    .college-hero-chips{gap:6px}
    .college-hero-chips span{padding:5px 10px;font-size:.72rem}
  }
  @media(max-width:480px) {
    .college-hero-inner.container{padding-left:16px;padding-right:16px}
    .college-hero-title{font-size:1.15rem;line-height:1.3}
    .college-hero-sub{font-size:.75rem;line-height:1.4}
    .college-hero-logo{width:48px;height:48px;border-radius:10px}
    .college-hero-chips span{padding:4px 8px;font-size:.65rem}
    .college-hero-actions .college-btn-primary,.college-hero-actions .college-btn-outline{padding:9px 14px;font-size:.8rem}
  }
  /* Review modal responsive */
  @media(max-width:480px) {
    #reviewModal>div{border-radius:16px;margin:0 8px}
    #reviewModal>div>div:first-child{padding:16px 18px 0}
    #reviewModal>div>div:first-child h3{font-size:1.05rem;gap:6px}
    #reviewModal>div>div:last-child{padding:16px 18px 24px}
    #reviewModal .star-rating-group i{font-size:1.5rem!important}
    #reviewModal [style*="grid-template-columns:1fr 1fr"]{grid-template-columns:1fr!important;gap:10px!important}
  }
</style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- HERO -->
<div class="college-hero" style="background-image:url('<?= cImg($university['cover_image_url'] ?? '') ?>')">
  <div class="college-hero-overlay"></div>
  <div class="container college-hero-inner">
    <div class="shiksha-breadcrumb college-breadcrumb">
      <a href="<?= $navBase ?>/">Home</a>
      <i class="ph ph-caret-right"></i>
      <a href="<?= $navBase ?>/universities">Universities</a>
      <i class="ph ph-caret-right"></i>
      <span><?= $universityName ?></span>
    </div>
    <div class="college-hero-card">
      <div class="college-hero-main">
        <?php if (!empty($university['logo_url'])): ?>
        <img src="<?= cImg($university['logo_url']) ?>" class="college-hero-logo" alt="<?= $universityName ?>">
        <?php endif; ?>
        <div>
          <h1 class="college-hero-title"><?= $universityName ?></h1>
          <p class="college-hero-sub"><?= $universityName ?>: Fees, Admission <?= date('Y') ?>, Courses, Placements<?= !empty($university['ranking_nirf']) ? ', Ranking' : '' ?></p>
          <div class="college-hero-chips">
            <?php if ($location): ?><span><i class="ph ph-map-pin"></i> <?= htmlspecialchars($location) ?></span><?php endif; ?>
            <?php if ($overallRating > 0): ?>
            <span><i class="ph ph-star-fill" style="color:#19376D"></i> <?= number_format($overallRating,1) ?> / 5</span>
            <span><?= $reviewCount ?> Reviews</span>
            <?php endif; ?>
            <span><i class="ph ph-buildings"></i> <?= htmlspecialchars($typeLabel) ?></span>
            <?php if ($year): ?><span><i class="ph ph-calendar"></i> Estd <?= htmlspecialchars((string)$year) ?></span><?php endif; ?>
            <?php if (!empty($university['is_verified'])): ?><span style="background:rgba(22,163,74,.35);border-color:rgba(22,163,74,.5)"><i class="ph-fill ph-seal-check"></i> Verified</span><?php endif; ?>
            <?php if (!empty($university['ranking_nirf'])): ?><span><i class="ph ph-trophy"></i> NIRF #<?= (int)$university['ranking_nirf'] ?></span><?php endif; ?>
            <?php if (!empty($university['naac_grade'])): ?><span>NAAC <?= htmlspecialchars($university['naac_grade']) ?></span><?php endif; ?>
            <?php if (!empty($university['ugc_approved'])): ?><span>UGC ✓</span><?php endif; ?>
            <?php if (!empty($university['aicte_approved'])): ?><span>AICTE ✓</span><?php endif; ?>
          </div>
        </div>
      </div>
      <div class="college-hero-actions">
        <?php if (!empty($university['website_url'])): ?>
        <a href="<?= htmlspecialchars($university['website_url']) ?>" target="_blank" class="college-btn-primary"><i class="ph ph-globe"></i> Visit Website</a>
        <?php endif; ?>
        <a href="<?= $navBase ?>/counselling?university=<?= urlencode($slug) ?>" class="college-btn-primary"><i class="ph ph-headset"></i> Get Counselling</a>
      </div>
    </div>
  </div>
</div>

<!-- TABS NAV -->
<div class="shiksha-tabs-nav college-tabs-sticky">
  <div class="container">
    <div class="college-tabs-wrapper">
      <button class="tab-arrow tab-arrow-left" onclick="scrollTabs(-1)" aria-label="Scroll left"><i class="ph ph-caret-left"></i></button>
      <div class="shiksha-tabs college-detail-tabs" id="uniTabs">
        <?php foreach ($tabs as $key => $label): ?>
        <a href="<?= universityUrl($slug, $key) ?>" class="<?= $tab === $key ? 'active' : '' ?>">
          <?php if (isset($tabIcons[$key])): ?><i class="ph <?= $tabIcons[$key] ?>"></i> <?php endif; ?>
          <?= htmlspecialchars($label) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <button class="tab-arrow tab-arrow-right" onclick="scrollTabs(1)" aria-label="Scroll right"><i class="ph ph-caret-right"></i></button>
    </div>
  </div>
</div>

<!-- MAIN CONTENT -->
<div class="container shiksha-main-wrapper college-detail-wrap">
  <div class="shiksha-layout">
    <main class="shiksha-content">
      <div class="college-tab-content">

        <!-- ── INFO ────────────────────────────────────────────── -->
        <?php if ($tab === 'info'): ?>
          <section class="college-section">
            <h2>About <?= $universityName ?></h2>
            <?php if (!empty($university['about_text'])): ?>
            <div class="college-prose"><?= nl2br(htmlspecialchars($university['about_text'])) ?></div>
            <?php else: ?>
            <div class="college-prose" style="color:rgba(15,23,42,.4)">No description available.</div>
            <?php endif; ?>
          </section>

          <?php if ($university['total_students'] || $university['total_faculty'] || $university['campus_area_acres']): ?>
          <section class="college-section">
            <h2>Quick Facts</h2>
            <div class="overview-stat-grid">
              <?php if ($university['total_students']): ?><div class="overview-stat"><div class="overview-stat-val"><?= number_format((int)$university['total_students']) ?></div><div class="overview-stat-lbl">Total Students</div></div><?php endif; ?>
              <?php if ($university['total_faculty']): ?><div class="overview-stat"><div class="overview-stat-val"><?= number_format((int)$university['total_faculty']) ?></div><div class="overview-stat-lbl">Faculty</div></div><?php endif; ?>
              <?php if ($university['campus_area_acres']): ?><div class="overview-stat"><div class="overview-stat-val"><?= number_format((float)$university['campus_area_acres'],1) ?></div><div class="overview-stat-lbl">Campus (Acres)</div></div><?php endif; ?>
              <?php if ($year): ?><div class="overview-stat"><div class="overview-stat-val"><?= htmlspecialchars((string)$year) ?></div><div class="overview-stat-lbl">Established</div></div><?php endif; ?>
              <?php if (!empty($university['ranking_nirf'])): ?><div class="overview-stat"><div class="overview-stat-val">#<?= (int)$university['ranking_nirf'] ?></div><div class="overview-stat-lbl">NIRF Ranking</div></div><?php endif; ?>
              <?php if (!empty($university['naac_grade'])): ?><div class="overview-stat"><div class="overview-stat-val"><?= htmlspecialchars($university['naac_grade']) ?></div><div class="overview-stat-lbl">NAAC Grade</div></div><?php endif; ?>
            </div>
          </section>
          <?php endif; ?>

          <?php if ($university['email'] || $university['phone'] || $university['address']): ?>
          <section class="college-section">
            <h2>Contact Information</h2>
            <div class="college-contact-grid">
              <?php if ($university['address']): ?><p><i class="ph ph-map-pin"></i> <?= htmlspecialchars($university['address']) ?></p><?php endif; ?>
              <?php if ($university['phone']): ?><p><i class="ph ph-phone"></i> <?= htmlspecialchars($university['phone']) ?></p><?php endif; ?>
              <?php if ($university['email']): ?><p><i class="ph ph-envelope"></i> <a href="mailto:<?= htmlspecialchars($university['email']) ?>"><?= htmlspecialchars($university['email']) ?></a></p><?php endif; ?>
              <?php if ($university['website_url']): ?><p><i class="ph ph-globe"></i> <a href="<?= htmlspecialchars($university['website_url']) ?>" target="_blank"><?= htmlspecialchars($university['website_url']) ?></a></p><?php endif; ?>
            </div>
          </section>
          <?php endif; ?>

        <!-- ── COURSES ─────────────────────────────────────────── -->
        <?php elseif ($tab === 'courses'): ?>
          <section class="college-section">
            <h2>Courses & Fees <span class="college-count">(<?= count($courses) ?>)</span></h2>
            <?php if (empty($courses)): ?>
            <div class="tab-empty-state"><i class="ph ph-book-open"></i><p>No courses listed yet.</p></div>
            <?php else: ?>
            <div class="college-table-wrap">
              <table class="college-data-table courses-table">
                <thead><tr><th>Course Name</th><th>Level</th><th>Duration</th><th>Seats</th><th>Annual Fee</th><th>EMI</th></tr></thead>
                <tbody>
                <?php foreach ($courses as $co):
                  $lvl = strtolower($co['course_level'] ?? '');
                  $levelMap = ['ug'=>'level-ug','pg'=>'level-pg','phd'=>'level-phd','diploma'=>'level-diploma','certificate'=>'level-certificate'];
                ?>
                <tr>
                  <td data-label="Course">
                    <strong><?= htmlspecialchars($co['course_name'] ?? '—') ?></strong>
                    <?php if (!empty($co['specializations'])): ?>
                      <br><small style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px"><?php
                        $specs = is_string($co['specializations']) ? json_decode($co['specializations'], true) : $co['specializations'];
                        if (is_array($specs)) foreach ($specs as $sp) echo '<span style="display:inline-block;padding:2px 8px;border-radius:10px;background:rgba(37,99,235,0.08);color:#2563eb;font-size:11px;white-space:nowrap">' . htmlspecialchars($sp) . '</span>';
                      ?></small>
                    <?php endif; ?>
                    <?php if (!empty($co['eligibility_criteria'])): ?><br><small style="color:rgba(15,23,42,0.4)">Eligibility: <?= htmlspecialchars($co['eligibility_criteria']) ?></small><?php endif; ?>
                  </td>
                  <td data-label="Level"><span class="course-level-badge <?= $levelMap[$lvl] ?? 'level-ug' ?>"><?= htmlspecialchars($co['course_level'] ?? '—') ?></span></td>
                  <td data-label="Duration"><?= $co['duration_years'] ? htmlspecialchars((string)$co['duration_years']) . ' yrs' : '—' ?></td>
                  <td data-label="Seats"><?= htmlspecialchars((string)($co['seats_available'] ?? '—')) ?></td>
                  <td data-label="Fee"><strong style="color:#0B2447"><?= formatFee(isset($co['annual_fee']) ? (float)$co['annual_fee'] : null) ?></strong></td>
                  <td data-label="EMI"><?= !empty($co['emi_available']) ? '<span style="color:#0B2447;font-weight:700">✓ EMI</span>' : '<span style="color:rgba(15,23,42,0.4)">—</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── PLACEMENTS ──────────────────────────────────────── -->
        <?php elseif ($tab === 'placements'): ?>
          <section class="college-section">
            <h2>Placement Statistics</h2>
            <?php if (empty($placements)): ?>
            <div class="tab-empty-state"><i class="ph ph-briefcase"></i><p>Placement data not available.</p></div>
            <?php else: ?>
            <?php if ($best): ?>
            <div class="placement-highlight">
              <div class="ph-stat"><strong><?= formatLpa((float)($best['avg_package_lpa'] ?? 0)) ?></strong><span>Avg Package</span></div>
              <div class="ph-stat"><strong><?= formatLpa((float)($best['highest_package_lpa'] ?? 0)) ?></strong><span>Highest Package</span></div>
              <div class="ph-stat"><strong><?= $best['placement_percentage'] ? number_format((float)$best['placement_percentage'],1).'%' : '—' ?></strong><span>Placement Rate</span></div>
              <div class="ph-stat"><strong><?= htmlspecialchars((string)($best['students_placed'] ?? '—')) ?></strong><span>Students Placed</span></div>
            </div>
            <?php endif; ?>
            <div class="college-table-wrap">
              <table class="college-data-table placements-table">
                <thead><tr><th>Year</th><th>Avg Package</th><th>Highest</th><th>Median</th><th>Placed %</th><th>Students</th></tr></thead>
                <tbody>
                <?php foreach ($placements as $pl): ?>
                <tr>
                  <td data-label="Year"><strong><?= htmlspecialchars((string)($pl['placement_year'] ?? '—')) ?></strong></td>
                  <td data-label="Avg Package"><?= formatLpa(isset($pl['avg_package_lpa']) ? (float)$pl['avg_package_lpa'] : null) ?></td>
                  <td data-label="Highest"><?= formatLpa(isset($pl['highest_package_lpa']) ? (float)$pl['highest_package_lpa'] : null) ?></td>
                  <td data-label="Median"><?= formatLpa(isset($pl['median_package_lpa']) ? (float)$pl['median_package_lpa'] : null) ?></td>
                  <td data-label="Placed %"><?= !empty($pl['placement_percentage']) ? number_format((float)$pl['placement_percentage'],1).'%' : '—' ?></td>
                  <td data-label="Students"><?= htmlspecialchars((string)($pl['students_placed'] ?? '—')) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php foreach ($placements as $pl): if (empty($pl['top_recruiters'])) continue;
              $rec = is_string($pl['top_recruiters']) ? json_decode($pl['top_recruiters'], true) : $pl['top_recruiters'];
              if (!is_array($rec)) $rec = array_filter(explode(',', (string)$pl['top_recruiters']));
            ?>
            <div style="margin-top:20px">
              <h3>Top Recruiters (<?= htmlspecialchars((string)($pl['placement_year'] ?? '')) ?>)</h3>
              <div class="college-tag-row"><?php foreach ($rec as $r): ?><span class="college-tag"><?= htmlspecialchars((string)$r) ?></span><?php endforeach; ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </section>

        <!-- ── CUTOFFS ─────────────────────────────────────────── -->
        <?php elseif ($tab === 'cutoffs'): ?>
          <section class="college-section">
            <h2>Cut-Off Data</h2>
            <?php if (empty($cutoffs)): ?>
            <div class="tab-empty-state"><i class="ph ph-scissors"></i><p>Cutoff data not available.</p></div>
            <?php else: ?>
            <div class="college-table-wrap">
              <table class="college-data-table cutoffs-table">
                <thead><tr><th>Exam</th><th>Course</th><th>Year</th><th>Category</th><th>Round</th><th>Opening</th><th>Closing</th></tr></thead>
                <tbody>
                <?php foreach ($cutoffs as $cu): ?>
                <tr>
                  <td data-label="Exam"><strong><?= htmlspecialchars($cu['exam_name'] ?? '—') ?></strong></td>
                  <td data-label="Course"><?= htmlspecialchars($cu['course_name'] ?? '—') ?></td>
                  <td data-label="Year"><?= htmlspecialchars((string)($cu['year'] ?? '—')) ?></td>
                  <td data-label="Category"><span class="college-tag" style="font-size:.72rem"><?= htmlspecialchars($cu['category'] ?? '—') ?></span></td>
                  <td data-label="Round"><?= htmlspecialchars((string)($cu['round_number'] ?? '—')) ?></td>
                  <td data-label="Opening"><?= htmlspecialchars((string)($cu['opening_rank'] ?? '—')) ?></td>
                  <td data-label="Closing"><strong><?= htmlspecialchars((string)($cu['closing_rank'] ?? '—')) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── ADMISSIONS ──────────────────────────────────────── -->
        <?php elseif ($tab === 'admissions'): ?>
          <section class="college-section">
            <h2>Admission Process</h2>
            <?php if (!empty($university['admission_process'])): ?>
            <div class="college-prose"><?= nl2br(htmlspecialchars($university['admission_process'])) ?></div>
            <?php else: ?>
            <div class="tab-empty-state"><i class="ph ph-graduation-cap"></i><p>Admission details not available.</p></div>
            <?php endif; ?>
            <div class="college-info-grid" style="margin-top:20px">
              <?php if ($university['admission_start_date']): ?><div><strong>Application Start</strong><p><?= date('d M Y', strtotime($university['admission_start_date'])) ?></p></div><?php endif; ?>
              <?php if ($university['admission_end_date']): ?><div><strong>Application End</strong><p><?= date('d M Y', strtotime($university['admission_end_date'])) ?></p></div><?php endif; ?>
              <?php if ($university['application_mode']): ?><div><strong>Application Mode</strong><p><?= htmlspecialchars(ucfirst($university['application_mode'])) ?></p></div><?php endif; ?>
              <?php if ($university['selection_criteria']): ?><div><strong>Selection Criteria</strong><p><?= htmlspecialchars($university['selection_criteria']) ?></p></div><?php endif; ?>
              <?php if ($university['management_quota_seats']): ?><div><strong>Management Quota</strong><p><?= (int)$university['management_quota_seats'] ?> seats</p></div><?php endif; ?>
              <?php if (!empty($university['merit_based'])): ?><div><strong>Merit Based</strong><p>Yes</p></div><?php endif; ?>
            </div>
          </section>

        <!-- ── INFRASTRUCTURE ──────────────────────────────────── -->
        <?php elseif ($tab === 'infrastructure'): ?>
          <section class="college-section">
            <h2>Campus Infrastructure</h2>
            <div class="college-facility-grid">
              <?php if ($university['library']): ?><div class="college-facility-item"><i class="ph ph-books"></i><div><strong>Library</strong><span><?= $university['library_books_count'] ? number_format((int)$university['library_books_count']) . ' books' : 'Available' ?></span></div></div><?php endif; ?>
              <?php if ($university['wifi']): ?><div class="college-facility-item"><i class="ph ph-wifi-high"></i><div><strong>Wi-Fi</strong><span><?= $university['wifi_speed_mbps'] ? $university['wifi_speed_mbps'] . ' Mbps' : 'Available' ?></span></div></div><?php endif; ?>
              <?php if ($university['auditorium']): ?><div class="college-facility-item"><i class="ph ph-megaphone"></i><div><strong>Auditorium</strong><span><?= $university['auditorium_capacity'] ? 'Capacity: ' . number_format((int)$university['auditorium_capacity']) : 'Available' ?></span></div></div><?php endif; ?>
              <?php if ($university['cafeteria']): ?><div class="college-facility-item"><i class="ph ph-coffee"></i><div><strong>Cafeteria</strong><span>Available</span></div></div><?php endif; ?>
              <?php if ($university['medical_facility']): ?><div class="college-facility-item"><i class="ph ph-first-aid"></i><div><strong>Medical Facility</strong><span>Available</span></div></div><?php endif; ?>
              <?php if ($university['transport']): ?><div class="college-facility-item"><i class="ph ph-bus"></i><div><strong>Transport</strong><span>Available</span></div></div><?php endif; ?>
              <?php if ($university['sports_facilities']): ?><div class="college-facility-item"><i class="ph ph-soccer-ball"></i><div><strong>Sports</strong><span>Available</span></div></div><?php endif; ?>
              <?php if ($university['labs']): ?><div class="college-facility-item"><i class="ph ph-flask"></i><div><strong>Labs</strong><span>Available</span></div></div><?php endif; ?>
              <?php if ($university['solar_power']): ?><div class="college-facility-item"><i class="ph ph-sun"></i><div><strong>Solar Power</strong><span>Available</span></div></div><?php endif; ?>
              <?php if ($university['ev_charging']): ?><div class="college-facility-item"><i class="ph ph-lightning"></i><div><strong>EV Charging</strong><span>Available</span></div></div><?php endif; ?>
            </div>
          </section>
          <?php if ($university['hostel_available']): ?>
          <section class="college-section">
            <h2>Hostel</h2>
            <div class="college-info-grid">
              <div><strong>Type</strong><p><?= htmlspecialchars(ucfirst($university['hostel_type'] ?? '—')) ?></p></div>
              <div><strong>Capacity</strong><p><?= $university['hostel_capacity'] ? number_format((int)$university['hostel_capacity']) . ' students' : '—' ?></p></div>
              <div><strong>Annual Fee</strong><p><?= formatFee(isset($university['hostel_fee_annual']) ? (float)$university['hostel_fee_annual'] : null) ?></p></div>
              <div><strong>Mess</strong><p><?= !empty($university['mess_available']) ? 'Available (' . ucfirst($university['mess_type'] ?? '') . ')' : 'Not Available' ?></p></div>
              <div><strong>AC Rooms</strong><p><?= !empty($university['ac_available']) ? 'Yes' : 'No' ?></p></div>
              <div><strong>Laundry</strong><p><?= !empty($university['laundry_available']) ? 'Available' : 'Not Available' ?></p></div>
            </div>
          </section>
          <?php endif; ?>

        <!-- ── FACULTY ─────────────────────────────────────────── -->
        <?php elseif ($tab === 'faculty'): ?>
          <section class="college-section">
            <h2>Faculty <span class="college-count">(<?= count($faculty) ?>)</span></h2>
            <?php if (empty($faculty)): ?>
            <div class="tab-empty-state"><i class="ph ph-users-three"></i><p>No faculty data available.</p></div>
            <?php else: ?>
            <div class="college-faculty-grid">
              <?php foreach ($faculty as $f): ?>
              <div class="college-faculty-card">
                <?php if (!empty($f['photo_url'])): ?>
                <img src="<?= cImg($f['photo_url']) ?>" alt="<?= htmlspecialchars($f['faculty_name']) ?>">
                <?php else: ?>
                <div class="cf-avatar"><i class="ph ph-user"></i></div>
                <?php endif; ?>
                <div>
                  <strong><?= htmlspecialchars($f['faculty_name']) ?></strong>
                  <span><?= htmlspecialchars($f['designation'] ?? '') ?><?= !empty($f['department']) ? ' — ' . htmlspecialchars($f['department']) : '' ?></span>
                  <?php if (!empty($f['qualification'])): ?><small><?= htmlspecialchars($f['qualification']) ?></small><?php endif; ?>
                  <?php if (!empty($f['specialization'])): ?><small>Specialization: <?= htmlspecialchars($f['specialization']) ?></small><?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── SCHOLARSHIPS ────────────────────────────────────── -->
        <?php elseif ($tab === 'scholarships'): ?>
          <section class="college-section">
            <h2>Scholarships</h2>
            <?php if (empty($scholarships)): ?>
            <div class="tab-empty-state"><i class="ph ph-medal"></i><p>No scholarship data available.</p></div>
            <?php else: ?>
            <div style="display:grid;gap:12px">
              <?php foreach ($scholarships as $sch): ?>
              <div style="background:#fff;border:1px solid var(--cp-border,#e2e8f0);border-radius:12px;padding:16px">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
                  <strong style="font-size:.92rem;color:#0B2447"><?= htmlspecialchars($sch['scholarship_name']) ?></strong>
                  <?php if ($sch['amount']): ?>
                  <span style="background:rgba(37,99,235,.08);color:#2563eb;padding:3px 10px;border-radius:10px;font-size:.75rem;font-weight:600">
                    <?= $sch['amount_type'] === 'full_tuition' ? 'Full Tuition' : ($sch['amount_type'] === 'percentage' ? $sch['amount'].'%' : formatFee((float)$sch['amount'])) ?>
                  </span>
                  <?php endif; ?>
                </div>
                <?php if ($sch['eligibility_criteria']): ?><p style="font-size:.82rem;color:rgba(15,23,42,.6);margin:8px 0 0"><?= htmlspecialchars($sch['eligibility_criteria']) ?></p><?php endif; ?>
                <?php if ($sch['renewable']): ?><span style="font-size:.72rem;color:#059669;font-weight:600">♻ Renewable</span><?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── GALLERY ─────────────────────────────────────────── -->
        <?php elseif ($tab === 'gallery'): ?>
          <section class="college-section">
            <h2>Gallery</h2>
            <?php if (empty($gallery)): ?>
            <div class="tab-empty-state"><i class="ph ph-images"></i><p>No media available.</p></div>
            <?php else: ?>
            <?php
            $images = array_filter($gallery, fn($g) => !empty($g['image_url']) && empty($g['video_url']) && empty($g['document_url']));
            $videos = array_filter($gallery, fn($g) => !empty($g['video_url']));
            $docs = array_filter($gallery, fn($g) => !empty($g['document_url']));
            ?>
            <?php if ($images): ?>
            <div class="college-gallery-grid">
              <?php foreach ($images as $img): ?>
              <a href="<?= cImg($img['image_url']) ?>" target="_blank" style="display:block;border-radius:10px;overflow:hidden;aspect-ratio:4/3">
                <img src="<?= cImg($img['image_url']) ?>" alt="<?= htmlspecialchars($img['caption'] ?? '') ?>" style="width:100%;height:100%;object-fit:cover" loading="lazy">
              </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($videos): ?>
            <h3 style="margin:20px 0 12px">Videos</h3>
            <div class="college-gallery-grid">
              <?php foreach ($videos as $vid): ?>
              <div class="gallery-video-wrap" style="border-radius:10px;overflow:hidden;aspect-ratio:16/9">
                <iframe src="<?= htmlspecialchars($vid['video_url']) ?>" style="width:100%;height:100%;border:none" allowfullscreen loading="lazy"></iframe>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($docs): ?>
            <h3 style="margin:20px 0 12px">Documents</h3>
            <div class="gallery-doc-list">
              <?php foreach ($docs as $doc): ?>
              <a href="<?= cImg($doc['document_url']) ?>" target="_blank" class="gallery-doc-card">
                <div class="gallery-doc-icon"><i class="ph ph-file-pdf"></i></div>
                <div class="gallery-doc-info"><div class="gallery-doc-name"><?= htmlspecialchars($doc['caption'] ?? ucfirst($doc['document_type'] ?? 'Document')) ?></div></div>
                <div class="gallery-doc-dl"><i class="ph ph-download-simple"></i></div>
              </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
          </section>

        <!-- ── FAQS ────────────────────────────────────────────── -->
        <?php elseif ($tab === 'faqs'): ?>
          <section class="college-section">
            <h2>Frequently Asked Questions</h2>
            <?php if (empty($faqs)): ?>
            <div class="tab-empty-state"><i class="ph ph-question"></i><p>No FAQs available.</p></div>
            <?php else: ?>
            <div class="college-faq-list">
              <?php foreach ($faqs as $faq): ?>
              <details class="college-faq-item">
                <summary><i class="ph ph-caret-right" style="transition:transform .2s"></i> <?= htmlspecialchars($faq['question_text']) ?></summary>
                <p><?= nl2br(htmlspecialchars($faq['answer_text'])) ?></p>
              </details>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <?php endif; ?>
      </div>
    </main>

    <!-- SIDEBAR -->
    <aside class="shiksha-sidebar">
      <div class="shiksha-widget">
        <h4 class="shiksha-widget-title"><i class="ph ph-info"></i> Quick Info</h4>
        <ul class="shiksha-widget-list">
          <li><span>Type</span><strong><?= htmlspecialchars($typeLabel) ?></strong></li>
          <?php if ($year): ?><li><span>Established</span><strong><?= htmlspecialchars((string)$year) ?></strong></li><?php endif; ?>
          <?php if ($university['ranking_nirf']): ?><li><span>NIRF Rank</span><strong>#<?= (int)$university['ranking_nirf'] ?></strong></li><?php endif; ?>
          <?php if ($university['naac_grade']): ?><li><span>NAAC</span><strong><?= htmlspecialchars($university['naac_grade']) ?></strong></li><?php endif; ?>
          <?php if ($location): ?><li><span>Location</span><strong><?= htmlspecialchars($location) ?></strong></li><?php endif; ?>
          <?php if ($university['total_students']): ?><li><span>Students</span><strong><?= number_format((int)$university['total_students']) ?></strong></li><?php endif; ?>
        </ul>
      </div>

      <?php if (!empty($courses)): ?>
      <div class="shiksha-widget">
        <h4 class="shiksha-widget-title"><i class="ph ph-book-bookmark"></i> Popular Courses</h4>
        <ul class="shiksha-widget-list">
          <?php foreach (array_slice($courses, 0, 6) as $co): ?>
          <li><a href="<?= universityUrl($slug, 'courses') ?>"><?= htmlspecialchars($co['course_name'] ?? '') ?><span><?= formatFee(isset($co['annual_fee']) ? (float)$co['annual_fee'] : null) ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <div class="shiksha-widget" style="text-align:center;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;border-radius:16px">
        <h4 class="shiksha-widget-title" style="color:#fff"><i class="ph ph-headset"></i> Free Counselling</h4>
        <p style="font-size:.82rem;opacity:.8;margin:0 0 12px">Get expert guidance for <?= $universityName ?></p>
        <a href="<?= $navBase ?>/counselling?university=<?= urlencode($slug) ?>" class="college-btn-primary" style="width:100%;justify-content:center;text-decoration:none;display:inline-flex;align-items:center;gap:8px"><i class="ph ph-paper-plane-tilt"></i> Apply Now</a>
      </div>
    </aside>
  </div>
</div>

<script>
document.querySelectorAll('.college-detail-tabs a.active').forEach(el => el.scrollIntoView({behavior:'smooth',block:'nearest',inline:'center'}));
function scrollTabs(dir) {
  const tabs = document.getElementById('uniTabs');
  if (tabs) tabs.scrollBy({left:dir*200,behavior:'smooth'});
}
function updateTabArrows() {
  const tabs = document.getElementById('uniTabs');
  if (!tabs) return;
  const wrapper = tabs.closest('.college-tabs-wrapper');
  const left = document.querySelector('.tab-arrow-left');
  const right = document.querySelector('.tab-arrow-right');
  const canScroll = tabs.scrollWidth > tabs.clientWidth;
  if (wrapper) wrapper.classList.toggle('has-scroll', canScroll);
  if (left) { const atStart = !canScroll || tabs.scrollLeft <= 5; left.classList.toggle('hidden', window.innerWidth > 768 && atStart); }
  if (right) { const atEnd = !canScroll || tabs.scrollLeft + tabs.clientWidth >= tabs.scrollWidth - 5; right.classList.toggle('hidden', window.innerWidth > 768 && atEnd); }
  if (wrapper) wrapper.classList.toggle('scroll-end', canScroll && tabs.scrollLeft + tabs.clientWidth >= tabs.scrollWidth - 5);
}
document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.getElementById('uniTabs');
  if (tabs) { updateTabArrows(); tabs.addEventListener('scroll', updateTabArrows); window.addEventListener('resize', updateTabArrows); }
});
document.querySelectorAll('.college-faq-item summary').forEach(s => {
  s.addEventListener('click', () => { const icon = s.querySelector('i'); if (icon) icon.style.transform = s.parentElement.open ? '' : 'rotate(90deg)'; });
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
