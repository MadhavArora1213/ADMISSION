<?php
require 'db.php';
$sql = file_get_contents('notifications_schema.sql');
$pdo->exec($sql);
echo "Notifications Schema applied.\n";
?>
