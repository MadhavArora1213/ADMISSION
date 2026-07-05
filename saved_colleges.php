<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';
require_once __DIR__ . '/includes/school_helpers.php';

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

// Tab filter
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
if (!in_array($activeTab, ['all', 'colleges', 'schools'])) $activeTab = 'all';

// Fetch saved colleges
$collegeSql = "
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
$collegeStmt = $pdo->prepare($collegeSql);
$collegeStmt->execute([$userId]);
$colleges = $collegeStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch saved schools
$schoolSql = "
    SELECT s.id, s.name, s.slug, s.school_type, s.board_affiliation, s.board_state_name,
           s.overall_rating_avg, s.total_reviews, s.established_year,
           s.is_verified, s.is_featured, s.total_students, s.campus_area_acres,
           st.name AS state_name, ci.name AS city_name,
           sm.logo_url, sm.cover_image_url
    FROM saved_schools ss
    JOIN schools s ON s.id = ss.school_id
    LEFT JOIN states st ON s.state_id = st.id
    LEFT JOIN cities ci ON s.city_id = ci.id
    LEFT JOIN school_media sm ON sm.school_id = s.id AND sm.image_type IS NULL
    WHERE ss.user_id = ?
    ORDER BY ss.created_at DESC
";
$schoolStmt = $pdo->prepare($schoolSql);
$schoolStmt->execute([$userId]);
$schools = $schoolStmt->fetchAll(PDO::FETCH_ASSOC);

$totalSaved = count($colleges) + count($schools);

$pageTitle = 'My Saved Colleges & Schools — Wishlist';

$siteBase = defined('BASE_URL') ? BASE_URL : '/ADMISSION';
$canonicalUrl = $siteBase . '/saved_colleges.php';
$metaDesc = 'View and manage your saved colleges wishlist. Compare shortlisted colleges, track application status and get personalized recommendations.';
$metaKeywords = 'saved colleges, my wishlist, college wishlist, shortlisted colleges, saved colleges list, compare saved colleges, AdmissionSeason';
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
  <meta name="robots" content="noindex, nofollow">
  <link rel="canonical" href="<?= $canonicalUrl ?>">
  <meta name="author" content="AdmissionSeason">
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
    <h1 class="shiksha-title" style="color:#fff"><i class="ph-fill ph-heart" style="color:#e11d48; margin-right: 8px;"></i> My Saved</h1>
    <p class="college-list-sub" style="color:rgba(255,255,255,0.8)">You have bookmarked <strong><?= count($colleges) ?></strong> colleges and <strong><?= count($schools) ?></strong> schools in your wishlist.</p>
  </div>
</div>

<!-- Filter Tabs -->
<div style="background:#fff;border-bottom:1px solid rgba(15,23,42,0.08);">
  <div class="container" style="padding-top:12px;padding-bottom:12px;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a href="?tab=all" style="padding:8px 20px;border-radius:100px;font-size:.85rem;font-weight:600;text-decoration:none;border:1.5px solid <?= $activeTab === 'all' ? 'transparent' : 'rgba(15,23,42,0.08)' ?>;color:<?= $activeTab === 'all' ? '#fff' : 'rgba(15,23,42,0.45)' ?>;background:<?= $activeTab === 'all' ? 'linear-gradient(135deg,#19376D,#19376D)' : 'transparent' ?>;transition:all .2s;<?= $activeTab === 'all' ? 'box-shadow:0 4px 12px rgba(37,99,235,.3);' : '' ?>">All (<?= $totalSaved ?>)</a>
      <a href="?tab=colleges" style="padding:8px 20px;border-radius:100px;font-size:.85rem;font-weight:600;text-decoration:none;border:1.5px solid <?= $activeTab === 'colleges' ? 'transparent' : 'rgba(15,23,42,0.08)' ?>;color:<?= $activeTab === 'colleges' ? '#fff' : 'rgba(15,23,42,0.45)' ?>;background:<?= $activeTab === 'colleges' ? 'linear-gradient(135deg,#19376D,#19376D)' : 'transparent' ?>;transition:all .2s;<?= $activeTab === 'colleges' ? 'box-shadow:0 4px 12px rgba(37,99,235,.3);' : '' ?>">Colleges (<?= count($colleges) ?>)</a>
      <a href="?tab=schools" style="padding:8px 20px;border-radius:100px;font-size:.85rem;font-weight:600;text-decoration:none;border:1.5px solid <?= $activeTab === 'schools' ? 'transparent' : 'rgba(15,23,42,0.08)' ?>;color:<?= $activeTab === 'schools' ? '#fff' : 'rgba(15,23,42,0.45)' ?>;background:<?= $activeTab === 'schools' ? 'linear-gradient(135deg,#19376D,#19376D)' : 'transparent' ?>;transition:all .2s;<?= $activeTab === 'schools' ? 'box-shadow:0 4px 12px rgba(37,99,235,.3);' : '' ?>">Schools (<?= count($schools) ?>)</a>
    </div>
  </div>
