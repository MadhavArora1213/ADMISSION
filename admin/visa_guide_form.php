<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$guide = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM visa_guides WHERE id = ?");
    $stmt->execute([$id]);
    $guide = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$guide) { die("Visa guide not found."); }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $country_id = (int)$_POST['country_id'];
    $visa_type = trim($_POST['visa_type']);
    $processing_time_days = $_POST['processing_time_days'] !== '' ? (int)$_POST['processing_time_days'] : null;
    $visa_fee_usd = $_POST['visa_fee_usd'] !== '' ? (float)$_POST['visa_fee_usd'] : null;
    $pswv_duration_months = $_POST['pswv_duration_months'] !== '' ? (int)$_POST['pswv_duration_months'] : null;
    $success_tips = $_POST['success_tips'];
    
    if (empty($country_id) || empty($visa_type)) {
        $error = "Country ID and Visa Type are required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE visa_guides SET country_id=?, visa_type=?, processing_time_days=?, visa_fee_usd=?, pswv_duration_months=?, success_tips=? WHERE id=?");
            $stmt->execute([$country_id, $visa_type, $processing_time_days, $visa_fee_usd, $pswv_duration_months, $success_tips, $id]);
            $success = "Visa guide updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM visa_guides WHERE id = ?");
            $stmt->execute([$id]);
            $guide = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("INSERT INTO visa_guides (country_id, visa_type, processing_time_days, visa_fee_usd, pswv_duration_months, success_tips) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$country_id, $visa_type, $processing_time_days, $visa_fee_usd, $pswv_duration_months, $success_tips]);
            $id = $pdo->lastInsertId();
            header("Location: visa_guide_form.php?id=$id&msg=created");
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
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Visa Guide | Admin</title>
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
        .content-area { padding: 32px; max-width: 900px; margin: 0 auto; width: 100%; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h2 { font-size: 1.8rem; font-weight: 800; display:flex; align-items:center; gap:10px; }
        .btn-secondary { background: #fff; border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; color: var(--text-dark); display:inline-flex; align-items:center; gap:6px; font-weight:600;}
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        
        .form-panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); font-size: 0.9rem; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; transition: border-color 0.2s; }
        .form-control:focus { border-color: var(--primary); }
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
        <header class="topbar">
            <div class="user-profile"><span>Admin</span></div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <h2>
                    <a href="visa_guides.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                    <?php echo $id ? 'Edit Visa Guide' : 'Add New Visa Guide'; ?>
                </h2>
                <a href="visa_guides.php" class="btn-secondary">Cancel</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?>
                <div class="msg-alert alert-success">Visa guide created successfully!</div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="msg-alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="msg-alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-panel">
                <form method="POST">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Country ID * (Ensure ID exists in countries table)</label>
                            <input type="number" name="country_id" class="form-control" required value="<?php echo htmlspecialchars($guide['country_id'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Visa Type * (e.g. Student Visa F1, Tier 4)</label>
                            <input type="text" name="visa_type" class="form-control" required value="<?php echo htmlspecialchars($guide['visa_type'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Processing Time (Days)</label>
                            <input type="number" name="processing_time_days" class="form-control" value="<?php echo htmlspecialchars($guide['processing_time_days'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Visa Fee (USD)</label>
                            <input type="number" step="0.01" name="visa_fee_usd" class="form-control" value="<?php echo htmlspecialchars($guide['visa_fee_usd'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>PSWV Duration (Months) - Post Study Work Visa</label>
                        <input type="number" name="pswv_duration_months" class="form-control" value="<?php echo htmlspecialchars($guide['pswv_duration_months'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Success Tips</label>
                        <textarea name="success_tips" class="form-control" rows="5"><?php echo htmlspecialchars($guide['success_tips'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn-primary">Save Guide</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
