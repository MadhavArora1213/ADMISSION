<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

if (isset($_GET['action']) && $_GET['action'] === 'resolve' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE admin_alerts SET status = 'resolved', resolved_by = ?, resolved_at = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id'], $_GET['id']]);
    header("Location: alerts.php?msg=resolved");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'acknowledge' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE admin_alerts SET status = 'acknowledged' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: alerts.php?msg=acknowledged");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'ignore' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE admin_alerts SET status = 'ignored', resolved_by = ?, resolved_at = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id'], $_GET['id']]);
    header("Location: alerts.php?msg=ignored");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'resolve_all' && isset($_GET['type'])) {
    $stmt = $pdo->prepare("UPDATE admin_alerts SET status = 'resolved', resolved_by = ?, resolved_at = NOW() WHERE alert_type = ? AND status IN ('open','acknowledged')");
    $stmt->execute([$_SESSION['admin_id'], $_GET['type']]);
    header("Location: alerts.php?msg=resolved_all");
    exit;
}

function countSafe($pdo, $sql) {
    try { return (int)$pdo->query($sql)->fetchColumn(); } catch (Exception $e) { return 0; }
}

$filterSeverity = isset($_GET['severity']) ? trim($_GET['severity']) : '';
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$filterType = isset($_GET['type_filter']) ? trim($_GET['type_filter']) : '';

$where = "1=1";
$params = [];
if ($filterSeverity && in_array($filterSeverity, ['low','medium','high','critical'])) {
    $where .= " AND a.severity = ?";
    $params[] = $filterSeverity;
}
if ($filterStatus && in_array($filterStatus, ['open','acknowledged','resolved','ignored'])) {
    $where .= " AND a.status = ?";
    $params[] = $filterStatus;
}
if ($filterType) {
    $where .= " AND a.alert_type = ?";
    $params[] = $filterType;
}

$stmt = $pdo->prepare("SELECT a.*, u.full_name as resolved_by_name FROM admin_alerts a LEFT JOIN users u ON a.resolved_by = u.id WHERE $where ORDER BY FIELD(a.severity, 'critical','high','medium','low'), a.created_at DESC LIMIT 200");
$stmt->execute($params);
$alerts = $stmt->fetchAll();

$totalAlerts = countSafe($pdo, "SELECT COUNT(*) FROM admin_alerts");
$openAlerts = countSafe($pdo, "SELECT COUNT(*) FROM admin_alerts WHERE status = 'open'");
$criticalCount = countSafe($pdo, "SELECT COUNT(*) FROM admin_alerts WHERE status = 'open' AND severity = 'critical'");
$highCount = countSafe($pdo, "SELECT COUNT(*) FROM admin_alerts WHERE status = 'open' AND severity = 'high'");
$acknowledgedCount = countSafe($pdo, "SELECT COUNT(*) FROM admin_alerts WHERE status = 'acknowledged'");
$resolvedToday = countSafe($pdo, "SELECT COUNT(*) FROM admin_alerts WHERE status = 'resolved' AND DATE(resolved_at) = CURDATE()");

