<?php
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

require_once __DIR__ . '/panel_cms_2847/db.php';
require_once __DIR__ . '/includes/college_helpers.php';
require_once __DIR__ . '/includes/news_seo_helpers.php';
$baseUrl = getBaseUrl();
$today   = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

  <!-- Main Pages -->
  <url>
    <loc><?= $baseUrl ?>/</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>

  <!-- Colleges (main + filter variants) -->
  <url>
    <loc><?= $baseUrl ?>/colleges</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/colleges?type=govt</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/colleges?type=private</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/colleges?type=deemed</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/colleges?type=autonomous</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>

  <!-- Universities (main + filter variants) -->
  <url>
    <loc><?= $baseUrl ?>/universities</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/universities?type=govt</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/universities?type=private</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/universities?type=deemed</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/universities?type=autonomous</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/courses</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/courses?level=UG</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/courses?level=PG</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/courses?level=Diploma</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/courses?level=PhD</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/exams</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/exams?level=national</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/exams?level=state</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/exams?level=university</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/exams?mode=online</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/exams?mode=offline</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/rankings</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/compare</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/predictor</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/ask-question</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/news</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/news?type=news</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/news?type=exam_update</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/news?type=blog</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/news?type=guide</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/news?type=opinion</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/news?type=ranking</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/news/rss</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>hourly</changefreq>
    <priority>0.6</priority>
  </url>

  <!-- Dynamic: State-wise College Pages -->
