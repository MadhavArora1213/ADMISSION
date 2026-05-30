<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = "1=1";
$params = [];
if ($search) {
    $where .= " AND (a.entity_type LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT a.*, u.full_name as user_name 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    WHERE $where 
    ORDER BY a.created_at DESC 
    LIMIT 150
");
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | Admin Panel</title>
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
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; background: #f1f5f9; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap; text-transform: uppercase;}
        .a-create { background:#dcfce7; color:#166534; }
        .a-update { background:#dbeafe; color:#1e40af; }
        .a-delete { background:#fee2e2; color:#dc2626; }
        .a-login { background:#fefce8; color:#ca8a04; }
        .a-export { background:#f3e8ff; color:#7e22ce; }
        
        .search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 7px 14px; width: 300px;}
        .search-box input { border: none; outline: none; font-size: 0.9rem; width: 100%; }
        
        .json-viewer { font-family: monospace; font-size: 0.75rem; background: #f1f5f9; padding: 8px; border-radius: 6px; max-height: 100px; overflow-y: auto; max-width: 300px; white-space: pre-wrap; word-break: break-all;}
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
                    <h2><i class="ph ph-file-search" style="color:var(--primary);"></i> System Audit Logs</h2>
                    <p style="color:var(--text-muted);">Track all administrative actions, data changes, and access logs.</p>
                </div>
                <form method="GET">
                    <div class="search-box">
                        <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                        <input type="text" name="q" placeholder="Search entity or user..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </form>
            </div>

            <div class="panel">
                <?php if(empty($logs)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:40px;">No audit logs found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp & IP</th>
                                <th>User</th>
                                <th>Action & Entity</th>
                                <th>Data Changes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($logs as $l): ?>
                            <tr>
                                <td style="white-space:nowrap;">
                                    <div style="font-weight:600;"><?php echo date('M d, Y H:i:s', strtotime($l['created_at'])); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted); font-family:monospace; margin-top:4px;"><?php echo htmlspecialchars($l['ip_address'] ?? 'Unknown IP'); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($l['user_name'] ?? 'System / Anonymous'); ?></div>
                                </td>
                                <td>
                                    <span class="badge a-<?php echo $l['audit_action']; ?>"><?php echo $l['audit_action']; ?></span>
                                    <div style="font-size:0.8rem; font-weight:700; margin-top:6px;"><?php echo strtoupper(htmlspecialchars($l['entity_type'])); ?></div>
                                    <?php if($l['entity_id']): ?>
                                        <div style="font-size:0.7rem; color:var(--text-muted); font-family:monospace;"><?php echo htmlspecialchars($l['entity_id']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($l['old_value'] || $l['new_value']): ?>
                                        <div style="display:flex; gap:10px;">
                                            <?php if($l['old_value']): ?>
                                                <div>
                                                    <div style="font-size:0.7rem; font-weight:700; color:#dc2626; margin-bottom:2px;">OLD VALUE</div>
                                                    <div class="json-viewer"><?php echo htmlspecialchars($l['old_value']); ?></div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if($l['new_value']): ?>
                                                <div>
                                                    <div style="font-size:0.7rem; font-weight:700; color:#166534; margin-bottom:2px;">NEW VALUE</div>
                                                    <div class="json-viewer"><?php echo htmlspecialchars($l['new_value']); ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.85rem;">No data recorded</span>
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
