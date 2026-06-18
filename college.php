<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$slug = trim($_GET['slug'] ?? '');
$tab  = trim($_GET['tab'] ?? 'info');
$tabs = collegeTabs();

if ($slug === '') {
    header('Location: colleges.php');
    exit;
}
if (!isset($tabs[$tab])) {
    $tab = 'info';
}

$college = loadCollegeBySlug($pdo, $slug);
if (!$college) {
    header('HTTP/1.0 404 Not Found');
    header('Location: colleges.php');
    exit;
}

$cid = $college['id'];
$ratings = collegeRatingBreakdown($pdo, $cid);
$overallRating = $ratings['overall'] ?? $college['overall_rating_avg'] ?? 0;
$reviewCount = (int)($ratings['count'] ?? $college['total_reviews'] ?? 0);

$courses = $placements = $cutoffs = $rankings = $gallery = $faculty = $faqs = $qnaList = $updates = $reviews = [];

try { $s=$pdo->prepare("SELECT * FROM college_courses WHERE college_id=? ORDER BY course_name ASC"); $s->execute([$cid]); $courses=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT * FROM college_placements WHERE college_id=? ORDER BY placement_year DESC"); $s->execute([$cid]); $placements=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT cc.*,e.exam_name,co.course_name FROM college_cutoffs cc LEFT JOIN exams e ON e.id=cc.exam_id LEFT JOIN courses co ON co.id=cc.course_id WHERE cc.college_id=? ORDER BY cc.cutoff_year DESC,e.exam_name ASC"); $s->execute([$cid]); $cutoffs=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT * FROM rankings WHERE college_id=? ORDER BY ranking_year DESC,rank_position ASC"); $s->execute([$cid]); $rankings=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT id,college_id,image_url,video_url,caption,image_type,document_url,document_type,logo_url,cover_image_url FROM college_media WHERE college_id=? ORDER BY sort_order ASC"); $s->execute([$cid]); $gallery=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT * FROM college_faculty WHERE college_id=? ORDER BY faculty_name ASC"); $s->execute([$cid]); $faculty=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT * FROM college_faqs WHERE college_id=? AND is_active=1 ORDER BY sort_order ASC"); $s->execute([$cid]); $faqs=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT * FROM college_qna WHERE college_id=? AND status='approved' ORDER BY created_at DESC LIMIT 50"); $s->execute([$cid]); $qnaList=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT * FROM college_updates WHERE college_id=? AND status='published' ORDER BY event_date DESC,created_at DESC LIMIT 30"); $s->execute([$cid]); $updates=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
try { $s=$pdo->prepare("SELECT r.*,u.full_name AS user_name FROM reviews r LEFT JOIN users u ON u.id=r.user_id WHERE r.college_id=? AND r.moderation_status='approved' ORDER BY r.created_at DESC LIMIT 30"); $s->execute([$cid]); $reviews=$s->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}

$qnaCount = count($faqs) + count($qnaList);
$year = $college['established_year'] ?? $college['founded_year'] ?? '';
$location = trim(($college['city_name'] ?? '') . ($college['city_name'] && $college['state_name'] ? ', ' : '') . ($college['state_name'] ?? ''));
$typeLabel = collegeTypeLabel($college['college_type'], $college['ownership']);
$highlights = jsonLines($college['highlights_json'] ?? null);
$accreditations = jsonLines($college['accreditations_json'] ?? null);

$pageTitle = $college['meta_title'] ?: ($college['name'] . ': Fees, Admission ' . date('Y') . ', Courses, Placements, Ranking');
$metaDesc = $college['meta_description'] ?: ('Explore ' . $college['name'] . ' — courses, fees, placements, cutoffs, reviews and admission details.');

$ratingItems = [
    ['key'=>'placements',     'label'=>'Placements',       'icon'=>'ph-briefcase',     'val'=>$ratings['placements'] ?? 0],
    ['key'=>'infrastructure', 'label'=>'Infrastructure',   'icon'=>'ph-buildings',     'val'=>$ratings['infrastructure'] ?? 0],
    ['key'=>'faculty',        'label'=>'Faculty & Course', 'icon'=>'ph-book-open',     'val'=>$ratings['faculty'] ?? 0],
    ['key'=>'campus_life',    'label'=>'Campus Life',      'icon'=>'ph-users-three',   'val'=>$ratings['campus_life'] ?? 0],
    ['key'=>'value_money',    'label'=>'Value for Money',  'icon'=>'ph-currency-inr',  'val'=>$ratings['value_money'] ?? 0],
];

$brochureUrl = '';
foreach ($gallery as $m) {
    if (!empty($m['document_url']) && ($m['document_type'] ?? '') === 'brochure') {
        $brochureUrl = $m['document_url']; break;
    }
}

