<?php
$pdo = new PDO('mysql:host=localhost;dbname=admission;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = ['exams', 'exam_dates', 'exam_syllabus', 'exam_cutoffs', 'exam_subjects'];
foreach ($tables as $t) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "$t: $count rows" . PHP_EOL;
    } catch (Exception $e) {
        echo "$t: ERROR - " . $e->getMessage() . PHP_EOL;
    }
}
