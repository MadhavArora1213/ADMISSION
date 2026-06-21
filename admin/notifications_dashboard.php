<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Fetch quick stats
$totalCampaigns = $pdo->query("SELECT COUNT(*) FROM notification_campaigns")->fetchColumn();
$totalSent = $pdo->query("SELECT SUM(sent_count) FROM notification_campaigns")->fetchColumn() ?: 0;
$totalDelivered = $pdo->query("SELECT SUM(delivered_count) FROM notification_campaigns")->fetchColumn() ?: 0;
$totalOpened = $pdo->query("SELECT SUM(opened_count) FROM notification_campaigns")->fetchColumn() ?: 0;

$recentLogs = $pdo->query("SELECT * FROM notification_logs ORDER BY sent_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications Dashboard | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}.stat-card{background:#fff;border-radius:12px;border:1px solid var(--border-color);padding:20px;box-shadow:var(--shadow-sm)}.stat-card .num{font-size:2rem;font-weight:800;color:var(--primary)}.stat-card .label{font-size:.8rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-top:4px}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.sub-links{display:flex;gap:8px;margin-bottom:20px}.sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:rgba(0,0,0,.05);color:var(--primary)}
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
                    <h2><i class="ph ph-bell-ringing" style="color:var(--primary);"></i> Notifications Dashboard</h2>
                    <p style="color:var(--text-muted);">Monitor and manage transactional and marketing communications.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="notifications_dashboard.php" class="sub-link active"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="notification_templates.php" class="sub-link"><i class="ph ph-file-text"></i> Templates</a>
                <a href="audience_segments.php" class="sub-link"><i class="ph ph-users-three"></i> Segments</a>
                <a href="notification_campaigns.php" class="sub-link"><i class="ph ph-megaphone"></i> Campaigns</a>
                <a href="notification_logs.php" class="sub-link"><i class="ph ph-list-dashes"></i> Logs</a>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="num"><?php echo number_format($totalCampaigns); ?></div><div class="label">Total Campaigns</div></div>
                <div class="stat-card"><div class="num" style="color:#19376D;"><?php echo number_format($totalSent); ?></div><div class="label">Total Sent</div></div>
                <div class="stat-card"><div class="num" style="color:#0B2447;"><?php echo number_format($totalDelivered); ?></div><div class="label">Total Delivered</div></div>
                <div class="stat-card"><div class="num" style="color:#0B2447;"><?php echo number_format($totalOpened); ?></div><div class="label">Total Opened</div></div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
                <div class="panel">
                    <h3><i class="ph ph-clock-counter-clockwise"></i> Recent Delivery Logs</h3>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead><tr><th>User ID</th><th>Channel</th><th>Status</th><th>Time</th></tr></thead>
                            <tbody>
                                <?php foreach($recentLogs as $log): ?>
                                <tr>
                                    <td style="font-family:monospace; font-size:0.8rem; color:rgba(15,23,42,0.45);"><?php echo substr($log['user_id'], 0, 8).'...'; ?></td>
                                    <td><span class="badge" style="background:rgba(11,36,71,0.06);color:#19376D;"><?php echo ucfirst($log['channel']); ?></span></td>
                                    <td>
                                        <?php if($log['status'] == 'sent' || $log['status'] == 'delivered' || $log['status'] == 'opened'): ?>
                                        <span class="badge" style="background:rgba(11,36,71,0.04);color:#0B2447;"><?php echo ucfirst($log['status']); ?></span>
                                        <?php else: ?>
                                        <span class="badge" style="background:rgba(15,23,42,0.06);color:#0F172A;"><?php echo ucfirst($log['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo date('d M, H:i', strtotime($log['sent_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($recentLogs)): ?>
                                <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No logs available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div style="text-align:right; margin-top:16px;">
                            <a href="notification_logs.php" style="color:var(--primary); font-size:0.85rem; font-weight:600; text-decoration:none;">View All Logs &rarr;</a>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <h3><i class="ph ph-rocket"></i> Quick Actions</h3>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <a href="notification_templates.php" class="btn btn-primary" style="display:flex; align-items:center; gap:8px; justify-content:center; padding:12px; border-radius:8px; text-decoration:none;">
                            <i class="ph ph-plus"></i> Create New Template
                        </a>
                        <a href="notification_campaigns.php" class="btn btn-primary" style="display:flex; align-items:center; gap:8px; justify-content:center; padding:12px; border-radius:8px; text-decoration:none; background:#0f172a; color:#fff;">
                            <i class="ph ph-paper-plane-tilt"></i> Schedule Campaign
                        </a>
                        <a href="audience_segments.php" class="btn" style="display:flex; align-items:center; gap:8px; justify-content:center; padding:12px; border-radius:8px; text-decoration:none; background:#F8FAFC; color:var(--text-color); border:1px solid var(--border-color);">
                            <i class="ph ph-users"></i> Manage Audience
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
