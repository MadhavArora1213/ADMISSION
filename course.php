<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';
require_once __DIR__ . '/includes/course_helpers.php';

$slug = trim($_GET['slug'] ?? '');
$tab  = trim($_GET['tab'] ?? 'info');
$tabs = courseTabs();

if ($slug === '') {
    header('Location: courses.php');
    exit;
}
if (!isset($tabs[$tab])) {
    $tab = 'info';
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
$colleges = getCollegesForCourse($pdo, $course['course_name']);

$pageTitle = $course['course_name'] . ' 2026: Scope, Fees, Specializations, Jobs & Top Colleges';
$metaDesc = 'Details about ' . $course['course_name'] . ' including average salary, eligibility, specializations, career paths and top colleges in India.';

$tabIcons = [
    'info'=>'ph-info', 'specializations'=>'ph-git-branch', 'careers'=>'ph-briefcase', 'colleges'=>'ph-buildings'
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
    .course-hero { background: linear-gradient(135deg, #0B2447 0%, #19376D 100%), url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M54.627 0l.83.83-49.12 49.12L5.5 49.12 54.627 0zM0 54.627l.83.83L5.5 54.627 0 49.12v5.507z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E"); padding: 80px 0 60px; color: #fff; position: relative; overflow: hidden; }
    .course-hero::after { content:''; position:absolute; bottom:0; left:0; right:0; height:40px; background:linear-gradient(to top, rgba(255,255,255,0.1), transparent); pointer-events:none; }
    .course-hero-inner { display: flex; gap: 32px; align-items: flex-start; position: relative; z-index: 2; }
    .course-hero-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 3rem; font-weight: 800; margin: 0 0 20px 0; line-height: 1.2; text-shadow: 0 2px 10px rgba(0,0,0,0.2); }
    .course-hero-chips { display: flex; flex-wrap: wrap; gap: 12px; }
    .course-hero-chips span { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); border-radius: 30px; font-size: 0.95rem; font-weight: 600; backdrop-filter: blur(8px); box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .course-hero-chips span i { font-size: 1.2rem; }
    .course-hero-actions { margin-left: auto; display: flex; flex-direction: column; gap: 12px; }
    .course-btn-primary { background: #fff; color: var(--cp-blue); padding: 16px 32px; border-radius: 50px; font-weight: 800; text-decoration: none; text-align: center; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.15); font-size: 1.05rem; display: inline-flex; align-items: center; gap: 8px; }
    .course-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.2); background: #f8fafc; color: #0f172a; }
    
    .course-tabs-sticky { position: sticky; top: 0; z-index: 100; background: rgba(255,255,255,0.95); border-bottom: 1px solid var(--cp-border); box-shadow: 0 4px 20px rgba(0,0,0,0.04); backdrop-filter: blur(10px); }
    .shiksha-tabs-nav ul { display: flex; list-style: none; padding: 0; margin: 0; overflow-x: auto; gap: 40px; }
    .shiksha-tabs-nav li a { display: flex; align-items: center; gap: 10px; padding: 22px 0; color: rgba(15,23,42,0.45); font-weight: 700; text-decoration: none; border-bottom: 3px solid transparent; transition: all 0.3s ease; white-space: nowrap; font-size: 1rem; }
    .shiksha-tabs-nav li a:hover { color: var(--cp-blue); }
    .shiksha-tabs-nav li a.active { color: var(--cp-blue); border-bottom-color: var(--cp-blue); }
    
    .tab-content { padding: 50px 0; min-height: 50vh; }
    
    .info-card { background: #fff; border-radius: 20px; padding: 40px; border: 1px solid var(--cp-border); box-shadow: 0 10px 40px rgba(0,0,0,0.03); margin-bottom: 32px; position: relative; overflow: hidden; }
    .info-card::before { content:''; position:absolute; left:0; top:0; width:6px; height:100%; background: linear-gradient(to bottom, var(--cp-blue), #19376D); border-radius: 20px 0 0 20px; }
    .info-card-title { font-size: 1.6rem; font-weight: 800; color: var(--cp-blue); margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
    .info-card-title i { color: #19376D; background: rgba(11,36,71,0.06); padding: 10px; border-radius: 12px; font-size: 1.5rem; }
    .info-card-content { font-size: 1.1rem; line-height: 1.8; color: rgba(15,23,42,0.65); }

    .specs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; margin-top: 32px; }
    .spec-card { background: #fff; border: 1px solid var(--cp-border); border-radius: 20px; padding: 30px; transition: all 0.3s ease; display: flex; flex-direction: column; gap: 12px; position: relative; overflow: hidden; }
    .spec-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(11,36,71,0.06); border-color: var(--cp-blue); }
    .spec-card-icon { width: 50px; height: 50px; background: rgba(11,36,71,0.06); color: var(--cp-blue); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 8px; transition: all 0.3s ease; }
    .spec-card:hover .spec-card-icon { background: var(--cp-blue); color: #fff; transform: scale(1.1) rotate(5deg); }
    .spec-card h4 { font-size: 1.25rem; color: #0f172a; margin: 0; font-weight: 800; }
    .spec-card p { font-size: 1rem; color: rgba(15,23,42,0.45); margin: 0; line-height: 1.6; }
    
    .career-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px; margin-top: 32px; }
    .career-card { background: #fff; border-radius: 20px; padding: 30px; border: 1px solid var(--cp-border); box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: all 0.3s ease; }
    .career-card:hover { transform: translateY(-5px); box-shadow: 0 20px 50px rgba(11,36,71,0.08); border-color: var(--cp-blue); }
    .career-role { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
    .career-role i { color: #19376D; font-size: 1.8rem; background: rgba(11,36,71,0.04); padding: 12px; border-radius: 14px; }
    .career-salary-box { display: flex; background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 24px; border: 1px solid rgba(15,23,42,0.08); }
    .career-salary-item { flex: 1; text-align: center; }
    .career-salary-item:first-child { border-right: 1px solid rgba(15,23,42,0.08); }
    .career-salary-label { font-size: 0.85rem; color: rgba(15,23,42,0.45); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .career-salary-val { font-size: 1.25rem; font-weight: 800; color: #0B2447; }
    .career-companies { font-size: 0.95rem; color: rgba(15,23,42,0.65); line-height: 1.6; }
    .career-companies strong { color: #0f172a; display: block; margin-bottom: 8px; font-weight: 700; }
    
    .clg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; margin-top: 32px; }
    .clg-mini-card { background: #fff; border: 1px solid var(--cp-border); border-radius: 20px; padding: 20px; display: flex; align-items: center; gap: 20px; text-decoration: none; transition: all 0.3s ease; }
    .clg-mini-card:hover { box-shadow: 0 15px 35px rgba(0,0,0,0.06); border-color: var(--cp-blue); transform: translateX(5px); }
    .clg-mini-img { width: 70px; height: 70px; border-radius: 14px; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .clg-mini-info h4 { font-size: 1.1rem; color: #0f172a; margin: 0 0 6px 0; font-weight: 800; line-height: 1.4; }
    .clg-mini-info p { font-size: 0.9rem; color: rgba(15,23,42,0.45); margin: 0; display: flex; align-items: center; gap: 6px; }
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
        <span><i class="ph ph-buildings"></i> Offered by <?= htmlspecialchars((string)$course['total_colleges_offering']) ?> Colleges</span>
        <?php endif; ?>
        
        <?php if(!empty($course['avg_salary_lpa'])): ?>
        <span><i class="ph ph-currency-inr"></i> ₹<?= htmlspecialchars((string)$course['avg_salary_lpa']) ?> LPA Avg Salary</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="course-hero-actions">
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/colleges" class="course-btn-primary">
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
  <div class="container tab-content">
    
    <?php if ($tab === 'info'): ?>
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

      <div class="info-card">
        <h2 class="info-card-title"><i class="ph ph-rocket"></i> Career Scope & Future</h2>
        <div class="info-card-content">
          <?= nl2br(htmlspecialchars((string)($course['career_scope'] ?? 'Details not available.'))) ?>
        </div>
      </div>
      
      <?php if (!empty($course['top_recruiters'])): ?>
      <div class="info-card" style="padding-top:30px;">
        <h3 style="font-size:1.4rem;font-weight:800;margin-bottom:20px;color:var(--cp-blue);display:flex;align-items:center;gap:10px;"><i class="ph ph-buildings" style="color:#19376D"></i> Top Recruiters</h3>
        <div style="display:flex;flex-wrap:wrap;gap:12px;">
          <?php 
            $recs = json_decode($course['top_recruiters'], true);
            if (!is_array($recs)) $recs = explode(',', $course['top_recruiters']);
            foreach($recs as $tr): 
          ?>
            <span style="background:var(--cp-light);border:1px solid rgba(15,23,42,0.08);padding:10px 20px;border-radius:30px;font-weight:700;font-size:0.95rem;color:var(--cp-blue);display:inline-flex;align-items:center;gap:6px;transition:all 0.3s ease;cursor:default;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 10px rgba(0,0,0,0.05)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none'"><i class="ph ph-briefcase"></i> <?= htmlspecialchars(trim((string)$tr)) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    <?php elseif ($tab === 'specializations'): ?>
      <h2 style="font-size:1.8rem;font-weight:800;color:var(--cp-blue);margin-bottom:8px">Popular Specializations</h2>
      <p style="color:rgba(15,23,42,0.45);font-size:1.1rem;margin-bottom:32px">Explore the various branches and specialized fields within this course.</p>
      
      <?php if(empty($specs)): ?>
        <div style="background:#fff;padding:60px 20px;text-align:center;border-radius:20px;border:1px dashed rgba(15,23,42,0.15)">
          <i class="ph ph-empty" style="font-size:3rem;color:rgba(15,23,42,0.4);margin-bottom:10px"></i>
          <p style="font-size:1.1rem;color:rgba(15,23,42,0.45)">No specific specializations listed for this course yet.</p>
        </div>
      <?php else: ?>
        <div class="specs-grid">
          <?php foreach($specs as $s): ?>
          <div class="spec-card">
            <div class="spec-card-icon"><i class="ph ph-star"></i></div>
            <h4><?= htmlspecialchars((string)$s['specialization_name']) ?></h4>
            <p><?= htmlspecialchars((string)$s['description']) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php elseif ($tab === 'careers'): ?>
      <h2 style="font-size:1.8rem;font-weight:800;color:var(--cp-blue);margin-bottom:8px">Career & Job Opportunities</h2>
      <p style="color:rgba(15,23,42,0.45);font-size:1.1rem;margin-bottom:32px">Discover the top roles, salary expectations, and top hiring companies.</p>
      
      <?php if(empty($careers)): ?>
        <div style="background:#fff;padding:60px 20px;text-align:center;border-radius:20px;border:1px dashed rgba(15,23,42,0.15)">
          <i class="ph ph-empty" style="font-size:3rem;color:rgba(15,23,42,0.4);margin-bottom:10px"></i>
          <p style="font-size:1.1rem;color:rgba(15,23,42,0.45)">No career data available right now.</p>
        </div>
      <?php else: ?>
        <div class="career-grid">
          <?php foreach($careers as $cr): ?>
          <div class="career-card">
            <div class="career-role">
              <i class="ph ph-briefcase"></i>
              <?= htmlspecialchars((string)$cr['job_role']) ?>
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
            
            <div class="career-companies">
              <strong><i class="ph ph-buildings"></i> Top Hiring Companies</strong>
              <?php 
                $comps = json_decode($cr['top_companies'] ?? '', true);
                echo htmlspecialchars((string)(is_array($comps) ? implode(', ', $comps) : ($cr['top_companies'] ?? '')));
              ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php elseif ($tab === 'colleges'): ?>
      <h2 style="font-size:1.8rem;font-weight:800;color:var(--cp-blue);margin-bottom:8px">Top Colleges offering <?= htmlspecialchars((string)($course['course_name'] ?? 'this course')) ?></h2>
      <p style="color:rgba(15,23,42,0.45);font-size:1.1rem;margin-bottom:32px">Find the best institutions across India offering this program.</p>
      
      <?php if(empty($colleges)): ?>
        <div style="background:#fff;padding:60px 20px;text-align:center;border-radius:20px;border:1px dashed rgba(15,23,42,0.15)">
          <i class="ph ph-empty" style="font-size:3rem;color:rgba(15,23,42,0.4);margin-bottom:10px"></i>
          <p style="font-size:1.1rem;color:rgba(15,23,42,0.45)">No colleges found offering this course yet.</p>
        </div>
      <?php else: ?>
        <div class="clg-grid">
          <?php foreach($colleges as $clg): ?>
          <a href="<?= collegeUrl($clg['slug'] ?? '') ?>" class="clg-mini-card">
            <img src="<?= cImg($clg['logo'] ?? '') ?>" class="clg-mini-img" alt="<?= htmlspecialchars((string)($clg['name'] ?? '')) ?>">
            <div class="clg-mini-info">
              <h4><?= htmlspecialchars((string)($clg['name'] ?? '')) ?></h4>
              <p><i class="ph ph-map-pin" style="color:var(--cp-blue)"></i> <?= htmlspecialchars((string)($clg['city'] ?? '')) ?>, <?= htmlspecialchars((string)($clg['state_name'] ?? 'India')) ?></p>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
