<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/news_seo_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions (if not already defined in db.php)
if (!function_exists('cAll')) {
    function cAll(PDO $pdo, string $sql): array {
        try { return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
        catch (Exception $e) { return []; }
    }
}
if (!function_exists('cImg')) {
    function cImg(?string $url=''): string {
        if (!$url) return 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80';
        if (str_starts_with($url, 'http') || str_starts_with($url, '//')) return $url;
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return $base . '/' . ltrim($url, '/');
    }
}

$type = $_GET['type'] ?? 'all';
$categorySlug = $_GET['category'] ?? '';
$valid_types = ['all', 'news', 'blog', 'guide', 'exam_update', 'opinion', 'ranking'];

if (!in_array($type, $valid_types)) {
    $type = 'all';
}

$query = "SELECT a.id, a.article_title, a.article_slug, a.article_type, a.excerpt, a.featured_image_url, a.publish_at, c.category_name 
          FROM articles a 
          LEFT JOIN article_categories c ON a.category_id = c.id 
          WHERE a.status = 'published'";

$params = [];
if ($type !== 'all') {
    $query .= " AND a.article_type = :type";
    $params[':type'] = $type;
}
if (!empty($categorySlug)) {
    $query .= " AND c.category_slug = :category_slug";
    $params[':category_slug'] = $categorySlug;
}

$query .= " ORDER BY a.publish_at DESC LIMIT 50";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoryLabel = '';
if (!empty($categorySlug)) {
    $catStmt = $pdo->prepare("SELECT category_name FROM article_categories WHERE category_slug = ?");
    $catStmt->execute([$categorySlug]);
    $categoryLabel = $catStmt->fetchColumn() ?: $categorySlug;
}

// Fetch categories for the sidebar
$sidebarCats = cAll($pdo, "SELECT category_name, category_slug, COUNT(a.id) as count FROM article_categories c LEFT JOIN articles a ON a.category_id = c.id WHERE a.status='published' GROUP BY c.id ORDER BY count DESC LIMIT 8");

// ── Per-Tab SEO Configuration ──
$tabSEO = [
    'all' => [
        'title'       => 'College & University News ' . date('Y') . ': Latest News, Updates & Notifications',
        'description' => 'Stay updated with the latest college and university news, exam updates, admission alerts, and education tips from AdmissionSeason. Find comprehensive coverage of Indian education including JEE, NEET, CUET, board exams, and university admissions.',
        'keywords'    => 'college news, university news, exam updates, admission alerts, education news India, JEE updates, NEET news, CUET notifications, board exam results, college admissions ' . date('Y'),
        'og_title'    => 'Latest College & University News ' . date('Y') . ' - AdmissionSeason',
        'og_desc'     => 'India\'s leading education news portal. Get real-time updates on colleges, exams, admissions, and study abroad opportunities.',
    ],
    'news' => [
        'title'       => 'College News & University Updates ' . date('Y') . ' - AdmissionSeason',
        'description' => 'Read the latest college news and university updates on AdmissionSeason. Stay informed about new admissions, campus events, placement drives, fee structures, and important announcements from top colleges across India.',
        'keywords'    => 'college news, university updates, campus news, college admissions, placement drives, fee structure, university announcements, college events India',
        'og_title'    => 'College News & University Updates ' . date('Y') . ' - AdmissionSeason',
        'og_desc'     => 'Latest updates from colleges and universities across India. Admissions, placements, campus events, and more.',
    ],
    'exam_update' => [
        'title'       => 'Exam Alerts & Notifications ' . date('Y') . ' - JEE, NEET, CUET, Board Exams',
        'description' => 'Get the latest exam alerts and notifications for JEE Main, JEE Advanced, NEET UG, CUET, CAT, GATE, board exams, and more. Check exam dates, application deadlines, admit card downloads, and result announcements on AdmissionSeason.',
        'keywords'    => 'exam alerts, JEE Main dates, NEET notification, CUET updates, CAT exam, GATE notification, board exam dates, admit card download, exam results ' . date('Y'),
        'og_title'    => 'Exam Alerts & Notifications ' . date('Y') . ' - JEE, NEET, CUET',
        'og_desc'     => 'All exam notifications in one place. JEE, NEET, CUET, CAT, GATE, board exams - dates, results, and more.',
    ],
    'blog' => [
        'title'       => 'Education Blog & Tips - College Admission Guidance',
        'description' => 'Read expert education blogs and tips on AdmissionSeason. Get guidance on college selection, course comparisons, career planning, study strategies, exam preparation tips, and admission process walkthroughs.',
        'keywords'    => 'education blog, college tips, admission guidance, career planning, study tips, exam preparation, course selection, college comparison, education advice India',
        'og_title'    => 'Education Blog & Tips - AdmissionSeason',
        'og_desc'     => 'Expert education blogs and tips for students. College guidance, career planning, exam prep, and more.',
    ],
    'guide' => [
        'title'       => 'Student Guides - Complete College & Career Guide ' . date('Y'),
        'description' => 'Comprehensive student guides for college admissions, course selection, career paths, and study abroad. AdmissionSeason provides detailed step-by-step guides for JEE, NEET, CUET preparation and college application processes.',
        'keywords'    => 'student guide, college admission guide, career guide, study abroad guide, JEE preparation guide, NEET preparation, CUET guide, course selection guide India',
        'og_title'    => 'Student Guides - Complete College & Career Guide',
        'og_desc'     => 'Step-by-step guides for college admissions, exam prep, career planning, and study abroad.',
    ],
    'opinion' => [
        'title'       => 'Expert Opinions & Education Analysis - AdmissionSeason',
        'description' => 'Read expert opinions and in-depth analysis on Indian education trends, policy changes, exam patterns, and college rankings. AdmissionSeason brings you thoughtful perspectives from education experts and industry leaders.',
        'keywords'    => 'education opinion, expert analysis, education policy, exam pattern analysis, college ranking analysis, Indian education trends, education expert views',
        'og_title'    => 'Expert Opinions & Education Analysis - AdmissionSeason',
        'og_desc'     => 'Expert opinions and in-depth analysis on Indian education trends and policies.',
    ],
    'ranking' => [
        'title'       => 'College Rankings ' . date('Y') . ' - Top Colleges & Universities in India',
        'description' => 'Check the latest college rankings for ' . date('Y') . ' on AdmissionSeason. View top engineering colleges, medical colleges, MBA colleges, law colleges, and university rankings in India with detailed comparisons and cutoff analysis.',
        'keywords'    => 'college rankings ' . date('Y') . ', top colleges India, best engineering colleges, medical college ranking, MBA colleges ranking, university rankings, NIRF ranking, college comparison',
        'og_title'    => 'College Rankings ' . date('Y') . ' - Top Colleges in India',
        'og_desc'     => 'Latest college and university rankings for ' . date('Y') . '. Top engineering, medical, MBA colleges in India.',
    ],
];

$seo = $tabSEO[$type] ?? $tabSEO['all'];

if (!empty($categoryLabel)) {
    $pageTitle   = $categoryLabel . ' - Latest Articles & Updates';
    $metaDesc    = 'Browse the latest ' . $categoryLabel . ' articles, news, and updates on AdmissionSeason. Stay informed about ' . strtolower($categoryLabel) . ' in Indian education.';
    $metaKeywords = strtolower($categoryLabel) . ', education news, college updates, AdmissionSeason';
    $ogTitle     = $categoryLabel . ' - Latest Articles | AdmissionSeason';
    $ogDesc      = $metaDesc;
} else {
    $pageTitle   = $seo['title'];
    $metaDesc    = $seo['description'];
    $metaKeywords = $seo['keywords'];
    $ogTitle     = $seo['og_title'];
    $ogDesc      = $seo['og_desc'];
}

// Build canonical URL
$siteBase = getBaseUrl();
$canonicalUrl = $siteBase . '/news.php';
if ($type !== 'all') $canonicalUrl .= '?type=' . urlencode($type);
if (!empty($categorySlug)) $canonicalUrl .= ($type !== 'all' ? '&' : '?') . 'category=' . urlencode($categorySlug);

// Tab label for breadcrumb
$tabLabel = $type === 'all' ? 'All News' : ucwords(str_replace('_', ' ', $type));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/favicon.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
  <link rel="canonical" href="<?= $canonicalUrl ?>">
  <meta name="author" content="AdmissionSeason">
  <meta name="revisit-after" content="1 days">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= $canonicalUrl ?>">
  <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($ogDesc) ?>">
  <meta property="og:image" content="$siteBase/assets/images/og-news.jpg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:site_name" content="AdmissionSeason">
  <meta property="og:locale" content="en_IN">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= $canonicalUrl ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($ogDesc) ?>">
  <meta name="twitter:image" content="$siteBase/assets/images/og-news.jpg">
  <meta name="twitter:site" content="@AdmissionSeason">
  <meta name="twitter:creator" content="@AdmissionSeason">

  <!-- GEO Meta Tags (India) -->
  <meta name="geo.region" content="IN">
  <meta name="geo.placename" content="India">
  <meta name="geo.position" content="20.5937;78.9629">
  <meta name="ICBM" content="20.5937, 78.9629">
  <meta name="language" content="English">

  <!-- RSS Feed -->
  <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($pageTitle) ?> RSS" href="$siteBase/news/rss">

  <!-- Structured Data: CollectionPage -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'description' => $metaDesc,
    'url' => $canonicalUrl,
    'publisher' => [
      '@type' => 'Organization',
      'name' => 'AdmissionSeason',
      'url' => '$siteBase',
      'logo' => [
        '@type' => 'ImageObject',
        'url' => '$siteBase/assets/images/logo.png',
        'width' => 600,
        'height' => 60
      ],
      'sameAs' => [
        'https://www.facebook.com/admissionseason',
        'https://twitter.com/AdmissionSeason',
        'https://www.instagram.com/admissionseason',
        'https://www.linkedin.com/company/admissionseason',
        'https://www.youtube.com/@admissionseason'
      ]
    ],
    'isPartOf' => [
      '@type' => 'WebSite',
      'name' => 'AdmissionSeason',
      'url' => '$siteBase'
    ],
    'about' => [
      '@type' => 'Thing',
      'name' => $tabLabel . ' - Indian Education'
    ],
    'inLanguage' => 'en-IN',
    'mainEntity' => [
      '@type' => 'ItemList',
      'name' => $pageTitle,
      'numberOfItems' => count($articles),
    ]
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <!-- Structured Data: BreadcrumbList -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => array_values(array_filter([
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => '$siteBase/'],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'News', 'item' => '$siteBase/news.php'],
      ($type !== 'all' || !empty($categoryLabel))
        ? ['@type' => 'ListItem', 'position' => 3, 'name' => !empty($categoryLabel) ? $categoryLabel : $tabLabel]
        : null,
    ])),
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <!-- Structured Data: ItemList of NewsArticles -->
  <?php if (!empty($articles)): ?>
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => $pageTitle,
    'description' => $metaDesc,
    'url' => $canonicalUrl,
    'numberOfItems' => count($articles),
    'itemListElement' => array_map(function($art, $i) {
        $url = '$siteBase/news_details.php?slug=' . urlencode($art['article_slug']);
        $desc = mb_strimwidth(strip_tags($art['excerpt'] ?? ''), 0, 160, '...');
        return [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'item' => [
                '@type' => 'NewsArticle',
                'headline' => $art['article_title'],
                'url' => $url,
                'datePublished' => !empty($art['publish_at']) ? date('c', strtotime($art['publish_at'])) : '',
                'description' => $desc,
                'author' => ['@type' => 'Organization', 'name' => 'AdmissionSeason'],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'AdmissionSeason',
                    'logo' => ['@type' => 'ImageObject', 'url' => '$siteBase/assets/images/logo.png']
                ],
                'image' => cImg($art['featured_image_url']),
                'articleSection' => $art['category_name'] ?? 'News',
            ]
        ];
    }, $articles, array_keys($articles)),
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>
  <?php endif; ?>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="assets/css/style.css?v=<?=time()?>">
