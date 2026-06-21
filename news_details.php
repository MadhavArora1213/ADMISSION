<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/admin/db.php';

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
$readingTime  = $article['reading_time_mins'] ?? max(1, (int)ceil(str_word_count(strip_tags($article['content_body'] ?? '')) / 200));

$shareUrl   = 'https://' . $_SERVER['HTTP_HOST'] . '/ADMISSION/news_details.php?slug=' . urlencode($slug);
$shareImage = cImg($article['featured_image_url'] ?? '');
$shareDesc  = mb_strimwidth(strip_tags($article['excerpt'] ?? $article['content_body'] ?? ''), 0, 160, '...');
$siteName   = 'AdmissionSeason';

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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($article['article_title']) ?> - <?= $siteName ?></title>
  <meta name="description" content="<?= htmlspecialchars($shareDesc) ?>">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="<?= $shareUrl ?>">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?= $shareUrl ?>">
  <meta property="og:title" content="<?= htmlspecialchars($article['article_title']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($shareDesc) ?>">
  <meta property="og:image" content="<?= $shareImage ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:site_name" content="<?= $siteName ?>">
  <meta property="og:locale" content="en_IN">
  <?php if (!empty($article['category_name'])): ?>
  <meta property="article:section" content="<?= htmlspecialchars($article['category_name']) ?>">
  <?php endif; ?>
  <?php if ($publishDate): ?>
  <meta property="article:published_time" content="<?= date('c', strtotime($article['publish_at'])) ?>">
  <?php endif; ?>

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= $shareUrl ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($article['article_title']) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($shareDesc) ?>">
  <meta name="twitter:image" content="<?= $shareImage ?>">
  <meta name="twitter:site" content="@AdmissionSeason">

  <!-- Structured Data -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $article['article_title'],
    'description' => $shareDesc,
    'datePublished' => $article['publish_at'] ?? '',
    'author' => ['@type' => 'Organization', 'name' => $article['custom_author_name'] ?: $siteName],
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
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-fb"><i class="ph ph-facebook-logo"></i> Facebook</a>
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($article['article_title'] . ' — ' . $siteName) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-tw"><i class="ph ph-x-logo"></i> X</a>
        <a href="https://api.whatsapp.com/send?text=<?= urlencode($article['article_title'] . ' ' . $shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-wa"><i class="ph ph-whatsapp-logo"></i> WhatsApp</a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-li"><i class="ph ph-linkedin-logo"></i> LinkedIn</a>
        <a href="https://t.me/share/url?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($article['article_title']) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-tg"><i class="ph ph-telegram-logo"></i> Telegram</a>
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
