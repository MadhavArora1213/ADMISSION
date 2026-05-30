<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle adding/editing (Admins generally only edit/moderate questions, but we'll add an edit form)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = $_POST['id'] ?? null;
    if ($id) {
        $data = [
            $_POST['question_text'], $_POST['question_category'], $_POST['status'],
            isset($_POST['is_featured']) ? 1 : 0, $_POST['trending_score'], $id
        ];
        $pdo->prepare("UPDATE questions SET question_text=?, question_category=?, status=?, is_featured=?, trending_score=? WHERE id=?")->execute($data);
        header("Location: questions_manager.php?msg=saved"); exit;
    }
}

// Handle deleting
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: questions_manager.php?msg=deleted"); exit;
}

// Fetch all questions
$questions = $pdo->query("SELECT * FROM questions ORDER BY created_at DESC")->fetchAll();
$edit_q = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_q = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions Manager | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.sub-links{display:flex;gap:8px;margin-bottom:20px}.sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:rgba(0,0,0,.05);color:var(--primary)}.form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.95rem;box-sizing:border-box}.form-group{margin-bottom:16px}.form-group label{display:block;font-weight:600;margin-bottom:7px;font-size:.9rem;color:var(--text-muted)}.msg-alert{padding:14px 20px;border-radius:8px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;margin-bottom:20px}
        .stats-micro {display:flex; gap:8px; font-size:0.75rem; color:#64748b; margin-top:4px;}
        .stats-micro span {display:flex; align-items:center; gap:3px;}
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
                <div>
                    <h2><i class="ph ph-question" style="color:var(--primary);"></i> Questions Manager</h2>
                    <p style="color:var(--text-muted);">Moderate and edit questions submitted by users.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="community_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="questions_manager.php" class="sub-link active"><i class="ph ph-question"></i> Questions</a>
                <a href="answers_manager.php" class="sub-link"><i class="ph ph-chat-text"></i> Answers</a>
                <a href="experts.php" class="sub-link"><i class="ph ph-user-check"></i> Experts</a>
                <a href="qa_moderation.php" class="sub-link"><i class="ph ph-shield-warning"></i> Moderation</a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Operation successful.</div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: <?php echo $edit_q ? '350px 1fr' : '1fr'; ?>; gap:24px;">
                <!-- Edit Form (Only visible when editing) -->
                <?php if($edit_q): ?>
                <div class="panel">
                    <h3>Edit Question</h3>
                    <form method="POST" action="questions_manager.php">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="<?php echo $edit_q['id']; ?>">

                        <div class="form-group">
                            <label>Question Text *</label>
                            <textarea name="question_text" class="form-control" rows="4" required><?php echo htmlspecialchars($edit_q['question_text']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Category</label>
                            <select name="question_category" class="form-control">
                                <?php foreach(['admission','fees','placements','hostel','exams','general'] as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo ($edit_q['question_category']??'') == $opt ? 'selected' : ''; ?>><?php echo ucfirst($opt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="open" <?php echo ($edit_q['status']??'') == 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="answered" <?php echo ($edit_q['status']??'') == 'answered' ? 'selected' : ''; ?>>Answered</option>
                                <option value="closed" <?php echo ($edit_q['status']??'') == 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Trending Score</label>
                            <input type="number" step="0.1" name="trending_score" class="form-control" value="<?php echo htmlspecialchars($edit_q['trending_score']??'0'); ?>">
                        </div>

                        <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="is_featured" id="is_featured" <?php echo ($edit_q['is_featured']??0) ? 'checked' : ''; ?>>
                            <label for="is_featured" style="margin:0;">Featured Question</label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;"><i class="ph ph-floppy-disk"></i> Save Changes</button>
                        <a href="questions_manager.php" class="btn" style="display:block; text-align:center; margin-top:10px; background:#f1f5f9; text-decoration:none; color:var(--text-color); padding:10px; border-radius:8px;">Cancel Edit</a>
                    </form>
                </div>
                <?php endif; ?>

                <!-- List Panel -->
                <div class="panel">
                    <h3>All Questions (<?php echo count($questions); ?>)</h3>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead><tr><th>Question</th><th>Category / Status</th><th>Engagement</th><th>Flags</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($questions as $q): ?>
                                <tr>
                                    <td style="max-width:300px;">
                                        <div style="font-weight:600; color:var(--primary); line-height:1.4;">
                                            <?php echo htmlspecialchars(strlen($q['question_text']) > 80 ? substr($q['question_text'], 0, 80).'...' : $q['question_text']); ?>
                                        </div>
                                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px; font-family:monospace;">
                                            Author: <?php echo substr($q['asked_by'], 0, 8); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:#f1f5f9;color:#475569; margin-bottom:4px; display:inline-block;"><?php echo ucfirst($q['question_category']); ?></span><br>
                                        <?php if($q['status'] == 'open'): ?><span class="badge" style="background:#dcfce7;color:#166534;">Open</span>
                                        <?php elseif($q['status'] == 'answered'): ?><span class="badge" style="background:#e0e7ff;color:#3730a3;">Answered</span>
                                        <?php else: ?><span class="badge" style="background:#fee2e2;color:#dc2626;">Closed</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="stats-micro">
                                            <span title="Views"><i class="ph ph-eye" style="color:#0284c7;"></i> <?php echo number_format($q['views']); ?></span>
                                            <span title="Answers"><i class="ph ph-chat-text" style="color:#16a34a;"></i> <?php echo number_format($q['answer_count']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($q['is_featured']): ?>
                                        <span class="badge" style="background:#fef08a;color:#854d0e;"><i class="ph-fill ph-star"></i> Featured</span>
                                        <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.8rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?edit_id=<?php echo $q['id']; ?>" style="color:var(--primary); margin-right:8px;"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?delete_id=<?php echo $q['id']; ?>" onclick="return confirm('Delete question permanently?');" style="color:#dc2626;"><i class="ph ph-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($questions)): ?>
                                <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No questions found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
