<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
if (!$course_id) { header('Location: courses.php'); exit; }

$stmt = $pdo->prepare("SELECT course_name FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) { header('Location: courses.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = !empty($_POST['id']) ? $_POST['id'] : sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $name = trim($_POST['specialization_name']);
    $slug = !empty($_POST['specialization_slug']) ? $_POST['specialization_slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    
    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE course_specializations SET specialization_name = ?, specialization_slug = ?, description = ?, sort_order = ?, is_popular = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $_POST['description'], $_POST['sort_order'], isset($_POST['is_popular']) ? 1 : 0, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO course_specializations (id, parent_course_id, specialization_name, specialization_slug, description, sort_order, is_popular) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $course_id, $name, $slug, $_POST['description'], $_POST['sort_order'], isset($_POST['is_popular']) ? 1 : 0]);
    }
    header("Location: course_specializations.php?course_id=$course_id&msg=saved");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM course_specializations WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: course_specializations.php?course_id=$course_id&msg=deleted");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM course_specializations WHERE parent_course_id = ? ORDER BY sort_order ASC, specialization_name ASC");
$stmt->execute([$course_id]);
$list = $stmt->fetchAll();

$edit = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM course_specializations WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Specializations | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; padding-bottom: 60px; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 1000px; margin: 0 auto; width: 100%; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 12px; }
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        .form-section { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; }
        .action-links { display: flex; gap: 8px; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #F8FAFC; color: var(--text-dark); border: 1px solid var(--border-color); }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.delete:hover { background: #0F172A; color: white; border-color: #0F172A; }
        .msg-alert { padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 24px; border: 1px solid rgba(11,36,71,0.04); }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <header class="topbar">
                <div class="user-profile">
                    <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" style="margin-left: 16px; color: #19376d;"><i class="ph ph-sign-out" style="font-size: 1.5rem;"></i></a>
                </div>
            </header>

            <div class="content-area">
                <div class="page-header">
                    <h2><a href="course_form.php?id=<?php echo $course_id; ?>&tab=basic" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> Specializations: <?php echo htmlspecialchars($course['course_name']); ?></h2>
                </div>

                <div class="tabs-nav">
                    <a href="course_form.php?id=<?php echo $course_id; ?>&tab=basic" class="tab-link">Basic Info</a>
                    <a href="course_form.php?id=<?php echo $course_id; ?>&tab=details" class="tab-link">Descriptions & Scope</a>
                    <a href="course_form.php?id=<?php echo $course_id; ?>&tab=salary" class="tab-link">Salary & Recruiters</a>
                    <a href="course_specializations.php?course_id=<?php echo $course_id; ?>" class="tab-link active">Specializations</a>
                    <a href="course_career_paths.php?course_id=<?php echo $course_id; ?>" class="tab-link">Career Paths</a>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> Specialization saved successfully!</div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> Specialization deleted.</div>
                <?php endif; ?>

                <form action="" method="POST" class="form-section">
                    <h3><?php echo $edit ? 'Edit Specialization' : 'Add Specialization'; ?></h3>
                    <input type="hidden" name="action" value="save">
                    <?php if($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Specialization Name *</label>
                            <input type="text" name="specialization_name" class="form-control" required value="<?php echo $edit ? htmlspecialchars($edit['specialization_name']) : ''; ?>">
                        </div>
                        <div class="form-group full">
                            <label>Slug</label>
                            <input type="text" name="specialization_slug" class="form-control" placeholder="Auto-generated if empty" value="<?php echo $edit ? htmlspecialchars($edit['specialization_slug']) : ''; ?>">
                        </div>
                        <div class="form-group full">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo $edit ? htmlspecialchars($edit['description']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="<?php echo $edit ? $edit['sort_order'] : '0'; ?>">
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:8px; padding-top: 28px;">
                            <input type="checkbox" name="is_popular" id="is_pop" <?php echo ($edit && $edit['is_popular']) ? 'checked' : ''; ?>>
                            <label for="is_pop" style="margin:0;">Is Popular?</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 16px;"><?php echo $edit ? 'Update' : 'Add'; ?> Specialization</button>
                    <?php if($edit): ?>
                        <a href="?course_id=<?php echo $course_id; ?>" class="btn" style="margin-left: 8px;">Cancel</a>
                    <?php endif; ?>
                </form>

                <div class="form-section">
                    <h3>Existing Specializations</h3>
                    <?php if(empty($list)): ?>
                        <p style="color:var(--text-muted);">No specializations added.</p>
                    <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <tr>
                                <th>Name</th>
                                <th>Order</th>
                                <th>Popular</th>
                                <th>Action</th>
                            </tr>
                            <?php foreach($list as $d): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--primary);">
                                    <?php echo htmlspecialchars($d['specialization_name']); ?>
                                </td>
                                <td><?php echo $d['sort_order']; ?></td>
                                <td><?php echo $d['is_popular'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <div class="action-links">
                                        <a href="?course_id=<?php echo $course_id; ?>&edit_id=<?php echo $d['id']; ?>" class="action-btn"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?course_id=<?php echo $course_id; ?>&action=delete&id=<?php echo $d['id']; ?>" class="action-btn delete" onclick="return confirm('Delete?');"><i class="ph ph-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
