<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // header('Location: index.php');
    // exit;
}
require_once 'db.php';

// Fetch latest metrics
$stmt = $pdo->query("SELECT * FROM dashboard_snapshots ORDER BY recorded_at DESC LIMIT 1");
$metrics = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Fallback to 0 if null
$getMetric = function($key) use ($metrics) {
    return isset($metrics[$key]) ? $metrics[$key] : 0;
};

// Fetch Alerts
$alertsStmt = $pdo->query("SELECT * FROM admin_alerts ORDER BY created_at DESC LIMIT 5");
$alerts = $alertsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Activity Feed
$feedStmt = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 5");
$activities = $feedStmt->fetchAll(PDO::FETCH_ASSOC);

// Parse JSON fields
$top_search_queries = json_decode($getMetric('top_search_queries'), true) ?: [['query'=>'b.tech admission', 'count'=>1450], ['query'=>'top mba colleges', 'count'=>980], ['query'=>'jee main cutoff', 'count'=>850]];
$top_landing_pages = json_decode($getMetric('top_landing_pages'), true) ?: [['url'=>'/colleges/iit-bombay', 'views'=>4500], ['url'=>'/btech-colleges', 'views'=>3200], ['url'=>'/exams/jee-main', 'views'=>2900]];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Dashboard | AdmissionSeason</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f1f5f9; margin: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .admin-layout { display: flex; min-height: 100vh; }
        
        /* Sidebar styles */
        .sidebar { width: 260px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 50; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-header .logo { font-size: 1.2rem; color: #f8fafc; display:flex; align-items:center; gap:8px; font-weight:700; }
        .sidebar-nav { padding: 16px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #cbd5e1; transition: all 0.2s; font-size:0.95rem; text-decoration:none; }
        .sidebar-nav a:hover, .sidebar-nav a.active { color: #fff; background: rgba(255,255,255,0.05); border-left: 3px solid #3b82f6; }
        .sidebar-nav a i { font-size: 1.2rem; }
        
        .main-content { flex: 1; margin-left: 260px; display: flex; flex-direction: column; }
        
        /* Top Header */
        .topbar { height: 64px; background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 40; }
        .header-left { display: flex; align-items: center; gap: 16px; }
        .env-badge { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; border: 1px solid #bbf7d0; }
        .header-right { display: flex; align-items: center; gap: 16px; }
        .avatar { width: 32px; height: 32px; border-radius: 50%; background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size:0.85rem; cursor:pointer; }
        
        .content-area { padding: 24px; display: flex; flex-direction: column; gap: 24px; }
        
        .section-title { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: -8px; }

        /* KPI Grid for Overview */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .kpi-title { font-size: 0.85rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .kpi-icon { padding: 8px; border-radius: 8px; font-size: 1.25rem; }
        .kpi-value-row { display: flex; align-items: baseline; gap: 12px; }
        .kpi-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; }
        .kpi-trend { font-size: 0.8rem; font-weight: 600; padding: 2px 8px; border-radius: 20px; }
        .trend-up { background: #dcfce7; color: #166534; }
        .trend-down { background: #fee2e2; color: #991b1b; }
        .trend-neutral { background: #f1f5f9; color: #475569; }

        /* Dynamic Grid Layouts */
        .widget-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; }
        .widget-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; display:flex; flex-direction:column; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow:hidden; }
        
        .col-span-12 { grid-column: span 12; }
        .col-span-8 { grid-column: span 8; }
        .col-span-6 { grid-column: span 6; }
        .col-span-4 { grid-column: span 4; }
        .col-span-3 { grid-column: span 3; }
        
        .widget-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
        .widget-title { font-weight: 700; font-size: 1rem; color: #0f172a; display:flex; align-items:center; gap:8px; }
        .widget-body { padding: 20px; flex:1; overflow-y:auto; }

        /* Revenue Breakdown List */
        .revenue-list { list-style: none; padding: 0; margin: 0; }
        .revenue-list li { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .revenue-list li:last-child { border-bottom: none; }
        .rev-label { color: #64748b; font-weight: 500; font-size: 0.9rem; }
        .rev-val { font-weight: 700; color: #0f172a; }

        /* Tables */
        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th { padding: 10px 16px; font-size: 0.8rem; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        .data-table td { padding: 12px 16px; font-size: 0.9rem; border-bottom: 1px solid #f1f5f9; }
        .data-table tr:last-child td { border-bottom: none; }

        /* Feeds and Alerts */
        .feed-item { display:flex; gap:16px; padding:12px 0; border-bottom:1px solid #f1f5f9; }
        .feed-icon { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .feed-content p { font-size:0.9rem; color:#1e293b; margin:0 0 4px 0; font-weight:600; }
        .feed-meta { font-size:0.75rem; color:#64748b; }
        
        .alert-item { display:flex; align-items:flex-start; gap:12px; padding:12px 16px; border-radius:8px; margin-bottom:12px; border-left:4px solid transparent; background:#f8fafc; }
        .alert-high { border-left-color:#ef4444; background:#fef2f2; }
        .alert-medium { border-left-color:#f59e0b; }
        .alert-title { font-weight:600; font-size:0.9rem; color:#0f172a; margin-bottom:4px; }
        .alert-desc { font-size:0.8rem; color:#475569; }

        /* Colors for icons */
        .bg-blue { background: #eff6ff; color: #3b82f6; }
        .bg-green { background: #dcfce7; color: #22c55e; }
        .bg-purple { background: #f3e8ff; color: #a855f7; }
        .bg-orange { background: #ffedd5; color: #f97316; }
        .bg-red { background: #fef2f2; color: #ef4444; }
        .bg-indigo { background: #e0e7ff; color: #4f46e5; }
        .bg-teal { background: #ccfbf1; color: #14b8a6; }
        
    </style>
</head>
<body>

    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="topbar">
                <div class="header-left">
                    <div class="env-badge">PRODUCTION</div>
                    <div style="font-weight:700; color:#0f172a; margin-left:16px;">College Directory OS</div>
                </div>
                <div class="header-right">
                    <div class="avatar">A</div>
                </div>
            </header>

            <div class="content-area">
                
                <div class="section-title">Overview KPI Metrics</div>
                
                <!-- ROW 1: General Stats -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Total Colleges</span>
                            <i class="ph-fill ph-buildings kpi-icon bg-blue"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($getMetric('total_colleges')) ?></span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Total Exams</span>
                            <i class="ph-fill ph-exam kpi-icon bg-purple"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($getMetric('total_exams')) ?></span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Total Users</span>
                            <i class="ph-fill ph-users kpi-icon bg-indigo"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($getMetric('total_users')) ?></span>
                            <span class="kpi-trend trend-up">+<?= number_format($getMetric('new_signups_today')) ?> Today</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Daily Leads</span>
                            <i class="ph-fill ph-funnel kpi-icon bg-orange"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($getMetric('daily_leads')) ?></span>
                        </div>
                    </div>
                </div>

                <!-- ROW 2: Engagement & Moderation -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Active Sessions</span>
                            <i class="ph-fill ph-activity kpi-icon bg-teal"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($getMetric('active_sessions')) ?></span>
                        </div>
                        <div style="font-size:0.8rem; color:#64748b; margin-top:8px;">Avg duration: <?= gmdate("i:s", $getMetric('avg_session_duration_sec')) ?></div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Pending Moderation</span>
                            <i class="ph-fill ph-shield-warning kpi-icon bg-red"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($getMetric('pending_moderation')) ?></span>
                        </div>
                        <div style="font-size:0.8rem; color:#64748b; margin-top:8px;">Reviews, comments, q&a</div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Global CTR Today</span>
                            <i class="ph-fill ph-cursor-click kpi-icon bg-blue"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($getMetric('ctr_today'), 1) ?>%</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Revenue Today</span>
                            <i class="ph-fill ph-currency-inr kpi-icon bg-green"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value">₹<?= number_format($getMetric('revenue_today')) ?></span>
                        </div>
                    </div>
                </div>

                <div class="section-title" style="margin-top:10px;">Revenue Snapshot</div>
                
                <div class="widget-grid">
                    <!-- Revenue Chart -->
                    <div class="widget-panel col-span-8">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-chart-line-up"></i> 30-Day Revenue Trend</span>
                            <div style="display:flex; gap:12px;">
                                <span class="kpi-trend trend-up">MoM: +<?= $getMetric('mom_growth_pct') ?>%</span>
                                <span class="kpi-trend trend-up">YoY: +<?= $getMetric('yoy_growth_pct') ?>%</span>
                            </div>
                        </div>
                        <div class="widget-body" style="height: 300px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <!-- Revenue Breakdown -->
                    <div class="widget-panel col-span-4">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-wallet"></i> Monthly Breakdown</span>
                            <span style="font-weight:800; color:#16a34a;">₹<?= number_format($getMetric('monthly_revenue')) ?></span>
                        </div>
                        <div class="widget-body">
                            <ul class="revenue-list">
                                <li>
                                    <span class="rev-label">Lead Revenue</span>
                                    <span class="rev-val">₹<?= number_format($getMetric('lead_revenue')) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label">Subscription Revenue</span>
                                    <span class="rev-val">₹<?= number_format($getMetric('subscription_revenue')) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label">Ad Revenue</span>
                                    <span class="rev-val">₹<?= number_format($getMetric('ad_revenue')) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label">Commission Earned</span>
                                    <span class="rev-val">₹<?= number_format($getMetric('commission_earned')) ?></span>
                                </li>
                            </ul>
                            
                            <!-- Small pie chart placeholder -->
                            <div style="height:120px; margin-top:20px;">
                                <canvas id="revPieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-title" style="margin-top:10px;">Traffic & SEO Metrics</div>

                <div class="widget-grid">
                    <!-- Traffic Stats -->
                    <div class="widget-panel col-span-4">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-globe-hemisphere-west"></i> Website Traffic</span>
                        </div>
                        <div class="widget-body">
                            <ul class="revenue-list">
                                <li>
                                    <span class="rev-label">Page Views Today</span>
                                    <span class="rev-val"><?= number_format($getMetric('page_views_today')) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label">Bounce Rate</span>
                                    <span class="rev-val"><?= number_format($getMetric('bounce_rate'), 1) ?>%</span>
                                </li>
                                <li>
                                    <span class="rev-label">Organic Traffic</span>
                                    <span class="rev-val"><?= number_format($getMetric('organic_traffic_pct'), 1) ?>%</span>
                                </li>
                                <li>
                                    <span class="rev-label">Core Web Vitals (LCP)</span>
                                    <span class="rev-val <?= $getMetric('core_web_vitals_lcp') < 2.5 ? 'trend-up' : 'trend-down' ?>" style="padding:2px 6px; border-radius:4px;"><?= number_format($getMetric('core_web_vitals_lcp'), 2) ?>s</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Top Landing Pages -->
                    <div class="widget-panel col-span-4">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-file-html"></i> Top Landing Pages</span>
                        </div>
                        <div class="widget-body" style="padding:0;">
                            <table class="data-table">
                                <tr><th>URL PATH</th><th>VIEWS</th></tr>
                                <?php foreach($top_landing_pages as $page): ?>
                                <tr>
                                    <td style="color:#3b82f6; font-family:monospace;"><?= htmlspecialchars($page['url']) ?></td>
                                    <td><?= number_format($page['views']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Top Search Queries -->
                    <div class="widget-panel col-span-4">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-magnifying-glass"></i> Top Search Queries</span>
                        </div>
                        <div class="widget-body" style="padding:0;">
                            <table class="data-table">
                                <tr><th>QUERY</th><th>COUNT</th></tr>
                                <?php foreach($top_search_queries as $query): ?>
                                <tr>
                                    <td style="font-weight:500;"><?= htmlspecialchars($query['query']) ?></td>
                                    <td><?= number_format($query['count']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="section-title" style="margin-top:10px;">Alerts & Activity Feed</div>

                <div class="widget-grid">
                    <!-- Live Activity Feed -->
                    <div class="widget-panel col-span-6">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-clock-counter-clockwise"></i> Platform Activity Log</span>
                            <span style="font-size:0.8rem; color:#3b82f6; cursor:pointer;">View All</span>
                        </div>
                        <div class="widget-body">
                            <?php if(empty($activities)): ?>
                                <div style="color:#94a3b8; font-size:0.9rem;">No recent activities.</div>
                            <?php endif; ?>
                            <?php foreach ($activities as $act): 
                                $meta = json_decode($act['meta_json'], true) ?: [];
                                $iconClass = 'bg-blue'; $icon = 'ph-star';
                                if ($act['entity_type'] == 'lead') { $iconClass = 'bg-orange'; $icon = 'ph-funnel'; }
                                if ($act['entity_type'] == 'college') { $iconClass = 'bg-purple'; $icon = 'ph-buildings'; }
                                if ($act['activity_type'] == 'delete') { $iconClass = 'bg-red'; $icon = 'ph-trash'; }
                            ?>
                            <div class="feed-item">
                                <div class="feed-icon <?= $iconClass ?>"><i class="ph-fill <?= $icon ?>"></i></div>
                                <div class="feed-content">
                                    <p><?= ucfirst($act['activity_type']) ?> <?= ucfirst($act['entity_type']) ?></p>
                                    <div class="feed-meta"><?= htmlspecialchars(is_array($meta) ? json_encode($meta) : '') ?> • <?= date('M d, H:i', strtotime($act['created_at'])) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Incident Center -->
                    <div class="widget-panel col-span-6">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-warning"></i> Incident & Alert Center</span>
                            <span style="font-size:0.8rem; background:#f1f5f9; padding:2px 8px; border-radius:12px; font-weight:600;">Action Required</span>
                        </div>
                        <div class="widget-body">
                            <?php if(empty($alerts)): ?>
                                <div style="color:#94a3b8; font-size:0.9rem;">No active alerts. System healthy.</div>
                            <?php endif; ?>
                            <?php foreach ($alerts as $alert): 
                                $isHigh = $alert['alert_severity'] == 'critical' || $alert['alert_severity'] == 'high';
                                $alertClass = $isHigh ? 'alert-high' : 'alert-medium';
                                $iconColor = $isHigh ? '#ef4444' : '#f59e0b';
                                $icon = $isHigh ? 'ph-warning-circle' : 'ph-warning';
                            ?>
                            <div class="alert-item <?= $alertClass ?>">
                                <i class="ph-fill <?= $icon ?>" style="color:<?= $iconColor ?>; font-size:1.2rem; margin-top:2px;"></i>
                                <div>
                                    <div class="alert-title"><?= htmlspecialchars(ucfirst($alert['alert_type'])) ?> Alert (<?= ucfirst($alert['alert_severity']) ?>)</div>
                                    <div class="alert-desc"><?= htmlspecialchars($alert['alert_message']) ?></div>
                                    <div style="font-size:0.75rem; color:#94a3b8; margin-top:4px;"><?= date('M d, H:i', strtotime($alert['created_at'])) ?> • Status: <?= $alert['is_resolved'] ? 'Resolved' : 'Open' ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Revenue Chart Configuration
        const ctxRev = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctxRev, {
            type: 'line',
            data: {
                labels: ['1st', '5th', '10th', '15th', '20th', '25th', '30th'],
                datasets: [{
                    label: 'Daily Revenue (₹)',
                    data: [12000, 19000, 15000, 25000, 22000, 30000, 28000],
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Revenue Pie Chart
        const ctxPie = document.getElementById('revPieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Lead', 'Sub', 'Ad', 'Comm'],
                datasets: [{
                    data: [
                        <?= $getMetric('lead_revenue') ?>, 
                        <?= $getMetric('subscription_revenue') ?>, 
                        <?= $getMetric('ad_revenue') ?>, 
                        <?= $getMetric('commission_earned') ?>
                    ],
                    backgroundColor: ['#f97316', '#3b82f6', '#a855f7', '#22c55e'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { position: 'right', labels:{boxWidth:10, font:{size:10}} } }
            }
        });
    </script>
</body>
</html>
