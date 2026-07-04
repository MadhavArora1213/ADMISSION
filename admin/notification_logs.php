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
        body{background:var(--bg-light);margin:0}
        .admin-layout{display:flex;min-height:100vh}
        .sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto;z-index:100;transition:transform .3s ease}
        .sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}
        .sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}
        .sidebar-nav{padding:24px 0;flex:1}
        .sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s;text-decoration:none}
        .sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}
        .main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;min-width:0;padding-bottom:60px}
        .topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}
        .content-area{padding:32px}
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:12px;flex-wrap:wrap}
        .page-header h2{font-size:2rem;font-weight:800}
        .panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px;overflow:hidden}
        .panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}
        .panel-header{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        .panel-body{margin-top:0;overflow-x:auto;-webkit-overflow-scrolling:touch}
        table{width:100%;border-collapse:collapse;font-size:.88rem}
        th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}
        th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}
        tr:hover{background:rgba(0,0,0,.015)}
        .badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700;display:inline-block;white-space:nowrap}
        .sub-links{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
        .sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}
        .sub-link:hover,.sub-link.active{background:var(--primary);color:#fff}
        .pagination{display:flex;gap:6px;justify-content:center;margin-top:20px;flex-wrap:wrap}
        .pagination a{padding:6px 12px;border-radius:6px;border:1px solid var(--border-color);text-decoration:none;color:var(--text-dark);font-size:.85rem;font-weight:600}
        .pagination a.active{background:var(--primary);color:#fff;border-color:var(--primary)}

        .mobile-menu-btn{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#0f172a;padding:4px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:90}

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .sidebar-overlay.show{display:block}
            .main-content{margin-left:0}
            .mobile-menu-btn{display:block}
            .topbar{height:auto;min-height:56px;padding:10px 12px;justify-content:space-between}
            .content-area{padding:12px}
            .page-header{flex-direction:column;align-items:flex-start}
            .page-header h2{font-size:1.3rem}
            .panel{padding:14px;border-radius:12px}
            .panel-header{flex-direction:column;align-items:flex-start}
            .panel h3{font-size:1rem}
            th,td{padding:8px 10px;font-size:.8rem}
        }
        @media(max-width:480px){
            .content-area{padding:8px}
            .page-header h2{font-size:1.1rem}
            .panel{padding:12px}
            th,td{padding:6px 8px;font-size:.75rem}
        }
    </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <button class="mobile-menu-btn" id="mobile-menu-btn"><i class="ph ph-list"></i></button>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
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
                <div class="panel-header">
                    <h3>All Logs (<?php echo number_format($total_logs); ?> total)</h3>
                </div>
                <div class="panel-body">
                    <table style="min-width:580px;">
                        <thead><tr><th>User ID</th><th>Campaign</th><th>Channel</th><th>Status</th><th>Error Message</th><th>Time</th></tr></thead>
                        <tbody>
                            <?php foreach($logs as $l): ?>
                            <tr>
                                <td style="font-family:monospace; font-size:0.8rem; color:rgba(15,23,42,0.45);" title="<?php echo htmlspecialchars($l['user_id']); ?>">
                                    <?php echo substr($l['user_id'], 0, 12).'...'; ?>
                                </td>
                                <td>
                                    <?php if($l['campaign_name']): ?>
                                    <span style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($l['campaign_name']); ?></span>
                                    <?php else: ?>
                                    <span style="color:var(--text-muted); font-style:italic;">Manual / System</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge" style="background:rgba(11,36,71,0.06);color:#19376D;"><?php echo ucfirst($l['channel']); ?></span></td>
                                <td>
                                    <?php 
                                        $color = 'rgba(15,23,42,0.65)'; $bg = '#F8FAFC';
                                        if(in_array($l['status'], ['sent','delivered','opened'])) { $color = '#0B2447'; $bg = 'rgba(11,36,71,0.04)'; }
                                        if(in_array($l['status'], ['failed','bounced'])) { $color = '#0F172A'; $bg = 'rgba(15,23,42,0.06)'; }
                                    ?>
                                    <span class="badge" style="background:<?php echo $bg; ?>;color:<?php echo $color; ?>;"><?php echo ucfirst($l['status']); ?></span>
                                </td>
                                <td style="color:#0F172A; font-size:0.8rem; max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
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
<script>
document.getElementById('mobile-menu-btn').addEventListener('click',function(){document.querySelector('.sidebar').classList.toggle('open');document.getElementById('sidebar-overlay').classList.toggle('show');});
document.getElementById('sidebar-overlay').addEventListener('click',function(){document.querySelector('.sidebar').classList.remove('open');this.classList.remove('show');});
</script>
</body>
</html>
