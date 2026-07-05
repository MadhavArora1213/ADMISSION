<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = $_GET['id'] ?? '';
$school_id = $_GET['school_id'] ?? '';
if (!$id || !$school_id) { header('Location: schools.php'); exit; }

$item = $pdo->prepare("SELECT * FROM school_news WHERE id = ? AND school_id = ?");
$item->execute([$id, $school_id]);
$item = $item->fetch(PDO::FETCH_ASSOC);
if (!$item) { header("Location: school_news.php?school_id=$school_id"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit News | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:#f1f5f9;margin:0;font-family:'Plus Jakarta Sans',sans-serif}
        .wrap{max-width:700px;margin:0 auto;padding:24px 20px}
        .top-bar{display:flex;align-items:center;gap:10px;margin-bottom:24px}
        .top-bar h1{font-size:1.3rem;font-weight:800;margin:0}
        .top-bar a{color:#64748b;text-decoration:none;font-size:1.1rem}
        .form-section{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px}
        .fg{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .fg.f1{grid-template-columns:1fr}
        .fi{margin-bottom:14px}
        .fi label{display:block;font-weight:600;font-size:.82rem;color:#334155;margin-bottom:5px}
        .fi input,.fi select,.fi textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.88rem;font-family:inherit;box-sizing:border-box}
        .fi input:focus,.fi select:focus,.fi textarea:focus{outline:none;border-color:#19376D}
        .btn-row{display:flex;gap:10px;margin-top:16px}
        .btn{padding:10px 20px;border-radius:8px;font-weight:700;font-size:.85rem;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
        .btn-p{background:linear-gradient(135deg,#0B2447,#19376D);color:#fff}
        .btn-e{background:none;color:#334155;border:1.5px solid #e2e8f0}
    </style>
</head>
<body>
<div class="wrap">
    <div class="top-bar">
        <a href="school_news.php?school_id=<?= $school_id ?>"><i class="ph ph-arrow-left"></i></a>
        <h1>Edit: <?= htmlspecialchars($item['title']) ?></h1>
    </div>
    <div class="form-section">
        <form method="POST" action="school_news_save.php">
            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
            <input type="hidden" name="school_id" value="<?= htmlspecialchars($school_id) ?>">
            <div class="fg f1"><div class="fi"><label>Title *</label><input type="text" name="title" required value="<?= htmlspecialchars($item['title']) ?>"></div></div>
            <div class="fg">
                <div class="fi"><label>Event Date</label><input type="date" name="event_date" value="<?= htmlspecialchars($item['event_date'] ?? '') ?>"></div>
                <div class="fi"><label>Status</label><select name="status"><option value="draft" <?= $item['status']==='draft'?'selected':'' ?>>Draft</option><option value="published" <?= $item['status']==='published'?'selected':'' ?>>Published</option></select></div>
            </div>
            <div class="fg f1"><div class="fi"><label>Summary</label><textarea name="excerpt" rows="2"><?= htmlspecialchars($item['excerpt'] ?? '') ?></textarea></div></div>
            <div class="fg f1"><div class="fi"><label>Full Content</label><textarea name="content" rows="6"><?= htmlspecialchars($item['content'] ?? '') ?></textarea></div></div>
            <div class="btn-row">
                <button type="submit" class="btn btn-p"><i class="ph ph-floppy-disk"></i> Save</button>
                <a href="school_news.php?school_id=<?= $school_id ?>" class="btn btn-e">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
