<?php
require 'db.php';
$cms_tables = ['article_categories','tags','articles','article_tags','seo_meta','media_files','article_revisions'];
$found = [];
foreach($cms_tables as $t) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$t'");
    if($stmt->rowCount() > 0) $found[] = $t . ' ✓';
    else $found[] = $t . ' ✗ MISSING';
}
echo implode("\n", $found);
?>
