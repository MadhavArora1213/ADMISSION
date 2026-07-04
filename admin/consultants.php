<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM consultants WHERE id = ?")->execute([$_GET['id']]);
    header("Location: consultants.php?msg=deleted");
    exit;
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$verified_filter = isset($_GET['verified']) ? $_GET['verified'] : 'all';

$where = [];
$params = [];
if ($search !== '') { 
    $where[] = "(consultant_name LIKE ? OR contact_email LIKE ?)"; 
    $params[] = "%$search%"; 
    $params[] = "%$search%"; 
}
if ($verified_filter !== 'all') {
    $where[] = "verified_consultant = ?";
    $params[] = $verified_filter;
}
$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT * 
    FROM consultants 
    $whereSQL 
    ORDER BY created_at DESC 
    LIMIT 100
");
$stmt->execute($params);
$consultants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_c = $pdo->query("SELECT count(*) FROM consultants")->fetchColumn();
$verified_c = $pdo->query("SELECT count(*) FROM consultants WHERE verified_consultant = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultants | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 20px; box-shadow: var(--shadow-sm); }
        .stat-card .num { font-size: 2rem; font-weight: 800; color: var(--primary); }
        .stat-card .label { font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-top: 4px; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; background: #fff; padding: 12px; border-radius: 12px; border: 1px solid var(--border-color); }
        .filter-select { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem; outline: none; min-width: 0; }
        .search-box { display: flex; align-items: center; gap: 8px; border: 1px solid var(--border-color); border-radius: 6px; padding: 7px 12px; flex: 1; min-width: 0; }
        .search-box input { border: none; outline: none; font-size: 0.85rem; width: 100%; min-width: 0; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .btn-primary:hover { background: #19376D; }
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; min-width: 650px; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.05em; background: #f8fafc; }
        tr:hover { background-color: #f8fafc; }
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; text-transform: capitalize; white-space: nowrap; }
        .s-verified { background: rgba(11,36,71,0.04); color: #0B2447; }
        .s-unverified { background: #F8FAFC; color: rgba(15,23,42,0.65); }
        .action-btn { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #F8FAFC; color: var(--text-dark); border: 1px solid var(--border-color); text-decoration: none; transition: all 0.2s; }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.delete:hover { background: #0F172A; color: white; border-color: #0F172A; }
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 20px; border: 1px solid rgba(11,36,71,0.04); font-weight: 500; }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .topbar { height: 56px; padding: 0 12px; justify-content: space-between; }
            .mobile-menu-btn { display: block; }
            .content-area { padding: 12px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .page-header h2 { font-size: 1.4rem; }
            .page-header .btn-primary { width: 100%; justify-content: center; }
            .stats-grid { grid-template-columns: 1fr; }
            .stat-card { padding: 14px; }
            .stat-card .num { font-size: 1.5rem; }
            .filter-bar { padding: 10px; gap: 8px; }
            .filter-select { flex: 1; min-width: 120px; }
            .search-box { margin-left: 0; }
            th, td { padding: 10px 12px; font-size: 0.82rem; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
            .stat-card .num { font-size: 1.2rem; }
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
            <div class="user-profile">
                <span><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') : 'Admin'; ?></span>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-users-four" style="color:var(--primary);"></i> Consultants</h2>
                    <p style="color:var(--text-muted); margin-top:4px;">Manage study abroad consultants and agents.</p>
                </div>
                <a href="consultant_form.php" class="btn-primary"><i class="ph ph-plus"></i> Add Consultant</a>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Consultant deleted successfully.</div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="num"><?php echo $total_c; ?></div>
                    <div class="label">Total Consultants</div>
                </div>
                <div class="stat-card">
                    <div class="num" style="color:#0B2447;"><?php echo $verified_c; ?></div>
                    <div class="label">Verified Consultants</div>
                </div>
            </div>

            <form class="filter-bar" method="GET">
                <select name="verified" class="filter-select" onchange="this.form.submit()">
                    <option value="all">All Verification Statuses</option>
                    <option value="1" <?php echo $verified_filter=='1'?'selected':''; ?>>Verified</option>
                    <option value="0" <?php echo $verified_filter=='0'?'selected':''; ?>>Unverified</option>
                </select>

                <div class="search-box">
                    <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                    <input type="text" name="q" placeholder="Search Consultants..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" style="display:none;"></button>
                </div>
            </form>

            <div class="panel">
                <?php if(empty($consultants)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 40px;">No consultants found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Consultant</th>
                                <th>Contact</th>
                                <th>Specializations</th>
                                <th>Experience</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($consultants as $c): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; gap:12px; align-items:center;">
                                        <?php if(!empty($c['profile_picture'])): ?>
                                            <img src="../<?php echo htmlspecialchars($c['profile_picture']); ?>" alt="" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:40px; height:40px; border-radius:50%; background:rgba(15,23,42,0.08); display:flex; align-items:center; justify-content:center; color:rgba(15,23,42,0.45); font-weight:700;">
                                                <?php echo substr($c['consultant_name'], 0, 1); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight:600; color:var(--text-dark);">
                                                <?php echo htmlspecialchars($c['consultant_name']); ?>
                                            </div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);"><i class="ph ph-star-fill" style="color:#19376D"></i> <?php echo $c['consultant_rating'] ?: 'N/A'; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($c['contact_email'] ?: '-'); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);"><i class="ph ph-map-pin"></i> <?php echo htmlspecialchars($c['office_location'] ?: 'Remote'); ?></div>
                                </td>
                                <td><?php echo $c['specializations'] ? htmlspecialchars(substr($c['specializations'], 0, 30)) . (strlen($c['specializations']) > 30 ? '...' : '') : '-'; ?></td>
                                <td>
                                    <div><?php echo $c['experience_years'] ? htmlspecialchars($c['experience_years']) . ' Yrs' : '-'; ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo $c['success_rate_percent'] ? htmlspecialchars($c['success_rate_percent']) . '% Success' : ''; ?></div>
                                </td>
                                <td>
                                    <?php if($c['verified_consultant']): ?>
                                        <span class="badge s-verified"><i class="ph ph-seal-check"></i> Verified</span>
                                    <?php else: ?>
                                        <span class="badge s-unverified">Unverified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="consultant_form.php?id=<?php echo $c['id']; ?>" class="action-btn" title="Edit"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?action=delete&id=<?php echo $c['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirm('Delete this consultant?');"><i class="ph ph-trash"></i></a>
                                    </div>
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
