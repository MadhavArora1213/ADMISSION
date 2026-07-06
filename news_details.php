<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/panel_cms_2847/db.php';
require_once __DIR__ . '/includes/news_seo_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('cAll')) {
    function cAll(PDO $pdo, string $sql): array {
        try { return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
        catch (Exception $e) { return []; }
    }
}
if (!function_exists('cImg')) {
    function cImg(?string $url = ''): string {
        if (!$url) return 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80';
        if (str_starts_with($url, 'http') || str_starts_with($url, '//')) return $url;
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return $base . '/' . ltrim($url, '/');
    }
}

// Get slug from URL
$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    header('Location: news.php');
    exit;
}

// Handle Logout from comments section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    header("Location: news_details.php?slug=" . urlencode($slug) . "#comments-section");
    exit;
}

// Fetch the article
$stmt = $pdo->prepare("
    SELECT a.*, c.category_name, c.category_slug
    FROM articles a
    LEFT JOIN article_categories c ON a.category_id = c.id
    WHERE a.article_slug = ? AND a.status = 'published'
    LIMIT 1
");
$stmt->execute([$slug]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    // Fallback: check college_updates table
    if (!function_exists('getBaseUrl')) { require_once __DIR__ . '/includes/news_seo_helpers.php'; }
    if (!function_exists('cImg')) {
        function cImg(?string $url = ''): string {
            $base = defined('BASE_URL') ? BASE_URL : '/ADMISSION';
            if (!$url) return $base . '/assets/img/logo.png';
            if (str_starts_with($url, 'http') || str_starts_with($url, '//')) return $url;
            return $base . '/' . ltrim($url, '/');
        }
    }
    $siteBase = getBaseUrl();
    $cuStmt = $pdo->prepare("SELECT cu.*, col.name AS college_name, col.slug AS college_slug FROM college_updates cu LEFT JOIN colleges col ON col.id = cu.college_id WHERE cu.slug = ? AND cu.status = 'published' LIMIT 1");
    $cuStmt->execute([$slug]);
    $cuUpdate = $cuStmt->fetch(PDO::FETCH_ASSOC);
    if ($cuUpdate) {
        $cuTitle = $cuUpdate['title'];
        $cuDesc = $cuUpdate['description'] ?? '';
        $cuDate = $cuUpdate['event_date'] ?? $cuUpdate['created_at'];
        $cuCollege = $cuUpdate['college_name'] ?? '';
        $cuCollegeSlug = $cuUpdate['college_slug'] ?? '';
        $cuImage = $cuUpdate['image_url'] ?? '';
        $cuActionUrl = $cuUpdate['action_url'] ?? '';
        $cuType = ucwords(str_replace('_', ' ', $cuUpdate['update_type'] ?? 'news'));
        $cuShareUrl = $siteBase . '/news/' . urlencode($slug);
        $cuBackUrl = $siteBase . '/college/' . urlencode($cuCollegeSlug) . '/news';

        // SEO variables
        $cuSiteName = 'AdmissionSeason';
        $cuShareDesc = mb_strimwidth(strip_tags($cuDesc), 0, 160, '...');
        $cuPublishISO = !empty($cuDate) ? date('c', strtotime($cuDate)) : '';
        $cuKeywords = $cuType . ', ' . $cuCollege . ', AdmissionSeason, college news, education updates';
        // OG image
        $cuRawImage = $cuImage;
        if (!empty($cuRawImage) && !str_starts_with($cuRawImage, 'http') && !str_starts_with($cuRawImage, '//')) {
            $cuShareImage = $siteBase . '/' . ltrim($cuRawImage, '/');
        } elseif (!empty($cuRawImage) && str_starts_with($cuRawImage, 'http')) {
            $cuShareImage = $cuRawImage;
        } else {
            $cuShareImage = $siteBase . '/assets/img/logo.png';
        }
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/favicon.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($cuTitle) ?> - <?= htmlspecialchars($cuCollege) ?> News | <?= $cuSiteName ?></title>
  <meta name="description" content="<?= htmlspecialchars($cuShareDesc) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($cuKeywords) ?>">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
  <link rel="canonical" href="<?= $cuShareUrl ?>">
  <meta name="author" content="<?= $cuSiteName ?>">
  <meta name="revisit-after" content="7 days">
  <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($cuTitle) ?> RSS Feed" href="<?= $siteBase ?>/news/rss">

  <?= renderGeoMetaTags($cuTitle, $cuDesc, $cuDesc) ?>

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?= $cuShareUrl ?>">
  <meta property="og:title" content="<?= htmlspecialchars($cuTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($cuShareDesc) ?>">
  <meta property="og:image" content="<?= $cuShareImage ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="<?= htmlspecialchars($cuTitle) ?>">
  <meta property="og:site_name" content="<?= $cuSiteName ?>">
  <meta property="og:locale" content="en_IN">
  <meta property="article:section" content="<?= htmlspecialchars($cuCollege) ?> News">
  <?php if ($cuPublishISO): ?>
  <meta property="article:published_time" content="<?= $cuPublishISO ?>">
  <?php endif; ?>
  <meta property="article:publisher" content="https://www.facebook.com/admissionseason">
  <meta property="article:tag" content="<?= htmlspecialchars($cuType) ?>">
  <meta property="article:tag" content="<?= htmlspecialchars($cuCollege) ?>">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= $cuShareUrl ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($cuTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($cuShareDesc) ?>">
  <meta name="twitter:image" content="<?= $cuShareImage ?>">
  <meta name="twitter:image:alt" content="<?= htmlspecialchars($cuTitle) ?>">
  <meta name="twitter:site" content="@AdmissionSeason">
  <meta name="twitter:creator" content="@AdmissionSeason">

  <!-- Structured Data: NewsArticle -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'mainEntityOfPage' => [
      '@type' => 'WebPage',
      '@id' => $cuShareUrl
    ],
    'headline' => mb_strlen($cuTitle) > 110 ? mb_substr($cuTitle, 0, 107) . '...' : $cuTitle,
    'description' => $cuShareDesc,
    'datePublished' => $cuPublishISO,
    'dateModified' => $cuPublishISO,
    'author' => [
      '@type' => 'Organization',
      'name' => $cuSiteName,
      'url' => $siteBase
    ],
    'publisher' => [
      '@type' => 'Organization',
      'name' => $cuSiteName,
      'url' => $siteBase,
      'logo' => [
        '@type' => 'ImageObject',
        'url' => $siteBase . '/assets/img/logo.png',
        'width' => 600,
        'height' => 60
      ],
      'sameAs' => [
        'https://www.facebook.com/admissionseason',
        'https://twitter.com/AdmissionSeason',
        'https://www.instagram.com/admissionseason',
        'https://www.linkedin.com/company/admissionseason'
      ]
    ],
    'image' => [
      '@type' => 'ImageObject',
      'url' => $cuShareImage,
      'width' => 1200,
      'height' => 630,
      'alt' => $cuTitle
    ],
    'url' => $cuShareUrl,
    'articleSection' => $cuCollege . ' News',
    'keywords' => $cuKeywords,
    'wordCount' => str_word_count(strip_tags($cuDesc)),
    'isPartOf' => [
      '@type' => 'WebSite',
      'name' => $cuSiteName,
      'url' => $siteBase
    ],
    'inLanguage' => 'en-IN',
    'about' => [
      '@type' => 'Thing',
      'name' => $cuCollege . ' Updates'
    ],
    'interactionStatistic' => [
      '@type' => 'InteractionCounter',
      'interactionType' => 'https://schema.org/ViewAction',
      'userInteractionCount' => 0
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <!-- Structured Data: BreadcrumbList -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => array_filter([
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $siteBase . '/'],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'Colleges', 'item' => $siteBase . '/colleges.php'],
      !empty($cuCollegeSlug) ? ['@type' => 'ListItem', 'position' => 3, 'name' => $cuCollege, 'item' => $siteBase . '/college/' . urlencode($cuCollegeSlug)] : null,
      ['@type' => 'ListItem', 'position' => !empty($cuCollegeSlug) ? 4 : 3, 'name' => mb_strimwidth($cuTitle, 0, 60, '...')],
    ]),
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="<?= $siteBase ?>/assets/css/style.css?v=<?= time() ?>">
  <style>
    .nd-hero-banner{position:relative;width:100%;max-height:420px;overflow:hidden;border-radius:14px;margin-bottom:28px}
    .nd-hero-banner img{width:100%;height:100%;object-fit:cover;display:block}
    .nd-hero-overlay{position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(11,36,71,0.85));pointer-events:none}
    .nd-hero-bottom{position:absolute;bottom:0;left:0;right:0;padding:28px 32px;color:#fff}
    .nd-hero-bottom h1{font-size:1.9rem;font-weight:800;line-height:1.25;margin:0 0 10px;font-family:'Plus Jakarta Sans',sans-serif}
    .nd-hero-meta{display:flex;flex-wrap:wrap;gap:16px;font-size:.88rem;opacity:.9}
    .nd-hero-meta span{display:flex;align-items:center;gap:5px}
    .nd-hero-meta i{font-size:1rem}
    .nd-back-link{display:inline-flex;align-items:center;gap:6px;margin-top:20px;padding:10px 22px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;color:#0B2447;text-decoration:none;font-weight:600;font-size:.88rem;transition:all .2s;box-shadow:0 1px 4px rgba(0,0,0,.04)}
    .nd-back-link:hover{background:#0B2447;color:#fff;border-color:#0B2447}
    .nd-cu-body{font-size:1.05rem;color:#333;line-height:1.85;font-family:'Merriweather',Georgia,serif}
    .nd-cu-body h2,.nd-cu-body h3{font-family:'Plus Jakarta Sans',sans-serif;color:#111;margin:24px 0 12px}
    .nd-cu-body p{margin-bottom:18px}
    .nd-cu-body ul,.nd-cu-body ol{margin:0 0 18px 24px}
    .nd-cu-body li{margin-bottom:8px}
    .nd-cu-body img{max-width:100%;border-radius:8px;margin:16px 0}
    .nd-cu-body blockquote{border-left:4px solid #0B2447;margin:20px 0;padding:14px 20px;background:rgba(11,36,71,.04);border-radius:0 8px 8px 0;font-style:italic;color:#444}
    .nd-cu-action{display:inline-flex;align-items:center;gap:8px;margin-top:22px;padding:13px 28px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:.92rem;transition:all .2s;box-shadow:0 4px 14px rgba(11,36,71,.2)}
    .nd-cu-action:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(11,36,71,.3)}
    .nd-share-strip{display:flex;align-items:center;gap:10px;margin-top:30px;padding-top:24px;border-top:1px solid #f1f5f9;flex-wrap:wrap}
    .nd-share-strip span{font-size:.85rem;font-weight:600;color:#64748b}
    .nd-share-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;border:none;color:#fff;text-decoration:none;transition:all .2s;font-size:.82rem;font-weight:600}
    .nd-share-btn:hover{opacity:.88;transform:translateY(-1px)}
    .nd-share-btn.fb{background:#1877f2}.nd-share-btn.tw{background:#0f172a}.nd-share-btn.wa{background:#25d366}.nd-share-btn.li{background:#0a66c2}.nd-share-btn.tg{background:#0088cc}
    .nd-share-btn.copy-link{background:#64748b;cursor:pointer}
    .nd-share-btn.copy-link.copied{background:#059669}
    .nd-section-title{font-size:1.15rem;font-weight:700;color:#0B2447;margin-bottom:16px;display:flex;align-items:center;gap:8px;font-family:'Plus Jakarta Sans',sans-serif}

    /* ═══ RESPONSIVE ═══ */
    @media(max-width:768px){
      .nd-hero-banner{border-radius:0;max-height:300px;margin-bottom:0}
      .nd-hero-banner img{min-height:200px}
      .nd-hero-bottom{padding:20px 18px}
      .nd-hero-bottom h1{font-size:1.3rem}
      .nd-hero-meta{gap:10px;font-size:.8rem}
      .nd-back-link{margin:16px 0 0;font-size:.84rem;padding:9px 18px}
      .nd-cu-body{font-size:.98rem}
      .nd-cu-body h2{font-size:1.35rem}
      .nd-cu-body h3{font-size:1.15rem}
      .nd-cu-body p{margin-bottom:14px}
      .nd-cu-body ul,.nd-cu-body ol{margin-left:18px}
      .nd-cu-body table{font-size:.85rem;display:block;overflow-x:auto}
      .nd-cu-body table th,.nd-cu-body table td{padding:8px 10px;white-space:nowrap}
      .nd-cu-action{padding:11px 22px;font-size:.88rem;width:100%;justify-content:center}
      .nd-share-strip{gap:8px;padding-top:18px;margin-top:22px}
      .nd-share-strip span{font-size:.8rem;width:100%;margin-bottom:2px}
      .nd-share-btn{padding:8px 13px;font-size:.76rem;gap:4px}
      .nd-share-btn i{font-size:.95rem}
      .nd-cu-noimg-header{padding:20px 18px 0!important}
      .nd-cu-noimg-header h1{font-size:1.3rem!important}
      .nd-cu-content{padding:18px 16px 20px!important}
    }
    @media(max-width:480px){
      .nd-hero-banner{max-height:240px}
      .nd-hero-bottom{padding:16px 14px}
      .nd-hero-bottom h1{font-size:1.1rem;line-height:1.3}
      .nd-hero-meta{font-size:.75rem;gap:8px;flex-direction:column}
      .nd-back-link{font-size:.8rem;padding:8px 14px}
      .nd-cu-body{font-size:.93rem;line-height:1.75}
      .nd-cu-body h2{font-size:1.2rem}
      .nd-cu-body blockquote{padding:12px 14px;margin:14px 0}
      .nd-share-btn{padding:7px 10px;font-size:.72rem}
    }
  </style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Breadcrumb -->
<div class="shiksha-header">
  <div class="container">
    <div class="shiksha-breadcrumb">
      <a href="<?= $siteBase ?>/index.php">Home</a>
      <i class="ph ph-caret-right"></i>
      <a href="<?= $siteBase ?>/colleges">Colleges</a>
      <i class="ph ph-caret-right"></i>
      <?php if ($cuCollegeSlug): ?>
      <a href="<?= $siteBase ?>/college/<?= urlencode($cuCollegeSlug) ?>"><?= htmlspecialchars($cuCollege) ?></a>
      <i class="ph ph-caret-right"></i>
      <?php endif; ?>
      <span><?= htmlspecialchars(mb_strimwidth($cuTitle, 0, 50, '...')) ?></span>
    </div>
  </div>
</div>

<div class="container shiksha-main-wrapper" style="max-width:860px;margin:0 auto;padding:28px 20px">
  <a href="<?= $cuBackUrl ?>" class="nd-back-link"><i class="ph ph-arrow-left"></i> Back to <?= htmlspecialchars($cuCollege) ?> News</a>

  <div style="background:#fff;border-radius:14px;border:1px solid #f1f5f9;overflow:hidden;margin-top:20px;box-shadow:0 2px 12px rgba(0,0,0,.04)">
    <?php if ($cuImage): ?>
    <div class="nd-hero-banner">
      <img src="<?= cImg($cuImage) ?>" alt="<?= htmlspecialchars($cuTitle) ?>">
      <div class="nd-hero-overlay"></div>
      <div class="nd-hero-bottom">
        <span style="display:inline-block;padding:4px 14px;background:rgba(255,255,255,.18);backdrop-filter:blur(4px);border-radius:50px;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px"><?= htmlspecialchars($cuType) ?></span>
        <h1><?= htmlspecialchars($cuTitle) ?></h1>
        <div class="nd-hero-meta">
          <span><i class="ph ph-graduation-cap"></i> <?= htmlspecialchars($cuCollege) ?></span>
          <span><i class="ph ph-calendar-blank"></i> <?= date('d M Y', strtotime($cuDate)) ?></span>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div style="padding:28px 28px 0" class="nd-cu-noimg-header">
      <span style="display:inline-block;padding:5px 14px;background:rgba(11,36,71,.06);border-radius:4px;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#0B2447;margin-bottom:16px"><?= htmlspecialchars($cuType) ?></span>
      <h1 style="font-size:1.8rem;font-weight:800;color:#111;line-height:1.25;margin:0 0 14px"><?= htmlspecialchars($cuTitle) ?></h1>
      <div style="display:flex;flex-wrap:wrap;gap:14px;font-size:.88rem;color:#666;padding-bottom:18px;border-bottom:1px solid #f8fafc">
        <span style="display:flex;align-items:center;gap:5px"><i class="ph ph-graduation-cap" style="color:#0B2447"></i> <?= htmlspecialchars($cuCollege) ?></span>
        <span style="display:flex;align-items:center;gap:5px"><i class="ph ph-calendar-blank" style="color:#0B2447"></i> <?= date('d M Y', strtotime($cuDate)) ?></span>
      </div>
    </div>
    <?php endif; ?>

    <div style="padding:24px 28px 28px" class="nd-cu-content">
      <div class="nd-cu-body">
        <?php if ($cuDesc): ?>
          <?= $cuDesc ?>
        <?php else: ?>
          <p>Full details coming soon.</p>
        <?php endif; ?>
      </div>

      <?php if ($cuActionUrl): ?>
      <a href="<?= htmlspecialchars($cuActionUrl) ?>" target="_blank" rel="noopener" class="nd-cu-action">
        <i class="ph ph-arrow-square-out"></i> Visit Official Link
      </a>
      <?php endif; ?>

      <div class="nd-share-strip">
        <span><i class="ph ph-share-network"></i> Share:</span>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($cuShareUrl) ?>" target="_blank" rel="noopener noreferrer" class="nd-share-btn fb" aria-label="Share on Facebook"><i class="ph ph-facebook-logo"></i> Facebook</a>
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($cuShareUrl) ?>&text=<?= urlencode($cuTitle) ?>" target="_blank" rel="noopener noreferrer" class="nd-share-btn tw" aria-label="Share on X (Twitter)"><i class="ph ph-x-logo"></i> X</a>
        <a href="https://api.whatsapp.com/send?text=<?= urlencode($cuTitle . ' ' . $cuShareUrl) ?>" target="_blank" rel="noopener noreferrer" class="nd-share-btn wa" aria-label="Share on WhatsApp"><i class="ph ph-whatsapp-logo"></i> WhatsApp</a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($cuShareUrl) ?>" target="_blank" rel="noopener noreferrer" class="nd-share-btn li" aria-label="Share on LinkedIn"><i class="ph ph-linkedin-logo"></i> LinkedIn</a>
        <a href="https://t.me/share/url?url=<?= urlencode($cuShareUrl) ?>&text=<?= urlencode($cuTitle) ?>" target="_blank" rel="noopener noreferrer" class="nd-share-btn tg" aria-label="Share on Telegram"><i class="ph ph-telegram-logo"></i> Telegram</a>
        <button type="button" class="nd-share-btn copy-link" onclick="copyShareLink(this, '<?= urlencode($cuShareUrl) ?>')" aria-label="Copy link to clipboard"><i class="ph ph-link"></i> Copy Link</button>
      </div>
    </div>
  </div>

  <!-- Counselling CTA -->
  <div style="background:linear-gradient(135deg,rgba(11,36,71,.06),rgba(11,36,71,.03));border:1px solid rgba(79,70,229,.15);border-radius:14px;padding:24px 28px;margin-top:24px;text-align:center">
    <h3 style="font-size:1.1rem;font-weight:700;color:#0B2447;margin:0 0 8px">Need Help with College Admissions?</h3>
    <p style="font-size:.9rem;color:rgba(15,23,42,.6);margin:0 0 16px">Get expert guidance — it's completely free.</p>
    <a href="<?= $siteBase ?>/counselling" style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:.9rem;box-shadow:0 4px 14px rgba(11,36,71,.2)">Get Free Help <i class="ph ph-arrow-right"></i></a>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
function copyShareLink(btn, encodedUrl) {
  var url = decodeURIComponent(encodedUrl);
  navigator.clipboard.writeText(url).then(function() {
    btn.classList.add('copied');
    btn.innerHTML = '<i class="ph ph-check"></i> Copied!';
    setTimeout(function() {
      btn.classList.remove('copied');
      btn.innerHTML = '<i class="ph ph-link"></i> Copy Link';
    }, 2000);
  }).catch(function() {
    var t = document.createElement('textarea');
    t.value = url;
    document.body.appendChild(t);
    t.select();
    document.execCommand('copy');
    document.body.removeChild(t);
    btn.classList.add('copied');
    btn.innerHTML = '<i class="ph ph-check"></i> Copied!';
    setTimeout(function() {
      btn.classList.remove('copied');
      btn.innerHTML = '<i class="ph ph-link"></i> Copy Link';
    }, 2000);
  });
}
</script>
</body>
</html>
<?php
        exit;
    }
    header('HTTP/1.0 404 Not Found');
    header('Location: news.php');
    exit;
}

// Track unique view (by user_id, session, or IP — only once per 24h)
$articleId = $article['id'];
$viewerUserId   = $_SESSION['user_id'] ?? null;
$viewerSession  = session_id();
$viewerIp       = $_SERVER['REMOTE_ADDR'] ?? '';

$alreadyViewed = false;
if ($viewerUserId) {
    $chk = $pdo->prepare("SELECT id FROM article_views WHERE article_id=? AND user_id=? AND viewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1");
    $chk->execute([$articleId, $viewerUserId]);
    $alreadyViewed = (bool)$chk->fetch();
} else {
    $chk = $pdo->prepare("SELECT id FROM article_views WHERE article_id=? AND ip_address=? AND viewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1");
    $chk->execute([$articleId, $viewerIp]);
    $alreadyViewed = (bool)$chk->fetch();
}

if (!$alreadyViewed) {
    $ins = $pdo->prepare("INSERT INTO article_views (article_id, user_id, session_id, ip_address) VALUES (?, ?, ?, ?)");
    $ins->execute([$articleId, $viewerUserId, $viewerSession, $viewerIp]);
    $cnt = $pdo->prepare("SELECT COUNT(DISTINCT COALESCE(user_id, ip_address)) FROM article_views WHERE article_id=?");
    $cnt->execute([$articleId]);
    $uniqueViews = (int)$cnt->fetchColumn();
    $pdo->prepare("UPDATE articles SET view_count=? WHERE id=?")->execute([$uniqueViews, $articleId]);
    $article['view_count'] = $uniqueViews;
}

// Fetch related articles
$currentTags = json_decode($article['tags'] ?? '[]', true);
$currentTags = is_array($currentTags) ? array_filter(array_map('intval', $currentTags)) : [];
$currentTitleWords = array_filter(explode(' ', mb_strtolower($article['article_title'] ?? '')));
$currentTitleWords = array_map(fn($w) => preg_replace('/[^a-z0-9]/', '', $w), $currentTitleWords);
$currentTitleWords = array_filter($currentTitleWords, fn($w) => mb_strlen($w) > 3);

$related = [];

if (count($currentTags) > 0) {
    $jsonContainsParts = [];
    $jsonContainsParams = [];
    foreach ($currentTags as $tid) {
        $jsonContainsParts[] = "JSON_CONTAINS(a.tags, ?)";
        $jsonContainsParams[] = json_encode((int)$tid);
    }
    $tagWhereSql = implode(' OR ', $jsonContainsParts);

    $tagOverlapParts = [];
    foreach ($currentTags as $tid) {
        $tagOverlapParts[] = "(CASE WHEN a.tags LIKE ? THEN 1 ELSE 0 END)";
    }
    $tagOverlapSql = implode('+', $tagOverlapParts);

    $overlapLikeParams = array_map(fn($tid) => '%"'.$tid.'"%', array_values($currentTags));
    $params = array_merge($overlapLikeParams, $jsonContainsParams, [$article['id']]);

    $stmtRel = $pdo->prepare("
        SELECT a.id, a.article_title, a.article_slug, a.article_type, a.category_id,
               a.featured_image_url, a.publish_at, a.tags, c.category_name,
               1 AS match_priority,
               ($tagOverlapSql) AS tag_overlap
        FROM articles a
        LEFT JOIN article_categories c ON a.category_id = c.id
        WHERE a.status = 'published'
          AND a.id != ?
          AND ($tagWhereSql)
        ORDER BY tag_overlap DESC, a.publish_at DESC
        LIMIT 8
    ");
    $stmtRel->execute($params);
    $related = $stmtRel->fetchAll(PDO::FETCH_ASSOC);
}

$relatedIds = array_column($related, 'id');
if (count($related) < 4 && !empty($article['category_id'])) {
    $excludeIds = array_merge([$article['id']], $relatedIds);
    $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
    $stmtCat = $pdo->prepare("
        SELECT a.id, a.article_title, a.article_slug, a.article_type, a.category_id,
               a.featured_image_url, a.publish_at, a.tags, c.category_name,
               2 AS match_priority, 0 AS tag_overlap
        FROM articles a
        LEFT JOIN article_categories c ON a.category_id = c.id
        WHERE a.status = 'published'
          AND a.id NOT IN ($placeholders)
          AND a.category_id = ?
        ORDER BY a.publish_at DESC
        LIMIT 8
    ");
    $catParams = array_merge($excludeIds, [$article['category_id']]);
    $stmtCat->execute($catParams);
    $related = array_merge($related, $stmtCat->fetchAll(PDO::FETCH_ASSOC));
}

$relatedIds = array_column($related, 'id');
if (count($related) < 4) {
    $excludeIds = array_merge([$article['id']], $relatedIds);
    $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
    $stmtType = $pdo->prepare("
        SELECT a.id, a.article_title, a.article_slug, a.article_type, a.category_id,
               a.featured_image_url, a.publish_at, a.tags, c.category_name,
               3 AS match_priority, 0 AS tag_overlap
        FROM articles a
        LEFT JOIN article_categories c ON a.category_id = c.id
        WHERE a.status = 'published'
          AND a.id NOT IN ($placeholders)
          AND a.article_type = ?
        ORDER BY a.publish_at DESC
        LIMIT 8
    ");
    $stmtType->execute(array_merge($excludeIds, [$article['article_type']]));
    $related = array_merge($related, $stmtType->fetchAll(PDO::FETCH_ASSOC));
}

$relatedIds = array_column($related, 'id');
if (count($related) < 4) {
    $excludeIds = array_merge([$article['id']], $relatedIds);
    if (count($excludeIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
        $stmtAny = $pdo->prepare("
            SELECT a.id, a.article_title, a.article_slug, a.article_type, a.category_id,
                   a.featured_image_url, a.publish_at, a.tags, c.category_name,
                   4 AS match_priority, 0 AS tag_overlap
            FROM articles a
            LEFT JOIN article_categories c ON a.category_id = c.id
            WHERE a.status = 'published'
              AND a.id NOT IN ($placeholders)
            ORDER BY a.view_count DESC, a.publish_at DESC
            LIMIT 8
        ");
        $stmtAny->execute($excludeIds);
        $related = array_merge($related, $stmtAny->fetchAll(PDO::FETCH_ASSOC));
    }
}

$relatedIds = array_column($related, 'id');
if (count($related) < 4 && count($currentTitleWords) > 0) {
    $excludeIds = array_merge([$article['id']], $relatedIds);
    $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
    $likeClauses = [];
    $likeParams = [];
    foreach (array_slice($currentTitleWords, 0, 5) as $word) {
        $likeClauses[] = "a.article_title LIKE ?";
        $likeParams[] = "%{$word}%";
    }
    $likeSql = implode(' OR ', $likeClauses);
    $kwParams = array_merge($excludeIds, $likeParams);
    $stmtKw = $pdo->prepare("
        SELECT a.id, a.article_title, a.article_slug, a.article_type, a.category_id,
               a.featured_image_url, a.publish_at, a.tags, c.category_name,
               5 AS match_priority, 0 AS tag_overlap
        FROM articles a
        LEFT JOIN article_categories c ON a.category_id = c.id
        WHERE a.status = 'published'
          AND a.id NOT IN ($placeholders)
          AND ($likeSql)
        ORDER BY a.publish_at DESC
        LIMIT 4
    ");
    $stmtKw->execute($kwParams);
    $related = array_merge($related, $stmtKw->fetchAll(PDO::FETCH_ASSOC));
}

$seen = [];
$relatedArticles = [];
foreach ($related as $rel) {
    if (!isset($seen[$rel['id']])) {
        $seen[$rel['id']] = true;
        $relatedArticles[] = $rel;
    }
}
$relatedArticles = array_slice($relatedArticles, 0, 4);

// Sidebar: Popular news
$popular = $pdo->query("
    SELECT article_title, article_slug, featured_image_url, publish_at
    FROM articles WHERE status = 'published'
    ORDER BY view_count DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Sidebar: Categories
$sidebarCats = $pdo->query("
    SELECT c.category_name, c.category_slug, COUNT(a.id) as count 
    FROM article_categories c 
    LEFT JOIN articles a ON a.category_id = c.id AND a.status='published' 
    GROUP BY c.id ORDER BY count DESC LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$typeLabel    = ucwords(str_replace('_', ' ', $article['article_type']));
$publishDate  = !empty($article['publish_at']) ? date('F d, Y', strtotime($article['publish_at'])) : '';
$readingTime  = $article['reading_time_mins'] ?? max(1, (int)ceil(str_word_count(strip_tags($article['content_body'] ?? '')) / 200));

$siteBase  = getBaseUrl();
$shareUrl   = $siteBase . '/news/' . urlencode($slug);

$rawImage = $article['featured_image_url'] ?? '';
if (!empty($rawImage) && !str_starts_with($rawImage, 'http') && !str_starts_with($rawImage, '//')) {
    $shareImage = $siteBase . '/' . ltrim($rawImage, '/');
} elseif (!empty($rawImage) && str_contains($rawImage, 'admissionseason.com')) {
    $shareImage = $rawImage;
} elseif (!empty($rawImage) && str_starts_with($rawImage, 'http')) {
    $localPath = 'uploads/' . basename(parse_url($rawImage, PHP_URL_PATH));
    $localFile = __DIR__ . '/' . $localPath;
    if (file_exists($localFile)) {
        $shareImage = $siteBase . '/' . $localPath;
    } else {
        $shareImage = $siteBase . '/assets/img/logo.png';
    }
} else {
    $shareImage = $siteBase . '/assets/img/logo.png';
}
$shareDesc  = mb_strimwidth(strip_tags($article['excerpt'] ?? $article['content_body'] ?? ''), 0, 160, '...');
$siteName   = 'AdmissionSeason';
$publishDateISO  = !empty($article['publish_at']) ? date('c', strtotime($article['publish_at'])) : '';
$modifiedDateISO = !empty($article['updated_at']) ? date('c', strtotime($article['updated_at'])) : $publishDateISO;

$tags = [];
if (!empty($article['tags'])) {
    $tagIds = json_decode($article['tags'], true);
    if (is_array($tagIds) && count($tagIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
        $tagStmt = $pdo->prepare("SELECT tag_name FROM tags WHERE id IN ($placeholders)");
        $tagStmt->execute(array_map('intval', $tagIds));
        $tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
$keywords = !empty($tags) ? implode(', ', array_slice($tags, 0, 10)) : $typeLabel . ', ' . ($article['category_name'] ?? 'News') . ', AdmissionSeason, college news, education updates';

// Handle Comment Submission
$comment_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    if (!isset($_SESSION['user_id'])) {
        $comment_error = 'You must be logged in to comment.';
    } else {
        $comment_text = trim($_POST['comment_text'] ?? '');
        if (empty($comment_text)) {
            $comment_error = 'Comment cannot be empty.';
        } else {
            $stmtComment = $pdo->prepare("INSERT INTO article_comments (article_id, user_id, comment_text) VALUES (?, ?, ?)");
            $stmtComment->execute([$article['id'], $_SESSION['user_id'], $comment_text]);
            header("Location: news_details.php?slug=" . urlencode($slug) . "#comments-section");
            exit;
        }
    }
}

// Fetch all comments for this article
$stmtComments = $pdo->prepare("
    SELECT c.*, u.full_name 
    FROM article_comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.article_id = ? 
    ORDER BY c.created_at DESC
");
$stmtComments->execute([$article['id']]);
$comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/favicon.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($article['article_title']) ?> - <?= $siteName ?></title>
  <meta name="description" content="<?= htmlspecialchars($shareDesc) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($keywords) ?>">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
  <link rel="canonical" href="<?= $shareUrl ?>">
  <meta name="author" content="<?= htmlspecialchars($article['custom_author_name'] ?: 'AdmissionSeason') ?>">
  <meta name="revisit-after" content="7 days">
  <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($article['article_title']) ?> RSS Feed" href="<?= $siteBase ?>/news/rss">

  <?= renderGeoMetaTags($article['article_title'], $article['content_body'], $article['excerpt']) ?>

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?= $shareUrl ?>">
  <meta property="og:title" content="<?= htmlspecialchars($article['article_title']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($shareDesc) ?>">
  <meta property="og:image" content="<?= $shareImage ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="<?= htmlspecialchars($article['featured_image_alt'] ?? $article['article_title']) ?>">
  <meta property="og:site_name" content="<?= $siteName ?>">
  <meta property="og:locale" content="en_IN">
  <?php if (!empty($article['category_name'])): ?>
  <meta property="article:section" content="<?= htmlspecialchars($article['category_name']) ?>">
  <?php endif; ?>
  <?php if ($publishDateISO): ?>
  <meta property="article:published_time" content="<?= $publishDateISO ?>">
  <?php endif; ?>
  <?php if ($modifiedDateISO && $modifiedDateISO !== $publishDateISO): ?>
  <meta property="article:modified_time" content="<?= $modifiedDateISO ?>">
  <?php endif; ?>
  <meta property="article:publisher" content="https://www.facebook.com/admissionseason">
  <?php foreach (array_slice($tags, 0, 5) as $tag): ?>
  <meta property="article:tag" content="<?= htmlspecialchars($tag) ?>">
  <?php endforeach; ?>

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= $shareUrl ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($article['article_title']) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($shareDesc) ?>">
  <meta name="twitter:image" content="<?= $shareImage ?>">
  <meta name="twitter:image:alt" content="<?= htmlspecialchars($article['featured_image_alt'] ?? $article['article_title']) ?>">
  <meta name="twitter:site" content="@AdmissionSeason">
  <meta name="twitter:creator" content="@AdmissionSeason">

  <!-- Structured Data: NewsArticle -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'mainEntityOfPage' => [
      '@type' => 'WebPage',
      '@id' => $shareUrl
    ],
    'headline' => mb_strlen($article['article_title']) > 110 ? mb_substr($article['article_title'], 0, 107) . '...' : $article['article_title'],
    'description' => $shareDesc,
    'datePublished' => $publishDateISO,
    'dateModified' => $modifiedDateISO,
    'author' => [
      '@type' => 'Person',
      'name' => $article['custom_author_name'] ?: 'AdmissionSeason Desk',
      'url' => $siteBase . '/about'
    ],
    'publisher' => [
      '@type' => 'Organization',
      'name' => $siteName,
      'url' => $siteBase,
      'logo' => [
        '@type' => 'ImageObject',
        'url' => $siteBase . '/assets/img/logo.png',
        'width' => 600,
        'height' => 60
      ],
      'sameAs' => [
        'https://www.facebook.com/admissionseason',
        'https://twitter.com/AdmissionSeason',
        'https://www.instagram.com/admissionseason',
        'https://www.linkedin.com/company/admissionseason'
      ]
    ],
    'image' => [
      '@type' => 'ImageObject',
      'url' => $shareImage,
      'width' => 1200,
      'height' => 630,
      'alt' => $article['featured_image_alt'] ?? $article['article_title']
    ],
    'url' => $shareUrl,
    'articleSection' => $article['category_name'] ?? $typeLabel,
    'keywords' => $keywords,
    'wordCount' => str_word_count(strip_tags($article['content_body'] ?? '')),
    'contentLocation' => getArticleContentLocation($article['article_title'], $article['content_body'], $article['excerpt']),
    'isPartOf' => [
      '@type' => 'WebSite',
      'name' => $siteName,
      'url' => $siteBase
    ],
    'inLanguage' => 'en-IN',
    'about' => [
      '@type' => 'Thing',
      'name' => 'Education News India'
    ],
    'interactionStatistic' => [
      '@type' => 'InteractionCounter',
      'interactionType' => 'https://schema.org/ViewAction',
      'userInteractionCount' => (int)($article['view_count'] ?? 0)
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <!-- Structured Data: BreadcrumbList -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => array_filter([
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $siteBase . '/'],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'News', 'item' => $siteBase . '/news.php'],
      !empty($article['category_name']) ? ['@type' => 'ListItem', 'position' => 3, 'name' => $article['category_name'], 'item' => $siteBase . '/news.php?category=' . urlencode($article['category_slug'])] : null,
      ['@type' => 'ListItem', 'position' => !empty($article['category_name']) ? 4 : 3, 'name' => mb_strimwidth($article['article_title'], 0, 60, '...')],
    ]),
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <!-- Structured Data: FAQPage -->
  <?php if (!empty($article['content_body']) && preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/i', $article['content_body'], $faqHeadings) && count($faqHeadings[0]) >= 2): ?>
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(function($h) {
        $text = strip_tags($h);
        return [
          '@type' => 'Question',
          'name' => $text,
          'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'For detailed information about ' . $text . ', please read the full article on AdmissionSeason.'
          ]
        ];
    }, array_slice($faqHeadings[1], 0, 5))
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>
  <?php endif; ?>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">

  <style>
    /* ═══════════════════════════════════════════════════
       NEWS DETAILS — Premium Redesign
       ═══════════════════════════════════════════════════ */

    /* --- Hero Banner --- */
    .nd-hero {
      position: relative;
      width: 100%;
      max-height: 480px;
      border-radius: 14px;
      overflow: hidden;
      margin-bottom: 0;
    }
    .nd-hero img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      min-height: 280px;
    }
    .nd-hero-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, transparent 30%, rgba(11,36,71,0.88));
      pointer-events: none;
    }
    .nd-hero-content {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 32px 36px;
      color: #fff;
    }
    .nd-hero-badges {
      display: flex;
      gap: 8px;
      margin-bottom: 14px;
      flex-wrap: wrap;
    }
    .nd-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 14px;
      border-radius: 50px;
      font-size: .75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .04em;
      backdrop-filter: blur(6px);
    }
    .nd-badge-type {
      background: rgba(255,255,255,.18);
      color: #fff;
    }
    .nd-badge-cat {
      background: rgba(16,185,129,.25);
      color: #a7f3d0;
    }
    .nd-hero-content h1 {
      font-size: 2rem;
      font-weight: 800;
      line-height: 1.25;
      margin: 0 0 14px;
      text-shadow: 0 2px 8px rgba(0,0,0,.2);
    }
    .nd-hero-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 18px;
      font-size: .88rem;
      opacity: .92;
    }
    .nd-hero-meta span {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .nd-hero-meta i {
      font-size: 1rem;
    }

    /* --- Article Card Container --- */
    .nd-article-card {
      background: #fff;
      border-radius: 14px;
      border: 1px solid #f1f5f9;
      overflow: hidden;
      box-shadow: 0 2px 16px rgba(0,0,0,.04);
    }

    /* --- Article Content Area --- */
    .nd-article-body {
      padding: 32px 36px;
    }

    /* --- Lead / Excerpt --- */
    .nd-lead {
      font-size: 1.15rem;
      color: #444;
      line-height: 1.7;
      font-style: italic;
      margin-bottom: 26px;
      padding-left: 18px;
      border-left: 4px solid #0B2447;
    }

    /* --- Article Typography --- */
    .nd-body {
      font-size: 1.05rem;
      color: #333;
      line-height: 1.85;
      font-family: 'Merriweather', Georgia, serif;
    }
    .nd-body h2, .nd-body h3, .nd-body h4 {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: #111;
      margin: 28px 0 14px;
      line-height: 1.3;
    }
    .nd-body h2 { font-size: 1.65rem; font-weight: 700; }
    .nd-body h3 { font-size: 1.35rem; font-weight: 700; }
    .nd-body h4 { font-size: 1.15rem; font-weight: 600; }
    .nd-body p { margin-bottom: 18px; }
    .nd-body a { color: #0B2447; text-decoration: underline; text-underline-offset: 3px; }
    .nd-body a:hover { color: #19376D; }
    .nd-body ul, .nd-body ol { margin: 0 0 18px 24px; }
    .nd-body li { margin-bottom: 8px; }
    .nd-body blockquote {
      border-left: 4px solid #0B2447;
      margin: 24px 0;
      padding: 16px 22px;
      background: rgba(11,36,71,.04);
      border-radius: 0 10px 10px 0;
      color: #444;
      font-style: italic;
    }
    .nd-body img {
      max-width: 100%;
      border-radius: 10px;
      margin: 18px 0;
    }
    .nd-body table {
      width: 100%;
      border-collapse: collapse;
      margin: 22px 0;
      font-size: .95rem;
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid #f1f5f9;
    }
    .nd-body table th,
    .nd-body table td {
      border: 1px solid #f1f5f9;
      padding: 12px 16px;
      text-align: left;
    }
    .nd-body table th {
      background: rgba(11,36,71,.05);
      color: #0B2447;
      font-weight: 700;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .nd-body table tr:hover td {
      background: rgba(11,36,71,.02);
    }

    /* --- Tags --- */
    .nd-tags {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 10px;
      margin: 28px 0;
      padding: 22px 0;
      border-top: 1px solid #f1f5f9;
      border-bottom: 1px solid #f1f5f9;
    }
    .nd-tags-label {
      font-size: .88rem;
      color: #555;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .nd-tags-label i { color: #0B2447; }
    .nd-tag {
      padding: 5px 14px;
      background: #f8fafc;
      border-radius: 50px;
      font-size: .8rem;
      color: rgba(15,23,42,.75);
      font-weight: 500;
      border: 1px solid rgba(15,23,42,.08);
      text-decoration: none;
      transition: all .2s;
    }
    .nd-tag:hover {
      background: #0B2447;
      color: #fff;
      border-color: #0B2447;
    }

    /* --- Share Bar --- */
    .nd-share {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
      margin: 28px 0 36px;
    }
    .nd-share-label {
      font-size: .88rem;
      font-weight: 600;
      color: #64748b;
      margin-right: 4px;
    }
    .nd-share-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 9px 18px;
      border-radius: 8px;
      border: none;
      color: #fff;
      text-decoration: none;
      font-size: .82rem;
      font-weight: 600;
      transition: all .2s;
    }
    .nd-share-btn:hover { opacity: .88; transform: translateY(-1px); }
    .nd-share-btn.s-fb { background: #1877f2; }
    .nd-share-btn.s-tw { background: #0f172a; }
    .nd-share-btn.s-wa { background: #25d366; }
    .nd-share-btn.s-li { background: #0a66c2; }
    .nd-share-btn.s-tg { background: #0088cc; }
    .nd-share-btn.s-copy { background: #64748b; cursor: pointer; }
    .nd-share-btn.s-copy:hover { background: #475569; }

    /* --- NPS Widget --- */
    .nd-nps {
      border: 1px solid #f1f5f9;
      border-radius: 12px;
      padding: 28px 32px;
      margin: 32px 0;
      background: #fff;
      text-align: center;
    }
    .nd-nps-question {
      font-size: 1.08rem;
      color: #222;
      margin-bottom: 22px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      line-height: 1.4;
    }
    .nd-nps-question strong { color: #0B2447; }
    .nd-nps-scale {
      display: flex;
      justify-content: center;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 10px;
    }
    .nd-nps-btn {
      width: 46px;
      height: 46px;
      border-radius: 50%;
      border: 2px solid rgba(15,23,42,.12);
      background: #fff;
      color: #555;
      font-size: .95rem;
      font-weight: 700;
      cursor: pointer;
      transition: all .2s;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .nd-nps-btn:hover {
      border-color: #0B2447;
      color: #0B2447;
      background: rgba(11,36,71,.04);
      transform: scale(1.1);
    }
    .nd-nps-btn.selected {
      background: #0B2447;
      border-color: #0B2447;
      color: #fff;
      transform: scale(1.15);
      box-shadow: 0 4px 14px rgba(11,36,71,.3);
    }
    .nd-nps-labels {
      display: flex;
      justify-content: space-between;
      font-size: .78rem;
      color: #999;
      font-weight: 500;
      padding: 0 2px;
    }
    .nd-nps-thanks {
      padding: 16px;
      animation: ndFadeIn .4s ease;
    }
    .nd-nps-thanks i { font-size: 2.2rem; color: #059669; }
    .nd-nps-thanks p { font-size: 1rem; color: #444; margin-top: 8px; font-weight: 600; }

    @keyframes ndFadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* --- Comments --- */
    .nd-comments {
      margin-top: 36px;
      padding-top: 28px;
      border-top: 1px solid #f1f5f9;
    }
    .nd-comments-title {
      font-size: 1.3rem;
      font-weight: 800;
      color: #0B2447;
      margin-bottom: 22px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .nd-comments-title i { color: #19376D; font-size: 1.5rem; }
    .nd-comment-form-wrap {
      background: #f8fafc;
      border: 1px solid rgba(15,23,42,.06);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 28px;
    }
    .nd-user-row {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 14px;
      padding-bottom: 12px;
      border-bottom: 1px solid rgba(15,23,42,.06);
    }
    .nd-user-avatar {
      width: 34px;
      height: 34px;
      background: linear-gradient(135deg, #19376D, #0B2447);
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: .85rem;
    }
    .nd-user-name {
      font-size: .9rem;
      color: #333;
    }
    .nd-user-name strong { color: #0B2447; }
    .nd-logout-btn {
      margin-left: auto;
      background: none;
      border: none;
      color: #999;
      font-size: .82rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 4px;
      padding: 4px 8px;
      border-radius: 6px;
      transition: all .2s;
    }
    .nd-logout-btn:hover { color: #ef4444; background: rgba(239,68,68,.06); }
    .nd-comment-textarea {
      width: 100%;
      border: 1px solid rgba(15,23,42,.1);
      border-radius: 10px;
      padding: 14px 16px;
      font-size: .95rem;
      font-family: inherit;
      resize: vertical;
      min-height: 100px;
      transition: border-color .2s;
      background: #fff;
    }
    .nd-comment-textarea:focus {
      outline: none;
      border-color: #0B2447;
      box-shadow: 0 0 0 3px rgba(11,36,71,.08);
    }
    .nd-comment-submit {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 12px;
      padding: 11px 24px;
      background: linear-gradient(135deg, #0B2447, #19376D);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-weight: 700;
      font-size: .9rem;
      cursor: pointer;
      transition: all .2s;
    }
    .nd-comment-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(11,36,71,.25); }
    .nd-login-prompt {
      display: flex;
      align-items: center;
      gap: 16px;
      background: #f8fafc;
      border: 1px dashed rgba(15,23,42,.12);
      border-radius: 12px;
      padding: 20px 24px;
      margin-bottom: 28px;
    }
    .nd-login-icon {
      width: 48px;
      height: 48px;
      background: rgba(11,36,71,.06);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .nd-login-icon i { font-size: 1.4rem; color: #0B2447; }
    .nd-login-text h4 { font-size: 1rem; color: #111; margin: 0 0 4px; }
    .nd-login-text p { font-size: .85rem; color: #888; margin: 0; }
    .nd-login-btn {
      margin-left: auto;
      padding: 10px 22px;
      background: linear-gradient(135deg, #0B2447, #19376D);
      color: #fff;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 700;
      font-size: .88rem;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all .2s;
      white-space: nowrap;
    }
    .nd-login-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(11,36,71,.25); }

    .nd-comment-list { display: flex; flex-direction: column; gap: 16px; }
    .nd-comment-item {
      display: flex;
      gap: 14px;
      padding: 18px;
      background: #fff;
      border: 1px solid #f1f5f9;
      border-radius: 12px;
      transition: box-shadow .2s;
    }
    .nd-comment-item:hover { box-shadow: 0 2px 12px rgba(0,0,0,.04); }
    .nd-comment-avatar {
      width: 38px;
      height: 38px;
      background: linear-gradient(135deg, #19376D, #0B2447);
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: .9rem;
      flex-shrink: 0;
    }
    .nd-comment-content { flex: 1; min-width: 0; }
    .nd-comment-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
    }
    .nd-comment-author { font-weight: 700; color: #111; font-size: .92rem; }
    .nd-comment-date { font-size: .78rem; color: #999; }
    .nd-comment-body { font-size: .93rem; color: #444; line-height: 1.65; }
    .nd-no-comments {
      text-align: center;
      padding: 36px 20px;
      color: #999;
    }
    .nd-no-comments i { font-size: 2.5rem; color: rgba(15,23,42,.1); display: block; margin-bottom: 12px; }
    .nd-no-comments p { font-size: .95rem; }
    .nd-alert-error {
      padding: 12px 16px;
      border-radius: 10px;
      margin-bottom: 18px;
      font-size: .9rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(239,68,68,.08);
      color: #0f172a;
      border: 1px solid rgba(239,68,68,.15);
    }

    /* --- Related Articles --- */
    .nd-related {
      border-top: 2px solid #f1f5f9;
      padding-top: 28px;
      margin-top: 8px;
    }
    .nd-related-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: #111;
      margin-bottom: 20px;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .nd-related-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 18px;
    }
    .nd-rel-card {
      display: block;
      border: 1px solid #f1f5f9;
      border-radius: 10px;
      overflow: hidden;
      text-decoration: none;
      transition: all .25s;
    }
    .nd-rel-card:hover {
      box-shadow: 0 8px 24px rgba(0,0,0,.08);
      transform: translateY(-3px);
    }
    .nd-rel-img {
      height: 150px;
      overflow: hidden;
    }
    .nd-rel-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform .4s;
    }
    .nd-rel-card:hover .nd-rel-img img { transform: scale(1.05); }
    .nd-rel-body { padding: 14px 16px; }
    .nd-rel-type {
      display: inline-block;
      font-size: .7rem;
      font-weight: 700;
      color: #19376D;
      text-transform: uppercase;
      letter-spacing: .04em;
      margin-bottom: 6px;
    }
    .nd-rel-match {
      display: inline-block;
      font-size: .65rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 10px;
      margin-left: 6px;
      vertical-align: middle;
    }
    .nd-rel-match.m-tags { background: rgba(16,185,129,.1); color: #059669; }
    .nd-rel-match.m-cat { background: rgba(37,99,235,.1); color: #2563eb; }
    .nd-rel-match.m-type { background: rgba(15,23,42,.06); color: rgba(15,23,42,.5); }
    .nd-rel-match.m-kw { background: rgba(245,158,11,.1); color: #D97706; }
    .nd-rel-body h4 {
      font-size: .95rem;
      color: #111;
      line-height: 1.4;
      margin-bottom: 8px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .nd-rel-date {
      font-size: .78rem;
      color: #999;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    /* --- Sidebar --- */
    .nd-sidebar { top: 130px; position: sticky; }
    .nd-widget {
      background: #fff;
      border: 1px solid #f1f5f9;
      border-radius: 14px;
      padding: 22px;
      margin-bottom: 20px;
      box-shadow: 0 1px 8px rgba(0,0,0,.03);
    }
    .nd-widget-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: #0B2447;
      margin-bottom: 16px;
      padding-bottom: 12px;
      border-bottom: 2px solid #f1f5f9;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .nd-pop-list { display: flex; flex-direction: column; gap: 14px; }
    .nd-pop-item {
      display: flex;
      gap: 12px;
      text-decoration: none;
      color: inherit;
      padding: 6px;
      border-radius: 8px;
      transition: background .2s;
    }
    .nd-pop-item:hover { background: #f8fafc; }
    .nd-pop-item img {
      width: 64px;
      height: 52px;
      object-fit: cover;
      border-radius: 8px;
      flex-shrink: 0;
    }
    .nd-pop-title {
      font-size: .88rem;
      font-weight: 600;
      color: #111;
      line-height: 1.4;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .nd-pop-date { font-size: .75rem; color: #999; margin-top: 3px; }
    .nd-cat-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; }
    .nd-cat-list li a {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 9px 12px;
      border-radius: 8px;
      color: #444;
      font-size: .92rem;
      text-decoration: none;
      transition: all .2s;
    }
    .nd-cat-list li a:hover { background: #0B2447; color: #fff; }
    .nd-cat-list li a span:first-child { display: flex; align-items: center; gap: 6px; }
    .nd-cat-list li a span:last-child { font-size: .78rem; opacity: .6; }
    .nd-type-links { display: flex; flex-direction: column; gap: 8px; }
    .nd-type-links a {
      color: #444;
      font-size: .92rem;
      padding: 9px 12px;
      border-radius: 8px;
      background: #f8fafc;
      border: 1px solid #f1f5f9;
      text-decoration: none;
      transition: all .2s;
    }
    .nd-type-links a:hover { background: #0B2447; color: #fff; border-color: #0B2447; }

    /* --- Counselling CTA Widget --- */
    .nd-cta-widget {
      background: linear-gradient(135deg, rgba(11,36,71,.06), rgba(11,36,71,.03));
      border: 1px solid rgba(79,70,229,.15);
      border-radius: 14px;
      padding: 24px;
      text-align: center;
    }
    .nd-cta-widget h4 {
      font-size: 1.05rem;
      font-weight: 700;
      color: #0B2447;
      margin: 0 0 8px;
    }
    .nd-cta-widget p {
      font-size: .88rem;
      color: rgba(15,23,42,.6);
      margin: 0 0 14px;
    }
    .nd-cta-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 11px 24px;
      background: linear-gradient(135deg, #0B2447, #19376D);
      color: #fff;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 700;
      font-size: .88rem;
      transition: all .2s;
    }
    .nd-cta-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(11,36,71,.25); }

    /* --- Responsive --- */
    @media(max-width: 768px) {
      .nd-hero { border-radius: 0; max-height: 340px; }
      .nd-hero-content { padding: 24px 20px; }
      .nd-hero-content h1 { font-size: 1.45rem; }
      .nd-hero-meta { gap: 12px; font-size: .82rem; }
      .nd-article-body { padding: 24px 20px; }
      .nd-related-grid { grid-template-columns: 1fr; }
      .nd-sidebar { position: static; }
      .nd-nps { padding: 20px 16px; }
      .nd-nps-btn { width: 38px; height: 38px; font-size: .85rem; }
      .nd-nps-scale { gap: 6px; }
      .nd-login-prompt { flex-direction: column; text-align: center; gap: 12px; }
      .nd-login-btn { margin-left: 0; }
      .nd-share { gap: 8px; }
      .nd-share-btn { padding: 8px 14px; font-size: .78rem; }
      .nd-comment-item { padding: 14px; }
    }
  </style>
</head>
<body class="bg-light">

<?php include 'includes/navbar.php'; ?>

<!-- Breadcrumb -->
<div class="shiksha-header">
  <div class="container">
    <div class="shiksha-breadcrumb">
      <a href="index.php">Home</a>
      <i class="ph ph-caret-right"></i>
      <a href="news.php">News</a>
      <i class="ph ph-caret-right"></i>
      <?php if (!empty($article['category_name'])): ?>
        <a href="news.php?category=<?=urlencode($article['category_slug']) ?>"><?= htmlspecialchars($article['category_name']) ?></a>
        <i class="ph ph-caret-right"></i>
      <?php endif; ?>
      <span><?= htmlspecialchars(mb_strimwidth($article['article_title'], 0, 50, '...')) ?></span>
    </div>
  </div>
</div>

<div class="container shiksha-main-wrapper">
  <div class="shiksha-layout">

    <!-- ═══ MAIN ARTICLE ═══ -->
    <main class="shiksha-content" style="padding:0">

      <div class="nd-article-card">

        <!-- Hero Banner with Featured Image -->
        <?php if (!empty($article['featured_image_url'])): ?>
        <div class="nd-hero">
          <img src="<?= cImg($article['featured_image_url']) ?>"
               alt="<?= htmlspecialchars($article['featured_image_alt'] ?? $article['article_title']) ?>">
          <div class="nd-hero-overlay"></div>
          <div class="nd-hero-content">
            <div class="nd-hero-badges">
              <span class="nd-badge nd-badge-type"><?= $typeLabel ?></span>
              <?php if (!empty($article['category_name'])): ?>
              <span class="nd-badge nd-badge-cat"><i class="ph ph-folder"></i> <?= htmlspecialchars($article['category_name']) ?></span>
              <?php endif; ?>
            </div>
            <h1><?= htmlspecialchars($article['article_title']) ?></h1>
            <div class="nd-hero-meta">
              <span><i class="ph ph-user-circle"></i> <?= htmlspecialchars($article['custom_author_name'] ?: 'AdmissionSeason Desk') ?></span>
              <?php if ($publishDate): ?>
              <span><i class="ph ph-calendar-blank"></i> <?= $publishDate ?></span>
              <?php endif; ?>
              <span><i class="ph ph-clock"></i> <?= $readingTime ?> min read</span>
              <?php if ($article['view_count'] > 0): ?>
              <span><i class="ph ph-eye"></i> <?= number_format($article['view_count']) ?> views</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Article Body -->
        <div class="nd-article-body">

          <?php if (empty($article['featured_image_url'])): ?>
          <!-- Fallback header when no featured image -->
          <div class="nd-hero-badges" style="margin-bottom:16px">
            <span class="nd-badge nd-badge-type" style="background:rgba(11,36,71,.06);color:#0B2447"><?= $typeLabel ?></span>
            <?php if (!empty($article['category_name'])): ?>
            <span class="nd-badge nd-badge-cat" style="background:rgba(16,185,129,.1);color:#059669"><i class="ph ph-folder"></i> <?= htmlspecialchars($article['category_name']) ?></span>
            <?php endif; ?>
          </div>
          <h1 style="font-size:2rem;font-weight:800;color:#111;line-height:1.25;margin:0 0 18px;font-family:'Plus Jakarta Sans',sans-serif"><?= htmlspecialchars($article['article_title']) ?></h1>
          <div class="nd-hero-meta" style="padding-bottom:20px;border-bottom:1px solid #f1f5f9;margin-bottom:22px;color:#666">
            <span><i class="ph ph-user-circle" style="color:#0B2447"></i> <?= htmlspecialchars($article['custom_author_name'] ?: 'AdmissionSeason Desk') ?></span>
            <?php if ($publishDate): ?>
            <span><i class="ph ph-calendar-blank" style="color:#0B2447"></i> <?= $publishDate ?></span>
            <?php endif; ?>
            <span><i class="ph ph-clock" style="color:#0B2447"></i> <?= $readingTime ?> min read</span>
            <?php if ($article['view_count'] > 0): ?>
            <span><i class="ph ph-eye" style="color:#0B2447"></i> <?= number_format($article['view_count']) ?> views</span>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Excerpt / Lead -->
          <?php if (!empty($article['excerpt'])): ?>
          <p class="nd-lead"><?= htmlspecialchars($article['excerpt']) ?></p>
          <?php endif; ?>

          <!-- Content Body -->
          <div class="nd-body">
            <?= $article['content_body'] ?: '<p>Full article content coming soon.</p>' ?>
          </div>

          <!-- Tags -->
          <?php if (!empty($tags)): ?>
          <div class="nd-tags">
            <span class="nd-tags-label"><i class="ph ph-tag"></i> Tags:</span>
            <?php foreach ($tags as $tag): ?>
              <a href="news.php" class="nd-tag"><?= htmlspecialchars($tag) ?></a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Share Bar -->
          <div class="nd-share">
            <span class="nd-share-label"><i class="ph ph-share-network"></i> Share this article:</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="nd-share-btn s-fb" aria-label="Share on Facebook"><i class="ph ph-facebook-logo"></i> Facebook</a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($article['article_title'] . ' — ' . $siteName) ?>" target="_blank" rel="noopener noreferrer" class="nd-share-btn s-tw" aria-label="Share on X (Twitter)"><i class="ph ph-x-logo"></i> X</a>
            <a href="https://api.whatsapp.com/send?text=<?= urlencode($article['article_title'] . ' ' . $shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="nd-share-btn s-wa" aria-label="Share on WhatsApp"><i class="ph ph-whatsapp-logo"></i> WhatsApp</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="nd-share-btn s-li" aria-label="Share on LinkedIn"><i class="ph ph-linkedin-logo"></i> LinkedIn</a>
            <a href="https://t.me/share/url?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($article['article_title']) ?>" target="_blank" rel="noopener noreferrer" class="nd-share-btn s-tg" aria-label="Share on Telegram"><i class="ph ph-telegram-logo"></i> Telegram</a>
            <button type="button" class="nd-share-btn s-copy" onclick="copyArticleLink(this)" aria-label="Copy link to clipboard"><i class="ph ph-link"></i> Copy Link</button>
          </div>

          <!-- ═══ NPS RATING WIDGET ═══ -->
          <?php
          $npsUserId = $_SESSION['user_id'] ?? null;
          $npsAlreadySubmitted = false;
          if ($npsUserId) {
              $npsChk = $pdo->prepare("SELECT id FROM nps_feedback WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
              $npsChk->execute([$npsUserId]);
              $npsAlreadySubmitted = (bool)$npsChk->fetch();
          }
          ?>
          <div class="nd-nps" id="npsWidget">
            <p class="nd-nps-question">How likely are you to recommend <strong>AdmissionSeason</strong> to a friend or a colleague?</p>
            <?php if ($npsUserId && $npsAlreadySubmitted): ?>
            <div class="nd-nps-thanks">
              <i class="ph-fill ph-check-circle"></i>
              <p>Thank you! You have already submitted your feedback.</p>
            </div>
            <?php elseif ($npsUserId): ?>
            <div class="nd-nps-scale" id="npsScale">
              <?php for($i = 1; $i <= 10; $i++): ?>
              <button class="nd-nps-btn" data-score="<?= $i ?>" onclick="selectNps(this, <?= $i ?>)"><?= $i ?></button>
              <?php endfor; ?>
            </div>
            <div class="nd-nps-labels" id="npsLabels">
              <span>Not so likely</span>
              <span>Highly Likely</span>
            </div>
            <div class="nd-nps-thanks" id="npsThanks" style="display:none">
              <i class="ph-fill ph-check-circle"></i>
              <p>Thank you for your feedback!</p>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:20px 0">
              <i class="ph ph-lock-key" style="font-size:2rem;color:rgba(15,23,42,.12);display:block;margin-bottom:10px"></i>
              <p style="font-size:.9rem;color:rgba(15,23,42,.45);margin-bottom:14px">Please log in to share your feedback.</p>
              <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="nd-login-btn" style="margin-left:0;display:inline-flex">
                <i class="ph ph-sign-in"></i> Login to Rate
              </a>
            </div>
            <?php endif; ?>
          </div>

          <!-- ═══ COMMENTS SECTION ═══ -->
          <div class="nd-comments" id="comments-section">
            <h3 class="nd-comments-title"><i class="ph ph-chat-circle-dots"></i> Discussion (<?= count($comments) ?>)</h3>

            <?php if (!empty($comment_error)): ?>
              <div class="nd-alert-error">
                <i class="ph ph-warning-circle"></i> <?= htmlspecialchars($comment_error) ?>
              </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): ?>
              <div class="nd-comment-form-wrap">
                <div class="nd-user-row">
                  <span class="nd-user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></span>
                  <span class="nd-user-name">Commenting as <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></span>
                  <form method="POST" action="">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="nd-logout-btn"><i class="ph ph-sign-out"></i> Logout</button>
                  </form>
                </div>
                <form method="POST" action="">
                  <input type="hidden" name="action" value="add_comment">
                  <textarea name="comment_text" class="nd-comment-textarea" placeholder="Share your thoughts or ask a question about this article..." required></textarea>
                  <button type="submit" class="nd-comment-submit"><i class="ph ph-paper-plane-tilt"></i> Post Comment</button>
                </form>
              </div>
            <?php else: ?>
              <div class="nd-login-prompt">
                <div class="nd-login-icon"><i class="ph ph-lock-key"></i></div>
                <div class="nd-login-text">
                  <h4>Join the Discussion</h4>
                  <p>You must be logged in to post comments and interact with other readers.</p>
                </div>
                <a href="login.php" class="nd-login-btn"><i class="ph ph-sign-in"></i> Login</a>
              </div>
            <?php endif; ?>

            <div class="nd-comment-list">
              <?php if (empty($comments)): ?>
                <div class="nd-no-comments">
                  <i class="ph ph-chat-circle-slash"></i>
                  <p>No comments yet. Be the first to share your thoughts!</p>
                </div>
              <?php else: ?>
                <?php foreach ($comments as $comment): 
                  $commentDate = date('M d, Y \a\t h:i A', strtotime($comment['created_at']));
                ?>
                  <div class="nd-comment-item">
                    <div class="nd-comment-avatar"><?= strtoupper(substr($comment['full_name'], 0, 1)) ?></div>
                    <div class="nd-comment-content">
                      <div class="nd-comment-header">
                        <span class="nd-comment-author"><?= htmlspecialchars($comment['full_name']) ?></span>
                        <span class="nd-comment-date"><?= $commentDate ?></span>
                      </div>
                      <div class="nd-comment-body">
                        <?= nl2br(htmlspecialchars($comment['comment_text'])) ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <!-- Related Articles -->
          <?php if (!empty($relatedArticles)): ?>
          <div class="nd-related">
            <h2 class="nd-related-title">Related Articles</h2>
            <div class="nd-related-grid">
              <?php foreach ($relatedArticles as $rel):
                $relDate = !empty($rel['publish_at']) ? date('M d, Y', strtotime($rel['publish_at'])) : '';
                $relType = ucwords(str_replace('_', ' ', $rel['article_type']));
                $matchLabel = '';
                $matchClass = '';
                $mp = $rel['match_priority'] ?? 4;
                $tagOverlap = (int)($rel['tag_overlap'] ?? 0);
                if ($mp == 1 && $tagOverlap > 0) {
                  $matchLabel = $tagOverlap . ' tag' . ($tagOverlap > 1 ? 's' : '') . ' matched';
                  $matchClass = 'm-tags';
                } elseif ($mp == 2) {
                  $matchLabel = 'Same category';
                  $matchClass = 'm-cat';
                } elseif ($mp == 3) {
                  $matchLabel = $relType;
                  $matchClass = 'm-type';
                } elseif ($mp == 5) {
                  $matchLabel = 'Keyword match';
                  $matchClass = 'm-kw';
                }
              ?>
              <a href="news_details.php?slug=<?= urlencode($rel['article_slug']) ?>" class="nd-rel-card">
                <div class="nd-rel-img">
                  <img src="<?= cImg($rel['featured_image_url']) ?>" alt="<?= htmlspecialchars($rel['article_title']) ?>">
                </div>
                <div class="nd-rel-body">
                  <span class="nd-rel-type"><?= $relType ?></span>
                  <?php if ($matchLabel): ?>
                  <span class="nd-rel-match <?= $matchClass ?>"><?= $matchLabel ?></span>
                  <?php endif; ?>
                  <h4><?= htmlspecialchars($rel['article_title']) ?></h4>
                  <span class="nd-rel-date"><i class="ph ph-calendar-blank"></i> <?= $relDate ?></span>
                </div>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

        </div><!-- /nd-article-body -->
      </div><!-- /nd-article-card -->

    </main>

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="shiksha-sidebar nd-sidebar">

      <!-- Counselling CTA -->
      <div class="nd-cta-widget">
        <h4>Free College Counselling</h4>
        <p>Get expert guidance for college admissions.</p>
        <a href="<?= $siteBase ?>/counselling" class="nd-cta-btn">Get Free Help <i class="ph ph-arrow-right"></i></a>
      </div>

      <!-- Popular Articles -->
      <div class="nd-widget">
        <h3 class="nd-widget-title"><i class="ph ph-fire" style="margin-right:6px"></i> Popular Articles</h3>
        <div class="nd-pop-list">
          <?php foreach ($popular as $pop):
            $popDate = !empty($pop['publish_at']) ? date('M d, Y', strtotime($pop['publish_at'])) : '';
          ?>
          <a href="news_details.php?slug=<?= urlencode($pop['article_slug']) ?>" class="nd-pop-item">
            <img src="<?= cImg($pop['featured_image_url']) ?>" alt="Thumb">
            <div>
              <div class="nd-pop-title"><?= htmlspecialchars($pop['article_title']) ?></div>
              <?php if ($popDate): ?><div class="nd-pop-date"><?= $popDate ?></div><?php endif; ?>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Categories -->
      <div class="nd-widget">
        <h3 class="nd-widget-title"><i class="ph ph-folder-open" style="margin-right:6px"></i> Trending Categories</h3>
        <ul class="nd-cat-list">
          <?php foreach ($sidebarCats as $sc): ?>
            <?php if ($sc['count'] > 0): ?>
            <li>
              <a href="news.php?category=<?=urlencode($sc['category_slug']) ?>">
                <span><i class="ph ph-caret-right"></i> <?= htmlspecialchars($sc['category_name']) ?></span>
                <span>(<?= $sc['count'] ?>)</span>
              </a>
            </li>
            <?php endif; ?>
          <?php endforeach; ?>
          <?php if (empty($sidebarCats)): ?>
            <li><a href="news.php"><i class="ph ph-caret-right"></i> All Articles</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Browse by Type -->
      <div class="nd-widget">
        <h3 class="nd-widget-title"><i class="ph ph-squares-four" style="margin-right:6px"></i> Browse by Type</h3>
        <div class="nd-type-links">
          <a href="news.php?type=news"><i class="ph ph-newspaper" style="margin-right:6px"></i> College News</a>
          <a href="news.php?type=exam_update"><i class="ph ph-clipboard-text" style="margin-right:6px"></i> Exam Alerts</a>
          <a href="news.php?type=blog"><i class="ph ph-note-pencil" style="margin-right:6px"></i> Blogs</a>
          <a href="news.php?type=guide"><i class="ph ph-compass" style="margin-right:6px"></i> Guides</a>
          <a href="news.php?type=opinion"><i class="ph ph-chats-circle" style="margin-right:6px"></i> Opinions</a>
          <a href="news.php?type=ranking"><i class="ph ph-trophy" style="margin-right:6px"></i> Rankings</a>
        </div>
      </div>

    </aside>

  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
const NPS_ARTICLE_ID = <?= json_encode($article['id']) ?>;
const NPS_ARTICLE_SLUG = <?= json_encode($slug) ?>;
function selectNps(btn, score) {
  document.querySelectorAll('.nd-nps-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  var scale = document.getElementById('npsScale');
  var labels = document.getElementById('npsLabels');
  if (scale) { scale.style.opacity = '0.5'; scale.style.pointerEvents = 'none'; }
  if (labels) labels.style.display = 'none';
  fetch('<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/api/nps_submit.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      score: score,
      article_id: NPS_ARTICLE_ID,
      article_slug: NPS_ARTICLE_SLUG,
      page_url: window.location.href
    })
  })
  .then(r => r.json())
  .then(data => { document.getElementById('npsThanks').style.display = 'block'; })
  .catch(() => { document.getElementById('npsThanks').style.display = 'block'; });
}
function copyArticleLink(btn) {
  var url = window.location.href;
  navigator.clipboard.writeText(url).then(function() {
    btn.style.background = '#059669';
    btn.innerHTML = '<i class="ph ph-check"></i> Copied!';
    setTimeout(function() { btn.style.background = '#64748b'; btn.innerHTML = '<i class="ph ph-link"></i> Copy Link'; }, 2000);
  }).catch(function() {
    var t = document.createElement('textarea');
    t.value = url;
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
