<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
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
        .page-header h2 { font-size: 2rem; font-weight: 800; display:flex; align-items:center; gap:10px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 24px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; }
        .stat-card .icon { width: 48px; height: 48px; border-radius: 12px; background: #F8FAFC; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 16px; }
        .stat-card .value { font-size: 1.8rem; font-weight: 800; color: var(--text-dark); margin-bottom: 4px; }
        .stat-card .label { color: var(--text-muted); font-size: 0.9rem; font-weight: 600; }
        
        .chart-container { background: #fff; padding: 24px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); margin-bottom: 30px; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar"><div class="user-profile"><span>Admin</span></div></header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-chart-line-up" style="color:var(--primary);"></i> Traffic Analytics</h2>
                    <p style="color:var(--text-muted); margin-top:4px;">Live overview of your portal's traffic and engagement.</p>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <select style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ddd; outline:none; background:#fff;">
                        <option>Last 7 Days</option>
                        <option>Last 30 Days</option>
                        <option>This Month</option>
                    </select>
                    <button style="background:var(--primary); color:white; border:none; padding:10px 16px; border-radius:6px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px;"><i class="ph ph-download-simple"></i> Export</button>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon"><i class="ph ph-users"></i></div>
                    <div class="value">124,592</div>
                    <div class="label">Total Page Views</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="ph ph-user-focus"></i></div>
                    <div class="value">45,102</div>
                    <div class="label">Unique Visitors</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="ph ph-clock"></i></div>
                    <div class="value">02:45</div>
                    <div class="label">Avg. Session Duration</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="ph ph-arrow-u-down-left"></i></div>
                    <div class="value">42.8%</div>
                    <div class="label">Bounce Rate</div>
                </div>
            </div>

            <div class="chart-container">
                <h3 style="margin-bottom: 20px; font-size: 1.1rem; color: var(--text-dark);">Traffic Overview</h3>
                <canvas id="trafficChart" height="80"></canvas>
            </div>

        </div>
    </main>
</div>

<script>
    const ctx = document.getElementById('trafficChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Page Views',
                data: [12000, 19000, 15000, 22000, 18000, 25000, 30000],
                borderColor: '#19376D',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
</body>
</html>
