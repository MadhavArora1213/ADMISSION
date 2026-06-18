<?php
require_once 'admin/db.php';
$res = $pdo->query("SELECT id, slug, name FROM colleges WHERE slug='iit-bombay'")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
