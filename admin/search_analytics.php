<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Fetch overall search stats
$totalSearches = $pdo->query("SELECT COUNT(*) FROM search_queries")->fetchColumn();
$zeroResults = $pdo->query("SELECT COUNT(*) FROM search_queries WHERE zero_results = 1")->fetchColumn();
$zeroRate = $totalSearches > 0 ? round(($zeroResults / $totalSearches) * 100, 1) : 0;

$mobile = $pdo->query("SELECT COUNT(*) FROM search_queries WHERE device_type = 'mobile'")->fetchColumn();
$desktop = $pdo->query("SELECT COUNT(*) FROM search_queries WHERE device_type = 'desktop'")->fetchColumn();
$tablet = $pdo->query("SELECT COUNT(*) FROM search_queries WHERE device_type = 'tablet'")->fetchColumn();

// Fetch trending queries
$trending = $pdo->query("SELECT * FROM search_trending ORDER BY trending_score DESC LIMIT 20")->fetchAll();

// Fetch recent queries
$recent_queries = $pdo->query("SELECT * FROM search_queries ORDER BY search_timestamp DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Analytics | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}.stat-card{background:#fff;border-radius:12px;border:1px solid var(--border-color);padding:20px;box-shadow:var(--shadow-sm)}.stat-card .num{font-size:2rem;font-weight:800;color:var(--primary)}.stat-card .label{font-size:.8rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-top:4px}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.badge.mobile{background:rgba(11,36,71,0.06);color:#19376D}.badge.desktop{background:rgba(11,36,71,0.04);color:#0F172A}.badge.tablet{background:rgba(11,36,71,0.04);color:#0B2447}.sub-links{display:flex;gap:8px;margin-bottom:20px}.sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:rgba(0,0,0,.05);color:var(--primary)}.bar-bg{background:#F8FAFC;height:6px;border-radius:3px;margin-top:4px;overflow:hidden}.bar-fill{background:var(--primary);height:100%}
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
                    <h2><i class="ph ph-magnifying-glass" style="color:var(--primary);"></i> Search Analytics</h2>
                    <p style="color:var(--text-muted);">Analyze user search patterns and zero-result queries.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="search_analytics.php" class="sub-link active"><i class="ph ph-chart-bar"></i> Analytics</a>
                <a href="search_config.php" class="sub-link"><i class="ph ph-sliders"></i> Search Configuration</a>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="num"><?php echo number_format($totalSearches); ?></div><div class="label">Total Searches</div></div>
                <div class="stat-card"><div class="num" style="color:#0F172A;"><?php echo $zeroRate; ?>%</div><div class="label">Zero Results Rate</div></div>
                <div class="stat-card">
                    <div class="num" style="color:#0B2447; font-size:1.5rem; display:flex; justify-content:space-between; align-items:center;">
                        <span><i class="ph ph-device-mobile"></i> <?php echo number_format($mobile); ?></span>
                        <span><i class="ph ph-desktop"></i> <?php echo number_format($desktop); ?></span>
                    </div>
                    <div class="label">Mobile vs Desktop</div>
                </div>
                <div class="stat-card"><div class="num"><?php echo count($trending); ?></div><div class="label">Trending Keywords</div></div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 2fr; gap:24px;">
                <!-- Trending Panel -->
                <div class="panel">
                    <h3><i class="ph ph-trend-up"></i> Trending Searches</h3>
                    <?php if(empty($trending)): ?>
                        <p style="color:var(--text-muted);">No trending data available.</p>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:12px;">
                        <?php foreach($trending as $t): ?>
                            <div>
                                <div style="display:flex; justify-content:space-between; font-size:0.85rem; font-weight:600;">
                                    <span><?php echo htmlspecialchars($t['query_text']); ?></span>
                                    <span style="color:var(--primary);"><?php echo round($t['trending_score']); ?></span>
                                </div>
                                <div class="bar-bg"><div class="bar-fill" style="width:<?php echo min(100, $t['trending_score']); ?>%;"></div></div>
                                <div style="font-size:0.7rem; color:var(--text-muted); margin-top:2px;">Period: <?php echo ucfirst($t['trending_period']); ?></div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Queries Panel -->
                <div class="panel">
                    <h3><i class="ph ph-clock-counter-clockwise"></i> Live Search Feed</h3>
                    <?php if(empty($recent_queries)): ?>
                        <p style="color:var(--text-muted);">No recent searches.</p>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Query</th>
                                    <th>Results</th>
                                    <th>Device</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_queries as $rq): ?>
                                <tr>
                                    <td style="font-weight:600; color:var(--primary);">
                                        <?php echo htmlspecialchars($rq['query_text']); ?>
                                        <?php if($rq['clicked_result_id']): ?> <i class="ph-fill ph-check-circle" style="color:#0B2447;" title="Clicked a result"></i><?php endif; ?>
                                        <?php if($rq['zero_results']): ?> <i class="ph-fill ph-warning-circle" style="color:#0F172A;" title="Zero results"></i><?php endif; ?>
                                    </td>
                                    <td><?php echo $rq['results_count']; ?></td>
                                    <td><span class="badge <?php echo $rq['device_type'] ?: 'desktop'; ?>"><?php echo ucfirst($rq['device_type'] ?: 'Desktop'); ?></span></td>
                                    <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo date('d M, H:i', strtotime($rq['search_timestamp'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
