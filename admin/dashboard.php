<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

// Fetch today's metrics
$stmt = $pdo->query("SELECT metric_key, metric_value FROM dashboard_snapshots WHERE DATE(recorded_at) = CURDATE()");
$metrics = [];
while ($row = $stmt->fetch()) { $metrics[$row['metric_key']] = $row['metric_value']; }

// Fetch Layouts and Widgets (Mocking logic if DB is empty to show UI)
$layoutsQuery = $pdo->query("SELECT * FROM dashboard_layouts WHERE is_default = 1 LIMIT 1");
$defaultLayout = $layoutsQuery->fetch(PDO::FETCH_ASSOC);

function getMetric($key, $metrics, $default = 0) {
    return isset($metrics[$key]) ? number_format($metrics[$key], strpos($metrics[$key], '.') !== false ? 2 : 0) : $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Control Center | AdmissionSeason</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f1f5f9; }
        .admin-layout { display: flex; min-height: 100vh; }
        
        /* Sidebar styling remains external in sidebar.php */
        .sidebar { width: 260px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 50; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-header .logo { font-size: 1.2rem; color: #f8fafc; display:flex; align-items:center; gap:8px; font-weight:700; }
        .sidebar-nav { padding: 16px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #cbd5e1; transition: all 0.2s; font-size:0.95rem; text-decoration:none; }
        .sidebar-nav a:hover, .sidebar-nav a.active { color: #fff; background: rgba(255,255,255,0.05); border-left: 3px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.2rem; }
        
        /* Main Content */
        .main-content { flex: 1; margin-left: 260px; display: flex; flex-direction: column; }
        
        /* Top Header */
        .topbar { height: 64px; background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 40; }
        
        .header-left { display: flex; align-items: center; gap: 16px; }
        .env-badge { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; border: 1px solid #bbf7d0; }
        .global-search { display: flex; align-items: center; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 12px; width: 300px; color: #64748b; cursor: pointer; transition: all 0.2s; }
        .global-search:hover { border-color: #cbd5e1; background: #fff; }
        .global-search span { margin-left: auto; font-size: 0.7rem; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-weight: 600; }
        
        .header-right { display: flex; align-items: center; gap: 16px; }
        .header-icon { font-size: 1.25rem; color: #64748b; cursor: pointer; position: relative; padding: 4px; border-radius: 6px; transition: all 0.2s; }
        .header-icon:hover { background: #f1f5f9; color: #0f172a; }
        .header-icon.active::after { content:''; position:absolute; top:4px; right:4px; width:8px; height:8px; background:#ef4444; border-radius:50%; border:2px solid #fff; }
        .avatar { width: 32px; height: 32px; border-radius: 50%; background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size:0.85rem; cursor:pointer; }
        
        /* Filter Bar */
        .filter-bar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 12px 24px; display: flex; align-items: center; gap: 12px; overflow-x: auto; }
        .filter-btn { display: flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 500; color: #475569; cursor: pointer; transition: all 0.2s; white-space:nowrap; }
        .filter-btn:hover { background: #f1f5f9; border-color: #cbd5e1; color: #0f172a; }
        .filter-btn.active { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
        .filter-divider { width: 1px; height: 24px; background: #e2e8f0; margin: 0 8px; }
        
        .content-area { padding: 24px; display: flex; flex-direction: column; gap: 24px; }
        
        /* KPI Strip */
        .kpi-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position:relative; overflow:hidden; }
        .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .kpi-title { font-size: 0.85rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .kpi-icon { padding: 6px; border-radius: 8px; font-size: 1.25rem; }
        .kpi-value-row { display: flex; align-items: baseline; gap: 12px; }
        .kpi-value { font-size: 2rem; font-weight: 800; color: #0f172a; }
        .kpi-trend { font-size: 0.85rem; font-weight: 600; display:flex; align-items:center; gap:4px; padding:2px 8px; border-radius:20px; }
        .trend-up { background: #dcfce7; color: #166534; }
        .trend-down { background: #fee2e2; color: #991b1b; }
        .sparkline-container { height: 40px; margin-top: 16px; width:100%; }
        
        /* Dynamic Grid Layouts */
        .widget-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; grid-auto-rows: minmax(100px, auto); }
        .widget-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; display:flex; flex-direction:column; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        
        /* Grid Spans */
        .col-span-12 { grid-column: span 12; }
        .col-span-8 { grid-column: span 8; }
        .col-span-6 { grid-column: span 6; }
        .col-span-4 { grid-column: span 4; }
        .col-span-3 { grid-column: span 3; }
        
        .row-span-4 { grid-row: span 4; }
        .row-span-3 { grid-row: span 3; }
        .row-span-2 { grid-row: span 2; }
        
        .widget-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .widget-title { font-weight: 700; font-size: 1rem; color: #0f172a; display:flex; align-items:center; gap:8px; }
        .widget-actions { display:flex; gap:8px; }
        .widget-action-btn { color:#94a3b8; cursor:pointer; transition:all 0.2s; }
        .widget-action-btn:hover { color:#0f172a; }
        .widget-body { padding: 20px; flex:1; overflow-y:auto; position:relative; }
        
        /* Feeds and Alerts */
        .feed-item { display:flex; gap:16px; padding:12px 0; border-bottom:1px solid #f1f5f9; }
        .feed-item:last-child { border-bottom:none; }
        .feed-icon { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .icon-blue { background:#eff6ff; color:#3b82f6; }
        .icon-green { background:#dcfce7; color:#22c55e; }
        .icon-orange { background:#ffedd5; color:#f97316; }
        .icon-purple { background:#f3e8ff; color:#a855f7; }
        .feed-content p { font-size:0.9rem; color:#1e293b; margin:0 0 4px 0; font-weight:500; }
        .feed-meta { font-size:0.75rem; color:#64748b; }
        
        .alert-item { display:flex; align-items:flex-start; gap:12px; padding:12px 16px; border-radius:8px; margin-bottom:12px; border-left:4px solid transparent; background:#f8fafc; }
        .alert-high { border-left-color:#ef4444; }
        .alert-medium { border-left-color:#f59e0b; }
        .alert-title { font-weight:600; font-size:0.9rem; color:#0f172a; margin-bottom:4px; }
        .alert-desc { font-size:0.8rem; color:#475569; }
        
        /* AI Insight */
        .ai-insight { background: linear-gradient(135deg, #fdf4ff 0%, #f3e8ff 100%); border: 1px solid #e9d5ff; border-radius: 12px; padding: 20px; display:flex; gap:16px; }
        .ai-icon { width:40px; height:40px; background:#fff; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:#a855f7; box-shadow:0 2px 4px rgba(168,85,247,0.1); }
        
    </style>
</head>
<body>

    <div class="admin-layout">
        <!-- Enterprise Navigation Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header UI -->
            <header class="topbar">
                <div class="header-left">
                    <div class="env-badge">PRODUCTION</div>
                    <div class="global-search">
                        <i class="ph ph-magnifying-glass"></i>
                        <span style="background:transparent; margin-left:8px; font-size:0.9rem;">Search colleges, exams, users...</span>
                        <span>⌘ K</span>
                    </div>
                </div>
                <div class="header-right">
                    <i class="ph ph-sparkle header-icon" style="color:#a855f7;" title="AI Assistant"></i>
                    <i class="ph ph-bell header-icon active" title="Notifications"></i>
                    <i class="ph ph-envelope-simple header-icon" title="Messages"></i>
                    <div style="width:1px; height:24px; background:#e2e8f0; margin:0 8px;"></div>
                    <div class="avatar" title="Profile">A</div>
                </div>
            </header>

            <!-- Filter Bar UI -->
            <div class="filter-bar">
                <div class="filter-btn active"><i class="ph ph-calendar-blank"></i> Today</div>
                <div class="filter-btn">7D</div>
                <div class="filter-btn">30D</div>
                <div class="filter-btn">90D</div>
                <div class="filter-btn"><i class="ph ph-calendar"></i> Custom</div>
                <div class="filter-divider"></div>
                <div class="filter-btn"><i class="ph ph-funnel"></i> Add Filter +</div>
                <div class="filter-btn" style="border:none; background:transparent;"><i class="ph ph-globe"></i> All Regions</div>
                <div class="filter-btn" style="border:none; background:transparent;"><i class="ph ph-device-mobile"></i> All Devices</div>
            </div>

            <div class="content-area">
                
                <!-- KPI Strip UI -->
                <div class="kpi-strip">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Revenue Today</span>
                            <i class="ph ph-currency-inr kpi-icon" style="background:#eff6ff; color:#3b82f6;"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value">₹2.3L</span>
                            <span class="kpi-trend trend-up"><i class="ph-bold ph-trend-up"></i> +14%</span>
                        </div>
                        <div class="sparkline-container"><canvas id="spark1"></canvas></div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Active Users</span>
                            <i class="ph ph-users kpi-icon" style="background:#f3e8ff; color:#a855f7;"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value">1,482</span>
                            <span class="kpi-trend trend-up"><i class="ph-bold ph-trend-up"></i> +5.2%</span>
                        </div>
                        <div class="sparkline-container"><canvas id="spark2"></canvas></div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">New Leads</span>
                            <i class="ph ph-funnel kpi-icon" style="background:#ffedd5; color:#f97316;"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value">349</span>
                            <span class="kpi-trend trend-down"><i class="ph-bold ph-trend-down"></i> -2.1%</span>
                        </div>
                        <div class="sparkline-container"><canvas id="spark3"></canvas></div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">System Health</span>
                            <i class="ph ph-activity kpi-icon" style="background:#dcfce7; color:#22c55e;"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value">99.9%</span>
                            <span class="kpi-trend trend-up" style="background:#f8fafc; color:#64748b;">Optimal</span>
                        </div>
                        <div class="sparkline-container"><canvas id="spark4"></canvas></div>
                    </div>
                </div>

                <!-- AI Insights Row -->
                <div class="ai-insight col-span-12">
                    <div class="ai-icon"><i class="ph-fill ph-sparkle"></i></div>
                    <div>
                        <h4 style="font-weight:700; color:#4c1d95; margin-bottom:4px; font-size:1.05rem;">AI Insight: Lead Funnel Anomaly Detected</h4>
                        <p style="color:#6b21a8; font-size:0.9rem; line-height:1.4;">The conversion rate for "B.Tech in Pune" searches has dropped by 14% in the last 6 hours compared to the 30-day moving average. Consider reviewing the B.Tech Pune landing page performance.</p>
                        <button style="margin-top:12px; background:#a855f7; color:white; border:none; padding:6px 12px; border-radius:6px; font-size:0.8rem; font-weight:600; cursor:pointer;">Analyze Funnel</button>
                    </div>
                </div>

                <!-- Dynamic Widget Grid -->
                <!-- Note: In a fully implemented system, this grid is generated from layout_json in dashboard_layouts -->
                <div class="widget-grid">
                    
                    <!-- Chart Widget (w:8, h:3) -->
                    <div class="widget-panel col-span-8 row-span-3">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph ph-chart-line-up"></i> Revenue vs Traffic Trend</span>
                            <div class="widget-actions">
                                <i class="ph ph-arrows-out widget-action-btn" title="Fullscreen"></i>
                                <i class="ph ph-download-simple widget-action-btn" title="Export"></i>
                                <i class="ph ph-dots-three widget-action-btn" title="More"></i>
                            </div>
                        </div>
                        <div class="widget-body">
                            <canvas id="mainChart"></canvas>
                        </div>
                    </div>

                    <!-- Realtime Feed Widget (w:4, h:3) -->
                    <div class="widget-panel col-span-4 row-span-3">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph ph-clock-counter-clockwise"></i> Live Activity Feed</span>
                            <div class="widget-actions"><i class="ph ph-dots-three widget-action-btn"></i></div>
                        </div>
                        <div class="widget-body" style="padding:0 20px;">
                            <div class="feed-item">
                                <div class="feed-icon icon-orange"><i class="ph-fill ph-funnel"></i></div>
                                <div class="feed-content">
                                    <p>New Lead: Rohan Sharma</p>
                                    <div class="feed-meta">Interested in B.Tech • 2 mins ago</div>
                                </div>
                            </div>
                            <div class="feed-item">
                                <div class="feed-icon icon-green"><i class="ph-fill ph-check-circle"></i></div>
                                <div class="feed-content">
                                    <p>Payment Successful</p>
                                    <div class="feed-meta">₹5,000 for Premium Listing • 14 mins ago</div>
                                </div>
                            </div>
                            <div class="feed-item">
                                <div class="feed-icon icon-blue"><i class="ph-fill ph-star"></i></div>
                                <div class="feed-content">
                                    <p>New Review Submitted</p>
                                    <div class="feed-meta">IIT Bombay • 4.5 Stars • 28 mins ago</div>
                                </div>
                            </div>
                            <div class="feed-item">
                                <div class="feed-icon icon-purple"><i class="ph-fill ph-buildings"></i></div>
                                <div class="feed-content">
                                    <p>College Data Updated</p>
                                    <div class="feed-meta">SRM University • By Admin • 1 hr ago</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Center Widget (w:6, h:2) -->
                    <div class="widget-panel col-span-6 row-span-2">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph ph-warning"></i> Incident & Alert Center</span>
                            <div class="widget-actions"><span style="font-size:0.8rem; background:#f1f5f9; padding:2px 8px; border-radius:12px; font-weight:600;">View All</span></div>
                        </div>
                        <div class="widget-body">
                            <div class="alert-item alert-high">
                                <i class="ph-fill ph-warning-circle" style="color:#ef4444; font-size:1.2rem; margin-top:2px;"></i>
                                <div>
                                    <div class="alert-title">Search API Latency Spike</div>
                                    <div class="alert-desc">Search API response time exceeded 2000ms. Source: Infrastructure.</div>
                                    <div style="font-size:0.75rem; color:#94a3b8; margin-top:4px;">10 mins ago • Status: Open</div>
                                </div>
                            </div>
                            <div class="alert-item alert-medium">
                                <i class="ph-fill ph-warning" style="color:#f59e0b; font-size:1.2rem; margin-top:2px;"></i>
                                <div>
                                    <div class="alert-title">Lead Delivery Failed</div>
                                    <div class="alert-desc">Failed to sync 12 leads to College CRM Webhook.</div>
                                    <div style="font-size:0.75rem; color:#94a3b8; margin-top:4px;">45 mins ago • Status: Acknowledged</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Colleges Widget (w:6, h:2) -->
                    <div class="widget-panel col-span-6 row-span-2">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph ph-chart-bar"></i> Top Performing Entities</span>
                            <div class="widget-actions">
                                <select style="border:1px solid #e2e8f0; border-radius:4px; font-size:0.8rem; padding:2px 4px;"><option>Colleges</option><option>Exams</option></select>
                            </div>
                        </div>
                        <div class="widget-body" style="padding:0;">
                            <table style="width:100%; border-collapse:collapse; text-align:left;">
                                <tr style="border-bottom:1px solid #e2e8f0; background:#f8fafc;">
                                    <th style="padding:10px 20px; font-size:0.8rem; color:#64748b; font-weight:600;">Name</th>
                                    <th style="padding:10px 20px; font-size:0.8rem; color:#64748b; font-weight:600;">Views</th>
                                    <th style="padding:10px 20px; font-size:0.8rem; color:#64748b; font-weight:600;">Leads</th>
                                </tr>
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:12px 20px; font-weight:600; font-size:0.9rem;">IIT Bombay</td>
                                    <td style="padding:12px 20px; font-size:0.9rem;">12,450</td>
                                    <td style="padding:12px 20px; font-size:0.9rem; color:#16a34a;">+432</td>
                                </tr>
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:12px 20px; font-weight:600; font-size:0.9rem;">BITS Pilani</td>
                                    <td style="padding:12px 20px; font-size:0.9rem;">9,210</td>
                                    <td style="padding:12px 20px; font-size:0.9rem; color:#16a34a;">+289</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 20px; font-weight:600; font-size:0.9rem;">SRM University</td>
                                    <td style="padding:12px 20px; font-size:0.9rem;">8,105</td>
                                    <td style="padding:12px 20px; font-size:0.9rem; color:#16a34a;">+210</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- Chart Implementations -->
    <script>
        // Common sparkline config
        const sparklineOptions = {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: { x: { display: false }, y: { display: false, min: 0 } },
            elements: { point: { radius: 0 }, line: { tension: 0.4, borderWidth: 2 } }
        };

        // Sparklines
        new Chart(document.getElementById('spark1').getContext('2d'), {
            type: 'line',
            data: { labels: ['1','2','3','4','5','6','7'], datasets: [{ data: [65, 59, 80, 81, 56, 85, 100], borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', fill: true }] },
            options: sparklineOptions
        });
        new Chart(document.getElementById('spark2').getContext('2d'), {
            type: 'line',
            data: { labels: ['1','2','3','4','5','6','7'], datasets: [{ data: [12, 19, 15, 25, 22, 30, 28], borderColor: '#a855f7', backgroundColor: 'rgba(168, 85, 247, 0.1)', fill: true }] },
            options: sparklineOptions
        });
        new Chart(document.getElementById('spark3').getContext('2d'), {
            type: 'line',
            data: { labels: ['1','2','3','4','5','6','7'], datasets: [{ data: [45, 40, 38, 35, 42, 38, 30], borderColor: '#f97316', backgroundColor: 'rgba(249, 115, 22, 0.1)', fill: true }] },
            options: sparklineOptions
        });
        new Chart(document.getElementById('spark4').getContext('2d'), {
            type: 'line',
            data: { labels: ['1','2','3','4','5','6','7'], datasets: [{ data: [99, 99.5, 99.9, 99.9, 99.8, 99.9, 99.9], borderColor: '#22c55e', backgroundColor: 'rgba(34, 197, 94, 0.1)', fill: true }] },
            options: { ...sparklineOptions, scales: { y: { min: 90, display:false } } }
        });

        // Main Chart
        new Chart(document.getElementById('mainChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [
                    {
                        label: 'Revenue (₹)',
                        data: [12000, 19000, 15000, 25000, 22000, 30000, 28000],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Traffic (Visits)',
                        data: [5000, 7000, 6000, 9000, 8000, 12000, 11000],
                        borderColor: '#a855f7',
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } }
                },
                scales: {
                    y: { type: 'linear', display: true, position: 'left', grid: { color: '#f1f5f9' } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>
