<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$comm = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM commissions WHERE id = ?");
    $stmt->execute([$id]);
    $comm = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$comm) { die("Commission not found."); }
}

$error = '';
$success = '';

$colleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $application_id = (int)$_POST['application_id'];
    $college_id = $_POST['college_id'];
    $consultant_id = $_POST['consultant_id'] !== '' ? (int)$_POST['consultant_id'] : null;
    $invoice_id = $_POST['invoice_id'] !== '' ? (int)$_POST['invoice_id'] : null;
    $commission_pct = (float)$_POST['commission_pct'];
    $commission_earned = (float)$_POST['commission_earned'];
    $commission_status = $_POST['commission_status'];
    $payout_date = $_POST['payout_date'] ? $_POST['payout_date'] : null;
    $payout_method = $_POST['payout_method'] ? $_POST['payout_method'] : null;
    
    if (empty($application_id) || empty($college_id)) {
        $error = "Application ID and College ID are required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE commissions SET application_id=?, college_id=?, consultant_id=?, invoice_id=?, commission_pct=?, commission_earned=?, commission_status=?, payout_date=?, payout_method=? WHERE id=?");
            $stmt->execute([$application_id, $college_id, $consultant_id, $invoice_id, $commission_pct, $commission_earned, $commission_status, $payout_date, $payout_method, $id]);
            $success = "Commission updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM commissions WHERE id = ?");
            $stmt->execute([$id]);
            $comm = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("INSERT INTO commissions (application_id, college_id, consultant_id, invoice_id, commission_pct, commission_earned, commission_status, payout_date, payout_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$application_id, $college_id, $consultant_id, $invoice_id, $commission_pct, $commission_earned, $commission_status, $payout_date, $payout_method]);
            $id = $pdo->lastInsertId();
            header("Location: commission_form.php?id=$id&msg=created");
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
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Commission | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none;}
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
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
                    <a href="commissions.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                    <?php echo $id ? 'Edit Commission' : 'Add Commission'; ?>
                </h2>
                <a href="commissions.php" class="btn-secondary">Cancel</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?><div class="msg-alert alert-success">Commission added!</div><?php endif; ?>
            <?php if ($success): ?><div class="msg-alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg-alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <div class="form-panel">
                <form method="POST">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Application ID *</label>
                            <input type="number" name="application_id" class="form-control" required value="<?php echo htmlspecialchars($comm['application_id'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>College *</label>
                            <select name="college_id" class="form-control" required>
                                <option value="">Select College</option>
                                <?php foreach($colleges as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['id']); ?>" <?php echo (isset($comm['college_id']) && $comm['college_id'] == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Consultant ID (Optional)</label>
                            <input type="number" name="consultant_id" class="form-control" value="<?php echo htmlspecialchars($comm['consultant_id'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Invoice ID (Optional)</label>
                            <input type="number" name="invoice_id" class="form-control" value="<?php echo htmlspecialchars($comm['invoice_id'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Commission Pct (%) *</label>
                            <input type="number" step="0.1" name="commission_pct" class="form-control" required value="<?php echo htmlspecialchars($comm['commission_pct'] ?? '0.0'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Earned Amount (INR) *</label>
                            <input type="number" step="0.01" name="commission_earned" class="form-control" required value="<?php echo htmlspecialchars($comm['commission_earned'] ?? '0.00'); ?>">
                        </div>
                    </div>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="commission_status" class="form-control">
                                <?php foreach(['pending','paid','disputed'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo (isset($comm['commission_status']) && $comm['commission_status']==$t) ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Payout Method</label>
                            <select name="payout_method" class="form-control">
                                <option value="">-- None --</option>
                                <option value="bank_transfer" <?php echo (isset($comm['payout_method']) && $comm['payout_method']=='bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="credit" <?php echo (isset($comm['payout_method']) && $comm['payout_method']=='credit') ? 'selected' : ''; ?>>Credit to Account</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Payout Date</label>
                        <input type="date" name="payout_date" class="form-control" value="<?php echo htmlspecialchars($comm['payout_date'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top:10px;">Save Commission</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
