<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/admin/db.php';
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
    // Update cached view_count
    $cnt = $pdo->prepare("SELECT COUNT(DISTINCT COALESCE(user_id, ip_address)) FROM article_views WHERE article_id=?");
    $cnt->execute([$articleId]);
    $uniqueViews = (int)$cnt->fetchColumn();
    $pdo->prepare("UPDATE articles SET view_count=? WHERE id=?")->execute([$uniqueViews, $articleId]);
    $article['view_count'] = $uniqueViews;
}

// Fetch related articles with multi-criteria matching:
// 1. Shared tags (JSON array in articles.tags) - using JSON_CONTAINS (MariaDB compatible)
// 2. Same category
// 3. Same article_type
// 4. Title keyword overlap (LIKE)
// Excludes current article, ordered by relevance then recency
$currentTags = json_decode($article['tags'] ?? '[]', true);
$currentTags = is_array($currentTags) ? array_filter(array_map('intval', $currentTags)) : [];
$currentTitleWords = array_filter(explode(' ', mb_strtolower($article['article_title'] ?? '')));
$currentTitleWords = array_map(fn($w) => preg_replace('/[^a-z0-9]/', '', $w), $currentTitleWords);
$currentTitleWords = array_filter($currentTitleWords, fn($w) => mb_strlen($w) > 3);

$related = [];

