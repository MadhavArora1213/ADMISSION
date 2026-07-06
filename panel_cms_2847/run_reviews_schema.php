<?php
require_once 'db.php';

try {
    $sql = file_get_contents('reviews_schema.sql');
    $pdo->exec($sql);
    echo "Reviews schema applied successfully!";
} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage());
}
?>
