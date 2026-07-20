<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/panel_cms_2847/db.php';
require_once __DIR__ . '/includes/school_helpers.php';
require_once __DIR__ . '/includes/news_seo_helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$newsId = trim($_GET['id'] ?? '');
if (!$newsId) { header('Location: schools.php'); exit; }

$news = [];
try {
    $s = $pdo->prepare("SELECT n.*, s.name AS school_name, s.slug AS school_slug FROM school_news n LEFT JOIN schools s ON s.id = n.school_id WHERE n.id = ? AND n.status = 'published' LIMIT 1");
    $s->execute([$newsId]);
    $news = $s->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

if (!$news) { header('Location: schools.php'); exit; }

$siteBase = getBaseUrl();
$schoolSlug = $news['school_slug'];
$schoolName = $news['school_name'];
$newsTitle = $news['title'];
$pageUrl = $siteBase . '/school/' . urlencode($schoolSlug) . '/news/' . $newsId;

$imgSrc = '';
if (!empty($news['image_url'])) {
    $imgSrc = str_starts_with($news['image_url'], 'http') ? $news['image_url'] : $siteBase . '/' . ltrim($news['image_url'], '/');
}

$shareDesc = mb_strimwidth(strip_tags($news['excerpt'] ?? $news['content'] ?? ''), 0, 160, '...');
$publishDate = date('d M Y', strtotime($news['event_date'] ?? $news['created_at']));
$publishISO = date('c', strtotime($news['event_date'] ?? $news['created_at']));
$readingTime = max(1, (int)ceil(str_word_count(strip_tags($news['content'] ?? '')) / 200));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/favicon.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($newsTitle) ?> - <?= htmlspecialchars($schoolName) ?> News | AdmissionSeason</title>
  <meta name="description" content="<?= htmlspecialchars($shareDesc) ?>">
  <meta name="robots" content="index, follow, max-image-preview:large">
  <link rel="canonical" href="<?= $pageUrl ?>">
  <meta name="author" content="AdmissionSeason">
  <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($newsTitle) ?> RSS" href="<?= $siteBase ?>/news/rss">

  <?= renderGeoMetaTags($newsTitle, $news['content'] ?? '', $news['excerpt'] ?? '') ?>

  <meta property="og:type" content="article">
  <meta property="og:url" content="<?= $pageUrl ?>">
  <meta property="og:title" content="<?= htmlspecialchars($newsTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($shareDesc) ?>">
  <?php if ($imgSrc): ?>
  <meta property="og:image" content="<?= htmlspecialchars($imgSrc) ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="<?= htmlspecialchars($newsTitle) ?>">
  <?php endif; ?>
  <meta property="og:site_name" content="AdmissionSeason">
  <meta property="og:locale" content="en_IN">
  <meta property="article:section" content="<?= htmlspecialchars($schoolName) ?> News">
  <meta property="article:published_time" content="<?= $publishISO ?>">
  <meta property="article:publisher" content="https://www.facebook.com/admissionseason">

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= $pageUrl ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($newsTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($shareDesc) ?>">
  <?php if ($imgSrc): ?><meta name="twitter:image" content="<?= htmlspecialchars($imgSrc) ?>"><?php endif; ?>
  <meta name="twitter:site" content="@AdmissionSeason">

  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $pageUrl],
    'headline' => $newsTitle,
    'description' => $shareDesc,
    'datePublished' => $publishISO,
    'dateModified' => $publishISO,
    'author' => ['@type' => 'Organization', 'name' => 'AdmissionSeason'],
    'publisher' => [
      '@type' => 'Organization',
      'name' => 'AdmissionSeason',
      'logo' => ['@type' => 'ImageObject', 'url' => $siteBase . '/assets/img/logo.png', 'width' => 600, 'height' => 60]
    ],
    'image' => $imgSrc ? ['@type' => 'ImageObject', 'url' => $imgSrc, 'width' => 1200, 'height' => 630] : $siteBase . '/assets/img/logo.png',
    'url' => $pageUrl,
    'articleSection' => $schoolName . ' News',
    'inLanguage' => 'en-IN'
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $siteBase . '/'],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'Schools', 'item' => $siteBase . '/schools'],
      ['@type' => 'ListItem', 'position' => 3, 'name' => $schoolName, 'item' => $siteBase . '/school/' . urlencode($schoolSlug)],
      ['@type' => 'ListItem', 'position' => 4, 'name' => mb_strimwidth($newsTitle, 0, 50, '...')]
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= $siteBase ?>/assets/css/style.css?v=<?= time() ?>">
  <style>
    body{background:#f1f5f9;margin:0;font-family:'Plus Jakarta Sans',sans-serif;color:#0f172a}

    /* Hero */
    .sn-hero{position:relative;width:100%;overflow:hidden;border-radius:14px;margin-bottom:0}
    .sn-hero img{width:100%;display:block;min-height:280px;max-height:520px;object-fit:cover}
    .sn-hero-overlay{position:absolute;inset:0;background:linear-gradient(180deg,transparent 30%,rgba(11,36,71,0.88));pointer-events:none}
    .sn-hero-content{position:absolute;bottom:0;left:0;right:0;padding:32px 36px;color:#fff}
    .sn-hero-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:50px;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:rgba(255,255,255,.18);backdrop-filter:blur(6px);color:#fff;margin-bottom:14px}
    .sn-hero-content h1{font-size:2rem;font-weight:800;line-height:1.25;margin:0 0 14px;text-shadow:0 2px 8px rgba(0,0,0,.2)}
    .sn-hero-meta{display:flex;flex-wrap:wrap;gap:18px;font-size:.88rem;opacity:.92}
    .sn-hero-meta span{display:flex;align-items:center;gap:5px}

    /* Card */
    .sn-card{background:#fff;border-radius:14px;border:1px solid #f1f5f9;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.04)}
    .sn-body{padding:32px 36px}
    .sn-excerpt{font-size:1.1rem;color:#444;line-height:1.7;font-style:italic;margin-bottom:22px;padding-left:18px;border-left:4px solid #0B2447}
    .sn-content{font-size:1.05rem;line-height:1.85;color:#333;font-family:'Merriweather',Georgia,serif}
    .sn-content h2,.sn-content h3,.sn-content h4{font-family:'Plus Jakarta Sans',sans-serif;color:#111;margin:28px 0 14px;line-height:1.3}
    .sn-content h2{font-size:1.6rem;font-weight:700}
    .sn-content h3{font-size:1.3rem;font-weight:700}
    .sn-content p{margin-bottom:18px}
    .sn-content img{max-width:100%;border-radius:10px;margin:18px 0}
    .sn-content ul,.sn-content ol{margin:0 0 18px 24px}
    .sn-content li{margin-bottom:8px}
    .sn-content blockquote{border-left:4px solid #0B2447;margin:24px 0;padding:16px 22px;background:rgba(11,36,71,.04);border-radius:0 10px 10px 0;font-style:italic;color:#444}
    .sn-content table{width:100%;border-collapse:collapse;margin:22px 0;font-size:.95rem;border:1px solid #f1f5f9;border-radius:10px;overflow:hidden}
    .sn-content table th,.sn-content table td{border:1px solid #f1f5f9;padding:12px 16px;text-align:left}
    .sn-content table th{background:rgba(11,36,71,.05);color:#0B2447;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif}

    /* Share */
    .sn-share{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin:28px 0 0;padding-top:24px;border-top:1px solid #f1f5f9}
    .sn-share-label{font-size:.88rem;font-weight:600;color:#64748b;display:flex;align-items:center;gap:5px}
    .sn-share-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;border:none;color:#fff;text-decoration:none;font-size:.82rem;font-weight:600;transition:all .2s}
    .sn-share-btn:hover{opacity:.88;transform:translateY(-1px)}
    .sn-share-btn.s-fb{background:#1877f2}.sn-share-btn.s-tw{background:#0f172a}.sn-share-btn.s-wa{background:#25d366}.sn-share-btn.s-li{background:#0a66c2}.sn-share-btn.s-copy{background:#64748b;cursor:pointer}

    /* Back */
    .sn-back{display:inline-flex;align-items:center;gap:6px;margin-top:24px;padding:10px 22px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;color:#0B2447;text-decoration:none;font-weight:600;font-size:.88rem;transition:all .2s;box-shadow:0 1px 4px rgba(0,0,0,.04)}
    .sn-back:hover{background:#0B2447;color:#fff;border-color:#0B2447}

    /* CTA */
    .sn-cta{background:linear-gradient(135deg,rgba(11,36,71,.06),rgba(11,36,71,.03));border:1px solid rgba(79,70,229,.15);border-radius:14px;padding:24px 28px;margin-top:24px;text-align:center}
    .sn-cta h3{font-size:1.1rem;font-weight:700;color:#0B2447;margin:0 0 8px}
    .sn-cta p{font-size:.9rem;color:rgba(15,23,42,.6);margin:0 0 16px}
    .sn-cta-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:.9rem;box-shadow:0 4px 14px rgba(11,36,71,.2);transition:all .2s}
    .sn-cta-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(11,36,71,.3)}

    /* Responsive */
    @media(max-width:768px){
      .sn-hero{border-radius:0}
      .sn-hero img{min-height:220px;max-height:360px}
      .sn-hero-content{padding:24px 20px}
      .sn-hero-content h1{font-size:1.4rem}
      .sn-hero-meta{gap:12px;font-size:.82rem}
      .sn-body{padding:24px 20px}
      .sn-excerpt{font-size:1rem;padding-left:14px}
      .sn-content{font-size:.98rem}
      .sn-content h2{font-size:1.35rem}
      .sn-content h3{font-size:1.15rem}
      .sn-content table{font-size:.85rem;display:block;overflow-x:auto}
      .sn-content table th,.sn-content table td{padding:8px 10px;white-space:nowrap}
      .sn-share{gap:8px}
      .sn-share-label{font-size:.82rem;width:100%;margin-bottom:2px}
      .sn-share-btn{padding:8px 14px;font-size:.78rem}
    }
    @media(max-width:480px){
      .sn-hero img{min-height:180px;max-height:280px}
      .sn-hero-content{padding:18px 14px}
      .sn-hero-content h1{font-size:1.15rem;line-height:1.3}
      .sn-hero-meta{flex-direction:column;gap:6px;font-size:.78rem}
      .sn-body{padding:18px 14px}
      .sn-content{font-size:.93rem;line-height:1.75}
      .sn-content h2{font-size:1.2rem}
      .sn-content blockquote{padding:12px 14px;margin:14px 0}
      .sn-share-btn{padding:7px 10px;font-size:.74rem}
    }
  </style>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Breadcrumb -->
<div class="shiksha-header">
  <div class="container">
    <div class="shiksha-breadcrumb">
      <a href="<?= $siteBase ?>/index.php">Home</a>
      <i class="ph ph-caret-right"></i>
      <a href="<?= $siteBase ?>/schools">Schools</a>
      <i class="ph ph-caret-right"></i>
      <a href="<?= $siteBase ?>/school/<?= urlencode($schoolSlug) ?>"><?= htmlspecialchars($schoolName) ?></a>
      <i class="ph ph-caret-right"></i>
      <span><?= htmlspecialchars(mb_strimwidth($newsTitle, 0, 50, '...')) ?></span>
    </div>
  </div>
</div>

<div class="container" style="max-width:860px;margin:0 auto;padding:28px 20px">
  <a href="<?= $siteBase ?>/school/<?= urlencode($schoolSlug) ?>/news" class="sn-back"><i class="ph ph-arrow-left"></i> Back to <?= htmlspecialchars($schoolName) ?> News</a>

  <article class="sn-card" style="margin-top:20px">
    <?php if ($imgSrc): ?>
    <div class="sn-hero">
      <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($newsTitle) ?>">
      <div class="sn-hero-overlay"></div>
      <div class="sn-hero-content">
        <span class="sn-hero-badge"><i class="ph ph-newspaper"></i> School News</span>
        <h1><?= htmlspecialchars($newsTitle) ?></h1>
        <div class="sn-hero-meta">
          <span><i class="ph ph-calendar-blank"></i> <?= $publishDate ?></span>
          <span><i class="ph ph-buildings"></i> <?= htmlspecialchars($schoolName) ?></span>
          <span><i class="ph ph-clock"></i> <?= $readingTime ?> min read</span>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="sn-body">
      <?php if (empty($imgSrc)): ?>
      <span class="sn-hero-badge" style="background:rgba(11,36,71,.06);color:#0B2447"><i class="ph ph-newspaper"></i> School News</span>
      <h1 style="font-size:2rem;font-weight:800;color:#111;line-height:1.25;margin:0 0 16px"><?= htmlspecialchars($newsTitle) ?></h1>
      <div class="sn-hero-meta" style="padding-bottom:20px;border-bottom:1px solid #f1f5f9;margin-bottom:22px;color:#666">
        <span><i class="ph ph-calendar-blank" style="color:#0B2447"></i> <?= $publishDate ?></span>
        <span><i class="ph ph-buildings" style="color:#0B2447"></i> <?= htmlspecialchars($schoolName) ?></span>
        <span><i class="ph ph-clock" style="color:#0B2447"></i> <?= $readingTime ?> min read</span>
      </div>
      <?php endif; ?>

      <?php if (!empty($news['excerpt'])): ?>
        <p class="sn-excerpt"><?= nl2br(htmlspecialchars($news['excerpt'])) ?></p>
      <?php endif; ?>

      <?php if (!empty($news['content'])): ?>
        <div class="sn-content"><?= $news['content'] ?></div>
      <?php else: ?>
        <p style="color:#999;text-align:center;padding:30px 0">Full article content coming soon.</p>
      <?php endif; ?>

      <!-- Share Bar -->
      <div class="sn-share">
        <span class="sn-share-label"><i class="ph ph-share-network"></i> Share:</span>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer" class="sn-share-btn s-fb" aria-label="Share on Facebook"><i class="ph ph-facebook-logo"></i> Facebook</a>
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($newsTitle) ?>" target="_blank" rel="noopener noreferrer" class="sn-share-btn s-tw" aria-label="Share on X"><i class="ph ph-x-logo"></i> X</a>
        <a href="https://api.whatsapp.com/send?text=<?= urlencode($newsTitle . ' ' . $pageUrl) ?>" target="_blank" rel="noopener noreferrer" class="sn-share-btn s-wa" aria-label="Share on WhatsApp"><i class="ph ph-whatsapp-logo"></i> WhatsApp</a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($pageUrl) ?>" target="_blank" rel="noopener noreferrer" class="sn-share-btn s-li" aria-label="Share on LinkedIn"><i class="ph ph-linkedin-logo"></i> LinkedIn</a>
        <button type="button" class="sn-share-btn s-copy" onclick="copyLink(this)" aria-label="Copy link"><i class="ph ph-link"></i> Copy Link</button>
      </div>
    </div>
  </article>

  <a href="<?= $siteBase ?>/school/<?= urlencode($schoolSlug) ?>/news" class="sn-back" style="margin-top:24px"><i class="ph ph-arrow-left"></i> Back to all <?= htmlspecialchars($schoolName) ?> news</a>

  <div class="sn-cta">
    <h3>Need Help with School Admissions?</h3>
    <p>Get expert guidance — it's completely free.</p>
    <a href="<?= $siteBase ?>/counselling" class="sn-cta-btn">Get Free Help <i class="ph ph-arrow-right"></i></a>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
function copyLink(btn) {
  navigator.clipboard.writeText(window.location.href).then(function() {
    btn.style.background = '#059669';
    btn.innerHTML = '<i class="ph ph-check"></i> Copied!';
    setTimeout(function() { btn.style.background = '#64748b'; btn.innerHTML = '<i class="ph ph-link"></i> Copy Link'; }, 2000);
  }).catch(function() {
    var t = document.createElement('textarea');
    t.value = window.location.href;
    document.body.appendChild(t);
    t.select();
    document.execCommand('copy');
    document.body.removeChild(t);
    btn.style.background = '#059669';
    btn.innerHTML = '<i class="ph ph-check"></i> Copied!';
    setTimeout(function() { btn.style.background = '#64748b'; btn.innerHTML = '<i class="ph ph-link"></i> Copy Link'; }, 2000);
  });
}
</script>
</body>
</html>