// Icons for tabs
$tabIcons = [
    'info'=>'ph-info','courses'=>'ph-book-open','fees'=>'ph-currency-inr',
    'reviews'=>'ph-star','admissions'=>'ph-paper-plane-tilt','placements'=>'ph-briefcase',
    'cutoffs'=>'ph-scissors','rankings'=>'ph-trophy','gallery'=>'ph-images',
    'infrastructure'=>'ph-buildings','faculty'=>'ph-chalkboard-teacher',
    'compare'=>'ph-scales','qna'=>'ph-chat-circle','news'=>'ph-newspaper',
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
    /* Detail page extras */
    .cr-sub-ratings{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:12px}
    .cr-sub-item{display:flex;align-items:center;justify-content:space-between;
      font-size:.8rem;color:#475569;gap:8px}
    .cr-sub-bar{flex:1;height:4px;background:#e2e8f0;border-radius:2px;overflow:hidden}
    .cr-sub-fill{height:100%;background:linear-gradient(90deg,#2563eb,#4f46e5);border-radius:2px}
    .cr-sub-val{font-weight:700;color:#111827;min-width:24px;text-align:right}
    .overview-stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:24px}
    .overview-stat{text-align:center;padding:18px 12px;background:linear-gradient(135deg,#f8fafc,#eff6ff);
      border-radius:14px;border:1px solid rgba(37,99,235,.1)}
    .overview-stat-val{font-size:1.4rem;font-weight:800;color:#1e40af;font-family:'Plus Jakarta Sans',sans-serif}
    .overview-stat-lbl{font-size:.72rem;color:#64748b;margin-top:4px;text-transform:uppercase;letter-spacing:.4px}
    .course-level-badge{display:inline-block;padding:2px 9px;border-radius:20px;font-size:.7rem;font-weight:700;text-transform:uppercase}
    .level-ug{background:#f0fdf4;color:#16a34a}
    .level-pg{background:#eff6ff;color:#2563eb}
    .level-phd{background:#faf5ff;color:#7c3aed}
    .level-diploma{background:#fff7ed;color:#c2410c}
    .level-certificate{background:#fef3c7;color:#92400e}
    .placement-highlight{
      display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:20px;
    }
    .ph-stat{padding:20px;background:linear-gradient(135deg,#eff6ff,#e0e7ff);
      border-radius:14px;border:1px solid rgba(37,99,235,.15);text-align:center}
    .ph-stat strong{display:block;font-size:1.3rem;font-weight:800;color:#1e40af}
    .ph-stat span{font-size:.75rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px}
    .compare-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-top:20px}
    .compare-card{padding:20px;background:var(--cp-light,#f8fafc);border-radius:14px;border:1.5px solid #e2e8f0;text-align:center}
    .compare-card strong{display:block;font-size:1.35rem;font-weight:800;color:#1e40af;margin-bottom:4px}
    .compare-card span{font-size:.78rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px}
    .tab-empty-state{text-align:center;padding:48px 24px;color:#94a3b8}
    .tab-empty-state i{font-size:3rem;display:block;margin-bottom:12px}
    .tab-empty-state p{font-size:.92rem}
    .news-type-badge{font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;
      letter-spacing:.4px;background:#eff6ff;color:#2563eb;display:inline-block}
  </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ══════════════════════════════════════════════════════════════════
     HERO
     ══════════════════════════════════════════════════════════════════ -->
<div class="college-hero" style="background-image:url('<?= cImg($college['cover_image_url']) ?>')">
  <div class="college-hero-overlay"></div>
  <div class="container college-hero-inner">
    <div class="shiksha-breadcrumb college-breadcrumb">
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/index.php">Home</a>
      <i class="ph ph-caret-right"></i>
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/colleges.php">Colleges</a>
      <i class="ph ph-caret-right"></i>
      <span><?= htmlspecialchars($college['name']) ?></span>
    </div>
    <div class="college-hero-card">
      <div class="college-hero-main">
        <?php if ($college['logo_url']): ?>
        <img src="<?= cImg($college['logo_url']) ?>" class="college-hero-logo" alt="<?= htmlspecialchars($college['name']) ?>">
        <?php endif; ?>
        <div>
          <h1 class="college-hero-title"><?= htmlspecialchars($college['name']) ?></h1>
          <p class="college-hero-sub"><?= htmlspecialchars($college['name']) ?>: Fees, Admission <?= date('Y') ?>, Courses, Placements<?= !empty($college['ranking_nirf']) ? ', Ranking, Cutoff' : '' ?></p>
          <div class="college-hero-chips">
            <?php if ($location): ?><span><i class="ph ph-map-pin"></i> <?= htmlspecialchars($location) ?></span><?php endif; ?>
            <?php if ($overallRating > 0): ?>
            <span><i class="ph ph-star-fill" style="color:#fbbf24"></i> <?= number_format((float)$overallRating,1) ?> / 5</span>
            <span><?= $reviewCount ?> Reviews</span>
            <?php endif; ?>
            <?php if ($qnaCount > 0): ?><span><i class="ph ph-chat-circle"></i> Student Q&A</span><?php endif; ?>
            <span><i class="ph ph-buildings"></i> <?= htmlspecialchars($typeLabel) ?></span>
            <?php if ($year): ?><span><i class="ph ph-calendar"></i> Estd <?= htmlspecialchars((string)$year) ?></span><?php endif; ?>
            <?php if (!empty($college['is_verified'])): ?><span style="background:rgba(22,163,74,.35);border-color:rgba(22,163,74,.5)"><i class="ph-fill ph-seal-check"></i> Verified</span><?php endif; ?>
            <?php if (!empty($college['ranking_nirf'])): ?><span><i class="ph ph-trophy"></i> NIRF #<?= (int)$college['ranking_nirf'] ?></span><?php endif; ?>
            <?php if (!empty($college['naac_grade'])): ?><span>NAAC <?= htmlspecialchars($college['naac_grade']) ?></span><?php endif; ?>
            <?php if (!empty($college['ugc_approved'])): ?><span>UGC ✓</span><?php endif; ?>
            <?php if (!empty($college['aicte_approved'])): ?><span>AICTE ✓</span><?php endif; ?>
          </div>
        </div>
      </div>
      <div class="college-hero-actions">
        <button type="button" class="college-btn-outline" title="Save to wishlist" onclick="this.innerHTML='<i class=\'ph-fill ph-heart\'></i> Saved'">
          <i class="ph ph-heart"></i> Save
        </button>
        <a href="<?= collegeUrl($slug, 'compare') ?>" class="college-btn-outline">
          <i class="ph ph-scales"></i> Compare
        </a>
        <?php if ($brochureUrl): ?>
        <a href="<?= htmlspecialchars($brochureUrl) ?>" target="_blank" class="college-btn-primary">
          <i class="ph ph-download-simple"></i> Brochure
        </a>
        <?php else: ?>
        <a href="#apply" class="college-btn-primary">
          <i class="ph ph-paper-plane-tilt"></i> Apply Now
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     TABS NAV
     ══════════════════════════════════════════════════════════════════ -->
<div class="shiksha-tabs-nav college-tabs-sticky">
  <div class="container">
    <div class="shiksha-tabs college-detail-tabs">
      <?php foreach ($tabs as $key => $label): ?>
      <a href="<?= collegeUrl($slug, $key) ?>" class="<?= $tab === $key ? 'active' : '' ?>">
        <?php if (isset($tabIcons[$key])): ?><i class="ph <?= $tabIcons[$key] ?>"></i> <?php endif; ?>
        <?= htmlspecialchars($label) ?>
        <?php if ($key === 'reviews' && $reviewCount > 0): ?><span style="background:#eff6ff;color:#2563eb;padding:1px 6px;border-radius:10px;font-size:.7rem;margin-left:3px"><?= $reviewCount ?></span><?php endif; ?>
        <?php if ($key === 'qna' && $qnaCount > 0): ?><span style="background:#eff6ff;color:#2563eb;padding:1px 6px;border-radius:10px;font-size:.7rem;margin-left:3px"><?= $qnaCount ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     MAIN CONTENT
     ══════════════════════════════════════════════════════════════════ -->
<div class="container shiksha-main-wrapper college-detail-wrap">
  <div class="shiksha-layout">

    <main class="shiksha-content">
      <div class="college-tab-content">

        <!-- ── COLLEGE INFO ──────────────────────────────────────── -->
        <?php if ($tab === 'info'): ?>
          <p class="college-updated"><i class="ph ph-clock"></i> Last updated on <?= date('d M \'y') ?></p>

          <!-- Rating row -->
          <?php if (array_filter(array_column($ratingItems, 'val'))): ?>
          <div class="college-rating-row">
            <?php foreach ($ratingItems as $ri): if ((float)$ri['val'] <= 0) continue; ?>
            <div class="college-rating-pill">
              <i class="ph <?= $ri['icon'] ?>"></i>
              <div>
                <strong><?= number_format((float)$ri['val'], 1) ?></strong>
                <span><?= htmlspecialchars($ri['label']) ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Quick overview stats -->
          <div class="overview-stat-grid">
            <?php if ($year): ?><div class="overview-stat"><div class="overview-stat-val"><?= htmlspecialchars((string)$year) ?></div><div class="overview-stat-lbl">Established</div></div><?php endif; ?>
            <?php if (!empty($college['total_students'])): ?><div class="overview-stat"><div class="overview-stat-val"><?= number_format((int)$college['total_students']) ?>+</div><div class="overview-stat-lbl">Students</div></div><?php endif; ?>
            <?php if (!empty($college['total_faculty'])): ?><div class="overview-stat"><div class="overview-stat-val"><?= (int)$college['total_faculty'] ?>+</div><div class="overview-stat-lbl">Faculty</div></div><?php endif; ?>
            <?php if (!empty($college['campus_area_acres'])): ?><div class="overview-stat"><div class="overview-stat-val"><?= (float)$college['campus_area_acres'] ?></div><div class="overview-stat-lbl">Acres Campus</div></div><?php endif; ?>
            <?php if (!empty($college['ranking_nirf'])): ?><div class="overview-stat"><div class="overview-stat-val">#<?= (int)$college['ranking_nirf'] ?></div><div class="overview-stat-lbl">NIRF Rank</div></div><?php endif; ?>
            <div class="overview-stat"><div class="overview-stat-val"><?= count($courses) ?></div><div class="overview-stat-lbl">Courses</div></div>
          </div>

          <?php if (!empty($college['about_text'])): ?>
          <section class="college-section">
            <h2>About <?= htmlspecialchars($college['name']) ?></h2>
            <div class="college-prose"><?= nl2br(htmlspecialchars($college['about_text'])) ?></div>
          </section>
          <?php endif; ?>

          <?php if (!empty($highlights)): ?>
          <section class="college-section">
            <h2>College Highlights</h2>
            <ul class="college-highlight-list">
              <?php foreach ($highlights as $h): ?>
              <li><i class="ph ph-check-circle"></i> <?= htmlspecialchars(is_array($h) ? ($h['text'] ?? json_encode($h)) : (string)$h) ?></li>
              <?php endforeach; ?>
            </ul>
          </section>
          <?php endif; ?>

          <?php if (!empty($accreditations)): ?>
          <section class="college-section">
            <h2>Accreditations & Approvals</h2>
            <div class="college-tag-row">
              <?php foreach ($accreditations as $a): ?>
              <span class="college-tag"><?= htmlspecialchars(is_array($a) ? ($a['name'] ?? json_encode($a)) : (string)$a) ?></span>
              <?php endforeach; ?>
              <?php if ($college['naac_grade']): ?><span class="college-tag">NAAC <?= htmlspecialchars($college['naac_grade']) ?></span><?php endif; ?>
              <?php if (!empty($college['ugc_approved'])): ?><span class="college-tag">UGC Approved</span><?php endif; ?>
              <?php if (!empty($college['aicte_approved'])): ?><span class="college-tag">AICTE Approved</span><?php endif; ?>
            </div>
          </section>
          <?php endif; ?>

          <section class="college-section">
            <h2>Contact & Location</h2>
            <div class="college-contact-grid">
              <?php if ($college['address']): ?><p><i class="ph ph-map-pin"></i> <?= htmlspecialchars($college['address']) ?><?= $college['pincode'] ? ', ' . htmlspecialchars($college['pincode']) : '' ?></p><?php endif; ?>
              <?php if ($college['phone']): ?><p><i class="ph ph-phone"></i> <a href="tel:<?= htmlspecialchars($college['phone']) ?>"><?= htmlspecialchars($college['phone']) ?></a></p><?php endif; ?>
              <?php if ($college['email']): ?><p><i class="ph ph-envelope"></i> <a href="mailto:<?= htmlspecialchars($college['email']) ?>"><?= htmlspecialchars($college['email']) ?></a></p><?php endif; ?>
              <?php if ($college['website_url']): ?><p><i class="ph ph-globe"></i> <a href="<?= htmlspecialchars($college['website_url']) ?>" target="_blank" rel="noopener noreferrer">Official Website ↗</a></p><?php endif; ?>
            </div>
          </section>

        <!-- ── COURSES ───────────────────────────────────────────── -->
        <?php elseif ($tab === 'courses'): ?>
          <section class="college-section">
            <h2>Courses Offered <span class="college-count">(<?= count($courses) ?>)</span></h2>
            <?php if (empty($courses)): ?>
            <div class="tab-empty-state"><i class="ph ph-book-open"></i><p>No courses listed yet for this college.</p></div>
            <?php else: ?>
            <div class="college-table-wrap">
              <table class="college-data-table">
                <thead><tr><th>Course Name</th><th>Level</th><th>Duration</th><th>Seats</th><th>Annual Fee</th><th>EMI</th></tr></thead>
                <tbody>
                <?php foreach ($courses as $co):
                  $levelMap = ['ug'=>'level-ug','pg'=>'level-pg','phd'=>'level-phd','diploma'=>'level-diploma','certificate'=>'level-certificate'];
                  $lvl = strtolower($co['course_level'] ?? '');
                  $levelClass = $levelMap[$lvl] ?? 'level-ug';
                ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($co['course_name'] ?? '—') ?></strong>
                    <?php if (!empty($co['specializations'])): ?><br><small style="color:#94a3b8"><?= htmlspecialchars(is_string($co['specializations']) ? $co['specializations'] : implode(', ', json_decode($co['specializations'],true) ?: [])) ?></small><?php endif; ?>
                    <?php if (!empty($co['eligibility_criteria'])): ?><br><small style="color:#94a3b8">Eligibility: <?= htmlspecialchars($co['eligibility_criteria']) ?></small><?php endif; ?>
                  </td>
                  <td><span class="course-level-badge <?= $levelClass ?>"><?= htmlspecialchars($co['course_level'] ?? '—') ?></span></td>
                  <td><?= $co['duration_years'] ? htmlspecialchars((string)$co['duration_years']) . ' yrs' : '—' ?></td>
                  <td><?= htmlspecialchars((string)($co['seats_available'] ?? $co['seats'] ?? '—')) ?></td>
                  <td><strong style="color:#16a34a"><?= formatFee(isset($co['annual_fee']) ? (float)$co['annual_fee'] : null) ?></strong></td>
                  <td><?= !empty($co['emi_available']) ? '<span style="color:#16a34a;font-weight:700">✓ EMI</span>' : '<span style="color:#94a3b8">—</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── FEES ──────────────────────────────────────────────── -->
        <?php elseif ($tab === 'fees'): ?>
          <section class="college-section">
            <h2>Fee Structure</h2>
            <?php if (empty($courses)): ?>
            <div class="tab-empty-state"><i class="ph ph-currency-inr"></i><p>Fee details not available.</p></div>
            <?php else: ?>
            <div class="college-table-wrap">
              <table class="college-data-table">
                <thead><tr><th>Course</th><th>Annual Fee</th><th>Semester Fee</th><th>Total Fee</th><th>Application Fee</th></tr></thead>
                <tbody>
                <?php foreach ($courses as $co): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($co['course_name'] ?? '—') ?></strong></td>
                  <td><strong style="color:#16a34a"><?= formatFee(isset($co['annual_fee']) ? (float)$co['annual_fee'] : null) ?></strong></td>
                  <td><?= formatFee(isset($co['semester_fee']) ? (float)$co['semester_fee'] : null) ?></td>
                  <td><?= formatFee(isset($co['total_fee']) ? (float)$co['total_fee'] : null) ?></td>
                  <td><?= formatFee(isset($co['application_fee']) ? (float)$co['application_fee'] : null) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── REVIEWS ───────────────────────────────────────────── -->
        <?php elseif ($tab === 'reviews'): ?>
          <section class="college-section">
            <h2>Student Reviews <span class="college-count">(<?= $reviewCount ?>)</span></h2>
            <?php if (array_filter(array_column($ratingItems, 'val'))): ?>
            <div class="college-rating-row" style="margin-bottom:24px">
              <?php foreach ($ratingItems as $ri): if ((float)$ri['val'] <= 0) continue; ?>
              <div class="college-rating-pill">
                <i class="ph <?= $ri['icon'] ?>"></i>
                <div><strong><?= number_format((float)$ri['val'], 1) ?></strong><span><?= htmlspecialchars($ri['label']) ?></span></div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (empty($reviews)): ?>
            <div class="tab-empty-state"><i class="ph ph-star"></i><p>No reviews yet. Be the first to review!</p></div>
            <?php else: ?>
            <div class="college-reviews-list">
              <?php foreach ($reviews as $rev): ?>
              <article class="college-review-card">
                <div class="cr-head">
                  <strong><?= htmlspecialchars($rev['user_name'] ?? 'Student') ?></strong>
                  <span class="cr-rating"><i class="ph ph-star-fill"></i> <?= number_format((float)$rev['overall_rating'], 1) ?></span>
                  <?php if ($rev['batch_year']): ?><span class="cr-batch">Batch <?= htmlspecialchars((string)$rev['batch_year']) ?></span><?php endif; ?>
                </div>
                <?php if ($rev['review_title']): ?><h4><?= htmlspecialchars($rev['review_title']) ?></h4><?php endif; ?>
                <?php if ($rev['review_body']): ?><p><?= nl2br(htmlspecialchars($rev['review_body'])) ?></p><?php endif; ?>
                <?php if ($rev['pros']): ?><p class="cr-pros"><strong>👍 Pros:</strong> <?= htmlspecialchars($rev['pros']) ?></p><?php endif; ?>
                <?php if ($rev['cons']): ?><p class="cr-cons"><strong>👎 Cons:</strong> <?= htmlspecialchars($rev['cons']) ?></p><?php endif; ?>
              </article>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── ADMISSIONS ────────────────────────────────────────── -->
        <?php elseif ($tab === 'admissions'): ?>
          <section class="college-section">
            <h2>Admission Process</h2>
            <?php if (empty($college['admission_process'])): ?>
            <div class="tab-empty-state"><i class="ph ph-paper-plane-tilt"></i><p>Admission details coming soon.</p></div>
            <?php else: ?>
            <div class="college-prose"><?= nl2br(htmlspecialchars($college['admission_process'])) ?></div>
            <?php endif; ?>
            <div class="college-info-grid">
              <?php if ($college['accepted_exams']):
                $exams = json_decode($college['accepted_exams'], true);
                if (!is_array($exams)) $exams = array_filter(explode(',', $college['accepted_exams']));
              ?>
              <div style="grid-column:1/-1">
                <strong>Accepted Exams</strong>
                <div class="college-tag-row" style="margin-top:8px">
                  <?php foreach ($exams as $ex): ?><span class="college-tag"><?= htmlspecialchars(trim((string)$ex)) ?></span><?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>
              <?php if ($college['admission_start_date']): ?><div><strong>Application Start</strong><p><?= date('d M Y', strtotime($college['admission_start_date'])) ?></p></div><?php endif; ?>
              <?php if ($college['admission_end_date']): ?><div><strong>Application End</strong><p><?= date('d M Y', strtotime($college['admission_end_date'])) ?></p></div><?php endif; ?>
              <?php if ($college['application_mode']): ?><div><strong>Application Mode</strong><p><?= htmlspecialchars($college['application_mode']) ?></p></div><?php endif; ?>
              <?php if ($college['selection_criteria']): ?><div><strong>Selection Criteria</strong><p><?= htmlspecialchars($college['selection_criteria']) ?></p></div><?php endif; ?>
              <?php if ($college['management_quota_seats']): ?><div><strong>Management Quota</strong><p><?= (int)$college['management_quota_seats'] ?> seats</p></div><?php endif; ?>
              <?php if (!empty($college['merit_based'])): ?><div><strong>Merit Based</strong><p>Yes</p></div><?php endif; ?>
              <?php if (!empty($college['lateral_entry_available'])): ?><div><strong>Lateral Entry</strong><p>Available</p></div><?php endif; ?>
            </div>
          </section>

        <!-- ── PLACEMENTS ────────────────────────────────────────── -->
        <?php elseif ($tab === 'placements'): ?>
          <section class="college-section">
            <h2>Placement Statistics</h2>
            <?php if (empty($placements)): ?>
            <div class="tab-empty-state"><i class="ph ph-briefcase"></i><p>Placement data not available.</p></div>
            <?php else: ?>
            <?php
            $best = array_reduce($placements, function($carry,$pl){ return ($pl['avg_package_lpa'] > ($carry['avg_package_lpa'] ?? 0)) ? $pl : $carry; }, null);
            if ($best): ?>
            <div class="placement-highlight">
              <div class="ph-stat"><strong><?= formatLpa((float)($best['avg_package_lpa'] ?? 0)) ?></strong><span>Avg Package</span></div>
              <div class="ph-stat"><strong><?= formatLpa((float)($best['highest_package_lpa'] ?? 0)) ?></strong><span>Highest Package</span></div>
              <div class="ph-stat"><strong><?= $best['placement_percentage'] ? number_format((float)$best['placement_percentage'],1).'%' : '—' ?></strong><span>Placement Rate</span></div>
              <div class="ph-stat"><strong><?= htmlspecialchars((string)($best['students_placed'] ?? '—')) ?></strong><span>Students Placed</span></div>
            </div>
            <?php endif; ?>
            <div class="college-table-wrap">
              <table class="college-data-table">
                <thead><tr><th>Year</th><th>Avg Package</th><th>Highest</th><th>Median</th><th>Placed %</th><th>Students Placed</th></tr></thead>
                <tbody>
                <?php foreach ($placements as $pl): ?>
                <tr>
                  <td><strong><?= htmlspecialchars((string)($pl['placement_year'] ?? '—')) ?></strong></td>
                  <td><?= formatLpa(isset($pl['avg_package_lpa']) ? (float)$pl['avg_package_lpa'] : null) ?></td>
                  <td><?= formatLpa(isset($pl['highest_package_lpa']) ? (float)$pl['highest_package_lpa'] : null) ?></td>
                  <td><?= formatLpa(isset($pl['median_package_lpa']) ? (float)$pl['median_package_lpa'] : null) ?></td>
                  <td><?= !empty($pl['placement_percentage']) ? number_format((float)$pl['placement_percentage'],1).'%' : '—' ?></td>
                  <td><?= htmlspecialchars((string)($pl['students_placed'] ?? '—')) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php foreach ($placements as $pl): if (empty($pl['top_recruiters'])) continue;
              $rec = json_decode($pl['top_recruiters'], true);
              if (!is_array($rec)) $rec = array_filter(explode(',', $pl['top_recruiters']));
            ?>
            <div style="margin-top:20px">
              <h3>Top Recruiters (<?= htmlspecialchars((string)($pl['placement_year'] ?? '')) ?>)</h3>
              <div class="college-tag-row"><?php foreach ($rec as $r): ?><span class="college-tag"><?= htmlspecialchars((string)$r) ?></span><?php endforeach; ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </section>

        <!-- ── CUTOFFS ───────────────────────────────────────────── -->
        <?php elseif ($tab === 'cutoffs'): ?>
          <section class="college-section">
            <h2>Cut-Off Data</h2>
            <?php if (empty($cutoffs)): ?>
            <div class="tab-empty-state"><i class="ph ph-scissors"></i><p>Cutoff data not available for this college.</p></div>
            <?php else: ?>
            <div class="college-table-wrap">
              <table class="college-data-table">
                <thead><tr><th>Exam</th><th>Course</th><th>Year</th><th>Category</th><th>Round</th><th>Opening Rank</th><th>Closing Rank</th></tr></thead>
                <tbody>
                <?php foreach ($cutoffs as $cu): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($cu['exam_name'] ?? '—') ?></strong></td>
                  <td><?= htmlspecialchars($cu['course_name'] ?? '—') ?></td>
                  <td><?= htmlspecialchars((string)($cu['cutoff_year'] ?? '—')) ?></td>
                  <td><span class="college-tag" style="font-size:.72rem"><?= htmlspecialchars($cu['category'] ?? '—') ?></span></td>
                  <td><?= htmlspecialchars((string)($cu['round_number'] ?? '—')) ?></td>
                  <td><?= htmlspecialchars((string)($cu['opening_rank'] ?? '—')) ?></td>
                  <td><strong><?= htmlspecialchars((string)($cu['closing_rank'] ?? '—')) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── RANKINGS ──────────────────────────────────────────── -->
        <?php elseif ($tab === 'rankings'): ?>
          <section class="college-section">
            <h2>Rankings</h2>
            <?php if (!empty($college['ranking_nirf'])): ?>
            <p class="college-nirf-badge"><i class="ph ph-trophy"></i> NIRF Rank: <strong>#<?= (int)$college['ranking_nirf'] ?></strong></p>
            <?php endif; ?>
            <?php if (empty($rankings)): ?>
            <div class="tab-empty-state"><i class="ph ph-trophy"></i><p>Detailed ranking history not available.</p></div>
            <?php else: ?>
            <div class="college-table-wrap">
              <table class="college-data-table">
                <thead><tr><th>Ranking Body</th><th>Year</th><th>Category</th><th>Rank</th><th>Score</th></tr></thead>
                <tbody>
                <?php foreach ($rankings as $rk): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($rk['ranking_body'] ?? '—') ?></strong></td>
                  <td><?= htmlspecialchars((string)($rk['ranking_year'] ?? '—')) ?></td>
                  <td><?= htmlspecialchars($rk['category'] ?? '—') ?></td>
                  <td><span class="college-nirf-badge" style="display:inline;padding:4px 10px;font-size:.82rem">#<?= htmlspecialchars((string)($rk['rank_position'] ?? $rk['rank_band'] ?? '—')) ?></span></td>
                  <td><?= $rk['score'] ? number_format((float)$rk['score'], 2) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── GALLERY ───────────────────────────────────────────── -->
        <?php elseif ($tab === 'gallery'): ?>
          <section class="college-section">
            <h2>Photo Gallery</h2>
            <?php
            $images = [];
            foreach ($gallery as $m) {
                $imgUrl = $m['image_url'] ?? $m['cover_image_url'] ?? '';
                if ($imgUrl) $images[] = array_merge($m, ['display_url' => $imgUrl]);
            }
            if (empty($images)): ?>
            <div class="tab-empty-state"><i class="ph ph-images"></i><p>No gallery images yet.</p></div>
            <?php else: ?>
            <div class="college-gallery-grid">
              <?php foreach ($images as $img): ?>
              <a href="<?= htmlspecialchars($img['display_url']) ?>" target="_blank" class="college-gallery-item">
                <img src="<?= cImg($img['display_url']) ?>" alt="<?= htmlspecialchars($img['caption'] ?? $college['name']) ?>" loading="lazy">
                <?php if ($img['caption']): ?><span><?= htmlspecialchars($img['caption']) ?></span><?php endif; ?>
              </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── INFRASTRUCTURE ────────────────────────────────────── -->
        <?php elseif ($tab === 'infrastructure'): ?>
          <section class="college-section">
            <h2>Infrastructure & Facilities</h2>
            <div class="college-facility-grid">
              <?php
              $facilities = [
                ['library','Library','ph-books'],['labs','Laboratories','ph-flask'],
                ['sports_facilities','Sports','ph-football'],['auditorium','Auditorium','ph-presentation'],
                ['cafeteria','Cafeteria','ph-coffee'],['wifi','Wi-Fi Campus','ph-wifi-high'],
                ['medical_facility','Medical Center','ph-first-aid'],['transport','Transport','ph-bus'],
                ['ev_charging','EV Charging','ph-lightning'],['solar_power','Solar Power','ph-sun'],
              ];
              foreach ($facilities as $f):
                if (empty($college[$f[0]])) continue;
              ?>
              <div class="college-facility-item"><i class="ph <?= $f[2] ?>"></i><span><?= $f[1] ?></span></div>
              <?php endforeach; ?>
            </div>
            <?php if ($college['total_students'] || $college['total_faculty'] || $college['campus_area_acres']): ?>
            <h3 style="margin-top:28px">Campus Statistics</h3>
            <div class="college-info-grid">
              <?php if ($college['total_students']): ?><div><strong>Total Students</strong><p><?= number_format((int)$college['total_students']) ?></p></div><?php endif; ?>
              <?php if ($college['total_faculty']): ?><div><strong>Total Faculty</strong><p><?= (int)$college['total_faculty'] ?></p></div><?php endif; ?>
              <?php if ($college['campus_area_acres']): ?><div><strong>Campus Area</strong><p><?= (float)$college['campus_area_acres'] ?> Acres</p></div><?php endif; ?>
            </div>
            <?php
            $sportsList = jsonLines($college['sports_facilities'] ?? '');
            $labsList   = jsonLines($college['labs'] ?? '');
            if (!empty($sportsList)): ?>
            <h3 style="margin-top:24px">Sports Facilities</h3>
            <div class="college-tag-row"><?php foreach ($sportsList as $sp): ?><span class="college-tag"><?= htmlspecialchars((string)$sp) ?></span><?php endforeach; ?></div>
            <?php endif; ?>
            <?php if (!empty($labsList)): ?>
            <h3 style="margin-top:24px">Laboratories</h3>
            <div class="college-tag-row"><?php foreach ($labsList as $lb): ?><span class="college-tag"><?= htmlspecialchars((string)$lb) ?></span><?php endforeach; ?></div>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($college['hostel_available']): ?>
            <h3 style="margin-top:28px">Hostel Details</h3>
            <div class="college-info-grid">
              <?php if ($college['hostel_type']): ?><div><strong>Hostel Type</strong><p><?= htmlspecialchars(ucfirst($college['hostel_type'])) ?></p></div><?php endif; ?>
              <?php if ($college['hostel_capacity']): ?><div><strong>Capacity</strong><p><?= number_format((int)$college['hostel_capacity']) ?></p></div><?php endif; ?>
              <?php if ($college['hostel_fee_annual']): ?><div><strong>Annual Fee</strong><p><?= formatFee((float)$college['hostel_fee_annual']) ?></p></div><?php endif; ?>
              <?php if ($college['mess_available']): ?><div><strong>Mess</strong><p><?= htmlspecialchars(ucfirst($college['mess_type'] ?? 'Available')) ?></p></div><?php endif; ?>
              <?php if (isset($college['ac_available'])): ?><div><strong>AC Rooms</strong><p><?= $college['ac_available'] ? '✓ Available' : '✗ Not Available' ?></p></div><?php endif; ?>
              <?php if (isset($college['laundry_available'])): ?><div><strong>Laundry</strong><p><?= $college['laundry_available'] ? '✓ Available' : '✗ Not Available' ?></p></div><?php endif; ?>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── FACULTY ───────────────────────────────────────────── -->
        <?php elseif ($tab === 'faculty'): ?>
          <section class="college-section">
            <h2>Faculty <span class="college-count">(<?= count($faculty) ?>)</span></h2>
            <?php if (empty($faculty)): ?>
            <div class="tab-empty-state"><i class="ph ph-chalkboard-teacher"></i><p>Faculty profiles not listed yet.</p></div>
            <?php else: ?>
            <div class="college-faculty-grid">
              <?php foreach ($faculty as $fc): ?>
              <div class="college-faculty-card">
                <?php if ($fc['photo_url']): ?><img src="<?= cImg($fc['photo_url']) ?>" alt="<?= htmlspecialchars($fc['faculty_name']) ?>">
                <?php else: ?><div class="cf-avatar"><i class="ph ph-user"></i></div><?php endif; ?>
                <div>
                  <strong><?= htmlspecialchars($fc['faculty_name']) ?></strong>
                  <span><?= htmlspecialchars($fc['designation'] ?? '') ?></span>
                  <?php if ($fc['department']): ?><small><?= htmlspecialchars($fc['department']) ?></small><?php endif; ?>
                  <?php if ($fc['qualification']): ?><small><?= htmlspecialchars($fc['qualification']) ?></small><?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── COMPARE ───────────────────────────────────────────── -->
        <?php elseif ($tab === 'compare'): ?>
          <section class="college-section">
            <h2>Compare <?= htmlspecialchars($college['name']) ?></h2>
            <p style="color:#64748b;margin-bottom:20px">Compare on the basis of fees, placements, rankings, reviews and more.</p>
            <div class="compare-grid">
              <?php $mf=null; foreach($courses as $co){if($co['annual_fee']>0&&($mf===null||$co['annual_fee']<$mf))$mf=(float)$co['annual_fee'];} ?>
              <div class="compare-card"><strong><?= formatFee($mf) ?></strong><span>Min Annual Fee</span></div>
              <?php $ap=null; foreach($placements as $pl){if($pl['avg_package_lpa']>0&&($ap===null||$pl['avg_package_lpa']>$ap))$ap=(float)$pl['avg_package_lpa'];} ?>
              <div class="compare-card"><strong><?= formatLpa($ap) ?></strong><span>Avg Package</span></div>
              <div class="compare-card"><strong><?= $college['ranking_nirf'] ? '#'.(int)$college['ranking_nirf'] : '—' ?></strong><span>NIRF Rank</span></div>
              <div class="compare-card"><strong><?= $overallRating > 0 ? number_format((float)$overallRating,1).'/5 ★' : '—' ?></strong><span>Rating</span></div>
              <div class="compare-card"><strong><?= count($courses) ?></strong><span>Courses</span></div>
              <div class="compare-card"><strong><?= $college['naac_grade'] ? htmlspecialchars($college['naac_grade']) : '—' ?></strong><span>NAAC Grade</span></div>
            </div>
            <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/colleges.php" class="college-btn-primary" style="display:inline-flex;margin-top:24px"><i class="ph ph-scales"></i> Browse Colleges to Compare</a>
          </section>

        <!-- ── Q&A ───────────────────────────────────────────────── -->
        <?php elseif ($tab === 'qna'): ?>
          <section class="college-section">
            <h2>Student Q&A <span class="college-count">(<?= $qnaCount ?>)</span></h2>
            <?php if (empty($faqs) && empty($qnaList)): ?>
            <div class="tab-empty-state"><i class="ph ph-chat-circle"></i><p>No questions yet. Ask the first question!</p></div>
            <?php else: ?>
            <div class="college-faq-list">
              <?php foreach ($faqs as $fq): ?>
              <details class="college-faq-item" open>
                <summary><?= htmlspecialchars($fq['question_text']) ?></summary>
                <p><?= nl2br(htmlspecialchars($fq['answer_text'] ?? '')) ?></p>
              </details>
              <?php endforeach; ?>
              <?php foreach ($qnaList as $qn): ?>
              <details class="college-faq-item">
                <summary><?= htmlspecialchars($qn['question_text']) ?></summary>
                <p><?= nl2br(htmlspecialchars($qn['answer_text'] ?? 'Awaiting answer from expert.')) ?></p>
              </details>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <!-- ── NEWS ─────────────────────────────────────────────── -->
        <?php elseif ($tab === 'news'): ?>
          <section class="college-section">
            <h2>News & Updates</h2>
            <?php if (empty($updates)): ?>
            <div class="tab-empty-state"><i class="ph ph-newspaper"></i><p>No updates for this college yet.</p></div>
            <?php else: ?>
            <div class="college-news-list">
              <?php foreach ($updates as $up): ?>
              <article class="college-news-item">
                <div class="cn-meta">
                  <span class="news-type-badge"><?= htmlspecialchars(ucwords(str_replace('_',' ',$up['update_type']??'news'))) ?></span>
                  <?php if ($up['event_date']): ?><span><i class="ph ph-calendar"></i> <?= date('d M Y', strtotime($up['event_date'])) ?></span><?php endif; ?>
                </div>
                <h4><?= htmlspecialchars($up['title']) ?></h4>
                <?php if ($up['description']): ?><p><?= nl2br(htmlspecialchars($up['description'])) ?></p><?php endif; ?>
                <?php if ($up['action_url']): ?><a href="<?= htmlspecialchars($up['action_url']) ?>" target="_blank" rel="noopener">Read more <i class="ph ph-arrow-right"></i></a><?php endif; ?>
              </article>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </section>

        <?php endif; ?>

      </div><!-- /.college-tab-content -->
    </main>

    <!-- ── Sidebar ───────────────────────────────────────────────── -->
    <aside class="shiksha-sidebar">
      <!-- Notification widget -->
      <div class="shiksha-widget college-notify-widget" id="apply">
        <h4 class="shiksha-widget-title"><i class="ph ph-bell-ringing"></i> <?= htmlspecialchars($college['name']) ?> Alerts</h4>
        <?php if (!empty($updates)): ?>
        <ul class="college-notify-list">
          <?php foreach (array_slice($updates, 0, 4) as $up): ?>
          <li><a href="<?= collegeUrl($slug, 'news') ?>"><?= htmlspecialchars($up['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p>Get admission alerts, exam dates and cutoff updates directly in your inbox.</p>
        <?php endif; ?>
        <a href="<?= collegeUrl($slug, 'admissions') ?>" class="college-btn-primary college-widget-btn">
          <i class="ph ph-paper-plane-tilt"></i> Apply Now
        </a>
      </div>

      <!-- Rating widget -->
      <?php if ($overallRating > 0): ?>
      <div class="shiksha-widget">
        <h4 class="shiksha-widget-title">⭐ Overall Rating</h4>
        <div style="text-align:center;padding:10px 0">
          <div style="font-size:2.5rem;font-weight:800;color:#1e40af;font-family:'Plus Jakarta Sans',sans-serif"><?= number_format((float)$overallRating, 1) ?>/5</div>
          <div style="color:#fbbf24;font-size:1.2rem;margin:4px 0">★★★★<?= $overallRating >= 4.5 ? '★' : '☆' ?></div>
          <div style="font-size:.8rem;color:#94a3b8">Based on <?= $reviewCount ?> reviews</div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Popular courses -->
      <?php if (!empty($courses)): ?>
      <div class="shiksha-widget">
        <h4 class="shiksha-widget-title">📚 Popular Courses</h4>
        <ul class="shiksha-widget-list">
          <?php foreach (array_slice($courses, 0, 6) as $co): ?>
          <li><a href="<?= collegeUrl($slug, 'courses') ?>"><?= htmlspecialchars($co['course_name'] ?? '') ?><span><?= formatFee(isset($co['annual_fee']) ? (float)$co['annual_fee'] : null) ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <!-- Quick links -->
      <div class="shiksha-widget">
        <h4 class="shiksha-widget-title">🔗 Quick Links</h4>
        <ul class="shiksha-widget-list">
          <li><a href="<?= collegeUrl($slug, 'fees') ?>">Fee Structure</a></li>
          <li><a href="<?= collegeUrl($slug, 'placements') ?>">Placements</a></li>
          <li><a href="<?= collegeUrl($slug, 'cutoffs') ?>">Cut-Offs</a></li>
          <li><a href="<?= collegeUrl($slug, 'rankings') ?>">Rankings</a></li>
          <li><a href="<?= collegeUrl($slug, 'gallery') ?>">Gallery</a></li>
          <li><a href="<?= collegeUrl($slug, 'reviews') ?>">Student Reviews</a></li>
        </ul>
      </div>
    </aside>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/js/main.js"></script>
<script>
// Smooth tab scroll on mobile
document.querySelectorAll('.college-detail-tabs a.active').forEach(el => {
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
});
</script>
</body>
</html>
