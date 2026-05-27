<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

// Fetch today's metrics from dashboard_snapshots
$stmt = $pdo->query("SELECT metric_key, metric_value FROM dashboard_snapshots WHERE snapshot_date = CURDATE()");
$metrics = [];
while ($row = $stmt->fetch()) {
    $metrics[$row['metric_key']] = $row['metric_value'];
}

// Fetch Alerts
$alertsStmt = $pdo->query("SELECT * FROM admin_alerts ORDER BY created_at DESC LIMIT 5");
$alerts = $alertsStmt->fetchAll();

// Fetch Activity Feed
$activityStmt = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 5");
$activities = $activityStmt->fetchAll();

// Utility function to get metric
function getMetric($key, $metrics, $default = 0) {
    return isset($metrics[$key]) ? number_format($metrics[$key], strpos($metrics[$key], '.') !== false ? 2 : 0) : $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 280px;
            background: #0f172a;
            color: #f8fafc;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header .logo {
            font-size: 1.3rem;
            color: #f8fafc;
        }
        .sidebar-nav {
            padding: 24px 0;
            flex: 1;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 24px;
            color: #f8fafc;
            transition: all 0.3s ease;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            color: #f8fafc;
            background: rgba(255,255,255,0.05);
            border-left: 4px solid var(--primary);
        }
        .sidebar-nav a i {
            font-size: 1.25rem;
        }
        .main-content {
            flex: 1;
            margin-left: 280px;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            height: 80px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .content-area {
            padding: 32px;
        }
        .section-heading {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text-dark);
            margin-top: 32px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 8px;
        }
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .m-card {
            background: #f8fafc;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: transform 0.3s ease;
        }
        .m-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        .m-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .m-icon {
            font-size: 1.5rem;
            padding: 8px;
            border-radius: 8px;
        }
        .icon-blue { background: #f8fafc; color: #0b2447; }
        .icon-green { background: #f8fafc; color: #19376d; }
        .icon-yellow { background: #f8fafc; color: #19376d; }
        .icon-purple { background: #f8fafc; color: #19376d; }
        .icon-red { background: #f8fafc; color: #19376d; }
        
        .m-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-dark);
        }
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }
        .panel {
            background: #f8fafc;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }
        .panel h3 {
            font-size: 1.1rem;
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        /* Alerts */
        .alert-item {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border-left: 4px solid transparent;
        }
        .alert-high, .alert-critical { background: #f8fafc; border-left-color: #19376d; }
        .alert-medium { background: #f8fafc; border-left-color: #19376d; }
        .alert-low { background: #f8fafc; border-left-color: #19376d; }
        .alert-item i { font-size: 1.25rem; margin-top: 2px; }
        .alert-high i, .alert-critical i { color: #19376d; }
        .alert-medium i { color: #19376d; }
        .alert-low i { color: #19376d; }
        
        .alert-content p { font-size: 0.95rem; font-weight: 500; margin-bottom: 4px; }
        .alert-meta { font-size: 0.8rem; color: var(--text-muted); }

        /* Activity Feed */
        .activity-feed {
            position: relative;
            padding-left: 24px;
        }
        .activity-feed::before {
            content: '';
            position: absolute;
            top: 0; left: 7px;
            width: 2px;
            height: 100%;
            background: var(--border-color);
        }
        .activity-item {
            position: relative;
            margin-bottom: 24px;
        }
        .activity-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--primary);
            border: 3px solid #f8fafc;
            box-shadow: 0 0 0 1px var(--border-color);
        }
        .activity-item p {
            font-size: 0.95rem;
            margin-bottom: 4px;
            color: var(--text-dark);
        }
        .activity-item span {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #f8fafc;
        }

        @media (max-width: 1024px) {
            .two-col { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="admin-layout">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="topbar">
                <div class="user-profile">
                    <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <div class="avatar"><?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?></div>
                    <a href="logout.php" style="margin-left: 16px; color: #19376d;" title="Logout">
                        <i class="ph ph-sign-out" style="font-size: 1.5rem;"></i>
                    </a>
                </div>
            </header>

            <div class="content-area">
                <div style="margin-bottom: 24px;">
                    <h2 style="font-size: 2rem; font-weight: 800;">Overview Dashboard</h2>
                    <p style="color: var(--text-muted);">Real-time metrics and system alerts for AdmissionSeason.</p>
                </div>

                <!-- Overview Metrics -->
                <div class="section-heading">Core Metrics</div>
                <div class="metric-grid">
                    <div class="m-card">
                        <div class="m-header">
                            <span>Total Colleges</span>
                            <i class="ph ph-buildings m-icon icon-blue"></i>
                        </div>
                        <div class="m-value"><?php echo getMetric('total_colleges', $metrics); ?></div>
                    </div>
                    <div class="m-card">
                        <div class="m-header">
                            <span>Total Exams</span>
                            <i class="ph ph-exam m-icon icon-yellow"></i>
                        </div>
                        <div class="m-value"><?php echo getMetric('total_exams', $metrics); ?></div>
                    </div>
                    <div class="m-card">
                        <div class="m-header">
                            <span>Total Users</span>
                            <i class="ph ph-users m-icon icon-green"></i>
                        </div>
                        <div class="m-value"><?php echo getMetric('total_users', $metrics); ?></div>
                    </div>
                    <div class="m-card">
                        <div class="m-header">
                            <span>Daily Leads</span>
                            <i class="ph ph-funnel m-icon icon-purple"></i>
                        </div>
                        <div class="m-value"><?php echo getMetric('daily_leads', $metrics); ?></div>
                    </div>
                    <div class="m-card">
                        <div class="m-header">
                            <span>Active Sessions</span>
                            <i class="ph ph-activity m-icon icon-blue"></i>
                        </div>
                        <div class="m-value"><?php echo getMetric('active_sessions', $metrics); ?></div>
                    </div>
                    <div class="m-card">
                        <div class="m-header">
                            <span>Pending Mods</span>
                            <i class="ph ph-warning-circle m-icon icon-red"></i>
                        </div>
                        <div class="m-value"><?php echo getMetric('pending_moderation', $metrics); ?></div>
                    </div>
                    <div class="m-card">
                        <div class="m-header">
                            <span>New Signups Today</span>
                            <i class="ph ph-user-plus m-icon icon-green"></i>
                        </div>
                        <div class="m-value"><?php echo getMetric('new_signups_today', $metrics); ?></div>
                    </div>
                    <div class="m-card">
                        <div class="m-header">
                            <span>Page Views</span>
                            <i class="ph ph-eye m-icon icon-purple"></i>
                        </div>
                        <div class="m-value"><?php echo getMetric('page_views_today', $metrics); ?></div>
                    </div>
                </div>

                <!-- Revenue & Traffic -->
                <div class="section-heading">Financial & Traffic Snapshot</div>
                <div class="two-col">
                    <div class="panel">
                        <h3><i class="ph ph-currency-inr" style="color:var(--primary);"></i> Revenue Breakdown</h3>
                        <div class="metric-grid" style="grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 0;">
                            <div class="m-card" style="padding: 16px;">
                                <div class="m-header" style="font-size:0.75rem;">Today's Revenue</div>
                                <div class="m-value" style="font-size:1.4rem;">₹<?php echo getMetric('revenue_today', $metrics); ?></div>
                            </div>
                            <div class="m-card" style="padding: 16px;">
                                <div class="m-header" style="font-size:0.75rem;">Monthly Revenue</div>
                                <div class="m-value" style="font-size:1.4rem;">₹<?php echo getMetric('monthly_revenue', $metrics); ?></div>
                            </div>
                            <div class="m-card" style="padding: 16px;">
                                <div class="m-header" style="font-size:0.75rem;">Lead Revenue</div>
                                <div class="m-value" style="font-size:1.4rem;">₹<?php echo getMetric('lead_revenue', $metrics); ?></div>
                            </div>
                            <div class="m-card" style="padding: 16px;">
                                <div class="m-header" style="font-size:0.75rem;">Ad & Comm.</div>
                                <div class="m-value" style="font-size:1.4rem;">₹<?php echo getMetric('ad_revenue', $metrics); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="panel">
                        <h3><i class="ph ph-trend-up" style="color:var(--accent);"></i> Traffic Quality</h3>
                        <div style="display:flex; flex-direction:column; gap:20px; margin-top:24px;">
                            <div>
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span style="font-weight:600;">Bounce Rate</span>
                                    <span style="font-weight:700; color:var(--text-dark);"><?php echo getMetric('bounce_rate', $metrics); ?>%</span>
                                </div>
                                <div style="width:100%; height:8px; background:#f8fafc; border-radius:4px; overflow:hidden;">
                                    <div style="width: <?php echo getMetric('bounce_rate', $metrics); ?>%; height:100%; background:var(--accent);"></div>
                                </div>
                            </div>
                            <div style="padding: 16px; background:#f8fafc; border-radius:8px; border:1px solid var(--border-color);">
                                <h4 style="font-size:0.875rem; color:var(--text-muted); margin-bottom:12px; text-transform:uppercase;">Top Search Queries</h4>
                                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                                    <span class="badge">MBA in Delhi</span>
                                    <span class="badge">Top IITs</span>
                                    <span class="badge">NEET 2026</span>
                                    <span class="badge">B.Tech CS</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts & Activity -->
                <div class="two-col">
                    <div class="panel">
                        <h3><i class="ph ph-bell-ringing" style="color: #19376d;"></i> System Alerts</h3>
                        <div>
                            <?php if(empty($alerts)): ?>
                                <p style="color:var(--text-muted); text-align:center; padding:20px;">No active alerts.</p>
                            <?php else: ?>
                                <?php foreach($alerts as $alert): 
                                    $alertClass = 'alert-low';
                                    if(in_array($alert['severity'], ['high', 'critical'])) $alertClass = 'alert-high';
                                    if($alert['severity'] == 'medium') $alertClass = 'alert-medium';
                                ?>
                                <div class="alert-item <?php echo $alertClass; ?>">
                                    <i class="ph-fill ph-warning-circle"></i>
                                    <div class="alert-content">
                                        <p><?php echo htmlspecialchars($alert['message']); ?></p>
                                        <div class="alert-meta">
                                            <span style="font-weight:600; text-transform:capitalize;"><?php echo $alert['alert_type']; ?></span> • 
                                            <?php echo date('M d, H:i', strtotime($alert['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="panel">
                        <h3><i class="ph ph-clock-counter-clockwise" style="color: var(--primary);"></i> Activity Feed</h3>
                        <div class="activity-feed">
                            <?php if(empty($activities)): ?>
                                <p style="color:var(--text-muted);">No recent activity.</p>
                            <?php else: ?>
                                <?php foreach($activities as $act): ?>
                                <div class="activity-item">
                                    <p><strong><?php echo htmlspecialchars($act['action']); ?></strong> on <span class="badge" style="text-transform:capitalize;"><?php echo htmlspecialchars($act['entity_type']); ?></span></p>
                                    <span><?php echo date('M d, Y h:i A', strtotime($act['created_at'])); ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

</body>
</html>
