<?php
require 'db.php';

$checks = [];

// Check tables exist
$tables = ['articles', 'article_categories', 'article_tags', 'tags', 'seo_meta', 'media_files', 'article_revisions'];
foreach($tables as $t) {
    $r = $pdo->query("SHOW TABLES LIKE '$t'")->rowCount();
    $checks["TABLE: $t"] = $r > 0 ? '✅' : '❌ MISSING';
}

// Check articles columns
$article_cols = ['article_title','article_slug','article_type','content_body','excerpt',
    'featured_image_url','featured_image_alt','author_id','editor_id','category_id',
    'tags','status','publish_at','reading_time_mins','view_count','share_count',
    'draft_saved_at','auto_save_version','scheduled_at','unpublish_at'];
foreach($article_cols as $col) {
    $r = $pdo->query("SHOW COLUMNS FROM articles LIKE '$col'")->rowCount();
    $checks["  articles.$col"] = $r > 0 ? '✅' : '❌ MISSING';
}

// Check seo_meta columns
$seo_cols = ['meta_title','meta_description','og_title','og_description','og_image',
    'canonical_url','schema_type','schema_json','primary_keyword','keyword_density'];
foreach($seo_cols as $col) {
    $r = $pdo->query("SHOW COLUMNS FROM seo_meta LIKE '$col'")->rowCount();
    $checks["  seo_meta.$col"] = $r > 0 ? '✅' : '❌ MISSING';
}

// Check media_files columns
$media_cols = ['file_name','file_url','cdn_url','file_type','file_size_kb',
    'dimensions_json','alt_text','uploaded_by','folder_path','webp_url'];
foreach($media_cols as $col) {
    $r = $pdo->query("SHOW COLUMNS FROM media_files LIKE '$col'")->rowCount();
    $checks["  media_files.$col"] = $r > 0 ? '✅' : '❌ MISSING';
}

// Check article_revisions columns
$rev_cols = ['article_id','version','user_id','content_snapshot','saved_at'];
foreach($rev_cols as $col) {
    $r = $pdo->query("SHOW COLUMNS FROM article_revisions LIKE '$col'")->rowCount();
    $checks["  article_revisions.$col"] = $r > 0 ? '✅' : '❌ MISSING';
}

echo "<pre style='font-family:monospace; font-size:14px; line-height:1.8;'>";
echo "=== CMS & ARTICLES — DATABASE VERIFICATION ===\n\n";
$missing = 0;
foreach($checks as $label => $status) {
    echo str_pad($label, 45) . " $status\n";
    if(strpos($status,'❌') !== false) $missing++;
}
echo "\n";
echo $missing === 0 
    ? "✅ ALL CHECKS PASSED — Database fully implemented!\n"
    : "❌ $missing item(s) MISSING — Needs fixing!\n";
echo "</pre>";
?>
