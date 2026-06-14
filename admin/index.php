<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        // We now use the 'users' table which handles RBAC. Admin users must have a role_id or be super_admin.
        $stmt = $pdo->prepare('SELECT id, full_name as username, password_hash as password FROM users WHERE (email = :username OR phone = :username) AND (role_id IS NOT NULL OR is_super_admin = TRUE) AND status = "active"');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials or you do not have admin access.';
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
            background: #f8fafc;
            color: #19376d;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
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
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <i class="ph ph-user"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="ph ph-lock-key"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Login to Dashboard</button>
        </form>
    </div>

</body>
</html>
