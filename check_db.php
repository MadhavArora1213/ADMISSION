<?php
require_once 'admin/db.php';
$cols = $pdo->query("SELECT id, name, slug FROM colleges")->fetchAll(PDO::FETCH_ASSOC);
echo "Total Colleges: " . count($cols) . "\n";
foreach($cols as $c) {
    echo " - " . $c['name'] . " (" . $c['slug'] . ")\n";
}
