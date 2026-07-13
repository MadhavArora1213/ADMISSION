<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

// Handle Delete/Archive
if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE universities SET status = 'archived' WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare("UPDATE universities SET status = 'archived', archived_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: universities.php?msg=archived');
    exit;
}

// Handle Restore from Archive
if (isset($_GET['action']) && $_GET['action'] == 'restore' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE universities SET status = 'active' WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: universities.php?status=archived&msg=restored');
    exit;
}

// Status Filter
$statusF = isset($_GET['status']) ? trim($_GET['status']) : 'all';

// Fetch Universities with City and State Names
$where = [];
if ($statusF !== 'all' && in_array($statusF, ['active','pending','archived','rejected'])) {
    $where[] = "c.status = '" . $statusF . "'";
} else {
    $where[] = "c.status != 'archived'";
}
$whereSQL = implode(' AND ', $where);

$query = "
    SELECT c.id, c.name, c.university_type, c.status, c.publish_status, c.is_verified,
           ci.name as city_name, s.name as state_name
    FROM universities c
    LEFT JOIN cities ci ON c.city_id = ci.id
    LEFT JOIN states s ON c.state_id = s.id
    WHERE $whereSQL
    ORDER BY c.created_at DESC
";
$stmt = $pdo->query($query);
$universities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Universities | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
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
        .user-profile { display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
        .content-area { padding: 32px; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; min-width: 650px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; }
        tr:hover { background-color: rgba(0,0,0,0.02); }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
        .status-active { background: rgba(11,36,71,0.04); color: #0B2447; }
        .status-pending { background: rgba(11,36,71,0.06); color: #0F172A; }
        .status-archived { background: rgba(15,23,42,0.06); color: #0B2447; }
        .verified-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: rgba(11,36,71,0.06); color: #19376D; white-space: nowrap; }
        .action-links { display: flex; gap: 8px; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; color: var(--text-dark); background: #F8FAFC; border: 1px solid var(--border-color); }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.delete:hover { background: #0F172A; color: white; border-color: #0F172A; }
        .msg-alert { padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 24px; border: 1px solid rgba(11,36,71,0.04); }
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
            .page-header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .page-header h2 { font-size: 1.4rem; }
            .page-header .btn { width: 100%; justify-content: center; }
            .panel { padding: 14px; }
            .panel table { min-width: 600px; }
            th, td { padding: 10px 12px; font-size: 0.88rem; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
            .panel { padding: 10px; }
            .page-header h2 { font-size: 1.2rem; }
            .avatar { width: 34px; height: 34px; font-size: 0.85rem; }
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
                    <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                    <div class="avatar"><?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?></div>
                    <a href="logout.php" style="margin-left: 16px; color: #19376d;" title="Logout">
                        <i class="ph ph-sign-out" style="font-size: 1.5rem;"></i>
                    </a>
                </div>
            </header>

            <div class="content-area">
                <div class="page-header">
                    <div>
                        <h2>Manage Universities</h2>
                        <p style="color: var(--text-muted);">View, add, and manage university listings.</p>
                    </div>
                    <a href="university_form.php" class="btn btn-primary">
                        <i class="ph ph-plus"></i> Add New University
                    </a>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'archived'): ?>
                <div class="msg-alert">
                    <i class="ph ph-check-circle"></i> University has been archived successfully.
                </div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'restored'): ?>
                <div class="msg-alert">
                    <i class="ph ph-check-circle"></i> University has been restored successfully.
                </div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert">
                    <i class="ph ph-check-circle"></i> University details saved successfully.
                </div>
                <?php endif; ?>

                <div style="display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap;">
                    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $statusF==='all'?'selected':''; ?>>All Status</option>
                            <option value="active" <?php echo $statusF==='active'?'selected':''; ?>>Active</option>
                            <option value="pending" <?php echo $statusF==='pending'?'selected':''; ?>>Pending</option>
                            <option value="rejected" <?php echo $statusF==='rejected'?'selected':''; ?>>Rejected</option>
                            <option value="archived" <?php echo $statusF==='archived'?'selected':''; ?>>Archived</option>
                        </select>
                    </form>
                    <?php if($statusF!=='all'): ?>
                        <a href="universities.php" style="padding:8px 14px;border:1px solid var(--border-color);border-radius:8px;font-size:.88rem;text-decoration:none;color:var(--text-dark);">Clear</a>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <?php if(empty($universities)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding: 40px;">No universities found. Click "Add New University" to create one.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Publish Status</th>
                                        <th>Verification</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($universities as $university): ?>
                                    <tr>
                                        <td style="font-weight: 500; color: var(--primary);">
                                            <?php echo htmlspecialchars($university['name']); ?>
                                        </td>
                                        <td style="text-transform: capitalize;">
                                            <?php echo htmlspecialchars($university['university_type']); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $loc = [];
                                            if($university['city_name']) $loc[] = $university['city_name'];
                                            if($university['state_name']) $loc[] = $university['state_name'];
                                            echo htmlspecialchars(implode(', ', $loc));
                                            if(empty($loc)) echo '<span style="color:rgba(15,23,42,0.4);">Not set</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $university['status']; ?>">
                                                <?php echo htmlspecialchars($university['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $ps = $university['publish_status'] ?: 'draft';
                                            $ps_class = $ps == 'published' ? 'status-active' : ($ps == 'draft' ? 'status-pending' : 'status-archived');
                                            ?>
                                            <span class="status-badge <?php echo $ps_class; ?>">
                                                <?php echo htmlspecialchars(ucfirst($ps)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($university['is_verified']): ?>
                                                <span class="verified-badge"><i class="ph-fill ph-seal-check"></i> Verified</span>
                                            <?php else: ?>
                                                <span style="color:rgba(15,23,42,0.4); font-size:0.85rem;">Unverified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-links">
                                                <a href="university_form.php?id=<?php echo $university['id']; ?>" class="action-btn" title="Edit">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </a>
                                                <?php if($university['status'] === 'archived'): ?>
                                                <a href="universities.php?action=restore&id=<?php echo $university['id']; ?>" class="action-btn" title="Restore" style="color:#16a34a;border-color:#16a34a;" onclick="return confirm('Restore this university?');">
                                                    <i class="ph ph-arrow-u-up-left"></i>
                                                </a>
                                                <?php else: ?>
                                                <a href="universities.php?action=archive&id=<?php echo $university['id']; ?>" class="action-btn delete" title="Archive" onclick="return confirm('Are you sure you want to archive this university?');">
                                                    <i class="ph ph-archive"></i>
                                                </a>
                                                <?php endif; ?>
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
