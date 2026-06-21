<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) { header('Location: reviews.php'); exit; }

// Handle Moderation Action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'moderate') {
    $status = $_POST['moderation_status'];
    $reason = $_POST['moderation_reason'];
    
    $stmt = $pdo->prepare("UPDATE reviews SET moderation_status = ?, moderation_reason = ?, moderated_by = ?, moderated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$status, $reason, $_SESSION['admin_id'], $id]);
    
    header("Location: review_moderation.php?id=$id&msg=saved");
    exit;
}

// Fetch Review Data
$stmt = $pdo->prepare("SELECT r.*, u.full_name as user_name, u.email as user_email, c.name as college_name, m.full_name as moderator_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id LEFT JOIN colleges c ON r.college_id = c.id LEFT JOIN users m ON r.moderated_by = m.id WHERE r.id = ?");
$stmt->execute([$id]);
$review = $stmt->fetch();
if (!$review) { header('Location: reviews.php'); exit; }

// Fetch Meta
$stmtMeta = $pdo->prepare("SELECT * FROM review_meta WHERE review_id = ?");
$stmtMeta->execute([$id]);
$meta = $stmtMeta->fetch();

// Fetch Reports
$stmtRep = $pdo->prepare("SELECT rr.*, u.full_name as reporter_name FROM review_reports rr LEFT JOIN users u ON rr.reported_by = u.id WHERE rr.review_id = ? ORDER BY rr.created_at DESC");
$stmtRep->execute([$id]);
$reports = $stmtRep->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderate Review | AdmissionSeason Admin</title>
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
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; padding-bottom: 60px;}
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 1200px; margin: 0 auto; width: 100%; display: grid; grid-template-columns: 1fr 350px; gap: 32px; align-items: start;}
        .page-header { grid-column: 1 / -1; display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); margin-bottom: 24px;}
        .panel h3 { font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 24px; display: flex; align-items: center; gap: 8px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); }
        .msg-alert { grid-column: 1 / -1; padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 16px; border: 1px solid rgba(11,36,71,0.04); }
        
        .detail-row { display: flex; border-bottom: 1px solid var(--border-color); padding: 12px 0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { width: 40%; font-weight: 600; color: var(--text-muted); font-size: 0.9rem;}
        .detail-value { width: 60%; color: var(--text-dark); font-weight: 500;}
        
        .review-text { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); margin-top: 16px; font-size: 0.95rem; line-height: 1.6;}
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; }
        
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background: rgba(11,36,71,0.06); color: #0F172A; }
        .status-approved { background: rgba(11,36,71,0.04); color: #0B2447; }
        .status-rejected { background: rgba(15,23,42,0.06); color: #0B2447; }
        .status-escalated { background: rgba(11,36,71,0.04); color: #0B2447; }
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
                    <a href="reviews.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a>
                    <h2>Review Moderation</h2>
                </div>
                
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> Moderation status updated successfully.</div>
                <?php endif; ?>

                <!-- Left Column -->
                <div>
                    <div class="panel">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">
                            <div>
                                <h3 style="border:none; margin:0; padding:0;"><?php echo htmlspecialchars($review['review_title'] ?: 'Untitled Review'); ?></h3>
                                <div style="color:var(--text-muted); margin-top:4px;">For <strong style="color:var(--primary);"><?php echo htmlspecialchars($review['college_name']); ?></strong></div>
                            </div>
                            <span class="badge status-<?php echo $review['moderation_status']; ?>"><?php echo ucfirst($review['moderation_status']); ?></span>
                        </div>
                        
                        <div style="display:flex; gap:16px; margin-bottom: 24px; background:#F8FAFC; padding:16px; border-radius:8px;">
                            <div style="flex:1;">
                                <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Overall Rating</div>
                                <div style="font-size:1.5rem; font-weight:800; color:var(--primary); display:flex; align-items:center; gap:4px;"><i class="ph-fill ph-star" style="color:#19376D;"></i> <?php echo $review['overall_rating']; ?>/5</div>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Author</div>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($review['user_name']); ?></div>
                                <div style="font-size:0.8rem;"><?php echo htmlspecialchars($review['user_email']); ?></div>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Batch</div>
                                <div style="font-weight:600;"><?php echo $review['batch_year'] ?: 'N/A'; ?></div>
                            </div>
                        </div>

                        <div class="review-text">
                            <h4 style="margin-top:0; font-size:1rem; color:var(--primary);">Review Content</h4>
                            <?php echo nl2br(htmlspecialchars($review['review_body'])); ?>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:16px;">
                            <div class="review-text" style="margin-top:0; border-left:4px solid #19376D;">
                                <h4 style="margin-top:0; font-size:0.9rem; color:#0B2447; display:flex; align-items:center; gap:4px;"><i class="ph ph-thumbs-up"></i> Pros</h4>
                                <?php echo nl2br(htmlspecialchars($review['pros'] ?: 'None provided')); ?>
                            </div>
                            <div class="review-text" style="margin-top:0; border-left:4px solid #0F172A;">
                                <h4 style="margin-top:0; font-size:0.9rem; color:#0B2447; display:flex; align-items:center; gap:4px;"><i class="ph ph-thumbs-down"></i> Cons</h4>
                                <?php echo nl2br(htmlspecialchars($review['cons'] ?: 'None provided')); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="panel">
                        <h3><i class="ph ph-chart-bar"></i> Detailed Ratings</h3>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:x-32px;">
                            <?php
                            $ratings = [
                                'Academics' => 'academics_rating',
                                'Faculty' => 'faculty_rating',
                                'Placements' => 'placements_rating',
                                'Infrastructure' => 'infrastructure_rating',
                                'Hostel' => 'hostel_rating',
                                'Social Life' => 'social_life_rating',
                                'Food' => 'food_rating'
                            ];
                            foreach($ratings as $label => $key):
                                $val = $review[$key] ?: '-';
                            ?>
                            <div class="detail-row">
                                <div class="detail-label"><?php echo $label; ?></div>
                                <div class="detail-value" style="display:flex; align-items:center; gap:4px;">
                                    <?php if($val != '-'): ?><i class="ph-fill ph-star" style="color:#19376D;"></i><?php endif; ?> <?php echo $val; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if(!empty($reports)): ?>
                    <div class="panel" style="border-color:rgba(15,23,42,0.06);">
                        <h3 style="color:#0B2447;"><i class="ph ph-flag"></i> User Reports (<?php echo count($reports); ?>)</h3>
                        <?php foreach($reports as $rep): ?>
                        <div style="padding:12px; background:rgba(15,23,42,0.04); border:1px solid rgba(15,23,42,0.06); border-radius:8px; margin-bottom:8px;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                <strong style="color:#0B2447;"><?php echo ucfirst($rep['reason']); ?></strong>
                                <span class="badge" style="background:#fff; color:#0B2447; border:1px solid rgba(15,23,42,0.06);"><?php echo ucfirst($rep['status']); ?></span>
                            </div>
                            <div style="font-size:0.9rem; margin-bottom:8px;"><?php echo htmlspecialchars($rep['description']); ?></div>
                            <div style="font-size:0.75rem; color:#0B2447;">Reported by <?php echo htmlspecialchars($rep['reporter_name']); ?> on <?php echo date('M d, Y', strtotime($rep['created_at'])); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column -->
                <div>
                    <div class="panel">
                        <h3><i class="ph ph-shield-check"></i> Moderation Action</h3>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="moderate">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="moderation_status" class="form-control">
                                    <option value="pending" <?php echo $review['moderation_status']=='pending'?'selected':''; ?>>Pending</option>
                                    <option value="approved" <?php echo $review['moderation_status']=='approved'?'selected':''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $review['moderation_status']=='rejected'?'selected':''; ?>>Rejected</option>
                                    <option value="escalated" <?php echo $review['moderation_status']=='escalated'?'selected':''; ?>>Escalated</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Moderation Reason / Notes (Internal)</label>
                                <textarea name="moderation_reason" class="form-control" rows="3"><?php echo htmlspecialchars($review['moderation_reason']); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%;">Save Moderation</button>
                        </form>
                        
                        <?php if($review['moderated_by']): ?>
                        <div style="margin-top:16px; font-size:0.8rem; color:var(--text-muted); border-top:1px solid var(--border-color); padding-top:16px;">
                            Last moderated by <strong><?php echo htmlspecialchars($review['moderator_name'] ?: 'Admin'); ?></strong> on <?php echo date('M d, Y H:i', strtotime($review['moderated_at'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="panel">
                        <h3><i class="ph ph-robot"></i> AI & Fraud Meta</h3>
                        <div class="detail-row"><div class="detail-label">Spam Score</div><div class="detail-value"><?php echo $review['ai_spam_score']; ?></div></div>
                        <div class="detail-row"><div class="detail-label">Sentiment</div><div class="detail-value"><?php echo ucfirst($review['ai_sentiment'] ?: 'N/A'); ?></div></div>
                        <div class="detail-row"><div class="detail-label">Fraud Flag</div><div class="detail-value"><?php echo $review['fraud_flag'] ? '<span style="color:#0F172A; font-weight:700;">Yes</span>' : 'No'; ?></div></div>
                        <div class="detail-row"><div class="detail-label">Duplicate Score</div><div class="detail-value"><?php echo $review['duplicate_score']; ?></div></div>
                        
                        <?php if($meta): ?>
                        <h4 style="margin-top:24px; margin-bottom:8px; font-size:0.9rem; text-transform:uppercase; color:var(--text-muted);">Device Info</h4>
                        <div class="detail-row"><div class="detail-label">IP Address</div><div class="detail-value"><?php echo htmlspecialchars($meta['ip_address'] ?: 'N/A'); ?></div></div>
                        <div class="detail-row"><div class="detail-label">VPN Detected</div><div class="detail-value"><?php echo $meta['vpn_detected'] ? 'Yes' : 'No'; ?></div></div>
                        <div class="detail-row"><div class="detail-label">Velocity Flag</div><div class="detail-value"><?php echo $meta['velocity_flag'] ? 'Yes (>3/day)' : 'No'; ?></div></div>
                        <div class="detail-row"><div class="detail-label">Geo Country</div><div class="detail-value"><?php echo htmlspecialchars($meta['geo_country'] ?: 'N/A'); ?></div></div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
