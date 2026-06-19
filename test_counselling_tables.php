<?php
require_once 'admin/db.php';

// consultants table structure
$stmt = $pdo->query('DESCRIBE consultants');
echo "=== CONSULTANTS ===\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// leads table structure
$stmt = $pdo->query('DESCRIBE leads');
echo "\n=== LEADS ===\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// student_profiles
$stmt = $pdo->query('DESCRIBE student_profiles');
echo "\n=== STUDENT_PROFILES ===\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// applications
$stmt = $pdo->query('DESCRIBE applications');
echo "\n=== APPLICATIONS ===\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
