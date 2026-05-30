<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = $_POST['id'] ?? null;
    $data = [
        $_POST['link_source_page'], $_POST['link_target_page'], $_POST['anchor_text'], isset($_POST['is_broken']) ? 1 : 0
    ];
    if ($id) {
        $data[] = $id;
        $pdo->prepare("UPDATE internal_links SET link_source_page=?, link_target_page=?, anchor_text=?, is_broken=? WHERE id=?")->execute($data);
    } else {
        $pdo->prepare("INSERT INTO internal_links (link_source_page, link_target_page, anchor_text, is_broken) VALUES (?, ?, ?, ?)")->execute($data);
    }
    header("Location: internal_links.php?msg=saved"); exit;
}

// Handle deleting
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM internal_links WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: internal_links.php?msg=deleted"); exit;
}

// Handle toggle broken
if (isset($_GET['toggle_broken_id'])) {
    $pdo->prepare("UPDATE internal_links SET is_broken = NOT is_broken WHERE id = ?")->execute([$_GET['toggle_broken_id']]);
    header("Location: internal_links.php"); exit;
}

// Fetch all links
$links = $pdo->query("SELECT * FROM internal_links ORDER BY created_at DESC")->fetchAll();
$edit_link = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM internal_links WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_link = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Links | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.sub-links{display:flex;gap:8px;margin-bottom:20px}.sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:rgba(0,0,0,.05);color:var(--primary)}.form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.95rem;box-sizing:border-box}.form-group{margin-bottom:16px}.form-group label{display:block;font-weight:600;margin-bottom:7px;font-size:.9rem;color:var(--text-muted)}.msg-alert{padding:14px 20px;border-radius:8px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;margin-bottom:20px}.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
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
                    <h2><i class="ph ph-link-break" style="color:var(--primary);"></i> Internal Links</h2>
                    <p style="color:var(--text-muted);">Manage explicit internal cross-linking for SEO.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="seo_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="seo_meta.php" class="sub-link"><i class="ph ph-tag"></i> Meta Tags & Schema</a>
                <a href="redirects.php" class="sub-link"><i class="ph ph-arrows-left-right"></i> Redirects</a>
                <a href="sitemaps.php" class="sub-link"><i class="ph ph-map-trifold"></i> Sitemaps</a>
                <a href="internal_links.php" class="sub-link active"><i class="ph ph-link-break"></i> Internal Links</a>
                <a href="seo_templates.php" class="sub-link"><i class="ph ph-file-code"></i> SEO Templates</a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Operation successful.</div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 350px 1fr; gap:24px;">
                <!-- Add/Edit Form -->
                <div class="panel">
                    <h3><?php echo $edit_link ? 'Edit Link' : 'Add New Link'; ?></h3>
                    <form method="POST" action="internal_links.php">
                        <input type="hidden" name="action" value="save">
                        <?php if($edit_link): ?><input type="hidden" name="id" value="<?php echo $edit_link['id']; ?>"><?php endif; ?>

                        <div class="form-group">
                            <label>Source Page URL *</label>
                            <input type="text" name="link_source_page" class="form-control" value="<?php echo htmlspecialchars($edit_link['link_source_page']??''); ?>" required placeholder="/course/btech">
                        </div>
                        <div class="form-group">
                            <label>Target Page URL *</label>
                            <input type="text" name="link_target_page" class="form-control" value="<?php echo htmlspecialchars($edit_link['link_target_page']??''); ?>" required placeholder="/colleges/btech">
                        </div>
                        <div class="form-group">
                            <label>Anchor Text</label>
                            <input type="text" name="anchor_text" class="form-control" value="<?php echo htmlspecialchars($edit_link['anchor_text']??''); ?>" placeholder="Top B.Tech Colleges">
                        </div>
                        
                        <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="is_broken" id="is_broken" <?php echo ($edit_link['is_broken']??0) ? 'checked' : ''; ?>>
                            <label for="is_broken" style="margin:0;">Mark as Broken Link</label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;"><i class="ph ph-floppy-disk"></i> Save Link</button>
                        <?php if($edit_link): ?>
                        <a href="internal_links.php" class="btn" style="display:block; text-align:center; margin-top:10px; background:#f1f5f9; text-decoration:none; color:var(--text-color); padding:10px; border-radius:8px;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- List Panel -->
                <div class="panel">
                    <h3>Registered Internal Links (<?php echo count($links); ?>)</h3>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead><tr><th>Source</th><th>Target</th><th>Anchor</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($links as $l): ?>
                                <tr>
                                    <td style="color:var(--text-muted); font-size:0.8rem; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                        <?php echo htmlspecialchars($l['link_source_page']); ?>
                                    </td>
                                    <td style="font-weight:600; color:var(--primary); max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                        <?php echo htmlspecialchars($l['link_target_page']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($l['anchor_text']); ?></td>
                                    <td>
                                        <a href="?toggle_broken_id=<?php echo $l['id']; ?>" style="text-decoration:none;">
                                            <?php if($l['is_broken']): ?><span class="badge" style="background:#fee2e2;color:#dc2626;">Broken</span>
                                            <?php else: ?><span class="badge" style="background:#dcfce7;color:#166534;">Valid</span><?php endif; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="?edit_id=<?php echo $l['id']; ?>" style="color:var(--primary); margin-right:8px;"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?delete_id=<?php echo $l['id']; ?>" onclick="return confirm('Delete internal link?');" style="color:#dc2626;"><i class="ph ph-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($links)): ?>
                                <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No internal links configured.</td></tr>
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
