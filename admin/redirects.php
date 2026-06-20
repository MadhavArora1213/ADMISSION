<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle adding redirect
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $pdo->prepare("INSERT INTO redirects (redirect_from, redirect_to, redirect_type, redirect_reason) VALUES (?, ?, ?, ?)")
        ->execute([$_POST['redirect_from'], $_POST['redirect_to'], $_POST['redirect_type'], $_POST['redirect_reason']]);
    header("Location: redirects.php?msg=added"); exit;
}
// Handle deleting redirect
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM redirects WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: redirects.php?msg=deleted"); exit;
}

// Handle toggle
if (isset($_GET['toggle_id'])) {
    $pdo->prepare("UPDATE redirects SET is_active = NOT is_active WHERE id = ?")->execute([$_GET['toggle_id']]);
    header("Location: redirects.php"); exit;
}

// Fetch all redirects
$redirects = $pdo->query("SELECT * FROM redirects ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirects Manager | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.sub-links{display:flex;gap:8px;margin-bottom:20px}.sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:rgba(0,0,0,.05);color:var(--primary)}.form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.95rem;box-sizing:border-box}.form-group{margin-bottom:16px}.form-group label{display:block;font-weight:600;margin-bottom:7px;font-size:.9rem;color:var(--text-muted)}.msg-alert{padding:14px 20px;border-radius:8px;background:rgba(11,36,71,0.04);color:#0B2447;border:1px solid rgba(11,36,71,0.04);margin-bottom:20px}
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
                    <h2><i class="ph ph-arrows-left-right" style="color:var(--primary);"></i> Redirects Manager</h2>
                    <p style="color:var(--text-muted);">Manage 301/302 redirects to prevent 404 errors and preserve SEO equity.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="seo_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="seo_meta.php" class="sub-link"><i class="ph ph-tag"></i> Meta Tags & Schema</a>
                <a href="redirects.php" class="sub-link active"><i class="ph ph-arrows-left-right"></i> Redirects</a>
                <a href="sitemaps.php" class="sub-link"><i class="ph ph-map-trifold"></i> Sitemaps</a>
                <a href="internal_links.php" class="sub-link"><i class="ph ph-link-break"></i> Internal Links</a>
                <a href="seo_templates.php" class="sub-link"><i class="ph ph-file-code"></i> SEO Templates</a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Redirect successfully added.</div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 350px 1fr; gap:24px;">
                <!-- Add Form -->
                <div class="panel">
                    <h3>Add New Redirect</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label>From URL *</label>
                            <input type="text" name="redirect_from" class="form-control" placeholder="/old-course-page" required>
                        </div>
                        <div class="form-group">
                            <label>To URL *</label>
                            <input type="text" name="redirect_to" class="form-control" placeholder="/new-course-page" required>
                        </div>
                        <div class="form-group">
                            <label>Redirect Type</label>
                            <select name="redirect_type" class="form-control">
                                <option value="301">301 - Permanent Move</option>
                                <option value="302">302 - Temporary Move</option>
                                <option value="410">410 - Content Deleted</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reason / Notes</label>
                            <input type="text" name="redirect_reason" class="form-control" placeholder="Course renamed">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;"><i class="ph ph-plus"></i> Create Redirect</button>
                    </form>
                </div>

                <!-- List Panel -->
                <div class="panel">
                    <h3>Active Redirects (<?php echo count($redirects); ?>)</h3>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead><tr><th>From</th><th>To</th><th>Type</th><th>Hits</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($redirects as $r): ?>
                                <tr>
                                    <td style="font-weight:600; color:#0F172A;"><?php echo htmlspecialchars($r['redirect_from']); ?></td>
                                    <td style="font-weight:600; color:#0B2447;"><i class="ph ph-arrow-right"></i> <?php echo htmlspecialchars($r['redirect_to']); ?></td>
                                    <td><span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65);"><?php echo $r['redirect_type']; ?></span></td>
                                    <td><?php echo number_format($r['hits']); ?></td>
                                    <td>
                                        <a href="?toggle_id=<?php echo $r['id']; ?>" style="text-decoration:none;">
                                        <?php if($r['is_active']): ?><i class="ph-fill ph-check-circle" style="color:#0B2447;font-size:1.2rem;"></i>
                                        <?php else: ?><i class="ph-fill ph-minus-circle" style="color:#0F172A;font-size:1.2rem;"></i><?php endif; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="?delete_id=<?php echo $r['id']; ?>" onclick="return confirm('Delete redirect?');" style="color:#0F172A;"><i class="ph ph-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($redirects)): ?>
                                <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No redirects configured.</td></tr>
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
