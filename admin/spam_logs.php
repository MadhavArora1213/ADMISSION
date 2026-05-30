<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'bans';

// Handle adding to blacklist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_ban') {
    $entity_type = $_POST['entity_type'];
    $entity_value = trim($_POST['entity_value']);
    $reason = trim($_POST['reason']);
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    
    // UUID in mysql schema we created as VARCHAR(36)
    $stmt = $pdo->prepare("
        INSERT INTO blacklisted_entities (id, entity_type, entity_value, reason, expires_at, added_by) 
        VALUES (UUID(), ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE reason = VALUES(reason), expires_at = VALUES(expires_at), is_active = TRUE, updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$entity_type, $entity_value, $reason, $expires_at, $_SESSION['admin_id']]);
    header("Location: spam_logs.php?tab=bans&msg=added");
    exit;
}

// Handle revoking a ban
if (isset($_GET['action']) && $_GET['action'] === 'revoke' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE blacklisted_entities SET is_active = FALSE WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: spam_logs.php?tab=bans&msg=revoked");
    exit;
}

if ($tab === 'bans') {
    $stmt = $pdo->query("SELECT b.*, u.full_name as added_by_name FROM blacklisted_entities b LEFT JOIN users u ON b.added_by = u.id ORDER BY b.created_at DESC LIMIT 100");
    $bans = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT s.*, u.full_name as user_name FROM spam_detection_logs s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 100");
    $logs = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spam & Bans | Admin Panel</title>
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
        
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;}
        .tab-link { padding: 10px 20px; font-weight: 600; color: var(--text-muted); font-size: 0.9rem; text-decoration: none; transition: all 0.2s; border-bottom: 3px solid transparent; }
        .tab-link:hover { color: var(--primary); }
        .tab-link.active { color: var(--primary); border-bottom-color: var(--primary); }
        
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px;}
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top;}
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; background: #f1f5f9; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap; }
        .s-active { background:#dcfce7; color:#166534; }
        .s-inactive { background:#f1f5f9; color:#94a3b8; }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 20px; border: 1px solid #bbf7d0; }
        
        /* Forms */
        .form-grid { display: grid; grid-template-columns: 1fr 2fr 1fr 1fr auto; gap: 16px; padding: 20px; align-items: end; background: #fff;}
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;}
        .form-group input, .form-group select { padding: 10px; font-size: 0.9rem; border: 1px solid var(--border-color); border-radius: 8px; }
        .btn-primary { padding: 10px 20px; font-size: 0.9rem; background: var(--primary); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;}
        .btn-primary:hover { opacity: 0.9; }
        
        .btn-action { padding: 4px 8px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; color: var(--text-dark); text-decoration: none;}
        .btn-action:hover { background: #f1f5f9; }
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
                    <h2><i class="ph ph-warning-circle" style="color:var(--primary);"></i> Spam & Bans</h2>
                    <p style="color:var(--text-muted);">Manage blacklisted entities and monitor spam detection logs.</p>
                </div>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg']=='added'): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Entity added to blacklist.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg']=='revoked'): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Ban revoked successfully.</div>
            <?php endif; ?>

            <div class="filter-bar">
                <a href="?tab=bans" class="tab-link <?php echo $tab=='bans'?'active':''; ?>">Blacklisted Entities</a>
                <a href="?tab=logs" class="tab-link <?php echo $tab=='logs'?'active':''; ?>">Spam Detection Logs</a>
            </div>

            <?php if($tab === 'bans'): ?>
                <div class="panel">
                    <form method="POST" class="form-grid">
                        <input type="hidden" name="action" value="add_ban">
                        <div class="form-group">
                            <label>Entity Type</label>
                            <select name="entity_type" required>
                                <option value="ip">IP Address</option>
                                <option value="user">User ID</option>
                                <option value="email">Email Address</option>
                                <option value="phone">Phone Number</option>
                                <option value="device">Device Fingerprint</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Entity Value</label>
                            <input type="text" name="entity_value" required placeholder="e.g. 192.168.1.1">
                        </div>
                        <div class="form-group">
                            <label>Reason</label>
                            <input type="text" name="reason" required placeholder="e.g. Mass reporting">
                        </div>
                        <div class="form-group">
                            <label>Expires At (Optional)</label>
                            <input type="datetime-local" name="expires_at">
                        </div>
                        <button type="submit" class="btn-primary">Blacklist</button>
                    </form>
                </div>

                <div class="panel">
                    <?php if(empty($bans)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:40px;">No bans found.</p>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Entity</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                    <th>Banned By</th>
                                    <th>Dates</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($bans as $b): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700;"><?php echo htmlspecialchars($b['entity_value']); ?></div>
                                        <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;"><?php echo $b['entity_type']; ?></div>
                                    </td>
                                    <td>
                                        <?php if($b['is_active']): ?>
                                            <span class="badge s-active">Active</span>
                                        <?php else: ?>
                                            <span class="badge s-inactive">Revoked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width: 200px;"><?php echo htmlspecialchars($b['reason']); ?></td>
                                    <td><?php echo htmlspecialchars($b['added_by_name'] ?? 'System'); ?></td>
                                    <td>
                                        <div style="font-size:0.8rem; color:var(--text-muted);">Added: <?php echo date('M d, Y', strtotime($b['created_at'])); ?></div>
                                        <?php if($b['expires_at']): ?>
                                            <div style="font-size:0.8rem; color:#ea580c;">Expires: <?php echo date('M d, Y', strtotime($b['expires_at'])); ?></div>
                                        <?php else: ?>
                                            <div style="font-size:0.8rem; color:var(--text-muted);">Expires: Never</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($b['is_active']): ?>
                                            <a href="?tab=bans&action=revoke&id=<?php echo $b['id']; ?>" class="btn-action" onclick="return confirm('Revoke this ban?')">Revoke</a>
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

            <?php else: ?>
                <div class="panel">
                    <?php if(empty($logs)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:40px;">No spam logs found.</p>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>IP / Fingerprint</th>
                                    <th>User ID</th>
                                    <th>Flags Detected</th>
                                    <th>Duplicate Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($logs as $l): ?>
                                <tr>
                                    <td style="white-space:nowrap;"><?php echo date('M d, Y H:i:s', strtotime($l['created_at'])); ?></td>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($l['ip_address']); ?></div>
                                        <?php if($l['device_fingerprint']): ?>
                                            <div style="font-size:0.75rem; font-family:monospace; color:var(--text-muted);"><?php echo htmlspecialchars($l['device_fingerprint']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $l['user_id'] ? htmlspecialchars($l['user_name'] ?? $l['user_id']) : '<span style="color:var(--text-muted);">Guest</span>'; ?></td>
                                    <td>
                                        <?php if($l['velocity_flag']): ?><span class="badge" style="background:#fee2e2; color:#991b1b; margin-right:4px;">Velocity</span><?php endif; ?>
                                        <?php if($l['vpn_detected']): ?><span class="badge" style="background:#ffedd5; color:#9a3412; margin-right:4px;">VPN</span><?php endif; ?>
                                        <?php if($l['proxy_detected']): ?><span class="badge" style="background:#fef3c7; color:#92400e;">Proxy</span><?php endif; ?>
                                        <?php if(!$l['velocity_flag'] && !$l['vpn_detected'] && !$l['proxy_detected']): ?>
                                            <span style="color:var(--text-muted); font-size:0.85rem;">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="width: 100px; background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                                            <div style="height: 100%; background: <?php echo $l['duplicate_content_score'] > 0.8 ? '#dc2626' : 'var(--primary)'; ?>; width: <?php echo ($l['duplicate_content_score']*100); ?>%;"></div>
                                        </div>
                                        <div style="font-size:0.75rem; margin-top:4px; color:var(--text-muted);"><?php echo number_format($l['duplicate_content_score'], 2); ?></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
