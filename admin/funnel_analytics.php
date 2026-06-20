<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funnel Analytics | AdmissionSeason Admin</title>
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
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 24px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; }
        .stat-card .icon { width: 48px; height: 48px; border-radius: 12px; background: #F8FAFC; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 16px; }
        .stat-card .value { font-size: 1.8rem; font-weight: 800; color: var(--text-dark); margin-bottom: 4px; }
        .stat-card .label { color: var(--text-muted); font-size: 0.9rem; font-weight: 600; }
        
        .chart-container { background: #fff; padding: 32px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); margin-bottom: 30px; display: flex; flex-direction: column; align-items: center;}
        
        .funnel-stage { width: 100%; max-width: 600px; margin-bottom: 12px; display: flex; align-items: center; gap: 20px;}
        .funnel-bar-container { flex: 1; height: 40px; background: #F8FAFC; border-radius: 6px; overflow: hidden; position: relative;}
        .funnel-bar { height: 100%; background: var(--primary); transition: width 1s ease-in-out;}
        .funnel-label { width: 150px; font-weight: 600; color: var(--text-dark); text-align: right;}
        .funnel-value { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #fff; font-weight: 700; font-size: 0.9rem; text-shadow: 0px 1px 2px rgba(0,0,0,0.2);}
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
                    <h2><i class="ph ph-funnel" style="color:var(--primary);"></i> Funnel Analytics</h2>
                    <p style="color:var(--text-muted); margin-top:4px;">Analyze user drop-off across key stages.</p>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <select style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ddd; outline:none; background:#fff;">
                        <option>Registration Funnel</option>
                        <option>College Application Funnel</option>
                    </select>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon"><i class="ph ph-users"></i></div>
                    <div class="value">10,240</div>
                    <div class="label">Total Funnel Entrants</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="ph ph-check-circle"></i></div>
                    <div class="value">1,480</div>
                    <div class="label">Successful Completions</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="ph ph-percent"></i></div>
                    <div class="value">14.4%</div>
                    <div class="label">Overall Conversion Rate</div>
                </div>
            </div>

            <div class="chart-container">
                <h3 style="margin-bottom: 30px; font-size: 1.2rem; color: var(--text-dark); width: 100%; text-align:left;">Registration Funnel Drop-off</h3>
                
                <div class="funnel-stage">
                    <div class="funnel-label">Visited Homepage</div>
                    <div class="funnel-bar-container">
                        <div class="funnel-bar" style="width: 100%; background: #19376D;"></div>
                        <div class="funnel-value">10,240 (100%)</div>
                    </div>
                </div>
                
                <div class="funnel-stage">
                    <div class="funnel-label">Clicked Register</div>
                    <div class="funnel-bar-container">
                        <div class="funnel-bar" style="width: 65%; background: #19376D;"></div>
                        <div class="funnel-value">6,656 (65%)</div>
                    </div>
                </div>
                
                <div class="funnel-stage">
                    <div class="funnel-label">Filled OTP Form</div>
                    <div class="funnel-bar-container">
                        <div class="funnel-bar" style="width: 40%; background: #19376D;"></div>
                        <div class="funnel-value">4,096 (40%)</div>
                    </div>
                </div>
                
                <div class="funnel-stage">
                    <div class="funnel-label">Verified Email</div>
                    <div class="funnel-bar-container">
                        <div class="funnel-bar" style="width: 25%; background: #19376D;"></div>
                        <div class="funnel-value">2,560 (25%)</div>
                    </div>
                </div>
                
                <div class="funnel-stage">
                    <div class="funnel-label">Completed Profile</div>
                    <div class="funnel-bar-container">
                        <div class="funnel-bar" style="width: 14%; background: #19376D;"></div>
                        <div class="funnel-value">1,480 (14.4%)</div>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>
</body>
</html>
