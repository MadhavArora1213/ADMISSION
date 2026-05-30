<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $stmt = $pdo->prepare("INSERT INTO dashboard_layouts (id, layout_name, is_default, layout_json) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id, $_POST['layout_name'], isset($_POST['is_default']) ? 1 : 0, $_POST['layout_json']]);
    header("Location: manage_layouts.php?msg=added");
    exit;
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM dashboard_layouts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: manage_layouts.php?msg=deleted");
    exit;
}

$layouts = $pdo->query("SELECT * FROM dashboard_layouts ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layout Engine | AdmissionSeason</title>
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
        .content-area { padding: 32px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 12px; }
        
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background-color: rgba(0,0,0,0.02); }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: #dcfce7; color: #166534; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <header class="topbar">
                <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span></div>
            </header>
            <div class="content-area">
                <div class="page-header">
                    <h2><i class="ph ph-layout"></i> Dashboard Layouts</h2>
                </div>
                
                <div class="panel">
                    <h3><i class="ph ph-plus"></i> Define New Layout</h3>
                    <form action="" method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="add">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Layout Name</label>
                                <input type="text" name="layout_name" class="form-control" required placeholder="e.g. Sales Manager Default">
                            </div>
                            <div class="form-group">
                                <label>Layout Configuration (JSON Array of Widget IDs and coordinates)</label>
                                <textarea name="layout_json" class="form-control" rows="6" placeholder='{"widgets": [{"widget_id": "uuid-here", "x":0, "y":0, "w":4, "h":2}]}'></textarea>
                            </div>
                            <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                                <input type="checkbox" name="is_default" id="default">
                                <label for="default" style="margin:0;">Set as Global Default?</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top:20px;">Save Layout</button>
                    </form>
                </div>

                <div class="panel">
                    <h3>Available Layouts</h3>
                    <table>
                        <tr>
                            <th>Name</th>
                            <th>Default?</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach($layouts as $l): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($l['layout_name']); ?></strong></td>
                            <td><?php if($l['is_default']) echo '<span class="badge">Default</span>'; else echo '-'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($l['created_at'])); ?></td>
                            <td><a href="?action=delete&id=<?php echo $l['id']; ?>" class="action-btn"><i class="ph ph-trash"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($layouts)): ?>
                        <tr><td colspan="4" style="text-align:center; color:gray; padding:20px;">No layouts defined yet.</td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
