<?php
$pdo = new PDO('mysql:host=localhost;dbname=admission;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

foreach (['exam_dates', 'exam_syllabus', 'exam_cutoffs'] as $t) {
    echo "=== $t ===" . PHP_EOL;
    $cols = $pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  {$c['Field']} {$c['Type']}" . ($c['Null'] === 'YES' ? ' NULL' : '') . PHP_EOL;
    }
    echo PHP_EOL;
}
