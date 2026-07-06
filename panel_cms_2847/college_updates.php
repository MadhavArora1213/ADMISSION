<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

$college_id = $_GET['college_id'] ?? null;
if (!$college_id) {
    header('Location: colleges.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, name FROM colleges WHERE id = ?");
$stmt->execute([$college_id]);
$college = $stmt->fetch();
if (!$college) {
    header('Location: colleges.php');
    exit;
}

$error = '';

function genUuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'add') {
        try {
            // Generate slug from title
            $slug = strtolower(trim($_POST['title']));
            $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
            $slug = preg_replace('/[\s-]+/', '-', $slug);
            $slug = trim($slug, '-');
            $slug = mb_strimwidth($slug, 0, 80, '');
            // Ensure unique slug
            $original = $slug;
            $counter = 1;
            while (true) {
                $checkStmt = $pdo->prepare("SELECT id FROM college_updates WHERE slug = ?");
                $checkStmt->execute([$slug]);
                if (!$checkStmt->fetch()) break;
                $slug = $original . '-' . $counter;
                $counter++;
            }

            $pdo->prepare("INSERT INTO college_updates (id, slug, college_id, update_type, title, description, event_date, action_url, status) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([
                    genUuid(), $slug, $college_id,
                    $_POST['update_type'] ?? 'news',
                    $_POST['title'],
                    $_POST['description'] ?: null,
                    $_POST['event_date'] ?: null,
                    $_POST['action_url'] ?: null,
                    $_POST['status'] ?? 'published',
                ]);
            header("Location: college_updates.php?college_id=$college_id&msg=added");
            exit;
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM college_updates WHERE id = ? AND college_id = ?")->execute([$_POST['id'], $college_id]);
        header("Location: college_updates.php?college_id=$college_id&msg=deleted");
        exit;
    }
}

$updates = [];
try {
    $s = $pdo->prepare("SELECT * FROM college_updates WHERE college_id = ? ORDER BY event_date DESC, created_at DESC");
    $s->execute([$college_id]);
    $updates = $s->fetchAll();
} catch (Exception $e) {
    $error = 'Run admin/college_engagement_schema.sql first. ' . $e->getMessage();
}

$active_tab = 'college_updates';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College News & Updates</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
    <style>
        body { background: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .main-content { flex: 1; margin-left: 280px; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header { margin-bottom: 24px; }
        .page-header h2 { font-size: 1.8rem; font-weight: 700; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; white-space: nowrap; text-decoration: none; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 24px; margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid rgba(15,23,42,0.15); border-radius: 8px; font-family: inherit; font-size: 1rem; min-width: 0; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; min-width: 550px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .topbar { height: 56px; padding: 0 12px; justify-content: space-between; }
            .mobile-menu-btn { display: block; }
            .content-area { padding: 12px; }
            .page-header h2 { font-size: 1.2rem; gap: 8px; }
            .panel { padding: 14px; }
            .panel h3 { font-size: 1rem; margin-bottom: 12px; }
            .form-grid { grid-template-columns: 1fr; gap: 10px; }
            .form-group { margin-bottom: 12px; }
            .form-group label { font-size: 0.85rem; }
            .form-control { padding: 9px 12px; font-size: 0.9rem; }
            .tabs-nav { gap: 4px; margin-bottom: 16px; }
            .tab-link { padding: 6px 12px; font-size: 0.8rem; }
            table { min-width: 480px; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
            .panel { padding: 10px; border-radius: 10px; }
            .page-header h2 { font-size: 1.05rem; }
            .tabs-nav { gap: 3px; }
            .tab-link { padding: 5px 10px; font-size: 0.75rem; }
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
            <span>Admin</span>
        </header>
        <div class="content-area">
            <div class="page-header">
                <h2><a href="colleges.php" style="color:var(--text-muted)"><i class="ph ph-arrow-left"></i></a> <?= htmlspecialchars($college['name']) ?> — News & Updates</h2>
            </div>
            <?php include 'includes/college_tabs_nav.php'; ?>
            <?php if (isset($_GET['msg'])): ?><div style="padding:12px;background:rgba(11,36,71,0.04);color:#0B2447;border-radius:8px;margin-bottom:16px">Saved successfully.</div><?php endif; ?>
            <?php if ($error): ?><div style="padding:12px;background:rgba(15,23,42,0.06);color:#0B2447;border-radius:8px;margin-bottom:16px"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="panel">
                <h3><i class="ph ph-plus-circle"></i> Add Update / News</h3>
                <form method="post" style="margin-top:16px">
                    <input type="hidden" name="action" value="add">
                    <div class="form-grid">
                        <div class="form-group"><label>Type</label>
                            <select name="update_type" class="form-control">
                                <option value="news">News</option>
                                <option value="event">Event</option>
                                <option value="admission_deadline">Admission Deadline</option>
                                <option value="exam_date">Exam Date</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Event Date</label><input type="date" name="event_date" class="form-control"></div>
                        <div class="form-group"><label>Status</label>
                            <select name="status" class="form-control"><option value="published">Published</option><option value="draft">Draft</option></select>
                        </div>
                        <div class="form-group full"><label>Title *</label><input type="text" name="title" class="form-control" required placeholder="Admission Open 2026 — Apply Now"></div>
                        <div class="form-group full"><label>Description</label><textarea name="description" class="form-control" rows="4"></textarea></div>
                        <div class="form-group full"><label>Action URL</label><input type="url" name="action_url" class="form-control" placeholder="https://..."></div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:12px;">Add Update</button>
                </form>
            </div>

            <div class="panel">
                <h3>All Updates</h3>
                <?php if (empty($updates)): ?><p style="color:var(--text-muted); margin-top:16px;">No updates yet.</p><?php else: ?>
                <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Type</th><th>Title</th><th>Date</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($updates as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['update_type']) ?></td>
                        <td><?= htmlspecialchars($u['title']) ?></td>
                        <td><?= $u['event_date'] ? date('d M Y', strtotime($u['event_date'])) : '—' ?></td>
                        <td><?= htmlspecialchars($u['status']) ?></td>
                        <td>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($u['id']) ?>">
                                <button type="submit" style="background:none;border:none;color:#0F172A;cursor:pointer"><i class="ph ph-trash"></i></button>
                            </form>
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
