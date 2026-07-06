<?php
require 'db.php';

$checks = [];

// Tables to check
$tables = ['seo_meta', 'redirects', 'sitemaps', 'internal_links', 'seo_templates'];

foreach($tables as $t) {
    $r = $pdo->query("SHOW TABLES LIKE '$t'")->rowCount();
    $checks["TABLE: $t"] = $r > 0 ? '✅' : '❌ MISSING';
}

// Columns to check
$columns = [
    'seo_meta' => ['page_type', 'page_id', 'meta_title', 'meta_description', 'og_image', 'canonical_url', 'robots_directive', 'schema_type', 'schema_json', 'hreflang', 'last_crawled_at', 'google_index_status'],
    'redirects' => ['redirect_from', 'redirect_to', 'redirect_type', 'redirect_reason', 'hits', 'is_active'],
    'sitemaps' => ['sitemap_name', 'sitemap_url', 'sitemap_type', 'last_generated_at', 'url_count'],
    'internal_links' => ['link_source_page', 'link_target_page', 'anchor_text', 'is_broken'],
    'seo_templates' => ['template_name', 'template_slug_pattern', 'data_source', 'title_template', 'description_template', 'is_active', 'pages_generated']
];

foreach ($columns as $table => $cols) {
    foreach ($cols as $col) {
        $r = $pdo->query("SHOW COLUMNS FROM $table LIKE '$col'")->rowCount();
        $checks["  $table.$col"] = $r > 0 ? '✅' : '❌ MISSING';
    }
}

echo "<pre style='font-family:monospace; font-size:14px; line-height:1.8;'>";
echo "=== SEO MANAGEMENT — VERIFICATION ===\n\n";
$missing = 0;
foreach($checks as $label => $status) {
    echo str_pad($label, 45) . " $status\n";
    if(strpos($status,'❌') !== false) $missing++;
}
echo "\n";
echo $missing === 0 
    ? "✅ ALL CHECKS PASSED — SEO Module fully implemented in DB!\n"
    : "❌ $missing item(s) MISSING — Needs fixing!\n";
echo "</pre>";
?>
