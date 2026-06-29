<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // header('Location: index.php');
    // exit;
}
require_once 'db.php';

// ── Helper: safe count from any table ──
function safeCount($pdo, $sql) {
    try {
        return (int) $pdo->query($sql)->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// ── KPI Counts (live from actual tables) ──
$totalColleges   = safeCount($pdo, "SELECT COUNT(*) FROM colleges");
$totalExams      = safeCount($pdo, "SELECT COUNT(*) FROM exams");
$totalUsers      = safeCount($pdo, "SELECT COUNT(*) FROM users");
$signupsToday    = safeCount($pdo, "SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
$totalLeads      = safeCount($pdo, "SELECT COUNT(*) FROM leads");
$dailyLeads      = safeCount($pdo, "SELECT COUNT(*) FROM leads WHERE DATE(created_at) = CURDATE()");
$totalReviews    = safeCount($pdo, "SELECT COUNT(*) FROM reviews");
$pendingReviews  = safeCount($pdo, "SELECT COUNT(*) FROM reviews WHERE moderation_status = 'pending'");
$totalArticles   = safeCount($pdo, "SELECT COUNT(*) FROM articles");
$publishedArticles = safeCount($pdo, "SELECT COUNT(*) FROM articles WHERE status = 'published'");
$totalApplications = safeCount($pdo, "SELECT COUNT(*) FROM applications");
$totalScholarships = safeCount($pdo, "SELECT COUNT(*) FROM scholarships");
$totalUniversities = safeCount($pdo, "SELECT COUNT(*) FROM universities");
$totalCourses    = safeCount($pdo, "SELECT COUNT(*) FROM courses");
$activeSubscriptions = safeCount($pdo, "SELECT COUNT(*) FROM subscriptions WHERE status = 'active'");
$pendingModeration = safeCount($pdo, "SELECT COUNT(*) FROM moderation_queue WHERE status = 'pending'");

// ── Revenue (live) ──
$totalRevenue = 0;
$revenueToday = 0;
try {
    $totalRevenue = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='success'")->fetchColumn();
    $revenueToday = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='success' AND DATE(paid_at) = CURDATE()")->fetchColumn();
} catch (Exception $e) {}

$monthlyRevenue = 0;
try {
    $monthlyRevenue = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE payment_status='paid' AND MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE())")->fetchColumn();
} catch (Exception $e) {}

$leadRevenue = 0;
try {
    $leadRevenue = (float) $pdo->query("SELECT COALESCE(SUM(revenue_attributed),0) FROM leads WHERE revenue_attributed > 0")->fetchColumn();
} catch (Exception $e) {}

$subscriptionRevenue = $monthlyRevenue;
$adRevenue = 0;
try {
    $adRevenue = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM ad_products WHERE status='active'")->fetchColumn();
} catch (Exception $e) {}
$commissionEarned = 0;
try {
    $col = $pdo->query("SHOW COLUMNS FROM commissions LIKE 'commission_amount'")->fetch();
    if ($col) {
        $commissionEarned = (float) $pdo->query("SELECT COALESCE(SUM(commission_amount),0) FROM commissions WHERE status='paid'")->fetchColumn();
    } else {
        $col2 = $pdo->query("SHOW COLUMNS FROM commissions LIKE 'amount'")->fetch();
        if ($col2) {
            $commissionEarned = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM commissions WHERE status='paid'")->fetchColumn();
        }
    }
} catch (Exception $e) {}

// ── Revenue chart: monthly totals for last 6 months ──
$revenueChartData = [];
try {
    $revStmt = $pdo->query("SELECT DATE_FORMAT(invoice_date, '%b %Y') as label, SUM(total_amount) as total FROM invoices WHERE payment_status='paid' AND invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY YEAR(invoice_date), MONTH(invoice_date), label ORDER BY MIN(invoice_date) ASC");
    $revenueChartData = $revStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Pending counts by source ──
$pendingReviewsByStatus = safeCount($pdo, "SELECT COUNT(*) FROM reviews WHERE moderation_status = 'pending'");
$pendingModByQueue = safeCount($pdo, "SELECT COUNT(*) FROM moderation_queue WHERE status = 'pending'");

// ── Alerts (fixed column names: severity, message, status) ──
$alerts = [];
try {
    $alertsStmt = $pdo->query("SELECT * FROM admin_alerts WHERE status != 'resolved' ORDER BY created_at DESC LIMIT 5");
    $alerts = $alertsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Activity Feed with actor name ──
$activities = [];
try {
    $feedStmt = $pdo->query("
        SELECT 
            a.*,
            u.full_name AS actor_name
        FROM activity_log a
        LEFT JOIN users u ON a.actor_id = u.id
        ORDER BY a.created_at DESC 
        LIMIT 10
    ");
    $activities = $feedStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Top Colleges: real avg rating from reviews table ──
$topColleges = [];
try {
    $topColleges = $pdo->query("
        SELECT 
            c.name, 
            c.college_type,
            c.id,
            COALESCE(ROUND(AVG(r.overall_rating), 1), 0) AS computed_rating,
            COUNT(r.id) AS review_count
        FROM colleges c
        INNER JOIN reviews r ON r.college_id = c.id AND r.moderation_status = 'approved'
        WHERE c.status = 'active'
        GROUP BY c.id, c.name, c.college_type
        HAVING review_count > 0
        ORDER BY computed_rating DESC, review_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Recent Reviews with user name ──
$recentReviews = [];
try {
    $recentReviews = $pdo->query("
        SELECT 
            r.review_title, 
            r.overall_rating, 
            r.moderation_status, 
            r.created_at, 
            c.name AS college_name,
            u.full_name AS reviewer_name
        FROM reviews r 
        LEFT JOIN colleges c ON r.college_id = c.id 
        LEFT JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Exam Stats ──
$examStats = [];
try {
    $examStats = $pdo->query("SELECT exam_name, exam_level, status, applicants_last_year FROM exams ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Top Landing Pages (from page_analytics if available) ──
$topLandingPages = [];
try {
    $topLandingPages = $pdo->query("SELECT url_path as url, page_views as views FROM page_analytics ORDER BY page_views DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Top Search Queries (from search_queries if available) ──
$topSearchQueries = [];
try {
    $topSearchQueries = $pdo->query("SELECT query_text as `query`, search_count as `count` FROM search_queries ORDER BY search_count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Dashboard | AdmissionSeason</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        body { background-color: #F8FAFC; margin: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .admin-layout { display: flex; min-height: 100vh; }
        
        /* Sidebar styles */
        .sidebar { width: 260px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 50; transition: transform 0.3s ease; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-header .logo { font-size: 1.2rem; color: #f8fafc; display:flex; align-items:center; gap:8px; font-weight:700; }
        .sidebar-nav { padding: 16px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.6); transition: all 0.2s; font-size:0.95rem; text-decoration:none; }
        .sidebar-nav a:hover, .sidebar-nav a.active { color: #fff; background: rgba(255,255,255,0.05); border-left: 3px solid #19376D; }
        .sidebar-nav a i { font-size: 1.2rem; }

        .main-content { flex: 1; margin-left: 260px; display: flex; flex-direction: column; }
        
        /* Top Header */
        .topbar { height: 64px; background: #fff; border-bottom: 1px solid rgba(15,23,42,0.08); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 40; }
        .header-left { display: flex; align-items: center; gap: 16px; }
        .env-badge { background: rgba(11,36,71,0.04); color: #0B2447; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; border: 1px solid rgba(11,36,71,0.04); }
        .header-right { display: flex; align-items: center; gap: 16px; }
        .avatar { width: 32px; height: 32px; border-radius: 50%; background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size:0.85rem; cursor:pointer; }
        
        .content-area { padding: 24px; display: flex; flex-direction: column; gap: 24px; }
        
        .section-title { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: -8px; }

        /* KPI Grid for Overview */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .kpi-card { background: #fff; border: 1px solid rgba(15,23,42,0.08); border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .kpi-title { font-size: 0.85rem; font-weight: 600; color: rgba(15,23,42,0.45); text-transform: uppercase; letter-spacing: 0.05em; }
        .kpi-icon { padding: 8px; border-radius: 8px; font-size: 1.25rem; }
        .kpi-value-row { display: flex; align-items: baseline; gap: 12px; }
        .kpi-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; }
        .kpi-trend { font-size: 0.8rem; font-weight: 600; padding: 2px 8px; border-radius: 20px; }
        .trend-up { background: rgba(11,36,71,0.04); color: #0B2447; }
        .trend-down { background: rgba(15,23,42,0.06); color: #0B2447; }
        .trend-neutral { background: #F8FAFC; color: rgba(15,23,42,0.65); }

        /* Dynamic Grid Layouts */
        .widget-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; }
        .widget-panel { background: #fff; border: 1px solid rgba(15,23,42,0.08); border-radius: 12px; display:flex; flex-direction:column; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow:hidden; }
        
        .col-span-12 { grid-column: span 12; }
        .col-span-8 { grid-column: span 8; }
        .col-span-6 { grid-column: span 6; }
        .col-span-4 { grid-column: span 4; }
        .col-span-3 { grid-column: span 3; }
        
        .widget-header { padding: 16px 20px; border-bottom: 1px solid rgba(15,23,42,0.08); display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
        .widget-title { font-weight: 700; font-size: 1rem; color: #0f172a; display:flex; align-items:center; gap:8px; }
        .widget-body { padding: 20px; flex:1; overflow-y:auto; }

        /* Revenue Breakdown List */
        .revenue-list { list-style: none; padding: 0; margin: 0; }
        .revenue-list li { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #F8FAFC; }
        .revenue-list li:last-child { border-bottom: none; }
        .rev-label { color: rgba(15,23,42,0.45); font-weight: 500; font-size: 0.9rem; }
        .rev-val { font-weight: 700; color: #0f172a; }

        /* Tables */
        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th { padding: 10px 16px; font-size: 0.8rem; color: rgba(15,23,42,0.45); font-weight: 600; border-bottom: 1px solid rgba(15,23,42,0.08); }
        .data-table td { padding: 12px 16px; font-size: 0.9rem; border-bottom: 1px solid #F8FAFC; }
        .data-table tr:last-child td { border-bottom: none; }

        /* Feeds and Alerts */
        .feed-item { display:flex; gap:16px; padding:12px 0; border-bottom:1px solid #F8FAFC; }
        .feed-icon { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .feed-content p { font-size:0.9rem; color:rgba(15,23,42,0.9); margin:0 0 4px 0; font-weight:600; }
        .feed-meta { font-size:0.75rem; color:rgba(15,23,42,0.45); }
        
        .alert-item { display:flex; align-items:flex-start; gap:12px; padding:12px 16px; border-radius:8px; margin-bottom:12px; border-left:4px solid transparent; background:#f8fafc; }
        .alert-high { border-left-color:#0F172A; background:rgba(15,23,42,0.04); }
        .alert-medium { border-left-color:#19376D; }
        .alert-title { font-weight:600; font-size:0.9rem; color:#0f172a; margin-bottom:4px; }
        .alert-desc { font-size:0.8rem; color:rgba(15,23,42,0.65); }

        /* Colors for icons */
        .bg-blue { background: rgba(11,36,71,0.06); color: #19376D; }
        .bg-green { background: rgba(11,36,71,0.04); color: #0B2447; }
        .bg-purple { background: rgba(11,36,71,0.04); color: #19376D; }
        .bg-orange { background: rgba(11,36,71,0.04); color: #19376D; }
        .bg-red { background: rgba(15,23,42,0.04); color: #0F172A; }
        .bg-indigo { background: rgba(11,36,71,0.06); color: #19376D; }
        .bg-teal { background: rgba(11,36,71,0.04); color: #19376D; }

        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:49; }
        .mobile-toggle { display:none; position:fixed; bottom:20px; right:20px; z-index:60; width:50px; height:50px; border-radius:50%; background:#0f172a; color:#fff; border:none; font-size:1.4rem; cursor:pointer; box-shadow:0 4px 14px rgba(0,0,0,.3); align-items:center; justify-content:center; }

        @media(max-width:1024px){
            .sidebar { transform:translateX(-100%) !important; }
            .sidebar.open { transform:translateX(0) !important; }
            .sidebar-overlay.show { display:block; }
            .mobile-toggle { display:none !important; }
            #topbarToggle { display:flex !important; }
            .main-content { margin-left:0 !important; }
            .kpi-grid { grid-template-columns:repeat(2,1fr) !important; gap:12px !important; }
            .widget-grid { grid-template-columns:1fr !important; gap:16px !important; }
            .col-span-3, .col-span-4, .col-span-6, .col-span-8, .col-span-12 { grid-column:span 1 !important; }
            .content-area { padding:16px !important; gap:16px !important; }
        }
        @media(max-width:768px){
            .kpi-grid { grid-template-columns:1fr !important; gap:10px !important; }
            .widget-grid { grid-template-columns:1fr !important; gap:12px !important; }
            .topbar { padding:0 12px !important; height:56px !important; }
            .content-area { padding:12px !important; gap:12px !important; }
            .kpi-value { font-size:1.4rem !important; }
            .kpi-card { padding:14px !important; }
            .section-title { font-size:1rem !important; }
            .widget-header { padding:12px 16px !important; }
            .widget-body { padding:12px 16px !important; overflow-x:auto; }
            .data-table th, .data-table td { padding:8px 10px !important; font-size:0.78rem !important; }
            .revenue-list li { padding:8px 0 !important; }
            .rev-label, .rev-val { font-size:0.82rem !important; }
            .env-badge { display:none !important; }
            .alert-item { padding:10px 12px !important; }
            .feed-item { gap:10px !important; }
            .kpi-title { font-size:0.75rem !important; }
            .kpi-icon { font-size:1rem !important; padding:6px !important; }
            .widget-title { font-size:0.9rem !important; }
        }
        @media(max-width:480px){
            .kpi-card { padding:12px !important; }
            .kpi-header { margin-bottom:6px !important; }
            .widget-body { padding:10px 12px !important; overflow-x:auto; }
            .data-table { font-size:0.75rem !important; }
            .section-title { font-size:0.9rem !important; margin-top:4px !important; }
            .kpi-value { font-size:1.2rem !important; }
            .topbar { padding:0 10px !important; }
            .content-area { padding:10px !important; gap:10px !important; }
        }
    </style>
</head>
<body>

    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="topbar">
                <div class="header-left">
                    <button onclick="toggleSidebar()" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:#0f172a; display:none; padding:4px;" id="topbarToggle"><i class="ph ph-list"></i></button>
                    <div style="font-weight:700; color:#0f172a; margin-left:8px;">College Directory OS</div>
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
                            <span class="kpi-value"><?= number_format($totalColleges) ?></span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Total Exams</span>
                            <i class="ph-fill ph-exam kpi-icon bg-purple"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($totalExams) ?></span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Total Users</span>
                            <i class="ph-fill ph-users kpi-icon bg-indigo"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($totalUsers) ?></span>
                            <span class="kpi-trend trend-up">+<?= number_format($signupsToday) ?> Today</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Total Leads</span>
                            <i class="ph-fill ph-funnel kpi-icon bg-orange"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($totalLeads) ?></span>
                            <?php if($dailyLeads > 0): ?>
                            <span class="kpi-trend trend-up">+<?= number_format($dailyLeads) ?> Today</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ROW 2: Engagement & Moderation -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Total Reviews</span>
                            <i class="ph-fill ph-star kpi-icon bg-teal"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($totalReviews) ?></span>
                            <?php if($pendingReviews > 0): ?>
                            <span class="kpi-trend trend-down"><?= $pendingReviews ?> pending</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Pending Moderation</span>
                            <i class="ph-fill ph-shield-warning kpi-icon bg-red"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($pendingModeration + $pendingReviews) ?></span>
                        </div>
                        <div style="font-size:0.8rem; color:rgba(15,23,42,0.45); margin-top:8px;">Reviews, comments, q&a</div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Articles Published</span>
                            <i class="ph-fill ph-newspaper kpi-icon bg-blue"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($totalArticles) ?></span>
                            <span class="kpi-trend trend-neutral"><?= $publishedArticles ?> live</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Revenue (All Time)</span>
                            <i class="ph-fill ph-currency-inr kpi-icon bg-green"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value">₹<?= number_format($totalRevenue) ?></span>
                        </div>
                    </div>
                </div>

                <div class="section-title" style="margin-top:10px;">Platform Overview</div>
                
                <div class="widget-grid">
                    <!-- Content Summary -->
                    <div class="widget-panel col-span-8">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-chart-line-up"></i> Content Summary</span>
                        </div>
                        <div class="widget-body">
                            <ul class="revenue-list">
                                <li>
                                    <span class="rev-label"><i class="ph ph-buildings" style="margin-right:6px;"></i> Total Colleges</span>
                                    <span class="rev-val"><?= number_format($totalColleges) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label"><i class="ph ph-bank" style="margin-right:6px;"></i> Total Universities</span>
                                    <span class="rev-val"><?= number_format($totalUniversities) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label"><i class="ph ph-exam" style="margin-right:6px;"></i> Total Exams</span>
                                    <span class="rev-val"><?= number_format($totalExams) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label"><i class="ph ph-books" style="margin-right:6px;"></i> Total Courses</span>
                                    <span class="rev-val"><?= number_format($totalCourses) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label"><i class="ph ph-file-text" style="margin-right:6px;"></i> Applications</span>
                                    <span class="rev-val"><?= number_format($totalApplications) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label"><i class="ph ph-graduation-cap" style="margin-right:6px;"></i> Scholarships</span>
                                    <span class="rev-val"><?= number_format($totalScholarships) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label"><i class="ph ph-newspaper" style="margin-right:6px;"></i> Articles</span>
                                    <span class="rev-val"><?= number_format($totalArticles) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label"><i class="ph ph-medal" style="margin-right:6px;"></i> Rankings</span>
                                    <span class="rev-val"><?= number_format(safeCount($pdo, 'SELECT COUNT(*) FROM rankings')) ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Revenue Breakdown -->
                    <div class="widget-panel col-span-4">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-wallet"></i> Revenue</span>
                            <span style="font-weight:800; color:#0B2447;">₹<?= number_format($totalRevenue) ?></span>
                        </div>
                        <div class="widget-body">
                            <ul class="revenue-list">
                                <li>
                                    <span class="rev-label">Today</span>
                                    <span class="rev-val">₹<?= number_format($revenueToday) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label">This Month (Invoices)</span>
                                    <span class="rev-val">₹<?= number_format($monthlyRevenue) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label">Lead Revenue</span>
                                    <span class="rev-val">₹<?= number_format($leadRevenue) ?></span>
                                </li>
                                <li>
                                    <span class="rev-label">Commissions</span>
                                    <span class="rev-val">₹<?= number_format($commissionEarned) ?></span>
                                </li>
                            </ul>

                            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #F8FAFC;">
                                <div style="font-size:0.8rem; font-weight:600; color:rgba(15,23,42,0.65); margin-bottom:10px;">Quick Stats</div>
                                <ul class="revenue-list">
                                    <li>
                                        <span class="rev-label">Active Subscriptions</span>
                                        <span class="rev-val"><?= number_format($activeSubscriptions) ?></span>
                                    </li>
                                    <li>
                                        <span class="rev-label">Active Partners</span>
                                        <span class="rev-val"><?= number_format(safeCount($pdo, "SELECT COUNT(*) FROM partners WHERE status='active'")) ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- College Portal Stats -->
                <?php
                $pendingAccounts = safeCount($pdo, "SELECT COUNT(*) FROM college_accounts WHERE status='pending'");
                $approvedAccounts = safeCount($pdo, "SELECT COUNT(*) FROM college_accounts WHERE status='approved'");
                $totalAccounts = safeCount($pdo, "SELECT COUNT(*) FROM college_accounts");
                $pendingSubmissions = safeCount($pdo, "SELECT COUNT(*) FROM college_submissions WHERE status='pending'");
                ?>
                <div class="section-title" style="margin-top:10px;">College Portal</div>
                <div class="kpi-grid">
                    <a href="college_accounts.php" style="text-decoration:none;">
                        <div class="kpi-card" style="border-left:3px solid #19376D;">
                            <div class="kpi-header">
                                <span class="kpi-title">College Accounts</span>
                                <i class="ph-fill ph-graduation-cap kpi-icon bg-blue"></i>
                            </div>
                            <div class="kpi-value-row">
                                <span class="kpi-value"><?= number_format($totalAccounts) ?></span>
                                <?php if($pendingAccounts > 0): ?>
                                <span class="kpi-trend" style="background:#fef3c7;color:#92400e;"><?= $pendingAccounts ?> pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <a href="college_submissions.php" style="text-decoration:none;">
                        <div class="kpi-card" style="border-left:3px solid #059669;">
                            <div class="kpi-header">
                                <span class="kpi-title">Pending Submissions</span>
                                <i class="ph-fill ph-inbox kpi-icon bg-green"></i>
                            </div>
                            <div class="kpi-value-row">
                                <span class="kpi-value"><?= number_format($pendingSubmissions) ?></span>
                            </div>
                        </div>
                    </a>
                    <div class="kpi-card" style="border-left:3px solid #7c3aed;">
                        <div class="kpi-header">
                            <span class="kpi-title">Approved Accounts</span>
                            <i class="ph-fill ph-check-circle kpi-icon bg-purple"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span class="kpi-value"><?= number_format($approvedAccounts) ?></span>
                        </div>
                    </div>
                    <div class="kpi-card" style="border-left:3px solid #ea580c;">
                        <div class="kpi-header">
                            <span class="kpi-title">Portal Actions</span>
                            <i class="ph-fill ph-link kpi-icon bg-orange"></i>
                        </div>
                        <div class="kpi-value-row">
                            <span style="font-size:0.85rem;color:rgba(15,23,42,0.65);">Manage institute portal access, approve accounts & review submissions</span>
                        </div>
                    </div>
                </div>

                <div class="section-title" style="margin-top:10px;">Engagement & Activity</div>

                <div class="widget-grid">
                    <!-- Top Colleges -->
                    <div class="widget-panel col-span-4">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-medal"></i> Top Colleges</span>
                        </div>
                        <div class="widget-body" style="padding:0;">
                            <?php if(empty($topColleges)): ?>
                            <div style="padding:20px; color:rgba(15,23,42,0.4); font-size:0.9rem;">No college reviews yet.</div>
                            <?php else: ?>
                            <table class="data-table">
                                <tr><th>COLLEGE</th><th>RATING</th><th>REVIEWS</th></tr>
                                <?php foreach($topColleges as $c): ?>
                                <tr>
                                    <td style="font-weight:600; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($c['name']) ?></td>
                                    <td>
                                        <?php if($c['computed_rating'] > 0): ?>
                                        <span style="color:#0B2447; font-weight:700;"><?= number_format($c['computed_rating'], 1) ?></span> <i class="ph-fill ph-star" style="color:#19376D; font-size:0.75rem;"></i>
                                        <?php else: ?>
                                        <span style="color:rgba(15,23,42,0.3);">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($c['review_count']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Exam Stats -->
                    <div class="widget-panel col-span-4">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-exam"></i> Exam Overview</span>
                        </div>
                        <div class="widget-body" style="padding:0;">
                            <?php if(empty($examStats)): ?>
                            <div style="padding:20px; color:rgba(15,23,42,0.4); font-size:0.9rem;">No exam data yet.</div>
                            <?php else: ?>
                            <table class="data-table">
                                <tr><th>EXAM</th><th>LEVEL</th><th>STATUS</th></tr>
                                <?php foreach($examStats as $e): ?>
                                <tr>
                                    <td style="font-weight:600;"><?= htmlspecialchars($e['exam_name']) ?></td>
                                    <td style="text-transform:capitalize;"><?= $e['exam_level'] ?></td>
                                    <td>
                                        <?php
                                        $statusColor = $e['status'] === 'active' ? 'trend-up' : ($e['status'] === 'upcoming' ? 'trend-neutral' : 'trend-down');
                                        ?>
                                        <span class="kpi-trend <?= $statusColor ?>" style="font-size:0.75rem;"><?= ucfirst($e['status']) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Reviews -->
                    <div class="widget-panel col-span-4">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-star"></i> Recent Reviews</span>
                        </div>
                        <div class="widget-body" style="padding:12px 20px;">
                            <?php if(empty($recentReviews)): ?>
                            <div style="padding:20px; color:rgba(15,23,42,0.4); font-size:0.9rem;">No reviews yet.</div>
                            <?php else: ?>
                            <?php foreach($recentReviews as $rv): ?>
                            <div style="padding:10px 0; border-bottom:1px solid #F8FAFC;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <span style="font-weight:700; font-size:0.85rem; color:#0f172a; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($rv['review_title'] ?: 'Untitled') ?></span>
                                    <span style="color:#0B2447; font-weight:700; font-size:0.85rem;"><?= number_format($rv['overall_rating'], 1) ?> <i class="ph-fill ph-star" style="color:#19376D; font-size:0.65rem;"></i></span>
                                </div>
                                <div style="font-size:0.78rem; color:rgba(15,23,42,0.5); margin-bottom:2px;">
                                    <i class="ph ph-buildings" style="font-size:0.7rem; margin-right:2px;"></i><?= htmlspecialchars($rv['college_name'] ?: 'N/A') ?>
                                    &nbsp;&bull;&nbsp;
                                    <i class="ph ph-user" style="font-size:0.7rem; margin-right:2px;"></i><?= htmlspecialchars($rv['reviewer_name'] ?: 'Anonymous') ?>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <span style="font-size:0.7rem; color:rgba(15,23,42,0.35);"><?= date('M d, Y', strtotime($rv['created_at'])) ?></span>
                                    <?php
                                    $modColor = $rv['moderation_status'] === 'approved' ? 'trend-up' : ($rv['moderation_status'] === 'pending' ? 'trend-neutral' : 'trend-down');
                                    ?>
                                    <span class="kpi-trend <?= $modColor ?>" style="font-size:0.65rem; padding:1px 6px;"><?= ucfirst($rv['moderation_status']) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="section-title" style="margin-top:10px;">Content & SEO</div>

                <div class="widget-grid">
                    <!-- Top Landing Pages -->
                    <div class="widget-panel col-span-6">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-file-html"></i> Top Landing Pages</span>
                        </div>
                        <div class="widget-body" style="padding:0;">
                            <?php if(empty($topLandingPages)): ?>
                            <div style="padding:20px; color:rgba(15,23,42,0.4); font-size:0.9rem;">No page analytics data yet.</div>
                            <?php else: ?>
                            <table class="data-table">
                                <tr><th>URL PATH</th><th>VIEWS</th></tr>
                                <?php foreach($topLandingPages as $page): ?>
                                <tr>
                                    <td style="color:#19376D; font-family:monospace;"><?= htmlspecialchars($page['url']) ?></td>
                                    <td><?= number_format($page['views']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Top Search Queries -->
                    <div class="widget-panel col-span-6">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-magnifying-glass"></i> Top Search Queries</span>
                        </div>
                        <div class="widget-body" style="padding:0;">
                            <?php if(empty($topSearchQueries)): ?>
                            <div style="padding:20px; color:rgba(15,23,42,0.4); font-size:0.9rem;">No search analytics data yet.</div>
                            <?php else: ?>
                            <table class="data-table">
                                <tr><th>QUERY</th><th>COUNT</th></tr>
                                <?php foreach($topSearchQueries as $query): ?>
                                <tr>
                                    <td style="font-weight:500;"><?= htmlspecialchars($query['query']) ?></td>
                                    <td><?= number_format($query['count']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="section-title" style="margin-top:10px;">Alerts & Activity Feed</div>

                <div class="widget-grid">
                    <!-- Live Activity Feed -->
                    <div class="widget-panel col-span-8">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-clock-counter-clockwise"></i> Platform Activity Log</span>
                            <span style="font-size:0.8rem; color:#19376D; cursor:pointer;">View All</span>
                        </div>
                        <div class="widget-body">
                            <?php if(empty($activities)): ?>
                                <div style="color:rgba(15,23,42,0.4); font-size:0.9rem; padding:10px;">No recent activities.</div>
                            <?php else: ?>
                            <table class="data-table">
                                <tr><th>ACTION</th><th>DETAILS</th><th>BY</th><th>TIME</th></tr>
                                <?php foreach ($activities as $act): 
                                    $meta = json_decode($act['meta_json'], true) ?: [];
                                    $activityType = $act['activity_type'];
                                    $entityType = $act['entity_type'];
                                    $actorName = $act['actor_name'] ?: 'System';

                                    // Icon & color by entity type
                                    $icon = 'ph-star'; $iconColor = '#19376D';
                                    if ($entityType === 'lead') { $icon = 'ph-funnel'; $iconColor = '#19376D'; }
                                    if ($entityType === 'college') { $icon = 'ph-buildings'; $iconColor = '#0B2447'; }
                                    if ($entityType === 'exam') { $icon = 'ph-exam'; $iconColor = '#19376D'; }
                                    if ($entityType === 'review') { $icon = 'ph-star'; $iconColor = '#0B2447'; }
                                    if ($entityType === 'article') { $icon = 'ph-newspaper'; $iconColor = '#19376D'; }
                                    if ($activityType === 'delete') { $iconColor = '#dc2626'; }
                                    if ($activityType === 'flag') { $icon = 'ph-flag'; $iconColor = '#dc2626'; }

                                    // Build readable description
                                    $desc = '';
                                    if ($activityType === 'create' && $entityType === 'lead') {
                                        $name = $meta['name'] ?? 'Unknown';
                                        $course = $meta['course'] ?? '';
                                        $desc = "New lead created: <strong>".htmlspecialchars($name)."</strong>";
                                        if ($course) $desc .= " (".htmlspecialchars($course).")";
                                    } elseif ($activityType === 'update' && $entityType === 'college') {
                                        $field = $meta['field'] ?? 'status';
                                        $old = $meta['old'] ?? '';
                                        $new = $meta['new'] ?? '';
                                        $desc = "College <strong>".htmlspecialchars(ucfirst($field))."</strong> changed: ".htmlspecialchars($old)." &rarr; ".htmlspecialchars($new);
                                    } elseif ($activityType === 'create') {
                                        $desc = "Created new ".htmlspecialchars(ucfirst($entityType));
                                    } elseif ($activityType === 'update') {
                                        $desc = "Updated ".htmlspecialchars(ucfirst($entityType));
                                    } elseif ($activityType === 'delete') {
                                        $desc = "Deleted ".htmlspecialchars(ucfirst($entityType));
                                    } elseif ($activityType === 'login') {
                                        $desc = "User login recorded";
                                    } elseif ($activityType === 'flag') {
                                        $desc = "Flagged ".htmlspecialchars(ucfirst($entityType));
                                    } else {
                                        $desc = ucfirst(htmlspecialchars($activityType))." ".htmlspecialchars($entityType);
                                        if (!empty($meta)) {
                                            $desc .= " (".htmlspecialchars(implode(', ', array_map(fn($k,$v) => "$k: $v", array_keys($meta), $meta))).")";
                                        }
                                    }
                                ?>
                                <tr>
                                    <td style="white-space:nowrap;">
                                        <span style="display:inline-flex; align-items:center; gap:6px;">
                                            <i class="ph-fill <?= $icon ?>" style="color:<?= $iconColor ?>; font-size:1rem;"></i>
                                            <span style="font-weight:600; font-size:0.85rem; text-transform:capitalize;"><?= $activityType ?></span>
                                        </span>
                                    </td>
                                    <td style="font-size:0.85rem; color:rgba(15,23,42,0.75);"><?= $desc ?: 'Activity recorded' ?></td>
                                    <td style="font-size:0.82rem; white-space:nowrap;">
                                        <span style="display:inline-flex; align-items:center; gap:4px;">
                                            <span style="width:20px; height:20px; border-radius:50%; background:rgba(11,36,71,0.06); display:inline-flex; align-items:center; justify-content:center; font-size:0.6rem; font-weight:700; color:#0B2447;"><?= strtoupper(substr($actorName, 0, 1)) ?></span>
                                            <?= htmlspecialchars($actorName) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:0.8rem; color:rgba(15,23,42,0.4); white-space:nowrap;"><?= date('M d, H:i', strtotime($act['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Incident Center -->
                    <div class="widget-panel col-span-4">
                        <div class="widget-header">
                            <span class="widget-title"><i class="ph-fill ph-warning"></i> Incident & Alert Center</span>
                            <?php if(count($alerts) > 0): ?>
                            <span style="font-size:0.8rem; background:rgba(15,23,42,0.06); padding:2px 8px; border-radius:12px; font-weight:600;"><?= count($alerts) ?> Open</span>
                            <?php endif; ?>
                        </div>
                        <div class="widget-body">
                            <?php if(empty($alerts)): ?>
                                <div style="color:rgba(15,23,42,0.4); font-size:0.9rem;">No active alerts. System healthy.</div>
                            <?php endif; ?>
                            <?php foreach ($alerts as $alert): 
                                $severity = $alert['severity'] ?? 'low';
                                $isHigh = $severity === 'critical' || $severity === 'high';
                                $alertClass = $isHigh ? 'alert-high' : 'alert-medium';
                                $iconColor = $isHigh ? '#0F172A' : '#19376D';
                                $icon = $isHigh ? 'ph-warning-circle' : 'ph-warning';
                                $alertTitle = $alert['title'] ?? ($alert['alert_type'] ?? 'Alert');
                                $alertMessage = $alert['message'] ?? '';
                                $alertStatus = $alert['status'] ?? 'open';
                            ?>
                            <div class="alert-item <?= $alertClass ?>">
                                <i class="ph-fill <?= $icon ?>" style="color:<?= $iconColor ?>; font-size:1.2rem; margin-top:2px;"></i>
                                <div>
                                    <div class="alert-title"><?= htmlspecialchars(ucfirst($alertTitle)) ?> (<?= ucfirst($severity) ?>)</div>
                                    <div class="alert-desc"><?= htmlspecialchars($alertMessage) ?></div>
                                    <div style="font-size:0.75rem; color:rgba(15,23,42,0.4); margin-top:4px;"><?= date('M d, H:i', strtotime($alert['created_at'])) ?> &bull; Status: <?= ucfirst($alertStatus) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    <button class="mobile-toggle" id="mobileToggle" onclick="toggleSidebar()"><i class="ph ph-list"></i></button>

    <script>
        function toggleSidebar() {
            var sidebar = document.querySelector('.sidebar');
            var overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }
    </script>
</body>
</html>
