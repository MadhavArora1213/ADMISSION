<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/admin/db.php';

if (!function_exists('cAll')) {
    function cAll(PDO $pdo, string $sql): array {
        try { return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
        catch (Exception $e) { return []; }
    }
}
if (!function_exists('cImg')) {
    function cImg(?string $url = ''): string {
        return $url ?: 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80';
    }
}

// Get slug from URL
$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    header('Location: news.php');
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

// Increment view count
$pdo->prepare("UPDATE articles SET view_count = view_count + 1 WHERE id = ?")->execute([$article['id']]);

// Fetch related articles (same type or same category, exclude current)
$related = $pdo->prepare("
    SELECT a.id, a.article_title, a.article_slug, a.article_type, a.featured_image_url, a.publish_at, c.category_name
    FROM articles a
    LEFT JOIN article_categories c ON a.category_id = c.id
    WHERE a.status = 'published'
      AND a.id != ?
      AND (a.article_type = ? OR a.category_id = ?)
    ORDER BY a.publish_at DESC
    LIMIT 4
");
$related->execute([$article['id'], $article['article_type'], $article['category_id']]);
$relatedArticles = $related->fetchAll(PDO::FETCH_ASSOC);

// Sidebar: Popular news
$popular = $pdo->query("
    SELECT article_title, article_slug, featured_image_url, publish_at
    FROM articles WHERE status = 'published'
    ORDER BY view_count DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Sidebar: Categories
$sidebarCats = $pdo->query("
    SELECT c.category_name, COUNT(a.id) as count 
    FROM article_categories c 
    LEFT JOIN articles a ON a.category_id = c.id AND a.status='published' 
    GROUP BY c.id ORDER BY count DESC LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$typeLabel    = ucwords(str_replace('_', ' ', $article['article_type']));
$publishDate  = !empty($article['publish_at']) ? date('F d, Y', strtotime($article['publish_at'])) : '';
$readingTime  = $article['reading_time_mins'] ?? ceil(str_word_count(strip_tags($article['content_body'] ?? '')) / 200);
$tags         = !empty($article['tags']) ? json_decode($article['tags'], true) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($article['article_title']) ?> - AdmissionSeason</title>
  <meta name="description" content="<?= htmlspecialchars($article['excerpt'] ?? '') ?>">

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
        <a href="news.php"><?= htmlspecialchars($article['category_name']) ?></a>
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
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>" target="_blank" class="share-btn share-fb"><i class="ph ph-facebook-logo"></i> Facebook</a>
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($article['article_title']) ?>" target="_blank" class="share-btn share-tw"><i class="ph ph-x-logo"></i> Twitter</a>
        <a href="https://api.whatsapp.com/send?text=<?= urlencode($article['article_title'].' http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>" target="_blank" class="share-btn share-wa"><i class="ph ph-whatsapp-logo"></i> WhatsApp</a>
      </div>

      <!-- ═══ NPS RATING WIDGET ═══ -->
      <div class="nps-widget" id="npsWidget">
        <p class="nps-question">How likely are you to recommend <strong>AdmissionSeason</strong> to a friend or a colleague?</p>
        <div class="nps-scale">
          <?php for($i = 1; $i <= 10; $i++): ?>
          <button class="nps-btn" data-score="<?= $i ?>" onclick="selectNps(this, <?= $i ?>)"><?= $i ?></button>
          <?php endfor; ?>
        </div>
        <div class="nps-labels">
          <span>Not so likely</span>
          <span>Highly Likely</span>
        </div>
        <div class="nps-thanks" id="npsThanks" style="display:none;">
          <i class="ph ph-smiley"></i>
          <p>Thank you for your feedback!</p>
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
          ?>
          <a href="news_details.php?slug=<?= urlencode($rel['article_slug']) ?>" class="art-rel-card">
            <div class="art-rel-img">
              <img src="<?= cImg($rel['featured_image_url']) ?>" alt="<?= htmlspecialchars($rel['article_title']) ?>">
            </div>
            <div class="art-rel-body">
              <span class="art-rel-type"><?= $relType ?></span>
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
              <a href="news.php">
                <span><i class="ph ph-caret-right"></i> <?= htmlspecialchars($sc['category_name']) ?></span>
                <span>(<?= $sc['count'] ?>)</span>
              </a>
            </li>
            <?php endif; ?>
          <?php endforeach; ?>
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
function selectNps(btn, score) {
  // Remove selected from all
  document.querySelectorAll('.nps-btn').forEach(b => b.classList.remove('selected'));
  
  // Mark this button selected
  btn.classList.add('selected');

  // After short delay, show thank you
  setTimeout(() => {
    document.querySelector('.nps-scale').style.opacity = '0.5';
    document.querySelector('.nps-scale').style.pointerEvents = 'none';
    document.querySelector('.nps-labels').style.display = 'none';
    document.getElementById('npsThanks').style.display = 'block';
  }, 600);
}
</script>
</body>
</html>
