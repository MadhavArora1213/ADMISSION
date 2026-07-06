<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/panel_cms_2847/db.php';
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
$siteBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/favicon.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Saved Colleges & Schools - AdmissionSeason</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= $siteBase ?>/assets/css/style.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { background: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; color: #0f172a; }

    /* ── Hero ── */
    .sv-hero {
      background: linear-gradient(135deg, #0B2447 0%, #19376D 50%, #19376D 100%);
      padding: 36px 0 28px; position: relative; overflow: hidden;
    }
    .sv-hero::before {
      content: ''; position: absolute; inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .sv-hero .container { position: relative; z-index: 1; }
    .sv-breadcrumb { display: flex; align-items: center; gap: 6px; font-size: .82rem; color: rgba(255,255,255,.7); margin-bottom: 14px; }
    .sv-breadcrumb a { color: rgba(255,255,255,.85); text-decoration: none; }
    .sv-breadcrumb a:hover { color: #fff; }
    .sv-hero h1 { color: #fff; font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 800; margin: 0 0 6px; }
    .sv-hero p { color: rgba(255,255,255,.75); font-size: .9rem; margin: 0; }
    .sv-hero strong { color: #fff; }

    /* ── Tabs ── */
    .sv-tabs {
      background: #fff; border-bottom: 1px solid rgba(15,23,42,.08);
      position: sticky; top: 0; z-index: 50; box-shadow: 0 2px 8px rgba(0,0,0,.04);
    }
    .sv-tabs .container { display: flex; gap: 6px; padding-top: 10px; padding-bottom: 10px; flex-wrap: wrap; }
    .sv-tab {
      padding: 8px 20px; border-radius: 100px; font-size: .85rem; font-weight: 600;
      text-decoration: none; border: 1.5px solid rgba(15,23,42,.08);
      color: rgba(15,23,42,.45); background: transparent; transition: all .2s; white-space: nowrap;
    }
    .sv-tab:hover { border-color: #19376D; color: #19376D; }
    .sv-tab.active { background: #19376D; color: #fff; border-color: #19376D; box-shadow: 0 4px 12px rgba(25,55,109,.3); }

    /* ── Content ── */
    .sv-content { max-width: 900px; margin: 0 auto; padding: 28px 20px 60px; }

    /* ── Section Header ── */
    .sv-section-header {
      display: flex; align-items: center; gap: 10px; margin-bottom: 16px;
    }
    .sv-section-header h2 {
      font-size: 1rem; font-weight: 700; color: #0B2447; margin: 0;
      display: flex; align-items: center; gap: 8px;
    }
    .sv-section-header .sv-count {
      font-size: .75rem; color: rgba(15,23,42,.4); background: rgba(11,36,71,.05);
      padding: 2px 10px; border-radius: 20px; font-weight: 600;
    }

    /* ── Card ── */
    .sv-card {
      background: #fff; border: 1px solid rgba(15,23,42,.07); border-radius: 16px;
      overflow: hidden; margin-bottom: 16px; transition: all .25s;
      box-shadow: 0 1px 3px rgba(11,36,71,.04);
    }
    .sv-card:hover { box-shadow: 0 8px 30px rgba(11,36,71,.1); transform: translateY(-2px); }
    .sv-card-top { display: flex; gap: 0; }
    .sv-card-img {
      width: 220px; min-height: 160px; flex-shrink: 0; position: relative; overflow: hidden;
    }
    .sv-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
    .sv-card:hover .sv-card-img img { transform: scale(1.05); }
    .sv-card-img .sv-badge-row {
      position: absolute; bottom: 8px; left: 8px; display: flex; gap: 4px; flex-wrap: wrap;
    }
    .sv-badge {
      background: rgba(0,0,0,.6); color: #fff; font-size: .65rem; font-weight: 600;
      padding: 3px 8px; border-radius: 4px; backdrop-filter: blur(4px); white-space: nowrap;
    }
    .sv-badge-green { background: rgba(22,163,74,.85); }
    .sv-featured-tag {
      position: absolute; top: 10px; left: 10px; z-index: 2;
      background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff;
      font-size: .6rem; font-weight: 700; padding: 3px 8px; border-radius: 4px;
      text-transform: uppercase; letter-spacing: .5px;
    }
    .sv-card-body { flex: 1; padding: 18px 20px; display: flex; flex-direction: column; min-width: 0; }
    .sv-card-title {
      font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0 0 6px;
      display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;
    }
    .sv-card-title a { color: inherit; text-decoration: none; }
    .sv-card-title a:hover { color: #19376D; }
    .sv-card-meta {
      display: flex; flex-wrap: wrap; gap: 10px; font-size: .8rem; color: rgba(15,23,42,.5);
      margin-bottom: 10px;
    }
    .sv-card-meta i { margin-right: 3px; vertical-align: middle; font-size: .85rem; }
    .sv-card-chips { display: flex; flex-wrap: wrap; gap: 5px; margin-top: auto; }
    .sv-chip {
      font-size: .65rem; font-weight: 700; padding: 3px 8px; border-radius: 20px;
      background: rgba(11,36,71,.05); color: #19376D; text-transform: uppercase; letter-spacing: .3px;
    }

    /* ── Card Bottom (Stats + Remove) ── */
    .sv-card-bottom {
      display: flex; align-items: center; border-top: 1px solid rgba(15,23,42,.06);
      background: #fafbfc;
    }
    .sv-card-stats { display: flex; flex: 1; }
    .sv-stat {
      flex: 1; text-align: center; padding: 12px 8px;
      border-right: 1px solid rgba(15,23,42,.06);
    }
    .sv-stat:last-child { border-right: none; }
    .sv-stat strong {
      display: block; font-size: .95rem; font-weight: 700; color: #0f172a; line-height: 1.2;
    }
    .sv-stat span {
      font-size: .65rem; color: rgba(15,23,42,.4); text-transform: uppercase;
      letter-spacing: .4px; font-weight: 600;
    }
    .sv-remove-btn {
      padding: 12px 20px; background: none; border: none; border-left: 1px solid rgba(15,23,42,.06);
      color: rgba(225,29,72,.6); font-size: .8rem; font-weight: 600; cursor: pointer;
      display: flex; align-items: center; gap: 5px; transition: all .2s; white-space: nowrap;
      font-family: inherit;
    }
    .sv-remove-btn:hover { color: #e11d48; background: rgba(225,29,72,.04); }

    /* ── Empty State ── */
    .sv-empty {
      text-align: center; padding: 80px 24px; background: #fff; border-radius: 16px;
      border: 1px solid rgba(15,23,42,.06);
    }
    .sv-empty i { font-size: 3.5rem; color: rgba(225,29,72,.12); display: block; margin-bottom: 16px; }
    .sv-empty h3 { font-size: 1.3rem; font-weight: 800; color: #0B2447; margin: 0 0 8px; }
    .sv-empty p { color: rgba(15,23,42,.45); font-size: .9rem; max-width: 380px; margin: 0 auto 24px; }
    .sv-empty-btns { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
    .sv-empty-btn {
      display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px;
      border-radius: 10px; font-weight: 700; font-size: .88rem; text-decoration: none;
      transition: all .2s; font-family: inherit;
    }
    .sv-empty-btn-primary { background: #19376D; color: #fff; border: none; }
    .sv-empty-btn-primary:hover { background: #0B2447; }
    .sv-empty-btn-outline { background: #fff; color: #19376D; border: 1.5px solid #19376D; }
    .sv-empty-btn-outline:hover { background: #f0f4ff; }

    /* ── Responsive ── */
    @media(max-width:768px) {
      .sv-card-top { flex-direction: column; }
      .sv-card-img { width: 100%; height: 180px; }
      .sv-card-body { padding: 14px 16px; }
      .sv-card-title { font-size: .95rem; }
      .sv-card-meta { font-size: .75rem; gap: 8px; }
      .sv-card-stats { flex-wrap: wrap; }
      .sv-stat { min-width: calc(50% - 1px); padding: 10px 8px; }
      .sv-stat strong { font-size: .88rem; }
      .sv-remove-btn { padding: 10px 16px; font-size: .75rem; }
    }
    @media(max-width:480px) {
      .sv-hero { padding: 24px 0 20px; }
      .sv-hero h1 { font-size: 1.25rem; }
      .sv-hero p { font-size: .82rem; }
      .sv-content { padding: 20px 14px 40px; }
      .sv-card { border-radius: 12px; margin-bottom: 12px; }
      .sv-card-img { height: 150px; }
      .sv-card-body { padding: 12px 14px; }
      .sv-card-title { font-size: .9rem; }
      .sv-chip { font-size: .6rem; padding: 2px 6px; }
      .sv-stat { padding: 8px 6px; }
      .sv-stat strong { font-size: .82rem; }
      .sv-stat span { font-size: .6rem; }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Hero -->
<div class="sv-hero">
  <div class="container">
    <div class="sv-breadcrumb">
      <a href="<?= $siteBase ?>/index.php">Home</a>
      <i class="ph ph-caret-right"></i>
      <span style="color:#fff">My Saved</span>
    </div>
    <h1><i class="ph-fill ph-heart" style="color:#e11d48;margin-right:8px"></i> My Saved</h1>
    <p>You have bookmarked <strong><?= count($colleges) ?></strong> colleges and <strong><?= count($schools) ?></strong> schools.</p>
  </div>
</div>

<!-- Tabs -->
<div class="sv-tabs">
  <div class="container">
    <a href="?tab=all" class="sv-tab <?= $activeTab === 'all' ? 'active' : '' ?>">All (<?= $totalSaved ?>)</a>
    <a href="?tab=colleges" class="sv-tab <?= $activeTab === 'colleges' ? 'active' : '' ?>">Colleges (<?= count($colleges) ?>)</a>
    <a href="?tab=schools" class="sv-tab <?= $activeTab === 'schools' ? 'active' : '' ?>">Schools (<?= count($schools) ?>)</a>
  </div>
</div>

<!-- Content -->
<div class="sv-content">

  <?php if (empty($colleges) && empty($schools)): ?>
    <div class="sv-empty">
      <i class="ph ph-heart"></i>
      <h3>Your Wishlist is Empty</h3>
      <p>Explore colleges and schools, and save the ones you like to build your wishlist.</p>
      <div class="sv-empty-btns">
        <a href="<?= $siteBase ?>/colleges.php" class="sv-empty-btn sv-empty-btn-primary"><i class="ph ph-buildings"></i> Browse Colleges</a>
        <a href="<?= $siteBase ?>/schools.php" class="sv-empty-btn sv-empty-btn-outline"><i class="ph ph-graduation-cap"></i> Browse Schools</a>
      </div>
    </div>

  <?php else: ?>

    <!-- ── COLLEGE CARDS ── -->
    <?php if (($activeTab === 'all' || $activeTab === 'colleges') && !empty($colleges)): ?>
      <?php if ($activeTab === 'all'): ?>
      <div class="sv-section-header">
        <h2><i class="ph ph-buildings"></i> Saved Colleges</h2>
        <span class="sv-count"><?= count($colleges) ?></span>
      </div>
      <?php endif; ?>

      <?php foreach ($colleges as $cl):
        $year = $cl['established_year'] ?? $cl['founded_year'] ?? '';
        $rating = (float)($cl['overall_rating_avg'] ?? 0);
        $ownMap = ['central'=>'Central','state'=>'State Govt','private_trust'=>'Trust','minority'=>'Minority'];
        $ownershipLabel = $ownMap[$cl['ownership'] ?? ''] ?? '';
        $location = trim(($cl['city_name'] ?? '') . ($cl['city_name'] && $cl['state_name'] ? ', ' : '') . ($cl['state_name'] ?? ''));
      ?>
      <div class="sv-card" id="card-<?= $cl['id'] ?>">
        <div class="sv-card-top">
          <div class="sv-card-img">
            <?php if (!empty($cl['is_featured'])): ?><span class="sv-featured-tag">Featured</span><?php endif; ?>
            <img src="<?= cImg($cl['cover_image_url']) ?>" alt="<?= htmlspecialchars($cl['name']) ?>" loading="lazy">
            <div class="sv-badge-row">
              <?php if ($cl['naac_grade']): ?><span class="sv-badge">NAAC <?= htmlspecialchars($cl['naac_grade']) ?></span><?php endif; ?>
              <?php if (!empty($cl['is_verified'])): ?><span class="sv-badge sv-badge-green"><i class="ph-fill ph-seal-check"></i> Verified</span><?php endif; ?>
            </div>
          </div>
          <div class="sv-card-body">
            <h3 class="sv-card-title"><a href="<?= collegeUrl($cl['slug']) ?>"><?= htmlspecialchars($cl['name']) ?></a></h3>
            <div class="sv-card-meta">
              <?php if ($location): ?><span><i class="ph ph-map-pin"></i><?= htmlspecialchars($location) ?></span><?php endif; ?>
              <?php if ($year): ?><span><i class="ph ph-calendar"></i>Est. <?= htmlspecialchars((string)$year) ?></span><?php endif; ?>
              <?php if (!empty($cl['total_courses'])): ?><span><i class="ph ph-book-open"></i><?= (int)$cl['total_courses'] ?> Courses</span><?php endif; ?>
            </div>
            <div class="sv-card-chips">
              <span class="sv-chip"><?= htmlspecialchars(collegeTypeLabel($cl['college_type'], $cl['ownership'])) ?></span>
              <?php if ($ownershipLabel): ?><span class="sv-chip"><?= $ownershipLabel ?></span><?php endif; ?>
              <?php if (!empty($cl['ugc_approved'])): ?><span class="sv-chip">UGC</span><?php endif; ?>
              <?php if (!empty($cl['ranking_nirf'])): ?><span class="sv-chip">NIRF #<?= (int)$cl['ranking_nirf'] ?></span><?php endif; ?>
            </div>
          </div>
        </div>
        <div class="sv-card-bottom">
          <div class="sv-card-stats">
            <div class="sv-stat"><strong><?= $rating > 0 ? number_format($rating, 1) . '/5' : '—' ?></strong><span>Rating</span></div>
            <div class="sv-stat"><strong><?= formatFee(isset($cl['min_fee']) ? (float)$cl['min_fee'] : null) ?></strong><span>Fee/Yr</span></div>
            <div class="sv-stat"><strong><?= formatLpa(isset($cl['avg_package']) ? (float)$cl['avg_package'] : null) ?></strong><span>Package</span></div>
            <?php if (!empty($cl['total_courses'])): ?>
            <div class="sv-stat"><strong><?= (int)$cl['total_courses'] ?></strong><span>Courses</span></div>
            <?php endif; ?>
          </div>
          <button class="sv-remove-btn" onclick="removeSaved('college','<?= $cl['id'] ?>')"><i class="ph ph-x"></i> Remove</button>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <!-- ── SCHOOL CARDS ── -->
    <?php if (($activeTab === 'all' || $activeTab === 'schools') && !empty($schools)): ?>
      <?php if ($activeTab === 'all'): ?>
      <div class="sv-section-header" style="margin-top:28px">
        <h2><i class="ph ph-graduation-cap"></i> Saved Schools</h2>
        <span class="sv-count"><?= count($schools) ?></span>
      </div>
      <?php endif; ?>

      <?php foreach ($schools as $sch):
        $rating = (float)($sch['overall_rating_avg'] ?? 0);
        $boardLabel = schoolBoardLabel($sch['board_affiliation']);
        if ($sch['board_affiliation'] === 'State' && !empty($sch['board_state_name'])) $boardLabel = $sch['board_state_name'];
        $location = trim(($sch['city_name'] ?? '') . ($sch['city_name'] && $sch['state_name'] ? ', ' : '') . ($sch['state_name'] ?? ''));
      ?>
      <div class="sv-card" id="card-<?= $sch['id'] ?>">
        <div class="sv-card-top">
          <div class="sv-card-img">
            <?php if (!empty($sch['is_featured'])): ?><span class="sv-featured-tag">Featured</span><?php endif; ?>
            <img src="<?= cImg($sch['cover_image_url']) ?>" alt="<?= htmlspecialchars($sch['name']) ?>" loading="lazy">
            <div class="sv-badge-row">
              <?php if ($sch['board_affiliation']): ?><span class="sv-badge"><?= htmlspecialchars($boardLabel) ?></span><?php endif; ?>
              <?php if (!empty($sch['is_verified'])): ?><span class="sv-badge sv-badge-green"><i class="ph-fill ph-seal-check"></i> Verified</span><?php endif; ?>
            </div>
          </div>
          <div class="sv-card-body">
            <h3 class="sv-card-title"><a href="<?= schoolUrl($sch['slug']) ?>"><?= htmlspecialchars($sch['name']) ?></a></h3>
            <div class="sv-card-meta">
              <?php if ($location): ?><span><i class="ph ph-map-pin"></i><?= htmlspecialchars($location) ?></span><?php endif; ?>
              <?php if ($sch['established_year']): ?><span><i class="ph ph-calendar"></i>Est. <?= htmlspecialchars((string)$sch['established_year']) ?></span><?php endif; ?>
              <?php if (!empty($sch['total_students'])): ?><span><i class="ph ph-users"></i><?= number_format((int)$sch['total_students']) ?> Students</span><?php endif; ?>
            </div>
            <div class="sv-card-chips">
              <span class="sv-chip"><?= htmlspecialchars(schoolTypeLabel($sch['school_type'])) ?></span>
              <?php if ($boardLabel): ?><span class="sv-chip"><?= htmlspecialchars($boardLabel) ?></span><?php endif; ?>
            </div>
          </div>
        </div>
        <div class="sv-card-bottom">
          <div class="sv-card-stats">
            <div class="sv-stat"><strong><?= $rating > 0 ? number_format($rating, 1) . '/5' : '—' ?></strong><span>Rating</span></div>
            <div class="sv-stat"><strong><?= !empty($sch['total_students']) ? number_format((int)$sch['total_students']) : '—' ?></strong><span>Students</span></div>
            <?php if (!empty($sch['campus_area_acres'])): ?>
            <div class="sv-stat"><strong><?= (float)$sch['campus_area_acres'] ?> ac</strong><span>Campus</span></div>
            <?php endif; ?>
          </div>
          <button class="sv-remove-btn" onclick="removeSaved('school','<?= $sch['id'] ?>')"><i class="ph ph-x"></i> Remove</button>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
function removeSaved(type, id) {
  var label = type === 'college' ? 'college' : 'school';
  if (!confirm('Remove this ' + label + ' from your wishlist?')) return;

  var endpoint = type === 'college'
    ? '<?= $siteBase ?>/api/save_college.php'
    : '<?= $siteBase ?>/api/toggle_save_school.php';
  var payload = type === 'college'
    ? { college_id: id, action: 'unsave' }
    : { school_id: id };

  fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(payload)
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.ok) {
      var card = document.getElementById('card-' + id);
      if (card) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(-10px) scale(.98)';
        card.style.transition = 'all .3s ease';
        card.style.maxHeight = card.offsetHeight + 'px';
        setTimeout(function() {
          card.style.maxHeight = '0';
          card.style.marginBottom = '0';
          card.style.padding = '0';
          card.style.borderWidth = '0';
        }, 100);
        setTimeout(function() { card.remove(); checkEmpty(); }, 400);
      }
    } else {
      alert(data.msg || data.message || data.error || 'Failed to remove.');
    }
  })
  .catch(function() { alert('Network error. Please try again.'); });
}

function checkEmpty() {
  if (document.querySelectorAll('.sv-card').length === 0) location.reload();
}
</script>
</body>
</html>
