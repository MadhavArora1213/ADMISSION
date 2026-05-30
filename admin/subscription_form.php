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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $college_id = (int)$_POST['college_id'];
    $plan_id = (int)$_POST['plan_id'];
    $amount = $_POST['amount'] !== '' ? (float)$_POST['amount'] : 0.00;
    $billing_cycle = $_POST['billing_cycle'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $auto_renew = isset($_POST['auto_renew']) ? 1 : 0;
    $status = $_POST['status'];
    
    if (empty($college_id) || empty($plan_id)) {
        $error = "College ID and Plan ID are required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE subscriptions SET college_id=?, plan_id=?, amount=?, billing_cycle=?, start_date=?, end_date=?, auto_renew=?, status=? WHERE id=?");
            $stmt->execute([$college_id, $plan_id, $amount, $billing_cycle, $start_date, $end_date, $auto_renew, $status, $id]);
            $success = "Subscription updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE id = ?");
            $stmt->execute([$id]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("INSERT INTO subscriptions (college_id, plan_id, amount, billing_cycle, start_date, end_date, auto_renew, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$college_id, $plan_id, $amount, $billing_cycle, $start_date, $end_date, $auto_renew, $status]);
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
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 600px; margin: 0 auto; width: 100%; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h2 { font-size: 1.8rem; font-weight: 800; display:flex; align-items:center; gap:10px; }
        .btn-secondary { background: #fff; border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; color: var(--text-dark); display:inline-flex; align-items:center; gap:6px; font-weight:600;}
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .form-panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); font-size: 0.9rem; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .msg-alert { padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid transparent; font-weight:500; }
        .alert-success { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar"><div class="user-profile"><span>Admin</span></div></header>
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
                            <label>College ID *</label>
                            <input type="number" name="college_id" class="form-control" required value="<?php echo htmlspecialchars($sub['college_id'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Plan ID *</label>
                            <input type="number" name="plan_id" class="form-control" required value="<?php echo htmlspecialchars($sub['plan_id'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Amount (USD)</label>
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
</body>
</html>
