<?php
/**
 * Trumbowyg editor upload handler.
 * Accepts images AND files via multipart upload, returns JSON:
 *   { success: bool, file: url, isImage: bool, name: string, message?: string }
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}
require_once 'db.php';

if (!isset($_FILES['fileToUpload']) || $_FILES['fileToUpload']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file received or upload error.']);
    exit;
}

$file = $_FILES['fileToUpload'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Allowlist by extension (case-insensitive)
$allowed = [
    'jpg','jpeg','png','gif','webp','svg','bmp',          // images
    'pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','rtf', // docs
    'zip','rar','7z',                                       // archives
    'mp4','webm','mp3','wav',                               // media
];
if (!in_array($ext, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'File type ".' . htmlspecialchars($ext) . '" is not allowed.']);
    exit;
}

// 25 MB cap
if ($file['size'] > 25 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large (max 25 MB).']);
    exit;
}

// MIME sanity check for images
$imageExts = ['jpg','jpeg','png','gif','webp','svg','bmp'];
$isImage   = in_array($ext, $imageExts, true);
if ($isImage) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $imgMimes = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml','image/bmp'];
    if (!in_array($mime, $imgMimes, true)) {
        echo json_encode(['success' => false, 'message' => 'Image MIME type not allowed.']);
        exit;
    }
}

$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true);
}

// Obscure, collision-proof filename
$safeName = 'editor_' . date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$dest     = $upload_dir . $safeName;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file on server.']);
    exit;
}

// Base URL matches existing convention (project root = /ADMISSION/)
$base = BASE_URL . '/uploads/';
$url  = $base . $safeName;

echo json_encode([
    'success' => true,
    'file'    => $url,
    'url'     => $url,
    'name'    => $file['name'],
    'isImage' => $isImage,
]);