</head>
<body class="bg-light">

<?php include 'includes/navbar.php'; ?>

<!-- ═══ BREADCRUMB & HEADER ═══ -->
<div class="shiksha-header">
  <div class="container">
    <div class="shiksha-breadcrumb">
      <a href="index.php">Home</a> <i class="ph ph-caret-right"></i>
      <a href="news.php">News</a> <i class="ph ph-caret-right"></i>
      <span><?= !empty($categoryLabel) ? htmlspecialchars($categoryLabel) : ($type === 'all' ? 'College' : ucwords(str_replace('_', ' ', $type))) ?></span>
    </div>
    <h1 class="shiksha-title"><?= htmlspecialchars($pageTitle) ?></h1>
  </div>
</div>

<!-- ═══ MINIMAL TABS ═══ -->
<div class="shiksha-tabs-nav">
  <div class="container">
    <div class="shiksha-tabs">
      <a href="news.php" class="<?= $type === 'all' && empty($categorySlug) ? 'active' : '' ?>">All News</a>
      <a href="?type=news" class="<?= $type === 'news' ? 'active' : '' ?>">College News</a>
      <a href="?type=exam_update" class="<?= $type === 'exam_update' ? 'active' : '' ?>">Exam Alerts</a>
      <a href="?type=blog" class="<?= $type === 'blog' ? 'active' : '' ?>">Blogs & Tips</a>
      <a href="?type=guide" class="<?= $type === 'guide' ? 'active' : '' ?>">Guides</a>
      <a href="?type=opinion" class="<?= $type === 'opinion' ? 'active' : '' ?>">Opinions</a>
      <a href="?type=ranking" class="<?= $type === 'ranking' ? 'active' : '' ?>">Rankings</a>
    </div>
  </div>
