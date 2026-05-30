<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$consultant = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM consultants WHERE id = ?");
    $stmt->execute([$id]);
    $consultant = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$consultant) { die("Consultant not found."); }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $consultant_name = trim($_POST['consultant_name']);
    $consultant_rating = $_POST['consultant_rating'] !== '' ? (float)$_POST['consultant_rating'] : null;
    $verified_consultant = isset($_POST['verified_consultant']) ? 1 : 0;
    $contact_email = trim($_POST['contact_email']);
    $contact_phone = trim($_POST['contact_phone']);
    $experience_years = $_POST['experience_years'] !== '' ? (int)$_POST['experience_years'] : null;
    $success_rate_percent = $_POST['success_rate_percent'] !== '' ? (float)$_POST['success_rate_percent'] : null;
    
    if (empty($consultant_name)) {
        $error = "Consultant Name is required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE consultants SET consultant_name=?, consultant_rating=?, verified_consultant=?, contact_email=?, contact_phone=?, experience_years=?, success_rate_percent=? WHERE id=?");
            $stmt->execute([$consultant_name, $consultant_rating, $verified_consultant, $contact_email, $contact_phone, $experience_years, $success_rate_percent, $id]);
            $success = "Consultant updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM consultants WHERE id = ?");
            $stmt->execute([$id]);
            $consultant = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("INSERT INTO consultants (consultant_name, consultant_rating, verified_consultant, contact_email, contact_phone, experience_years, success_rate_percent) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$consultant_name, $consultant_rating, $verified_consultant, $contact_email, $contact_phone, $experience_years, $success_rate_percent]);
            $id = $pdo->lastInsertId();
            header("Location: consultant_form.php?id=$id&msg=created");
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
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Consultant | Admin</title>
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
        
        .checkbox-label { display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer; }
        .checkbox-label input { width: 18px; height: 18px; }
        
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
                    <a href="consultants.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                    <?php echo $id ? 'Edit Consultant' : 'Add New Consultant'; ?>
                </h2>
                <a href="consultants.php" class="btn-secondary">Cancel</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?>
                <div class="msg-alert alert-success">Consultant created successfully!</div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="msg-alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="msg-alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-panel">
                <form method="POST">
                    <div class="form-group">
                        <label>Consultant Name / Agency Name *</label>
                        <input type="text" name="consultant_name" class="form-control" required value="<?php echo htmlspecialchars($consultant['consultant_name'] ?? ''); ?>">
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($consultant['contact_email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($consultant['contact_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Experience (Years)</label>
                            <input type="number" name="experience_years" class="form-control" value="<?php echo htmlspecialchars($consultant['experience_years'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Success Rate (%)</label>
                            <input type="number" step="0.1" name="success_rate_percent" class="form-control" value="<?php echo htmlspecialchars($consultant['success_rate_percent'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Rating (out of 5.0)</label>
                        <input type="number" step="0.1" max="5" min="0" name="consultant_rating" class="form-control" value="<?php echo htmlspecialchars($consultant['consultant_rating'] ?? ''); ?>">
                    </div>

                    <div class="form-group" style="margin-top: 30px;">
                        <label class="checkbox-label">
                            <input type="checkbox" name="verified_consultant" <?php echo (isset($consultant['verified_consultant']) && $consultant['verified_consultant']) ? 'checked' : ''; ?>>
                            Verified Consultant / Recommended by Platform
                        </label>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 10px;">Save Consultant</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
