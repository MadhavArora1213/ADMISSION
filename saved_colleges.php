<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    $redirectUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']);
    header("Location: $redirectUrl");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch saved colleges
$sql = "
    SELECT c.id, c.name, c.slug, c.college_type, c.ownership, c.naac_grade, c.ranking_nirf,
           c.overall_rating_avg, c.total_reviews, c.established_year, c.founded_year,
           c.is_verified, c.is_featured, c.total_students, c.campus_area_acres,
           c.ugc_approved, c.aicte_approved,
           s.name AS state_name, ci.name AS city_name,
           cm.logo_url, cm.cover_image_url,
           (SELECT MIN(cc.annual_fee) FROM college_courses cc WHERE cc.college_id = c.id AND cc.annual_fee > 0) AS min_fee,
           (SELECT MAX(cp.avg_package_lpa) FROM college_placements cp WHERE cp.college_id = c.id) AS avg_package,
           (SELECT COUNT(*) FROM college_courses cc WHERE cc.college_id = c.id) AS total_courses
    FROM saved_colleges sc
    JOIN colleges c ON c.id = sc.college_id
    LEFT JOIN states s ON c.state_id = s.id
    LEFT JOIN cities ci ON c.city_id = ci.id
    LEFT JOIN college_media cm ON cm.college_id = c.id AND (cm.image_type IS NULL OR cm.image_type = 'cover')
    WHERE sc.user_id = ?
    ORDER BY sc.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Saved Colleges — My Wishlist';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/favicon.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - AdmissionSeason</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/college-pages.css?v=<?= time() ?>">
  <style>
    .cl-stats-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:24px;position:relative;z-index:1}
    .cl-stat{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
      border-radius:12px;padding:16px 20px;backdrop-filter:blur(10px);text-align:center}
    .cl-stat-val{font-size:1.5rem;font-weight:800;color:#fff;font-family:'Plus Jakarta Sans',sans-serif}
    .cl-stat-lbl{font-size:.75rem;color:rgba(255,255,255,.7);margin-top:2px;text-transform:uppercase;letter-spacing:.5px}
    .clc-featured-badge{
      position:absolute;top:10px;right:10px;
      background:linear-gradient(135deg,#19376D,#0F172A);
      color:#fff;font-size:.65rem;font-weight:700;
      padding:3px 9px;border-radius:6px;text-transform:uppercase;letter-spacing:.5px;
    }
    @media(min-width:769px){
      .college-list-card {
        padding-right: 160px !important;
      }
    }
    @media(max-width:768px){
      .cl-stats-bar{grid-template-columns:repeat(2,1fr)}
      .college-grid-list > div {
        padding-bottom: 50px !important;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(11,36,71,0.05);
        border: 1px solid rgba(15,23,42,0.1);
      }
      .college-list-card {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
      }
      .college-grid-list button {
        bottom: 12px !important;
        right: 12px !important;
      }
    }
    @media(max-width:480px){.cl-stats-bar{grid-template-columns:1fr 1fr}}
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ── Hero Header ───────────────────────────────────────────────── -->
<div class="shiksha-header" style="background: linear-gradient(135deg, #0b2447 0%, #19376d 100%);">
  <div class="container">
    <div class="shiksha-breadcrumb">
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/index.php" style="color:rgba(255,255,255,0.7)">Home</a>
      <i class="ph ph-caret-right" style="color:rgba(255,255,255,0.4)"></i>
      <span style="color:#fff">Saved Colleges</span>
    </div>
    <h1 class="shiksha-title" style="color:#fff"><i class="ph-fill ph-heart" style="color:#e11d48; margin-right: 8px;"></i> My Saved Colleges</h1>
    <p class="college-list-sub" style="color:rgba(255,255,255,0.8)">You have bookmarked <strong><?= count($colleges) ?></strong> colleges in your wishlist.</p>
  </div>
</div>

<!-- ── Main Content ──────────────────────────────────────────────── -->
<div class="container shiksha-main-wrapper" style="margin-top: 32px; min-height: 50vh;">
  <div class="shiksha-layout" style="grid-template-columns: 1fr;">

    <!-- Content -->
    <main class="shiksha-content college-list-main" style="width: 100%;">

      <!-- College cards -->
      <?php if (empty($colleges)): ?>
        <div class="shiksha-empty" style="text-align:center; padding: 80px 24px; background:#fff; border-radius:16px; border:1px solid rgba(15,23,42,0.06)">
          <i class="ph ph-heart" style="font-size:4rem;color:rgba(225,29,72,0.15);display:block;margin-bottom:16px"></i>
          <h3 style="font-size:1.35rem;font-weight:800;color:#0b2447;margin-bottom:8px;">Your Wishlist is Empty</h3>
          <p style="color:rgba(15,23,42,0.5); font-size:.9rem; max-width:400px; margin:0 auto 24px;">Explore best colleges in India, check out details, and click the save button to build your wishlist!</p>
          <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/colleges.php" style="display:inline-flex; align-items:center; gap:8px; padding:12px 28px; background:#19376D; color:#fff; font-weight:700; border-radius:10px; transition:all .2s;" onmouseover="this.style.background='#0B2447'" onmouseout="this.style.background='#19376D'">
            <i class="ph ph-buildings"></i> Browse Colleges
          </a>
        </div>
      <?php else: ?>
        <div class="college-grid-list" style="display: flex; flex-direction: column; gap: 20px;">
          <?php foreach ($colleges as $cl):
            $year = $cl['established_year'] ?? $cl['founded_year'] ?? '';
            $rating = (float)($cl['overall_rating_avg'] ?? 0);
            $ownMap = ['central'=>'Central','state'=>'State Govt','private_trust'=>'Trust','minority'=>'Minority'];
            $ownershipLabel = $ownMap[$cl['ownership'] ?? ''] ?? '';
          ?>
          <div style="position: relative;">
            <a href="<?= collegeUrl($cl['slug']) ?>" class="college-list-card" style="display: flex; position: relative;">
              <!-- Featured badge -->
              <?php if (!empty($cl['is_featured'])): ?>
              <span class="clc-featured-badge" style="position:absolute;top:12px;right:12px;z-index:2">⭐ Featured</span>
              <?php endif; ?>

              <!-- Image -->
              <div class="clc-img">
                <img src="<?= cImg($cl['cover_image_url']) ?>" alt="<?= htmlspecialchars($cl['name']) ?>" loading="lazy">
                <div class="clc-img-badges">
                  <?php if ($cl['naac_grade']): ?><span class="clc-badge">NAAC <?= htmlspecialchars($cl['naac_grade']) ?></span><?php endif; ?>
                  <?php if (!empty($cl['is_verified'])): ?><span class="clc-badge clc-badge-verified"><i class="ph-fill ph-seal-check"></i> Verified</span><?php endif; ?>
                </div>
              </div>

              <!-- Body -->
              <div class="clc-body" style="flex: 1;">
                <div class="clc-top">
                  <?php if ($cl['logo_url']): ?><img src="<?= cImg($cl['logo_url']) ?>" class="clc-logo" alt=""><?php endif; ?>
                  <div style="flex:1;min-width:0">
                    <h3><?= htmlspecialchars($cl['name']) ?></h3>
                    <div class="clc-meta">
                      <?php if ($cl['city_name'] || $cl['state_name']): ?>
                      <span><i class="ph ph-map-pin"></i><?= htmlspecialchars(trim(($cl['city_name'] ?? '') . ($cl['city_name'] && $cl['state_name'] ? ', ' : '') . ($cl['state_name'] ?? ''))) ?></span>
                      <?php endif; ?>
                      <?php if ($year): ?><span><i class="ph ph-calendar"></i>Est. <?= htmlspecialchars((string)$year) ?></span><?php endif; ?>
                      <?php if (!empty($cl['total_courses'])): ?><span><i class="ph ph-book-open"></i><?= (int)$cl['total_courses'] ?> Courses</span><?php endif; ?>
                    </div>
                    <div class="clc-chips">
                      <span class="clc-chip"><?= htmlspecialchars(collegeTypeLabel($cl['college_type'], $cl['ownership'])) ?></span>
                      <?php if ($ownershipLabel): ?><span class="clc-chip"><?= $ownershipLabel ?></span><?php endif; ?>
                      <?php if (!empty($cl['ugc_approved'])): ?><span class="clc-chip chip-green">UGC ✓</span><?php endif; ?>
                      <?php if (!empty($cl['aicte_approved'])): ?><span class="clc-chip chip-green">AICTE ✓</span><?php endif; ?>
                      <?php if (!empty($cl['ranking_nirf'])): ?><span class="clc-chip chip-orange">NIRF #<?= (int)$cl['ranking_nirf'] ?></span><?php endif; ?>
                    </div>
                  </div>
                </div>
                <!-- Stats row -->
                <div class="clc-stats">
                  <div>
                    <strong><?= $rating > 0 ? number_format($rating, 1) . '/5 ★' : '—' ?></strong>
                    <span>Rating</span>
                  </div>
                  <div>
                    <strong><?= formatFee(isset($cl['min_fee']) ? (float)$cl['min_fee'] : null) ?></strong>
                    <span>Min Fee/Yr</span>
                  </div>
                  <div>
                    <strong><?= formatLpa(isset($cl['avg_package']) ? (float)$cl['avg_package'] : null) ?></strong>
                    <span>Avg Package</span>
                  </div>
                  <?php if (!empty($cl['total_courses'])): ?>
                  <div>
                    <strong><?= (int)$cl['total_courses'] ?></strong>
                    <span>Courses</span>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </a>
            
            <!-- Remove from saved action -->
            <button onclick="removeSavedCollege('<?= htmlspecialchars($cl['id']) ?>', this.closest('.college-grid-list > div'))" style="position: absolute; bottom: 20px; right: 20px; z-index: 5; background: rgba(225,29,72,0.06); border: 1px solid rgba(225,29,72,0.15); color: #e11d48; padding: 8px 16px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#e11d48'; this.style.color='#fff'" onmouseout="this.style.background='rgba(225,29,72,0.06)'; this.style.color='#e11d48'">
              <i class="ph ph-trash"></i> Remove
            </button>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/js/main.js"></script>
<script>
function removeSavedCollege(collegeId, cardElement) {
  if (!confirm('Are you sure you want to remove this college from your wishlist?')) return;
  
  fetch('<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/api/save_college.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      college_id: collegeId,
      action: 'unsave'
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.ok) {
      cardElement.style.opacity = '0';
      cardElement.style.transform = 'translateY(10px)';
      cardElement.style.transition = 'all 0.3s ease';
      setTimeout(() => {
        cardElement.remove();
        if (document.querySelectorAll('.college-grid-list > div').length === 0) {
          location.reload(); // Reload to show empty state
        }
      }, 300);
    } else {
      alert(data.msg || 'Failed to remove college.');
    }
  })
  .catch(err => {
    console.error(err);
  });
}
</script>
</body>
</html>
