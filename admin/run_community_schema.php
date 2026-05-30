<?php
require 'db.php';
$sql = file_get_contents('community_schema.sql');
$pdo->exec($sql);
echo "Community Schema applied.\n";
?>
