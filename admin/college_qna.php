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
    <style>
        body { background: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-left: 280px; min-width: 0; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; }
        .content-area { padding: 32px; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header h2 { font-size: 1.8rem; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; white-space: nowrap; text-decoration: none; }
        .tab-link.active { background: var(--primary); color: white; }
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 24px; margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid rgba(15,23,42,0.15); border-radius: 8px; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top; }
        th { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; }
        .badge { padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: rgba(11,36,71,0.06); color: #0F172A; }
        .badge-approved { background: rgba(11,36,71,0.04); color: #0B2447; }
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
                    <button type="submit" style="margin-top:12px;padding:10px 20px;background:var(--primary);color:#fff;border:none;border-radius:8px;font-weight:600">Add Q&A</button>
                </form>
            </div>

            <div class="panel">
                <h3>All Questions</h3>
                <?php if (empty($qna)): ?><p>No Q&A yet.</p><?php else: ?>
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
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
