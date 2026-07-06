<?php
/**
 * AdmissionSeason - Dynamic RSS Feed
 * Supports category/type filtering like sitemap
 * Usage: /news/rss, /news/rss?type=news, /news/rss?type=blog&category=engineering
 */

header('Content-Type: application/rss+xml; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: public, max-age=3600');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

require_once __DIR__ . '/../panel_cms_2847/db.php';
require_once __DIR__ . '/../includes/news_seo_helpers.php';

$baseUrl  = getBaseUrl();
$siteName = 'AdmissionSeason';

// Filter parameters
$type = trim($_GET['type'] ?? '');
$category = trim($_GET['category'] ?? '');
$validTypes = ['all', 'news', 'blog', 'guide', 'exam_update', 'opinion', 'ranking'];
if (!in_array($type, $validTypes)) $type = '';

// Build query
$query = "SELECT a.id, a.article_title, a.article_slug, a.article_type, a.excerpt,
           a.featured_image_url, a.publish_at, a.updated_at, a.content_body,
           a.custom_author_name, a.tags,
           c.category_name, c.category_slug
    FROM articles a
    LEFT JOIN article_categories c ON a.category_id = c.id
    WHERE a.status = 'published'";
$params = [];

if ($type !== '') {
    $query .= " AND a.article_type = :type";
    $params[':type'] = $type;
}
if ($category !== '') {
    $query .= " AND c.category_slug = :category";
    $params[':category'] = $category;
}

$query .= " ORDER BY a.publish_at DESC LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch tag names for each article
foreach ($articles as &$art) {
    if (!empty($art['tags'])) {
        $tagIds = json_decode($art['tags'], true);
        if (is_array($tagIds) && count($tagIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
            $tagStmt = $pdo->prepare("SELECT tag_name FROM tags WHERE id IN ($placeholders)");
            $tagStmt->execute(array_map('intval', $tagIds));
            $art['tag_names'] = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $art['tag_names'] = [];
        }
    } else {
        $art['tag_names'] = [];
    }
}
unset($art);

// Dynamic feed title/description based on filters
$typeLabels = ['news'=>'College News','blog'=>'Education Blog','guide'=>'Student Guides','exam_update'=>'Exam Updates','opinion'=>'Expert Opinions','ranking'=>'College Rankings'];
$feedTitle = $siteName . ' - ' . ($typeLabels[$type] ?? 'Education News');
$feedDesc = $type !== '' 
    ? 'Latest ' . strtolower($typeLabels[$type] ?? 'articles') . ' from AdmissionSeason.'
    : 'Latest college and university news, exam updates, admission alerts, and education tips from AdmissionSeason.';
$feedLink = $baseUrl . '/news';
if ($type !== '') $feedLink .= '?type=' . urlencode($type);
if ($category !== '') $feedLink .= ($type !== '' ? '&' : '?') . 'category=' . urlencode($category);

// Last build date
$lastBuildDate = !empty($articles[0]['publish_at'])
    ? date('r', strtotime($articles[0]['publish_at']))
    : date('r');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0"
     xmlns:media="http://search.yahoo.com/mrss/"
     xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
  <title><?= htmlspecialchars($feedTitle) ?></title>
  <link><?= $feedLink ?></link>
  <description><?= htmlspecialchars($feedDesc) ?></description>
  <language>en-in</language>
  <copyright>&amp;copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved.</copyright>
  <lastBuildDate><?= $lastBuildDate ?></lastBuildDate>
  <pubDate><?= $lastBuildDate ?></pubDate>
  <ttl>60</ttl>
  <atom:link href="<?= $baseUrl ?>/news/rss<?= !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : '' ?>" rel="self" type="application/rss+xml"/>
  <image>
    <url><?= $baseUrl ?>/assets/img/logo.png</url>
    <title><?= htmlspecialchars($siteName) ?></title>
    <link><?= $baseUrl ?></link>
    <width>600</width>
    <height>60</height>
  </image>

<?php foreach ($articles as $art):
  $artUrl = $baseUrl . '/news/' . urlencode($art['article_slug']);
  $artDate = !empty($art['publish_at']) ? date('r', strtotime($art['publish_at'])) : date('r');
  $artImage = !empty($art['featured_image_url']) 
      ? (str_starts_with($art['featured_image_url'], 'http') ? $art['featured_image_url'] : $baseUrl . '/' . ltrim($art['featured_image_url'], '/'))
      : $baseUrl . '/assets/img/logo.png';
  $artDesc = mb_strimwidth(strip_tags($art['excerpt'] ?? $art['content_body'] ?? ''), 0, 300, '...');
  $categoryName = $art['category_name'] ?? 'News';
  $authorName = $art['custom_author_name'] ?: 'AdmissionSeason Desk';
?>
  <item>
    <title><?= htmlspecialchars($art['article_title']) ?></title>
    <link><?= $artUrl ?></link>
    <guid isPermaLink="true"><?= $artUrl ?></guid>
    <description><![CDATA[<?= nl2br(htmlspecialchars($artDesc)) ?>]]></description>
    <pubDate><?= $artDate ?></pubDate>
    <dc:creator><?= htmlspecialchars($authorName) ?></dc:creator>
    <category><?= htmlspecialchars($categoryName) ?></category>

    <!-- Google News specific tags -->
    <news:news>
      <news:publication>
        <news:name><?= htmlspecialchars($siteName) ?></news:name>
        <news:language>en</news:language>
      </news:publication>
      <news:publication_date><?= date('c', strtotime($art['publish_at'])) ?></news:publication_date>
      <news:title><?= htmlspecialchars($art['article_title']) ?></news:title>
      <news:keywords><?= htmlspecialchars(implode(', ', $art['tag_names'] ?: [$categoryName, 'education', 'India'])) ?></news:keywords>
    </news:news>

    <!-- Media content for rich snippets -->
    <media:content url="<?= $artImage ?>" medium="image">
      <media:title><?= htmlspecialchars($art['article_title']) ?></media:title>
      <media:description><?= htmlspecialchars($artDesc) ?></media:description>
    </media:content>
    <media:thumbnail url="<?= $artImage ?>" width="800" height="450"/>
  </item>
<?php endforeach; ?>

</channel>
</rss>
