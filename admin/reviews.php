<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Fetch Reviews
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$query = "SELECT r.*, u.full_name as user_name, c.name as college_name, (SELECT COUNT(*) FROM review_reports rr WHERE rr.review_id = r.id AND rr.status = 'open') as open_reports FROM reviews r LEFT JOIN users u ON r.user_id = u.id LEFT JOIN colleges c ON r.college_id = c.id";

if ($status_filter !== 'all') {
    $query .= " WHERE r.moderation_status = :status";
}
$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
if ($status_filter !== 'all') {
    $stmt->execute(['status' => $status_filter]);
} else {
    $stmt->execute();
}
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews | AdmissionSeason Admin</title>
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
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); }
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; text-decoration: none; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; }
        tr:hover { background-color: rgba(0,0,0,0.02); }
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background: #fef08a; color: #854d0e; }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-escalated { background: #ffedd5; color: #c2410c; }
        .action-links { display: flex; gap: 8px; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--text-dark); border: 1px solid var(--border-color); }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .msg-alert { padding: 16px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 24px; border: 1px solid #bbf7d0; }
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
                        <h2>Manage Reviews</h2>
                        <p style="color: var(--text-muted);">Moderate and manage user submitted college reviews.</p>
                    </div>
                </div>
                
                <div class="tabs-nav">
                    <a href="?status=all" class="tab-link <?php echo $status_filter=='all'?'active':''; ?>">All Reviews</a>
                    <a href="?status=pending" class="tab-link <?php echo $status_filter=='pending'?'active':''; ?>">Pending</a>
                    <a href="?status=approved" class="tab-link <?php echo $status_filter=='approved'?'active':''; ?>">Approved</a>
                    <a href="?status=rejected" class="tab-link <?php echo $status_filter=='rejected'?'active':''; ?>">Rejected</a>
                    <a href="?status=escalated" class="tab-link <?php echo $status_filter=='escalated'?'active':''; ?>">Escalated</a>
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
                                            <div style="font-weight: 600; color: var(--primary);"><?php echo htmlspecialchars($r['college_name']); ?></div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);">by <?php echo htmlspecialchars($r['user_name']); ?> <?php if($r['is_verified_alumnus']) echo '<i class="ph-fill ph-seal-check" style="color:#0ea5e9;" title="Verified Alumnus"></i>'; ?></div>
                                        </td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:4px; font-weight:700;">
                                                <i class="ph-fill ph-star" style="color:#fbbf24;"></i> <?php echo $r['overall_rating']; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge status-<?php echo $r['moderation_status']; ?>"><?php echo ucfirst($r['moderation_status']); ?></span>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:4px;">
                                                <?php if($r['fraud_flag']): ?><span class="badge" style="background:#fee2e2; color:#991b1b;" title="Fraud Flag"><i class="ph ph-warning"></i> Fraud</span><?php endif; ?>
                                                <?php if($r['ai_spam_score'] > 0.7): ?><span class="badge" style="background:#ffedd5; color:#c2410c;" title="High Spam Score"><i class="ph ph-robot"></i> Spam</span><?php endif; ?>
                                                <?php if($r['open_reports'] > 0): ?><span class="badge" style="background:#fee2e2; color:#991b1b;" title="Open Reports"><i class="ph ph-flag"></i> <?php echo $r['open_reports']; ?></span><?php endif; ?>
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
</body>
</html>
