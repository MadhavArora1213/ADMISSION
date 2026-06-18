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
            $pdo->prepare("INSERT INTO college_updates (id, college_id, update_type, title, description, event_date, action_url, status) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([
                    genUuid(), $college_id,
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
    <style>
        body { background: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; }
        .main-content { flex: 1; margin-left: 280px; min-width: 0; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; }
        .content-area { padding: 32px; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header h2 { font-size: 1.8rem; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; white-space: nowrap; text-decoration: none; }
        .tab-link.active { background: var(--primary); color: white; }
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 24px; margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } .content-area { padding: 16px; } }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar"><span>Admin</span></header>
        <div class="content-area">
            <div class="page-header">
                <h2><a href="colleges.php" style="color:var(--text-muted)"><i class="ph ph-arrow-left"></i></a> <?= htmlspecialchars($college['name']) ?> — News & Updates</h2>
            </div>
            <?php include 'includes/college_tabs_nav.php'; ?>
            <?php if (isset($_GET['msg'])): ?><div style="padding:12px;background:#dcfce7;color:#166534;border-radius:8px;margin-bottom:16px">Saved successfully.</div><?php endif; ?>
            <?php if ($error): ?><div style="padding:12px;background:#fee2e2;color:#991b1b;border-radius:8px;margin-bottom:16px"><?= htmlspecialchars($error) ?></div><?php endif; ?>

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
                    <button type="submit" style="margin-top:12px;padding:10px 20px;background:var(--primary);color:#fff;border:none;border-radius:8px;font-weight:600">Add Update</button>
                </form>
            </div>

            <div class="panel">
                <h3>All Updates</h3>
                <?php if (empty($updates)): ?><p>No updates yet.</p><?php else: ?>
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
                                <button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer"><i class="ph ph-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
