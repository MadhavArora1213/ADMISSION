<?php
require_once __DIR__ . '/admin/db.php';

// Check if tags table exists
$tables = $pdo->query("SHOW TABLES LIKE 'tags'")->fetchAll();
echo "Tags table exists: " . (count($tables) > 0 ? 'YES' : 'NO') . "\n\n";

if (count($tables) > 0) {
    // Show columns
    $cols = $pdo->query("SHOW COLUMNS FROM tags")->fetchAll(PDO::FETCH_ASSOC);
    echo "Tags table columns:\n";
    foreach($cols as $c) echo "  - " . $c['Field'] . " (" . $c['Type'] . ")\n";
    
    echo "\nSample data:\n";
    $rows = $pdo->query("SELECT * FROM tags LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
}

// Check what's stored in articles.tags
echo "\nArticle tags column sample:\n";
$arts = $pdo->query("SELECT article_title, tags FROM articles WHERE tags IS NOT NULL LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach($arts as $a) {
    echo $a['article_title'] . " => tags: " . $a['tags'] . "\n";
}
