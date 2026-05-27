<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    
    $stmt = $pdo->prepare("INSERT INTO dashboard_widgets (id, widget_key, widget_name, widget_type, data_source, config_json, default_size, is_realtime, cache_duration, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $id, 
        $_POST['widget_key'], 
        $_POST['widget_name'], 
        $_POST['widget_type'], 
        $_POST['data_source'], 
        $_POST['config_json'], 
        $_POST['default_size'], 
        isset($_POST['is_realtime']) ? 1 : 0, 
        $_POST['cache_duration'] ?: 300, 
        $_POST['status']
    ]);
    header("Location: manage_widgets.php?msg=added");
    exit;
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM dashboard_widgets WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: manage_widgets.php?msg=deleted");
    exit;
}

$widgets = $pdo->query("SELECT * FROM dashboard_widgets ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Widget Engine | AdmissionSeason</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 12px; }
        
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background-color: rgba(0,0,0,0.02); }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: #e0e7ff; color: #3730a3; }
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
                    <h2><i class="ph ph-squares-four"></i> Widget Engine</h2>
                </div>
                
                <div class="panel">
                    <h3><i class="ph ph-plus"></i> Define New Widget</h3>
                    <form action="" method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="add">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Widget Name</label>
                                <input type="text" name="widget_name" class="form-control" required placeholder="e.g. Revenue Chart">
                            </div>
                            <div class="form-group">
                                <label>Widget Key (Unique)</label>
                                <input type="text" name="widget_key" class="form-control" required placeholder="e.g. revenue_chart_v1">
                            </div>
                            <div class="form-group">
                                <label>Type</label>
                                <select name="widget_type" class="form-control" required>
                                    <option value="metric">Metric</option>
                                    <option value="chart">Chart</option>
                                    <option value="table">Table</option>
                                    <option value="feed">Feed</option>
                                    <option value="ai_summary">AI Summary</option>
                                    <option value="system_health">System Health</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Data Source (API / DB Table)</label>
                                <input type="text" name="data_source" class="form-control" placeholder="e.g. /api/revenue">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Config JSON (Chart Settings / Query logic)</label>
                                <textarea name="config_json" class="form-control" rows="3" placeholder='{"type": "line", "colors": ["#1e40af"]}'></textarea>
                            </div>
                            <div class="form-group">
                                <label>Default Size JSON</label>
                                <input type="text" name="default_size" class="form-control" placeholder='{"w": 4, "h": 2}'>
                            </div>
                            <div class="form-group">
                                <label>Cache Duration (Seconds)</label>
                                <input type="number" name="cache_duration" class="form-control" value="300">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                            <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                                <input type="checkbox" name="is_realtime" id="realtime">
                                <label for="realtime" style="margin:0;">Is Realtime?</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top:20px;">Save Widget</button>
                    </form>
                </div>

                <div class="panel">
                    <h3>Available Widgets Registry</h3>
                    <table>
                        <tr>
                            <th>Key</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Realtime</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach($widgets as $w): ?>
                        <tr>
                            <td style="font-family: monospace;"><?php echo htmlspecialchars($w['widget_key']); ?></td>
                            <td><?php echo htmlspecialchars($w['widget_name']); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($w['widget_type']); ?></span></td>
                            <td><?php echo $w['is_realtime'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo htmlspecialchars($w['status']); ?></td>
                            <td><a href="?action=delete&id=<?php echo $w['id']; ?>" class="action-btn"><i class="ph ph-trash"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($widgets)): ?>
                        <tr><td colspan="6" style="text-align:center; color:gray; padding:20px;">No widgets defined yet.</td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
