<?php
require_once 'db.php';

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
    
    // Drop foreign keys if they exist (we don't know the name, so we just change the column)
    $pdo->exec("ALTER TABLE foreign_universities CHANGE country_id country VARCHAR(255) NULL");
    echo "foreign_universities updated.\n";
    
    $pdo->exec("ALTER TABLE visa_guides CHANGE country_id country VARCHAR(255) NULL");
    echo "visa_guides updated.\n";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
