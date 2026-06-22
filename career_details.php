<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';
require_once __DIR__ . '/includes/exam_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header("Location: careers.php");
    exit;
}

try {
    // 1. Fetch Career Profile
    $stmt = $pdo->prepare("SELECT * FROM careers WHERE slug = ?");
    $stmt->execute([$slug]);
    $career = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$career) {
        header("Location: careers.php");
        exit;
    }

    // 2. Fetch Related Exams dynamically based on career name keywords
    $name = $career['name'];
    $examQuery = "SELECT exam_name, exam_slug FROM exams ";
    $examParams = [];

    if (stripos($name, 'Engineer') !== false || stripos($name, 'Pilot') !== false) {
        $examQuery .= "WHERE exam_name LIKE ? OR exam_name LIKE ? OR exam_name LIKE ? LIMIT 4";
        $examParams = ['%JEE%', '%BIT%', '%NDA%'];
    } elseif (stripos($name, 'Doctor') !== false) {
        $examQuery .= "WHERE exam_name LIKE ? LIMIT 4";
        $examParams = ['%NEET%'];
    } elseif (stripos($name, 'Lawyer') !== false) {
        $examQuery .= "WHERE exam_name LIKE ? OR exam_name LIKE ? LIMIT 4";
        $examParams = ['%CLAT%', '%AILET%'];
    } elseif (stripos($name, 'Hotel') !== false) {
        $examQuery .= "WHERE exam_name LIKE ? LIMIT 4";
        $examParams = ['%NCH%'];
    } else {
        $examQuery .= "LIMIT 4";
    }

    $examStmt = $pdo->prepare($examQuery);
    $examStmt->execute($examParams);
    $relatedExams = $examStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fallback if no matching exam found
    if (empty($relatedExams)) {
        $relatedExams = $pdo->query("SELECT exam_name, exam_slug FROM exams LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Fetch Related Colleges dynamically based on career stream/keywords
    $clgQuery = "SELECT c.name, c.slug, c.overall_rating_avg, cm.logo_url AS logo FROM colleges c LEFT JOIN college_media cm ON cm.college_id = c.id AND cm.image_type = 'logo' ";
    $clgParams = [];

    if (stripos($name, 'Engineer') !== false || stripos($name, 'Pilot') !== false) {
        $clgQuery .= "WHERE name LIKE ? OR name LIKE ? OR name LIKE ? LIMIT 4";
        $clgParams = ['%IIT%', '%BITS%', '%Engineering%'];
    } elseif (stripos($name, 'Doctor') !== false) {
        $clgQuery .= "WHERE name LIKE ? OR name LIKE ? LIMIT 4";
        $clgParams = ['%Medical%', '%AIIMS%'];
    } elseif (stripos($name, 'Accountant') !== false || stripos($name, 'Banker') !== false) {
        $clgQuery .= "WHERE name LIKE ? OR name LIKE ? OR name LIKE ? LIMIT 4";
        $clgParams = ['%IIM%', '%FMS%', '%Management%'];
    } elseif (stripos($name, 'Hotel') !== false) {
        $clgQuery .= "WHERE name LIKE ? OR name LIKE ? LIMIT 4";
        $clgParams = ['%Hotel%', '%Hospitality%'];
    } else {
        $clgQuery .= "WHERE status='active' ORDER BY is_featured DESC LIMIT 4";
    }

    $clgStmt = $pdo->prepare($clgQuery);
    $clgStmt->execute($clgParams);
    $relatedColleges = $clgStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fallback if no matching colleges
    if (empty($relatedColleges)) {
        $relatedColleges = $pdo->query("SELECT c.name, c.slug, c.overall_rating_avg, cm.logo_url AS logo FROM colleges c LEFT JOIN college_media cm ON cm.college_id = c.id AND cm.image_type = 'logo' WHERE c.status='active' ORDER BY c.overall_rating_avg DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    header("Location: careers.php");
    exit;
}

// Split skills comma list into array
$skills = array_filter(array_map('trim', explode(',', $career['skills_required'] ?: '')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($career['name']) ?> Job Profile | AdmissionSeason</title>
  <meta name="description" content="Learn how to become a <?= htmlspecialchars($career['name']) ?>. Explore job profiles, salary trends, step-by-step career path guidelines, and key skills.">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=8">

  <style>
    :root {
      --oxford-navy: #0B2447;
      --yale-blue: #19376D;
      --snow-pearl: #F8FAFC;
      --ink-black: #0F172A;
      --text-muted-alt: #64748b;
      --border-color-alt: #e2e8f0;
      --success-green: #10b981;
    }

    body {
      background-color: var(--snow-pearl);
      color: var(--ink-black);
      font-family: 'Inter', sans-serif;
    }

    /* Career Hero Header */
    .career-hero {
      background: linear-gradient(rgba(11, 36, 71, 0.9), rgba(25, 55, 109, 0.95)), 
                  url("<?= htmlspecialchars($career['image_url'] ?: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200&h=400&fit=crop') ?>") no-repeat center center;
      background-size: cover;
      color: #fff;
      padding: 90px 0 80px 0;
      text-align: left;
      position: relative;
    }

    .career-hero-content {
      max-width: 800px;
    }

    .career-hero-meta {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }

    .stream-tag {
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.25);
      color: #fff;
      font-size: 0.8rem;
      font-weight: 700;
      padding: 4px 14px;
      border-radius: 100px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .substream-lbl {
      font-size: 0.9rem;
      font-weight: 600;
      opacity: 0.85;
    }

    .career-hero h1 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 3.25rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 14px;
      line-height: 1.15;
    }

    .career-hero p.desc {
      font-size: 1.15rem;
      line-height: 1.6;
      opacity: 0.9;
    }

    /* Layout grid */
    .career-layout {
      padding: 60px 0 100px 0;
      display: grid;
      grid-template-columns: 1.3fr 0.7fr;
      gap: 40px;
      align-items: start;
    }

    .career-section-card {
      background: #fff;
      border: 1px solid var(--border-color-alt);
      border-radius: 24px;
      padding: 40px;
      margin-bottom: 30px;
      box-shadow: 0 4px 15px rgba(11, 36, 71, 0.01);
      text-align: left;
    }

    .career-section-card h2 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.65rem;
      font-weight: 800;
      color: var(--oxford-navy);
      margin-bottom: 24px;
      border-bottom: 2px solid var(--border-color-alt);
      padding-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .career-section-card h2 i {
      color: var(--yale-blue);
      font-size: 1.8rem;
    }

    .career-body-text {
      font-size: 1.02rem;
      line-height: 1.7;
      color: #334155;
    }

    .career-body-text p {
      margin-bottom: 18px;
    }

    .career-body-text p:last-child {
      margin-bottom: 0;
    }

    /* Step-by-Step Path layout */
    .step-path {
      position: relative;
      padding-left: 36px;
      margin-top: 10px;
    }

    .step-path::before {
      content: '';
      position: absolute;
      left: 14px;
      top: 10px;
      bottom: 10px;
      width: 2px;
      background: var(--border-color-alt);
    }

    .step-node {
      position: relative;
      margin-bottom: 30px;
    }

    .step-node:last-child {
      margin-bottom: 0;
    }

    .step-circle {
      position: absolute;
      left: -36px;
      top: 2px;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background: var(--yale-blue);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-family: 'Space Grotesk', sans-serif;
      font-size: 0.85rem;
      border: 3px solid #fff;
      box-shadow: 0 3px 8px rgba(25, 55, 109, 0.2);
    }

    .step-content h4 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.15rem;
      font-weight: 750;
      color: var(--oxford-navy);
      margin-bottom: 8px;
    }

    .step-content p {
      font-size: 0.92rem;
      color: var(--text-muted-alt);
      line-height: 1.5;
    }

    /* Skills list tags */
    .skills-tag-group {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }

    .skill-tag {
      background: var(--snow-pearl);
      border: 1px solid var(--border-color-alt);
      color: var(--oxford-navy);
      font-size: 0.88rem;
      font-weight: 650;
      padding: 8px 18px;
      border-radius: 100px;
      transition: all 0.2s ease;
    }

    .skill-tag:hover {
      background: var(--yale-blue);
      color: #fff;
      border-color: var(--yale-blue);
      transform: translateY(-1px);
    }

    /* Sidebar widgets */
    .sidebar-widget {
      background: #fff;
      border: 1px solid var(--border-color-alt);
      border-radius: 20px;
      padding: 26px;
      margin-bottom: 24px;
      text-align: left;
    }

    .sidebar-widget h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.2rem;
      font-weight: 800;
      color: var(--oxford-navy);
      margin-bottom: 18px;
      border-bottom: 1px solid var(--border-color-alt);
      padding-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .salary-box {
      background: rgba(16, 185, 129, 0.04);
      border: 1px solid rgba(16, 185, 129, 0.2);
      border-radius: 16px;
      padding: 24px;
      text-align: center;
    }

    .salary-num {
      display: block;
      font-family: 'Space Grotesk', sans-serif;
      font-size: 2.2rem;
      font-weight: 800;
      color: #059669;
      margin-bottom: 4px;
    }

    .salary-lbl {
      font-size: 0.85rem;
      color: var(--text-muted-alt);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* List widgets */
    .link-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .link-list li {
      margin-bottom: 12px;
      padding-bottom: 12px;
      border-bottom: 1px solid var(--border-color-alt);
    }

    .link-list li:last-child {
      margin-bottom: 0;
      padding-bottom: 0;
      border-bottom: none;
    }

    .link-list a {
      display: flex;
      align-items: center;
      justify-content: space-between;
      color: var(--oxford-navy);
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 700;
      transition: color 0.2s;
    }

    .link-list a:hover {
      color: var(--primary-accent);
    }

    .link-list a i {
      font-size: 1rem;
      color: var(--text-muted-alt);
      transition: transform 0.2s;
    }

    .link-list a:hover i {
      transform: translateX(3px);
      color: var(--primary-accent);
    }

    /* College Widget Card */
    .widget-college-card {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-bottom: 16px;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--border-color-alt);
    }

    .widget-college-card:last-child {
      margin-bottom: 0;
      padding-bottom: 0;
      border-bottom: none;
    }

    .widget-college-logo {
      width: 42px;
      height: 42px;
      border-radius: 8px;
      object-fit: cover;
      border: 1px solid var(--border-color-alt);
    }

    .widget-college-info h4 {
      font-size: 0.88rem;
      font-weight: 750;
      color: var(--oxford-navy);
    }

    .widget-college-info h4 a {
      color: inherit;
      text-decoration: none;
    }

    .widget-college-info h4 a:hover {
      color: var(--yale-blue);
    }

    .widget-rating {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 0.76rem;
      color: #eab308;
      font-weight: 700;
      margin-top: 2px;
    }

    /* Counselling CTA Card */
    .cns-cta-card {
      background: linear-gradient(135deg, var(--yale-blue) 0%, var(--oxford-navy) 100%);
      color: #fff;
      border-radius: 20px;
      padding: 30px;
      text-align: center;
    }

    .cns-cta-card h4 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.25rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .cns-cta-card p {
      font-size: 0.82rem;
      opacity: 0.85;
      margin-bottom: 20px;
      line-height: 1.4;
    }

    .cns-cta-btn {
      display: inline-block;
      background: #fff;
      color: var(--oxford-navy);
      text-decoration: none;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 750;
      font-size: 0.88rem;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: all 0.2s;
    }

    .cns-cta-btn:hover {
      background: var(--snow-pearl);
      transform: translateY(-1px);
    }

    /* Responsive Media Queries */
    @media (max-width: 992px) {
      .career-layout {
        grid-template-columns: 1fr;
        padding: 40px 20px;
        gap: 30px;
      }
      
      .career-hero {
        padding: 60px 20px;
      }
      
      .career-hero h1 {
        font-size: 2.25rem;
      }
    }

    @media (max-width: 768px) {
      .career-section-card {
        padding: 24px;
      }
      
      .career-section-card h2 {
        font-size: 1.4rem;
      }
      
      .career-hero h1 {
        font-size: 1.85rem;
      }
      
      .step-path {
        padding-left: 24px;
      }
      
      .step-circle {
        left: -24px;
        width: 24px;
        height: 24px;
        font-size: 0.75rem;
      }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Hero banner -->
<section class="career-hero">
  <div class="container">
    <div class="career-hero-content">
      <div class="career-hero-meta">
        <span class="stream-tag"><?= htmlspecialchars($career['stream']) ?></span>
        <span class="substream-lbl"><i class="ph ph-compass"></i> <?= htmlspecialchars($career['sub_stream']) ?></span>
      </div>
      <h1><?= htmlspecialchars($career['name']) ?></h1>
      <p class="desc"><?= htmlspecialchars($career['short_description']) ?></p>
    </div>
  </div>
</section>

<!-- Content Container -->
<div class="container career-layout">
  
  <!-- Left Column: Details -->
  <div>
    <!-- 1. Job Profile -->
    <div class="career-section-card">
      <h2><i class="ph ph-briefcase"></i> Job Profile Description</h2>
      <div class="career-body-text">
        <p><?= nl2br(htmlspecialchars($career['job_profile'] ?: 'No profile loaded.')) ?></p>
      </div>
    </div>

    <!-- 2. How to get there -->
    <div class="career-section-card">
      <h2><i class="ph ph-footprints"></i> How to Get There (Step-by-Step)</h2>
      <div class="step-path">
        <?php 
          // Format how_to_get_there string which has numbered blocks e.g. "1. **Title**: desc"
          $stepsRaw = explode("\n", $career['how_to_get_there'] ?: '');
          $stepCount = 1;
          foreach ($stepsRaw as $stepLine) {
              $stepLine = trim($stepLine);
              if (empty($stepLine)) continue;
              
              // Strip leading number if present (e.g. "1. ")
              $stepLineClean = preg_replace('/^\d+\.\s+/', '', $stepLine);
              
              // Check if contains bold title e.g. "**Title**:" or "**Title**"
              $title = "Step " . $stepCount;
              $body = $stepLineClean;
              if (preg_match('/^\*\*(.*?)\*\*:(.*)/', $stepLineClean, $matches)) {
                  $title = $matches[1];
                  $body = trim($matches[2]);
              }
              ?>
              <div class="step-node">
                <div class="step-circle"><?= $stepCount ?></div>
                <div class="step-content">
                  <h4><?= htmlspecialchars($title) ?></h4>
                  <p><?= htmlspecialchars($body) ?></p>
                </div>
              </div>
              <?php
              $stepCount++;
          }
        ?>
      </div>
    </div>

    <!-- 3. Key Skills Required -->
    <div class="career-section-card">
      <h2><i class="ph ph-wrench"></i> Key Skills Required</h2>
      <div class="skills-tag-group">
        <?php if(empty($skills)): ?>
          <span class="skill-tag">Analytical Skills</span>
          <span class="skill-tag">Logical Thinking</span>
        <?php else: ?>
          <?php foreach ($skills as $sk): ?>
            <span class="skill-tag"><?= htmlspecialchars($sk) ?></span>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right Column: Sidebar -->
  <div>
    <!-- 1. Average starting salary -->
    <div class="sidebar-widget">
      <h3><i class="ph ph-currency-circle-dollar" style="color:#059669;"></i> Average Salary</h3>
      <div class="salary-box">
        <span class="salary-num"><?= htmlspecialchars($career['salary_range'] ?: '6 - 12 LPA') ?></span>
        <span class="salary-lbl">Per Annum (Starting)</span>
      </div>
    </div>

    <!-- 2. Related entrance exams -->
    <div class="sidebar-widget">
      <h3><i class="ph ph-exam" style="color:var(--yale-blue);"></i> Entrance Exams</h3>
      <ul class="link-list">
        <?php foreach ($relatedExams as $ex): ?>
          <li>
            <a href="/ADMISSION/exam/<?= htmlspecialchars($ex['exam_slug']) ?>">
              <?= htmlspecialchars($ex['exam_name']) ?> <i class="ph ph-arrow-right"></i>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- 3. Recommended Colleges -->
    <div class="sidebar-widget">
      <h3><i class="ph ph-buildings" style="color:var(--yale-blue);"></i> Recommended Colleges</h3>
      <?php foreach ($relatedColleges as $clg): ?>
        <div class="widget-college-card">
          <img src="<?= htmlspecialchars($clg['logo'] ?: 'https://images.unsplash.com/photo-1562774053-701939374585?w=60&h=60&fit=crop') ?>" alt="college logo" class="widget-college-logo">
          <div class="widget-college-info">
            <h4><a href="/ADMISSION/college/<?= htmlspecialchars($clg['slug']) ?>"><?= htmlspecialchars($clg['name']) ?></a></h4>
            <div class="widget-rating">
              <i class="ph-fill ph-star"></i> <?= number_format((float)$clg['overall_rating_avg'], 1) ?> / 5.0
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- 4. Speak to expert counsellor -->
    <div class="cns-cta-card">
      <h4>Speak to an Expert Advisor</h4>
      <p>Confused about career options or how to secure admission for <?= htmlspecialchars($career['name']) ?>? Get 1-on-1 expert guidance for free!</p>
      <a href="/ADMISSION/counselling?prefill_career=<?= urlencode($career['name']) ?>&prefill_stream=<?= urlencode($career['stream']) ?>" class="cns-cta-btn">
        <i class="ph-fill ph-headset"></i> Request Call Back
      </a>
    </div>
  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
