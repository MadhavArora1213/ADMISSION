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
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}
        .sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto;z-index:100;transition:transform 0.3s ease}
        .sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}
        .sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s;text-decoration:none}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}
        .main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}
        .topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}
        .content-area{padding:32px;max-width:1400px;margin:0 auto;width:100%;box-sizing:border-box}
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:12px;flex-wrap:wrap}.page-header h2{font-size:2rem;font-weight:800}
        .sub-links{display:flex;gap:8px;margin-bottom:20px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:thin;flex-wrap:nowrap;border-bottom:1px solid var(--border-color);padding-bottom:12px}
        .sub-links::-webkit-scrollbar{height:5px}.sub-links::-webkit-scrollbar-track{background:#e2e8f0;border-radius:3px}.sub-links::-webkit-scrollbar-thumb{background:var(--primary);border-radius:3px}
        .sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s;white-space:nowrap;flex-shrink:0;display:flex;align-items:center;gap:4px}.sub-link:hover,.sub-link.active{color:var(--primary);background:rgba(0,0,0,.03)}
        .panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px;overflow-x:auto}
        .panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}
        table{width:100%;border-collapse:collapse;font-size:.88rem;min-width:600px}
        th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}
        tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700;white-space:nowrap}
        .form-group{margin-bottom:16px}.form-group label{display:block;font-weight:600;margin-bottom:6px;font-size:.9rem}
        .form-control{width:100%;min-width:0;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-size:.9rem;font-family:inherit;box-sizing:border-box}
        .form-control:focus{outline:none;border-color:var(--primary)}
        .btn{padding:10px 16px;border-radius:8px;font-weight:600;font-size:.9rem;cursor:pointer;border:none;display:inline-flex;align-items:center;justify-content:center;gap:6px;box-sizing:border-box;text-decoration:none;white-space:nowrap}
        .btn-primary{background:var(--primary);color:#fff}.btn-primary:hover{background:#19376D}
        .msg-alert{padding:14px 20px;border-radius:8px;background:rgba(11,36,71,0.04);color:#0B2447;margin-bottom:20px;border:1px solid rgba(11,36,71,0.04)}
        .mobile-menu-btn{display:none;background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-dark);padding:4px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:90}
        .edit-grid{display:grid;grid-template-columns:350px 1fr;gap:24px}

        @media(max-width:1024px){.edit-grid{grid-template-columns:1fr}}
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}
            .sidebar-overlay.show{display:block}.main-content{margin-left:0}
            .topbar{height:56px;padding:0 12px;justify-content:space-between}
            .mobile-menu-btn{display:block}.content-area{padding:12px}
            .page-header{flex-direction:column;align-items:flex-start}.page-header h2{font-size:1.4rem}
            .panel{padding:14px}.panel h3{font-size:1rem}
            .sub-links{gap:4px;margin-bottom:14px}.sub-link{padding:5px 8px;font-size:.78rem}
            .form-group{margin-bottom:12px}.form-group label{font-size:.82rem}.form-control{padding:9px 12px;font-size:.85rem}
            .btn{width:100%;text-align:center}
        }
        @media(max-width:480px){.content-area{padding:8px}.panel{padding:10px}.page-header h2{font-size:1.2rem}}
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
                <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
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

            <div class="edit-grid" style="<?php echo $edit_q ? '' : 'grid-template-columns:1fr'; ?>">
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
                        <a href="questions_manager.php" class="btn" style="display:block; text-align:center; margin-top:10px; background:#F8FAFC; text-decoration:none; color:var(--text-color); padding:10px; border-radius:8px;">Cancel Edit</a>
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
                                        <span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65); margin-bottom:4px; display:inline-block;"><?php echo ucfirst($q['question_category']); ?></span><br>
                                        <?php if($q['status'] == 'open'): ?><span class="badge" style="background:rgba(11,36,71,0.04);color:#0B2447;">Open</span>
                                        <?php elseif($q['status'] == 'answered'): ?><span class="badge" style="background:rgba(11,36,71,0.06);color:#19376D;">Answered</span>
                                        <?php else: ?><span class="badge" style="background:rgba(15,23,42,0.06);color:#0F172A;">Closed</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="stats-micro">
                                            <span title="Views"><i class="ph ph-eye" style="color:#19376D;"></i> <?php echo number_format($q['views']); ?></span>
                                            <span title="Answers"><i class="ph ph-chat-text" style="color:#0B2447;"></i> <?php echo number_format($q['answer_count']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($q['is_featured']): ?>
                                        <span class="badge" style="background:rgba(11,36,71,0.06);color:#0F172A;"><i class="ph-fill ph-star"></i> Featured</span>
                                        <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.8rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?edit_id=<?php echo $q['id']; ?>" style="color:var(--primary); margin-right:8px;"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?delete_id=<?php echo $q['id']; ?>" onclick="return confirm('Delete question permanently?');" style="color:#0F172A;"><i class="ph ph-trash"></i></a>
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
