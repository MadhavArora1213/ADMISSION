<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : 'all';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Handle Moderation Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id']) && isset($_POST['action_taken'])) {
    $ticket_id = $_POST['ticket_id'];
    $action_taken = $_POST['action_taken'];
    $action_note = $_POST['action_note'] ?? '';
    
    // Status depends on the action. Let's assume any action resolves it, except dismiss which just dismisses it
    $new_status = ($action_taken === 'dismiss') ? 'dismissed' : 'resolved';
    // If they selected dismiss, the actual action taken is null or 'dismiss' 
    // Wait, 'dismiss' is not in our action_taken ENUM, so we just set status='dismissed', action_taken=NULL
    if ($action_taken === 'dismiss') {
        $real_action = null;
    } else {
        $real_action = $action_taken;
    }

    $stmt = $pdo->prepare("
        UPDATE moderation_queue 
        SET status = ?, action_taken = ?, action_note = ?, moderator_id = ?, actioned_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $real_action, $action_note, $_SESSION['admin_id'], $ticket_id]);
    
    header("Location: moderation_queue.php?status=" . urlencode($status_filter) . "&msg=updated");
    exit;
}

// Build query
$where = [];
$params = [];

if ($status_filter !== 'all') { $where[] = "m.status = ?"; $params[] = $status_filter; }
if ($priority_filter !== 'all') { $where[] = "m.priority = ?"; $params[] = $priority_filter; }
if ($type_filter !== 'all') { $where[] = "m.entity_type = ?"; $params[] = $type_filter; }

