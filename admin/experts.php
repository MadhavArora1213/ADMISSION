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
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}
        .sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto;z-index:100;transition:transform 0.3s ease}
        .sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}
        .sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s;text-decoration:none}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}
        .main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}
        .topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}
        .content-area{padding:32px;max-width:1400px;margin:0 auto;width:100%;box-sizing:border-box}
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:12px;flex-wrap:wrap}.page-header h2{font-size:2rem;font-weight:800}
        .sub-links{display:flex;gap:8px;margin-bottom:20px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:thin;flex-wrap:nowrap;border-bottom:1px solid var(--border-color);padding-bottom:12px}
        .sub-links::-webkit-scrollbar{height:5px}.sub-links::-webkit-scrollbar-track{background:#e2e8f0;border-radius:3px}.sub-links::-webkit-scrollbar-thumb{background:var(--primary);border-radius:3px}
        .sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s;white-space:nowrap;flex-shrink:0;display:flex;align-items:center;gap:4px}.sub-link:hover,.sub-link.active{color:var(--primary);background:rgba(0,0,0,.03)}
        .panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px;overflow-x:auto}
        .panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}
        table{width:100%;border-collapse:collapse;font-size:.88rem;min-width:600px}
        th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}
        tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700;white-space:nowrap}
        .form-group{margin-bottom:16px}.form-group label{display:block;font-weight:600;margin-bottom:6px;font-size:.9rem}
        .form-control{width:100%;min-width:0;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-size:.9rem;font-family:inherit;box-sizing:border-box}
        .form-control:focus{outline:none;border-color:var(--primary)}
        .btn{padding:10px 16px;border-radius:8px;font-weight:600;font-size:.9rem;cursor:pointer;border:none;display:inline-flex;align-items:center;justify-content:center;gap:6px;box-sizing:border-box;text-decoration:none;white-space:nowrap}
        .btn-primary{background:var(--primary);color:#fff}.btn-primary:hover{background:#19376D}
        .msg-alert{padding:14px 20px;border-radius:8px;background:rgba(11,36,71,0.04);color:#0B2447;margin-bottom:20px;border:1px solid rgba(11,36,71,0.04)}
        .mobile-menu-btn{display:none;background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-dark);padding:4px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:90}
        .expert-grid{display:grid;grid-template-columns:350px 1fr;gap:24px}

        @media(max-width:1024px){.expert-grid{grid-template-columns:1fr}}
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}
            .sidebar-overlay.show{display:block}.main-content{margin-left:0}
            .topbar{height:56px;padding:0 12px;justify-content:space-between}
            .mobile-menu-btn{display:block}.content-area{padding:12px}
            .page-header{flex-direction:column;align-items:flex-start}.page-header h2{font-size:1.4rem}
            .panel{padding:14px}.panel h3{font-size:1rem}
            .sub-links{gap:4px;margin-bottom:14px}.sub-link{padding:5px 8px;font-size:.78rem}
            .form-group{margin-bottom:12px}.form-group label{font-size:.82rem}.form-control{padding:9px 12px;font-size:.85rem}
            .btn{width:100%;text-align:center}
        }
        @media(max-width:480px){.content-area{padding:8px}.panel{padding:10px}.page-header h2{font-size:1.2rem}}
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

            <div class="expert-grid">
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
                        <a href="experts.php" class="btn" style="display:block; text-align:center; margin-top:10px; background:#F8FAFC; text-decoration:none; color:var(--text-color); padding:10px; border-radius:8px;">Cancel Edit</a>
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
                                        <a href="<?php echo htmlspecialchars($e['linkedin_url']); ?>" target="_blank" style="font-size:0.75rem; color:#19376D; text-decoration:none;"><i class="ph ph-linkedin-logo"></i> LinkedIn</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size:0.8rem; color:var(--primary); font-weight:600;"><?php echo htmlspecialchars($e['expert_designation']); ?></div>
                                        <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($e['expert_college']); ?></div>
                                        <div style="font-size:0.75rem; color:rgba(15,23,42,0.65); margin-top:2px;">Spec: <?php echo htmlspecialchars($e['specialization']); ?></div>
                                    </td>
                                    <td>
                                        <div class="stats-micro">
                                            <span title="Answers Given"><i class="ph ph-chat-text" style="color:#0B2447;"></i> <?php echo number_format($e['answer_count']); ?> ans</span>
                                            <span title="Response Rate"><i class="ph ph-chart-line-up" style="color:#0B2447;"></i> <?php echo number_format($e['response_rate_pct']); ?>%</span>
                                        </div>
                                        <div style="font-size:0.7rem; color:var(--text-muted); margin-top:4px;">Avg Wait: <?php echo number_format($e['avg_response_hours'], 1); ?> hrs</div>
                                    </td>
                                    <td>
                                        <a href="?toggle_verified_id=<?php echo $e['id']; ?>" style="text-decoration:none;">
                                            <?php if($e['verified_badge']): ?><i class="ph-fill ph-seal-check" style="color:#19376D;font-size:1.5rem;" title="Verified"></i>
                                            <?php else: ?><i class="ph ph-seal" style="color:var(--text-muted);font-size:1.5rem;" title="Unverified"></i><?php endif; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="?edit_id=<?php echo $e['id']; ?>" style="color:var(--primary); margin-right:8px;"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?delete_id=<?php echo $e['id']; ?>" onclick="return confirm('Delete expert profile?');" style="color:#0F172A;"><i class="ph ph-trash"></i></a>
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
