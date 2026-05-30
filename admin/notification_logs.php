<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$total_logs = $pdo->query("SELECT COUNT(*) FROM notification_logs")->fetchColumn();
$total_pages = ceil($total_logs / $limit);

$logs = $pdo->query("SELECT l.*, c.campaign_name 
    FROM notification_logs l 
    LEFT JOIN notification_campaigns c ON l.campaign_id = c.id 
    ORDER BY l.sent_at DESC 
    LIMIT $limit OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Logs | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.sub-links{display:flex;gap:8px;margin-bottom:20px}.sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:rgba(0,0,0,.05);color:var(--primary)}.pagination{display:flex;gap:5px;margin-top:20px;justify-content:center}.pagination a{padding:6px 12px;border:1px solid var(--border-color);border-radius:6px;text-decoration:none;color:var(--text-color)}.pagination a.active{background:var(--primary);color:#fff;border-color:var(--primary)}
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" style="margin-left:16px;color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-list-dashes" style="color:var(--primary);"></i> Delivery Logs</h2>
                    <p style="color:var(--text-muted);">View granular history of all sent notifications.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="notifications_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="notification_templates.php" class="sub-link"><i class="ph ph-file-text"></i> Templates</a>
                <a href="audience_segments.php" class="sub-link"><i class="ph ph-users-three"></i> Segments</a>
                <a href="notification_campaigns.php" class="sub-link"><i class="ph ph-megaphone"></i> Campaigns</a>
                <a href="notification_logs.php" class="sub-link active"><i class="ph ph-list-dashes"></i> Logs</a>
            </div>

            <div class="panel">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="border:none; margin:0;">All Logs (<?php echo number_format($total_logs); ?> total)</h3>
                </div>
                <div style="margin-top:20px; overflow-x:auto;">
                    <table>
                        <thead><tr><th>User ID</th><th>Campaign</th><th>Channel</th><th>Status</th><th>Error Message</th><th>Time</th></tr></thead>
                        <tbody>
                            <?php foreach($logs as $l): ?>
                            <tr>
                                <td style="font-family:monospace; font-size:0.8rem; color:#64748b;" title="<?php echo htmlspecialchars($l['user_id']); ?>">
                                    <?php echo substr($l['user_id'], 0, 12).'...'; ?>
                                </td>
                                <td>
                                    <?php if($l['campaign_name']): ?>
                                    <span style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($l['campaign_name']); ?></span>
                                    <?php else: ?>
                                    <span style="color:var(--text-muted); font-style:italic;">Manual / System</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge" style="background:#e0e7ff;color:#3730a3;"><?php echo ucfirst($l['channel']); ?></span></td>
                                <td>
                                    <?php 
                                        $color = '#475569'; $bg = '#f1f5f9';
                                        if(in_array($l['status'], ['sent','delivered','opened'])) { $color = '#166534'; $bg = '#dcfce7'; }
                                        if(in_array($l['status'], ['failed','bounced'])) { $color = '#dc2626'; $bg = '#fee2e2'; }
                                    ?>
                                    <span class="badge" style="background:<?php echo $bg; ?>;color:<?php echo $color; ?>;"><?php echo ucfirst($l['status']); ?></span>
                                </td>
                                <td style="color:#dc2626; font-size:0.8rem; max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?php echo htmlspecialchars($l['error_message']); ?>
                                </td>
                                <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo date('M d, Y H:i:s', strtotime($l['sent_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($logs)): ?>
                            <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">No notification logs found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php for($i = max(1, $page - 3); $i <= min($total_pages, $page + 3); $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>
</body>
</html>
