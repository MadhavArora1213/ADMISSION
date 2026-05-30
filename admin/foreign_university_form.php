<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$uni = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM foreign_universities WHERE id = ?");
    $stmt->execute([$id]);
    $uni = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$uni) { die("University not found."); }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $university_name = trim($_POST['university_name']);
    $university_slug = trim($_POST['university_slug']);
    $country_id = (int)$_POST['country_id'];
    $qs_rank = $_POST['qs_rank'] !== '' ? (int)$_POST['qs_rank'] : null;
    $tuition_usd_annual = $_POST['tuition_usd_annual'] !== '' ? (float)$_POST['tuition_usd_annual'] : null;
    $description = $_POST['description'];
    
    if (empty($university_name) || empty($university_slug) || empty($country_id)) {
        $error = "Name, slug, and country ID are required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE foreign_universities SET university_name=?, university_slug=?, country_id=?, qs_rank=?, tuition_usd_annual=?, description=? WHERE id=?");
            $stmt->execute([$university_name, $university_slug, $country_id, $qs_rank, $tuition_usd_annual, $description, $id]);
            $success = "University updated successfully.";
            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM foreign_universities WHERE id = ?");
            $stmt->execute([$id]);
            $uni = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("INSERT INTO foreign_universities (university_name, university_slug, country_id, qs_rank, tuition_usd_annual, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$university_name, $university_slug, $country_id, $qs_rank, $tuition_usd_annual, $description]);
            $id = $pdo->lastInsertId();
            header("Location: foreign_university_form.php?id=$id&msg=created");
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
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Foreign University | Admin</title>
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
                    <a href="foreign_universities.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                    <?php echo $id ? 'Edit University' : 'Add New University'; ?>
                </h2>
                <a href="foreign_universities.php" class="btn-secondary">Cancel</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?>
                <div class="msg-alert alert-success">University created successfully!</div>
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
                            <label>University Name *</label>
                            <input type="text" name="university_name" class="form-control" required value="<?php echo htmlspecialchars($uni['university_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Slug (URL) *</label>
                            <input type="text" name="university_slug" class="form-control" required value="<?php echo htmlspecialchars($uni['university_slug'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Country ID * (Ensure ID exists in countries table)</label>
                            <input type="number" name="country_id" class="form-control" required value="<?php echo htmlspecialchars($uni['country_id'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>QS Rank</label>
                            <input type="number" name="qs_rank" class="form-control" value="<?php echo htmlspecialchars($uni['qs_rank'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tuition (USD/Year)</label>
                        <input type="number" step="0.01" name="tuition_usd_annual" class="form-control" value="<?php echo htmlspecialchars($uni['tuition_usd_annual'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($uni['description'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn-primary">Save University</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
