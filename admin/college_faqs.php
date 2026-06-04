<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

$college_id = isset($_GET['college_id']) ? $_GET['college_id'] : null;
if (!$college_id) { header('Location: colleges.php'); exit; }

$stmt = $pdo->prepare("SELECT id, name FROM colleges WHERE id = ?");
$stmt->execute([$college_id]);
$college = $stmt->fetch();
if (!$college) { header('Location: colleges.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO college_faqs (id, college_id, question_text, answer_text, category, sort_order, is_active) 
                VALUES (UUID(), ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $college_id,
                $_POST['question'],
                $_POST['answer'],
                $_POST['category'] ?: null,
                $_POST['sort_order'] ?: 0,
                isset($_POST['is_active']) ? 1 : 0
            ]);
            header("Location: college_faqs.php?college_id=$college_id&msg=added");
            exit;
        } catch (Exception $e) {
            $error = "Error adding FAQ: " . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM college_faqs WHERE id = ? AND college_id = ?");
        $stmt->execute([$_POST['f_id'], $college_id]);
        header("Location: college_faqs.php?college_id=$college_id&msg=deleted");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM college_faqs WHERE college_id = ? ORDER BY sort_order ASC, question_text ASC");
$stmt->execute([$college_id]);
$faqs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage FAQs</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none;}
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; min-width: 0; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .user-profile { display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .content-area { padding: 32px; max-width: 1200px; margin: 0 auto; width: 100%; }
        .page-header { margin-bottom: 24px; }
        .page-header h2 { font-size: 1.8rem; font-weight: 700; display:flex; align-items:center; gap: 12px; flex-wrap: wrap; }
        
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm); overflow-x: auto; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; box-sizing: border-box; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 16px; min-width: 600px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; }
        
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        @media (max-width: 768px) { 
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .topbar { justify-content: space-between; padding: 0 16px; }
            .mobile-menu-btn { display: block; }
            .content-area { padding: 16px; }
            .form-grid { grid-template-columns: 1fr; }
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
                    <h2><a href="colleges.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> Edit College: <?php echo htmlspecialchars($college['name']); ?></h2>
                </div>

                <div class="tabs-nav">
                    <a href="college_form.php?id=<?php echo $college_id; ?>&tab=identity" class="tab-link">Identity & Contact</a>
                    <a href="college_form.php?id=<?php echo $college_id; ?>&tab=about" class="tab-link">About & Amenities</a>
                    <a href="college_form.php?id=<?php echo $college_id; ?>&tab=seo" class="tab-link">SEO & Publish</a>
                    <a href="college_courses.php?college_id=<?php echo $college_id; ?>" class="tab-link">Courses & Fees</a>
                    <a href="college_placements.php?college_id=<?php echo $college_id; ?>" class="tab-link">Placements</a>
                    <a href="college_cutoffs.php?college_id=<?php echo $college_id; ?>" class="tab-link">Cutoffs</a>
                    <a href="college_media.php?college_id=<?php echo $college_id; ?>" class="tab-link">Media & Gallery</a>
                    <a href="college_faqs.php?college_id=<?php echo $college_id; ?>" class="tab-link active">FAQs</a>
                    <a href="college_faculty.php?college_id=<?php echo $college_id; ?>" class="tab-link">Faculty</a>
                    <a href="college_scholarships.php?college_id=<?php echo $college_id; ?>" class="tab-link">Scholarships</a>
                </div>

                <?php if(isset($_GET['msg'])): ?>
                    <div style="padding: 16px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 24px; border: 1px solid #bbf7d0;">Action completed successfully!</div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div style="padding: 16px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 24px; border: 1px solid #fecaca;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="panel">
                    <h3><i class="ph ph-plus-circle"></i> Add FAQ</h3>
                    <form action="" method="POST" style="margin-top:16px;">
                        <input type="hidden" name="action" value="add">
                        <div class="form-grid">
                            <div class="form-group full"><label>Question</label><textarea name="question" class="form-control" rows="2" required></textarea></div>
                            <div class="form-group full"><label>Answer</label><textarea name="answer" class="form-control" rows="4" required></textarea></div>
                            <div class="form-group"><label>Category</label><input type="text" name="category" class="form-control" placeholder="e.g. Admission, Hostel"></div>
                            <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-control" value="0"></div>
                            <div class="form-group checkbox-group full">
                                <input type="checkbox" name="is_active" checked> <label style="display:inline;">Active (Visible on website)</label>
                            </div>
                        </div>
                        <div style="text-align: right; margin-top:16px;"><button type="submit" class="btn btn-primary">Add FAQ</button></div>
                    </form>
                </div>

                <div class="panel">
                    <h3><i class="ph ph-list"></i> FAQ List</h3>
                    <?php if(empty($faqs)): ?>
                        <p style="color:var(--text-muted); margin-top:16px;">No FAQs added yet.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table>
                                <thead><tr><th>Question</th><th>Category</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach($faqs as $f): ?>
                                    <tr>
                                        <td style="font-weight:600; width:50%;"><?php echo htmlspecialchars($f['question_text']); ?></td>
                                        <td><?php echo htmlspecialchars($f['category']?:'-'); ?></td>
                                        <td><?php echo htmlspecialchars($f['sort_order']); ?></td>
                                        <td><?php echo $f['is_active'] ? '<span style="color:#166534;background:#dcfce7;padding:2px 8px;border-radius:12px;font-size:0.8rem;">Active</span>' : '<span style="color:#991b1b;background:#fee2e2;padding:2px 8px;border-radius:12px;font-size:0.8rem;">Inactive</span>'; ?></td>
                                        <td>
                                            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                                                <input type="hidden" name="action" value="delete"><input type="hidden" name="f_id" value="<?php echo $f['id']; ?>">
                                                <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer;"><i class="ph ph-trash" style="font-size:1.2rem;"></i></button>
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
