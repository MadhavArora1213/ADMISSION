<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'analytics';

if ($tab === 'analytics') {
    // Fetch the latest 30 days of analytics
    $stmt = $pdo->query("SELECT * FROM shortlist_analytics ORDER BY date DESC LIMIT 30");
    $analytics = $stmt->fetchAll();
    
    // Calculate aggregate totals from the shortlists table directly for summary stats
    $total_shortlists = $pdo->query("SELECT COUNT(*) FROM shortlists")->fetchColumn();
    $total_applied = $pdo->query("SELECT COUNT(*) FROM shortlists WHERE status = 'applied'")->fetchColumn();
    
    $dream_count = $pdo->query("SELECT COUNT(*) FROM shortlists WHERE priority = 'dream'")->fetchColumn();
    $target_count = $pdo->query("SELECT COUNT(*) FROM shortlists WHERE priority = 'target'")->fetchColumn();
    $safe_count = $pdo->query("SELECT COUNT(*) FROM shortlists WHERE priority = 'safe'")->fetchColumn();
    
    // Top 5 colleges all-time
    $top_colleges = $pdo->query("
        SELECT c.name, COUNT(s.id) as count 
        FROM shortlists s 
        JOIN colleges c ON s.college_id = c.id 
        GROUP BY s.college_id 
        ORDER BY count DESC 
        LIMIT 5
    ")->fetchAll();

} else {
    // Detailed User Shortlists
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $where = "1=1";
    $params = [];
    if ($search) {
        $where .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR c.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name, u.email, c.name as college_name
        FROM shortlists s 
        JOIN users u ON s.user_id = u.id 
        JOIN colleges c ON s.college_id = c.id 
        WHERE $where 
        ORDER BY s.added_at DESC 
        LIMIT 100
    ");
    $stmt->execute($params);
    $shortlists = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Shortlists | Admin Panel</title>
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
        .panel{background:#fff;border-radius:12px;border:1px solid var(--border-color);box-shadow:var(--shadow-sm);padding:24px;margin-bottom:24px}
        .panel-nopad{padding:0;overflow:hidden}
        .panel-body{margin-top:0;overflow-x:auto;-webkit-overflow-scrolling:touch}
        .filter-bar{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center;border-bottom:1px solid var(--border-color);padding-bottom:10px}
        .tab-link{padding:10px 20px;font-weight:600;color:var(--text-muted);font-size:.9rem;text-decoration:none;transition:all .2s;border-bottom:3px solid transparent;white-space:nowrap}
        .tab-link:hover{color:var(--primary)}
        .tab-link.active{color:var(--primary);border-bottom-color:var(--primary)}
        .search-box{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--border-color);border-radius:8px;padding:7px 14px;width:250px}
        .search-box input{border:none;outline:none;font-size:.9rem;width:100%}
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:24px}
        .stat-card{background:#fff;border-radius:12px;padding:20px;border:1px solid var(--border-color);box-shadow:var(--shadow-sm)}
        .stat-card h3{font-size:.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px}
        .stat-card .val{font-size:1.8rem;font-weight:800;color:var(--text-dark)}
        .stat-card .trend{font-size:.8rem;color:#0B2447;margin-top:4px;display:flex;align-items:center;gap:4px}
        .grid-2{display:flex;gap:24px}
        .grid-2>*{flex-shrink:0}
        table{width:100%;border-collapse:collapse;font-size:.88rem}
        th,td{padding:14px 16px;text-align:left;border-bottom:1px solid var(--border-color);vertical-align:top}
        th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}
        tr:hover{background:rgba(0,0,0,.015)}
        .badge{padding:3px 10px;border-radius:6px;font-size:.72rem;font-weight:700;display:inline-block;white-space:nowrap;text-transform:uppercase}
        .p-dream{background:rgba(11,36,71,.04);color:#0B2447}
        .p-target{background:rgba(11,36,71,.06);color:#19376D}
        .p-safe{background:rgba(11,36,71,.04);color:#0B2447}
        .s-active{background:#F8FAFC;color:rgba(15,23,42,.65)}
        .s-applied{background:rgba(11,36,71,.04);color:#0B2447}
        .s-removed{background:rgba(15,23,42,.06);color:#0F172A;text-decoration:line-through}

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
            .filter-bar{flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch}
            .stats-grid{grid-template-columns:repeat(2,1fr);gap:12px}
            .stat-card{padding:14px}
            .stat-card .val{font-size:1.3rem}
            .grid-2{flex-direction:column;gap:16px}
            .grid-2>*{flex:auto;width:100%}
            .search-box{width:100%}
            .panel{padding:14px;border-radius:10px}
            th,td{padding:8px 10px;font-size:.8rem}
        }
        @media(max-width:480px){
            .content-area{padding:8px}
            .page-header h2{font-size:1.1rem}
            .stats-grid{grid-template-columns:1fr}
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
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-heart" style="color:#0B2447;"></i> Student Shortlists</h2>
                    <p style="color:var(--text-muted);">Monitor student wishlists, ambitions, and college popularity.</p>
                </div>
            </div>

            <div class="filter-bar">
                <a href="?tab=analytics" class="tab-link <?php echo $tab=='analytics'?'active':''; ?>">Global Analytics</a>
                <a href="?tab=users" class="tab-link <?php echo $tab=='users'?'active':''; ?>">User Shortlists</a>
            </div>

            <?php if($tab === 'analytics'): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Shortlists</h3>
                        <div class="val"><?php echo number_format($total_shortlists); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Converted to Apply</h3>
                        <div class="val" style="color:#0B2447;"><?php echo number_format($total_applied); ?></div>
                        <?php if($total_shortlists > 0): ?>
                            <div class="trend"><?php echo number_format(($total_applied / $total_shortlists)*100, 1); ?>% Conversion Rate</div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-card">
                        <h3>Dream Colleges</h3>
                        <div class="val" style="color:#0B2447;"><?php echo number_format($dream_count); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Top College All-Time</h3>
                        <div class="val" style="font-size:1.2rem; color:var(--primary); line-height:1.2; margin-top:10px;">
                            <?php echo $top_colleges ? htmlspecialchars($top_colleges[0]['name']) : 'None'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="grid-2">
                    <div class="panel panel-nopad" style="flex:2;">
                        <div style="padding:15px 20px; border-bottom:1px solid var(--border-color); font-weight:700;">Daily Analytics Logs (Last 30 Days)</div>
                        <?php if(empty($analytics)): ?>
                            <p style="padding:30px; text-align:center; color:var(--text-muted);">No daily analytics logged yet. Cron job will generate this data nightly.</p>
                        <?php else: ?>
                            <div class="panel-body">
                            <table style="min-width:400px;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>New Shortlists</th>
                                        <th>Avg Per User</th>
                                        <th>Apply Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($analytics as $a): ?>
                                    <tr>
                                        <td style="font-weight:600;"><?php echo date('M d, Y', strtotime($a['date'])); ?></td>
                                        <td><?php echo number_format($a['shortlist_count']); ?></td>
                                        <td><?php echo number_format($a['avg_shortlists_per_user'], 2); ?></td>
                                        <td><?php echo number_format($a['shortlist_to_apply_rate'], 1); ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="panel panel-nopad" style="flex:1;">
                        <div style="padding:15px 20px; border-bottom:1px solid var(--border-color); font-weight:700;">Most Shortlisted (All Time)</div>
                        <div style="padding:20px;">
                            <?php if(empty($top_colleges)): ?>
                                <p style="color:var(--text-muted);">No data available.</p>
                            <?php else: ?>
                                <?php foreach($top_colleges as $idx => $tc): ?>
                                    <div style="display:flex; justify-content:space-between; margin-bottom:15px; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div style="width:24px; height:24px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:700;">
                                                <?php echo $idx + 1; ?>
                                            </div>
                                            <div style="font-weight:600; font-size:0.9rem;"><?php echo htmlspecialchars($tc['name']); ?></div>
                                        </div>
                                        <div style="font-weight:800; font-size:0.9rem; color:var(--text-muted);"><?php echo number_format($tc['count']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div style="display:flex; justify-content:flex-end; margin-bottom:15px;">
                    <form method="GET">
                        <input type="hidden" name="tab" value="users">
                        <div class="search-box">
                            <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                            <input type="text" name="q" placeholder="Search student or college..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </form>
                </div>

                <div class="panel panel-nopad">
                    <?php if(empty($shortlists)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:40px;">No shortlists found.</p>
                    <?php else: ?>
                    <div class="panel-body">
                        <table style="min-width:500px;">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>College Saved</th>
                                    <th>Priority & Status</th>
                                    <th>Added Date</th>
                                    <th>Student Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($shortlists as $s): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--primary);"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                        <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($s['email']); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight:700;"><?php echo htmlspecialchars($s['college_name']); ?></div>
                                        <?php if($s['notification_pref']): ?>
                                            <div style="font-size:0.75rem; color:#19376D; margin-top:4px;"><i class="ph-fill ph-bell-ringing"></i> Alerts On</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="margin-bottom:6px;"><span class="badge p-<?php echo $s['priority']; ?>"><?php echo $s['priority']; ?> Choice</span></div>
                                        <div><span class="badge s-<?php echo $s['status']; ?>"><?php echo $s['status']; ?></span></div>
                                    </td>
                                    <td>
                                        <div style="font-size:0.85rem;"><?php echo date('M d, Y', strtotime($s['added_at'])); ?></div>
                                        <?php if($s['status'] == 'applied'): ?>
                                            <div style="font-size:0.75rem; color:#0B2447; margin-top:4px;">Applied: <?php echo date('M d, Y', strtotime($s['updated_at'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($s['notes']): ?>
                                            <div style="font-size:0.8rem; color:var(--text-dark); background:#f8fafc; padding:8px; border-radius:6px; border:1px dashed var(--border-color); max-width:250px; font-style:italic;">
                                                "<?php echo htmlspecialchars($s['notes']); ?>"
                                            </div>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted); font-size:0.85rem;">—</span>
                                        <?php endif; ?>
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
<script>
document.getElementById('mobile-menu-btn').addEventListener('click',function(){document.querySelector('.sidebar').classList.toggle('open');document.getElementById('sidebar-overlay').classList.toggle('show');});
document.getElementById('sidebar-overlay').addEventListener('click',function(){document.querySelector('.sidebar').classList.remove('open');this.classList.remove('show');});
</script>
</body>
</html>
