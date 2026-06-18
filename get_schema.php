<?php
require_once 'admin/db.php';
$cols = $pdo->query("DESCRIBE college_updates")->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $c) echo $c['Field'].' | '.$c['Type'].' | '.$c['Null'].' | '.$c['Default']."\n";
