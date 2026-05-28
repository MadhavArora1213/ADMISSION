<?php
require_once 'db.php';

try {
    $sql = file_get_contents('leads_schema.sql');
    $pdo->exec($sql);
    echo "Leads schema applied successfully!";
} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage());
}
?>
