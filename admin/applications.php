<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : 'all';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$where = [];
$params = [];
if ($status_filter !== 'all') { $where[] = "a.status = ?"; $params[] = $status_filter; }
if ($payment_filter !== 'all') { $where[] = "a.payment_status = ?"; $params[] = $payment_filter; }
if ($search !== '') { 
    $where[] = "(a.application_number LIKE ? OR u.full_name LIKE ?)"; 
    $params[] = "%$search%"; 
    $params[] = "%$search%"; 
}
$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT a.*, u.full_name AS user_name, u.email AS user_email, c.name AS college_name, cr.course_name 
    FROM applications a 
    LEFT JOIN users u ON a.user_id = u.id 
    LEFT JOIN colleges c ON a.college_id = c.id
    LEFT JOIN courses cr ON a.course_id = cr.id
    $whereSQL 
    ORDER BY a.created_at DESC 
    LIMIT 100
");
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_apps = $pdo->query("SELECT count(*) FROM applications")->fetchColumn();
$pending_apps = $pdo->query("SELECT count(*) FROM applications WHERE status = 'submitted'")->fetchColumn();
$paid_apps = $pdo->query("SELECT count(*) FROM applications WHERE payment_status = 'paid'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications Management | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: #F8FAFC; margin: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-header .logo { font-size: 1.2rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; font-weight: 700; }
        .sidebar-nav { padding: 16px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.6); transition: all 0.2s; font-size: 0.95rem; text-decoration: none; }
        .sidebar-nav a:hover, .sidebar-nav a.active { color: #fff; background: rgba(255,255,255,0.05); border-left: 3px solid #19376D; }
        .sidebar-nav a i { font-size: 1.2rem; }
        .main-content { flex: 1; margin-left: 260px; display: flex; flex-direction: column; }
        .topbar { height: 64px; background: #fff; border-bottom: 1px solid rgba(15,23,42,0.08); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 40; }
        .content-area { padding: 24px; max-width: 1400px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 10px; color: #0f172a; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 12px; border: 1px solid rgba(15,23,42,0.08); padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .stat-card .num { font-size: 1.8rem; font-weight: 800; color: #0B2447; }
        .stat-card .label { font-size: 0.8rem; color: rgba(15,23,42,0.45); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; background: #fff; padding: 12px; border-radius: 12px; border: 1px solid rgba(15,23,42,0.08); }
        .filter-select { padding: 8px 12px; border: 1px solid rgba(15,23,42,0.1); border-radius: 8px; font-size: 0.85rem; outline: none; background: #fff; min-width: 0; }
        .search-box { display: flex; align-items: center; gap: 8px; border: 1px solid rgba(15,23,42,0.1); border-radius: 8px; padding: 7px 12px; margin-left: auto; flex: 1; min-width: 0; }
        .search-box input { border: none; outline: none; font-size: 0.85rem; width: 100%; min-width: 0; }
        .panel { background: #fff; border-radius: 12px; border: 1px solid rgba(15,23,42,0.08); box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; min-width: 650px; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid rgba(15,23,42,0.08); }
        th { font-weight: 700; color: rgba(15,23,42,0.45); text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.05em; background: #f8fafc; }
        tr:hover { background-color: #f8fafc; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; display: inline-block; text-transform: capitalize; white-space: nowrap; }
        .s-draft { background: #F8FAFC; color: rgba(15,23,42,0.65); }
        .s-submitted { background: rgba(11,36,71,0.06); color: #19376D; }
        .s-under_review { background: rgba(11,36,71,0.04); color: #0F172A; }
        .s-waitlisted { background: rgba(11,36,71,0.04); color: #0B2447; }
        .s-admitted { background: rgba(11,36,71,0.04); color: #0B2447; }
        .s-rejected { background: rgba(15,23,42,0.06); color: #0B2447; }
        .p-pending { background: rgba(11,36,71,0.04); color: #0F172A; }
        .p-paid { background: rgba(11,36,71,0.04); color: #0B2447; }
        .p-refunded { background: rgba(15,23,42,0.06); color: rgba(15,23,42,0.8); }
        .p-waived { background: rgba(11,36,71,0.06); color: #19376D; }
        .action-btn { background: #F8FAFC; color: #0f172a; padding: 6px 14px; border-radius: 8px; font-weight: 600; font-size: 0.8rem; text-decoration: none; border: 1px solid rgba(15,23,42,0.1); transition: all 0.2s; white-space: nowrap; }
        .action-btn:hover { background: #0B2447; color: white; border-color: #0B2447; }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .topbar { height: 56px; padding: 0 12px; }
            .mobile-menu-btn { display: block; }
            .content-area { padding: 12px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .page-header h2 { font-size: 1rem; }
            .stats-grid { grid-template-columns: 1fr; gap: 12px; }
            .stat-card { padding: 14px; }
            .stat-card .num { font-size: 1.4rem; }
            .filter-bar { padding: 10px; gap: 8px; }
            .filter-select { flex: 1; min-width: 120px; }
            .search-box { margin-left: 0; }
            .panel { border-radius: 10px; }
            th, td { padding: 10px 12px; font-size: 0.8rem; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
            .stat-card .num { font-size: 1.2rem; }
            .filter-select { min-width: 100px; font-size: 0.8rem; }
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
            <div style="font-weight:700; color:#0f172a;">Applications Management</div>
            <div class="header-right" style="display:flex; align-items:center; gap:16px;">
                <div class="avatar" style="width:32px; height:32px; border-radius:50%; background:#0f172a; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; cursor:pointer;">A</div>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-file-text" style="color:var(--primary);"></i> Applications</h2>
                    <p style="color:var(--text-muted); margin-top:4px;">Manage student applications, verify documents, and track payments.</p>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="num"><?php echo $total_apps; ?></div>
                    <div class="label">Total Applications</div>
                </div>
                <div class="stat-card">
                    <div class="num" style="color:#0F172A;"><?php echo $pending_apps; ?></div>
                    <div class="label">Pending Review</div>
                </div>
                <div class="stat-card">
                    <div class="num" style="color:#0B2447;"><?php echo $paid_apps; ?></div>
                    <div class="label">Fees Paid</div>
                </div>
            </div>

            <form class="filter-bar" method="GET">
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="all">All Statuses</option>
                    <option value="draft" <?php echo $status_filter=='draft'?'selected':''; ?>>Draft</option>
                    <option value="submitted" <?php echo $status_filter=='submitted'?'selected':''; ?>>Submitted</option>
                    <option value="under_review" <?php echo $status_filter=='under_review'?'selected':''; ?>>Under Review</option>
                    <option value="waitlisted" <?php echo $status_filter=='waitlisted'?'selected':''; ?>>Waitlisted</option>
                    <option value="admitted" <?php echo $status_filter=='admitted'?'selected':''; ?>>Admitted</option>
                    <option value="rejected" <?php echo $status_filter=='rejected'?'selected':''; ?>>Rejected</option>
                </select>
                
                <select name="payment" class="filter-select" onchange="this.form.submit()">
                    <option value="all">All Payments</option>
                    <option value="pending" <?php echo $payment_filter=='pending'?'selected':''; ?>>Pending</option>
                    <option value="paid" <?php echo $payment_filter=='paid'?'selected':''; ?>>Paid</option>
                    <option value="refunded" <?php echo $payment_filter=='refunded'?'selected':''; ?>>Refunded</option>
                    <option value="waived" <?php echo $payment_filter=='waived'?'selected':''; ?>>Waived</option>
                </select>
                
                <div class="search-box">
                    <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                    <input type="text" name="q" placeholder="Search Application No / Student Name..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" style="display:none;"></button>
                </div>
            </form>

            <div class="panel">
                <?php if(empty($applications)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 40px;">No applications found matching the criteria.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>App. No</th>
                                <th>Student Info</th>
                                <th>College & Course</th>
                                <th>Applied On</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($applications as $app): ?>
                            <tr>
                                <td style="font-weight:700; color:var(--text-dark);"><?php echo htmlspecialchars($app['application_number']); ?></td>
                                <td>
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($app['user_name'] ?: 'Unknown'); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($app['user_email'] ?: ''); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight:600; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($app['college_name']); ?>"><?php echo htmlspecialchars($app['college_name']); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted); max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($app['course_name']); ?>"><?php echo htmlspecialchars($app['course_name']); ?></div>
                                </td>
                                <td style="font-size:0.8rem;"><?php echo date('d M Y', strtotime($app['applied_at'])); ?></td>
                                <td><span class="badge s-<?php echo $app['status']; ?>"><?php echo str_replace('_', ' ', $app['status']); ?></span></td>
                                <td><span class="badge p-<?php echo $app['payment_status']; ?>"><?php echo $app['payment_status']; ?></span></td>
                                <td>
                                    <a href="application_details.php?id=<?php echo $app['id']; ?>" class="action-btn">View Details</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script>
document.getElementById('mobile-menu-btn').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.add('open');
    document.getElementById('sidebar-overlay').classList.add('show');
});
document.getElementById('sidebar-overlay').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.remove('open');
    this.classList.remove('show');
});
</script>
</body>
</html>
