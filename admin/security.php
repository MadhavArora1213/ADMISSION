<?php
/**
 * Admin Panel Security Layer
 * Rate limiting, CSRF protection, session hardening, brute-force prevention
 */

// ── Session Hardening (MUST be before session_start) ──
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly'  => true,
        'samesite'  => 'Lax'
    ]);
    session_start();
}

// ── CSRF Token ──
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals(csrf_token(), $token);
}

// ── Rate Limiter (file-based) ──
function rate_limit_key(string $action, string $identifier): string {
    return sys_get_temp_dir() . '/admission_rl_' . md5($action . '_' . $identifier);
}

function rate_limit(string $action, string $identifier, int $max_attempts, int $window_seconds): array {
    $key = rate_limit_key($action, $identifier);
    $now = time();
    $attempts = [];

    if (file_exists($key)) {
        $data = json_decode(file_get_contents($key), true);
        if (is_array($data)) {
            $attempts = array_filter($data, fn($ts) => ($now - $ts) < $window_seconds);
        }
    }

    $count = count($attempts);
    $remaining = max(0, $max_attempts - $count);
    $blocked = $count >= $max_attempts;

    if (!$blocked) {
        $attempts[] = $now;
        file_put_contents($key, json_encode(array_values($attempts)), LOCK_EX);
    }

    return [
        'blocked'   => $blocked,
        'attempts'  => $count,
        'remaining' => $remaining,
        'retry_after' => $blocked ? ($window_seconds - ($now - min($attempts))) : 0
    ];
}

function rate_limit_failed_login(string $identifier): array {
    return rate_limit('login_fail', $identifier, 5, 900); // 5 attempts per 15 min
}

function rate_limit_check_failed_login(string $identifier): bool {
    $key = rate_limit_key('login_fail', $identifier);
    if (!file_exists($key)) return false;
    $data = json_decode(file_get_contents($key), true);
    if (!is_array($data)) return false;
    $recent = array_filter($data, fn($ts) => (time() - $ts) < 900);
    return count($recent) >= 5;
}

function rate_limit_api(string $identifier): array {
    return rate_limit('api_call', $identifier, 60, 60); // 60 requests per minute
}

// ── Login Attempt Logging ──
function log_failed_attempt(string $username, string $ip) {
    $log_file = __DIR__ . '/../logs/failed_logins.log';
    $dir = dirname($log_file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $entry = date('Y-m-d H:i:s') . '|' . $ip . '|' . $username . '|' . $_SERVER['HTTP_USER_AGENT'] . "\n";
    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
}

function log_successful_login(string $user_id, string $ip) {
    $log_file = __DIR__ . '/../logs/login_success.log';
    $dir = dirname($log_file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $entry = date('Y-m-d H:i:s') . '|' . $ip . '|' . $user_id . '|' . $_SERVER['HTTP_USER_AGENT'] . "\n";
    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
}

// ── Security Headers ──
function security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (isset($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// ── Session Timeout (30 min inactivity) ──
function session_timeout_check() {
    if (!isset($_SESSION['admin_id'])) return;

    $timeout = 1800; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        header('Location: index.php?msg=timeout');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// ── Input Sanitization ──
function sanitize_input(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ── API Key Validation ──
function validate_api_key(): bool {
    $key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
    $valid_key = defined('API_KEY') ? API_KEY : 'admission_default_key_change_me';
    return hash_equals($valid_key, $key);
}

// ── IP Blocking ──
function is_ip_blocked(): bool {
    $ip = $_SERVER['REMOTE_ADDR'];
    $block_file = __DIR__ . '/../logs/blocked_ips.log';
    if (!file_exists($block_file)) return false;

    $blocked = file($block_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return in_array($ip, $blocked);
}

function block_ip(string $ip) {
    $block_file = __DIR__ . '/../logs/blocked_ips.log';
    $dir = dirname($block_file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $existing = [];
    if (file_exists($block_file)) {
        $existing = file($block_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    if (!in_array($ip, $existing)) {
        file_put_contents($block_file, $ip . "\n", FILE_APPEND | LOCK_EX);
    }
}

// ── Auto-block after 20 failed logins from same IP ──
function check_auto_block(string $ip) {
    $log_file = __DIR__ . '/../logs/failed_logins.log';
    if (!file_exists($log_file)) return;

    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recent = array_filter($lines, function($line) use ($ip) {
        $parts = explode('|', $line);
        if (count($parts) < 2) return false;
        $line_time = strtotime($parts[0]);
        return $parts[1] === $ip && (time() - $line_time) < 3600;
    });

    if (count($recent) >= 20) {
        block_ip($ip);
    }
}
