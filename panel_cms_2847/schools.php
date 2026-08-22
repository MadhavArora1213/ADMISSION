<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

// Handle Archive
if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE schools SET status = 'archived' WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: schools.php?msg=archived');
    exit;
}

// Handle Restore from Archive
if (isset($_GET['action']) && $_GET['action'] == 'restore' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE schools SET status = 'active' WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: schools.php?status=archived&msg=restored');
    exit;
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM school_media WHERE school_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM school_contacts WHERE school_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM school_content WHERE school_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM school_infrastructure WHERE school_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM school_courses WHERE school_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM school_news WHERE school_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM seo_meta WHERE page_type = 'school' AND page_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM reviews WHERE school_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM schools WHERE id = ?")->execute([$id]);
        $pdo->commit();
        header('Location: schools.php?msg=deleted');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: schools.php?msg=delete_error');
        exit;
    }
}

// Filters
$search    = isset($_GET['q']) ? trim($_GET['q']) : '';
$statusF  = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$typeF    = isset($_GET['type']) ? trim($_GET['type']) : 'all';
$boardF   = isset($_GET['board']) ? trim($_GET['board']) : 'all';
$publishF = isset($_GET['publish']) ? trim($_GET['publish']) : 'all';

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = "(s.name LIKE ? OR ci.name LIKE ? OR st.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($statusF !== 'all' && in_array($statusF, ['active','pending','archived','rejected'])) {
    $where[] = "s.status = ?";
    $params[] = $statusF;
} else {
    $where[] = "s.status != 'archived'";
}
if ($typeF !== 'all' && in_array($typeF, ['govt','private','aided','unaided','international','boarding'])) {
    $where[] = "s.school_type = ?";
    $params[] = $typeF;
}
if ($boardF !== 'all' && in_array($boardF, ['CBSE','ICSE','State','IB','IGCSE','NIOS'])) {
    $where[] = "s.board_affiliation = ?";
    $params[] = $boardF;
}
if ($publishF !== 'all' && in_array($publishF, ['published','draft'])) {
    $where[] = "s.publish_status = ?";
    $params[] = $publishF;
}

$whereSQL = "WHERE " . implode(" AND ", $where);

$countSQL = "SELECT COUNT(*) FROM schools s LEFT JOIN cities ci ON s.city_id = ci.id LEFT JOIN states st ON s.state_id = st.id $whereSQL";
$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();

