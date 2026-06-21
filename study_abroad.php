<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch all study abroad data
try {
    $universities = $pdo->query("SELECT * FROM foreign_universities ORDER BY qs_rank ASC")->fetchAll(PDO::FETCH_ASSOC);
    $visas = $pdo->query("SELECT * FROM visa_guides ORDER BY country ASC")->fetchAll(PDO::FETCH_ASSOC);
    $consultants = $pdo->query("SELECT * FROM consultants ORDER BY consultant_rating DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $universities = [];
    $visas = [];
    $consultants = [];
}

// Get unique countries in our database to populate filters
$countries = array_unique(array_column($universities, 'country'));

$activeTab = $_GET['tab'] ?? 'universities';
if (!in_array($activeTab, ['universities', 'visas', 'consultants'], true)) {
    $activeTab = 'universities';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Study Abroad Portal – Top Universities, Visas & Consultants 2026 | AdmissionSeason</title>
  <meta name="description" content="Explore world-class universities in US, UK, Canada, Australia, and Germany. View visa requirements, fees, and consult top verify overseas advisors.">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=8">
  
  <style>
    :root {
      --oxford-navy: #0B2447;
      --yale-blue: #19376D;
      --snow-pearl: #F8FAFC;
      --ink-black: #0F172A;
      --border-color-alt: #e2e8f0;
      --text-muted-alt: #64748b;
      --accent-glow: rgba(25, 55, 109, 0.08);
      --card-bg: #ffffff;
    }

    body {
      background-color: var(--snow-pearl);
      color: var(--ink-black);
      font-family: 'Inter', sans-serif;
    }

    /* Hero Banner */
    .abroad-hero {
      background: linear-gradient(135deg, var(--oxford-navy) 0%, var(--yale-blue) 100%);
      color: #fff;
      padding: 80px 0;
      position: relative;
      overflow: hidden;
      text-align: center;
    }

    .abroad-hero::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at 80% 20%, rgba(255,255,255,0.08) 0%, transparent 60%);
      pointer-events: none;
    }

    .abroad-hero h1 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 3rem;
      font-weight: 800;
      margin-bottom: 16px;
      line-height: 1.1;
      letter-spacing: -0.5px;
    }

    .abroad-hero p {
      font-size: 1.15rem;
      max-width: 600px;
      margin: 0 auto 32px auto;
      opacity: 0.9;
    }

    /* Quick Country Badges */
    .country-filter-wrap {
      display: flex;
      justify-content: center;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }

    .country-tab-btn {
      background: rgba(255, 255, 255, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
      padding: 10px 20px;
      border-radius: 100px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
    }

    .country-tab-btn:hover, .country-tab-btn.active {
      background: #fff;
      color: var(--oxford-navy);
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transform: translateY(-2px);
    }

    /* Search & Tab Container Section */
    .abroad-portal-container {
      padding: 60px 0 100px 0;
    }

    /* Navigation Tabs */
    .portal-tabs {
      display: flex;
      justify-content: center;
      gap: 16px;
      margin-bottom: 40px;
      border-bottom: 1.5px solid var(--border-color-alt);
      padding-bottom: 12px;
    }

    .portal-tab-btn {
      background: none;
      border: none;
      color: var(--text-muted-alt);
      font-size: 1.1rem;
      font-weight: 700;
      padding: 10px 24px;
      cursor: pointer;
      position: relative;
      transition: all 0.3s;
      font-family: 'Space Grotesk', sans-serif;
    }

    .portal-tab-btn:hover {
      color: var(--yale-blue);
    }

    .portal-tab-btn.active {
      color: var(--oxford-navy);
    }

    .portal-tab-btn.active::after {
      content: '';
      position: absolute;
      bottom: -14px;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--oxford-navy);
      border-radius: 4px;
    }

    /* Universities List Grid */
    .uni-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 30px;
    }

    .uni-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color-alt);
      border-radius: 20px;
      padding: 24px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: all 0.3s ease;
      position: relative;
    }

    .uni-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 30px rgba(11, 36, 71, 0.06);
      border-color: rgba(25, 55, 109, 0.2);
    }

    .uni-badge-qs {
      position: absolute;
      top: 20px;
      right: 20px;
      background: rgba(25, 55, 109, 0.08);
      color: var(--yale-blue);
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 0.78rem;
      font-weight: 700;
    }

    .uni-header {
      display: flex;
      gap: 16px;
      align-items: center;
      margin-bottom: 20px;
    }

    .uni-logo {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      object-fit: cover;
      border: 1px solid var(--border-color-alt);
    }

    .uni-meta h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--oxford-navy);
      margin-bottom: 4px;
    }

    .uni-loc {
      font-size: 0.85rem;
      color: var(--text-muted-alt);
      display: flex;
      align-items: center;
      gap: 4px;
      font-weight: 550;
    }

    .uni-desc {
      font-size: 0.9rem;
      color: #475569;
      line-height: 1.5;
      margin-bottom: 20px;
      flex-grow: 1;
    }

    .uni-details-list {
      border-top: 1px solid var(--border-color-alt);
      border-bottom: 1px solid var(--border-color-alt);
      padding: 14px 0;
      margin-bottom: 20px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      font-size: 0.82rem;
    }

    .detail-item span {
      display: block;
      color: var(--text-muted-alt);
      font-size: 0.75rem;
      margin-bottom: 2px;
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    .detail-item strong {
      color: var(--oxford-navy);
      font-size: 0.9rem;
      font-weight: 700;
    }

    .uni-cta {
      display: block;
      text-align: center;
      background: var(--oxford-navy);
      color: #fff;
      text-decoration: none;
      padding: 12px;
      border-radius: 10px;
      font-weight: 700;
      transition: all 0.3s;
      font-size: 0.9rem;
    }

    .uni-cta:hover {
      background: var(--yale-blue);
      box-shadow: 0 4px 12px rgba(25, 55, 109, 0.2);
    }

    /* Visa Cards styling */
    .visa-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color-alt);
      border-radius: 20px;
      padding: 30px;
      margin-bottom: 30px;
      transition: all 0.3s;
    }

    .visa-card:hover {
      box-shadow: 0 10px 25px rgba(0,0,0,0.03);
    }

    .visa-head-wrap {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1.5px solid var(--border-color-alt);
      padding-bottom: 18px;
      margin-bottom: 20px;
      flex-wrap: wrap;
      gap: 10px;
    }

    .visa-country-title {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.6rem;
      font-weight: 800;
      color: var(--oxford-navy);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .visa-type-badge {
      background: rgba(25, 55, 109, 0.08);
      color: var(--yale-blue);
      padding: 6px 14px;
      border-radius: 30px;
      font-size: 0.82rem;
      font-weight: 700;
    }

    .visa-metrics-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 20px;
      margin-bottom: 24px;
    }

    .visa-metric-box {
      background: var(--snow-pearl);
      border: 1px solid var(--border-color-alt);
      border-radius: 12px;
      padding: 14px;
      text-align: center;
    }

    .visa-metric-box span {
      display: block;
      color: var(--text-muted-alt);
      font-size: 0.72rem;
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
    }

    .visa-metric-box strong {
      color: var(--yale-blue);
      font-size: 1.15rem;
      font-weight: 800;
    }

    .visa-doc-section h4 {
      font-size: 1rem;
      font-weight: 750;
      color: var(--oxford-navy);
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .visa-doc-list {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 24px;
    }

    @media (max-width: 768px) {
      .visa-doc-list {
        grid-template-columns: 1fr;
      }
    }

    .doc-item {
      display: flex;
      gap: 8px;
      align-items: center;
      font-size: 0.88rem;
      color: #475569;
    }

    .doc-item i {
      color: #166534;
      font-size: 1.05rem;
    }

    .tips-box {
      background: rgba(22, 101, 52, 0.03);
      border: 1px dashed rgba(22, 101, 52, 0.2);
      border-radius: 12px;
      padding: 16px 20px;
      font-size: 0.88rem;
      color: #166534;
      line-height: 1.5;
    }

    /* Consultants style */
    .cons-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 30px;
    }

    .cons-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color-alt);
      border-radius: 20px;
      padding: 24px;
      transition: all 0.3s;
    }

    .cons-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.04);
      border-color: rgba(25, 55, 109, 0.15);
    }

    .cons-head {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 18px;
    }

    .cons-info-title h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--oxford-navy);
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 4px;
    }

    .cons-badge-verified {
      color: var(--yale-blue);
      font-size: 1.25rem;
    }

    .cons-rating {
      background: #fef9c3;
      color: #854d0e;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.78rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .cons-meta-list {
      border-top: 1px solid var(--border-color-alt);
      padding-top: 16px;
      margin-top: 16px;
      font-size: 0.85rem;
      color: #475569;
    }

    .cons-meta-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .cons-meta-row span {
      color: var(--text-muted-alt);
    }

    .cons-countries {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 14px;
    }

    .cons-country-badge {
      background: var(--snow-pearl);
      border: 1px solid var(--border-color-alt);
      border-radius: 6px;
      padding: 4px 10px;
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--yale-blue);
    }

    /* Portal CTA block */
    .portal-cta-banner {
      background: linear-gradient(135deg, var(--yale-blue) 0%, var(--oxford-navy) 100%);
      color: #fff;
      border-radius: 24px;
      padding: 50px;
      text-align: center;
      margin-top: 60px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(11,36,71,0.15);
    }

    .portal-cta-banner h2 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 2.2rem;
      font-weight: 800;
      margin-bottom: 14px;
    }

    .portal-cta-banner p {
      font-size: 1.05rem;
      max-width: 600px;
      margin: 0 auto 30px auto;
      opacity: 0.9;
    }

    .cta-btn-alt {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #fff;
      color: var(--oxford-navy);
      padding: 14px 32px;
      border-radius: 12px;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .cta-btn-alt:hover {
      background: var(--snow-pearl);
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(255,255,255,0.2);
    }

    /* JS Controlled displays */
    .portal-section-content {
      display: none;
    }

    .portal-section-content.active {
      display: block;
    }

    /* Responsive Media Queries */
    @media (max-width: 992px) {
      .abroad-hero {
        padding: 50px 20px;
      }
      
      .abroad-hero h1 {
        font-size: 2.2rem;
      }
      
      .abroad-portal-container {
        padding: 40px 20px 80px 20px;
      }
    }

    @media (max-width: 768px) {
      .portal-tabs {
        overflow-x: auto;
        justify-content: flex-start;
        padding-bottom: 8px;
        white-space: nowrap;
        gap: 8px;
      }
      
      .portal-tab-btn {
        font-size: 1rem;
        padding: 8px 16px;
      }
      
      .uni-grid, .cons-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      
      .visa-head-wrap {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .portal-cta-banner {
        padding: 30px 20px;
      }
      
      .portal-cta-banner h2 {
        font-size: 1.65rem;
      }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Study Abroad Hero -->
<section class="abroad-hero">
  <div class="container">
    <h1>Global Study Abroad Portal 2026</h1>
    <p>Unlock world-class education. Find top-ranked universities, read official visa guides, and consult verified study abroad counselors.</p>
    
    <!-- Filter Badges -->
    <div class="country-filter-wrap">
      <button class="country-tab-btn active" onclick="filterCountry('all')">
        <i class="ph ph-globe"></i> All Countries
      </button>
      <?php foreach ($countries as $c): ?>
        <button class="country-tab-btn" onclick="filterCountry('<?= htmlspecialchars($c) ?>')">
          <i class="ph ph-map-pin"></i> <?= htmlspecialchars($c) ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Main Portal Area -->
<section class="abroad-portal-container">
  <div class="container">
    
    <!-- Portal Tabs Menu -->
    <div class="portal-tabs">
      <button class="portal-tab-btn <?= $activeTab === 'universities' ? 'active' : '' ?>" onclick="switchSection('universities', this)">
        <i class="ph ph-graduation-cap"></i> Top Universities
      </button>
      <button class="portal-tab-btn <?= $activeTab === 'visas' ? 'active' : '' ?>" onclick="switchSection('visas', this)">
        <i class="ph ph-file-text"></i> Visa Requirements
      </button>
      <button class="portal-tab-btn <?= $activeTab === 'consultants' ? 'active' : '' ?>" onclick="switchSection('consultants', this)">
        <i class="ph ph-users-three"></i> Overseas Consultants
      </button>
    </div>

    <!-- 1. UNIVERSITIES TAB -->
    <div id="section-universities" class="portal-section-content <?= $activeTab === 'universities' ? 'active' : '' ?>">
      <?php if (empty($universities)): ?>
        <div style="text-align:center; padding: 60px 0; color: var(--text-muted-alt);">
          <i class="ph ph-folder-open" style="font-size:3rem; margin-bottom:12px;"></i>
          <p>No foreign universities database found. Please seed the database first.</p>
        </div>
      <?php else: ?>
        <div class="uni-grid">
          <?php foreach ($universities as $uni): ?>
            <div class="uni-card" data-country="<?= htmlspecialchars($uni['country']) ?>">
              <div>
                <span class="uni-badge-qs">QS Rank: #<?= htmlspecialchars((string)$uni['qs_rank']) ?></span>
                <div class="uni-header">
                  <img src="<?= htmlspecialchars($uni['logo_url'] ?: 'https://images.unsplash.com/photo-1592280771190-3e2e4d571952?w=100&h=100&fit=crop') ?>" alt="logo" class="uni-logo">
                  <div class="uni-meta">
                    <h3><?= htmlspecialchars($uni['university_name']) ?></h3>
                    <div class="uni-loc"><i class="ph ph-map-pin"></i> <?= htmlspecialchars($uni['city']) ? htmlspecialchars($uni['city']) . ', ' : '' ?><?= htmlspecialchars($uni['country']) ?></div>
                  </div>
                </div>
                <p class="uni-desc"><?= htmlspecialchars(substr(strip_tags($uni['description']), 0, 140)) ?>...</p>
              </div>

              <div>
                <div class="uni-details-list">
                  <div class="detail-item">
                    <span>Intake Months</span>
                    <strong>
                      <?php 
                        $months = json_decode($uni['intake_months'] ?? '[]', true);
                        echo !empty($months) ? implode(', ', $months) : 'Sept/Jan';
                      ?>
                    </strong>
                  </div>
                  <div class="detail-item">
                    <span>Est. Tuition (Annual)</span>
                    <strong><?= $uni['tuition_usd_annual'] > 0 ? '$' . number_format($uni['tuition_usd_annual'], 0) : 'Free/Varies' ?></strong>
                  </div>
                  <div class="detail-item">
                    <span>Min IELTS</span>
                    <strong><?= $uni['min_ielts'] ?: '6.5' ?></strong>
                  </div>
                  <div class="detail-item">
                    <span>Min TOEFL / GRE</span>
                    <strong><?= $uni['min_toefl'] ?: '90' ?> / <?= $uni['min_gre'] ?: 'N/A' ?></strong>
                  </div>
                </div>
                <a href="counselling" class="uni-cta">Apply & Get Counselling</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- 2. VISA GUIDES TAB -->
    <div id="section-visas" class="portal-section-content <?= $activeTab === 'visas' ? 'active' : '' ?>">
      <?php if (empty($visas)): ?>
        <div style="text-align:center; padding: 60px 0; color: var(--text-muted-alt);">
          <i class="ph ph-folder-open" style="font-size:3rem; margin-bottom:12px;"></i>
          <p>No visa guides found in the database.</p>
        </div>
      <?php else: ?>
        <div>
          <?php foreach ($visas as $visa): ?>
            <div class="visa-card" data-country="<?= htmlspecialchars($visa['country']) ?>">
              <div class="visa-head-wrap">
                <h3 class="visa-country-title">
                  <i class="ph ph-flag"></i> <?= htmlspecialchars($visa['country']) ?> Study Visa
                </h3>
                <span class="visa-type-badge"><?= htmlspecialchars($visa['visa_type']) ?></span>
              </div>
              
              <div class="visa-metrics-grid">
                <div class="visa-metric-box">
                  <span>Processing Time</span>
                  <strong><?= htmlspecialchars((string)$visa['processing_time_days']) ?> Days</strong>
                </div>
                <div class="visa-metric-box">
                  <span>Visa Fee</span>
                  <strong>$<?= number_format($visa['visa_fee_usd'], 2) ?></strong>
                </div>
                <div class="visa-metric-box">
                  <span>Post-Study Work Visa</span>
                  <strong><?= htmlspecialchars((string)$visa['pswv_duration_months']) ?> Months</strong>
                </div>
                <div class="visa-metric-box">
                  <span>Proof of Funds Req.</span>
                  <strong>$<?= number_format($visa['proof_of_funds_usd'], 0) ?></strong>
                </div>
              </div>

              <div class="visa-doc-section">
                <h4><i class="ph ph-check-square"></i> Mandatory Documents Required</h4>
                <div class="visa-doc-list">
                  <?php 
                    $docs = json_decode($visa['documents_required'] ?? '[]', true);
                    foreach($docs as $doc):
                  ?>
                    <div class="doc-item">
                      <i class="ph-bold ph-check-circle"></i>
                      <span><?= htmlspecialchars($doc) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>

                <?php if(!empty($visa['success_tips'])): ?>
                  <div class="tips-box">
                    <strong><i class="ph ph-lightbulb"></i> Expert Success Tips:</strong><br>
                    <?= htmlspecialchars($visa['success_tips']) ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- 3. CONSULTANTS TAB -->
    <div id="section-consultants" class="portal-section-content <?= $activeTab === 'consultants' ? 'active' : '' ?>">
      <?php if (empty($consultants)): ?>
        <div style="text-align:center; padding: 60px 0; color: var(--text-muted-alt);">
          <i class="ph ph-folder-open" style="font-size:3rem; margin-bottom:12px;"></i>
          <p>No study abroad consultants registered in the database.</p>
        </div>
      <?php else: ?>
        <div class="cons-grid">
          <?php foreach ($consultants as $con): ?>
            <?php 
              $conCountries = json_decode($con['specialization_countries'] ?? '[]', true);
            ?>
            <div class="cons-card" data-countries='<?= htmlspecialchars(json_encode($conCountries)) ?>'>
              <div class="cons-head">
                <div class="cons-info-title">
                  <h3>
                    <?= htmlspecialchars($con['consultant_name']) ?>
                    <?php if($con['verified_consultant']): ?>
                      <i class="ph-fill ph-seal-check cons-badge-verified" title="Verified Consultant"></i>
                    <?php endif; ?>
                  </h3>
                  <div class="cons-rating">
                    <i class="ph-fill ph-star"></i> <?= number_format($con['consultant_rating'], 1) ?> / 5.0
                  </div>
                </div>
              </div>

              <div class="cons-meta-list">
                <div class="cons-meta-row">
                  <span>Success Rate</span>
                  <strong><?= number_format($con['success_rate_percent'], 1) ?>%</strong>
                </div>
                <div class="cons-meta-row">
                  <span>Experience</span>
                  <strong><?= htmlspecialchars((string)$con['experience_years']) ?>+ Years</strong>
                </div>
                <div class="cons-meta-row">
                  <span>Fees Range</span>
                  <strong><?= htmlspecialchars($con['fee_range']) ?></strong>
                </div>
                <div class="cons-meta-row">
                  <span>Email</span>
                  <strong><?= htmlspecialchars($con['contact_email']) ?></strong>
                </div>
                <div class="cons-meta-row">
                  <span>Phone</span>
                  <strong><?= htmlspecialchars($con['contact_phone']) ?></strong>
                </div>
                <div class="cons-meta-row">
                  <span>Address</span>
                  <strong style="text-align:right; font-size:0.78rem; max-width:65%;"><?= htmlspecialchars($con['address'] . ', ' . $con['city']) ?></strong>
                </div>
              </div>

              <div class="cons-countries">
                <?php foreach($conCountries as $cc): ?>
                  <span class="cons-country-badge"><?= htmlspecialchars($cc) ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Prominent CTA Section -->
    <div class="portal-cta-banner">
      <h2>Confused About Choosing Your Country or Course?</h2>
      <p>Speak to our senior educational advisor today. We will evaluate your profile and create a personalized study abroad roadmap for you, free of cost.</p>
      <a href="counselling" class="cta-btn-alt">
        <i class="ph-fill ph-headset"></i> Get Free Abroad Counseling Now
      </a>
    </div>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
  // Tab Switching Functionality
  function switchSection(sectionId, btn) {
    // Hide all section content
    const sections = document.querySelectorAll('.portal-section-content');
    sections.forEach(sec => sec.classList.remove('active'));

    // Remove active state from all tab buttons
    const buttons = document.querySelectorAll('.portal-tab-btn');
    buttons.forEach(b => b.classList.remove('active'));

    // Show selected section and activate current button
    document.getElementById('section-' + sectionId).classList.add('active');
    btn.classList.add('active');
  }

  // Country Filtering Functionality
  function filterCountry(countryName) {
    // Update active country tab styling
    const btns = document.querySelectorAll('.country-tab-btn');
    btns.forEach(b => {
      if (b.getAttribute('onclick').includes("'" + countryName + "'")) {
        b.classList.add('active');
      } else {
        b.classList.remove('active');
      }
    });

    // 1. Filter Universities Card Grid
    const uniCards = document.querySelectorAll('.uni-card');
    uniCards.forEach(card => {
      const cardCountry = card.getAttribute('data-country');
      if (countryName === 'all' || cardCountry === countryName) {
        card.style.display = 'flex';
      } else {
        card.style.display = 'none';
      }
    });

    // 2. Filter Visa Cards
    const visaCards = document.querySelectorAll('.visa-card');
    visaCards.forEach(card => {
      const cardCountry = card.getAttribute('data-country');
      if (countryName === 'all' || cardCountry === countryName) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });

    // 3. Filter Consultants Cards (based on specialization JSON array)
    const consCards = document.querySelectorAll('.cons-card');
    consCards.forEach(card => {
      const countriesList = JSON.parse(card.getAttribute('data-countries') || '[]');
      if (countryName === 'all' || countriesList.includes(countryName)) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  }
</script>

</body>
</html>
