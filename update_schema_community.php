<?php
declare(strict_types=1);
require_once __DIR__ . '/panel_cms_2847/db.php';

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM questions LIKE 'upvotes'");
    $column = $stmt->fetch();

    if (!$column) {
        $pdo->exec("ALTER TABLE questions ADD COLUMN upvotes INT DEFAULT 0");
        echo "Successfully added 'upvotes' column to 'questions' table.\n";
    } else {
        echo "'upvotes' column already exists in 'questions' table.\n";
    }
} catch (Exception $e) {
    echo "Error updating questions schema: " . $e->getMessage() . "\n";
}
?>
