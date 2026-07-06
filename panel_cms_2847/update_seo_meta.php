<?php
require_once 'db.php';

try {
    // Add missing columns to seo_meta
    $cols = [
        "og_title VARCHAR(255) NULL",
        "og_description TEXT NULL",
        "og_image VARCHAR(255) NULL",
        "schema_type ENUM('Article','NewsArticle','HowTo','FAQ') NULL",
        "schema_json JSON NULL",
        "primary_keyword VARCHAR(255) NULL",
        "keyword_density FLOAT NULL"
    ];

    foreach ($cols as $col) {
        $colName = explode(' ', $col)[0];
        // Check if column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM seo_meta LIKE '$colName'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE seo_meta ADD COLUMN $col");
            echo "Added $colName\n";
        }
    }
    echo "SEO Meta table updated successfully.\n";
} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage());
}
?>
