<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = $_POST['id'] ?? null;
    $data = [
        $_POST['sitemap_name'], $_POST['sitemap_url'], $_POST['sitemap_type']
    ];
    if ($id) {
        $data[] = $id;
        $pdo->prepare("UPDATE sitemaps SET sitemap_name=?, sitemap_url=?, sitemap_type=? WHERE id=?")->execute($data);
    } else {
        $pdo->prepare("INSERT INTO sitemaps (sitemap_name, sitemap_url, sitemap_type, url_count) VALUES (?, ?, ?, 0)")->execute($data);
    }
    header("Location: sitemaps.php?msg=saved"); exit;
}

// Handle deleting
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM sitemaps WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: sitemaps.php?msg=deleted"); exit;
}

// Handle trigger generate
if (isset($_GET['generate_id'])) {
    // In a real app this would trigger a background job to generate XML.
    $pdo->prepare("UPDATE sitemaps SET last_generated_at=CURRENT_TIMESTAMP, url_count = FLOOR(RAND() * 500) + 10 WHERE id=?")->execute([$_GET['generate_id']]);
    header("Location: sitemaps.php?msg=generated"); exit;
}

// Fetch all sitemaps
$sitemaps = $pdo->query("SELECT * FROM sitemaps ORDER BY created_at DESC")->fetchAll();
$edit_sm = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM sitemaps WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_sm = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitemaps | Admin</title>
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
                    <h2><i class="ph ph-map-trifold" style="color:var(--primary);"></i> Sitemaps</h2>
                    <p style="color:var(--text-muted);">Manage and generate XML sitemaps for search engines.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="seo_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="seo_meta.php" class="sub-link"><i class="ph ph-tag"></i> Meta Tags & Schema</a>
                <a href="redirects.php" class="sub-link"><i class="ph ph-arrows-left-right"></i> Redirects</a>
                <a href="sitemaps.php" class="sub-link active"><i class="ph ph-map-trifold"></i> Sitemaps</a>
                <a href="internal_links.php" class="sub-link"><i class="ph ph-link-break"></i> Internal Links</a>
                <a href="seo_templates.php" class="sub-link"><i class="ph ph-file-code"></i> SEO Templates</a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Operation successful.</div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 350px 1fr; gap:24px;">
                <!-- Add/Edit Form -->
                <div class="panel">
                    <h3><?php echo $edit_sm ? 'Edit Sitemap' : 'Add New Sitemap'; ?></h3>
                    <form method="POST" action="sitemaps.php">
                        <input type="hidden" name="action" value="save">
                        <?php if($edit_sm): ?><input type="hidden" name="id" value="<?php echo $edit_sm['id']; ?>"><?php endif; ?>

                        <div class="form-group">
                            <label>Sitemap Name *</label>
                            <input type="text" name="sitemap_name" class="form-control" value="<?php echo htmlspecialchars($edit_sm['sitemap_name']??''); ?>" required placeholder="Colleges Sitemap">
                        </div>
                        <div class="form-group">
                            <label>Sitemap URL *</label>
                            <input type="url" name="sitemap_url" class="form-control" value="<?php echo htmlspecialchars($edit_sm['sitemap_url']??''); ?>" required placeholder="https://domain.com/sitemap_colleges.xml">
                        </div>
                        <div class="form-group">
                            <label>Sitemap Type</label>
                            <select name="sitemap_type" class="form-control">
                                <?php foreach(['colleges','exams','courses','articles','tools'] as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo ($edit_sm['sitemap_type']??'') == $opt ? 'selected' : ''; ?>><?php echo ucfirst($opt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;"><i class="ph ph-floppy-disk"></i> Save Sitemap</button>
                        <?php if($edit_sm): ?>
                        <a href="sitemaps.php" class="btn" style="display:block; text-align:center; margin-top:10px; background:#F8FAFC; text-decoration:none; color:var(--text-color); padding:10px; border-radius:8px;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- List Panel -->
                <div class="panel">
                    <h3>Registered Sitemaps (<?php echo count($sitemaps); ?>)</h3>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead><tr><th>Name / URL</th><th>Type</th><th>URLs</th><th>Last Generated</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($sitemaps as $sm): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($sm['sitemap_name']); ?></strong><br>
                                        <a href="<?php echo htmlspecialchars($sm['sitemap_url']); ?>" target="_blank" style="color:var(--text-muted); font-size:0.75rem; text-decoration:none;"><?php echo htmlspecialchars($sm['sitemap_url']); ?></a>
                                    </td>
                                    <td><span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65);"><?php echo ucfirst($sm['sitemap_type']); ?></span></td>
                                    <td style="font-weight:700; color:var(--primary);"><?php echo number_format($sm['url_count']); ?></td>
                                    <td style="font-size:0.8rem; color:var(--text-muted);">
                                        <?php echo $sm['last_generated_at'] ? date('M d, Y H:i', strtotime($sm['last_generated_at'])) : 'Never'; ?>
                                    </td>
                                    <td>
                                        <a href="?generate_id=<?php echo $sm['id']; ?>" title="Generate Now" style="color:#0B2447; margin-right:8px;"><i class="ph ph-arrows-clockwise"></i></a>
                                        <a href="?edit_id=<?php echo $sm['id']; ?>" style="color:var(--primary); margin-right:8px;"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?delete_id=<?php echo $sm['id']; ?>" onclick="return confirm('Delete sitemap entry?');" style="color:#0F172A;"><i class="ph ph-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($sitemaps)): ?>
                                <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No sitemaps configured.</td></tr>
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
