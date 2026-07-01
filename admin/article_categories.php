<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = !empty($_POST['id']) ? $_POST['id'] : (int)0;
    $name = trim($_POST['category_name']);
    $slug = !empty($_POST['category_slug']) ? $_POST['category_slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $parent = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    if ($id) {
        $pdo->prepare("UPDATE article_categories SET category_name=?, category_slug=?, parent_id=?, sort_order=? WHERE id=?")->execute([$name, $slug, $parent, $_POST['sort_order'], $id]);
    } else {
        $pdo->prepare("INSERT INTO article_categories (category_name, category_slug, parent_id, sort_order) VALUES (?,?,?,?)")->execute([$name, $slug, $parent, $_POST['sort_order']]);
    }
    header("Location: article_categories.php?msg=saved"); exit;
}
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM article_categories WHERE id=?")->execute([$_GET['id']]);
    header("Location: article_categories.php?msg=deleted"); exit;
}

$cats = $pdo->query("SELECT c1.*, c2.category_name as parent_name FROM article_categories c1 LEFT JOIN article_categories c2 ON c1.parent_id = c2.id ORDER BY c1.sort_order ASC, c1.category_name ASC")->fetchAll();
$allCats = $pdo->query("SELECT id, category_name FROM article_categories ORDER BY category_name ASC")->fetchAll();
$edit = null;
if (isset($_GET['edit_id'])) {
    $s = $pdo->prepare("SELECT * FROM article_categories WHERE id=?"); $s->execute([$_GET['edit_id']]); $edit = $s->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Article Categories | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light);margin:0}
        .admin-layout{display:flex;min-height:100vh}
        .sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto;z-index:100;transition:transform .3s ease}
        .sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}
        .sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}
        .sidebar-nav{padding:24px 0;flex:1}
        .sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s ease;text-decoration:none}
        .sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}
        .main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;min-width:0}
        .topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}
        .content-area{padding:32px;display:grid;grid-template-columns:340px 1fr;gap:32px;max-width:1200px;margin:0 auto;width:100%;box-sizing:border-box}
        .page-header{grid-column:1/-1;display:flex;align-items:center;gap:12px;margin-bottom:8px}
        .page-header h2{font-size:2rem;font-weight:800}
        .panel{background:#f8fafc;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);align-self:start}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-weight:600;margin-bottom:7px;font-size:.9rem;color:var(--text-muted)}
        .form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.95rem;box-sizing:border-box}
        .msg-alert{grid-column:1/-1;padding:14px 20px;border-radius:8px;background:rgba(11,36,71,0.04);color:#0B2447;border:1px solid rgba(11,36,71,0.04)}
        .action-btn{width:30px;height:30px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;background:#F8FAFC;color:var(--text-dark);border:1px solid var(--border-color);text-decoration:none}
        .action-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
        .action-btn.delete:hover{background:#0F172A;border-color:#0F172A}
        table{width:100%;border-collapse:collapse;font-size:.88rem}
        th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}
        th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}
        tr:hover{background-color:rgba(0,0,0,.015)}
        .mobile-menu-btn{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#0f172a;padding:4px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:90}

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .sidebar-overlay.show{display:block}
            .main-content{margin-left:0}
            .mobile-menu-btn{display:block}
            .topbar{height:auto;min-height:56px;padding:10px 12px;justify-content:space-between}
            .content-area{padding:12px;grid-template-columns:1fr;gap:16px}
            .page-header{flex-direction:column;align-items:flex-start;gap:8px}
            .page-header h2{font-size:1.3rem}
            .panel{padding:14px;border-radius:12px;overflow:hidden}
            th,td{padding:8px 10px;font-size:.82rem}
        }
        @media(max-width:480px){
            .content-area{padding:8px}
            .page-header h2{font-size:1.1rem}
            .panel{padding:12px}
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
                <a href="articles.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left" style="font-size:1.5rem;"></i></a>
                <h2>Article Categories</h2>
            </div>
            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> <?php echo $_GET['msg']=='saved'?'Category saved.':'Category deleted.'; ?></div>
            <?php endif; ?>

            <div class="panel">
                <h3><?php echo $edit ? 'Edit Category' : 'Add Category'; ?></h3>
                <form method="POST" style="margin-top:16px;">
                    <input type="hidden" name="action" value="save">
                    <?php if($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
                    <div class="form-group"><label>Category Name *</label><input type="text" name="category_name" class="form-control" required value="<?php echo $edit ? htmlspecialchars($edit['category_name']) : ''; ?>"></div>
                    <div class="form-group"><label>Slug</label><input type="text" name="category_slug" class="form-control" placeholder="auto-generated" value="<?php echo $edit ? htmlspecialchars($edit['category_slug']) : ''; ?>"></div>
                    <div class="form-group"><label>Parent Category</label>
                        <select name="parent_id" class="form-control">
                            <option value="">-- None --</option>
                            <?php foreach($allCats as $c): if($edit && $c['id']==$edit['id']) continue; ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($edit && $edit['parent_id']==$c['id'])?'selected':''; ?>><?php echo htmlspecialchars($c['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-control" value="<?php echo $edit ? $edit['sort_order'] : '0'; ?>"></div>
                    <button type="submit" class="btn btn-primary" style="width:100%;"><?php echo $edit ? 'Update' : 'Save'; ?> Category</button>
                    <?php if($edit): ?><a href="article_categories.php" class="btn" style="display:block;text-align:center;margin-top:8px;background:#F8FAFC;color:rgba(15,23,42,0.65);">Cancel</a><?php endif; ?>
                </form>
            </div>

            <div class="panel">
                <h3>All Categories (<?php echo count($cats); ?>)</h3>
                <?php if(empty($cats)): ?><p style="color:var(--text-muted);margin-top:16px;">No categories yet.</p>
                <?php else: ?>
                <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
                    <table style="min-width:420px;">
                        <thead><tr><th>Name</th><th>Parent</th><th>Order</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($cats as $c): ?>
                            <tr>
                                <td style="font-weight:600;color:var(--primary);"><?php echo htmlspecialchars($c['category_name']); ?><div style="font-size:.75rem;color:var(--text-muted);"><?php echo htmlspecialchars($c['category_slug']); ?></div></td>
                                <td><?php echo $c['parent_name'] ? htmlspecialchars($c['parent_name']) : '—'; ?></td>
                                <td><?php echo $c['sort_order']; ?></td>
                                <td><div style="display:flex;gap:6px;">
                                    <a href="?edit_id=<?php echo $c['id']; ?>" class="action-btn"><i class="ph ph-pencil-simple"></i></a>
                                    <a href="?action=delete&id=<?php echo $c['id']; ?>" class="action-btn delete" onclick="return confirm('Delete?')"><i class="ph ph-trash"></i></a>
                                </div></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
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
