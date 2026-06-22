<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /ADMISSION/study-abroad?tab=consultants'); exit; }

$stmt = $pdo->prepare("SELECT * FROM consultants WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$con = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$con) { header('Location: /ADMISSION/study-abroad?tab=consultants'); exit; }

$conCountries = json_decode($con['specialization_countries'] ?? '[]', true);
$mode = strtolower($con['consultation_mode'] ?? 'both');

$metaDesc = $con['consultant_name'] . ' – Verified study abroad consultant with ' . $con['experience_years'] . '+ years experience. ' . $con['success_rate_percent'] . '% success rate.';

$countryFlags = [
    'United States' => '🇺🇸', 'United Kingdom' => '🇬🇧', 'Canada' => '🇨🇦',
    'Australia' => '🇦🇺', 'Germany' => '🇩🇪', 'Singapore' => '🇸🇬',
    'Ireland' => '🇮🇪', 'New Zealand' => '🇳🇿',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($con['consultant_name']) ?> – Consultant Profile | AdmissionSeason</title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/ADMISSION/assets/css/style.css?v=8">
  <style>
    :root { --oxford-navy:#0B2447; --yale-blue:#19376D; --snow-pearl:#F8FAFC; --ink-black:#0F172A; --border-color-alt:#e2e8f0; --text-muted-alt:#64748b; }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Inter',sans-serif; background:var(--snow-pearl); color:var(--ink-black); }

    /* Hero */
    .con-hero {
      background: linear-gradient(135deg, var(--yale-blue), var(--oxford-navy));
      color: #fff; padding: 50px 0 40px; position: relative; overflow: hidden;
    }
    .con-hero::after {
      content: ''; position: absolute; top: -60px; right: -60px;
      width: 220px; height: 220px;
      background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
      border-radius: 50%;
    }
    .con-hero-inner { max-width: 1100px; margin: 0 auto; padding: 0 24px; }
    .con-back {
      color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.85rem;
      font-weight: 600; display: inline-flex; align-items: center; gap: 6px;
      margin-bottom: 20px; transition: color 0.2s;
    }
    .con-back:hover { color: #fff; }
    .con-hero-row { display: flex; gap: 24px; align-items: center; }
    .con-hero-avatar {
      width: 88px; height: 88px; border-radius: 20px;
      border: 3px solid rgba(255,255,255,0.3); object-fit: cover;
      background: rgba(255,255,255,0.1); flex-shrink: 0;
    }
    .con-hero-info h1 {
      font-family: 'Space Grotesk', sans-serif; font-size: 2rem;
      font-weight: 800; margin-bottom: 4px;
      display: flex; align-items: center; gap: 10px;
    }
    .con-hero-verified {
      font-size: 1.3rem; color: #38bdf8;
    }
    .con-hero-info .loc {
      font-size: 0.95rem; opacity: 0.85; display: flex; align-items: center; gap: 6px; margin-bottom: 12px;
    }
    .con-hero-badges { display: flex; gap: 8px; flex-wrap: wrap; }
    .con-hero-badge {
      background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
      padding: 5px 14px; border-radius: 100px; font-size: 0.78rem; font-weight: 700;
      display: inline-flex; align-items: center; gap: 5px;
    }

    /* Body */
    .con-body { max-width: 1100px; margin: 0 auto; padding: 30px 24px 80px; }
    .con-grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 28px; }

    /* Section card */
    .con-section {
      background: #fff; border: 1px solid var(--border-color-alt);
      border-radius: 16px; padding: 24px; margin-bottom: 24px;
    }
    .con-section h2 {
      font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem;
      font-weight: 700; color: var(--oxford-navy); margin-bottom: 16px;
      display: flex; align-items: center; gap: 8px;
    }

    /* Info grid */
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .info-box {
      background: var(--snow-pearl); border-radius: 10px; padding: 14px;
    }
    .info-box span {
      display: block; font-size: 0.7rem; text-transform: uppercase;
      font-weight: 600; color: var(--text-muted-alt); letter-spacing: 0.5px; margin-bottom: 4px;
    }
    .info-box strong {
      font-size: 1.1rem; color: var(--oxford-navy); font-weight: 800;
    }
    .info-box i {
      margin-right: 6px; color: var(--yale-blue);
    }

    /* Countries */
    .con-countries-list { display: flex; flex-wrap: wrap; gap: 10px; }
    .con-country-pill {
      display: flex; align-items: center; gap: 8px;
      padding: 10px 16px; border-radius: 12px;
      background: var(--snow-pearl); border: 1px solid var(--border-color-alt);
      font-size: 0.88rem; font-weight: 600; color: var(--oxford-navy);
    }
    .con-country-pill span.flag { font-size: 1.3rem; }

    /* Sidebar */
    .con-sidebar { position: sticky; top: 20px; }
    .sidebar-card {
      background: #fff; border: 1px solid var(--border-color-alt);
      border-radius: 16px; padding: 24px; margin-bottom: 20px;
    }
    .sidebar-card h3 {
      font-family: 'Space Grotesk', sans-serif; font-size: 1rem;
      font-weight: 700; color: var(--oxford-navy); margin-bottom: 14px;
      display: flex; align-items: center; gap: 8px;
    }
    .sidebar-row {
      display: flex; justify-content: space-between; padding: 10px 0;
      border-bottom: 1px solid var(--border-color-alt); font-size: 0.85rem;
    }
    .sidebar-row:last-child { border-bottom: none; }
    .sidebar-row span { color: var(--text-muted-alt); }
    .sidebar-row strong { color: var(--oxford-navy); font-weight: 700; }
    .sidebar-cta {
      display: block; text-align: center; background: var(--oxford-navy);
      color: #fff; text-decoration: none; padding: 14px; border-radius: 12px;
      font-weight: 700; font-size: 0.9rem; transition: all 0.25s; margin-top: 14px;
    }
    .sidebar-cta:hover { background: var(--yale-blue); box-shadow: 0 6px 20px rgba(11,36,71,0.15); }
    .sidebar-cta.secondary {
      background: transparent; color: var(--yale-blue); border: 1.5px solid var(--yale-blue); margin-top: 10px;
    }
    .sidebar-cta.secondary:hover { background: var(--yale-blue); color: #fff; }

    /* Rating big */
    .rating-big {
      display: flex; align-items: center; gap: 12px;
      padding: 16px; background: #fef9c3; border-radius: 12px; margin-bottom: 16px;
    }
    .rating-big .stars { display: flex; gap: 2px; }
    .rating-big .stars i { color: #f59e0b; font-size: 1.1rem; }
    .rating-big .rating-num {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.8rem; font-weight: 800; color: #854d0e;
    }
    .rating-big .rating-label { font-size: 0.78rem; color: #854d0e; font-weight: 600; }

    /* Other consultants */
    .other-cons-list { display: flex; flex-direction: column; gap: 8px; }
    .other-con-link {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px; border-radius: 10px;
      background: var(--snow-pearl); border: 1px solid var(--border-color-alt);
      text-decoration: none; color: var(--ink-black);
      font-size: 0.82rem; font-weight: 600; transition: all 0.2s;
    }
    .other-con-link:hover { border-color: var(--yale-blue); background: rgba(25,55,109,0.03); }
    .other-con-link img {
      width: 32px; height: 32px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-color-alt);
    }
    .other-con-link .oc-info { flex: 1; min-width: 0; }
    .other-con-link .oc-info strong {
      display: block; font-size: 0.8rem; color: var(--oxford-navy);
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .other-con-link .oc-info span { font-size: 0.68rem; color: var(--text-muted-alt); }

    @media(max-width:992px) {
      .con-grid-2 { grid-template-columns: 1fr; }
    }
    @media(max-width:768px) {
      .con-hero { padding: 36px 0 30px; }
      .con-hero-row { flex-direction: column; text-align: center; gap: 16px; }
      .con-hero-avatar { width: 70px; height: 70px; border-radius: 16px; }
      .con-hero-info h1 { font-size: 1.5rem; justify-content: center; }
      .con-hero-info .loc { justify-content: center; font-size: 0.85rem; }
      .con-hero-badges { justify-content: center; }
      .con-back { margin-bottom: 12px; font-size: 0.8rem; }
      .con-body { padding: 20px 16px 60px; }
      .con-section { padding: 18px; }
      .con-section h2 { font-size: 1rem; }
      .info-grid { grid-template-columns: 1fr; }
      .con-countries-list { gap: 8px; }
      .con-country-pill { padding: 8px 12px; font-size: 0.82rem; }
    }
    @media(max-width:480px) {
      .con-hero { padding: 28px 0 24px; }
      .con-hero-info h1 { font-size: 1.25rem; }
      .con-hero-avatar { width: 56px; height: 56px; border-radius: 12px; }
      .con-body { padding: 16px 12px 50px; }
      .con-section { padding: 14px; border-radius: 12px; }
      .info-grid { gap: 10px; }
      .info-box { padding: 12px; }
      .info-box strong { font-size: 0.95rem; }
      .con-country-pill { padding: 6px 10px; font-size: 0.75rem; border-radius: 8px; }
    }
  </style>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<section class="con-hero">
  <div class="con-hero-inner">
    <a href="/ADMISSION/study-abroad?tab=consultants" class="con-back">
      <i class="ph ph-arrow-left"></i> Back to Consultants
    </a>
    <div class="con-hero-row">
      <img src="<?= htmlspecialchars($con['logo_url'] ?: 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=150&h=150&fit=crop') ?>" alt="logo" class="con-hero-avatar">
      <div class="con-hero-info">
        <h1>
          <?= htmlspecialchars($con['consultant_name']) ?>
          <?php if ($con['verified_consultant']): ?>
            <i class="ph-fill ph-seal-check con-hero-verified" title="Verified Consultant"></i>
          <?php endif; ?>
        </h1>
        <div class="loc">
          <i class="ph ph-map-pin"></i> <?= htmlspecialchars(trim(($con['address'] ?? '') . ', ' . ($con['city'] ?? ''), ', ')) ?>
        </div>
        <div class="con-hero-badges">
          <span class="con-hero-badge"><i class="ph-fill ph-star" style="color:#f59e0b"></i> <?= number_format((float)$con['consultant_rating'], 1) ?> / 5.0</span>
          <span class="con-hero-badge"><i class="ph ph-trophy"></i> <?= htmlspecialchars((string)$con['success_rate_percent']) ?>% Success</span>
          <span class="con-hero-badge"><i class="ph ph-clock"></i> <?= htmlspecialchars((string)$con['experience_years']) ?>+ Years</span>
          <span class="con-hero-badge"><i class="ph ph-<?= $mode === 'online' ? 'monitor' : ($mode === 'offline' ? 'buildings' : 'arrows-clockwise') ?>"></i> <?= htmlspecialchars($con['consultation_mode'] ?? 'Both') ?></span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="con-body">
  <div class="con-grid-2">
    <div>

      <!-- Key Details -->
      <div class="con-section">
        <h2><i class="ph ph-chart-bar"></i> Consultant Details</h2>
        <div class="info-grid">
          <div class="info-box">
            <span>Success Rate</span>
            <strong><i class="ph ph-trophy"></i><?= number_format((float)$con['success_rate_percent'], 1) ?>%</strong>
          </div>
          <div class="info-box">
            <span>Experience</span>
            <strong><i class="ph ph-clock"></i><?= htmlspecialchars((string)$con['experience_years']) ?>+ Years</strong>
          </div>
          <div class="info-box">
            <span>Consultation Mode</span>
            <strong><i class="ph ph-<?= $mode === 'online' ? 'monitor' : ($mode === 'offline' ? 'buildings' : 'arrows-clockwise') ?>"></i><?= htmlspecialchars($con['consultation_mode'] ?? 'Both') ?></strong>
          </div>
          <div class="info-box">
            <span>Fee Range</span>
            <strong><i class="ph ph-wallet"></i><?= htmlspecialchars($con['fee_range'] ?? 'N/A') ?></strong>
          </div>
        </div>
      </div>

      <!-- Specialization Countries -->
      <div class="con-section">
        <h2><i class="ph ph-globe"></i> Specialization Countries</h2>
        <div class="con-countries-list">
          <?php foreach ($conCountries as $cc): ?>
            <?php $flag = $countryFlags[$cc] ?? '🌍'; ?>
            <div class="con-country-pill">
              <span class="flag"><?= $flag ?></span> <?= htmlspecialchars($cc) ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Contact Info -->
      <div class="con-section">
        <h2><i class="ph ph-address-book"></i> Contact Information</h2>
        <div class="info-grid">
          <div class="info-box">
            <span>Email</span>
            <strong><i class="ph ph-envelope"></i><?= htmlspecialchars($con['contact_email'] ?? 'N/A') ?></strong>
          </div>
          <div class="info-box">
            <span>Phone</span>
            <strong><i class="ph ph-phone"></i><?= htmlspecialchars($con['contact_phone'] ?? 'N/A') ?></strong>
          </div>
          <div class="info-box">
            <span>Address</span>
            <strong><i class="ph ph-map-pin"></i><?= htmlspecialchars($con['address'] ?? 'N/A') ?></strong>
          </div>
          <div class="info-box">
            <span>City</span>
            <strong><i class="ph ph-buildings"></i><?= htmlspecialchars($con['city'] ?? 'N/A') ?></strong>
          </div>
        </div>
      </div>

    </div>

    <!-- Sidebar -->
    <div>
      <div class="con-sidebar">

        <!-- Rating Card -->
        <div class="sidebar-card">
          <h3><i class="ph ph-star"></i> Rating & Reviews</h3>
          <div class="rating-big">
            <div>
              <div class="rating-num"><?= number_format((float)$con['consultant_rating'], 1) ?></div>
              <div class="rating-label">out of 5.0</div>
            </div>
            <div>
              <div class="stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="ph<?= $i <= round((float)$con['consultant_rating']) ? '-fill' : '' ?> ph-star"></i>
                <?php endfor; ?>
              </div>
            </div>
          </div>
          <div class="sidebar-row"><span>Success Rate</span><strong><?= number_format((float)$con['success_rate_percent'], 1) ?>%</strong></div>
          <div class="sidebar-row"><span>Experience</span><strong><?= htmlspecialchars((string)$con['experience_years']) ?>+ Years</strong></div>
          <div class="sidebar-row"><span>Verified</span><strong><?= $con['verified_consultant'] ? 'Yes' : 'No' ?></strong></div>
        </div>

        <!-- CTA -->
        <div class="sidebar-card">
          <h3><i class="ph ph-headset"></i> Get in Touch</h3>
          <p style="font-size:0.85rem; color:var(--text-muted-alt); margin-bottom:12px; line-height:1.5;">
            Contact <?= htmlspecialchars($con['consultant_name']) ?> for personalized study abroad guidance.
          </p>
          <?php if (!empty($con['contact_phone'])): ?>
            <a href="tel:<?= htmlspecialchars($con['contact_phone']) ?>" class="sidebar-cta">
              <i class="ph-fill ph-phone"></i> Call Now
            </a>
          <?php endif; ?>
          <?php if (!empty($con['contact_email'])): ?>
            <a href="mailto:<?= htmlspecialchars($con['contact_email']) ?>" class="sidebar-cta secondary">
              <i class="ph-fill ph-envelope"></i> Send Email
            </a>
          <?php endif; ?>
          <a href="/ADMISSION/counselling" class="sidebar-cta secondary" style="margin-top:10px">
            <i class="ph-fill ph-headset"></i> Free Counselling
          </a>
        </div>

        <!-- Other Consultants -->
        <div class="sidebar-card">
          <h3><i class="ph ph-users-three"></i> Other Consultants</h3>
          <div class="other-cons-list">
            <?php
            $otherCons = $pdo->query("SELECT id, consultant_name, logo_url, consultant_rating, city FROM consultants WHERE id != " . (int)$con['id'] . " ORDER BY consultant_rating DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($otherCons as $oc):
            ?>
              <a href="/ADMISSION/consultant/<?= (int)$oc['id'] ?>" class="other-con-link">
                <img src="<?= htmlspecialchars($oc['logo_url'] ?: 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=80&h=80&fit=crop') ?>" alt="logo">
                <div class="oc-info">
                  <strong><?= htmlspecialchars($oc['consultant_name']) ?></strong>
                  <span><?= htmlspecialchars($oc['city'] ?? '') ?> &middot; <?= number_format((float)$oc['consultant_rating'], 1) ?>/5</span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
