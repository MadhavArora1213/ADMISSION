<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle Moderation Action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'moderate') {
    $report_id = $_POST['report_id'];
    $mod_action = $_POST['moderation_action'];
    
    // Update the report
    $pdo->prepare("UPDATE qa_reports SET moderation_action = ? WHERE id = ?")->execute([$mod_action, $report_id]);
    
    // Fetch the report to see what it's attached to
    $report = $pdo->prepare("SELECT * FROM qa_reports WHERE id = ?");
    $report->execute([$report_id]);
    $r = $report->fetch();
    
    // If the action is 'remove', actually delete the content
    if ($mod_action == 'remove') {
        if ($r['question_id']) {
            $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$r['question_id']]);
        } elseif ($r['answer_id']) {
            $pdo->prepare("DELETE FROM answers WHERE id = ?")->execute([$r['answer_id']]);
        }
    }
    
    // Note: 'warn_user' would ideally trigger a notification, keeping it simple for UI demo
    
    header("Location: qa_moderation.php?msg=moderated"); exit;
}

// Fetch Pending Reports
$pending = $pdo->query("SELECT r.*, q.question_text, a.answer_text 
    FROM qa_reports r 
    LEFT JOIN questions q ON r.question_id = q.id 
    LEFT JOIN answers a ON r.answer_id = a.id 
    WHERE r.moderation_action IS NULL 
    ORDER BY r.created_at ASC")->fetchAll();

// Fetch Resolved Reports (Limit 50)
$resolved = $pdo->query("SELECT r.*, q.question_text, a.answer_text 
    FROM qa_reports r 
    LEFT JOIN questions q ON r.question_id = q.id 
    LEFT JOIN answers a ON r.answer_id = a.id 
    WHERE r.moderation_action IS NOT NULL 
    ORDER BY r.updated_at DESC LIMIT 50")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderation Queue | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.sub-links{display:flex;gap:8px;margin-bottom:20px}.sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:rgba(0,0,0,.05);color:var(--primary)}.form-control{padding:8px 12px;border:1px solid var(--border-color);border-radius:6px;font-family:inherit;font-size:.85rem;box-sizing:border-box}.msg-alert{padding:14px 20px;border-radius:8px;background:rgba(11,36,71,0.04);color:#0B2447;border:1px solid rgba(11,36,71,0.04);margin-bottom:20px}
        .content-box {background:#f8fafc; padding:12px; border-radius:8px; font-size:0.85rem; border:1px solid var(--border-color); margin-top:8px;}
        .mod-form {display:flex; gap:8px;}
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" style="margin-left:16px;color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-shield-warning" style="color:var(--primary);"></i> Moderation Queue</h2>
                    <p style="color:var(--text-muted);">Review and act on reported questions and answers.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="community_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="questions_manager.php" class="sub-link"><i class="ph ph-question"></i> Questions</a>
                <a href="answers_manager.php" class="sub-link"><i class="ph ph-chat-text"></i> Answers</a>
                <a href="experts.php" class="sub-link"><i class="ph ph-user-check"></i> Experts</a>
                <a href="qa_moderation.php" class="sub-link active"><i class="ph ph-shield-warning"></i> Moderation</a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Moderation action applied successfully.</div>
            <?php endif; ?>

            <div class="panel">
                <h3><i class="ph ph-clock"></i> Pending Review (<?php echo count($pending); ?>)</h3>
                <div style="overflow-x:auto;">
                    <table>
                        <thead><tr><th>Report Reason</th><th>Reported Content</th><th>Reporter ID</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach($pending as $p): ?>
                            <tr>
                                <td>
                                    <span class="badge" style="background:rgba(15,23,42,0.06);color:#0F172A; font-size:0.8rem;"><i class="ph ph-warning-circle"></i> <?php echo ucfirst($p['report_reason']); ?></span>
                                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;"><?php echo date('d M, H:i', strtotime($p['created_at'])); ?></div>
                                </td>
                                <td style="width:50%;">
                                    <?php if($p['question_id']): ?>
                                    <span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65);">Question</span>
                                    <div class="content-box"><?php echo htmlspecialchars($p['question_text'] ?? '[Content Deleted]'); ?></div>
                                    <?php elseif($p['answer_id']): ?>
                                    <span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65);">Answer</span>
                                    <div class="content-box"><?php echo htmlspecialchars(strip_tags($p['answer_text'] ?? '[Content Deleted]')); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-family:monospace; font-size:0.8rem; color:rgba(15,23,42,0.45);"><?php echo substr($p['reported_by'], 0, 12).'...'; ?></td>
                                <td>
                                    <form method="POST" action="qa_moderation.php" class="mod-form">
                                        <input type="hidden" name="action" value="moderate">
                                        <input type="hidden" name="report_id" value="<?php echo $p['id']; ?>">
                                        <select name="moderation_action" class="form-control" required>
                                            <option value="">-- Select Action --</option>
                                            <option value="reject">Reject Report (Keep Content)</option>
                                            <option value="remove">Remove Content (Delete)</option>
                                            <option value="warn_user">Warn Author & Keep Content</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary" style="padding:8px 12px; font-size:0.85rem;">Apply</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($pending)): ?>
                            <tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:40px 0;"><i class="ph ph-check-circle" style="font-size:2rem; color:#0B2447; margin-bottom:10px; display:block;"></i>All caught up! No pending reports.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel" style="margin-top:24px;">
                <h3 style="color:rgba(15,23,42,0.45);"><i class="ph ph-archive"></i> Recently Resolved</h3>
                <div style="overflow-x:auto;">
                    <table>
                        <thead><tr><th>Report Reason</th><th>Content Snapshot</th><th>Decision</th><th>Resolved At</th></tr></thead>
                        <tbody>
                            <?php foreach($resolved as $r): ?>
                            <tr>
                                <td><span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65);"><?php echo ucfirst($r['report_reason']); ?></span></td>
                                <td style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:0.85rem; color:var(--text-muted);">
                                    <?php 
                                        if($r['question_id']) echo htmlspecialchars($r['question_text'] ?? '[Deleted]');
                                        elseif($r['answer_id']) echo htmlspecialchars(strip_tags($r['answer_text'] ?? '[Deleted]'));
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        $color = 'rgba(15,23,42,0.65)'; $bg = '#F8FAFC';
                                        if($r['moderation_action'] == 'remove') { $color = '#0F172A'; $bg = 'rgba(15,23,42,0.06)'; }
                                        if($r['moderation_action'] == 'reject') { $color = '#0B2447'; $bg = 'rgba(11,36,71,0.04)'; }
                                        if($r['moderation_action'] == 'warn_user') { $color = '#19376D'; $bg = 'rgba(11,36,71,0.06)'; }
                                    ?>
                                    <span class="badge" style="background:<?php echo $bg; ?>;color:<?php echo $color; ?>;"><?php echo ucfirst(str_replace('_', ' ', $r['moderation_action'])); ?></span>
                                </td>
                                <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo date('M d, H:i', strtotime($r['updated_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
