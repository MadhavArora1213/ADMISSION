<?php
/**
 * Authentication & Security Check
 * Include this at the top of every admin page (after session_start).
 * Enforces: session validation, timeout, CSRF for POST, security headers.
 */

require_once __DIR__ . '/security.php';

security_headers();
session_timeout_check();

// Require authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// CSRF verification for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        http_response_code(403);
        die('Security error: Invalid CSRF token. Please go back and try again.');
    }
}

// Log any suspicious activity
$admin_id = $_SESSION['admin_id'];
$currentPage = basename($_SERVER['PHP_SELF']);

// Check for session fixation - if IP or user agent changed, force re-login
if (isset($_SESSION['login_ip']) && $_SESSION['login_ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    header('Location: index.php?msg=session_expired');
    exit;
}

if (isset($_SESSION['login_ua']) && $_SESSION['login_ua'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header('Location: index.php?msg=session_expired');
    exit;
}

if (!isset($_SESSION['login_ua'])) {
    $_SESSION['login_ua'] = $_SERVER['HTTP_USER_AGENT'];
}
