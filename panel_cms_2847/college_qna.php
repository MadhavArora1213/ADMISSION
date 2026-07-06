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
            $pdo->prepare("INSERT INTO college_qna (id, college_id, question_text, answer_text, status) VALUES (?,?,?,?,?)")
                ->execute([genUuid(), $college_id, $_POST['question_text'], $_POST['answer_text'] ?: null, $_POST['status'] ?? 'approved']);
            header("Location: college_qna.php?college_id=$college_id&msg=added");
            exit;
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif (($_POST['action'] ?? '') === 'approve') {
        $pdo->prepare("UPDATE college_qna SET status = 'approved' WHERE id = ? AND college_id = ?")->execute([$_POST['id'], $college_id]);
        header("Location: college_qna.php?college_id=$college_id&msg=approved");
        exit;
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM college_qna WHERE id = ? AND college_id = ?")->execute([$_POST['id'], $college_id]);
        header("Location: college_qna.php?college_id=$college_id&msg=deleted");
        exit;
    }
}

$qna = [];
try {
    $s = $pdo->prepare("SELECT * FROM college_qna WHERE college_id = ? ORDER BY created_at DESC");
    $s->execute([$college_id]);
    $qna = $s->fetchAll();
} catch (Exception $e) {
    $error = 'Run admin/college_engagement_schema.sql first. ' . $e->getMessage();
}

$active_tab = 'college_qna';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Student Q&A</title>
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
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid rgba(15,23,42,0.15); border-radius: 8px; font-family: inherit; font-size: 1rem; min-width: 0; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; min-width: 500px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top; }
        th { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; }
        .badge { padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: rgba(11,36,71,0.06); color: #0F172A; }
        .badge-approved { background: rgba(11,36,71,0.04); color: #0B2447; }
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
            .form-group label { font-size: 0.85rem; }
            .form-control { padding: 9px 12px; font-size: 0.9rem; }
            .tabs-nav { gap: 4px; margin-bottom: 16px; }
            .tab-link { padding: 6px 12px; font-size: 0.8rem; }
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
                <h2><a href="colleges.php" style="color:var(--text-muted)"><i class="ph ph-arrow-left"></i></a> <?= htmlspecialchars($college['name']) ?> — Student Q&A</h2>
            </div>
            <?php include 'includes/college_tabs_nav.php'; ?>
            <?php if (isset($_GET['msg'])): ?><div style="padding:12px;background:rgba(11,36,71,0.04);color:#0B2447;border-radius:8px;margin-bottom:16px">Done.</div><?php endif; ?>
            <?php if ($error): ?><div style="padding:12px;background:rgba(15,23,42,0.06);color:#0B2447;border-radius:8px;margin-bottom:16px"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="panel">
                <h3><i class="ph ph-plus-circle"></i> Add Q&A</h3>
                <form method="post" style="margin-top:16px">
                    <input type="hidden" name="action" value="add">
                    <div class="form-grid">
                        <div><label>Question *</label><textarea name="question_text" class="form-control" rows="2" required></textarea></div>
                        <div><label>Answer</label><textarea name="answer_text" class="form-control" rows="4"></textarea></div>
                        <div><label>Status</label>
                            <select name="status" class="form-control"><option value="approved">Approved</option><option value="pending">Pending</option></select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:12px;">Add Q&A</button>
                </form>
            </div>

            <div class="panel">
                <h3>All Questions</h3>
                <?php if (empty($qna)): ?><p style="color:var(--text-muted); margin-top:16px;">No Q&A yet.</p><?php else: ?>
                <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Question</th><th>Answer</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($qna as $q): ?>
                    <tr>
                        <td><?= htmlspecialchars($q['question_text']) ?></td>
                        <td><?= htmlspecialchars($q['answer_text'] ?? '—') ?></td>
                        <td><span class="badge badge-<?= $q['status'] === 'approved' ? 'approved' : 'pending' ?>"><?= htmlspecialchars($q['status']) ?></span></td>
                        <td>
                            <?php if ($q['status'] !== 'approved'): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($q['id']) ?>">
                                <button type="submit" style="background:none;border:none;color:#0B2447;cursor:pointer"><i class="ph ph-check"></i></button>
                            </form>
                            <?php endif; ?>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($q['id']) ?>">
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
