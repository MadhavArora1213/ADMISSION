<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rep = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM analytics_reports WHERE id = ?");
    $stmt->execute([$id]);
    $rep = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$rep) { die("Report not found."); }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $report_name = trim($_POST['report_name']);
    $report_format = $_POST['report_format'];
    $status = $_POST['status'];
    $report_url = trim($_POST['report_url']);
    $admin_id = $_POST['admin_id'] !== '' ? (int)$_POST['admin_id'] : null;
    
    if (empty($report_name)) {
        $error = "Report Name is required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE analytics_reports SET report_name=?, report_format=?, status=?, report_url=?, admin_id=? WHERE id=?");
            $stmt->execute([$report_name, $report_format, $status, $report_url, $admin_id, $id]);
            $success = "Report log updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM analytics_reports WHERE id = ?");
            $stmt->execute([$id]);
            $rep = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("INSERT INTO analytics_reports (report_name, report_format, status, report_url, admin_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$report_name, $report_format, $status, $report_url, $admin_id]);
            $id = $pdo->lastInsertId();
            header("Location: analytics_report_form.php?id=$id&msg=created");
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
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Report | Admin</title>
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
        .alert-success { background: rgba(11,36,71,0.04); color: #0B2447; border-color: rgba(11,36,71,0.04); }
        .alert-error { background: rgba(15,23,42,0.06); color: #0B2447; border-color: rgba(15,23,42,0.06); }
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
                    <a href="analytics_reports.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                    <?php echo $id ? 'Edit Report Log' : 'Add Report Log'; ?>
                </h2>
                <a href="analytics_reports.php" class="btn-secondary">Cancel</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?><div class="msg-alert alert-success">Report logged successfully!</div><?php endif; ?>
            <?php if ($success): ?><div class="msg-alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg-alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <div class="form-panel">
                <form method="POST">
                    <div class="form-group">
                        <label>Report Name *</label>
                        <input type="text" name="report_name" class="form-control" required value="<?php echo htmlspecialchars($rep['report_name'] ?? ''); ?>">
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Format</label>
                            <select name="report_format" class="form-control">
                                <?php foreach(['pdf','csv','xlsx'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo (isset($rep['report_format']) && $rep['report_format']==$t) ? 'selected' : ''; ?>><?php echo strtoupper($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <?php foreach(['pending','completed','failed'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo (isset($rep['status']) && $rep['status']==$t) ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Admin ID (Requested By)</label>
                        <input type="number" name="admin_id" class="form-control" value="<?php echo htmlspecialchars($rep['admin_id'] ?? $_SESSION['admin_id'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Report File URL</label>
                        <input type="url" name="report_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($rep['report_url'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top:10px;">Save Report Log</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
