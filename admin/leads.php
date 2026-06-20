<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$where = [];
$params = [];

if ($status_filter !== 'all') {
    $where[] = "l.lead_status = ?";
    $params[] = $status_filter;
}
if ($search !== '') {
    $where[] = "(l.phone LIKE ? OR l.email LIKE ? OR l.city LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";
$query = "SELECT l.*, c.name AS college_name, co.course_name, u.full_name AS assigned_name 
          FROM leads l 
          LEFT JOIN colleges c ON l.college_id = c.id 
          LEFT JOIN courses co ON l.course_id = co.id 
          LEFT JOIN users u ON l.assigned_to = u.id 
          $whereSQL
          ORDER BY l.created_at DESC 
          LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$leads = $stmt->fetchAll();

// Counsellors dropdown
$counselStmt = $pdo->query("SELECT id, full_name as name FROM users ORDER BY full_name ASC");
$counsellors = $counselStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads & CRM | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #f8fafc; border-radius: 12px; border: 1px solid var(--border-color); padding: 20px; box-shadow: var(--shadow-sm); }
        .stat-card .num { font-size: 2rem; font-weight: 800; color: var(--primary); }
        .stat-card .label { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-top: 4px; }
        .filter-bar { display: flex; gap: 12px; margin-bottom: 24px; align-items: center; flex-wrap: wrap; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; text-decoration: none; border: 1px solid var(--border-color); background: #f8fafc; }
        .tab-link:hover, .tab-link.active { background: var(--primary); color: white; border-color: var(--primary); }
        .search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 16px; flex: 1; max-width: 300px; }
        .search-box input { border: none; outline: none; font-size: 0.95rem; width: 100%; background: transparent; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.78rem; letter-spacing: 0.05em; background: #F8FAFC; }
        tr:hover { background-color: rgba(0,0,0,0.02); }
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
        .s-new { background: rgba(11,36,71,0.06); color: #19376D; }
        .s-contacted { background: rgba(11,36,71,0.06); color: #0F172A; }
        .s-qualified { background: rgba(11,36,71,0.06); color: #19376D; }
        .s-converted { background: rgba(11,36,71,0.04); color: #0B2447; }
        .s-lost { background: rgba(15,23,42,0.06); color: #0B2447; }
        .s-invalid { background: #F8FAFC; color: rgba(15,23,42,0.45); }
        .p-low { background: #F8FAFC; color: rgba(15,23,42,0.65); }
        .p-medium { background: rgba(11,36,71,0.04); color: #0F172A; }
        .p-high { background: rgba(11,36,71,0.04); color: #0B2447; }
        .p-urgent { background: rgba(15,23,42,0.06); color: #0B2447; }
        .d-pending { background: rgba(11,36,71,0.04); color: #0F172A; }
        .d-delivered { background: rgba(11,36,71,0.04); color: #0B2447; }
        .d-failed { background: rgba(15,23,42,0.06); color: #0B2447; }
        .d-disputed { background: rgba(11,36,71,0.04); color: #0B2447; }
        .action-btn { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #F8FAFC; color: var(--text-dark); border: 1px solid var(--border-color); text-decoration: none; }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 20px; border: 1px solid rgba(11,36,71,0.04); }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" style="margin-left: 16px; color: #19376d;"><i class="ph ph-sign-out" style="font-size: 1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-funnel" style="color:var(--primary);"></i> Leads & CRM</h2>
                    <p style="color:var(--text-muted);">Manage and track all leads, assignments, and delivery status.</p>
                </div>
                <a href="lead_form.php" class="btn btn-primary"><i class="ph ph-plus"></i> Add Lead</a>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg']=='saved'): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Lead saved successfully.</div>
            <?php endif; ?>

            <?php
            $totStmt = $pdo->query("SELECT lead_status, COUNT(*) AS cnt FROM leads GROUP BY lead_status");
            $totals = ['new'=>0,'contacted'=>0,'qualified'=>0,'converted'=>0,'lost'=>0,'invalid'=>0];
            foreach($totStmt->fetchAll() as $t) $totals[$t['lead_status']] = $t['cnt'];
            $totalAll = array_sum($totals);
            ?>
            <div class="stats-grid">
                <div class="stat-card"><div class="num"><?php echo $totalAll; ?></div><div class="label">Total Leads</div></div>
                <div class="stat-card"><div class="num" style="color:#19376D;"><?php echo $totals['new']; ?></div><div class="label">New</div></div>
                <div class="stat-card"><div class="num" style="color:#19376D;"><?php echo $totals['qualified']; ?></div><div class="label">Qualified</div></div>
                <div class="stat-card"><div class="num" style="color:#0B2447;"><?php echo $totals['converted']; ?></div><div class="label">Converted</div></div>
            </div>

            <div class="filter-bar">
                <a href="?status=all" class="tab-link <?php echo $status_filter=='all'?'active':''; ?>">All</a>
                <a href="?status=new" class="tab-link <?php echo $status_filter=='new'?'active':''; ?>">New</a>
                <a href="?status=contacted" class="tab-link <?php echo $status_filter=='contacted'?'active':''; ?>">Contacted</a>
                <a href="?status=qualified" class="tab-link <?php echo $status_filter=='qualified'?'active':''; ?>">Qualified</a>
                <a href="?status=converted" class="tab-link <?php echo $status_filter=='converted'?'active':''; ?>">Converted</a>
                <a href="?status=lost" class="tab-link <?php echo $status_filter=='lost'?'active':''; ?>">Lost</a>
                <a href="?status=invalid" class="tab-link <?php echo $status_filter=='invalid'?'active':''; ?>">Invalid</a>
                <form method="GET" style="margin-left:auto; display:flex; align-items:center; gap:8px;">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <div class="search-box">
                        <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                        <input type="text" name="q" placeholder="Search phone, email, city..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding:9px 16px;">Search</button>
                </form>
            </div>

            <div class="panel" style="padding:0; overflow:hidden;">
                <?php if(empty($leads)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:40px;">No leads found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Contact</th>
                                <th>Type</th>
                                <th>College / Course</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Delivery</th>
                                <th>Assigned To</th>
                                <th>Next Follow-up</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($leads as $l): ?>
                            <tr>
                                <td style="white-space:nowrap;"><?php echo date('d M y', strtotime($l['created_at'])); ?></td>
                                <td>
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($l['phone']); ?></div>
                                    <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($l['email']); ?></div>
                                    <?php if($l['city']): ?><div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($l['city'].', '.$l['state']); ?></div><?php endif; ?>
                                </td>
                                <td><?php echo $l['lead_type'] ? '<span class="badge" style="background:rgba(11,36,71,0.06);color:#19376D;">'.ucfirst($l['lead_type']).'</span>' : '-'; ?></td>
                                <td>
                                    <div style="font-size:0.85rem; font-weight:600;"><?php echo htmlspecialchars($l['college_name'] ?: '-'); ?></div>
                                    <div style="font-size:0.78rem; color:var(--text-muted);"><?php echo htmlspecialchars($l['course_name'] ?: ''); ?></div>
                                </td>
                                <td><span class="badge s-<?php echo $l['lead_status']; ?>"><?php echo ucfirst($l['lead_status']); ?></span></td>
                                <td><span class="badge p-<?php echo $l['priority']; ?>"><?php echo ucfirst($l['priority']); ?></span></td>
                                <td><span class="badge d-<?php echo $l['delivery_status']; ?>"><?php echo ucfirst($l['delivery_status']); ?></span></td>
                                <td style="font-size:0.85rem;"><?php echo htmlspecialchars($l['assigned_name'] ?: '—'); ?></td>
                                <td style="font-size:0.82rem; white-space:nowrap;"><?php echo $l['next_followup_at'] ? date('d M y H:i', strtotime($l['next_followup_at'])) : '—'; ?></td>
                                <td><a href="lead_form.php?id=<?php echo $l['id']; ?>" class="action-btn" title="View/Edit"><i class="ph ph-pencil-simple"></i></a></td>
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
