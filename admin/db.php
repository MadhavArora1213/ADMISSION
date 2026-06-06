<?php
// =============================================
// DO NOT CHANGE THESE — Production DB credentials
// For local dev: use a separate local config
// =============================================
$host   = 'localhost';
$user   = 'u642624414_db_user';
$pass   = '6NY@D$f#';
$dbname = 'u642624414_edusearch';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed. Please try again later.");
}
?>
