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
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 20px; box-shadow: var(--shadow-sm); }
        .stat-card .num { font-size: 2rem; font-weight: 800; color: var(--primary); }
        .stat-card .label { font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-top: 4px; }
        
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; background: #fff; padding: 12px; border-radius: 12px; border: 1px solid var(--border-color); }
        .filter-select { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem; outline: none; }
        .search-box { display: flex; align-items: center; gap: 8px; border: 1px solid var(--border-color); border-radius: 6px; padding: 7px 12px; margin-left: auto; }
        .search-box input { border: none; outline: none; font-size: 0.85rem; width: 220px; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.05em; background: #f8fafc; }
        tr:hover { background-color: #f8fafc; }
        
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; text-transform: capitalize; }
        .s-draft { background: #f1f5f9; color: #475569; }
        .s-submitted { background: #dbeafe; color: #1e40af; }
        .s-under_review { background: #fef9c3; color: #854d0e; }
        .s-waitlisted { background: #ffedd5; color: #c2410c; }
        .s-admitted { background: #dcfce7; color: #166534; }
        .s-rejected { background: #fee2e2; color: #b91c1c; }
        
        .p-pending { background: #fef9c3; color: #854d0e; }
        .p-paid { background: #dcfce7; color: #166534; }
        .p-refunded { background: #f3f4f6; color: #374151; }
        .p-waived { background: #e0e7ff; color: #4338ca; }
        
        .action-btn { background: #f1f5f9; color: var(--text-dark); padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; text-decoration: none; border: 1px solid var(--border-color); transition: all 0.2s; }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></span>
                <a href="logout.php" style="margin-left:16px; color:var(--text-dark);"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
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
                    <div class="num" style="color:#854d0e;"><?php echo $pending_apps; ?></div>
                    <div class="label">Pending Review</div>
                </div>
                <div class="stat-card">
                    <div class="num" style="color:#166534;"><?php echo $paid_apps; ?></div>
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
</body>
</html>
