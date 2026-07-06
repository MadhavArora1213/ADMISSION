<?php
require_once 'db.php';

try {
    $sql = file_get_contents('search_schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $count = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && strtoupper($statement) !== 'USE ADMISSION') {
            $pdo->exec($statement);
            $count++;
        }
    }
    echo "Search schema applied successfully! ($count statements executed)";
} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage());
}
?>
