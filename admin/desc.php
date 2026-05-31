<?php
require 'db.php';
try {
    $pdo->exec("ALTER TABLE seo_meta ADD COLUMN meta_keywords VARCHAR(255) NULL AFTER meta_description");
    echo "Added meta_keywords\n";
} catch(Exception $e) {
    echo "Error meta_keywords: " . $e->getMessage() . "\n";
}
