<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Fetch quick stats
$totalQuestions = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$totalAnswers = $pdo->query("SELECT COUNT(*) FROM answers")->fetchColumn();
$activeExperts = $pdo->query("SELECT COUNT(*) FROM experts WHERE verified_badge = 1")->fetchColumn();
$pendingReports = $pdo->query("SELECT COUNT(*) FROM qa_reports WHERE moderation_action IS NULL")->fetchColumn();

// Recent questions
$recentQuestions = $pdo->query("SELECT * FROM questions ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent reports
$recentReports = $pdo->query("SELECT * FROM qa_reports WHERE moderation_action IS NULL ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Dashboard | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}.stat-card{background:#fff;border-radius:12px;border:1px solid var(--border-color);padding:20px;box-shadow:var(--shadow-sm)}.stat-card .num{font-size:2rem;font-weight:800;color:var(--primary)}.stat-card .label{font-size:.8rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-top:4px}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.sub-links{display:flex;gap:8px;margin-bottom:20px}.sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:rgba(0,0,0,.05);color:var(--primary)}
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
                    <h2><i class="ph ph-users" style="color:var(--primary);"></i> Community Dashboard</h2>
                    <p style="color:var(--text-muted);">Monitor Q&A activity and manage experts and moderation queues.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="community_dashboard.php" class="sub-link active"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="questions_manager.php" class="sub-link"><i class="ph ph-question"></i> Questions</a>
                <a href="answers_manager.php" class="sub-link"><i class="ph ph-chat-text"></i> Answers</a>
                <a href="experts.php" class="sub-link"><i class="ph ph-user-check"></i> Experts</a>
                <a href="qa_moderation.php" class="sub-link"><i class="ph ph-shield-warning"></i> Moderation</a>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="num"><?php echo number_format($totalQuestions); ?></div><div class="label">Total Questions</div></div>
                <div class="stat-card"><div class="num" style="color:#0B2447;"><?php echo number_format($totalAnswers); ?></div><div class="label">Total Answers</div></div>
                <div class="stat-card"><div class="num" style="color:#19376D;"><?php echo number_format($activeExperts); ?></div><div class="label">Verified Experts</div></div>
                <div class="stat-card"><div class="num" style="color:#0F172A;"><?php echo number_format($pendingReports); ?></div><div class="label">Pending Reports</div></div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
                <div class="panel">
                    <h3><i class="ph ph-question"></i> Recent Questions</h3>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead><tr><th>Question</th><th>Category</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach($recentQuestions as $q): ?>
                                <tr>
                                    <td style="max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <strong><?php echo htmlspecialchars($q['question_text']); ?></strong>
                                    </td>
                                    <td><span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65);"><?php echo ucfirst($q['question_category']); ?></span></td>
                                    <td>
                                        <?php if($q['status'] == 'open'): ?><span class="badge" style="background:rgba(11,36,71,0.04);color:#0B2447;">Open</span>
                                        <?php elseif($q['status'] == 'answered'): ?><span class="badge" style="background:rgba(11,36,71,0.06);color:#19376D;">Answered</span>
                                        <?php else: ?><span class="badge" style="background:rgba(15,23,42,0.06);color:#0F172A;">Closed</span><?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($recentQuestions)): ?>
                                <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">No questions yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div style="text-align:right; margin-top:16px;">
                            <a href="questions_manager.php" style="color:var(--primary); font-size:0.85rem; font-weight:600; text-decoration:none;">View All Questions &rarr;</a>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <h3><i class="ph ph-shield-warning"></i> Moderation Queue</h3>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead><tr><th>Reason</th><th>Reported Target</th><th>Time</th></tr></thead>
                            <tbody>
                                <?php foreach($recentReports as $r): ?>
                                <tr>
                                    <td><span class="badge" style="background:rgba(15,23,42,0.06);color:#0F172A;"><?php echo ucfirst($r['report_reason']); ?></span></td>
                                    <td style="font-family:monospace; font-size:0.8rem; color:rgba(15,23,42,0.45);">
                                        <?php if($r['question_id']) echo "Q: " . substr($r['question_id'], 0, 8); ?>
                                        <?php if($r['answer_id']) echo "A: " . substr($r['answer_id'], 0, 8); ?>
                                    </td>
                                    <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo date('d M, H:i', strtotime($r['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($recentReports)): ?>
                                <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">Queue is clear!</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div style="text-align:right; margin-top:16px;">
                            <a href="qa_moderation.php" style="color:var(--primary); font-size:0.85rem; font-weight:600; text-decoration:none;">Go to Moderation &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
