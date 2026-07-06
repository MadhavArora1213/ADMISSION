<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$actionFilter = isset($_GET['action']) ? trim($_GET['action']) : '';
$entityFilter = isset($_GET['entity']) ? trim($_GET['entity']) : '';
$dateFrom = isset($_GET['from']) ? trim($_GET['from']) : '';
$dateTo = isset($_GET['to']) ? trim($_GET['to']) : '';

$where = "1=1";
$params = [];
if ($search) {
    $where .= " AND (a.entity_type LIKE ? OR a.entity_id LIKE ? OR u.full_name LIKE ? OR a.ip_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($actionFilter && in_array($actionFilter, ['create','update','delete','login','export','status_change','permission_change','login_failed','bulk_delete'])) {
    $where .= " AND a.audit_action = ?";
    $params[] = $actionFilter;
}
if ($entityFilter) {
    $where .= " AND a.entity_type = ?";
    $params[] = $entityFilter;
}
if ($dateFrom) {
    $where .= " AND DATE(a.created_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where .= " AND DATE(a.created_at) <= ?";
    $params[] = $dateTo;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE $where");
$countStmt->execute($params);
$totalLogs = (int) $countStmt->fetchColumn();

$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$perPage = 50;
$offset = ($page - 1) * $perPage;
$totalPages = max(1, ceil($totalLogs / $perPage));

$stmt = $pdo->prepare("
    SELECT a.*, u.full_name as user_name
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    WHERE $where
    ORDER BY a.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$entityStats = $pdo->query("SELECT entity_type, COUNT(*) as cnt FROM audit_logs GROUP BY entity_type ORDER BY cnt DESC")->fetchAll();
$actionStats = $pdo->query("SELECT audit_action, COUNT(*) as cnt FROM audit_logs GROUP BY audit_action ORDER BY cnt DESC")->fetchAll();
$recentActivity = $pdo->query("SELECT DATE(created_at) as day, COUNT(*) as cnt FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY day ORDER BY day DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | Admin Panel</title>
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
        .content-area { padding: 32px; max-width: 1400px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .mini-stat { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); }
        .mini-stat .label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 4px; }
        .mini-stat .value { font-size: 1.8rem; font-weight: 800; color: var(--text-dark); }
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px; }
        .panel-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .panel-header h3 { font-size: 1rem; font-weight: 700; color: var(--text-dark); }
        .filter-bar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 20px; background: #fff; padding: 16px 20px; border-radius: 12px; border: 1px solid var(--border-color); }
        .filter-bar select, .filter-bar input[type="text"], .filter-bar input[type="date"] { padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 0.85rem; outline: none; background: #fff; min-width: 0; box-sizing: border-box; }
        .filter-bar select:focus, .filter-bar input:focus { border-color: var(--primary); }
        .btn { padding: 8px 18px; border-radius: 8px; border: none; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-dark); }
        .btn-outline:hover { background: #f1f5f9; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; min-width: 800px; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top; }
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.05em; background: #F8FAFC; position: sticky; top: 0; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap; text-transform: uppercase; }
        .a-create { background: rgba(34,197,94,0.1); color: #16a34a; }
        .a-update { background: rgba(59,130,246,0.1); color: #2563eb; }
        .a-delete { background: rgba(239,68,68,0.1); color: #dc2626; }
        .a-login { background: rgba(168,85,247,0.1); color: #9333ea; }
        .a-export { background: rgba(234,179,8,0.1); color: #ca8a04; }
        .a-status_change { background: rgba(20,184,166,0.1); color: #0d9488; }
        .a-permission_change { background: rgba(249,115,22,0.1); color: #ea580c; }
        .a-login_failed { background: rgba(239,68,68,0.1); color: #dc2626; }
        .a-bulk_delete { background: rgba(239,68,68,0.15); color: #b91c1c; }
        .entity-badge { padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; background: #f1f5f9; color: #475569; display: inline-block; }
        .json-viewer { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 0.72rem; background: #f8fafc; padding: 8px 10px; border-radius: 6px; max-height: 80px; overflow-y: auto; max-width: 280px; white-space: pre-wrap; word-break: break-all; border: 1px solid #e2e8f0; line-height: 1.5; }
        .pagination { display: flex; gap: 4px; justify-content: center; margin-top: 20px; padding-bottom: 20px; flex-wrap: wrap; }
        .pagination a { padding: 6px 12px; border-radius: 6px; font-size: 0.82rem; text-decoration: none; color: var(--text-dark); border: 1px solid var(--border-color); }
        .pagination a.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .pagination a:hover:not(.active) { background: #f1f5f9; }
        .stat-chips { display: flex; flex-wrap: wrap; gap: 8px; padding: 16px 24px; }
        .stat-chip { display: flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f8fafc; border-radius: 8px; font-size: 0.78rem; border: 1px solid var(--border-color); }
        .stat-chip .dot { width: 8px; height: 8px; border-radius: 50%; }
        .dot-create { background: #16a34a; }
        .dot-update { background: #2563eb; }
        .dot-delete { background: #dc2626; }
        .dot-login { background: #9333ea; }
        .dot-export { background: #ca8a04; }
        .dot-status { background: #0d9488; }
        .dot-permission { background: #ea580c; }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        @media (max-width: 1024px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
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
            .stats-row { grid-template-columns: 1fr 1fr; gap: 10px; }
            .mini-stat { padding: 14px; }
            .mini-stat .value { font-size: 1.3rem; }
            .filter-bar { padding: 12px; gap: 8px; }
            .filter-bar input[type="text"] { width: 100%; }
            .filter-bar select { flex: 1; min-width: 100px; }
            th, td { padding: 10px 12px; font-size: 0.8rem; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
            .stats-row { grid-template-columns: 1fr; }
            .page-header h2 { font-size: 1.2rem; }
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
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-file-search" style="color:var(--primary);"></i> Audit Logs</h2>
                    <p style="color:var(--text-muted);">Track all system changes, user actions, and data modifications across the platform.</p>
                </div>
                <div style="display:flex; gap:8px;">
                    <a href="?q=&action=&entity=&from=&to=" class="btn btn-outline"><i class="ph ph-arrows-clockwise"></i> Reset Filters</a>
                </div>
            </div>

            <div class="stats-row">
                <div class="mini-stat">
                    <div class="label">Total Logs</div>
                    <div class="value"><?php echo number_format($totalLogs); ?></div>
                </div>
                <div class="mini-stat">
                    <div class="label">Today</div>
                    <div class="value"><?php
                        $todayCount = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
                        echo number_format($todayCount);
                    ?></div>
                </div>
                <div class="mini-stat">
                    <div class="label">Unique Entities</div>
                    <div class="value"><?php echo count($entityStats); ?></div>
                </div>
                <div class="mini-stat">
                    <div class="label">Last 7 Days</div>
                    <div class="value"><?php
                        $weekCount = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
                        echo number_format($weekCount);
                    ?></div>
                </div>
            </div>

            <div class="panel" style="margin-bottom:20px;">
                <div class="panel-header">
                    <h3>Action Breakdown</h3>
                </div>
                <div class="stat-chips">
                    <?php foreach($actionStats as $as): ?>
                    <div class="stat-chip">
                        <span class="dot dot-<?php echo $as['audit_action'] === 'status_change' ? 'status' : ($as['audit_action'] === 'permission_change' ? 'permission' : $as['audit_action']); ?>"></span>
                        <span style="font-weight:600;"><?php echo ucfirst(str_replace('_', ' ', $as['audit_action'])); ?></span>
                        <span style="color:var(--text-muted);"><?php echo number_format($as['cnt']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <form method="GET" class="filter-bar">
                <div style="display:flex;align-items:center;gap:6px;">
                    <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                    <input type="text" name="q" placeholder="Search entity, user, IP..." value="<?php echo htmlspecialchars($search); ?>" style="width:200px;">
                </div>
                <select name="action">
                    <option value="">All Actions</option>
                    <?php foreach(['create','update','delete','login','login_failed','export','status_change','permission_change','bulk_delete'] as $a): ?>
                    <option value="<?php echo $a; ?>" <?php echo $actionFilter === $a ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $a)); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="entity">
                    <option value="">All Entities</option>
                    <?php foreach($entityStats as $es): ?>
                    <option value="<?php echo htmlspecialchars($es['entity_type']); ?>" <?php echo $entityFilter === $es['entity_type'] ? 'selected' : ''; ?>><?php echo ucfirst($es['entity_type']); ?> (<?php echo $es['cnt']; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="from" value="<?php echo htmlspecialchars($dateFrom); ?>" title="From date">
                <input type="date" name="to" value="<?php echo htmlspecialchars($dateTo); ?>" title="To date">
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>

            <div class="panel">
                <?php if(empty($logs)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:60px 20px; font-size:0.95rem;">
                        <i class="ph ph-magnifying-glass" style="font-size:2rem; display:block; margin-bottom:12px;"></i>
                        No audit logs found matching your filters.
                    </p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:180px;">Timestamp</th>
                                <th style="width:80px;">IP Address</th>
                                <th style="width:140px;">User</th>
                                <th style="width:120px;">Action</th>
                                <th style="width:130px;">Entity</th>
                                <th style="width:100px;">Entity ID</th>
                                <th>Changes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($logs as $l): ?>
                            <tr>
                                <td style="white-space:nowrap;">
                                    <div style="font-weight:600; font-size:0.82rem;"><?php echo date('M d, Y', strtotime($l['created_at'])); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted); font-family:monospace;"><?php echo date('H:i:s', strtotime($l['created_at'])); ?></div>
                                </td>
                                <td>
                                    <div style="font-size:0.78rem; color:var(--text-muted); font-family:monospace;"><?php echo htmlspecialchars($l['ip_address'] ?? '—'); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight:600; color:var(--primary); font-size:0.85rem;"><?php echo htmlspecialchars($l['user_name'] ?? 'System'); ?></div>
                                </td>
                                <td>
                                    <span class="badge a-<?php echo $l['audit_action']; ?>"><?php echo str_replace('_', ' ', $l['audit_action']); ?></span>
                                </td>
                                <td>
                                    <span class="entity-badge"><?php echo htmlspecialchars($l['entity_type']); ?></span>
                                </td>
                                <td>
                                    <?php if($l['entity_id']): ?>
                                        <div style="font-size:0.72rem; font-family:monospace; color:var(--text-muted); max-width:90px; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($l['entity_id']); ?>"><?php echo htmlspecialchars(substr($l['entity_id'], 0, 8)); ?>...</div>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($l['old_value'] || $l['new_value']): ?>
                                        <div style="display:flex; gap:10px;">
                                            <?php if($l['old_value']): ?>
                                                <div style="flex:1; min-width:0;">
                                                    <div style="font-size:0.65rem; font-weight:700; color:#dc2626; margin-bottom:3px; text-transform:uppercase;">Before</div>
                                                    <div class="json-viewer"><?php
                                                        $decoded = json_decode($l['old_value'], true);
                                                        echo htmlspecialchars($decoded ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $l['old_value']);
                                                    ?></div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if($l['new_value']): ?>
                                                <div style="flex:1; min-width:0;">
                                                    <div style="font-size:0.65rem; font-weight:700; color:#16a34a; margin-bottom:3px; text-transform:uppercase;">After</div>
                                                    <div class="json-viewer"><?php
                                                        $decoded = json_decode($l['new_value'], true);
                                                        echo htmlspecialchars($decoded ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $l['new_value']);
                                                    ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.8rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if($totalPages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Prev</a>
                    <?php endif; ?>
                    <?php for($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                    <?php endif; ?>
                    <span style="padding:6px 12px; font-size:0.82rem; color:var(--text-muted);">Page <?php echo $page; ?> of <?php echo $totalPages; ?> (<?php echo number_format($totalLogs); ?> logs)</span>
                </div>
                <?php endif; ?>
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
