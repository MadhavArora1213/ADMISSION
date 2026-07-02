<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists('cImg')) {
    function cImg(?string $url = ''): string {
        if (!$url) return 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80';
        if (str_starts_with($url, 'http') || str_starts_with($url, '//')) return $url;
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return $base . '/' . ltrim($url, '/');
    }
}

$updateId = trim($_GET['id'] ?? '');
if (empty($updateId)) { header('Location: news.php'); exit; }

// Support both slug and ID lookup
$stmt = $pdo->prepare("SELECT u.*, c.name AS college_name, c.slug AS college_slug, cm.logo_url, cm.cover_image_url FROM college_updates u LEFT JOIN colleges c ON c.id = u.college_id LEFT JOIN college_media cm ON cm.college_id = c.id WHERE (u.id = ? OR u.slug = ?) AND u.status = 'published' LIMIT 1");
$stmt->execute([$updateId, $updateId]);
$update = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$update) { header('HTTP/1.0 404 Not Found'); header('Location: news.php'); exit; }

// Use actual ID for operations
$updateId = $update['id'];
$updateSlug = $update['slug'] ?? '';

// SEO-friendly URL
$sharePath = !empty($updateSlug) ? 'news/' . $updateSlug : 'college_update_detail.php?id=' . $updateId;

$collegeName = $update['college_name'] ?? 'College';
$collegeSlug = $update['college_slug'] ?? '';
$typeLabel = ucwords(str_replace('_', ' ', $update['update_type'] ?? 'news'));
$eventDate = !empty($update['event_date']) ? date('F d, Y', strtotime($update['event_date'])) : '';

