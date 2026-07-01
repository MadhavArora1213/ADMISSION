<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle adding/editing SEO Meta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = $_POST['id'] ?? null;
    $page_id = !empty($_POST['page_id']) ? trim($_POST['page_id']) : sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $data = [
        $_POST['page_type'], $page_id, $_POST['meta_title'], $_POST['meta_description'],
        $_POST['og_image'], $_POST['canonical_url'], $_POST['robots_directive'], $_POST['schema_type'],
        $_POST['schema_json'], $_POST['hreflang'], $_POST['google_index_status']
    ];
    if ($id) {
        $data[] = $id;
        $pdo->prepare("UPDATE seo_meta SET page_type=?, page_id=?, meta_title=?, meta_description=?, og_image=?, canonical_url=?, robots_directive=?, schema_type=?, schema_json=?, hreflang=?, google_index_status=? WHERE id=?")->execute($data);
    } else {
        $pdo->prepare("INSERT INTO seo_meta (page_type, page_id, meta_title, meta_description, og_image, canonical_url, robots_directive, schema_type, schema_json, hreflang, google_index_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute($data);
    }
    header("Location: seo_meta.php?msg=saved"); exit;
}

// Handle deleting
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM seo_meta WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: seo_meta.php?msg=deleted"); exit;
}

