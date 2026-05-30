<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $name = trim($_POST['tag_name']);
    $slug = !empty($_POST['tag_slug']) ? $_POST['tag_slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $tid = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    if ($tid) {
        $pdo->prepare("UPDATE tags SET tag_name=?, tag_slug=? WHERE id=?")->execute([$name, $slug, $tid]);
    } else {
        $pdo->prepare("INSERT INTO tags (tag_name, tag_slug) VALUES (?,?)")->execute([$name, $slug]);
    }
    header("Location: tags.php?msg=saved"); exit;
}
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM tags WHERE id=?")->execute([$_GET['id']]);
    header("Location: tags.php?msg=deleted"); exit;
}

$tags = $pdo->query("SELECT t.*, COUNT(at.article_id) as usage_count FROM tags t LEFT JOIN article_tags at ON t.id = at.tag_id GROUP BY t.id ORDER BY t.tag_name ASC")->fetchAll();
$edit = null;
if (isset($_GET['edit_id'])) { $s = $pdo->prepare("SELECT * FROM tags WHERE id=?"); $s->execute([$_GET['edit_id']]); $edit = $s->fetch(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tags | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px;display:grid;grid-template-columns:300px 1fr;gap:32px;max-width:1100px;margin:0 auto;width:100%}.page-header{grid-column:1/-1;display:flex;align-items:center;gap:12px;margin-bottom:8px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#f8fafc;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);align-self:start}.form-group{margin-bottom:16px}.form-group label{display:block;font-weight:600;margin-bottom:7px;font-size:.9rem;color:var(--text-muted)}.form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.95rem;box-sizing:border-box}.msg-alert{grid-column:1/-1;padding:14px 20px;border-radius:8px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0}.action-btn{width:30px;height:30px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;background:#f1f5f9;color:var(--text-dark);border:1px solid var(--border-color);text-decoration:none}.action-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary)}.action-btn.delete:hover{background:#dc2626;border-color:#dc2626}.tag-cloud{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}.tag-pill{display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;border:1px solid var(--border-color);border-radius:20px;padding:6px 14px;font-size:.85rem;font-weight:600}table{width:100%;border-collapse:collapse}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f1f5f9}
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
                <a href="articles.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left" style="font-size:1.5rem;"></i></a>
                <h2>Manage Tags</h2>
            </div>
            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> <?php echo $_GET['msg']=='saved'?'Tag saved.':'Tag deleted.'; ?></div>
            <?php endif; ?>

            <div class="panel">
                <h3><?php echo $edit ? 'Edit Tag' : 'Add Tag'; ?></h3>
                <form method="POST" style="margin-top:16px;">
                    <input type="hidden" name="action" value="save">
                    <?php if($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
                    <div class="form-group"><label>Tag Name *</label><input type="text" name="tag_name" class="form-control" required value="<?php echo $edit ? htmlspecialchars($edit['tag_name']) : ''; ?>"></div>
                    <div class="form-group"><label>Slug</label><input type="text" name="tag_slug" class="form-control" placeholder="auto-generated" value="<?php echo $edit ? htmlspecialchars($edit['tag_slug']) : ''; ?>"></div>
                    <button type="submit" class="btn btn-primary" style="width:100%;"><?php echo $edit ? 'Update' : 'Add'; ?> Tag</button>
                    <?php if($edit): ?><a href="tags.php" class="btn" style="display:block;text-align:center;margin-top:8px;background:#f1f5f9;color:#475569;">Cancel</a><?php endif; ?>
                </form>
            </div>

            <div class="panel">
                <h3>All Tags (<?php echo count($tags); ?>)</h3>
                <div class="tag-cloud">
                    <?php foreach($tags as $t): ?>
                    <div class="tag-pill">
                        <i class="ph ph-tag" style="color:var(--primary);"></i>
                        <?php echo htmlspecialchars($t['tag_name']); ?>
                        <span style="background:var(--primary);color:#fff;border-radius:10px;padding:1px 7px;font-size:.72rem;"><?php echo $t['usage_count']; ?></span>
                        <a href="?edit_id=<?php echo $t['id']; ?>" style="color:var(--text-muted);text-decoration:none;" title="Edit"><i class="ph ph-pencil-simple" style="font-size:.85rem;"></i></a>
                        <a href="?action=delete&id=<?php echo $t['id']; ?>" style="color:#dc2626;text-decoration:none;" onclick="return confirm('Delete tag?')" title="Delete"><i class="ph ph-x" style="font-size:.85rem;"></i></a>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($tags)): ?><p style="color:var(--text-muted);">No tags yet.</p><?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
