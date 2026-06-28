<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// --- Pull stats from page_analytics table ---
$totalViews     = 0;
$uniqueVisitors = 0;
$topPages       = [];
$chartLabels    = [];
$chartData      = [];

try {
    $totalViews     = (int)$pdo->query("SELECT COALESCE(SUM(page_views),0) FROM page_analytics")->fetchColumn();
    $uniqueVisitors = (int)$pdo->query("SELECT COALESCE(SUM(unique_visitors),0) FROM page_analytics")->fetchColumn();
    $topPages       = $pdo->query("SELECT url_path, page_title, page_views, unique_visitors FROM page_analytics ORDER BY page_views DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // Build chart by day
    try {
        $rows = $pdo->query("SELECT DATE(created_at) as day, SUM(page_views) as views FROM page_analytics GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
        $rows = array_reverse($rows);
        foreach ($rows as $r) {
            $chartLabels[] = date('D M j', strtotime($r['day']));
            $chartData[]   = (int)$r['views'];
        }
    } catch (Exception $e) {}
} catch (Exception $e) {}

// Fallbacks
if (empty($chartLabels)) {
    $chartLabels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    $chartData   = [0, 0, 0, 0, 0, 0, 0];
}

$chartLabelsJson = json_encode($chartLabels);
$chartDataJson   = json_encode($chartData);
$trackedPages    = count($topPages);
$avgPerPage      = ($trackedPages > 0 && $totalViews > 0) ? (int)($totalViews / $trackedPages) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traffic Analytics | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color:#F8FAFC; margin:0; font-family:'Inter',system-ui,sans-serif; }
        .admin-layout { display:flex; min-height:100vh; }
        .sidebar { width:260px; background:#0f172a; color:#f8fafc; display:flex; flex-direction:column; position:fixed; height:100vh; left:0; top:0; overflow-y:auto; z-index:50; }
        .sidebar-header { padding:20px; border-bottom:1px solid rgba(255,255,255,0.05); }
        .sidebar-header .logo { font-size:1.2rem; color:#f8fafc; display:flex; align-items:center; gap:8px; font-weight:700; }
        .sidebar-nav { padding:16px 0; flex:1; }
        .sidebar-nav a { display:flex; align-items:center; gap:12px; padding:12px 20px; color:rgba(255,255,255,0.6); transition:all 0.2s; font-size:0.95rem; text-decoration:none; }
        .sidebar-nav a:hover, .sidebar-nav a.active { color:#fff; background:rgba(255,255,255,0.05); border-left:3px solid #19376D; }
        .sidebar-nav a i { font-size:1.2rem; }
        .main-content { flex:1; margin-left:260px; display:flex; flex-direction:column; }
        .topbar { height:64px; background:#fff; border-bottom:1px solid rgba(15,23,42,0.08); display:flex; align-items:center; justify-content:space-between; padding:0 24px; position:sticky; top:0; z-index:40; }
        .header-left { display:flex; align-items:center; gap:16px; }
        .header-right { display:flex; align-items:center; gap:16px; }
        .avatar { width:32px; height:32px; border-radius:50%; background:#0f172a; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; }
        .content-area { padding:28px; display:flex; flex-direction:column; gap:24px; }
        .page-header h2 { font-size:1.6rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:10px; margin:0 0 4px; }
        .page-header p { color:rgba(15,23,42,0.45); margin:0; font-size:0.9rem; }

        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; }
        .stat-card { background:#fff; padding:24px; border-radius:12px; border:1px solid rgba(15,23,42,0.08); box-shadow:0 1px 3px rgba(0,0,0,0.05); }
        .stat-card .icon { width:44px; height:44px; border-radius:10px; background:#f1f5f9; color:#19376D; display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin-bottom:14px; }
        .stat-card .value { font-size:1.9rem; font-weight:800; color:#0f172a; }
        .stat-card .label { color:rgba(15,23,42,0.45); font-size:0.82rem; font-weight:600; margin-top:4px; text-transform:uppercase; letter-spacing:0.05em; }

        .chart-container { background:#fff; padding:24px; border-radius:12px; border:1px solid rgba(15,23,42,0.08); box-shadow:0 1px 3px rgba(0,0,0,0.05); }
        .chart-container h3 { font-size:1rem; font-weight:700; color:#0f172a; margin:0 0 20px; display:flex; align-items:center; gap:8px; }

        .table-card { background:#fff; border-radius:12px; border:1px solid rgba(15,23,42,0.08); box-shadow:0 1px 3px rgba(0,0,0,0.05); overflow:hidden; }
        .table-card-header { padding:16px 20px; border-bottom:1px solid rgba(15,23,42,0.06); display:flex; justify-content:space-between; align-items:center; background:#f8fafc; }
        .table-card-header h3 { font-size:1rem; font-weight:700; color:#0f172a; margin:0; display:flex; align-items:center; gap:8px; }
        table { width:100%; border-collapse:collapse; }
        th { padding:11px 18px; font-size:0.75rem; font-weight:700; color:rgba(15,23,42,0.4); text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid rgba(15,23,42,0.06); text-align:left; background:#f8fafc; }
        td { padding:13px 18px; font-size:0.875rem; border-bottom:1px solid rgba(15,23,42,0.04); color:#0f172a; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#f8fafc; }
        .empty-state { padding:60px 20px; text-align:center; color:rgba(15,23,42,0.35); font-size:0.9rem; }
        .empty-state i { font-size:2.5rem; display:block; margin-bottom:12px; }
        @media(max-width:900px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
        @media(max-width:600px) { .stats-grid { grid-template-columns:1fr; } .content-area { padding:16px; } }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="header-left">
                <div style="font-weight:700; color:#0f172a;">Traffic Analytics</div>
            </div>
            <div class="header-right">
                <div class="avatar">A</div>
            </div>
        </header>
        <div class="content-area">

            <div class="page-header">
                <h2><i class="ph ph-chart-line-up" style="color:#19376D;"></i> Traffic Analytics</h2>
                <p>Overview of your portal's page views and visitor engagement.</p>
            </div>

            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon"><i class="ph ph-eye"></i></div>
                    <div class="value"><?= number_format($totalViews) ?></div>
                    <div class="label">Total Page Views</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="ph ph-users"></i></div>
                    <div class="value"><?= number_format($uniqueVisitors) ?></div>
                    <div class="label">Unique Visitors</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="ph ph-file-text"></i></div>
                    <div class="value"><?= $trackedPages ?></div>
                    <div class="label">Tracked Pages</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="ph ph-chart-line-up"></i></div>
                    <div class="value"><?= number_format($avgPerPage) ?></div>
                    <div class="label">Avg. Views / Page</div>
                </div>
            </div>

            <!-- Chart -->
            <div class="chart-container">
                <h3><i class="ph ph-chart-line-up" style="color:#19376D;"></i> Traffic Overview (Last 7 Days)</h3>
                <canvas id="trafficChart" height="80"></canvas>
            </div>

            <!-- Top Pages Table -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3><i class="ph ph-list-bullets"></i> Top Pages by Views</h3>
                </div>
                <?php if (empty($topPages)): ?>
                <div class="empty-state">
                    <i class="ph ph-chart-bar"></i>
                    No page analytics data recorded yet.<br>Data will appear here once pages start being tracked.
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Page Title</th>
                            <th>URL Path</th>
                            <th>Views</th>
                            <th>Unique</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topPages as $i => $page): ?>
                        <tr>
                            <td style="color:rgba(15,23,42,0.35); font-weight:600;"><?= $i + 1 ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($page['page_title'] ?: 'Untitled') ?></td>
                            <td style="font-family:monospace; color:#19376D; font-size:0.82rem;"><?= htmlspecialchars($page['url_path']) ?></td>
                            <td style="font-weight:700;"><?= number_format($page['page_views']) ?></td>
                            <td><?= number_format($page['unique_visitors'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div><!-- /content-area -->
    </main>
</div>

<script>
const ctx = document.getElementById('trafficChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= $chartLabelsJson ?>,
        datasets: [{
            label: 'Page Views',
            data: <?= $chartDataJson ?>,
            borderColor: '#19376D',
            backgroundColor: 'rgba(25,55,109,0.07)',
            borderWidth: 2.5,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#19376D',
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(15,23,42,0.04)' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
</body>
</html>
