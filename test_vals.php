<?php
require_once 'admin/db.php';
require_once 'includes/college_helpers.php';
$c = loadCollegeBySlug($pdo, 'iit-bombay');
if ($c) {
    echo "cover_image_url: " . var_export(array_key_exists('cover_image_url', $c), true) . " - value: " . var_export($c['cover_image_url'] ?? 'UNDEFINED', true) . "\n";
    echo "logo_url: " . var_export(array_key_exists('logo_url', $c), true) . " - value: " . var_export($c['logo_url'] ?? 'UNDEFINED', true) . "\n";
}
