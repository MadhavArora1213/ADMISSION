<?php
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$baseUrl = 'https://admissionseason.com';
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
  <url>
    <loc><?= $baseUrl ?>/colleges.php</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/universities.php</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/courses.php</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/exams.php</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/rankings.php</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/compare.php</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/predictor.php</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/ask-question.php</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/news.php</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/study-abroad.php</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/reviews.php</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>

  <!-- Dynamic: Colleges -->
<?php
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';

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
<?php endforeach; ?>

  <!-- Dynamic: Universities -->
<?php
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
<?php endforeach; ?>

  <!-- Dynamic: Courses -->
<?php
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
<?php endforeach; ?>

  <!-- Dynamic: Exams -->
<?php
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
<?php endforeach; ?>

</urlset>