$shareUrl   = 'https://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/' . $sharePath;
$shareImage = cImg($update['cover_image_url'] ?? $update['logo_url'] ?? '');
$shareDesc  = mb_strimwidth(strip_tags($update['description'] ?? $update['title']), 0, 160, '...');
$siteName   = 'AdmissionSeason';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($update['title']) ?> - <?= htmlspecialchars($collegeName) ?> | <?= $siteName ?></title>
  <meta name="description" content="<?= htmlspecialchars($shareDesc) ?>">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="<?= $shareUrl ?>">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?= $shareUrl ?>">
  <meta property="og:title" content="<?= htmlspecialchars($update['title']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($shareDesc) ?>">
  <meta property="og:image" content="<?= $shareImage ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:site_name" content="<?= $siteName ?>">
  <meta property="og:locale" content="en_IN">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= $shareUrl ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($update['title']) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($shareDesc) ?>">
  <meta name="twitter:image" content="<?= $shareImage ?>">
  <meta name="twitter:site" content="@AdmissionSeason">

  <!-- Structured Data -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $update['title'],
    'description' => $shareDesc,
    'datePublished' => $update['event_date'] ?? date('Y-m-d'),
    'author' => ['@type' => 'Organization', 'name' => $siteName],
    'publisher' => [
      '@type' => 'Organization',
      'name' => $siteName,
      'logo' => ['@type' => 'ImageObject', 'url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=200&q=80']
    ],
    'mainEntityOfPage' => $shareUrl,
    'image' => $shareImage,
    'articleSection' => $typeLabel,
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
  <style>
    .upd-wrap{max-width:780px;margin:0 auto;padding:30px 20px 60px}
    .upd-breadcrumb{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:28px;font-size:.85rem;color:rgba(15,23,42,.5)}
    .upd-breadcrumb a{color:var(--cp-blue,#2563eb);text-decoration:none;font-weight:500}
    .upd-breadcrumb a:hover{text-decoration:underline}
    .upd-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:600;background:rgba(11,36,71,.06);color:#0B2447;margin-bottom:14px}
    .upd-title{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.65rem;font-weight:800;color:#0F172A;line-height:1.35;margin-bottom:12px}
    .upd-meta{display:flex;flex-wrap:wrap;gap:16px;align-items:center;font-size:.85rem;color:rgba(15,23,42,.5);margin-bottom:24px;padding-bottom:20px;border-bottom:1.5px solid rgba(15,23,42,.08)}
    .upd-meta span{display:inline-flex;align-items:center;gap:5px}
    .upd-college-link{display:inline-flex;align-items:center;gap:10px;padding:14px 18px;background:#fff;border:1.5px solid rgba(15,23,42,.08);border-radius:12px;margin-bottom:28px;text-decoration:none;transition:all .2s}
    .upd-college-link:hover{border-color:rgba(37,99,235,.25);box-shadow:0 4px 16px rgba(0,0,0,.06)}
    .upd-college-logo{width:44px;height:44px;border-radius:10px;object-fit:cover;background:#f1f5f9}
    .upd-college-name{font-weight:700;color:#0F172A;font-size:.95rem}
    .upd-college-label{font-size:.78rem;color:rgba(15,23,42,.5)}
    .upd-body{background:#fff;border:1.5px solid rgba(15,23,42,.06);border-radius:16px;padding:32px;font-size:.95rem;line-height:1.75;color:rgba(15,23,42,.8)}
    .upd-body p{margin-bottom:16px}
    .upd-actions{display:flex;gap:10px;margin-top:28px;flex-wrap:wrap}
    .upd-actions a,.upd-actions button{
      display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:10px;
      font-size:.85rem;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:all .2s
    }
    .upd-back{background:rgba(11,36,71,.06);color:#0B2447}
    .upd-back:hover{background:rgba(11,36,71,.12)}
    .upd-primary{background:linear-gradient(135deg,#2563eb,#19376D);color:#fff}
    .upd-primary:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.3)}
    .share-row{display:flex;gap:8px;align-items:center;margin-top:20px;flex-wrap:wrap}
    .share-row span{font-size:.85rem;color:rgba(15,23,42,.5);font-weight:500}
    .share-row a{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;font-size:.8rem;font-weight:600;text-decoration:none;color:#fff;transition:all .2s}
    .share-fb{background:#1877f2}.share-tw{background:#0f1419}.share-wa{background:#25d366}.share-li{background:#0a66c2}.share-tg{background:#0088cc}
    .share-row a:hover{opacity:.85;transform:translateY(-1px)}
  </style>
</head>
<body class="bg-light">

<?php include 'includes/navbar.php'; ?>

<div class="upd-wrap">
  <!-- Breadcrumb -->
  <div class="upd-breadcrumb">
    <a href="index.php">Home</a>
    <i class="ph ph-caret-right"></i>
    <?php if ($collegeSlug): ?>
      <a href="college/<?= urlencode($collegeSlug) ?>"><?= htmlspecialchars($collegeName) ?></a>
      <i class="ph ph-caret-right"></i>
    <?php endif; ?>
    <a href="college/<?= urlencode($collegeSlug) ?>/news">News</a>
    <i class="ph ph-caret-right"></i>
    <span><?= htmlspecialchars(mb_strimwidth($update['title'], 0, 40, '...')) ?></span>
  </div>

  <!-- Badge -->
  <div class="upd-badge"><i class="ph ph-newspaper"></i> <?= $typeLabel ?></div>

  <!-- Title -->
  <h1 class="upd-title"><?= htmlspecialchars($update['title']) ?></h1>

  <!-- Meta -->
  <div class="upd-meta">
    <?php if ($eventDate): ?><span><i class="ph ph-calendar-blank"></i> <?= $eventDate ?></span><?php endif; ?>
    <span><i class="ph ph-clock"></i> <?= max(1, (int)ceil(str_word_count(strip_tags($update['description'] ?? '')) / 200)) ?> min read</span>
    <span><i class="ph ph-buildings"></i> <?= htmlspecialchars($collegeName) ?></span>
  </div>

  <!-- College link -->
  <?php if ($collegeSlug): ?>
  <a href="college/<?= urlencode($collegeSlug) ?>" class="upd-college-link">
    <img src="<?= cImg($update['logo_url'] ?? '') ?>" class="upd-college-logo" alt="">
    <div>
      <div class="upd-college-name"><?= htmlspecialchars($collegeName) ?></div>
      <div class="upd-college-label">View College Profile <i class="ph ph-arrow-right"></i></div>
    </div>
  </a>
  <?php endif; ?>

  <!-- Body -->
  <div class="upd-body">
    <?= $update['description'] ? nl2br(htmlspecialchars($update['description'])) : '<p>Details will be updated soon.</p>' ?>
  </div>

  <!-- Actions -->
  <div class="upd-actions">
    <a href="college/<?= urlencode($collegeSlug) ?>/news" class="upd-back"><i class="ph ph-arrow-left"></i> Back to News</a>
    <?php if ($collegeSlug): ?>
    <a href="college/<?= urlencode($collegeSlug) ?>" class="upd-primary"><i class="ph ph-graduation-cap"></i> View College</a>
    <?php endif; ?>
  </div>

  <!-- Share -->
  <div class="share-row">
    <span>Share:</span>
    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="share-fb" title="Share on Facebook"><i class="ph ph-facebook-logo"></i> Facebook</a>
    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($update['title'] . ' — ' . $collegeName) ?>" target="_blank" rel="noopener noreferrer" class="share-tw" title="Share on X"><i class="ph ph-x-logo"></i> X</a>
    <a href="https://api.whatsapp.com/send?text=<?= urlencode($update['title'] . ' ' . $shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="share-wa" title="Share on WhatsApp"><i class="ph ph-whatsapp-logo"></i> WhatsApp</a>
    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="share-li" title="Share on LinkedIn"><i class="ph ph-linkedin-logo"></i> LinkedIn</a>
    <a href="https://t.me/share/url?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($update['title']) ?>" target="_blank" rel="noopener noreferrer" class="share-tg" title="Share on Telegram"><i class="ph ph-telegram-logo"></i> Telegram</a>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
