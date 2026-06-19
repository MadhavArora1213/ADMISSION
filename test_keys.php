<?php
require_once 'admin/db.php';
require_once 'includes/college_helpers.php';
$c = loadCollegeBySlug($pdo, 'iit-bombay');
if ($c) {
    print_r(array_keys($c));
} else {
    echo "College not found";
}
