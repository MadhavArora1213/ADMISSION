<?php
/**
 * Security Configuration Check
 * Run this once to verify your security setup.
 * DELETE THIS FILE after verification in production.
 */
session_start();

if (!isset($_SESSION['admin_id'])) {
    die('Admin access required.');
}

require_once 'db.php';

$checks = [];
$pass = 0;
$fail = 0;

// 1. Check .env file
$env_exists = file_exists(__DIR__ . '/../.env');
$env_detail = $env_exists ? 'Found' : 'Missing';
$checks[] = ['name' => 'Environment file (.env)', 'status' => $env_exists, 'detail' => $env_detail];

// 2. Check security.php exists
$security_exists = file_exists(__DIR__ . '/security.php');
$sec_detail = $security_exists ? 'Installed' : 'Missing';
$checks[] = ['name' => 'Security module', 'status' => $security_exists, 'detail' => $sec_detail];

// 3. Check auth_check.php exists
$auth_exists = file_exists(__DIR__ . '/auth_check.php');
$auth_detail = $auth_exists ? 'Installed' : 'Missing';
$checks[] = ['name' => 'Auth check module', 'status' => $auth_exists, 'detail' => $auth_detail];

// 4. Check logs directory
$logs_dir = is_dir(__DIR__ . '/../logs');
$logs_detail = $logs_dir ? 'Exists' : 'Missing';
$checks[] = ['name' => 'Logs directory', 'status' => $logs_dir, 'detail' => $logs_detail];

// 5. Check logs .htaccess
$logs_htaccess = file_exists(__DIR__ . '/../logs/.htaccess');
$htaccess_detail = $logs_htaccess ? 'Protected' : 'Unprotected';
$checks[] = ['name' => 'Logs directory protection', 'status' => $logs_htaccess, 'detail' => $htaccess_detail];

// 6. Check admin .htaccess
$admin_htaccess = file_exists(__DIR__ . '/.htaccess');
$admin_ht_detail = $admin_htaccess ? 'Present' : 'Missing';
$checks[] = ['name' => 'Admin .htaccess', 'status' => $admin_htaccess, 'detail' => $admin_ht_detail];

// 7. Check CSRF in login
$login_content = file_get_contents(__DIR__ . '/index.php');
$has_csrf = strpos($login_content, 'csrf_verify') !== false;
$csrf_detail = $has_csrf ? 'Enabled' : 'Missing';
$checks[] = ['name' => 'CSRF protection (login)', 'status' => $has_csrf, 'detail' => $csrf_detail];

// 8. Check rate limiting
$has_rate_limit = strpos($login_content, 'rate_limit') !== false;
$rl_detail = $has_rate_limit ? 'Enabled' : 'Missing';
$checks[] = ['name' => 'Rate limiting (login)', 'status' => $has_rate_limit, 'detail' => $rl_detail];

// 9. Check password hashing
$has_password_hash = strpos($login_content, 'password_verify') !== false;
$pw_detail = $has_password_hash ? 'Using bcrypt' : 'Not using password_verify';
$checks[] = ['name' => 'Password hashing', 'status' => $has_password_hash, 'detail' => $pw_detail];

// 10. Check PDO prepared statements
$db_content = file_get_contents(__DIR__ . '/db.php');
$has_prepared = strpos($db_content, 'ATTR_EMULATE_PREPARES') !== false;
$prep_detail = $has_prepared ? 'Native prepared statements' : 'Using emulated prepares';
$checks[] = ['name' => 'PDO prepared statements', 'status' => $has_prepared, 'detail' => $prep_detail];

// 11. Check session security
$security_content = file_get_contents(__DIR__ . '/security.php');
$has_session = strpos($security_content, 'httponly') !== false;
$sess_detail = $has_session ? 'Enabled' : 'Missing';
$checks[] = ['name' => 'Session security (httponly)', 'status' => $has_session, 'detail' => $sess_detail];

// 12. Check IP blocking
$has_ip_block = strpos($security_content, 'is_ip_blocked') !== false;
$ip_detail = $has_ip_block ? 'Enabled' : 'Missing';
$checks[] = ['name' => 'IP blocking', 'status' => $has_ip_block, 'detail' => $ip_detail];

foreach ($checks as $c) {
    if ($c['status']) $pass++;
    else $fail++;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Security Check | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body{background:#f8fafc;padding:40px;font-family:'Inter',sans-serif}
        .container{max-width:700px;margin:0 auto}
        .summary{display:flex;gap:20px;margin-bottom:24px}
        .summary-card{padding:16px 24px;border-radius:12px;border:1px solid #e2e8f0;background:#fff}
        .summary-card.pass{border-color:#bbf7d0;background:#f0fdf4}
        .summary-card.fail{border-color:#fecaca;background:#fef2f2}
        .check-list{background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden}
        .check-item{display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid #f1f5f9}
        .check-item:last-child{border-bottom:none}
        .check-icon{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
        .check-icon.pass{background:#dcfce7;color:#16a34a}
        .check-icon.fail{background:#fee2e2;color:#dc2626}
        .check-name{font-weight:600;font-size:.9rem}
        .check-detail{font-size:.8rem;color:#64748b}
    </style>
</head>
<body>
    <div class="container">
        <h1 style="font-size:1.5rem;font-weight:800;">Security Configuration Check</h1>
        <p style="color:#64748b;">Verify your admin panel security setup.</p>

        <div class="summary">
            <div class="summary-card pass">
                <div style="font-size:1.5rem;font-weight:800;color:#16a34a;"><?= $pass ?></div>
                <div style="font-size:.8rem;color:#16a34a;font-weight:600;">Passed</div>
            </div>
            <div class="summary-card fail">
                <div style="font-size:1.5rem;font-weight:800;color:#dc2626;"><?= $fail ?></div>
                <div style="font-size:.8rem;color:#dc2626;font-weight:600;">Failed</div>
            </div>
        </div>

        <div class="check-list">
            <?php foreach ($checks as $c): ?>
            <div class="check-item">
                <div class="check-icon <?= $c['status'] ? 'pass' : 'fail' ?>"><?= $c['status'] ? '&#10003;' : '&#10007;' ?></div>
                <div>
                    <div class="check-name"><?= htmlspecialchars($c['name']) ?></div>
                    <div class="check-detail"><?= htmlspecialchars($c['detail']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:24px;padding:16px;background:#fffbeb;border:1px solid #fef08a;border-radius:8px;font-size:.85rem;color:#854d0e;">
            <strong>Important:</strong> Delete this file (security_check.php) after verification.
        </div>
    </div>
</body>
</html>
