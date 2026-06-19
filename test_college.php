<?php
$_GET['slug'] = 'iit-bombay';
$_GET['tab'] = 'courses';
ob_start();
include 'college.php';
$html = ob_get_clean();
echo "HTML Length: " . strlen($html) . "\n";
if (strpos($html, 'B.Tech') !== false) {
    echo "Found Course: B.Tech\n";
}
if (strpos($html, 'M.Tech') !== false) {
    echo "Found Course: M.Tech\n";
}
