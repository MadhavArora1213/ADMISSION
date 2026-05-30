<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM funnel_analytics WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { die("Record not found."); }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $funnel_step = $_POST['funnel_step'];
    $users_entered = (int)$_POST['users_entered'];
    $users_completed = (int)$_POST['users_completed'];
    $date = $_POST['date'];
    $segment = trim($_POST['segment']);
    if (empty($segment)) $segment = 'All';
    
    if (empty($date)) {
        $error = "Date is required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE funnel_analytics SET funnel_step=?, users_entered=?, users_completed=?, date=?, segment=? WHERE id=?");
            $stmt->execute([$funnel_step, $users_entered, $users_completed, $date, $segment, $id]);
            $success = "Record updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM funnel_analytics WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("INSERT INTO funnel_analytics (funnel_step, users_entered, users_completed, date, segment) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$funnel_step, $users_entered, $users_completed, $date, $segment]);
            $id = $pdo->lastInsertId();
            header("Location: funnel_analytic_form.php?id=$id&msg=created");
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
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Funnel Record | Admin</title>
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
                    <a href="funnel_analytics.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                    <?php echo $id ? 'Edit Funnel Record' : 'Add Funnel Record'; ?>
                </h2>
                <a href="funnel_analytics.php" class="btn-secondary">Cancel</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?><div class="msg-alert alert-success">Record added successfully!</div><?php endif; ?>
            <?php if ($success): ?><div class="msg-alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg-alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <div class="form-panel">
                <form method="POST">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Funnel Step</label>
                            <select name="funnel_step" class="form-control">
                                <?php foreach(['visit','search','college_view','shortlist','lead','apply','convert'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo (isset($row['funnel_step']) && $row['funnel_step']==$t) ? 'selected' : ''; ?>><?php echo str_replace('_', ' ', ucfirst($t)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Segment</label>
                            <input type="text" name="segment" class="form-control" placeholder="e.g. All, Mobile, Paid" value="<?php echo htmlspecialchars($row['segment'] ?? 'All'); ?>">
                        </div>
                    </div>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Users Entered</label>
                            <input type="number" name="users_entered" class="form-control" required value="<?php echo htmlspecialchars($row['users_entered'] ?? '0'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Users Completed</label>
                            <input type="number" name="users_completed" class="form-control" required value="<?php echo htmlspecialchars($row['users_completed'] ?? '0'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" name="date" class="form-control" required value="<?php echo htmlspecialchars($row['date'] ?? date('Y-m-d')); ?>">
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top:10px;">Save Record</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