// Fetch all seo meta
$meta_tags = $pdo->query("SELECT * FROM seo_meta ORDER BY last_crawled_at DESC")->fetchAll();
$edit_meta = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM seo_meta WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_meta = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meta Tags & Schema | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light);margin:0}
        .admin-layout{display:flex;min-height:100vh}
        .sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto;z-index:100;transition:transform .3s ease}
        .sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}
        .sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}
        .sidebar-nav{padding:24px 0;flex:1}
        .sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s;text-decoration:none}
        .sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}
        .main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;min-width:0;padding-bottom:60px}
        .topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}
        .content-area{padding:32px}
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:12px;flex-wrap:wrap}
        .page-header h2{font-size:2rem;font-weight:800}
        .panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}
        .panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}
        .panel-header{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        .panel-body{margin-top:0;overflow-x:auto;-webkit-overflow-scrolling:touch}
        .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .form-layout{display:grid;grid-template-columns:380px 1fr;gap:24px}
        table{width:100%;border-collapse:collapse;font-size:.88rem}
        th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}
        th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}
        tr:hover{background:rgba(0,0,0,.015)}
        .badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700;display:inline-block;white-space:nowrap}
        .sub-links{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
        .sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}
        .sub-link:hover,.sub-link.active{background:var(--primary);color:#fff}
        .btn{padding:10px 20px;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;border:none;display:inline-flex;align-items:center;gap:6px;text-decoration:none;white-space:nowrap;box-sizing:border-box}
        .btn-primary{background:var(--primary);color:#fff}
        .btn-primary:hover{opacity:.9}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-weight:600;margin-bottom:6px;font-size:.85rem;color:var(--text-muted)}
        .form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.9rem;box-sizing:border-box}
        .msg-alert{padding:14px 20px;border-radius:8px;background:rgba(11,36,71,0.04);color:#0B2447;border:1px solid rgba(11,36,71,0.04);margin-bottom:20px}

        .mobile-menu-btn{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#0f172a;padding:4px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:90}

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .sidebar-overlay.show{display:block}
            .main-content{margin-left:0}
            .mobile-menu-btn{display:block}
            .topbar{height:auto;min-height:56px;padding:10px 12px;justify-content:space-between}
            .content-area{padding:12px}
            .page-header{flex-direction:column;align-items:flex-start}
            .page-header h2{font-size:1.3rem}
            .form-layout{grid-template-columns:1fr;gap:16px}
            .panel{padding:14px;border-radius:12px;overflow:hidden}
            .panel h3{font-size:1rem}
            .grid-2{grid-template-columns:1fr}
            th,td{padding:8px 10px;font-size:.8rem}
        }
        @media(max-width:480px){
            .content-area{padding:8px}
            .page-header h2{font-size:1.1rem}
            .panel{padding:12px}
            th,td{padding:6px 8px;font-size:.75rem}
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
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" style="margin-left:16px;color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-tag" style="color:var(--primary);"></i> Meta Tags & Schema</h2>
                    <p style="color:var(--text-muted);">Manage SEO attributes for specific pages.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="seo_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="seo_meta.php" class="sub-link active"><i class="ph ph-tag"></i> Meta Tags & Schema</a>
                <a href="redirects.php" class="sub-link"><i class="ph ph-arrows-left-right"></i> Redirects</a>
                <a href="sitemaps.php" class="sub-link"><i class="ph ph-map-trifold"></i> Sitemaps</a>
                <a href="internal_links.php" class="sub-link"><i class="ph ph-link-break"></i> Internal Links</a>
                <a href="seo_templates.php" class="sub-link"><i class="ph ph-file-code"></i> SEO Templates</a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Operation successful.</div>
            <?php endif; ?>

            <div class="form-layout">
                <!-- Add/Edit Form -->
                <div class="panel">
                    <h3><?php echo $edit_meta ? 'Edit Meta Tag' : 'Add New Meta Tag'; ?></h3>
                    <form method="POST" action="seo_meta.php">
                        <input type="hidden" name="action" value="save">
                        <?php if($edit_meta): ?><input type="hidden" name="id" value="<?php echo $edit_meta['id']; ?>"><?php endif; ?>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Page Type</label>
                                <select name="page_type" class="form-control" required>
                                    <?php foreach(['college','exam','course','article','listing','tool'] as $opt): ?>
                                    <option value="<?php echo $opt; ?>" <?php echo ($edit_meta['page_type']??'') == $opt ? 'selected' : ''; ?>><?php echo ucfirst($opt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Page ID (Leave blank to auto-generate)</label>
                                <input type="text" name="page_id" class="form-control" value="<?php echo $edit_meta['page_id']??''; ?>" placeholder="Will be auto-generated">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" maxlength="70" value="<?php echo htmlspecialchars($edit_meta['meta_title']??''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="3" maxlength="160"><?php echo htmlspecialchars($edit_meta['meta_description']??''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Canonical URL</label>
                            <input type="url" name="canonical_url" class="form-control" value="<?php echo htmlspecialchars($edit_meta['canonical_url']??''); ?>">
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label>Robots Directive</label>
                                <select name="robots_directive" class="form-control">
                                    <?php foreach(['index_follow','noindex','nofollow'] as $opt): ?>
                                    <option value="<?php echo $opt; ?>" <?php echo ($edit_meta['robots_directive']??'') == $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Schema Type</label>
                                <select name="schema_type" class="form-control">
                                    <?php foreach(['College','Exam','Article','FAQPage','BreadcrumbList'] as $opt): ?>
                                    <option value="<?php echo $opt; ?>" <?php echo ($edit_meta['schema_type']??'') == $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Schema JSON</label>
                            <textarea name="schema_json" class="form-control" rows="3"><?php echo htmlspecialchars($edit_meta['schema_json']??''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>OG Image URL</label>
                            <input type="url" name="og_image" class="form-control" value="<?php echo htmlspecialchars($edit_meta['og_image']??''); ?>">
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label>Hreflang</label>
                                <input type="text" name="hreflang" class="form-control" value="<?php echo htmlspecialchars($edit_meta['hreflang']??'en-IN'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Index Status</label>
                                <select name="google_index_status" class="form-control">
                                    <?php foreach(['indexed','not_indexed','excluded'] as $opt): ?>
                                    <option value="<?php echo $opt; ?>" <?php echo ($edit_meta['google_index_status']??'') == $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;"><i class="ph ph-floppy-disk"></i> Save Meta Tag</button>
                        <?php if($edit_meta): ?>
                        <a href="seo_meta.php" class="btn" style="display:block; text-align:center; margin-top:10px; background:#F8FAFC; text-decoration:none; color:var(--text-color); padding:10px; border-radius:8px;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- List Panel -->
                <div class="panel">
                    <h3>Registered SEO Overrides (<?php echo count($meta_tags); ?>)</h3>
                    <div class="panel-body">
                        <table style="min-width:480px;">
                            <thead><tr><th>Page</th><th>Title</th><th>Robots</th><th>Schema</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($meta_tags as $r): ?>
                                <tr>
                                    <td>
                                        <span class="badge" style="background:rgba(11,36,71,0.06);color:#19376D;"><?php echo htmlspecialchars($r['page_type']); ?></span><br>
                                        <small style="color:var(--text-muted);"><?php echo substr($r['page_id'], 0, 8).'...'; ?></small>
                                    </td>
                                    <td style="font-weight:600; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?php echo htmlspecialchars($r['meta_title']); ?>
                                    </td>
                                    <td><span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65);"><?php echo $r['robots_directive']; ?></span></td>
                                    <td><span class="badge" style="background:rgba(11,36,71,0.04);color:#0B2447;"><?php echo $r['schema_type']; ?></span></td>
                                    <td>
                                        <a href="?edit_id=<?php echo $r['id']; ?>" style="color:var(--primary); margin-right:8px;"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?delete_id=<?php echo $r['id']; ?>" onclick="return confirm('Delete meta tag?');" style="color:#0F172A;"><i class="ph ph-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($meta_tags)): ?>
                                <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No meta tags found.</td></tr>
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
document.getElementById('mobile-menu-btn').addEventListener('click',function(){document.querySelector('.sidebar').classList.toggle('open');document.getElementById('sidebar-overlay').classList.toggle('show');});
document.getElementById('sidebar-overlay').addEventListener('click',function(){document.querySelector('.sidebar').classList.remove('open');this.classList.remove('show');});
</script>
</body>
</html>