</div>

<!-- ── Main Content ──────────────────────────────────────────────── -->
<div class="container shiksha-main-wrapper" style="margin-top: 32px; min-height: 50vh;">
  <div class="shiksha-layout" style="grid-template-columns: 1fr;">

    <!-- Content -->
    <main class="shiksha-content college-list-main" style="width: 100%;">

      <?php if (empty($colleges) && empty($schools)): ?>
        <div class="shiksha-empty" style="text-align:center; padding: 80px 24px; background:#fff; border-radius:16px; border:1px solid rgba(15,23,42,0.06)">
          <i class="ph ph-heart" style="font-size:4rem;color:rgba(225,29,72,0.15);display:block;margin-bottom:16px"></i>
          <h3 style="font-size:1.35rem;font-weight:800;color:#0b2447;margin-bottom:8px;">Your Wishlist is Empty</h3>
          <p style="color:rgba(15,23,42,0.5); font-size:.9rem; max-width:400px; margin:0 auto 24px;">Explore best colleges and schools in India, and click the save button to build your wishlist!</p>
          <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
            <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/colleges.php" style="display:inline-flex; align-items:center; gap:8px; padding:12px 28px; background:#19376D; color:#fff; font-weight:700; border-radius:10px; transition:all .2s;" onmouseover="this.style.background='#0B2447'" onmouseout="this.style.background='#19376D'">
              <i class="ph ph-buildings"></i> Browse Colleges
            </a>
            <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/schools.php" style="display:inline-flex; align-items:center; gap:8px; padding:12px 28px; background:#fff; color:#19376D; font-weight:700; border-radius:10px; border:1.5px solid #19376D; transition:all .2s;" onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background='#fff'">
              <i class="ph ph-graduation-cap"></i> Browse Schools
            </a>
          </div>
        </div>

      <?php else: ?>
        <div class="college-grid-list" style="display: flex; flex-direction: column; gap: 20px;">

          <!-- ── COLLEGE CARDS ── -->
          <?php if (($activeTab === 'all' || $activeTab === 'colleges') && !empty($colleges)): ?>
            <?php if ($activeTab === 'all' && !empty($schools)): ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
              <h3 style="font-size:1.1rem;font-weight:700;color:#0B2447;margin:0"><i class="ph ph-buildings" style="margin-right:6px"></i> Saved Colleges</h3>
              <span style="font-size:.78rem;color:rgba(15,23,42,0.4);background:rgba(11,36,71,0.04);padding:3px 10px;border-radius:20px;font-weight:600"><?= count($colleges) ?></span>
            </div>
            <?php endif; ?>
            <?php foreach ($colleges as $cl):
              $year = $cl['established_year'] ?? $cl['founded_year'] ?? '';
              $rating = (float)($cl['overall_rating_avg'] ?? 0);
              $ownMap = ['central'=>'Central','state'=>'State Govt','private_trust'=>'Trust','minority'=>'Minority'];
              $ownershipLabel = $ownMap[$cl['ownership'] ?? ''] ?? '';
            ?>
            <div style="position: relative;" class="saved-card-wrap" data-type="college">
              <a href="<?= collegeUrl($cl['slug']) ?>" class="college-list-card" style="display: flex; position: relative;">
                <?php if (!empty($cl['is_featured'])): ?>
                <span class="clc-featured-badge" style="position:absolute;top:12px;right:12px;z-index:2">Featured</span>
                <?php endif; ?>
                <div class="clc-img">
                  <img src="<?= cImg($cl['cover_image_url']) ?>" alt="<?= htmlspecialchars($cl['name']) ?>" loading="lazy">
                  <div class="clc-img-badges">
                    <?php if ($cl['naac_grade']): ?><span class="clc-badge">NAAC <?= htmlspecialchars($cl['naac_grade']) ?></span><?php endif; ?>
                    <?php if (!empty($cl['is_verified'])): ?><span class="clc-badge clc-badge-verified"><i class="ph-fill ph-seal-check"></i> Verified</span><?php endif; ?>
                  </div>
                </div>
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
                        <?php if (!empty($cl['ranking_nirf'])): ?><span class="clc-chip chip-orange">NIRF #<?= (int)$cl['ranking_nirf'] ?></span><?php endif; ?>
                      </div>
                    </div>
                  </div>
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
              <button onclick="removeSavedItem('college', '<?= htmlspecialchars($cl['id']) ?>', this.closest('.saved-card-wrap'))" style="position: absolute; bottom: 20px; right: 20px; z-index: 5; background: rgba(225,29,72,0.06); border: 1px solid rgba(225,29,72,0.15); color: #e11d48; padding: 8px 16px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#e11d48'; this.style.color='#fff'" onmouseout="this.style.background='rgba(225,29,72,0.06)'; this.style.color='#e11d48'">
                <i class="ph ph-trash"></i> Remove
              </button>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <!-- ── SCHOOL CARDS ── -->
          <?php if (($activeTab === 'all' || $activeTab === 'schools') && !empty($schools)): ?>
            <?php if ($activeTab === 'all' && !empty($colleges)): ?>
            <div style="display:flex;align-items:center;gap:10px;margin-top:12px;margin-bottom:4px">
              <h3 style="font-size:1.1rem;font-weight:700;color:#0B2447;margin:0"><i class="ph ph-graduation-cap" style="margin-right:6px"></i> Saved Schools</h3>
              <span style="font-size:.78rem;color:rgba(15,23,42,0.4);background:rgba(11,36,71,0.04);padding:3px 10px;border-radius:20px;font-weight:600"><?= count($schools) ?></span>
            </div>
            <?php endif; ?>
            <?php foreach ($schools as $sch):
              $rating = (float)($sch['overall_rating_avg'] ?? 0);
              $boardLabel = schoolBoardLabel($sch['board_affiliation']);
              if ($sch['board_affiliation'] === 'State' && !empty($sch['board_state_name'])) {
                  $boardLabel = $sch['board_state_name'];
              }
            ?>
            <div style="position: relative;" class="saved-card-wrap" data-type="school">
              <a href="<?= schoolUrl($sch['slug']) ?>" class="college-list-card" style="display: flex; position: relative;">
                <?php if (!empty($sch['is_featured'])): ?>
                <span class="clc-featured-badge" style="position:absolute;top:12px;right:12px;z-index:2">Featured</span>
                <?php endif; ?>
                <div class="clc-img">
                  <img src="<?= cImg($sch['cover_image_url']) ?>" alt="<?= htmlspecialchars($sch['name']) ?>" loading="lazy">
                  <div class="clc-img-badges">
                    <?php if ($sch['board_affiliation']): ?><span class="clc-badge"><?= htmlspecialchars($boardLabel) ?></span><?php endif; ?>
                    <?php if (!empty($sch['is_verified'])): ?><span class="clc-badge clc-badge-verified"><i class="ph-fill ph-seal-check"></i> Verified</span><?php endif; ?>
                  </div>
                </div>
                <div class="clc-body" style="flex: 1;">
                  <div class="clc-top">
                    <?php if ($sch['logo_url']): ?><img src="<?= cImg($sch['logo_url']) ?>" class="clc-logo" alt=""><?php endif; ?>
                    <div style="flex:1;min-width:0">
                      <h3><?= htmlspecialchars($sch['name']) ?></h3>
                      <div class="clc-meta">
                        <?php if ($sch['city_name'] || $sch['state_name']): ?>
                        <span><i class="ph ph-map-pin"></i><?= htmlspecialchars(trim(($sch['city_name'] ?? '') . ($sch['city_name'] && $sch['state_name'] ? ', ' : '') . ($sch['state_name'] ?? ''))) ?></span>
                        <?php endif; ?>
                        <?php if ($sch['established_year']): ?><span><i class="ph ph-calendar"></i>Est. <?= htmlspecialchars((string)$sch['established_year']) ?></span><?php endif; ?>
                        <?php if (!empty($sch['total_students'])): ?><span><i class="ph ph-users"></i><?= number_format((int)$sch['total_students']) ?> Students</span><?php endif; ?>
                      </div>
                      <div class="clc-chips">
                        <span class="clc-chip"><?= htmlspecialchars(schoolTypeLabel($sch['school_type'])) ?></span>
                        <?php if ($boardLabel): ?><span class="clc-chip"><?= htmlspecialchars($boardLabel) ?></span><?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <div class="clc-stats">
                    <div>
                      <strong><?= $rating > 0 ? number_format($rating, 1) . '/5 ★' : '—' ?></strong>
                      <span>Rating</span>
                    </div>
                    <div>
                      <strong><?= !empty($sch['total_students']) ? number_format((int)$sch['total_students']) : '—' ?></strong>
                      <span>Students</span>
                    </div>
                    <?php if (!empty($sch['campus_area_acres'])): ?>
                    <div>
                      <strong><?= (float)$sch['campus_area_acres'] ?> acres</strong>
                      <span>Campus</span>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
              </a>
              <button onclick="removeSavedItem('school', '<?= htmlspecialchars($sch['id']) ?>', this.closest('.saved-card-wrap'))" style="position: absolute; bottom: 20px; right: 20px; z-index: 5; background: rgba(225,29,72,0.06); border: 1px solid rgba(225,29,72,0.15); color: #e11d48; padding: 8px 16px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#e11d48'; this.style.color='#fff'" onmouseout="this.style.background='rgba(225,29,72,0.06)'; this.style.color='#e11d48'">
                <i class="ph ph-trash"></i> Remove
              </button>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>

        </div>
      <?php endif; ?>
    </main>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/js/main.js"></script>
<script>
function removeSavedItem(type, itemId, cardElement) {
  var label = type === 'college' ? 'college' : 'school';
  if (!confirm('Are you sure you want to remove this ' + label + ' from your wishlist?')) return;

  var endpoint = type === 'college'
    ? '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/api/save_college.php'
    : '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/api/toggle_save_school.php';

  var payload = type === 'college'
    ? { college_id: itemId, action: 'unsave' }
    : { school_id: itemId };

  fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(payload)
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.ok) {
      cardElement.style.opacity = '0';
      cardElement.style.transform = 'translateY(10px)';
      cardElement.style.transition = 'all 0.3s ease';
      setTimeout(function() {
        cardElement.remove();
        if (document.querySelectorAll('.saved-card-wrap').length === 0) {
          location.reload();
        }
      }, 300);
    } else {
      alert(data.msg || data.message || data.error || 'Failed to remove.');
    }
  })
  .catch(function(err) {
    console.error(err);
    alert('Network error. Please try again.');
  });
}
</script>
</body>
</html>
