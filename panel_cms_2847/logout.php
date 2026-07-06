<?php
require_once 'security.php';

// Log the logout
if (isset($_SESSION['admin_id'])) {
    $log_file = __DIR__ . '/../logs/login_success.log';
    $dir = dirname($log_file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $entry = date('Y-m-d H:i:s') . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|LOGOUT|' . $_SESSION['admin_id'] . "\n";
    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
}

// Destroy all session data
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: index.php");
exit;
