<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

// Helper functions (if not already defined in db.php)
if (!function_exists('cAll')) {
    function cAll(PDO $pdo, string $sql): array {
        try { return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
        catch (Exception $e) { return []; }
    }
}
if (!function_exists('cImg')) {
    function cImg(?string $url=''): string { return $url ?: 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80'; }
}

$type = $_GET['type'] ?? 'all';
$valid_types = ['all', 'news', 'blog', 'guide', 'exam_update', 'opinion', 'ranking'];

if (!in_array($type, $valid_types)) {
    $type = 'all';
}

$query = "SELECT a.id, a.article_title, a.article_slug, a.article_type, a.excerpt, a.featured_image_url, a.publish_at, c.category_name 
          FROM articles a 
          LEFT JOIN article_categories c ON a.category_id = c.id 
          WHERE a.status = 'published'";

if ($type !== 'all') {
    $query .= " AND a.article_type = :type";
}

$query .= " ORDER BY a.publish_at DESC LIMIT 50";

$stmt = $pdo->prepare($query);
if ($type !== 'all') {
    $stmt->bindParam(':type', $type);
}
$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Split articles for the featured magazine layout if type is 'all'
$featuredArticles = [];
$feedArticles = $articles;

if ($type === 'all' && count($articles) >= 3) {
    $featuredArticles = array_splice($feedArticles, 0, 3);
} elseif ($type === 'all' && count($articles) > 0) {
    // If less than 3 but at least 1, just use 1 for featured
    $featuredArticles = array_splice($feedArticles, 0, 1);
}

// Fetch some categories for the sidebar
$sidebarCats = cAll($pdo, "SELECT category_name, COUNT(a.id) as count FROM article_categories c LEFT JOIN articles a ON a.category_id = c.id WHERE a.status='published' GROUP BY c.id ORDER BY count DESC LIMIT 5");

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>News & Insights - AdmissionSeason</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="assets/css/style.css?v=<?=time()?>">
</head>
<body class="bg-light">

<?php include 'includes/navbar.php'; ?>

<!-- ═══ TABS NAV ═══ -->
<div class="mag-tabs-wrapper">
  <div class="container">
    <div class="mag-tabs">
      <a href="?type=all" class="<?= $type === 'all' ? 'active' : '' ?>">All News</a>
      <a href="?type=news" class="<?= $type === 'news' ? 'active' : '' ?>">College News</a>
      <a href="?type=exam_update" class="<?= $type === 'exam_update' ? 'active' : '' ?>">Exam Alerts</a>
      <a href="?type=blog" class="<?= $type === 'blog' ? 'active' : '' ?>">Blogs & Tips</a>
      <a href="?type=guide" class="<?= $type === 'guide' ? 'active' : '' ?>">Guides</a>
      <a href="?type=opinion" class="<?= $type === 'opinion' ? 'active' : '' ?>">Opinions</a>
      <a href="?type=ranking" class="<?= $type === 'ranking' ? 'active' : '' ?>">Rankings</a>
    </div>
  </div>
</div>

<div class="container mag-container">

  <!-- ═══ FEATURED MAGAZINE HERO ═══ -->
  <?php if(!empty($featuredArticles)): ?>
  <section class="mag-featured-sec">
    <!-- Main Left Feature -->
    <?php 
      $main = $featuredArticles[0]; 
      $mainType = ucwords(str_replace('_', ' ', $main['article_type']));
    ?>
    <a href="news.php?article=<?=$main['article_slug']?>" class="mag-feat-main group">
      <img src="<?=cImg($main['featured_image_url'])?>" alt="<?=htmlspecialchars($main['article_title'])?>">
      <div class="mag-feat-overlay">
        <span class="mag-badge"><?=$mainType?></span>
        <h2><?=htmlspecialchars($main['article_title'])?></h2>
        <div class="mag-meta">
          <span><i class="ph ph-calendar"></i> <?=date('M d, Y', strtotime($main['publish_at']))?></span>
          <?php if(!empty($main['category_name'])): ?>
            <span><i class="ph ph-folder"></i> <?=htmlspecialchars($main['category_name'])?></span>
          <?php endif; ?>
        </div>
      </div>
    </a>

    <!-- Right Side Features (Only if we have 3 total) -->
    <?php if(count($featuredArticles) === 3): ?>
    <div class="mag-feat-side">
      <?php for($i=1; $i<=2; $i++): 
        $sub = $featuredArticles[$i];
        $subType = ucwords(str_replace('_', ' ', $sub['article_type']));
      ?>
      <a href="news.php?article=<?=$sub['article_slug']?>" class="mag-feat-sub group">
        <img src="<?=cImg($sub['featured_image_url'])?>" alt="<?=htmlspecialchars($sub['article_title'])?>">
        <div class="mag-feat-overlay">
          <span class="mag-badge small"><?=$subType?></span>
          <h3><?=htmlspecialchars($sub['article_title'])?></h3>
          <div class="mag-meta small">
            <span><i class="ph ph-calendar"></i> <?=date('M d, Y', strtotime($sub['publish_at']))?></span>
          </div>
        </div>
      </a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <!-- ═══ MAIN FEED & SIDEBAR ═══ -->
  <div class="mag-layout">
    
    <!-- Left Feed -->
    <main class="mag-feed">
      <div class="mag-feed-header">
        <h2><?= $type === 'all' ? 'Latest Updates' : ucwords(str_replace('_', ' ', $type)) . 's' ?></h2>
      </div>

      <?php if(empty($feedArticles) && empty($featuredArticles)): ?>
        <div class="mag-empty">
          <i class="ph ph-newspaper"></i>
          <p>No articles found in this category.</p>
        </div>
      <?php else: ?>
        <div class="mag-list">
          <?php foreach($feedArticles as $art): 
            $tLabel = ucwords(str_replace('_', ' ', $art['article_type']));
            $dStr = !empty($art['publish_at']) ? date('M d, Y', strtotime($art['publish_at'])) : '';
          ?>
          <a href="news.php?article=<?=$art['article_slug']?>" class="mag-card">
            <div class="mag-card-img">
              <img src="<?=cImg($art['featured_image_url'])?>" alt="<?=htmlspecialchars($art['article_title'])?>">
            </div>
            <div class="mag-card-body">
              <div class="mag-card-meta">
                <span class="mag-card-type"><?=$tLabel?></span>
                <span class="dot">•</span>
                <span><?=$dStr?></span>
              </div>
              <h4><?=htmlspecialchars($art['article_title'])?></h4>
              <p><?=htmlspecialchars(mb_strimwidth($art['excerpt'] ?? '', 0, 120, '...'))?></p>
              <?php if(!empty($art['category_name'])): ?>
                <span class="mag-card-cat"><i class="ph ph-folder-open"></i> <?=htmlspecialchars($art['category_name'])?></span>
              <?php endif; ?>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>

    <!-- Right Sidebar -->
    <aside class="mag-sidebar">
      
      <!-- Newsletter Widget -->
      <div class="mag-widget widget-newsletter">
        <i class="ph ph-envelope-simple-open bg-icon"></i>
        <h3>Stay Updated</h3>
        <p>Get the latest admission alerts and exam dates straight to your inbox.</p>
        <form class="mag-newsletter-form">
          <input type="email" placeholder="Enter your email" required>
          <button type="button">Subscribe</button>
        </form>
      </div>

      <!-- Categories Widget -->
      <div class="mag-widget">
        <h3 class="widget-title">Trending Topics</h3>
        <ul class="widget-cat-list">
          <?php foreach($sidebarCats as $sc): ?>
            <?php if($sc['count'] > 0): ?>
            <li><a href="news.php?type=all"><span><?=htmlspecialchars($sc['category_name'])?></span> <span class="count"><?=$sc['count']?></span></a></li>
            <?php endif; ?>
          <?php endforeach; ?>
          <?php if(empty($sidebarCats)): ?>
            <li><a href="#"><span>Admissions</span> <span class="count">0</span></a></li>
            <li><a href="#"><span>Exams</span> <span class="count">0</span></a></li>
          <?php endif; ?>
        </ul>
      </div>

    </aside>

  </div> <!-- /mag-layout -->

</div> <!-- /container -->

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