</div>

<div class="container shiksha-main-wrapper">

  <!-- ═══ MAIN LAYOUT ═══ -->
  <div class="shiksha-layout">
    
    <!-- Left Content Feed -->
    <main class="shiksha-content">
      <?php if(empty($articles)): ?>
        <div class="shiksha-empty">
          <p>No articles found for this section.</p>
        </div>
      <?php else: ?>
        <div class="shiksha-list">
          <?php foreach($articles as $art): 
            $dStr = !empty($art['publish_at']) ? date('M d, Y', strtotime($art['publish_at'])) : '';
          ?>
          <div class="shiksha-card">
            <a href="news_details.php?slug=<?=$art['article_slug']?>" class="shiksha-card-img">
              <img src="<?=cImg($art['featured_image_url'])?>" alt="<?=htmlspecialchars($art['article_title'])?>">
            </a>
            <div class="shiksha-card-body">
              <h3><a href="news_details.php?slug=<?=$art['article_slug']?>"><?=htmlspecialchars($art['article_title'])?></a></h3>
              <div class="shiksha-card-meta">
                <span><i class="ph ph-calendar-blank"></i> <?=$dStr?></span>
                <?php if(!empty($art['category_name'])): ?>
                  <span class="divider">|</span>
                  <span><i class="ph ph-folder"></i> <?=htmlspecialchars($art['category_name'])?></span>
                <?php endif; ?>
              </div>
              <p><?=htmlspecialchars(mb_strimwidth($art['excerpt'] ?? '', 0, 150, '...'))?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      
      <!-- Pagination Placeholder -->
      <?php if(!empty($articles) && count($articles) >= 50): ?>
      <div class="shiksha-pagination">
        <a href="#" class="btn-load-more">Load More Articles</a>
      </div>
      <?php endif; ?>

    </main>

    <!-- Right Sidebar -->
    <aside class="shiksha-sidebar">
      
      <!-- Popular Categories Widget -->
      <div class="shiksha-widget">
        <h3 class="shiksha-widget-title">Trending Categories</h3>
        <ul class="shiksha-cat-list">
          <?php foreach($sidebarCats as $sc): ?>
            <?php if($sc['count'] > 0): ?>
            <li><a href="news.php?category=<?=urlencode($sc['category_slug'])?>"><i class="ph ph-caret-right"></i> <?=htmlspecialchars($sc['category_name'])?> <span>(<?=$sc['count']?>)</span></a></li>
            <?php endif; ?>
          <?php endforeach; ?>
          <?php if(empty($sidebarCats)): ?>
            <li><a href="news.php"><i class="ph ph-caret-right"></i> All Articles</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Quick Links Widget -->
      <div class="shiksha-widget">
        <h3 class="shiksha-widget-title">Popular News</h3>
        <div class="shiksha-popular-list">
          <?php foreach(array_slice($articles, 0, 4) as $popArt): ?>
          <a href="news_details.php?slug=<?=$popArt['article_slug']?>" class="popular-item">
            <img src="<?=cImg($popArt['featured_image_url'])?>" alt="Thumb">
            <div class="pop-title"><?=htmlspecialchars($popArt['article_title'])?></div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

    </aside>

  </div> <!-- /shiksha-layout -->

</div> <!-- /container -->

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
