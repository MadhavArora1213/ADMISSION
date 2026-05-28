<?php
require_once 'db.php';
$sql1 = file_get_contents('universities_schema.sql');
$sql2 = file_get_contents('university_details_schema.sql');
try {
    $pdo->exec($sql1);
    echo "Successfully executed universities_schema.sql!\n";
    $pdo->exec($sql2);
    echo "Successfully executed university_details_schema.sql!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
