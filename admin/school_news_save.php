<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
}

$id = $_POST['id'] ?? null;
$school_id = $_POST['school_id'] ?? '';
$is_edit = $id !== null;

$data = [
    'school_id' => $school_id,
    'title' => $_POST['title'],
    'excerpt' => $_POST['excerpt'] ?: null,
    'content' => $_POST['content'] ?: null,
    'event_date' => $_POST['event_date'] ?: null,
    'status' => $_POST['status'] ?? 'draft',
];

if ($is_edit) {
    $fields = []; foreach ($data as $k=>$v) $fields[] = "$k = :$k";
    $data['id'] = $id;
    $pdo->prepare("UPDATE school_news SET " . implode(", ", $fields) . " WHERE id = :id AND school_id = :school_id")->execute($data);
} else {
    $data['id'] = generateUUID();
    $keys = array_keys($data);
    $pdo->prepare("INSERT INTO school_news (" . implode(",", $keys) . ") VALUES (:" . implode(",:", $keys) . ")")->execute($data);
}

header("Location: school_news.php?school_id=$school_id&msg=saved");
exit;
