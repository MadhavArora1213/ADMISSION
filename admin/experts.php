<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = $_POST['id'] ?? null;
    $data = [
        $_POST['expert_name'], $_POST['expert_designation'], $_POST['expert_college'],
        $_POST['specialization'], isset($_POST['verified_badge']) ? 1 : 0,
        $_POST['profile_url'], $_POST['linkedin_url']
    ];
    if ($id) {
        $data[] = $id;
        $pdo->prepare("UPDATE experts SET expert_name=?, expert_designation=?, expert_college=?, specialization=?, verified_badge=?, profile_url=?, linkedin_url=? WHERE id=?")->execute($data);
    } else {
        $pdo->prepare("INSERT INTO experts (expert_name, expert_designation, expert_college, specialization, verified_badge, profile_url, linkedin_url, answer_count, response_rate_pct, avg_response_hours) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 100, 0)")->execute($data);
    }
    header("Location: experts.php?msg=saved"); exit;
}

// Handle deleting
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM experts WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: experts.php?msg=deleted"); exit;
}

// Handle toggle verified
if (isset($_GET['toggle_verified_id'])) {
    $pdo->prepare("UPDATE experts SET verified_badge = NOT verified_badge WHERE id = ?")->execute([$_GET['toggle_verified_id']]);
    header("Location: experts.php"); exit;
}

// Fetch all experts
$experts = $pdo->query("SELECT * FROM experts ORDER BY created_at DESC")->fetchAll();
$edit_e = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM experts WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_e = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experts | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.sub-links{display:flex;gap:8px;margin-bottom:20px}.sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:rgba(0,0,0,.05);color:var(--primary)}.form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.95rem;box-sizing:border-box}.form-group{margin-bottom:16px}.form-group label{display:block;font-weight:600;margin-bottom:7px;font-size:.9rem;color:var(--text-muted)}.msg-alert{padding:14px 20px;border-radius:8px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;margin-bottom:20px}
        .stats-micro {display:flex; gap:8px; font-size:0.75rem; color:#64748b; margin-top:4px;}
        .stats-micro span {display:flex; align-items:center; gap:3px;}
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
                    <h2><i class="ph ph-user-check" style="color:var(--primary);"></i> Experts & Alumni</h2>
                    <p style="color:var(--text-muted);">Manage trusted voices in your Q&A community.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="community_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="questions_manager.php" class="sub-link"><i class="ph ph-question"></i> Questions</a>
                <a href="answers_manager.php" class="sub-link"><i class="ph ph-chat-text"></i> Answers</a>
                <a href="experts.php" class="sub-link active"><i class="ph ph-user-check"></i> Experts</a>
                <a href="qa_moderation.php" class="sub-link"><i class="ph ph-shield-warning"></i> Moderation</a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Operation successful.</div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 350px 1fr; gap:24px;">
                <!-- Add/Edit Form -->
                <div class="panel">
                    <h3><?php echo $edit_e ? 'Edit Expert' : 'Add New Expert'; ?></h3>
                    <form method="POST" action="experts.php">
                        <input type="hidden" name="action" value="save">
                        <?php if($edit_e): ?><input type="hidden" name="id" value="<?php echo $edit_e['id']; ?>"><?php endif; ?>

                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="expert_name" class="form-control" value="<?php echo htmlspecialchars($edit_e['expert_name']??''); ?>" required placeholder="Dr. Rakesh Kumar">
                        </div>
                        
                        <div class="form-group">
                            <label>Designation</label>
                            <input type="text" name="expert_designation" class="form-control" value="<?php echo htmlspecialchars($edit_e['expert_designation']??''); ?>" placeholder="Professor of Computer Science">
                        </div>

                        <div class="form-group">
                            <label>Associated College</label>
                            <input type="text" name="expert_college" class="form-control" value="<?php echo htmlspecialchars($edit_e['expert_college']??''); ?>" placeholder="IIT Delhi">
                        </div>

                        <div class="form-group">
                            <label>Specialization</label>
                            <input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($edit_e['specialization']??''); ?>" placeholder="AI, Machine Learning">
                        </div>

                        <div class="form-group">
                            <label>Profile URL</label>
                            <input type="url" name="profile_url" class="form-control" value="<?php echo htmlspecialchars($edit_e['profile_url']??''); ?>" placeholder="https://domain.com/expert/rakesh">
                        </div>

                        <div class="form-group">
                            <label>LinkedIn URL</label>
                            <input type="url" name="linkedin_url" class="form-control" value="<?php echo htmlspecialchars($edit_e['linkedin_url']??''); ?>" placeholder="https://linkedin.com/in/rakesh">
                        </div>

                        <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="verified_badge" id="verified_badge" <?php echo ($edit_e['verified_badge']??0) ? 'checked' : ''; ?>>
                            <label for="verified_badge" style="margin:0; font-weight:700; color:var(--primary);">Display Verified Badge</label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;"><i class="ph ph-floppy-disk"></i> Save Expert</button>
                        <?php if($edit_e): ?>
                        <a href="experts.php" class="btn" style="display:block; text-align:center; margin-top:10px; background:#f1f5f9; text-decoration:none; color:var(--text-color); padding:10px; border-radius:8px;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- List Panel -->
                <div class="panel">
                    <h3>Registered Experts (<?php echo count($experts); ?>)</h3>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead><tr><th>Expert Name</th><th>Credentials</th><th>Performance Stats</th><th>Badge</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($experts as $e): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600; color:var(--text-color);">
                                            <?php echo htmlspecialchars($e['expert_name']); ?>
                                        </div>
                                        <?php if($e['linkedin_url']): ?>
                                        <a href="<?php echo htmlspecialchars($e['linkedin_url']); ?>" target="_blank" style="font-size:0.75rem; color:#0284c7; text-decoration:none;"><i class="ph ph-linkedin-logo"></i> LinkedIn</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size:0.8rem; color:var(--primary); font-weight:600;"><?php echo htmlspecialchars($e['expert_designation']); ?></div>
                                        <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($e['expert_college']); ?></div>
                                        <div style="font-size:0.75rem; color:#475569; margin-top:2px;">Spec: <?php echo htmlspecialchars($e['specialization']); ?></div>
                                    </td>
                                    <td>
                                        <div class="stats-micro">
                                            <span title="Answers Given"><i class="ph ph-chat-text" style="color:#16a34a;"></i> <?php echo number_format($e['answer_count']); ?> ans</span>
                                            <span title="Response Rate"><i class="ph ph-chart-line-up" style="color:#7c3aed;"></i> <?php echo number_format($e['response_rate_pct']); ?>%</span>
                                        </div>
                                        <div style="font-size:0.7rem; color:var(--text-muted); margin-top:4px;">Avg Wait: <?php echo number_format($e['avg_response_hours'], 1); ?> hrs</div>
                                    </td>
                                    <td>
                                        <a href="?toggle_verified_id=<?php echo $e['id']; ?>" style="text-decoration:none;">
                                            <?php if($e['verified_badge']): ?><i class="ph-fill ph-seal-check" style="color:#0ea5e9;font-size:1.5rem;" title="Verified"></i>
                                            <?php else: ?><i class="ph ph-seal" style="color:var(--text-muted);font-size:1.5rem;" title="Unverified"></i><?php endif; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="?edit_id=<?php echo $e['id']; ?>" style="color:var(--primary); margin-right:8px;"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?delete_id=<?php echo $e['id']; ?>" onclick="return confirm('Delete expert profile?');" style="color:#dc2626;"><i class="ph ph-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($experts)): ?>
                                <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No experts registered.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
