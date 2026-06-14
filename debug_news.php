<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/admin/db.php';

// Check if navbar.php exists
echo 'navbar exists: ' . (file_exists(__DIR__.'/includes/navbar.php') ? 'YES' : 'NO') . "\n";
echo 'footer exists: ' . (file_exists(__DIR__.'/includes/footer.php') ? 'YES' : 'NO') . "\n";

// Check session/other issues
echo 'PHP version: ' . PHP_VERSION . "\n";

// Test the full query
$slug = 'top-10-engineering-colleges-2026-v2';
$stmt = $pdo->prepare("SELECT a.*, c.category_name, c.category_slug FROM articles a LEFT JOIN article_categories c ON a.category_id = c.id WHERE a.article_slug = ? AND a.status = 'published' LIMIT 1");
$stmt->execute([$slug]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);
echo 'Article found: ' . ($article ? 'YES' : 'NO') . "\n";

// Test related query
$related = $pdo->prepare("SELECT a.id, a.article_title, a.article_slug, a.article_type, a.featured_image_url, a.publish_at, c.category_name FROM articles a LEFT JOIN article_categories c ON a.category_id = c.id WHERE a.status = 'published' AND a.id != ? AND (a.article_type = ? OR a.category_id = ?) ORDER BY a.publish_at DESC LIMIT 4");
$related->execute([$article['id'], $article['article_type'], $article['category_id']]);
$relatedArticles = $related->fetchAll(PDO::FETCH_ASSOC);
echo 'Related count: ' . count($relatedArticles) . "\n";

// Test popular query
$popular = $pdo->query("SELECT article_title, article_slug, featured_image_url, publish_at FROM articles WHERE status = 'published' ORDER BY view_count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo 'Popular count: ' . count($popular) . "\n";

echo "\nAll good! Checking includes...";
