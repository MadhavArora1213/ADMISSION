<?php
require_once 'db.php';

try {
    $sql = file_get_contents('seo_schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $count = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && strtoupper($statement) !== 'USE ADMISSION') {
            try {
                $pdo->exec($statement);
                $count++;
            } catch (PDOException $e) {
                echo "Skipped or Error on a statement: " . $e->getMessage() . "\n";
            }
        }
    }
    echo "SEO schema applied successfully! ($count statements executed)\n";
} catch (Exception $e) {
    die("ERROR: " . $e->getMessage());
}
?>
