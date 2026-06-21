<?php
$pdo = new PDO('mysql:host=localhost;dbname=admission;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = ['exams', 'exam_dates', 'exam_syllabus', 'exam_cutoffs', 'exam_media'];
foreach ($tables as $t) {
    echo "=== $t ===" . PHP_EOL;
    $cols = $pdo->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) echo "  " . str_pad($c['Field'], 30) . " " . str_pad($c['Type'], 40) . ($c['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . PHP_EOL;
    $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "  Rows: $cnt" . PHP_EOL . PHP_EOL;
}

echo "=== Current exam data ===" . PHP_EOL;
$rows = $pdo->query("SELECT id, exam_name, exam_slug FROM exams")->fetchAll();
foreach ($rows as $r) echo "  " . $r['id'] . " | " . $r['exam_name'] . " | " . $r['exam_slug'] . PHP_EOL;
