<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$school_id = $_GET['school_id'] ?? '';
if (!$school_id) { header('Location: schools.php'); exit; }

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM school_news WHERE id = ? AND school_id = ?")->execute([$_GET['id'], $school_id]);
    header("Location: school_news.php?school_id=$school_id&msg=deleted");
    exit;
}
if (isset($_GET['action']) && $_GET['action'] == 'toggle' && isset($_GET['id'])) {
    $pdo->prepare("UPDATE school_news SET status = IF(status='published','draft','published') WHERE id = ? AND school_id = ?")->execute([$_GET['id'], $school_id]);
    header("Location: school_news.php?school_id=$school_id&msg=toggled");
    exit;
}

$schoolName = $pdo->prepare("SELECT name FROM schools WHERE id = ?");
$schoolName->execute([$school_id]);
$schoolName = $schoolName->fetchColumn() ?: 'School';
if (!$schoolName) { header('Location: schools.php'); exit; }

$news = $pdo->prepare("SELECT * FROM school_news WHERE school_id = ? ORDER BY event_date DESC, created_at DESC");
$news->execute([$school_id]);
$news = $news->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage School News | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:#f1f5f9;margin:0;font-family:'Plus Jakarta Sans',sans-serif}
        .wrap{max-width:1000px;margin:0 auto;padding:24px 20px}
        .top-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;gap:12px;flex-wrap:wrap}
        .top-bar h1{font-size:1.4rem;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
        .top-bar h1 a{color:#64748b;text-decoration:none;font-size:1.1rem}
        .msg{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:.9rem;font-weight:600}
        .msg.ok{background:rgba(22,163,74,.1);color:#16a34a;border:1px solid rgba(22,163,74,.2)}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:16px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:12px 16px;text-align:left;border-bottom:1px solid #f1f5f9;font-size:.88rem}
        th{font-weight:700;color:#64748b;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px}
        tr:hover{background:#f8fafc}
        .badge{padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
        .badge-green{background:rgba(22,163,74,.1);color:#16a34a}
        .badge-gray{background:rgba(100,116,139,.1);color:#64748b}
        .btn{padding:10px 20px;border-radius:8px;font-weight:700;font-size:.85rem;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;transition:all .2s}
        .btn-p{background:linear-gradient(135deg,#0B2447,#19376D);color:#fff}
        .btn-d{background:none;color:#dc2626;border:1px solid #fecaca;padding:6px 12px;font-size:.8rem}
        .btn-e{background:none;color:#19376D;border:1px solid #e2e8f0;padding:6px 12px;font-size:.8rem}
        .empty{text-align:center;padding:40px;color:#94a3b8}
        .empty i{font-size:2.5rem;display:block;margin-bottom:8px}
        .form-section{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:16px}
        .form-section h3{font-size:1rem;font-weight:700;margin:0 0 16px;color:#0f172a}
        .fg{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .fg.f1{grid-template-columns:1fr}
        .fi{margin-bottom:14px}
        .fi label{display:block;font-weight:600;font-size:.82rem;color:#334155;margin-bottom:5px}
        .fi input,.fi select,.fi textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.88rem;font-family:inherit;box-sizing:border-box}
        .fi input:focus,.fi select:focus,.fi textarea:focus{outline:none;border-color:#19376D}
        .btn-row{display:flex;gap:10px;margin-top:16px}
        @media(max-width:768px){.fg{grid-template-columns:1fr!important}.wrap{padding:16px 12px}}
    </style>
</head>
<body>
<div class="wrap">
    <div class="top-bar">
        <h1><a href="school_form.php?id=<?= htmlspecialchars($school_id) ?>&tab=identity"><i class="ph ph-arrow-left"></i></a> News: <?= htmlspecialchars($schoolName) ?></h1>
        <button class="btn btn-p" onclick="document.getElementById('addForm').style.display=document.getElementById('addForm').style.display==='none'?'block':'none'"><i class="ph ph-plus"></i> Add News</button>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg']=='saved'): ?><div class="msg ok">Saved!</div><?php endif; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg']=='deleted'): ?><div class="msg ok">Deleted!</div><?php endif; ?>

    <div id="addForm" style="display:none">
        <div class="form-section">
            <h3>Add News / Update</h3>
            <form method="POST" action="school_news_save.php">
                <input type="hidden" name="school_id" value="<?= htmlspecialchars($school_id) ?>">
                <div class="fg">
                    <div class="fg f1"><div class="fi"><label>Title *</label><input type="text" name="title" required placeholder="e.g. Annual Day Celebration"></div></div>
                    <div class="fi"><label>Event Date</label><input type="date" name="event_date"></div>
                    <div class="fi"><label>Status</label><select name="status"><option value="draft">Draft</option><option value="published">Published</option></select></div>
                </div>
                <div class="fg f1"><div class="fi"><label>Summary</label><textarea name="excerpt" rows="2" placeholder="Short description..."></textarea></div></div>
                <div class="fg f1"><div class="fi"><label>Full Content</label><textarea name="content" rows="4" placeholder="Detailed content..."></textarea></div></div>
                <div class="btn-row">
                    <button type="submit" class="btn btn-p"><i class="ph ph-floppy-disk"></i> Save</button>
                    <button type="button" class="btn btn-e" onclick="document.getElementById('addForm').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <?php if(empty($news)): ?>
            <div class="empty"><i class="ph ph-newspaper"></i>No news yet. Click "Add News" to start.</div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table>
                    <thead><tr><th>Title</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach($news as $n): ?>
                    <tr>
                        <td style="font-weight:600"><?= htmlspecialchars($n['title']) ?></td>
                        <td><?= $n['event_date'] ? date('d M Y', strtotime($n['event_date'])) : '—' ?></td>
                        <td><span class="badge <?= $n['status']==='published' ? 'badge-green' : 'badge-gray' ?>"><?= ucfirst($n['status']) ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px">
                                <a href="school_news_form.php?id=<?= $n['id'] ?>&school_id=<?= $school_id ?>" class="btn btn-e"><i class="ph ph-pencil-simple"></i> Edit</a>
                                <a href="school_news.php?school_id=<?= $school_id ?>&action=toggle&id=<?= $n['id'] ?>" class="btn btn-e"><?= $n['status']==='published' ? 'Unpublish' : 'Publish' ?></a>
                                <a href="school_news.php?school_id=<?= $school_id ?>&action=delete&id=<?= $n['id'] ?>" class="btn btn-d" onclick="return confirm('Delete?')"><i class="ph ph-trash"></i></a>
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
</body>
</html>
