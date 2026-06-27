<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

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

if (!empty($categoryLabel)) {
    $pageTitle = $categoryLabel . ' - Latest Articles';
} elseif ($type !== 'all') {
    $pageTitle = ucwords(str_replace('_', ' ', $type)) . ' Updates';
} else {
    $pageTitle = 'College & University News ' . date('Y') . ': Latest News & Notifications';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - AdmissionSeason</title>
  
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