if ($search !== '') { 
    $where[] = "(m.entity_id LIKE ? OR m.flagged_reason LIKE ?)"; 
    $params[] = "%$search%"; 
    $params[] = "%$search%"; 
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("SELECT m.*, u.full_name as reporter_name FROM moderation_queue m LEFT JOIN users u ON m.reporter_id = u.id $whereSQL ORDER BY m.created_at DESC LIMIT 100");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Count stats
$stats = $pdo->query("SELECT status, COUNT(*) AS cnt FROM moderation_queue GROUP BY status")->fetchAll();
$counts = ['pending'=>0,'in_progress'=>0,'resolved'=>0,'dismissed'=>0];
foreach($stats as $s) $counts[$s['status']] = $s['cnt'];
$total = array_sum($counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderation Queue | Admin Panel</title>
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
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #f8fafc; border-radius: 12px; border: 1px solid var(--border-color); padding: 20px; box-shadow: var(--shadow-sm); }
        .stat-card .num { font-size: 2rem; font-weight: 800; }
        .stat-card .label { font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-top: 4px; }
        
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .tab-link { padding: 7px 14px; font-weight: 600; color: var(--text-muted); border-radius: 8px; border: 1px solid var(--border-color); background: #f8fafc; font-size: 0.85rem; text-decoration: none; transition: all 0.2s; white-space: nowrap; }
        .tab-link:hover, .tab-link.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top; }
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; background: #f1f5f9; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap; }
        
        /* Priorities */
        .p-critical { background:#fef2f2; color:#dc2626; border: 1px solid #fca5a5; }
        .p-high { background:#fff7ed; color:#ea580c; border: 1px solid #fdba74; }
        .p-medium { background:#fefce8; color:#ca8a04; border: 1px solid #fde047; }
        .p-low { background:#f0fdf4; color:#16a34a; border: 1px solid #86efac; }
        
        /* Statuses */
        .s-pending { background:#f1f5f9; color:#475569; }
        .s-in_progress { background:#dbeafe; color:#1e40af; }
        .s-resolved { background:#dcfce7; color:#166534; }
        .s-dismissed { background:#f1f5f9; color:#94a3b8; text-decoration: line-through;}
        
        .search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 7px 14px; }
        .search-box input { border: none; outline: none; font-size: 0.9rem; width: 220px; }
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 20px; border: 1px solid #bbf7d0; }
        
        /* Action form */
        .action-form { display: flex; flex-direction: column; gap: 8px; }
        .action-form select, .action-form input { padding: 6px; font-size: 0.85rem; border: 1px solid var(--border-color); border-radius: 4px; }
        .btn-small { padding: 6px 12px; font-size: 0.85rem; background: var(--primary); color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .btn-small:hover { opacity: 0.9; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-shield-check" style="color:var(--primary);"></i> Moderation Queue</h2>
                    <p style="color:var(--text-muted);">Review and resolve flagged content and user reports.</p>
                </div>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg']=='updated'): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Ticket resolved successfully.</div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card"><div class="num"><?php echo $total; ?></div><div class="label">Total Tickets</div></div>
                <div class="stat-card"><div class="num" style="color:#dc2626;"><?php echo $counts['pending']; ?></div><div class="label">Pending</div></div>
                <div class="stat-card"><div class="num" style="color:#1e40af;"><?php echo $counts['in_progress']; ?></div><div class="label">In Progress</div></div>
                <div class="stat-card"><div class="num" style="color:#166534;"><?php echo $counts['resolved']; ?></div><div class="label">Resolved</div></div>
            </div>

            <div class="filter-bar">
                <a href="?status=all" class="tab-link <?php echo $status_filter=='all'?'active':''; ?>">All Status</a>
                <a href="?status=pending" class="tab-link <?php echo $status_filter=='pending'?'active':''; ?>">Pending</a>
                <a href="?status=in_progress" class="tab-link <?php echo $status_filter=='in_progress'?'active':''; ?>">In Progress</a>
                <a href="?status=resolved" class="tab-link <?php echo $status_filter=='resolved'?'active':''; ?>">Resolved</a>
                <a href="?status=dismissed" class="tab-link <?php echo $status_filter=='dismissed'?'active':''; ?>">Dismissed</a>
                
                <div style="width:1px; height:28px; background:var(--border-color); margin:0 4px;"></div>
                
                <form method="GET" style="margin-left:auto; display:flex; gap:8px; align-items:center;">
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                    <select name="priority" style="padding: 7px 14px; border: 1px solid var(--border-color); border-radius: 8px; background: #fff;" onchange="this.form.submit()">
                        <option value="all" <?php echo $priority_filter=='all'?'selected':''; ?>>All Priorities</option>
                        <option value="critical" <?php echo $priority_filter=='critical'?'selected':''; ?>>Critical</option>
                        <option value="high" <?php echo $priority_filter=='high'?'selected':''; ?>>High</option>
                        <option value="medium" <?php echo $priority_filter=='medium'?'selected':''; ?>>Medium</option>
                        <option value="low" <?php echo $priority_filter=='low'?'selected':''; ?>>Low</option>
                    </select>
                    <div class="search-box">
                        <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                        <input type="text" name="q" placeholder="Search ID or Reason..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn-small" style="padding:8px 14px;">Filter</button>
                </form>
            </div>

            <div class="panel">
                <?php if(empty($tickets)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:40px;">No tickets found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Report Details</th>
                                <th>Entity Target</th>
                                <th>AI / Reporter</th>
                                <th>Priority & Status</th>
                                <th>Resolution Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tickets as $t): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700; margin-bottom:4px; text-transform: capitalize;">
                                        <i class="ph ph-flag" style="color: #ea580c;"></i> <?php echo htmlspecialchars(str_replace('_',' ',$t['flagged_reason'])); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">Created: <?php echo date('M d, g:i A', strtotime($t['created_at'])); ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top:2px;">SLA: <span style="<?php echo (strtotime($t['sla_due_at']) < time() && $t['status'] !== 'resolved' && $t['status'] !== 'dismissed') ? 'color: #dc2626; font-weight:700;' : ''; ?>"><?php echo date('M d, g:i A', strtotime($t['sla_due_at'])); ?></span></div>
                                </td>
                                <td>
                                    <span class="badge" style="background:#e2e8f0; color:#0f172a; margin-bottom: 6px;"><?php echo strtoupper($t['entity_type']); ?></span>
                                    <div style="font-size: 0.75rem; font-family: monospace; color: var(--text-muted); word-break: break-all; max-width: 200px;"><?php echo htmlspecialchars($t['entity_id']); ?></div>
                                    <a href="#" style="font-size: 0.8rem; color: var(--primary); text-decoration: none; display:inline-block; margin-top:4px;">View Content &rarr;</a>
                                </td>
                                <td>
                                    <?php if($t['ai_score']): ?>
                                        <div style="font-size: 0.85rem; margin-bottom:4px;"><strong>AI Score:</strong> <?php echo number_format($t['ai_score'], 2); ?></div>
                                    <?php endif; ?>
                                    <?php if($t['reporter_id']): ?>
                                        <div style="font-size: 0.85rem;"><strong>By:</strong> <?php echo htmlspecialchars($t['reporter_name'] ?? 'Unknown'); ?></div>
                                    <?php else: ?>
                                        <div style="font-size: 0.85rem; color: var(--text-muted);"><i class="ph ph-robot"></i> System Flagged</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="margin-bottom:6px;"><span class="badge p-<?php echo $t['priority']; ?>"><?php echo strtoupper($t['priority']); ?></span></div>
                                    <div><span class="badge s-<?php echo $t['status']; ?>"><?php echo ucfirst(str_replace('_',' ',$t['status'])); ?></span></div>
                                </td>
                                <td>
                                    <?php if($t['status'] === 'resolved' || $t['status'] === 'dismissed'): ?>
                                        <div style="font-size: 0.85rem;">
                                            <strong>Action:</strong> <?php echo $t['action_taken'] ? ucfirst($t['action_taken']) : 'Dismissed'; ?><br>
                                            <?php if($t['action_note']): ?>
                                                <span style="color: var(--text-muted);">"<?php echo htmlspecialchars($t['action_note']); ?>"</span><br>
                                            <?php endif; ?>
                                            <span style="font-size:0.75rem; color:var(--text-muted);">Completed at <?php echo date('M d, g:i A', strtotime($t['actioned_at'])); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($t['id']); ?>">
                                            <select name="action_taken" required>
                                                <option value="">-- Select Action --</option>
                                                <option value="approve">Approve Content</option>
                                                <option value="reject">Reject/Delete Content</option>
                                                <option value="warn_user">Warn User</option>
                                                <option value="escalate">Escalate</option>
                                                <option value="dismiss">Dismiss Report</option>
                                            </select>
                                            <input type="text" name="action_note" placeholder="Optional note...">
                                            <button type="submit" class="btn-small">Resolve</button>
                                        </form>
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