$perPage = 25;
$page    = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$totalPages = max(1, ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

$query = "
    SELECT s.id, s.name, s.school_type, s.board_affiliation, s.status, s.publish_status, s.is_verified,
           ci.name as city_name, st.name as state_name
    FROM schools s
    LEFT JOIN cities ci ON s.city_id = ci.id
    LEFT JOIN states st ON s.state_id = st.id
    $whereSQL
    ORDER BY s.name ASC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$schools = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schools | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 50; transition: transform 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }

        /* ── Main layout ── */
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; min-width: 0; }
        .topbar { height: 64px; background: #fff; border-bottom: 1px solid rgba(15,23,42,0.08); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 40; }
        .header-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .header-left .page-title { font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .header-right { display: flex; align-items: center; gap: 16px; }
        .header-right .admin-name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #topbarToggle { display:none; background:none; border:none; font-size:1.4rem; cursor:pointer; color:#0f172a; padding:4px; flex-shrink: 0; }
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:49; }
        .content-area { padding: 32px; }

        /* ── Page header ── */
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }

        /* ── Panel / Table ── */
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); }
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; }
        td.school-name { white-space: normal; word-break: break-word; font-weight: 500; color: var(--primary); }
        tr:hover { background-color: rgba(0,0,0,0.02); }

        /* ── Badges ── */
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; white-space: nowrap; }
        .status-active { background: rgba(11,36,71,0.04); color: #0B2447; }
        .status-pending { background: rgba(11,36,71,0.06); color: #0F172A; }
        .status-archived { background: rgba(15,23,42,0.06); color: #0B2447; }
        .verified-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: rgba(11,36,71,0.06); color: #19376D; }

        /* ── Actions ── */
        .action-links { display: flex; gap: 8px; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; color: var(--text-dark); background: #F8FAFC; border: 1px solid var(--border-color); }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.delete:hover { background: #0F172A; color: white; border-color: #0F172A; }

        /* ── Alerts ── */
        .msg-alert { padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 24px; border: 1px solid rgba(11,36,71,0.04); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        /* ── Filters ── */
        .filter-bar { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }
        .filter-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 14px; flex: 1 1 220px; max-width: 350px; }
        .search-box input { border: none; outline: none; font-size: 0.9rem; width: 100%; background: transparent; }
        .filter-select { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.88rem; background: #fff; color: var(--text-dark); cursor: pointer; min-width: 0; flex: 1 1 120px; }
        .filter-select:focus { outline: none; border-color: var(--primary); }
        .filter-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        /* ── Pagination / Footer ── */
        .table-footer { padding: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
        .pagination { display: flex; gap: 4px; justify-content: center; flex-wrap: wrap; }
        .pagination a { padding: 6px 12px; border-radius: 6px; font-size: 0.82rem; text-decoration: none; color: var(--text-dark); border: 1px solid var(--border-color); }
        .pagination a.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .pagination a:hover:not(.active) { background: #f1f5f9; }
        .results-info { font-size: 0.82rem; color: var(--text-muted); }

        /* ── Mobile card styles (hidden on desktop) ── */
        .mobile-cards { display: none; }

        /* ══════════════════════════════════════════
           RESPONSIVE — Tablet (≤ 1024px)
           ══════════════════════════════════════════ */
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%) !important; }
            .sidebar.open { transform: translateX(0) !important; }
            .sidebar-overlay.show { display: block; }
            #topbarToggle { display: inline-flex !important; }
            .main-content { margin-left: 0 !important; }
            .content-area { padding: 20px !important; }
        }

        /* ══════════════════════════════════════════
           RESPONSIVE — Mobile landscape (≤ 768px)
           ══════════════════════════════════════════ */
        @media (max-width: 768px) {
            .topbar { height: 56px !important; padding: 0 16px !important; }
            .topbar .header-left .page-title { font-size: 0.95rem; }
            .topbar .header-right .admin-name { display: none; }
            .content-area { padding: 16px !important; }
            .page-header h2 { font-size: 1.5rem; }
            .page-header { flex-direction: column; gap: 12px; }
            .page-header .btn { width: 100%; justify-content: center; }

            /* Hide table on mobile, show cards */
            .table-wrap { display: none !important; }
            .mobile-cards { display: flex !important; flex-direction: column; gap: 12px; padding: 16px; }

            /* Filter stacking */
            .filter-row { flex-direction: column !important; }
            .search-box { max-width: none !important; flex: 1 1 100% !important; }
            .filter-select { width: 100% !important; flex: 1 1 100% !important; }
            .filter-actions { width: 100%; }
            .filter-actions .btn { width: 100%; justify-content: center; }
            .filter-actions a { width: 100%; text-align: center; }

            /* Table footer */
            .table-footer { flex-direction: column; align-items: center; gap: 12px; padding: 16px !important; }
            .panel { padding: 0 !important; border-radius: 12px !important; overflow: hidden; }
        }

        /* ══════════════════════════════════════════
           RESPONSIVE — Mobile portrait (≤ 480px)
           ══════════════════════════════════════════ */
        @media (max-width: 480px) {
            .content-area { padding: 10px !important; }
            .topbar { padding: 0 12px !important; }
            .topbar .header-left { gap: 8px; }
            .page-header h2 { font-size: 1.25rem; }
            .page-header p { font-size: 0.82rem; }
            .msg-alert { padding: 12px; font-size: 0.85rem; }

            /* Mobile cards tighter */
            .mobile-cards { padding: 12px !important; gap: 10px !important; }
            .mobile-card { padding: 14px !important; }
            .mobile-card .card-title { font-size: 0.92rem; }
            .mobile-card .card-detail { font-size: 0.78rem; }

            /* Pagination compact */
            .pagination a { padding: 5px 9px; font-size: 0.78rem; }
        }

        /* ══════════════════════════════════════════
           MOBILE CARD COMPONENT
           ══════════════════════════════════════════ */
        .mobile-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
        }
        .mobile-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
        }
        .mobile-card .card-title {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.95rem;
            line-height: 1.3;
        }
        .mobile-card .card-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }
        .mobile-card .card-actions .action-btn {
            width: 30px;
            height: 30px;
        }
        .mobile-card .card-details {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .mobile-card .card-detail {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            color: var(--text-dark);
        }
        .mobile-card .card-detail .label {
            color: var(--text-muted);
            min-width: 65px;
            flex-shrink: 0;
        }
        .mobile-card .card-badges {
            display: flex;
            gap: 6px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="topbar">
                <div class="header-left">
                    <button onclick="toggleSidebar()" id="topbarToggle"><i class="ph ph-list"></i></button>
                    <div class="page-title">Manage Schools</div>
                </div>
                <div class="header-right">
                    <span class="admin-name" style="font-size:0.88rem; color:rgba(15,23,42,0.65);"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
                    <a href="logout.php" style="color:#0f172a; font-size:1.2rem;"><i class="ph ph-sign-out"></i></a>
                </div>
            </header>

            <div class="content-area">
                <div class="page-header">
                    <div>
                        <h2>Manage Schools</h2>
                        <p style="color: var(--text-muted);">View, add, and manage school listings.</p>
                    </div>
                    <a href="school_form.php" class="btn btn-primary">
                        <i class="ph ph-plus"></i> Add New School
                    </a>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'archived'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> School has been archived successfully.</div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'restored'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> School has been restored successfully.</div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> School details saved successfully.</div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> School has been permanently deleted.</div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'delete_error'): ?>
                <div class="msg-alert" style="background:rgba(220,38,38,0.06);color:#991b1b;border-color:rgba(220,38,38,0.12);"><i class="ph ph-warning-circle"></i> Failed to delete school. Please try again.</div>
                <?php endif; ?>

                <form method="GET" class="filter-bar">
                    <div class="filter-row">
                        <div class="search-box">
                            <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                            <input type="text" name="q" placeholder="Search school, city, state..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?= $statusF==='all'?'selected':''; ?>>All Status</option>
                            <option value="active" <?= $statusF==='active'?'selected':''; ?>>Active</option>
                            <option value="pending" <?= $statusF==='pending'?'selected':''; ?>>Pending</option>
                            <option value="rejected" <?= $statusF==='rejected'?'selected':''; ?>>Rejected</option>
                            <option value="archived" <?= $statusF==='archived'?'selected':''; ?>>Archived</option>
                        </select>
                        <select name="type" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?= $typeF==='all'?'selected':''; ?>>All Types</option>
                            <option value="govt" <?= $typeF==='govt'?'selected':''; ?>>Government</option>
                            <option value="private" <?= $typeF==='private'?'selected':''; ?>>Private</option>
                            <option value="aided" <?= $typeF==='aided'?'selected':''; ?>>Aided</option>
                            <option value="international" <?= $typeF==='international'?'selected':''; ?>>International</option>
                            <option value="boarding" <?= $typeF==='boarding'?'selected':''; ?>>Boarding</option>
                        </select>
                        <select name="board" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?= $boardF==='all'?'selected':''; ?>>All Boards</option>
                            <option value="CBSE" <?= $boardF==='CBSE'?'selected':''; ?>>CBSE</option>
                            <option value="ICSE" <?= $boardF==='ICSE'?'selected':''; ?>>ICSE</option>
                            <option value="State" <?= $boardF==='State'?'selected':''; ?>>State</option>
                            <option value="IB" <?= $boardF==='IB'?'selected':''; ?>>IB</option>
                            <option value="IGCSE" <?= $boardF==='IGCSE'?'selected':''; ?>>IGCSE</option>
                        </select>
                        <select name="publish" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?= $publishF==='all'?'selected':''; ?>>All Publish</option>
                            <option value="published" <?= $publishF==='published'?'selected':''; ?>>Published</option>
                            <option value="draft" <?= $publishF==='draft'?'selected':''; ?>>Draft</option>
                        </select>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary" style="white-space:nowrap;"><i class="ph ph-magnifying-glass"></i> Search</button>
                            <?php if($search || $statusF!=='all' || $typeF!=='all' || $boardF!=='all' || $publishF!=='all'): ?>
                                <a href="schools.php" style="padding:8px 14px; border:1px solid var(--border-color); border-radius:8px; font-size:0.88rem; text-decoration:none; color:var(--text-dark); white-space:nowrap;">Clear</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <div class="panel">
                    <?php if(empty($schools)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding: 40px;">No schools found. Click "Add New School" to create one.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th class="col-hide-mobile">Type</th>
                                        <th class="col-hide-mobile">Board</th>
                                        <th class="col-hide-mobile">Location</th>
                                        <th>Status</th>
                                        <th class="col-hide-mobile">Publish</th>
                                        <th class="col-hide-mobile">Verified</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($schools as $school): ?>
                                    <tr>
                                        <td class="school-name">
                                            <?= htmlspecialchars($school['name']) ?>
                                        </td>
                                        <td class="col-hide-mobile" style="text-transform: capitalize;">
                                            <?= htmlspecialchars($school['school_type'] ?? '—') ?>
                                        </td>
                                        <td class="col-hide-mobile">
                                            <?= htmlspecialchars($school['board_affiliation'] ?? '—') ?>
                                        </td>
                                        <td class="col-hide-mobile">
                                            <?php
                                            $loc = [];
                                            if($school['city_name']) $loc[] = $school['city_name'];
                                            if($school['state_name']) $loc[] = $school['state_name'];
                                            echo htmlspecialchars(implode(', ', $loc));
                                            if(empty($loc)) echo '<span style="color:rgba(15,23,42,0.4);">Not set</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $school['status'] ?>">
                                                <?= htmlspecialchars($school['status']) ?>
                                            </span>
                                        </td>
                                        <td class="col-hide-mobile">
                                            <?php
                                            $ps = $school['publish_status'] ?: 'draft';
                                            $ps_class = $ps == 'published' ? 'status-active' : 'status-pending';
                                            ?>
                                            <span class="status-badge <?= $ps_class ?>">
                                                <?= htmlspecialchars(ucfirst($ps)) ?>
                                            </span>
                                        </td>
                                        <td class="col-hide-mobile">
                                            <?php if($school['is_verified']): ?>
                                                <span class="verified-badge"><i class="ph-fill ph-seal-check"></i> Verified</span>
                                            <?php else: ?>
                                                <span style="color:rgba(15,23,42,0.4); font-size:0.85rem;">Unverified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-links">
                                                <a href="school_form.php?id=<?= $school['id'] ?>" class="action-btn" title="Edit">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </a>
                                                <?php if($school['status'] === 'archived'): ?>
                                                <a href="schools.php?action=restore&id=<?= $school['id'] ?>" class="action-btn" title="Restore" style="color:#16a34a;border-color:#16a34a;" onclick="return confirm('Restore this school?');">
                                                    <i class="ph ph-arrow-u-up-left"></i>
                                                </a>
                                                <?php else: ?>
                                                <a href="schools.php?action=archive&id=<?= $school['id'] ?>" class="action-btn delete" title="Archive" onclick="return confirm('Are you sure you want to archive this school?');">
                                                    <i class="ph ph-archive"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="schools.php?action=delete&id=<?= $school['id'] ?>" class="action-btn delete" title="Delete" onclick="return confirm('Are you sure you want to permanently delete this school? This cannot be undone.');">
                                                    <i class="ph ph-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile card layout -->
                        <div class="mobile-cards">
                            <?php foreach($schools as $school): ?>
                            <div class="mobile-card">
                                <div class="card-header">
                                    <div class="card-title"><?= htmlspecialchars($school['name']) ?></div>
                                    <div class="card-actions">
                                        <a href="school_form.php?id=<?= $school['id'] ?>" class="action-btn" title="Edit">
                                            <i class="ph ph-pencil-simple"></i>
                                        </a>
                                        <?php if($school['status'] === 'archived'): ?>
                                        <a href="schools.php?action=restore&id=<?= $school['id'] ?>" class="action-btn" title="Restore" style="color:#16a34a;border-color:#16a34a;" onclick="return confirm('Restore this school?');">
                                            <i class="ph ph-arrow-u-up-left"></i>
                                        </a>
                                        <?php else: ?>
                                        <a href="schools.php?action=archive&id=<?= $school['id'] ?>" class="action-btn delete" title="Archive" onclick="return confirm('Are you sure you want to archive this school?');">
                                            <i class="ph ph-archive"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="schools.php?action=delete&id=<?= $school['id'] ?>" class="action-btn delete" title="Delete" onclick="return confirm('Are you sure you want to permanently delete this school? This cannot be undone.');">
                                            <i class="ph ph-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="card-details">
                                    <div class="card-detail">
                                        <span class="label">Type</span>
                                        <span style="text-transform:capitalize;"><?= htmlspecialchars($school['school_type'] ?? '—') ?></span>
                                    </div>
                                    <div class="card-detail">
                                        <span class="label">Board</span>
                                        <span><?= htmlspecialchars($school['board_affiliation'] ?? '—') ?></span>
                                    </div>
                                    <div class="card-detail">
                                        <span class="label">Location</span>
                                        <span>
                                            <?php
                                            $loc = [];
                                            if($school['city_name']) $loc[] = $school['city_name'];
                                            if($school['state_name']) $loc[] = $school['state_name'];
                                            echo htmlspecialchars(implode(', ', $loc)) ?: '<span style="color:rgba(15,23,42,0.4);">Not set</span>';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-badges">
                                    <span class="status-badge status-<?= $school['status'] ?>"><?= htmlspecialchars($school['status']) ?></span>
                                    <?php
                                    $ps = $school['publish_status'] ?: 'draft';
                                    $ps_class = $ps == 'published' ? 'status-active' : 'status-pending';
                                    ?>
                                    <span class="status-badge <?= $ps_class ?>"><?= htmlspecialchars(ucfirst($ps)) ?></span>
                                    <?php if($school['is_verified']): ?>
                                        <span class="verified-badge"><i class="ph-fill ph-seal-check"></i> Verified</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="table-footer">
                            <span class="results-info">Showing <?= $totalRows > 0 ? ($offset+1) : 0; ?>–<?= min($offset+$perPage, $totalRows); ?> of <?= number_format($totalRows); ?> schools</span>
                            <?php if($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if($page > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])); ?>">&laquo;</a>
                                <?php endif; ?>
                                <?php for($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])); ?>" class="<?= $i===$page?'active':''; ?>"><?= $i; ?></a>
                                <?php endfor; ?>
                                <?php if($page < $totalPages): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])); ?>">&raquo;</a>
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
