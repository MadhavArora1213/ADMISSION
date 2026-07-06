<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = $_POST['id'] ?? null;
    $data = [
        $_POST['template_name'], $_POST['template_slug_pattern'], $_POST['data_source'],
        $_POST['title_template'], $_POST['description_template'], isset($_POST['is_active']) ? 1 : 0
    ];
    if ($id) {
        $data[] = $id;
        $pdo->prepare("UPDATE seo_templates SET template_name=?, template_slug_pattern=?, data_source=?, title_template=?, description_template=?, is_active=? WHERE id=?")->execute($data);
    } else {
        $pdo->prepare("INSERT INTO seo_templates (template_name, template_slug_pattern, data_source, title_template, description_template, is_active, pages_generated) VALUES (?, ?, ?, ?, ?, ?, 0)")->execute($data);
    }
    header("Location: seo_templates.php?msg=saved"); exit;
}

// Handle deleting
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM seo_templates WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: seo_templates.php?msg=deleted"); exit;
}

// Handle toggle active
if (isset($_GET['toggle_active_id'])) {
    $pdo->prepare("UPDATE seo_templates SET is_active = NOT is_active WHERE id = ?")->execute([$_GET['toggle_active_id']]);
    header("Location: seo_templates.php"); exit;
}

// Fetch all templates
$templates = $pdo->query("SELECT * FROM seo_templates ORDER BY created_at DESC")->fetchAll();
$edit_tpl = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM seo_templates WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_tpl = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Templates | Admin</title>
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
        .panel-body{margin-top:0;overflow-x:auto;-webkit-overflow-scrolling:touch}
        .form-layout{display:grid;grid-template-columns:400px 1fr;gap:24px}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-weight:600;margin-bottom:6px;font-size:.85rem;color:var(--text-muted)}
        .form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.9rem;box-sizing:border-box}
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
        .msg-alert{padding:14px 20px;border-radius:8px;background:rgba(11,36,71,0.04);color:#0B2447;border:1px solid rgba(11,36,71,0.04);margin-bottom:20px}
        .vars-helper{font-size:.75rem;color:rgba(15,23,42,.45);background:#f8fafc;padding:8px;border-radius:6px;margin-top:4px;font-family:monospace}

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
                <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                <a href="logout.php" style="margin-left:16px;color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-file-code" style="color:var(--primary);"></i> Programmatic SEO Templates</h2>
                    <p style="color:var(--text-muted);">Generate thousands of pages automatically using templates.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="seo_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="seo_meta.php" class="sub-link"><i class="ph ph-tag"></i> Meta Tags & Schema</a>
                <a href="redirects.php" class="sub-link"><i class="ph ph-arrows-left-right"></i> Redirects</a>
                <a href="internal_links.php" class="sub-link"><i class="ph ph-link-break"></i> Internal Links</a>
                <a href="seo_templates.php" class="sub-link active"><i class="ph ph-file-code"></i> SEO Templates</a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Operation successful.</div>
            <?php endif; ?>

            <div class="form-layout">
                <!-- Add/Edit Form -->
                <div class="panel">
                    <h3><?php echo $edit_tpl ? 'Edit Template' : 'Add New Template'; ?></h3>
                    <form method="POST" action="seo_templates.php">
                        <input type="hidden" name="action" value="save">
                        <?php if($edit_tpl): ?><input type="hidden" name="id" value="<?php echo $edit_tpl['id']; ?>"><?php endif; ?>

                        <div class="form-group">
                            <label>Template Name *</label>
                            <input type="text" name="template_name" class="form-control" value="<?php echo htmlspecialchars($edit_tpl['template_name']??''); ?>" required placeholder="Top Colleges by City">
                        </div>
                        <div class="form-group">
                            <label>Slug Pattern *</label>
                            <input type="text" name="template_slug_pattern" class="form-control" value="<?php echo htmlspecialchars($edit_tpl['template_slug_pattern']??''); ?>" required placeholder="/:course-colleges-:city">
                            <div class="vars-helper">Use :var syntax for URL segments</div>
                        </div>
                        <div class="form-group">
                            <label>Data Source</label>
                            <select name="data_source" class="form-control">
                                <?php foreach(['colleges','exams','courses'] as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo ($edit_tpl['data_source']??'') == $opt ? 'selected' : ''; ?>><?php echo ucfirst($opt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Title Template *</label>
                            <input type="text" name="title_template" class="form-control" value="<?php echo htmlspecialchars($edit_tpl['title_template']??''); ?>" required placeholder="Top {course} Colleges in {city}">
                            <div class="vars-helper">Available vars: {course}, {city}, {state}</div>
                        </div>

                        <div class="form-group">
                            <label>Description Template *</label>
                            <textarea name="description_template" class="form-control" rows="4" required placeholder="Explore the best {course} colleges located in {city}..."><?php echo htmlspecialchars($edit_tpl['description_template']??''); ?></textarea>
                        </div>
                        
                        <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="is_active" id="is_active" <?php echo !isset($edit_tpl) || $edit_tpl['is_active'] ? 'checked' : ''; ?>>
                            <label for="is_active" style="margin:0;">Active (Generate Pages)</label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;"><i class="ph ph-floppy-disk"></i> Save Template</button>
                        <?php if($edit_tpl): ?>
                        <a href="seo_templates.php" class="btn" style="display:block; text-align:center; margin-top:10px; background:#F8FAFC; text-decoration:none; color:var(--text-color); padding:10px; border-radius:8px;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- List Panel -->
                <div class="panel">
                    <h3>Active Templates (<?php echo count($templates); ?>)</h3>
                    <div class="panel-body">
                        <table style="min-width:480px;">
                            <thead><tr><th>Template Name</th><th>Data Source</th><th>Pattern</th><th>Generated</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($templates as $t): ?>
                                <tr>
                                    <td style="font-weight:600; color:var(--primary);">
                                        <?php echo htmlspecialchars($t['template_name']); ?>
                                    </td>
                                    <td><span class="badge" style="background:rgba(11,36,71,0.06);color:#19376D;"><?php echo ucfirst($t['data_source']); ?></span></td>
                                    <td style="font-family:monospace; font-size:0.8rem; color:rgba(15,23,42,0.45);"><?php echo htmlspecialchars($t['template_slug_pattern']); ?></td>
                                    <td style="font-weight:700;"><?php echo number_format($t['pages_generated']); ?></td>
                                    <td>
                                        <a href="?toggle_active_id=<?php echo $t['id']; ?>" style="text-decoration:none;">
                                            <?php if($t['is_active']): ?><i class="ph-fill ph-check-circle" style="color:#0B2447;font-size:1.2rem;"></i>
                                            <?php else: ?><i class="ph-fill ph-minus-circle" style="color:#0F172A;font-size:1.2rem;"></i><?php endif; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="?edit_id=<?php echo $t['id']; ?>" style="color:var(--primary); margin-right:8px;"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?delete_id=<?php echo $t['id']; ?>" onclick="return confirm('Delete SEO template?');" style="color:#0F172A;"><i class="ph ph-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($templates)): ?>
                                <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">No SEO templates configured.</td></tr>
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
