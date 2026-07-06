<?php
/**
 * Google News Sitemap
 * Only includes articles published in the last 48 hours
 * Compliant with: https://developers.google.com/search/docs/specialty/google-news
 */

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

require_once __DIR__ . '/panel_cms_2847/db.php';
require_once __DIR__ . '/includes/news_seo_helpers.php';

$baseUrl = getBaseUrl();
$today   = date('Y-m-d');

// Google News sitemap requires articles from last 48 hours
$articles = $pdo->prepare("
    SELECT a.article_title, a.article_slug, a.publish_at, a.updated_at,
           a.custom_author_name, a.tags,
           c.category_name
    FROM articles a
    LEFT JOIN article_categories c ON a.category_id = c.id
    WHERE a.status = 'published'
      AND a.publish_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
    ORDER BY a.publish_at DESC
");
$articles->execute();
$articleList = $articles->fetchAll(PDO::FETCH_ASSOC);

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">

<?php foreach ($articleList as $art):
  $artUrl = $baseUrl . '/news/' . urlencode($art['article_slug']);
  $artDate = date('c', strtotime($art['publish_at']));
  $keywords = [];
  if (!empty($art['tags'])) {
      $tagIds = json_decode($art['tags'], true);
      if (is_array($tagIds)) {
          $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
          $tagStmt = $pdo->prepare("SELECT tag_name FROM tags WHERE id IN ($placeholders)");
          $tagStmt->execute(array_map('intval', $tagIds));
          $keywords = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
      }
  }
  if (empty($keywords) && !empty($art['category_name'])) {
      $keywords = [$art['category_name'], 'education', 'India'];
  }
  $authorName = $art['custom_author_name'] ?: 'AdmissionSeason Desk';
?>
  <url>
    <loc><?= $artUrl ?></loc>
    <news:news>
      <news:publication>
        <news:name>AdmissionSeason</news:name>
        <news:language>en</news:language>
      </news:publication>
      <news:publication_date><?= $artDate ?></news:publication_date>
      <news:title><?= htmlspecialchars($art['article_title']) ?></news:title>
      <news:keywords><?= htmlspecialchars(implode(', ', $keywords)) ?></news:keywords>
    </news:news>
  </url>
<?php endforeach; ?>

</urlset>
