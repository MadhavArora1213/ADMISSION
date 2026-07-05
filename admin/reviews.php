<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Fetch Reviews
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$query = "SELECT r.*, u.full_name as user_name, c.name as college_name, s.name as school_name, (SELECT COUNT(*) FROM review_reports rr WHERE rr.review_id = r.id AND rr.status = 'open') as open_reports FROM reviews r LEFT JOIN users u ON r.user_id = u.id LEFT JOIN colleges c ON r.college_id = c.id LEFT JOIN schools s ON r.school_id = s.id";

$conditions = [];
if ($status_filter !== 'all') {
    $conditions[] = "r.moderation_status = :status";
}
if ($type_filter === 'college') {
    $conditions[] = "r.college_id IS NOT NULL";
} elseif ($type_filter === 'school') {
    $conditions[] = "r.school_id IS NOT NULL";
}
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}
$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$bindParams = [];
if ($status_filter !== 'all') {
    $bindParams['status'] = $status_filter;
}
$stmt->execute($bindParams);
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews | AdmissionSeason Admin</title>
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
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); overflow-x: auto; }
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: thin; flex-wrap: nowrap; }
        .tabs-nav::-webkit-scrollbar { height: 5px; }
        .tabs-nav::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 3px; }
        .tabs-nav::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 3px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; flex-shrink: 0; font-size: 0.88rem; text-decoration: none; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; min-width: 600px; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; }
        tr:hover { background-color: rgba(0,0,0,0.02); }
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; white-space: nowrap; }
        .status-pending { background: rgba(11,36,71,0.06); color: #0F172A; }
        .status-approved { background: rgba(11,36,71,0.04); color: #0B2447; }
        .status-rejected { background: rgba(15,23,42,0.06); color: #0B2447; }
        .status-escalated { background: rgba(11,36,71,0.04); color: #0B2447; }
        .action-links { display: flex; gap: 8px; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #F8FAFC; color: var(--text-dark); border: 1px solid var(--border-color); text-decoration: none; }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
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
            .page-header { flex-direction: column; align-items: flex-start; }
            .page-header h2 { font-size: 1.4rem; }
            .panel { padding: 14px; }
            .tabs-nav { gap: 4px; margin-bottom: 16px; }
            .tab-link { padding: 6px 12px; font-size: 0.78rem; }
            th, td { padding: 10px 12px; font-size: 0.85rem; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
            .panel { padding: 10px; }
            .page-header h2 { font-size: 1.2rem; }
            .tabs-nav { gap: 3px; }
            .tab-link { padding: 5px 10px; font-size: 0.74rem; }
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
                    <a href="logout.php" style="margin-left: 16px; color: #19376d;"><i class="ph ph-sign-out" style="font-size: 1.5rem;"></i></a>
                </div>
            </header>
            <div class="content-area">
                <div class="page-header">
                    <div>
                        <h2>Manage Reviews</h2>
                        <p style="color: var(--text-muted);">Moderate and manage user submitted reviews.</p>
                    </div>
                </div>

                <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
                    <a href="?status=<?=$status_filter?>&type=all" class="tab-link <?= $type_filter=='all'?'active':''; ?>" style="text-decoration:none;padding:6px 14px;border-radius:8px;font-size:.85rem;font-weight:600;background:<?=$type_filter=='all'?'var(--primary)':'#f1f5f9'?>;color:<?=$type_filter=='all'?'#fff':'#64748b'?>">All Types</a>
                    <a href="?status=<?=$status_filter?>&type=college" class="tab-link <?= $type_filter=='college'?'active':''; ?>" style="text-decoration:none;padding:6px 14px;border-radius:8px;font-size:.85rem;font-weight:600;background:<?=$type_filter=='college'?'var(--primary)':'#f1f5f9'?>;color:<?=$type_filter=='college'?'#fff':'#64748b'?>">College</a>
                    <a href="?status=<?=$status_filter?>&type=school" class="tab-link <?= $type_filter=='school'?'active':''; ?>" style="text-decoration:none;padding:6px 14px;border-radius:8px;font-size:.85rem;font-weight:600;background:<?=$type_filter=='school'?'var(--primary)':'#f1f5f9'?>;color:<?=$type_filter=='school'?'#fff':'#64748b'?>">School</a>
                </div>

                <div class="tabs-nav">
                    <a href="?status=all&type=<?=$type_filter?>" class="tab-link <?php echo $status_filter=='all'?'active':''; ?>">All Reviews</a>
                    <a href="?status=pending&type=<?=$type_filter?>" class="tab-link <?php echo $status_filter=='pending'?'active':''; ?>">Pending</a>
                    <a href="?status=approved&type=<?=$type_filter?>" class="tab-link <?php echo $status_filter=='approved'?'active':''; ?>">Approved</a>
                    <a href="?status=rejected&type=<?=$type_filter?>" class="tab-link <?php echo $status_filter=='rejected'?'active':''; ?>">Rejected</a>
                    <a href="?status=escalated&type=<?=$type_filter?>" class="tab-link <?php echo $status_filter=='escalated'?'active':''; ?>">Escalated</a>
                </div>

                <div class="panel">
                    <?php if(empty($reviews)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding: 40px;">No reviews found for this status.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>College / User</th>
                                        <th>Rating</th>
                                        <th>Status</th>
                                        <th>Flags</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($reviews as $r): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: var(--primary);"><?php echo htmlspecialchars($r['school_name'] ?: $r['college_name'] ?: 'Unknown'); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $r['school_id'] ? '<span style="background:#e0f2fe;color:#0369a1;padding:1px 6px;border-radius:4px;font-size:.7rem">School</span>' : '<span style="background:#f0fdf4;color:#15803d;padding:1px 6px;border-radius:4px;font-size:.7rem">College</span>'; ?></div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);">by <?php echo htmlspecialchars($r['user_name']); ?> <?php if($r['is_verified_alumnus']) echo '<i class="ph-fill ph-seal-check" style="color:#19376D;" title="Verified Alumnus"></i>'; ?></div>
                                        </td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:4px; font-weight:700;">
                                                <i class="ph-fill ph-star" style="color:#19376D;"></i> <?php echo $r['overall_rating']; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge status-<?php echo $r['moderation_status']; ?>"><?php echo ucfirst($r['moderation_status']); ?></span>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:4px;">
                                                <?php if($r['fraud_flag']): ?><span class="badge" style="background:rgba(15,23,42,0.06); color:#0B2447;" title="Fraud Flag"><i class="ph ph-warning"></i> Fraud</span><?php endif; ?>
                                                <?php if($r['ai_spam_score'] > 0.7): ?><span class="badge" style="background:rgba(11,36,71,0.04); color:#0B2447;" title="High Spam Score"><i class="ph ph-robot"></i> Spam</span><?php endif; ?>
                                                <?php if($r['open_reports'] > 0): ?><span class="badge" style="background:rgba(15,23,42,0.06); color:#0B2447;" title="Open Reports"><i class="ph ph-flag"></i> <?php echo $r['open_reports']; ?></span><?php endif; ?>
                                                <?php if(!$r['fraud_flag'] && $r['ai_spam_score'] <= 0.7 && $r['open_reports'] == 0): ?><span style="color:var(--text-muted); font-size:0.8rem;">Clean</span><?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-links">
                                                <a href="review_moderation.php?id=<?php echo $r['id']; ?>" class="action-btn" title="Moderate / View Details"><i class="ph ph-magnifying-glass"></i></a>
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
