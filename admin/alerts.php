<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle resolving alert
if (isset($_GET['action']) && $_GET['action'] === 'resolve' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE admin_alerts SET is_resolved = 1, is_read = 1, resolved_by = ? WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id'], $_GET['id']]);
    header("Location: alerts.php?msg=resolved");
    exit;
}

$stmt = $pdo->query("SELECT a.*, u.full_name as resolved_by_name FROM admin_alerts a LEFT JOIN users u ON a.resolved_by = u.id ORDER BY a.created_at DESC LIMIT 100");
$alerts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Alerts | Admin Panel</title>
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
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px;}
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top;}
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; background: #F8FAFC; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap; text-transform: capitalize; }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 20px; border: 1px solid rgba(11,36,71,0.04); }
        
        /* Priorities */
        .p-critical { background:rgba(15,23,42,0.04); color:#0F172A; border: 1px solid rgba(15,23,42,0.06); }
        .p-high { background:rgba(11,36,71,0.04); color:#19376D; border: 1px solid rgba(11,36,71,0.06); }
        .p-medium { background:rgba(11,36,71,0.04); color:#19376D; border: 1px solid #19376D; }
        .p-low { background:rgba(11,36,71,0.04); color:#0B2447; border: 1px solid rgba(11,36,71,0.06); }
        
        .btn-action { padding: 4px 8px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; color: var(--text-dark); text-decoration: none;}
        .btn-action:hover { background: #F8FAFC; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-bell-ringing" style="color:var(--primary);"></i> System Alerts</h2>
                    <p style="color:var(--text-muted);">Monitor and resolve system-wide alerts and warnings.</p>
                </div>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg']=='resolved'): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Alert resolved successfully.</div>
            <?php endif; ?>

            <div class="panel">
                <?php if(empty($alerts)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:40px;">No alerts found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Severity</th>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($alerts as $alert): ?>
                            <tr>
                                <td style="white-space:nowrap;"><?php echo date('M d, Y H:i:s', strtotime($alert['created_at'])); ?></td>
                                <td>
                                    <span class="badge p-<?php echo $alert['alert_severity']; ?>"><?php echo htmlspecialchars($alert['alert_severity']); ?></span>
                                </td>
                                <td>
                                    <span style="font-weight:600; text-transform:uppercase; font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($alert['alert_type']); ?></span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($alert['alert_message']); ?>
                                </td>
                                <td>
                                    <?php if($alert['is_resolved']): ?>
                                        <span class="badge" style="background:rgba(11,36,71,0.04); color:#0B2447;">Resolved</span>
                                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">By <?php echo htmlspecialchars($alert['resolved_by_name'] ?? 'Admin'); ?></div>
                                    <?php else: ?>
                                        <span class="badge" style="background:rgba(11,36,71,0.06); color:#0F172A;">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!$alert['is_resolved']): ?>
                                        <a href="?action=resolve&id=<?php echo $alert['id']; ?>" class="btn-action" onclick="return confirm('Mark this alert as resolved?')">Resolve</a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