<?php try {
$statesList = $pdo->query("SELECT s.id, s.name, COUNT(c.id) AS cnt FROM states s LEFT JOIN colleges c ON c.state_id = s.id AND c.status='active' GROUP BY s.id, s.name HAVING cnt > 0 ORDER BY cnt DESC, s.name ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($statesList as $sl):
?>
  <url>
    <loc><?= $baseUrl ?>/colleges?state=<?= $sl['id'] ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
<?php endforeach;
} catch (Throwable $e) {} ?>

  <url>
    <loc><?= $baseUrl ?>/study-abroad</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/study-abroad?tab=universities</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/study-abroad?tab=visas</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/study-abroad?tab=consultants</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/reviews</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/careers</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/careers?stream=Science</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/careers?stream=Commerce</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/careers?stream=Humanities</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/community</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/community?tab=qna</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/community?tab=discussions</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/community?tab=unanswered</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.5</priority>
  </url>

<!-- Dynamic: Question Pages -->
<?php try {
$questions = $pdo->query("SELECT slug, created_at FROM questions WHERE status IN ('open','answered','resolved') AND slug IS NOT NULL AND slug != '' ORDER BY created_at DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
foreach ($questions as $q):
  $mod = !empty($q['created_at']) ? date('Y-m-d', strtotime($q['created_at'])) : $today;
?>
  <url>
    <loc><?= $baseUrl ?>/question/<?= htmlspecialchars($q['slug']) ?></loc>
    <lastmod><?= $mod ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>
<?php endforeach;
} catch (Throwable $e) {} ?>

  <url>
    <loc><?= $baseUrl ?>/counselling</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>

  <!-- Dynamic: Colleges -->
<?php try {
$colleges = $pdo->query("SELECT slug, updated_at FROM colleges WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($colleges as $c):
  $mod = !empty($c['updated_at']) ? date('Y-m-d', strtotime($c['updated_at'])) : $today;
?>
  <url>
    <loc><?= $baseUrl ?>/college/<?= htmlspecialchars($c['slug']) ?></loc>
    <lastmod><?= $mod ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
<?php endforeach;
} catch (Throwable $e) {} ?>

  <!-- Dynamic: Universities -->
<?php try {
$universities = $pdo->query("SELECT slug, updated_at FROM universities WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($universities as $u):
  $mod = !empty($u['updated_at']) ? date('Y-m-d', strtotime($u['updated_at'])) : $today;
?>
  <url>
    <loc><?= $baseUrl ?>/university/<?= htmlspecialchars($u['slug']) ?></loc>
    <lastmod><?= $mod ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
<?php endforeach;
} catch (Throwable $e) {} ?>

  <!-- Dynamic: Courses -->
<?php try {
$courses = $pdo->query("SELECT slug, updated_at FROM courses WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($courses as $co):
  $mod = !empty($co['updated_at']) ? date('Y-m-d', strtotime($co['updated_at'])) : $today;
?>
  <url>
    <loc><?= $baseUrl ?>/course/<?= htmlspecialchars($co['slug']) ?></loc>
    <lastmod><?= $mod ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach;
} catch (Throwable $e) {} ?>

<!-- Dynamic: Course Category Pages -->
<?php try {
$courseCats = $pdo->query("SELECT DISTINCT course_category FROM courses WHERE status='active' AND course_category IS NOT NULL AND course_category != '' ORDER BY course_category ASC")->fetchAll(PDO::FETCH_COLUMN);
foreach ($courseCats as $ccat):
?>
  <url>
    <loc><?= $baseUrl ?>/courses?category=<?= urlencode($ccat) ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach;
} catch (Throwable $e) {} ?>

  <!-- Dynamic: Exams -->
<?php try {
$exams = $pdo->query("SELECT slug, updated_at FROM exams WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($exams as $ex):
  $mod = !empty($ex['updated_at']) ? date('Y-m-d', strtotime($ex['updated_at'])) : $today;
?>
  <url>
    <loc><?= $baseUrl ?>/exam/<?= htmlspecialchars($ex['slug']) ?></loc>
    <lastmod><?= $mod ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach;
} catch (Throwable $e) {} ?>

<!-- Dynamic: News Articles -->
<?php try {
$newsArticles = $pdo->query("SELECT article_slug, publish_at, updated_at, category_slug FROM articles WHERE status='published' ORDER BY publish_at DESC LIMIT 1000")->fetchAll(PDO::FETCH_ASSOC);
foreach ($newsArticles as $na):
  $mod = !empty($na['updated_at']) ? date('Y-m-d', strtotime($na['updated_at'])) : (!empty($na['publish_at']) ? date('Y-m-d', strtotime($na['publish_at'])) : $today);
?>
  <url>
    <loc><?= $baseUrl ?>/news/<?= htmlspecialchars($na['article_slug']) ?></loc>
    <lastmod><?= $mod ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach;
} catch (Throwable $e) {} ?>

<!-- Dynamic: News Category Pages -->
<?php try {
$newsCats = $pdo->query("SELECT category_slug, category_name FROM article_categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($newsCats as $nc):
?>
  <url>
    <loc><?= $baseUrl ?>/news?category=<?= htmlspecialchars($nc['category_slug']) ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach;
} catch (Throwable $e) {} ?>

<!-- Dynamic: Career Pages -->
<?php try {
$careers = $pdo->query("SELECT career_slug, updated_at FROM careers WHERE status='active' ORDER BY career_name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($careers as $cr):
  $mod = !empty($cr['updated_at']) ? date('Y-m-d', strtotime($cr['updated_at'])) : $today;
?>
  <url>
    <loc><?= $baseUrl ?>/career/<?= htmlspecialchars($cr['career_slug']) ?></loc>
    <lastmod><?= $mod ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
<?php endforeach;
} catch (Throwable $e) {} ?>

<!-- Dynamic: Foreign Universities -->
<?php try {
$foreignUnis = $pdo->query("SELECT slug, updated_at FROM foreign_universities WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($foreignUnis as $fu):
  $mod = !empty($fu['updated_at']) ? date('Y-m-d', strtotime($fu['updated_at'])) : $today;
?>
  <url>
    <loc><?= $baseUrl ?>/foreign-university/<?= htmlspecialchars($fu['slug']) ?></loc>
    <lastmod><?= $mod ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach;
} catch (Throwable $e) {} ?>

<!-- Dynamic: Visa Guides -->
<?php try {
$visaGuides = $pdo->query("SELECT slug, updated_at FROM visa_guides WHERE status='active' ORDER BY country_name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($visaGuides as $vg):
  $mod = !empty($vg['updated_at']) ? date('Y-m-d', strtotime($vg['updated_at'])) : $today;
?>
  <url>
    <loc><?= $baseUrl ?>/visa-guide/<?= htmlspecialchars($vg['slug']) ?></loc>
    <lastmod><?= $mod ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
<?php endforeach;
} catch (Throwable $e) {} ?>

</urlset>
