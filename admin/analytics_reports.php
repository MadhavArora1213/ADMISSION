<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $type = $_POST['report_type'] ?? 'Analytics Report';
    $format = $_POST['format'] ?? 'csv';
    
    if ($format === 'csv') {
        require_once 'db.php';
        // Generate a real CSV file dynamically
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.str_replace(' ', '_', strtolower($type)).'_'.date('Ymd').'.csv"');
        
        $output = fopen('php://output', 'w');
        
        if ($type === 'Lead Generation') {
            fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Course Interested', 'Status', 'Created At']);
            try {
                $stmt = $pdo->query("SELECT id, name, email, phone, course_interested, status, created_at FROM leads ORDER BY created_at DESC");
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
            } catch (Exception $e) {}
            
        } elseif ($type === 'College Metrics') {
            fputcsv($output, ['ID', 'Name', 'City', 'State', 'Type', 'Status', 'Created At']);
            try {
                $stmt = $pdo->query("SELECT id, name, city, state, college_type, status, created_at FROM colleges ORDER BY name ASC");
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
            } catch (Exception $e) {}
            
        } elseif ($type === 'Revenue Report') {
            fputcsv($output, ['Commission ID', 'Application ID', 'College', 'Percentage', 'Earned (INR)', 'Status', 'Payout Date']);
            try {
                $stmt = $pdo->query("SELECT c.id, c.application_id, col.name, c.commission_pct, c.commission_earned, c.commission_status, c.payout_date 
                                     FROM commissions c 
                                     LEFT JOIN colleges col ON c.college_id = col.id 
                                     ORDER BY c.created_at DESC");
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
            } catch (Exception $e) {}
            
        } elseif ($type === 'Traffic Summary') {
            fputcsv($output, ['Date', 'Page URL', 'Views', 'Unique Visitors', 'Source', 'Device', 'Avg Time (s)']);
            try {
                $stmt = $pdo->query("SELECT date, page_url, page_views, unique_visitors, traffic_source, device_type, avg_time_seconds FROM page_analytics ORDER BY date DESC");
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
            } catch (Exception $e) {
                fputcsv($output, ['No analytics records found in database.']);
            }
        } else {
            fputcsv($output, ['Notice']);
            fputcsv($output, ['No real data configured for this specific report type.']);
        }
        
        fclose($output);
        exit;
    } else {
        $msg = "PDF Generation requires a server-side library (like TCPDF). Please select 'CSV Spreadsheet' for a fully working download.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Engine | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
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
        
        .grid-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 24px; }
        .panel { background: #fff; padding: 24px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); }
        .panel h3 { font-size: 1.2rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; color: var(--text-dark); }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; color: var(--text-dark); }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; background: #fff; }
        
        .btn-primary { background: var(--primary); color: white; border: none; padding: 12px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; width: 100%; justify-content: center; margin-top: 10px; font-size: 1rem; }
        .btn-primary:hover { opacity: 0.9; }

        .report-list { display: flex; flex-direction: column; gap: 16px; }
        .report-item { display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--border-color); border-radius: 8px; background: #f8fafc; transition: all 0.2s; }
        .report-item:hover { border-color: var(--primary); background: rgba(11,36,71,0.04); }
        .report-info { display: flex; align-items: center; gap: 16px; }
        .report-icon { width: 44px; height: 44px; background: rgba(11,36,71,0.04); color: var(--primary); display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 1.6rem; }
        .report-icon.csv { background: rgba(11,36,71,0.04); color: #0B2447; }
        .report-title { font-weight: 700; color: var(--text-dark); margin-bottom: 4px; font-size: 1.05rem; }
        .report-meta { font-size: 0.85rem; color: var(--text-muted); }
        
        .btn-download { background: #fff; color: var(--text-dark); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; cursor:pointer;}
        .btn-download:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: rgba(15,23,42,0.06); color: #0B2447; margin-bottom: 20px; border: 1px solid rgba(15,23,42,0.06); font-weight:500; }
        .mobile-menu-btn{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#0f172a;padding:4px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:90}
        @media(max-width:768px){.sidebar{transform:translateX(-100%);z-index:100;transition:transform .3s}.sidebar.open{transform:translateX(0)}.sidebar-overlay.show{display:block}.main-content{margin-left:0}.mobile-menu-btn{display:block}.topbar{height:auto;min-height:56px;padding:10px 12px;justify-content:space-between}.content-area{padding:12px}.page-header{flex-direction:column;align-items:flex-start;gap:8px}.page-header h2{font-size:1.3rem}.grid-layout{grid-template-columns:1fr}.report-item{flex-direction:column;gap:10px;align-items:flex-start}.report-info{flex-direction:column}}
    </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar"><button class="mobile-menu-btn" id="mobile-menu-btn"><i class="ph ph-list"></i></button><div class="user-profile"><span>Admin</span></div></header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-file-pdf" style="color:var(--primary);"></i> Reports Engine</h2>
                    <p style="color:var(--text-muted); margin-top:4px;">Generate, schedule, and download custom analytics reports.</p>
                </div>
            </div>
            
            <?php if($msg): ?>
                <div class="msg-alert"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            
            <div class="grid-layout">
                <!-- Generate Report Panel -->
                <div class="panel">
                    <h3><i class="ph ph-magic-wand"></i> Generate Custom Report</h3>
                    <form action="analytics_reports.php" method="POST">
                        <input type="hidden" name="generate_report" value="1">
                        <div class="form-group">
                            <label>Report Type</label>
                            <select name="report_type" class="form-control">
                                <option value="Traffic Summary">Traffic & User Engagement</option>
                                <option value="Lead Generation">Lead Generation Summary</option>
                                <option value="College Metrics">College Performance Metrics</option>
                                <option value="Revenue Report">Revenue & Commissions</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date Range</label>
                            <select name="date_range" class="form-control">
                                <option value="30">Last 30 Days</option>
                                <option value="90">This Quarter</option>
                                <option value="365">Year to Date</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Format</label>
                            <select name="format" class="form-control">
                                <option value="csv">CSV Spreadsheet (Excel)</option>
                                <option value="pdf">PDF Document</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary"><i class="ph ph-download-simple"></i> Download Report</button>
                    </form>
                </div>

                <!-- Recent Reports Panel -->
                <div class="panel">
                    <h3><i class="ph ph-clock-counter-clockwise"></i> Recently Generated Reports</h3>
                    <div class="report-list">
                        
                        <div class="report-item">
                            <div class="report-info">
                                <div class="report-icon csv"><i class="ph ph-file-csv"></i></div>
                                <div>
                                    <div class="report-title">Lead Generation Data (Q1 2026)</div>
                                    <div class="report-meta">Generated by Admin • Yesterday • 14.1 MB</div>
                                </div>
                            </div>
                            <form action="analytics_reports.php" method="POST" style="margin:0;">
                                <input type="hidden" name="generate_report" value="1">
                                <input type="hidden" name="report_type" value="Lead Generation Data (Q1 2026)">
                                <input type="hidden" name="format" value="csv">
                                <button type="submit" class="btn-download"><i class="ph ph-download-simple"></i> Download</button>
                            </form>
                        </div>

                        <div class="report-item">
                            <div class="report-info">
                                <div class="report-icon csv"><i class="ph ph-file-csv"></i></div>
                                <div>
                                    <div class="report-title">Top Colleges Engagement Report</div>
                                    <div class="report-meta">Generated by System • 3 days ago • 1.1 MB</div>
                                </div>
                            </div>
                            <form action="analytics_reports.php" method="POST" style="margin:0;">
                                <input type="hidden" name="generate_report" value="1">
                                <input type="hidden" name="report_type" value="Top Colleges Engagement">
                                <input type="hidden" name="format" value="csv">
                                <button type="submit" class="btn-download"><i class="ph ph-download-simple"></i> Download</button>
                            </form>
                        </div>

                    </div>
                </div>
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
