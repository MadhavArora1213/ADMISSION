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
        $stmt = $pdo->prepare("UPDATE colleges SET status = 'archived' WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare("UPDATE colleges SET status = 'archived', archived_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: colleges.php?msg=archived');
    exit;
}

// Handle Restore from Archive
if (isset($_GET['action']) && $_GET['action'] == 'restore' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE colleges SET status = 'active' WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: colleges.php?status=archived&msg=restored');
    exit;
}

// Filters
$search       = isset($_GET['q']) ? trim($_GET['q']) : '';
$statusF     = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$typeF       = isset($_GET['type']) ? trim($_GET['type']) : 'all';
$publishF    = isset($_GET['publish']) ? trim($_GET['publish']) : 'all';
$verifiedF   = isset($_GET['verified']) ? trim($_GET['verified']) : 'all';

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = "(c.name LIKE ? OR ci.name LIKE ? OR s.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($statusF !== 'all' && in_array($statusF, ['active','pending','archived','rejected'])) {
    $where[] = "c.status = ?";
    $params[] = $statusF;
} else {
    $where[] = "c.status != 'archived'";
}
if ($typeF !== 'all' && in_array($typeF, ['govt','private','deemed','autonomous'])) {
    $where[] = "c.college_type = ?";
    $params[] = $typeF;
}
if ($publishF !== 'all' && in_array($publishF, ['published','draft','archived'])) {
    $where[] = "c.publish_status = ?";
    $params[] = $publishF;
}
if ($verifiedF === 'yes') {
    $where[] = "c.is_verified = 1";
} elseif ($verifiedF === 'no') {
    $where[] = "c.is_verified = 0";
}

$whereSQL = "WHERE " . implode(" AND ", $where);

// Count total for pagination
$countSQL = "SELECT COUNT(*) FROM colleges c LEFT JOIN cities ci ON c.city_id = ci.id LEFT JOIN states s ON c.state_id = s.id $whereSQL";
$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();