// Strategy 1: Articles sharing tags (MariaDB: use JSON_CONTAINS for each tag)
if (count($currentTags) > 0) {
    // WHERE: match any shared tag via JSON_CONTAINS
    $jsonContainsParts = [];
    $jsonContainsParams = [];
    foreach ($currentTags as $tid) {
        $jsonContainsParts[] = "JSON_CONTAINS(a.tags, ?)";
        $jsonContainsParams[] = json_encode((int)$tid);
    }
    $tagWhereSql = implode(' OR ', $jsonContainsParts);

    // SELECT: count how many tags overlap via LIKE
    $tagOverlapParts = [];
    foreach ($currentTags as $tid) {
        $tagOverlapParts[] = "(CASE WHEN a.tags LIKE ? THEN 1 ELSE 0 END)";
    }
    $tagOverlapSql = implode('+', $tagOverlapParts);

    // Params: overlap params + where params + exclude current article
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

// Strategy 2: If not enough tag matches, fill with same category
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

// Strategy 3: Fill remaining with same article_type
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

// Strategy 4: Fill remaining with any recent articles
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

// Strategy 5: Title keyword overlap (LIKE match on important words from title)
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

// Deduplicate by id (keep first occurrence = highest priority)
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

// Ensure OG image is always an absolute URL for social sharing
$rawImage = $article['featured_image_url'] ?? '';
if (!empty($rawImage) && !str_starts_with($rawImage, 'http') && !str_starts_with($rawImage, '//')) {
    $shareImage = $siteBase . '/' . ltrim($rawImage, '/');
} elseif (!empty($rawImage) && str_contains($rawImage, 'admissionseason.com')) {
    $shareImage = $rawImage;
} elseif (!empty($rawImage) && str_starts_with($rawImage, 'http')) {
    // External image (Unsplash etc.) — X/Twitter can't reliably fetch these
    // Download and use locally, or fall back to default
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

// Fetch real tag names from the tags table using IDs stored in articles.tags
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

  <!-- Structured Data: NewsArticle (Google News compliant) -->
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

  <!-- Structured Data: FAQPage (if article has FAQ-like content) -->
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
    <main class="shiksha-content art-content">

      <!-- Article Type Badge -->
      <div class="art-type-tag art-type-<?= $article['article_type'] ?>"><?= $typeLabel ?></div>

      <!-- Title -->
      <h1 class="art-title"><?= htmlspecialchars($article['article_title']) ?></h1>

      <!-- Meta Row -->
      <div class="art-meta-row">
        <span class="art-meta-item">
          <i class="ph ph-user-circle"></i>
          <?= htmlspecialchars($article['custom_author_name'] ?: 'AdmissionSeason Desk') ?>
        </span>
        <?php if ($publishDate): ?>
        <span class="art-meta-item">
          <i class="ph ph-calendar-blank"></i>
          <?= $publishDate ?>
        </span>
        <?php endif; ?>
        <span class="art-meta-item">
          <i class="ph ph-clock"></i>
          <?= $readingTime ?> min read
        </span>
        <?php if ($article['view_count'] > 0): ?>
        <span class="art-meta-item">
          <i class="ph ph-eye"></i>
          <?= number_format($article['view_count']) ?> views
        </span>
        <?php endif; ?>
      </div>

      <!-- Excerpt / Lead -->
      <?php if (!empty($article['excerpt'])): ?>
      <p class="art-lead"><?= htmlspecialchars($article['excerpt']) ?></p>
      <?php endif; ?>

      <!-- Featured Image -->
      <div class="art-featured-img">
        <img src="<?= cImg($article['featured_image_url']) ?>"
             alt="<?= htmlspecialchars($article['featured_image_alt'] ?? $article['article_title']) ?>">
      </div>

      <!-- Article Body -->
      <div class="art-body">
        <?= $article['content_body'] ?: '<p>Full article content coming soon.</p>' ?>
      </div>

      <!-- Tags -->
      <?php if (!empty($tags)): ?>
      <div class="art-tags">
        <span class="art-tags-label"><i class="ph ph-tag"></i> Tags:</span>
        <?php foreach ($tags as $tag): ?>
          <a href="news.php" class="art-tag"><?= htmlspecialchars($tag) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Share Bar -->
      <div class="art-share-bar">
        <span>Share this article:</span>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-fb"><i class="ph ph-facebook-logo"></i> Facebook</a>
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($article['article_title'] . ' — ' . $siteName) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-tw"><i class="ph ph-x-logo"></i> X</a>
        <a href="https://api.whatsapp.com/send?text=<?= urlencode($article['article_title'] . ' ' . $shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-wa"><i class="ph ph-whatsapp-logo"></i> WhatsApp</a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-li"><i class="ph ph-linkedin-logo"></i> LinkedIn</a>
        <a href="https://t.me/share/url?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($article['article_title']) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-tg"><i class="ph ph-telegram-logo"></i> Telegram</a>
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
      <?php if ($npsUserId): ?>
      <div class="nps-widget" id="npsWidget">
        <p class="nps-question">How likely are you to recommend <strong>AdmissionSeason</strong> to a friend or a colleague?</p>
        <?php if ($npsAlreadySubmitted): ?>
        <div class="nps-thanks" style="display:block;">
          <i class="ph-fill ph-check-circle" style="color:#059669;font-size:2rem;"></i>
          <p>You have already submitted your feedback. Thank you!</p>
        </div>
        <?php else: ?>
        <div class="nps-scale" id="npsScale">
          <?php for($i = 1; $i <= 10; $i++): ?>
          <button class="nps-btn" data-score="<?= $i ?>" onclick="selectNps(this, <?= $i ?>)"><?= $i ?></button>
          <?php endfor; ?>
        </div>
        <div class="nps-labels" id="npsLabels">
          <span>Not so likely</span>
          <span>Highly Likely</span>
        </div>
        <div class="nps-thanks" id="npsThanks" style="display:none;">
          <i class="ph ph-smiley"></i>
          <p>Thank you for your feedback!</p>
        </div>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="nps-widget" id="npsWidget">
        <p class="nps-question">How likely are you to recommend <strong>AdmissionSeason</strong> to a friend or a colleague?</p>
        <div style="text-align:center;padding:20px 0;">
          <i class="ph ph-lock-key" style="font-size:2rem;color:rgba(15,23,42,0.15);display:block;margin-bottom:10px;"></i>
          <p style="font-size:.9rem;color:rgba(15,23,42,0.5);margin-bottom:12px;">Please log in to share your feedback.</p>
          <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" style="display:inline-flex;align-items:center;gap:6px;padding:10px 24px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:.88rem;">
            <i class="ph ph-sign-in"></i> Login to Rate
          </a>
        </div>
      </div>
      <?php endif; ?>

      <!-- ═══ COMMENTS SECTION ═══ -->
      <div class="comments-section" id="comments-section">
        <h3 class="comments-title"><i class="ph ph-chat-circle-dots"></i> Discussion (<?= count($comments) ?>)</h3>
        
        <?php if (!empty($comment_error)): ?>
          <div class="comment-alert alert-error">
            <i class="ph ph-warning-circle"></i> <?= htmlspecialchars($comment_error) ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
          <!-- Comment Form for Logged In Users -->
          <div class="comment-form-wrap">
            <div class="user-info-row">
              <span class="user-avatar-small"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></span>
              <span class="user-logged-in">Logged in as <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></span>
              <form method="POST" action="" style="margin-left: auto;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="logout-btn-link"><i class="ph ph-sign-out"></i> Logout</button>
              </form>
            </div>
            <form method="POST" action="" class="comment-form">
              <input type="hidden" name="action" value="add_comment">
              <textarea name="comment_text" rows="4" placeholder="Share your thoughts or ask a question about this article..." required></textarea>
              <div class="form-actions">
                <button type="submit" class="btn btn-primary comment-submit-btn"><i class="ph ph-paper-plane-tilt"></i> Post Comment</button>
              </div>
            </form>
          </div>
        <?php else: ?>
          <!-- Login Prompt for Guest Users -->
          <div class="comment-login-prompt">
            <div class="prompt-icon"><i class="ph ph-lock-key"></i></div>
            <div class="prompt-text">
              <h4>Join the Discussion</h4>
              <p>You must be logged in to post comments and interact with other readers.</p>
            </div>
            <a href="login.php" class="btn btn-primary login-btn-quick"><i class="ph ph-sign-in"></i> Login to Comment</a>
          </div>
        <?php endif; ?>

        <!-- Comments List -->
        <div class="comments-list">
          <?php if (empty($comments)): ?>
            <div class="no-comments">
              <i class="ph ph-chat-circle-slash"></i>
              <p>No comments yet. Be the first to share your thoughts!</p>
            </div>
          <?php else: ?>
            <?php foreach ($comments as $comment): 
              $commentDate = date('M d, Y \a\t h:i A', strtotime($comment['created_at']));
            ?>
              <div class="comment-item">
                <div class="comment-avatar"><?= strtoupper(substr($comment['full_name'], 0, 1)) ?></div>
                <div class="comment-content">
                  <div class="comment-header">
                    <span class="comment-author"><?= htmlspecialchars($comment['full_name']) ?></span>
                    <span class="comment-date"><?= $commentDate ?></span>
                  </div>
                  <div class="comment-body">
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
      <div class="art-related">
        <h2 class="art-related-title">Related Articles</h2>
        <div class="art-related-grid">
          <?php foreach ($relatedArticles as $rel):
            $relDate = !empty($rel['publish_at']) ? date('M d, Y', strtotime($rel['publish_at'])) : '';
            $relType = ucwords(str_replace('_', ' ', $rel['article_type']));
            $matchLabel = '';
            $matchClass = '';
            $mp = $rel['match_priority'] ?? 4;
            $tagOverlap = (int)($rel['tag_overlap'] ?? 0);
            if ($mp == 1 && $tagOverlap > 0) {
              $matchLabel = $tagOverlap . ' tag' . ($tagOverlap > 1 ? 's' : '') . ' matched';
              $matchClass = 'match-tags';
            } elseif ($mp == 2) {
              $matchLabel = 'Same category';
              $matchClass = 'match-category';
            } elseif ($mp == 3) {
              $matchLabel = $relType;
              $matchClass = 'match-type';
            } elseif ($mp == 5) {
              $matchLabel = 'Keyword match';
              $matchClass = 'match-keyword';
            }
          ?>
          <a href="news_details.php?slug=<?= urlencode($rel['article_slug']) ?>" class="art-rel-card">
            <div class="art-rel-img">
              <img src="<?= cImg($rel['featured_image_url']) ?>" alt="<?= htmlspecialchars($rel['article_title']) ?>">
            </div>
            <div class="art-rel-body">
              <span class="art-rel-type"><?= $relType ?></span>
              <?php if ($matchLabel): ?>
              <span class="art-rel-match <?= $matchClass ?>"><?= $matchLabel ?></span>
              <?php endif; ?>
              <h4><?= htmlspecialchars($rel['article_title']) ?></h4>
              <span class="art-rel-date"><i class="ph ph-calendar-blank"></i> <?= $relDate ?></span>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </main>

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="shiksha-sidebar art-sidebar">

      <!-- Popular Articles -->
      <div class="shiksha-widget">
        <h3 class="shiksha-widget-title">Popular Articles</h3>
        <div class="shiksha-popular-list">
          <?php foreach ($popular as $pop):
            $popDate = !empty($pop['publish_at']) ? date('M d, Y', strtotime($pop['publish_at'])) : '';
          ?>
          <a href="news_details.php?slug=<?= urlencode($pop['article_slug']) ?>" class="popular-item">
            <img src="<?= cImg($pop['featured_image_url']) ?>" alt="Thumb">
            <div>
              <div class="pop-title"><?= htmlspecialchars($pop['article_title']) ?></div>
              <?php if ($popDate): ?><div class="pop-date"><?= $popDate ?></div><?php endif; ?>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Categories -->
      <div class="shiksha-widget">
        <h3 class="shiksha-widget-title">Trending Categories</h3>
        <ul class="shiksha-cat-list">
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
      <div class="shiksha-widget">
        <h3 class="shiksha-widget-title">Browse by Type</h3>
        <div class="art-type-links">
          <a href="news.php?type=news">College News</a>
          <a href="news.php?type=exam_update">Exam Alerts</a>
          <a href="news.php?type=blog">Blogs</a>
          <a href="news.php?type=guide">Guides</a>
          <a href="news.php?type=opinion">Opinions</a>
          <a href="news.php?type=ranking">Rankings</a>
        </div>
      </div>

    </aside>

  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
const NPS_ARTICLE_ID = <?= json_encode($article['id']) ?>;
const NPS_ARTICLE_SLUG = <?= json_encode($slug) ?>;
function selectNps(btn, score) {
  document.querySelectorAll('.nps-btn').forEach(b => b.classList.remove('selected'));
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
</script>
</body>
</html>
