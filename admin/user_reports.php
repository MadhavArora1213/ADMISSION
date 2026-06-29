<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'reviews';

if ($tab === 'reviews') {
    // Fetch pending review reports
    $stmt = $pdo->query("
        SELECT rr.*, u.full_name as reporter_name, r.review_body as review_text, r.overall_rating as rating, c.name as college_name 
        FROM review_reports rr 
        LEFT JOIN users u ON rr.reported_by = u.id 
        LEFT JOIN reviews r ON rr.review_id = r.id 
        LEFT JOIN colleges c ON r.college_id = c.id
        WHERE rr.status = 'open'
        ORDER BY rr.created_at DESC 
        LIMIT 100
    ");
    $review_reports = $stmt->fetchAll();
} else {
    // Fetch pending Q&A reports
    $stmt = $pdo->query("
        SELECT r.*, u.full_name as reporter_name 
        FROM qa_reports r 
        LEFT JOIN users u ON r.reported_by = u.id 
        WHERE r.moderation_action IS NULL 
        ORDER BY r.created_at DESC 
        LIMIT 100
    ");
    $qa_reports = $stmt->fetchAll();
}

// Stats for the unified inbox
$stat_rev = $pdo->query("SELECT COUNT(*) FROM review_reports WHERE status = 'open'")->fetchColumn();
$stat_qa = $pdo->query("SELECT COUNT(*) FROM qa_reports WHERE moderation_action IS NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Reports | Admin Panel</title>
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
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; overflow-x: auto; }
        .tab-link { padding: 10px 20px; font-weight: 600; color: var(--text-muted); font-size: 0.9rem; text-decoration: none; transition: all 0.2s; border-bottom: 3px solid transparent; display: flex; align-items: center; gap: 8px; white-space: nowrap; flex-shrink: 0; }
        .tab-link:hover { color: var(--primary); }
        .tab-link.active { color: var(--primary); border-bottom-color: var(--primary); }
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); padding: 0; margin-bottom: 24px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; min-width: 600px; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top; }
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; background: #f8fafc; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap; text-transform: capitalize; }
        .btn-action { padding: 6px 10px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; color: var(--text-dark); text-decoration: none; display: inline-block; white-space: nowrap; }
        .btn-action:hover { background: #F8FAFC; }
        .btn-primary { background: var(--primary); color: #fff; border-color: var(--primary); }
        .btn-primary:hover { opacity: 0.9; background: var(--primary); }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: center; }
        .stat-card h3 { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
        .stat-card .val { font-size: 1.8rem; font-weight: 800; color: var(--text-dark); }
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
            .stats-grid { grid-template-columns: 1fr; gap: 12px; }
            .stat-card { padding: 14px; }
            .stat-card .val { font-size: 1.4rem; }
            .filter-bar { gap: 4px; padding-bottom: 8px; }
            .tab-link { padding: 8px 14px; font-size: 0.82rem; }
            th, td { padding: 10px 12px; font-size: 0.82rem; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
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
                    <h2><i class="ph ph-flag" style="color:#0B2447;"></i> User Reports Inbox</h2>
                    <p style="color:var(--text-muted);">Unified moderation queue for user-flagged reviews and community content.</p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Pending Reports</h3>
                    <div class="val" style="color:#0B2447;"><?php echo number_format($stat_rev + $stat_qa); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Reported Reviews</h3>
                    <div class="val"><?php echo number_format($stat_rev); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Reported Q&A Posts</h3>
                    <div class="val"><?php echo number_format($stat_qa); ?></div>
                </div>
            </div>

            <div class="filter-bar" style="margin-bottom:0; border-bottom:none;">
                <a href="?tab=reviews" class="tab-link <?php echo $tab=='reviews'?'active':''; ?>">
                    Review Reports
                    <?php if($stat_rev > 0): ?>
                        <span style="background:#0F172A; color:#fff; border-radius:10px; padding:2px 8px; font-size:0.7rem;"><?php echo $stat_rev; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=qa" class="tab-link <?php echo $tab=='qa'?'active':''; ?>">
                    Community Q&A Reports
                    <?php if($stat_qa > 0): ?>
                        <span style="background:#0F172A; color:#fff; border-radius:10px; padding:2px 8px; font-size:0.7rem;"><?php echo $stat_qa; ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="panel" style="margin-top:20px;">
                <?php if($tab === 'reviews'): ?>
                    <?php if(empty($review_reports)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:40px;">No pending review reports.</p>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reported By</th>
                                    <th>Review Content</th>
                                    <th>Reason / Note</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($review_reports as $r): ?>
                                <tr>
                                    <td style="font-weight:600;"><?php echo htmlspecialchars($r['reporter_name']); ?></td>
                                    <td>
                                        <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;"><?php echo htmlspecialchars($r['college_name']); ?> • ★ <?php echo $r['rating']; ?></div>
                                        <div style="font-size:0.85rem; max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            "<?php echo htmlspecialchars($r['review_text']); ?>"
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($r['report_reason']): ?>
                                            <span class="badge" style="background:rgba(15,23,42,0.06); color:#0B2447;"><?php echo str_replace('_', ' ', $r['report_reason']); ?></span>
                                        <?php endif; ?>
                                        <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;"><?php echo htmlspecialchars($r['report_notes'] ?? ''); ?></div>
                                    </td>
                                    <td style="font-size:0.85rem;"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                                    <td>
                                        <a href="review_moderation.php?id=<?php echo $r['review_id']; ?>" class="btn-action btn-primary"><i class="ph ph-shield-check"></i> Moderate Review</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <?php if(empty($qa_reports)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:40px;">No pending Q&A reports.</p>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reported By</th>
                                    <th>Content Type</th>
                                    <th>Reason / Note</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($qa_reports as $r): ?>
                                <tr>
                                    <td style="font-weight:600;"><?php echo htmlspecialchars($r['reporter_name']); ?></td>
                                    <td>
                                        <?php if($r['question_id']): ?>
                                            <span class="badge" style="background:rgba(11,36,71,0.06); color:#19376D;">Question</span>
                                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">ID: <?php echo substr($r['question_id'], 0, 8); ?>...</div>
                                        <?php else: ?>
                                            <span class="badge" style="background:rgba(11,36,71,0.04); color:#0B2447;">Answer</span>
                                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">ID: <?php echo substr($r['answer_id'], 0, 8); ?>...</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:rgba(15,23,42,0.06); color:#0B2447;"><?php echo str_replace('_', ' ', $r['report_reason']); ?></span>
                                        <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;"><?php echo htmlspecialchars($r['report_notes'] ?? ''); ?></div>
                                    </td>
                                    <td style="font-size:0.85rem;"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                                    <td>
                                        <a href="qa_moderation.php" class="btn-action btn-primary"><i class="ph ph-shield-check"></i> Go to Q&A Queue</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
