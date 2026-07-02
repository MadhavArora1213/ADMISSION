<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: ' . BASE_URL . '/study-abroad'); exit; }

$stmt = $pdo->prepare("SELECT * FROM foreign_universities WHERE university_slug = ? OR id = ? LIMIT 1");
$stmt->execute([$slug, $slug]);
$uni = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$uni) { header('Location: ' . BASE_URL . '/study-abroad'); exit; }

$metaDesc = 'Details about ' . $uni['university_name'] . ' including tuition, eligibility, rankings, and admission requirements.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($uni['university_name']) ?> - Details | AdmissionSeason</title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=8">
  <style>
    :root { --oxford-navy:#0B2447; --yale-blue:#19376D; --snow-pearl:#F8FAFC; --ink-black:#0F172A; --border-color-alt:#e2e8f0; --text-muted-alt:#64748b; }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Inter',sans-serif; background:var(--snow-pearl); color:var(--ink-black); }
    .uni-hero { background:linear-gradient(135deg,var(--yale-blue),var(--oxford-navy)); color:#fff; padding:50px 0 40px; }
    .uni-hero-inner { max-width:1100px; margin:0 auto; padding:0 24px; display:flex; gap:28px; align-items:center; }
    .uni-hero-logo { width:90px; height:90px; border-radius:18px; border:3px solid rgba(255,255,255,0.3); object-fit:cover; background:rgba(255,255,255,0.1); flex-shrink:0; }
    .uni-hero-info h1 { font-family:'Space Grotesk',sans-serif; font-size:2rem; font-weight:800; margin-bottom:6px; }
    .uni-hero-info .loc { font-size:0.95rem; opacity:0.85; display:flex; align-items:center; gap:6px; margin-bottom:12px; }
    .uni-hero-badges { display:flex; gap:10px; flex-wrap:wrap; }
    .uni-hero-badge { background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25); padding:5px 14px; border-radius:100px; font-size:0.78rem; font-weight:700; }
    .uni-back { color:rgba(255,255,255,0.7); text-decoration:none; font-size:0.85rem; font-weight:600; display:inline-flex; align-items:center; gap:6px; margin-bottom:16px; transition:color .2s; }
    .uni-back:hover { color:#fff; }
    .uni-body { max-width:1100px; margin:0 auto; padding:30px 24px 80px; }
    .uni-grid-2 { display:grid; grid-template-columns:2fr 1fr; gap:28px; }
    @media(max-width:992px) {
      .uni-grid-2 { grid-template-columns:1fr; }
      .uni-hero-inner { gap:20px; }
    }
    @media(max-width:768px) {
      .uni-hero { padding:36px 0 30px; }
      .uni-hero-inner { flex-direction:column; text-align:center; padding:0 16px; gap:14px; }
      .uni-hero-logo { width:70px; height:70px; border-radius:14px; }
      .uni-hero-info h1 { font-size:1.5rem; }
      .uni-hero-info .loc { justify-content:center; font-size:0.85rem; }
      .uni-hero-badges { justify-content:center; }
      .uni-hero-badge { font-size:0.7rem; padding:4px 10px; }
      .uni-back { margin-bottom:10px; font-size:0.8rem; }
      .uni-body { padding:20px 16px 60px; }
      .uni-grid-2 { gap:20px; }
      .uni-section { padding:18px; border-radius:14px; }
      .uni-section h2 { font-size:1rem; margin-bottom:12px; }
      .info-grid { grid-template-columns:1fr 1fr; gap:10px; }
      .info-box { padding:10px; }
      .info-box strong { font-size:0.95rem; }
      .sidebar-stat { padding:10px 0; }
      .desc-text { font-size:0.85rem; }
      .cta-btn { padding:12px; font-size:0.88rem; }
    }
    @media(max-width:480px) {
      .uni-hero { padding:28px 0 24px; }
      .uni-hero-info h1 { font-size:1.25rem; }
      .uni-hero-logo { width:56px; height:56px; border-radius:12px; }
      .uni-body { padding:16px 12px 50px; }
      .uni-section { padding:14px; border-radius:12px; }
      .info-grid { grid-template-columns:1fr; gap:8px; }
      .info-box { padding:10px 12px; }
      .tag-row { gap:6px; }
      .tag { padding:4px 10px; font-size:0.72rem; }
    }
    .uni-section { background:#fff; border:1px solid var(--border-color-alt); border-radius:16px; padding:24px; margin-bottom:24px; }
    .uni-section h2 { font-family:'Space Grotesk',sans-serif; font-size:1.15rem; font-weight:700; color:var(--oxford-navy); margin-bottom:16px; display:flex; align-items:center; gap:8px; }
    .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .info-box { background:var(--snow-pearl); border-radius:10px; padding:14px; }
    .info-box span { display:block; font-size:0.7rem; text-transform:uppercase; font-weight:600; color:var(--text-muted-alt); letter-spacing:0.5px; margin-bottom:4px; }
    .info-box strong { font-size:1.1rem; color:var(--oxford-navy); font-weight:800; }
    .tag-row { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
    .tag { display:inline-block; padding:5px 12px; border-radius:8px; background:rgba(25,55,109,0.06); color:var(--yale-blue); font-size:0.78rem; font-weight:600; }
    .desc-text { font-size:0.92rem; line-height:1.7; color:#334155; }
    .cta-btn { display:block; text-align:center; background:var(--oxford-navy); color:#fff; text-decoration:none; padding:14px; border-radius:12px; font-weight:700; font-size:0.95rem; transition:all .25s; }
    .cta-btn:hover { background:var(--yale-blue); box-shadow:0 6px 20px rgba(11,36,71,0.15); }
    .sidebar-stat { display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--border-color-alt); }
    .sidebar-stat:last-child { border-bottom:none; }
    .sidebar-stat span { color:var(--text-muted-alt); font-size:0.85rem; }
    .sidebar-stat strong { color:var(--oxford-navy); font-size:0.9rem; font-weight:700; }
  </style>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<section class="uni-hero">
  <div class="uni-hero-inner">
    <a href="<?= BASE_URL ?>/study-abroad" class="uni-back"><i class="ph ph-arrow-left"></i> Back to Study Abroad</a>
  </div>
  <div class="uni-hero-inner" style="margin-top:-10px">
    <img src="<?= htmlspecialchars($uni['logo_url'] ?: 'https://images.unsplash.com/photo-1592280771190-3e2e4d571952?w=150&h=150&fit=crop') ?>" alt="logo" class="uni-hero-logo">
    <div class="uni-hero-info">
      <h1><?= htmlspecialchars($uni['university_name']) ?></h1>
      <div class="loc"><i class="ph ph-map-pin"></i> <?= htmlspecialchars($uni['city'] ? $uni['city'] . ', ' : '') ?><?= htmlspecialchars($uni['country']) ?></div>
      <div class="uni-hero-badges">
        <?php if ($uni['qs_rank']): ?><span class="uni-hero-badge">QS World #<?= htmlspecialchars((string)$uni['qs_rank']) ?></span><?php endif; ?>
        <?php if ($uni['times_rank']): ?><span class="uni-hero-badge">THE #<?= htmlspecialchars((string)$uni['times_rank']) ?></span><?php endif; ?>
        <?php if ($uni['institution_type']): ?><span class="uni-hero-badge"><?= htmlspecialchars($uni['institution_type']) ?></span><?php endif; ?>
        <?php if ($uni['scholarship_available']): ?><span class="uni-hero-badge"><i class="ph ph-medal"></i> Scholarships Available</span><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="uni-body">
  <div class="uni-grid-2">
    <div>
      <!-- About -->
      <?php if (!empty($uni['description'])): ?>
      <div class="uni-section">
        <h2><i class="ph ph-book-open"></i> About</h2>
        <p class="desc-text"><?= nl2br(htmlspecialchars($uni['description'])) ?></p>
      </div>
      <?php endif; ?>

      <!-- Key Facts -->
      <div class="uni-section">
        <h2><i class="ph ph-chart-bar"></i> Key Facts</h2>
        <div class="info-grid">
          <div class="info-box">
            <span>Tuition Fee (Annual)</span>
            <strong><?= (float)$uni['tuition_usd_annual'] > 0 ? '$' . number_format((float)$uni['tuition_usd_annual'], 0) : 'Free' ?></strong>
          </div>
          <div class="info-box">
            <span>Living Cost (Monthly)</span>
            <strong>$<?= number_format((float)$uni['living_cost_usd_monthly'], 0) ?></strong>
          </div>
          <div class="info-box">
            <span>Acceptance Rate</span>
            <strong><?= htmlspecialchars((string)$uni['acceptance_rate']) ?>%</strong>
          </div>
          <div class="info-box">
            <span>Application Fee</span>
            <strong>$<?= number_format((float)$uni['application_fee_usd'], 0) ?></strong>
          </div>
          <div class="info-box">
            <span>Min GPA</span>
            <strong><?= htmlspecialchars((string)$uni['min_gpa']) ?></strong>
          </div>
          <div class="info-box">
            <span>Institution Type</span>
            <strong><?= htmlspecialchars($uni['institution_type'] ?: 'N/A') ?></strong>
          </div>
        </div>
      </div>

      <!-- Admission Requirements -->
      <div class="uni-section">
        <h2><i class="ph ph-graduation-cap"></i> Admission Requirements</h2>
        <div class="info-grid">
          <?php if ($uni['min_ielts']): ?>
          <div class="info-box"><span>IELTS (Min)</span><strong><?= htmlspecialchars((string)$uni['min_ielts']) ?></strong></div>
          <?php endif; ?>
          <?php if ($uni['min_toefl']): ?>
          <div class="info-box"><span>TOEFL (Min)</span><strong><?= htmlspecialchars((string)$uni['min_toefl']) ?></strong></div>
          <?php endif; ?>
          <?php if ($uni['min_pte']): ?>
          <div class="info-box"><span>PTE (Min)</span><strong><?= htmlspecialchars((string)$uni['min_pte']) ?></strong></div>
          <?php endif; ?>
          <?php if ($uni['min_gre']): ?>
          <div class="info-box"><span>GRE (Min)</span><strong><?= htmlspecialchars((string)$uni['min_gre']) ?></strong></div>
          <?php endif; ?>
          <?php if ($uni['min_gmat']): ?>
          <div class="info-box"><span>GMAT (Min)</span><strong><?= htmlspecialchars((string)$uni['min_gmat']) ?></strong></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Programs & Intake -->
      <div class="uni-section">
        <h2><i class="ph ph-calendar-blank"></i> Programs & Intake</h2>
        <?php
          $degrees = json_decode($uni['degrees_offered'] ?? '[]', true);
          $months = json_decode($uni['intake_months'] ?? '[]', true);
        ?>
        <?php if (!empty($degrees)): ?>
          <p style="font-size:.85rem;color:var(--text-muted-alt);margin-bottom:8px">Degrees Offered</p>
          <div class="tag-row"><?php foreach ($degrees as $d): ?><span class="tag"><?= htmlspecialchars($d) ?></span><?php endforeach; ?></div>
        <?php endif; ?>
        <?php if (!empty($months)): ?>
          <p style="font-size:.85rem;color:var(--text-muted-alt);margin:16px 0 8px">Intake Months</p>
          <div class="tag-row"><?php foreach ($months as $m): ?><span class="tag"><?= htmlspecialchars($m) ?></span><?php endforeach; ?></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sidebar -->
    <div>
      <div class="uni-section" style="position:sticky;top:20px">
        <h2><i class="ph ph-info"></i> Quick Info</h2>
        <div class="sidebar-stat"><span>Country</span><strong><?= htmlspecialchars($uni['country']) ?></strong></div>
        <div class="sidebar-stat"><span>City</span><strong><?= htmlspecialchars($uni['city'] ?: 'N/A') ?></strong></div>
        <?php if ($uni['qs_rank']): ?><div class="sidebar-stat"><span>QS Ranking</span><strong>#<?= htmlspecialchars((string)$uni['qs_rank']) ?></strong></div><?php endif; ?>
        <?php if ($uni['times_rank']): ?><div class="sidebar-stat"><span>THE Ranking</span><strong>#<?= htmlspecialchars((string)$uni['times_rank']) ?></strong></div><?php endif; ?>
        <div class="sidebar-stat"><span>Tuition/Year</span><strong>$<?= number_format((float)$uni['tuition_usd_annual'], 0) ?></strong></div>
        <div class="sidebar-stat"><span>Living Cost/Mo</span><strong>$<?= number_format((float)$uni['living_cost_usd_monthly'], 0) ?></strong></div>
        <div class="sidebar-stat"><span>Acceptance Rate</span><strong><?= htmlspecialchars((string)$uni['acceptance_rate']) ?>%</strong></div>

        <?php if ($uni['official_url']): ?>
        <a href="<?= htmlspecialchars($uni['official_url']) ?>" target="_blank" rel="noopener" class="cta-btn" style="margin-top:18px">
          <i class="ph ph-globe"></i> Visit Official Website
        </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/counselling" class="cta-btn" style="margin-top:10px;background:var(--yale-blue)">
          <i class="ph ph-headset"></i> Get Free Counselling
        </a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
