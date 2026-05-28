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
    
    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE course_career_paths SET job_role = ?, avg_salary_lpa = ?, top_companies = ?, growth_outlook = ?, skills_required = ?, fresher_salary_lpa = ?, experienced_salary_lpa = ? WHERE id = ?");
        $stmt->execute([$_POST['job_role'], $_POST['avg_salary_lpa']?:null, $_POST['top_companies']?:null, $_POST['growth_outlook']?:null, $_POST['skills_required']?:null, $_POST['fresher_salary_lpa']?:null, $_POST['experienced_salary_lpa']?:null, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO course_career_paths (id, course_id, job_role, avg_salary_lpa, top_companies, growth_outlook, skills_required, fresher_salary_lpa, experienced_salary_lpa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $course_id, $_POST['job_role'], $_POST['avg_salary_lpa']?:null, $_POST['top_companies']?:null, $_POST['growth_outlook']?:null, $_POST['skills_required']?:null, $_POST['fresher_salary_lpa']?:null, $_POST['experienced_salary_lpa']?:null]);
    }
    header("Location: course_career_paths.php?course_id=$course_id&msg=saved");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM course_career_paths WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: course_career_paths.php?course_id=$course_id&msg=deleted");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM course_career_paths WHERE course_id = ? ORDER BY job_role ASC");
$stmt->execute([$course_id]);
$list = $stmt->fetchAll();

$edit = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM course_career_paths WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Paths | AdmissionSeason Admin</title>
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
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--text-dark); border: 1px solid var(--border-color); }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.delete:hover { background: #dc2626; color: white; border-color: #dc2626; }
        .msg-alert { padding: 16px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 24px; border: 1px solid #bbf7d0; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }
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
                    <h2><a href="course_form.php?id=<?php echo $course_id; ?>&tab=basic" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> Career Paths: <?php echo htmlspecialchars($course['course_name']); ?></h2>
                </div>

                <div class="tabs-nav">
                    <a href="course_form.php?id=<?php echo $course_id; ?>&tab=basic" class="tab-link">Basic Info</a>
                    <a href="course_form.php?id=<?php echo $course_id; ?>&tab=details" class="tab-link">Descriptions & Scope</a>
                    <a href="course_form.php?id=<?php echo $course_id; ?>&tab=salary" class="tab-link">Salary & Recruiters</a>
                    <a href="course_specializations.php?course_id=<?php echo $course_id; ?>" class="tab-link">Specializations</a>
                    <a href="course_career_paths.php?course_id=<?php echo $course_id; ?>" class="tab-link active">Career Paths</a>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> Career path saved successfully!</div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> Career path deleted.</div>
                <?php endif; ?>

                <form action="" method="POST" class="form-section">
                    <h3><?php echo $edit ? 'Edit Career Path' : 'Add Career Path'; ?></h3>
                    <input type="hidden" name="action" value="save">
                    <?php if($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Job Role *</label>
                            <input type="text" name="job_role" class="form-control" required value="<?php echo $edit ? htmlspecialchars($edit['job_role']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Average Salary (LPA)</label>
                            <input type="number" step="0.01" name="avg_salary_lpa" class="form-control" value="<?php echo $edit ? $edit['avg_salary_lpa'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Growth Outlook</label>
                            <select name="growth_outlook" class="form-control">
                                <option value="">-- Select --</option>
                                <option value="high" <?php echo ($edit && $edit['growth_outlook']=='high') ? 'selected' : ''; ?>>High</option>
                                <option value="medium" <?php echo ($edit && $edit['growth_outlook']=='medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="low" <?php echo ($edit && $edit['growth_outlook']=='low') ? 'selected' : ''; ?>>Low</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fresher Salary (LPA)</label>
                            <input type="number" step="0.01" name="fresher_salary_lpa" class="form-control" value="<?php echo $edit ? $edit['fresher_salary_lpa'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Experienced Salary (LPA)</label>
                            <input type="number" step="0.01" name="experienced_salary_lpa" class="form-control" value="<?php echo $edit ? $edit['experienced_salary_lpa'] : ''; ?>">
                        </div>
                        <div class="form-group full">
                            <label>Skills Required (JSON Array)</label>
                            <textarea name="skills_required" class="form-control" rows="2" placeholder='["Java", "Python"]'><?php echo $edit ? htmlspecialchars($edit['skills_required']) : ''; ?></textarea>
                        </div>
                        <div class="form-group full">
                            <label>Top Companies (JSON Array [{name, logo}])</label>
                            <textarea name="top_companies" class="form-control" rows="2" placeholder='[{"name":"TCS"}]'><?php echo $edit ? htmlspecialchars($edit['top_companies']) : ''; ?></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 16px;"><?php echo $edit ? 'Update' : 'Add'; ?> Career Path</button>
                    <?php if($edit): ?>
                        <a href="?course_id=<?php echo $course_id; ?>" class="btn" style="margin-left: 8px;">Cancel</a>
                    <?php endif; ?>
                </form>

                <div class="form-section">
                    <h3>Existing Career Paths</h3>
                    <?php if(empty($list)): ?>
                        <p style="color:var(--text-muted);">No career paths added.</p>
                    <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <tr>
                                <th>Job Role</th>
                                <th>Avg Salary</th>
                                <th>Growth</th>
                                <th>Action</th>
                            </tr>
                            <?php foreach($list as $d): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--primary);">
                                    <?php echo htmlspecialchars($d['job_role']); ?>
                                </td>
                                <td><?php echo $d['avg_salary_lpa'] ? $d['avg_salary_lpa'] . ' LPA' : '-'; ?></td>
                                <td><?php echo $d['growth_outlook'] ? '<span class="badge">'.ucfirst($d['growth_outlook']).'</span>' : '-'; ?></td>
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
