<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = !empty($_POST['id']) ? $_POST['id'] : sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $name = trim($_POST['category_name']);
    $slug = !empty($_POST['category_slug']) ? $_POST['category_slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $parent = !empty($_POST['parent_category_id']) ? $_POST['parent_category_id'] : null;
    
    $icon_url = !empty($_POST['icon_url']) ? $_POST['icon_url'] : null;
    
    if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] == 0) {
        $upload_dir = '../uploads/categories/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['icon_file']['name']));
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['icon_file']['tmp_name'], $target_file)) {
            $icon_url = 'uploads/categories/' . $file_name;
            require_once __DIR__ . '/upload_sync.php';
            sync_to_github('uploads/categories/' . $file_name);
        }
    }

    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE course_categories SET category_name = ?, category_slug = ?, icon_url = COALESCE(?, icon_url), parent_category_id = ?, sort_order = ?, is_featured = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $icon_url, $parent, $_POST['sort_order'], isset($_POST['is_featured']) ? 1 : 0, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO course_categories (id, category_name, category_slug, icon_url, parent_category_id, sort_order, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $name, $slug, $icon_url, $parent, $_POST['sort_order'], isset($_POST['is_featured']) ? 1 : 0]);
    }
    header('Location: course_categories.php?msg=saved');
    exit;
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM course_categories WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header('Location: course_categories.php?msg=deleted');
    exit;
}

// Fetch Categories
$stmt = $pdo->query("SELECT c1.*, c2.category_name as parent_name FROM course_categories c1 LEFT JOIN course_categories c2 ON c1.parent_category_id = c2.id ORDER BY c1.sort_order ASC, c1.category_name ASC");
$categories = $stmt->fetchAll();

// Fetch parents for dropdown
$parentStmt = $pdo->query("SELECT id, category_name FROM course_categories ORDER BY category_name ASC");
$parentCategories = $parentStmt->fetchAll();

$edit_cat = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM course_categories WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_cat = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Categories | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; padding-bottom: 60px; min-width: 0; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 1200px; margin: 0 auto; width: 100%; display: grid; grid-template-columns: 350px 1fr; gap: 32px; }
        .page-header { grid-column: 1 / -1; margin-bottom: 8px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); align-self: start; overflow-x: auto; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; min-width: 600px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; }
        .action-links { display: flex; gap: 8px; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #F8FAFC; color: var(--text-dark); border: 1px solid var(--border-color); }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.delete:hover { background: #0F172A; color: white; border-color: #0F172A; }
        .msg-alert { grid-column: 1 / -1; padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 16px; border: 1px solid rgba(11,36,71,0.04); }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; background: rgba(11,36,71,0.04); color: #19376D; border: 1px solid rgba(11,36,71,0.08); }

        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        @media (max-width: 1024px) {
            .content-area { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) { 
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .topbar { justify-content: space-between; padding: 0 16px; }
            .mobile-menu-btn { display: block; }
            .content-area { padding: 16px; gap: 16px; }
            .page-header h2 { font-size: 1.5rem; }
            .panel { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <header class="topbar">
                <button class="mobile-menu-btn" id="mobile-menu-btn">
                    <i class="ph ph-list"></i>
                </button>
                <div class="user-profile">
                    <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" style="margin-left: 16px; color: #19376d;"><i class="ph ph-sign-out" style="font-size: 1.5rem;"></i></a>
                </div>
            </header>

            <div class="content-area">
                <div class="page-header">
                    <h2>Manage Course Categories</h2>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> Category saved successfully.</div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> Category deleted.</div>
                <?php endif; ?>

                <div class="panel">
                    <h3><?php echo $edit_cat ? 'Edit Category' : 'Add New Category'; ?></h3>
                    <form action="course_categories.php" method="POST" enctype="multipart/form-data" style="margin-top: 24px;">
                        <input type="hidden" name="action" value="save">
                        <?php if($edit_cat): ?><input type="hidden" name="id" value="<?php echo $edit_cat['id']; ?>"><?php endif; ?>
                        
                        <div class="form-group">
                            <label>Category Name *</label>
                            <input type="text" name="category_name" class="form-control" required value="<?php echo $edit_cat ? htmlspecialchars($edit_cat['category_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Category Slug</label>
                            <input type="text" name="category_slug" class="form-control" placeholder="Leave blank to auto-generate" value="<?php echo $edit_cat ? htmlspecialchars($edit_cat['category_slug']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Parent Category</label>
                            <select name="parent_category_id" class="form-control">
                                <option value="">-- None --</option>
                                <?php foreach($parentCategories as $p): 
                                    if($edit_cat && $p['id'] == $edit_cat['id']) continue;
                                ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ($edit_cat && $edit_cat['parent_category_id'] == $p['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['category_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Upload Icon Image</label>
                            <input type="file" name="icon_file" class="form-control" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>OR Icon URL</label>
                            <input type="url" name="icon_url" class="form-control" value="<?php echo $edit_cat ? htmlspecialchars($edit_cat['icon_url']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="<?php echo $edit_cat ? $edit_cat['sort_order'] : '0'; ?>">
                        </div>
                        <div class="form-group" style="display:flex; gap:8px; align-items:center;">
                            <input type="checkbox" name="is_featured" id="is_featured" <?php echo ($edit_cat && $edit_cat['is_featured']) ? 'checked' : ''; ?>>
                            <label for="is_featured" style="margin:0;">Is Featured?</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top: 16px; width:100%;">
                            <?php echo $edit_cat ? 'Update Category' : 'Save Category'; ?>
                        </button>
                        <?php if($edit_cat): ?>
                        <a href="course_categories.php" class="btn" style="display:block; text-align:center; margin-top:8px; background:#F8FAFC; color:rgba(15,23,42,0.65);">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="panel">
                    <h3>All Categories</h3>
                    <?php if(empty($categories)): ?>
                        <p style="color:var(--text-muted); margin-top:16px;">No categories found.</p>
                    <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Parent</th>
                                    <th>Order</th>
                                    <th>Featured</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($categories as $cat): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--primary); display: flex; align-items: center; gap: 12px;">
                                        <?php if($cat['icon_url']): ?>
                                            <img src="<?php echo preg_match('/^https?:\/\//', $cat['icon_url']) ? htmlspecialchars($cat['icon_url']) : '../' . htmlspecialchars($cat['icon_url']); ?>" alt="icon" style="width: 32px; height: 32px; object-fit: cover; border-radius: 6px; background: #fff; padding: 2px; border: 1px solid var(--border-color);">
                                        <?php else: ?>
                                            <div style="width: 32px; height: 32px; border-radius: 6px; background: rgba(15,23,42,0.08); display:flex; align-items:center; justify-content:center; color: rgba(15,23,42,0.45);"><i class="ph ph-image"></i></div>
                                        <?php endif; ?>
                                        <div>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;"><?php echo htmlspecialchars($cat['category_slug']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo $cat['parent_name'] ? htmlspecialchars($cat['parent_name']) : '-'; ?></td>
                                    <td><?php echo $cat['sort_order']; ?></td>
                                    <td><?php echo $cat['is_featured'] ? '<span class="badge">Yes</span>' : 'No'; ?></td>
                                    <td>
                                        <div class="action-links">
                                            <a href="course_categories.php?edit_id=<?php echo $cat['id']; ?>" class="action-btn" title="Edit"><i class="ph ph-pencil-simple"></i></a>
                                            <a href="course_categories.php?action=delete&id=<?php echo $cat['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirm('Are you sure you want to delete this category?');"><i class="ph ph-trash"></i></a>
                                        </div>
                                    </td>
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
