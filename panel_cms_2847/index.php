<?php
require_once 'security.php';
security_headers();
session_timeout_check();

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'db.php';

$error = '';
$ip = $_SERVER['REMOTE_ADDR'];

// Check if IP is auto-blocked
check_auto_block($ip);
if (is_ip_blocked()) {
    $error = 'Your IP has been temporarily blocked due to too many failed attempts. Try again later.';
} else {
    // Check rate limit
    $rate = rate_limit_check_failed_login($ip);
    if ($rate) {
        $error = 'Too many failed login attempts. Please wait 15 minutes before trying again.';
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    if (!csrf_verify()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username/email and password.';
        } else {
            $stmt = $pdo->prepare('SELECT id, full_name as username, password_hash as password FROM users WHERE (email = :email OR phone = :phone) AND (role_id IS NOT NULL OR is_super_admin = TRUE) AND status = "active"');
            $stmt->execute(['email' => $username, 'phone' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Successful login
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                $_SESSION['login_ip'] = $ip;
                $_SESSION['login_time'] = time();

                log_successful_login($user['id'], $ip);
                header('Location: dashboard.php');
                exit;
            } else {
                // Failed login
                $result = rate_limit_failed_login($ip);
                log_failed_attempt($username, $ip);

                if ($result['blocked']) {
                    $error = 'Too many failed attempts. Your IP has been blocked for 15 minutes.';
                } else {
                    $error = 'Invalid credentials or you do not have admin access. ' . $result['remaining'] . ' attempts remaining.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | AdmissionSeason</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            background: var(--bg-light);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            background: var(--bg-white);
            padding: 48px;
            border-radius: 24px;
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 440px;
            border: 1px solid var(--border-color);
        }
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-header .logo {
            justify-content: center;
            margin-bottom: 16px;
        }
        .login-header p {
            color: var(--text-muted);
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }
        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-group i {
            position: absolute;
            left: 16px;
            color: var(--text-muted);
            font-size: 1.25rem;
        }
        .input-group input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }
        .btn-block {
            width: 100%;
            padding: 16px;
            font-size: 1.1rem;
            margin-top: 8px;
        }
        .error-message {
            background: #fef2f2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #fecaca;
        }
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <a href="../index.php" class="logo">
                <i class="ph-fill ph-graduation-cap"></i>
                Admission<span>Season</span>
            </a>
            <p>Welcome back! Please login to your account.</p>
        </div>

        <?php if ($error): ?>
        <div class="error-message">
            <i class="ph-fill ph-warning-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <i class="ph ph-user"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="ph ph-lock-key"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Login to Dashboard</button>
        </form>

        <div class="login-footer">
            Secured with rate limiting and session protection
        </div>
    </div>

</body>
</html>
