<?php
require_once 'db.php';
$stmt = $pdo->query('DESCRIBE invoices');
while($r = $stmt->fetch(PDO::FETCH_ASSOC)){ echo $r['Field']." - ".$r['Type']."\n"; }