$perPage = 25;
$page    = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$totalPages = max(1, ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

// Fetch page of colleges
$query = "
    SELECT c.id, c.name, c.college_type, c.status, c.publish_status, c.is_verified,
           ci.name as city_name, s.name as state_name
    FROM colleges c
    LEFT JOIN cities ci ON c.city_id = ci.id
    LEFT JOIN states s ON c.state_id = s.id
    $whereSQL
    ORDER BY c.name ASC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$colleges = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Colleges | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        
        /* Sidebar styles matching dashboard */
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 50; transition: transform 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }
        
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 64px; background: #fff; border-bottom: 1px solid rgba(15,23,42,0.08); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 40; }
        .header-left { display: flex; align-items: center; gap: 12px; }
        .header-right { display: flex; align-items: center; gap: 16px; }
        #topbarToggle { display:none; background:none; border:none; font-size:1.4rem; cursor:pointer; color:#0f172a; padding:4px; }
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:49; }
        
        .content-area { padding: 32px; }
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; }
        tr:hover { background-color: rgba(0,0,0,0.02); }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
        .status-active { background: rgba(11,36,71,0.04); color: #0B2447; }
        .status-pending { background: rgba(11,36,71,0.06); color: #0F172A; }
        .status-archived { background: rgba(15,23,42,0.06); color: #0B2447; }
        
        .verified-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: rgba(11,36,71,0.06); color: #19376D; }
        
        .action-links { display: flex; gap: 8px; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; color: var(--text-dark); background: #F8FAFC; border: 1px solid var(--border-color); }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.delete:hover { background: #0F172A; color: white; border-color: #0F172A; }
        
        .msg-alert { padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 24px; border: 1px solid rgba(11,36,71,0.04); }

        .filter-bar { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }
        .filter-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 14px; flex: 1; min-width: 200px; max-width: 350px; }
        .search-box input { border: none; outline: none; font-size: 0.9rem; width: 100%; background: transparent; }
        .filter-select { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.88rem; background: #fff; color: var(--text-dark); cursor: pointer; }
        .filter-select:focus { outline: none; border-color: var(--primary); }
        .pagination { display: flex; gap: 4px; justify-content: center; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-color); }
        .pagination a { padding: 6px 12px; border-radius: 6px; font-size: 0.82rem; text-decoration: none; color: var(--text-dark); border: 1px solid var(--border-color); }
        .pagination a.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .pagination a:hover:not(.active) { background: #f1f5f9; }
        .results-info { font-size: 0.82rem; color: var(--text-muted); }

        @media(max-width:1024px){
            .sidebar { transform:translateX(-100%) !important; }
            .sidebar.open { transform:translateX(0) !important; }
            .sidebar-overlay.show { display:block; }
            #topbarToggle { display:inline-flex !important; }
            .main-content { margin-left:0 !important; }
            .content-area { padding:16px !important; }
            .page-header { flex-wrap:wrap !important; gap:10px !important; }
            .page-header h2 { font-size:1.4rem !important; }
        }
        @media(max-width:768px){
            .topbar { height:56px !important; padding:0 12px !important; }
            .content-area { padding:12px !important; }
            .page-header h2 { font-size:1.2rem !important; }
            .panel { padding:0 !important; border-radius:12px !important; overflow:hidden; }
            table { font-size:0.8rem !important; }
            th, td { padding:8px 10px !important; }
            .col-hide-mobile { display:none !important; }
            .filter-row { flex-direction:column !important; }
            .search-box { max-width:none !important; }
            .filter-select { width:100% !important; }
        }
        @media(max-width:480px){
            .page-header h2 { font-size:1.1rem !important; }
            .btn { padding:8px 12px !important; font-size:0.82rem !important; }
            th, td { padding:6px 8px !important; font-size:0.75rem !important; }
        }
    </style>
</head>
<body>

    <div class="admin-layout">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="topbar">
                <div class="header-left">
                    <button onclick="toggleSidebar()" id="topbarToggle"><i class="ph ph-list"></i></button>
                    <div style="font-weight:700; color:#0f172a;">Manage Colleges</div>
                </div>
                <div class="header-right">
                    <span style="font-size:0.88rem; color:rgba(15,23,42,0.65);"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                    <a href="logout.php" style="color:#0f172a; font-size:1.2rem;"><i class="ph ph-sign-out"></i></a>
                </div>
            </header>

            <div class="content-area">
                <div class="page-header">
                    <div>
                        <h2>Manage Colleges</h2>
                        <p style="color: var(--text-muted);">View, add, and manage college listings.</p>
                    </div>
                    <a href="college_form.php" class="btn btn-primary">
                        <i class="ph ph-plus"></i> Add New College
                    </a>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'archived'): ?>
                <div class="msg-alert">
                    <i class="ph ph-check-circle"></i> College has been archived successfully.
                </div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'restored'): ?>
                <div class="msg-alert">
                    <i class="ph ph-check-circle"></i> College has been restored successfully.
                </div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert">
                    <i class="ph ph-check-circle"></i> College details saved successfully.
                </div>
                <?php endif; ?>

                <form method="GET" class="filter-bar">
                    <div class="filter-row">
                        <div class="search-box">
                            <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                            <input type="text" name="q" placeholder="Search college, city, state..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $statusF==='all'?'selected':''; ?>>All Status</option>
                            <option value="active" <?php echo $statusF==='active'?'selected':''; ?>>Active</option>
                            <option value="pending" <?php echo $statusF==='pending'?'selected':''; ?>>Pending</option>
                            <option value="rejected" <?php echo $statusF==='rejected'?'selected':''; ?>>Rejected</option>
                            <option value="archived" <?php echo $statusF==='archived'?'selected':''; ?>>Archived</option>
                        </select>
                        <select name="type" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $typeF==='all'?'selected':''; ?>>All Types</option>
                            <option value="govt" <?php echo $typeF==='govt'?'selected':''; ?>>Government</option>
                            <option value="private" <?php echo $typeF==='private'?'selected':''; ?>>Private</option>
                            <option value="deemed" <?php echo $typeF==='deemed'?'selected':''; ?>>Deemed</option>
                            <option value="autonomous" <?php echo $typeF==='autonomous'?'selected':''; ?>>Autonomous</option>
                        </select>
                        <select name="publish" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $publishF==='all'?'selected':''; ?>>All Publish</option>
                            <option value="published" <?php echo $publishF==='published'?'selected':''; ?>>Published</option>
                            <option value="draft" <?php echo $publishF==='draft'?'selected':''; ?>>Draft</option>
                        </select>
                        <select name="verified" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $verifiedF==='all'?'selected':''; ?>>All Verification</option>
                            <option value="yes" <?php echo $verifiedF==='yes'?'selected':''; ?>>Verified</option>
                            <option value="no" <?php echo $verifiedF==='no'?'selected':''; ?>>Unverified</option>
                        </select>
                        <button type="submit" class="btn btn-primary" style="white-space:nowrap;"><i class="ph ph-magnifying-glass"></i> Search</button>
                        <?php if($search || $statusF!=='all' || $typeF!=='all' || $publishF!=='all' || $verifiedF!=='all'): ?>
                            <a href="colleges.php" style="padding:8px 14px; border:1px solid var(--border-color); border-radius:8px; font-size:0.88rem; text-decoration:none; color:var(--text-dark); white-space:nowrap;">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="panel">
                    <?php if(empty($colleges)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding: 40px;">No colleges found. Click "Add New College" to create one.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th class="col-hide-mobile">Type</th>
                                        <th class="col-hide-mobile">Location</th>
                                        <th>Status</th>
                                        <th class="col-hide-mobile">Publish Status</th>
                                        <th class="col-hide-mobile">Verification</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($colleges as $college): ?>
                                    <tr>
                                        <td style="font-weight: 500; color: var(--primary);">
                                            <?php echo htmlspecialchars($college['name']); ?>
                                        </td>
                                        <td class="col-hide-mobile" style="text-transform: capitalize;">
                                            <?php echo htmlspecialchars($college['college_type']); ?>
                                        </td>
                                        <td class="col-hide-mobile">
                                            <?php 
                                            $loc = [];
                                            if($college['city_name']) $loc[] = $college['city_name'];
                                            if($college['state_name']) $loc[] = $college['state_name'];
                                            echo htmlspecialchars(implode(', ', $loc));
                                            if(empty($loc)) echo '<span style="color:rgba(15,23,42,0.4);">Not set</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $college['status']; ?>">
                                                <?php echo htmlspecialchars($college['status']); ?>
                                            </span>
                                        </td>
                                        <td class="col-hide-mobile">
                                            <?php
                                            $ps = $college['publish_status'] ?: 'draft';
                                            $ps_class = $ps == 'published' ? 'status-active' : ($ps == 'draft' ? 'status-pending' : 'status-archived');
                                            ?>
                                            <span class="status-badge <?php echo $ps_class; ?>">
                                                <?php echo htmlspecialchars(ucfirst($ps)); ?>
                                            </span>
                                        </td>
                                        <td class="col-hide-mobile">
                                            <?php if($college['is_verified']): ?>
                                                <span class="verified-badge"><i class="ph-fill ph-seal-check"></i> Verified</span>
                                            <?php else: ?>
                                                <span style="color:rgba(15,23,42,0.4); font-size:0.85rem;">Unverified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-links">
                                                <a href="college_form.php?id=<?php echo $college['id']; ?>" class="action-btn" title="Edit">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </a>
                                                <?php if($college['status'] === 'archived'): ?>
                                                <a href="colleges.php?action=restore&id=<?php echo $college['id']; ?>" class="action-btn" title="Restore" style="color:#16a34a;border-color:#16a34a;" onclick="return confirm('Restore this college?');">
                                                    <i class="ph ph-arrow-u-up-left"></i>
                                                </a>
                                                <?php else: ?>
                                                <a href="colleges.php?action=archive&id=<?php echo $college['id']; ?>" class="action-btn delete" title="Archive" onclick="return confirm('Are you sure you want to archive this college?');">
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
                        <div style="padding:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                            <span class="results-info">Showing <?php echo $totalRows > 0 ? ($offset+1) : 0; ?>-<?php echo min($offset+$perPage, $totalRows); ?> of <?php echo number_format($totalRows); ?> colleges</span>
                            <?php if($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$page-1])); ?>">&laquo;</a>
                                <?php endif; ?>
                                <?php for($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$i])); ?>" class="<?php echo $i===$page?'active':''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                <?php if($page < $totalPages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$page+1])); ?>">&raquo;</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    <script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }
    </script>
</body>
</html>