$typeBreakdown = $pdo->query("SELECT alert_type, severity, COUNT(*) as cnt, MIN(created_at) as oldest FROM admin_alerts WHERE status IN ('open','acknowledged') GROUP BY alert_type, severity ORDER BY FIELD(severity, 'critical','high','medium','low'), cnt DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Alerts | Admin Panel</title>
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
        .page-header h2 { font-size: 2rem; font-weight: 800; }

        .stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px; }
        .mini-stat { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); position: relative; overflow: hidden; }
        .mini-stat .label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 4px; }
        .mini-stat .value { font-size: 1.8rem; font-weight: 800; }
        .mini-stat .icon-bg { position: absolute; right: 12px; top: 12px; font-size: 2rem; opacity: 0.08; }
        .v-critical { color: #dc2626; }
        .v-high { color: #ea580c; }
        .v-medium { color: #ca8a04; }
        .v-open { color: #2563eb; }
        .v-ack { color: #9333ea; }
        .v-resolved { color: #16a34a; }

        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px;}
        .panel-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .panel-header h3 { font-size: 1rem; font-weight: 700; color: var(--text-dark); }

        .filter-bar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 20px; background: #fff; padding: 16px 20px; border-radius: 12px; border: 1px solid var(--border-color); }
        .filter-bar select { padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 0.85rem; outline: none; background: #fff; }
        .btn { padding: 8px 18px; border-radius: 8px; border: none; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-dark); }
        .btn-outline:hover { background: #f1f5f9; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-success:hover { background: #15803d; }
        .btn-sm { padding: 5px 12px; font-size: 0.78rem; }

        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top;}
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.05em; background: #F8FAFC; position: sticky; top: 0; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        tr.row-critical { border-left: 3px solid #dc2626; }
        tr.row-high { border-left: 3px solid #ea580c; }

        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap; text-transform: capitalize;}
        .sev-critical { background:rgba(220,38,38,0.1); color:#dc2626; }
        .sev-high { background:rgba(234,88,12,0.1); color:#ea580c; }
        .sev-medium { background:rgba(202,138,4,0.1); color:#ca8a04; }
        .sev-low { background:rgba(100,116,139,0.1); color:#64748b; }
        .st-open { background:rgba(37,99,235,0.1); color:#2563eb; }
        .st-acknowledged { background:rgba(147,51,234,0.1); color:#9333ea; }
        .st-resolved { background:rgba(22,163,74,0.1); color:#16a34a; }
        .st-ignored { background:rgba(100,116,139,0.1); color:#64748b; }

        .type-chips { display: flex; flex-wrap: wrap; gap: 8px; padding: 16px 24px; }
        .type-chip { display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: #fff; border-radius: 8px; border: 1px solid var(--border-color); font-size: 0.8rem; }
        .type-chip .cnt { font-weight: 800; font-size: 1.1rem; }
        .msg-alert { padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid; display: flex; align-items: center; gap: 8px; }
        .msg-success { background: rgba(22,163,74,0.05); color: #16a34a; border-color: rgba(22,163,74,0.15); }
        .msg-info { background: rgba(37,99,235,0.05); color: #2563eb; border-color: rgba(37,99,235,0.15); }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; opacity: 0.3; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span></div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-bell-ringing" style="color:var(--primary);"></i> System Alerts</h2>
                    <p style="color:var(--text-muted);">Real-time monitoring for system health, data quality, security, and business operations.</p>
                </div>
                <div style="display:flex; gap:8px;">
                    <a href="alert_scanner.php" class="btn btn-primary" onclick="return confirm('Scan system for new alerts now?')"><i class="ph ph-magnifying-glass"></i> Run Scan</a>
                    <a href="?severity=&status=&type_filter=" class="btn btn-outline"><i class="ph ph-arrows-clockwise"></i> Reset</a>
                </div>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert msg-success">
                <i class="ph ph-check-circle"></i>
                <?php
                switch($_GET['msg']) {
                    case 'resolved': echo 'Alert resolved successfully.'; break;
                    case 'acknowledged': echo 'Alert acknowledged.'; break;
                    case 'ignored': echo 'Alert ignored.'; break;
                    case 'resolved_all': echo 'All matching alerts resolved.'; break;
                    case 'scanned': echo 'System scan complete.' . (isset($_GET['generated']) ? " Generated {$_GET['generated']} new alert(s)." : ''); break;
                }
                ?>
            </div>
            <?php endif; ?>

            <div class="stats-row">
                <div class="mini-stat">
                    <div class="icon-bg"><i class="ph ph-bell-ringing"></i></div>
                    <div class="label">Open Alerts</div>
                    <div class="value v-open"><?php echo number_format($openAlerts); ?></div>
                </div>
                <div class="mini-stat">
                    <div class="icon-bg"><i class="ph ph-warning-circle"></i></div>
                    <div class="label">Critical</div>
                    <div class="value v-critical"><?php echo number_format($criticalCount); ?></div>
                </div>
                <div class="mini-stat">
                    <div class="icon-bg"><i class="ph ph-flag"></i></div>
                    <div class="label">High Priority</div>
                    <div class="value v-high"><?php echo number_format($highCount); ?></div>
                </div>
                <div class="mini-stat">
                    <div class="icon-bg"><i class="ph ph-clock"></i></div>
                    <div class="label">Acknowledged</div>
                    <div class="value v-ack"><?php echo number_format($acknowledgedCount); ?></div>
                </div>
                <div class="mini-stat">
                    <div class="icon-bg"><i class="ph ph-check-circle"></i></div>
                    <div class="label">Resolved Today</div>
                    <div class="value v-resolved"><?php echo number_format($resolvedToday); ?></div>
                </div>
            </div>

            <?php if (!empty($typeBreakdown)): ?>
            <div class="panel" style="margin-bottom:20px;">
                <div class="panel-header">
                    <h3><i class="ph ph-chart-bar"></i> Open Alerts by Type</h3>
                </div>
                <div class="type-chips">
                    <?php foreach($typeBreakdown as $tb): ?>
                    <div class="type-chip">
                        <span class="badge sev-<?php echo $tb['severity']; ?>"><?php echo $tb['severity']; ?></span>
                        <div>
                            <div style="font-weight:700; font-size:0.82rem;"><?php echo ucfirst(str_replace('_', ' ', $tb['alert_type'])); ?></div>
                            <div style="font-size:0.72rem; color:var(--text-muted);">Oldest: <?php echo date('M d', strtotime($tb['oldest'])); ?></div>
                        </div>
                        <span class="cnt" style="margin-left:auto; color:var(--primary);"><?php echo $tb['cnt']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <form method="GET" class="filter-bar">
                <select name="severity">
                    <option value="">All Severities</option>
                    <option value="critical" <?php echo $filterSeverity==='critical'?'selected':''; ?>>Critical</option>
                    <option value="high" <?php echo $filterSeverity==='high'?'selected':''; ?>>High</option>
                    <option value="medium" <?php echo $filterSeverity==='medium'?'selected':''; ?>>Medium</option>
                    <option value="low" <?php echo $filterSeverity==='low'?'selected':''; ?>>Low</option>
                </select>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="open" <?php echo $filterStatus==='open'?'selected':''; ?>>Open</option>
                    <option value="acknowledged" <?php echo $filterStatus==='acknowledged'?'selected':''; ?>>Acknowledged</option>
                    <option value="resolved" <?php echo $filterStatus==='resolved'?'selected':''; ?>>Resolved</option>
                    <option value="ignored" <?php echo $filterStatus==='ignored'?'selected':''; ?>>Ignored</option>
                </select>
                <select name="type_filter">
                    <option value="">All Types</option>
                    <?php
                    $types = $pdo->query("SELECT DISTINCT alert_type FROM admin_alerts ORDER BY alert_type")->fetchAll(PDO::FETCH_COLUMN);
                    foreach($types as $t): ?>
                    <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $filterType===$t?'selected':''; ?>><?php echo ucfirst(str_replace('_', ' ', $t)); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </form>

            <div class="panel">
                <?php if(empty($alerts)): ?>
                <div class="empty-state">
                    <i class="ph ph-bell-simple"></i>
                    <div style="font-size:1rem; font-weight:600; margin-bottom:4px;">No alerts found</div>
                    <div style="font-size:0.85rem;">Run a system scan to check for issues, or adjust your filters.</div>
                </div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:150px;">Time</th>
                                <th style="width:80px;">Severity</th>
                                <th style="width:110px;">Type</th>
                                <th style="width:120px;">Source</th>
                                <th>Title</th>
                                <th style="width:260px;">Message</th>
                                <th style="width:100px;">Status</th>
                                <th style="width:200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($alerts as $alert): ?>
                            <tr class="<?php echo $alert['severity']==='critical'?'row-critical':($alert['severity']==='high'?'row-high':''); ?>">
                                <td style="white-space:nowrap;">
                                    <div style="font-weight:600; font-size:0.82rem;"><?php echo date('M d', strtotime($alert['created_at'])); ?></div>
                                    <div style="font-size:0.72rem; color:var(--text-muted); font-family:monospace;"><?php echo date('H:i:s', strtotime($alert['created_at'])); ?></div>
                                </td>
                                <td><span class="badge sev-<?php echo $alert['severity']; ?>"><?php echo $alert['severity']; ?></span></td>
                                <td><span style="font-weight:600; font-size:0.8rem; color:var(--text-dark);"><?php echo ucfirst(str_replace('_', ' ', $alert['alert_type'])); ?></span></td>
                                <td style="font-size:0.78rem; color:var(--text-muted);"><?php echo htmlspecialchars($alert['source_module'] ?? '—'); ?></td>
                                <td>
                                    <div style="font-weight:600; font-size:0.85rem; margin-bottom:2px;"><?php echo htmlspecialchars($alert['title']); ?></div>
                                </td>
                                <td style="font-size:0.82rem; color:#475569; max-width:260px; line-height:1.4;"><?php echo htmlspecialchars($alert['message']); ?></td>
                                <td>
                                    <span class="badge st-<?php echo $alert['status']; ?>"><?php echo $alert['status']; ?></span>
                                    <?php if($alert['status'] === 'resolved' && $alert['resolved_at']): ?>
                                        <div style="font-size:0.68rem; color:var(--text-muted); margin-top:3px;">by <?php echo htmlspecialchars($alert['resolved_by_name'] ?? 'Admin'); ?> on <?php echo date('M d H:i', strtotime($alert['resolved_at'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(in_array($alert['status'], ['open','acknowledged'])): ?>
                                    <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                        <a href="?action=acknowledge&id=<?php echo $alert['id']; ?>&<?php echo http_build_query(array_filter(['severity'=>$filterSeverity,'status'=>$filterStatus,'type_filter'=>$filterType])); ?>" class="btn btn-outline btn-sm" title="Acknowledge">Ack</a>
                                        <a href="?action=resolve&id=<?php echo $alert['id']; ?>&<?php echo http_build_query(array_filter(['severity'=>$filterSeverity,'status'=>$filterStatus,'type'=>$filterType])); ?>" class="btn btn-success btn-sm" onclick="return confirm('Resolve this alert?')">Resolve</a>
                                        <a href="?action=ignore&id=<?php echo $alert['id']; ?>&<?php echo http_build_query(array_filter(['severity'=>$filterSeverity,'status'=>$filterStatus,'type_filter'=>$filterType])); ?>" class="btn btn-outline btn-sm" onclick="return confirm('Ignore this alert?')" title="Ignore">Skip</a>
                                    </div>
                                    <?php elseif($alert['status'] === 'open'): ?>
                                    <a href="?action=resolve_all&type=<?php echo $alert['alert_type']; ?>" class="btn btn-outline btn-sm" onclick="return confirm('Resolve ALL alerts of type '<?php echo $alert['alert_type']; ?>'?')">Resolve All Same</a>
                                    <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.78rem;">—</span>
                                    <?php endif; ?>
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
