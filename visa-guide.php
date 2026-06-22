<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: /ADMISSION/study-abroad?tab=visas'); exit; }

$stmt = $pdo->prepare("SELECT * FROM visa_guides WHERE LOWER(REPLACE(REPLACE(REPLACE(country, ' ', '-'), '.', ''), ',', '')) = ? OR id = ? LIMIT 1");
$normalizedSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug), '-'));
$stmt->execute([$normalizedSlug, $slug]);
$visa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$visa) { header('Location: /ADMISSION/study-abroad?tab=visas'); exit; }

$countryFlags = [
    'United States' => '🇺🇸', 'United Kingdom' => '🇬🇧', 'Canada' => '🇨🇦',
    'Australia' => '🇦🇺', 'Germany' => '🇩🇪', 'Singapore' => '🇸🇬',
    'Ireland' => '🇮🇪', 'New Zealand' => '🇳🇿',
];
$flag = $countryFlags[$visa['country']] ?? '🌍';

$docs = json_decode($visa['documents_required'] ?? '[]', true);

$metaDesc = $visa['country'] . ' student visa guide 2026: ' . $visa['visa_type'] . ' fees, processing time, documents required, post-study work visa, and expert tips.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($visa['country']) ?> Visa Guide – <?= htmlspecialchars($visa['visa_type']) ?> | AdmissionSeason</title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/ADMISSION/assets/css/style.css?v=8">
  <style>
    :root { --oxford-navy:#0B2447; --yale-blue:#19376D; --snow-pearl:#F8FAFC; --ink-black:#0F172A; --border-color-alt:#e2e8f0; --text-muted-alt:#64748b; }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Inter',sans-serif; background:var(--snow-pearl); color:var(--ink-black); }

    /* Hero */
    .visa-hero {
      background: linear-gradient(135deg, var(--yale-blue), var(--oxford-navy));
      color: #fff;
      padding: 50px 0 40px;
      position: relative;
      overflow: hidden;
    }
    .visa-hero::after {
      content: '';
      position: absolute;
      top: -40px; right: -40px;
      width: 200px; height: 200px;
      background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
      border-radius: 50%;
    }
    .visa-hero-inner {
      max-width: 1100px; margin: 0 auto; padding: 0 24px;
    }
    .visa-back {
      color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.85rem;
      font-weight: 600; display: inline-flex; align-items: center; gap: 6px;
      margin-bottom: 20px; transition: color 0.2s;
    }
    .visa-back:hover { color: #fff; }
    .visa-hero-row {
      display: flex; gap: 24px; align-items: center;
    }
    .visa-hero-flag {
      font-size: 3.5rem;
      width: 80px; height: 80px;
      display: flex; align-items: center; justify-content: center;
      background: rgba(255,255,255,0.1);
      border: 2px solid rgba(255,255,255,0.2);
      border-radius: 18px;
      flex-shrink: 0;
    }
    .visa-hero-info h1 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 2rem; font-weight: 800; margin-bottom: 4px;
    }
    .visa-hero-info .visa-type-label {
      font-size: 1rem; opacity: 0.9; margin-bottom: 12px; font-weight: 500;
    }
    .visa-hero-badges {
      display: flex; gap: 8px; flex-wrap: wrap;
    }
    .visa-hero-badge {
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.25);
      padding: 5px 14px; border-radius: 100px;
      font-size: 0.78rem; font-weight: 700;
      display: inline-flex; align-items: center; gap: 5px;
    }

    /* Body */
    .visa-body {
      max-width: 1100px; margin: 0 auto; padding: 30px 24px 80px;
    }
    .visa-grid-2 {
      display: grid; grid-template-columns: 2fr 1fr; gap: 28px;
    }

    /* Section card */
    .visa-section {
      background: #fff; border: 1px solid var(--border-color-alt);
      border-radius: 16px; padding: 24px; margin-bottom: 24px;
    }
    .visa-section h2 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.15rem; font-weight: 700; color: var(--oxford-navy);
      margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
    }

    /* Metrics row */
    .metrics-row {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 14px;
      margin-bottom: 0;
    }
    .metric-card {
      background: var(--snow-pearl); border-radius: 12px; padding: 16px;
      text-align: center; border: 1px solid var(--border-color-alt);
    }
    .metric-card span {
      display: block; font-size: 0.68rem; text-transform: uppercase;
      font-weight: 600; letter-spacing: 0.5px; color: var(--text-muted-alt); margin-bottom: 4px;
    }
    .metric-card strong {
      font-size: 1.2rem; font-weight: 800; color: var(--yale-blue);
    }
    .metric-card i {
      display: block; font-size: 1.3rem; color: var(--yale-blue); margin-bottom: 6px;
    }

    /* Documents list */
    .doc-list {
      list-style: none; padding: 0;
    }
    .doc-list li {
      display: flex; gap: 10px; align-items: flex-start;
      padding: 12px 0;
      border-bottom: 1px solid var(--border-color-alt);
      font-size: 0.9rem; color: #334155; line-height: 1.5;
    }
    .doc-list li:last-child { border-bottom: none; }
    .doc-list li i {
      color: #166534; font-size: 1.1rem; margin-top: 2px; flex-shrink: 0;
    }

    /* Tips box */
    .tips-card {
      background: linear-gradient(135deg, rgba(22,101,52,0.04), rgba(22,101,52,0.02));
      border: 1px solid rgba(22,101,52,0.15);
      border-radius: 14px; padding: 22px 24px;
    }
    .tips-card h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1rem; font-weight: 700; color: #166534;
      display: flex; align-items: center; gap: 8px; margin-bottom: 12px;
    }
    .tips-card p {
      font-size: 0.9rem; line-height: 1.7; color: #334155;
    }

    /* Sidebar */
    .visa-sidebar {
      position: sticky; top: 20px;
    }
    .sidebar-card {
      background: #fff; border: 1px solid var(--border-color-alt);
      border-radius: 16px; padding: 24px; margin-bottom: 20px;
    }
    .sidebar-card h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1rem; font-weight: 700; color: var(--oxford-navy);
      margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
    }
    .sidebar-row {
      display: flex; justify-content: space-between; padding: 10px 0;
      border-bottom: 1px solid var(--border-color-alt);
      font-size: 0.85rem;
    }
    .sidebar-row:last-child { border-bottom: none; }
    .sidebar-row span { color: var(--text-muted-alt); }
    .sidebar-row strong { color: var(--oxford-navy); font-weight: 700; }
    .sidebar-cta {
      display: block; text-align: center; background: var(--oxford-navy);
      color: #fff; text-decoration: none; padding: 14px; border-radius: 12px;
      font-weight: 700; font-size: 0.9rem; transition: all 0.25s;
      margin-top: 14px;
    }
    .sidebar-cta:hover { background: var(--yale-blue); box-shadow: 0 6px 20px rgba(11,36,71,0.15); }
    .sidebar-cta.secondary { background: transparent; color: var(--yale-blue); border: 1.5px solid var(--yale-blue); margin-top: 10px; }
    .sidebar-cta.secondary:hover { background: var(--yale-blue); color: #fff; }

    /* Process steps */
    .process-steps { list-style: none; padding: 0; counter-reset: step; }
    .process-steps li {
      counter-increment: step;
      display: flex; gap: 14px; align-items: flex-start;
      padding: 14px 0;
      border-bottom: 1px solid var(--border-color-alt);
      font-size: 0.88rem; color: #334155; line-height: 1.5;
    }
    .process-steps li:last-child { border-bottom: none; }
    .process-steps li::before {
      content: counter(step);
      display: flex; align-items: center; justify-content: center;
      width: 28px; height: 28px; border-radius: 50%;
      background: var(--oxford-navy); color: #fff;
      font-size: 0.75rem; font-weight: 700; flex-shrink: 0; margin-top: 1px;
    }

    /* Other visas */
    .other-visas-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
    }
    .other-visa-link {
      display: flex; align-items: center; gap: 8px;
      padding: 10px 12px; border-radius: 10px;
      background: var(--snow-pearl); border: 1px solid var(--border-color-alt);
      text-decoration: none; color: var(--ink-black);
      font-size: 0.82rem; font-weight: 600; transition: all 0.2s;
    }
    .other-visa-link:hover {
      border-color: var(--yale-blue); background: rgba(25,55,109,0.03);
    }
    .other-visa-link span { font-size: 1.2rem; }

    @media(max-width:992px) {
      .visa-grid-2 { grid-template-columns: 1fr; }
    }
    @media(max-width:768px) {
      .visa-hero { padding: 36px 0 30px; }
      .visa-hero-row { flex-direction: column; text-align: center; gap: 16px; }
      .visa-hero-flag { width: 64px; height: 64px; font-size: 2.5rem; border-radius: 14px; }
      .visa-hero-info h1 { font-size: 1.5rem; }
      .visa-hero-info .visa-type-label { font-size: 0.88rem; }
      .visa-hero-badges { justify-content: center; }
      .visa-back { margin-bottom: 12px; font-size: 0.8rem; }
      .visa-body { padding: 20px 16px 60px; }
      .visa-section { padding: 18px; }
      .visa-section h2 { font-size: 1rem; }
      .metrics-row { grid-template-columns: 1fr 1fr; }
      .other-visas-grid { grid-template-columns: 1fr; }
    }
    @media(max-width:480px) {
      .visa-hero { padding: 28px 0 24px; }
      .visa-hero-info h1 { font-size: 1.25rem; }
      .visa-body { padding: 16px 12px 50px; }
      .visa-section { padding: 14px; border-radius: 12px; }
      .metrics-row { grid-template-columns: 1fr 1fr; gap: 10px; }
      .metric-card { padding: 12px; }
      .metric-card strong { font-size: 1rem; }
    }
  </style>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<section class="visa-hero">
  <div class="visa-hero-inner">
    <a href="/ADMISSION/study-abroad?tab=visas" class="visa-back">
      <i class="ph ph-arrow-left"></i> Back to Visa Guides
    </a>
    <div class="visa-hero-row">
      <div class="visa-hero-flag"><?= $flag ?></div>
      <div class="visa-hero-info">
        <h1><?= htmlspecialchars($visa['country']) ?> Student Visa</h1>
        <div class="visa-type-label"><?= htmlspecialchars($visa['visa_type']) ?></div>
        <div class="visa-hero-badges">
          <span class="visa-hero-badge"><i class="ph ph-clock"></i> <?= htmlspecialchars((string)$visa['processing_time_days']) ?> Days Processing</span>
          <span class="visa-hero-badge"><i class="ph ph-currency-dollar"></i> $<?= number_format((float)$visa['visa_fee_usd'], 2) ?> Fee</span>
          <?php if ($visa['interview_required']): ?>
            <span class="visa-hero-badge"><i class="ph ph-video-camera"></i> Interview Required</span>
          <?php endif; ?>
          <span class="visa-hero-badge"><i class="ph ph-briefcase"></i> <?= htmlspecialchars((string)$visa['pswv_duration_months']) ?>mo Post-Study Work</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="visa-body">
  <div class="visa-grid-2">
    <div>

      <!-- Key Metrics -->
      <div class="visa-section">
        <h2><i class="ph ph-chart-bar"></i> Visa at a Glance</h2>
        <div class="metrics-row">
          <div class="metric-card">
            <i class="ph ph-clock"></i>
            <span>Processing Time</span>
            <strong><?= htmlspecialchars((string)$visa['processing_time_days']) ?> Days</strong>
          </div>
          <div class="metric-card">
            <i class="ph ph-currency-dollar"></i>
            <span>Visa Fee</span>
            <strong>$<?= number_format((float)$visa['visa_fee_usd'], 2) ?></strong>
          </div>
          <div class="metric-card">
            <i class="ph ph-wallet"></i>
            <span>Proof of Funds</span>
            <strong>$<?= number_format((float)$visa['proof_of_funds_usd'], 0) ?></strong>
          </div>
          <div class="metric-card">
            <i class="ph ph-briefcase"></i>
            <span>Post-Study Work</span>
            <strong><?= htmlspecialchars((string)$visa['pswv_duration_months']) ?> Months</strong>
          </div>
          <div class="metric-card">
            <i class="ph ph-hard-hat"></i>
            <span>Part-Time Work</span>
            <strong><?= htmlspecialchars((string)($visa['part_time_work_hours'] ?? 0)) ?> hrs/week</strong>
          </div>
          <div class="metric-card">
            <i class="ph ph-video-camera"></i>
            <span>Interview</span>
            <strong><?= $visa['interview_required'] ? 'Yes' : 'No' ?></strong>
          </div>
        </div>
      </div>

      <!-- Documents Required -->
      <div class="visa-section">
        <h2><i class="ph ph-check-square"></i> Documents Required</h2>
        <?php if (!empty($docs)): ?>
          <ul class="doc-list">
            <?php foreach ($docs as $doc): ?>
              <li>
                <i class="ph-bold ph-check-circle"></i>
                <span><?= htmlspecialchars($doc) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p style="color:var(--text-muted-alt); font-size:0.9rem;">Document information not available yet.</p>
        <?php endif; ?>
      </div>

      <!-- Step-by-Step Process -->
      <div class="visa-section">
        <h2><i class="ph ph-list-numbers"></i> Step-by-Step Application Process</h2>
        <ol class="process-steps">
          <li>Receive an acceptance letter or Confirmation of Enrolment (CoE) from a recognized institution in <?= htmlspecialchars($visa['country']) ?>.</li>
          <li>Gather all mandatory documents as listed above. Ensure financial documents meet the proof of funds requirement of $<?= number_format((float)$visa['proof_of_funds_usd'], 0) ?>.</li>
          <li>Complete the online visa application form on the official <?= htmlspecialchars($visa['country']) ?> immigration portal.</li>
          <li>Pay the visa application fee of $<?= number_format((float)$visa['visa_fee_usd'], 2) ?> and retain the payment receipt.</li>
          <?php if ($visa['interview_required']): ?>
          <li>Schedule and attend a visa interview at the <?= htmlspecialchars($visa['country']) ?> embassy or consulate. Carry all original documents.</li>
          <?php endif; ?>
          <li>Submit biometrics (fingerprints and photograph) at the designated application centre, if required.</li>
          <li>Wait for processing — typically <?= htmlspecialchars((string)$visa['processing_time_days']) ?> days. Track your application online.</li>
          <li>Receive your visa decision. If approved, collect your passport with the visa sticker or e-visa confirmation.</li>
        </ol>
      </div>

      <!-- Expert Tips -->
      <?php if (!empty($visa['success_tips'])): ?>
      <div class="visa-section" style="padding:0; border:none; background:transparent;">
        <div class="tips-card">
          <h3><i class="ph ph-lightbulb"></i> Expert Success Tips</h3>
          <p><?= nl2br(htmlspecialchars($visa['success_tips'])) ?></p>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- Sidebar -->
    <div>
      <div class="visa-sidebar">

        <!-- Quick Summary -->
        <div class="sidebar-card">
          <h3><i class="ph ph-info"></i> Quick Summary</h3>
          <div class="sidebar-row"><span>Country</span><strong><?= htmlspecialchars($visa['country']) ?></strong></div>
          <div class="sidebar-row"><span>Visa Type</span><strong><?= htmlspecialchars($visa['visa_type']) ?></strong></div>
          <div class="sidebar-row"><span>Processing Time</span><strong><?= htmlspecialchars((string)$visa['processing_time_days']) ?> Days</strong></div>
          <div class="sidebar-row"><span>Application Fee</span><strong>$<?= number_format((float)$visa['visa_fee_usd'], 2) ?></strong></div>
          <div class="sidebar-row"><span>Proof of Funds</span><strong>$<?= number_format((float)$visa['proof_of_funds_usd'], 0) ?></strong></div>
          <div class="sidebar-row"><span>Post-Study Work</span><strong><?= htmlspecialchars((string)$visa['pswv_duration_months']) ?> Months</strong></div>
          <div class="sidebar-row"><span>Part-Time Work</span><strong><?= htmlspecialchars((string)($visa['part_time_work_hours'] ?? 0)) ?> hrs/week</strong></div>
          <div class="sidebar-row"><span>Interview Required</span><strong><?= $visa['interview_required'] ? 'Yes' : 'No' ?></strong></div>
        </div>

        <!-- CTA -->
        <div class="sidebar-card">
          <h3><i class="ph ph-headset"></i> Need Help?</h3>
          <p style="font-size:0.85rem; color:var(--text-muted-alt); margin-bottom:12px; line-height:1.5;">Get personalized guidance from verified <?= htmlspecialchars($visa['country']) ?> visa consultants.</p>
          <a href="/ADMISSION/counselling" class="sidebar-cta">
            <i class="ph-fill ph-headset"></i> Free Visa Counselling
          </a>
          <a href="/ADMISSION/study-abroad?tab=universities" class="sidebar-cta secondary">
            <i class="ph ph-graduation-cap"></i> Explore Universities
          </a>
        </div>

        <!-- Other Visa Guides -->
        <div class="sidebar-card">
          <h3><i class="ph ph-globe"></i> Other Visa Guides</h3>
          <div class="other-visas-grid">
            <?php
            $otherVisas = $pdo->query("SELECT country FROM visa_guides WHERE id != " . (int)$visa['id'] . " ORDER BY country ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($otherVisas as $ov):
              $ovSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $ov['country']), '-'));
              $ovFlag = $countryFlags[$ov['country']] ?? '🌍';
            ?>
              <a href="/ADMISSION/visa-guide/<?= htmlspecialchars($ovSlug) ?>" class="other-visa-link">
                <span><?= $ovFlag ?></span> <?= htmlspecialchars($ov['country']) ?>
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
