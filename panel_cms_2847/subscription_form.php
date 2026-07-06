<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sub = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE id = ?");
    $stmt->execute([$id]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sub) { die("Subscription not found."); }
}

$error = '';
$success = '';

$colleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$plans = $pdo->query("SELECT id, plan_name FROM subscription_plans ORDER BY plan_name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $college_id = $_POST['college_id'];
    $plan_id = (int)$_POST['plan_id'];
    $amount = $_POST['amount'] !== '' ? (float)$_POST['amount'] : 0.00;
    $billing_cycle = $_POST['billing_cycle'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $auto_renew = isset($_POST['auto_renew']) ? 1 : 0;
    $status = $_POST['status'];
    $payment_gateway_id = $_POST['payment_gateway_id'] ?? '';
    $next_billing_date = !empty($_POST['next_billing_date']) ? $_POST['next_billing_date'] : null;
    $trial_end_date = !empty($_POST['trial_end_date']) ? $_POST['trial_end_date'] : null;
    
    if (empty($college_id) || empty($plan_id)) {
        $error = "College ID and Plan ID are required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE subscriptions SET college_id=?, plan_id=?, amount=?, billing_cycle=?, start_date=?, end_date=?, auto_renew=?, status=?, payment_gateway_id=?, next_billing_date=?, trial_end_date=? WHERE id=?");
            $stmt->execute([$college_id, $plan_id, $amount, $billing_cycle, $start_date, $end_date, $auto_renew, $status, $payment_gateway_id, $next_billing_date, $trial_end_date, $id]);
            $success = "Subscription updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE id = ?");
            $stmt->execute([$id]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("INSERT INTO subscriptions (college_id, plan_id, amount, billing_cycle, start_date, end_date, auto_renew, status, payment_gateway_id, next_billing_date, trial_end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$college_id, $plan_id, $amount, $billing_cycle, $start_date, $end_date, $auto_renew, $status, $payment_gateway_id, $next_billing_date, $trial_end_date]);
            $id = $pdo->lastInsertId();
            header("Location: subscription_form.php?id=$id&msg=created");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Subscription | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 700px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 1.8rem; font-weight: 800; display: flex; align-items: center; gap: 10px; }
        .btn-secondary { background: #fff; border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; color: var(--text-dark); display: inline-flex; align-items: center; gap: 6px; font-weight: 600; white-space: nowrap; box-sizing: border-box; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; white-space: nowrap; box-sizing: border-box; }
        .form-panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); overflow-x: auto; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); font-size: 0.9rem; }
        .form-control { width: 100%; min-width: 0; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; box-sizing: border-box; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .msg-alert { padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid transparent; font-weight: 500; }
        .alert-success { background: rgba(11,36,71,0.04); color: #0B2447; border-color: rgba(11,36,71,0.04); }
        .alert-error { background: rgba(15,23,42,0.06); color: #0B2447; border-color: rgba(15,23,42,0.06); }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .topbar { height: 56px; padding: 0 12px; justify-content: space-between; }
            .mobile-menu-btn { display: block; }
            .content-area { padding: 12px; max-width: none; }
            .page-header h2 { font-size: 1.3rem; }
            .form-panel { padding: 16px; }
            .grid-2 { grid-template-columns: 1fr; gap: 12px; }
            .form-group { margin-bottom: 14px; }
            .form-group label { font-size: 0.85rem; margin-bottom: 6px; }
            .form-control { padding: 9px 12px; font-size: 0.9rem; }
            .btn-primary { width: 100%; text-align: center; justify-content: center; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
            .form-panel { padding: 12px; }
            .page-header h2 { font-size: 1.1rem; }
        }
    </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <button class="mobile-menu-btn" id="mobile-menu-btn"><i class="ph ph-list"></i></button>
            <div class="user-profile"><span>Admin</span></div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <h2>
                    <a href="subscriptions.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                    <?php echo $id ? 'Edit Subscription' : 'Add New Subscription'; ?>
                </h2>
                <a href="subscriptions.php" class="btn-secondary">Cancel</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?><div class="msg-alert alert-success">Subscription created!</div><?php endif; ?>
            <?php if ($success): ?><div class="msg-alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg-alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <div class="form-panel">
                <form method="POST">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>College *</label>
                            <select name="college_id" class="form-control" required>
                                <option value="">Select College</option>
                                <?php foreach($colleges as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['id']); ?>" <?php echo (isset($sub['college_id']) && $sub['college_id'] == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Plan *</label>
                            <select name="plan_id" class="form-control" required>
                                <option value="">Select Plan</option>
                                <?php foreach($plans as $p): ?>
                                    <option value="<?php echo htmlspecialchars($p['id']); ?>" <?php echo (isset($sub['plan_id']) && $sub['plan_id'] == $p['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['plan_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Amount (INR)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required value="<?php echo htmlspecialchars($sub['amount'] ?? '0.00'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Billing Cycle *</label>
                            <select name="billing_cycle" class="form-control">
                                <?php foreach(['monthly','quarterly','annual'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo (isset($sub['billing_cycle']) && $sub['billing_cycle']==$t) ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" required value="<?php echo htmlspecialchars($sub['start_date'] ?? date('Y-m-d')); ?>">
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" required value="<?php echo htmlspecialchars($sub['end_date'] ?? date('Y-m-d')); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <?php foreach(['active','cancelled','expired','pending'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo (isset($sub['status']) && $sub['status']==$t) ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Next Billing Date</label>
                            <input type="date" name="next_billing_date" class="form-control" value="<?php echo htmlspecialchars($sub['next_billing_date'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Trial End Date</label>
                            <input type="date" name="trial_end_date" class="form-control" value="<?php echo htmlspecialchars($sub['trial_end_date'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Payment Gateway / Stripe ID</label>
                        <input type="text" name="payment_gateway_id" class="form-control" placeholder="e.g. sub_1M..." value="<?php echo htmlspecialchars($sub['payment_gateway_id'] ?? ''); ?>">
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="auto_renew" <?php echo (isset($sub['auto_renew']) && $sub['auto_renew']) ? 'checked' : ''; ?>>
                            Auto Renew Subscription
                        </label>
                    </div>
                    <button type="submit" class="btn-primary" style="margin-top:10px;">Save Subscription</button>
                </form>
            </div>
        </div>
    </main>
</div>
<script>
document.getElementById('mobile-menu-btn').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.add('open');
    document.getElementById('sidebar-overlay').classList.add('show');
});
document.getElementById('sidebar-overlay').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.remove('open');
    this.classList.remove('show');
});
</script>
</body>
</html>
