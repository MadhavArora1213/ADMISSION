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
    
    // Skills
    $skills_json = null;
    if (isset($_POST['skill']) && is_array($_POST['skill'])) {
        $skills = array_filter(array_map('trim', $_POST['skill']));
        if (!empty($skills)) {
            $skills_json = json_encode(array_values($skills));
        }
    } elseif (isset($_POST['skills_required'])) {
        $skills_json = $_POST['skills_required'] ?: null;
    }

    // Top Companies
    $companies_json = null;
    if (isset($_POST['company_name']) && is_array($_POST['company_name'])) {
        $companies = [];
        $upload_dir = '../uploads/companies/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        foreach ($_POST['company_name'] as $index => $name) {
            $name = trim($name);
            $logo = trim($_POST['company_logo'][$index] ?? '');
            
            if (isset($_FILES['company_logo_file']['name'][$index]) && $_FILES['company_logo_file']['error'][$index] == 0) {
                $tmp_name = $_FILES['company_logo_file']['tmp_name'][$index];
                $file_name = time() . '_' . mt_rand(100, 999) . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_FILES['company_logo_file']['name'][$index]);
                if (move_uploaded_file($tmp_name, $upload_dir . $file_name)) {
                    $logo = 'uploads/companies/' . $file_name;
                    require_once __DIR__ . '/upload_sync.php';
                    sync_to_github('uploads/companies/' . $file_name);
                }
            }
            if ($name !== '') {
                $companies[] = ['name' => $name, 'logo' => $logo];
            }
        }
        if (!empty($companies)) $companies_json = json_encode($companies);
    } elseif (isset($_POST['top_companies'])) {
        $companies_json = $_POST['top_companies'] ?: null;
    }

    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE course_career_paths SET job_role = ?, avg_salary_lpa = ?, top_companies = ?, growth_outlook = ?, skills_required = ?, fresher_salary_lpa = ?, experienced_salary_lpa = ? WHERE id = ?");
        $stmt->execute([$_POST['job_role'], $_POST['avg_salary_lpa']?:null, $companies_json, $_POST['growth_outlook']?:null, $skills_json, $_POST['fresher_salary_lpa']?:null, $_POST['experienced_salary_lpa']?:null, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO course_career_paths (id, course_id, job_role, avg_salary_lpa, top_companies, growth_outlook, skills_required, fresher_salary_lpa, experienced_salary_lpa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $course_id, $_POST['job_role'], $_POST['avg_salary_lpa']?:null, $companies_json, $_POST['growth_outlook']?:null, $skills_json, $_POST['fresher_salary_lpa']?:null, $_POST['experienced_salary_lpa']?:null]);
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
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; padding-bottom: 60px; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 1000px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 12px; }
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; -webkit-overflow-scrolling: touch; scrollbar-width: thin; flex-wrap: nowrap; }
        .tabs-nav::-webkit-scrollbar { height: 5px; }
        .tabs-nav::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 3px; }
        .tabs-nav::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 3px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; flex-shrink: 0; font-size: 0.88rem; text-decoration: none; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        .form-section { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); margin-bottom: 24px; overflow-x: auto; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; }
        .form-control { width: 100%; min-width: 0; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; min-width: 400px; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; }
        .action-links { display: flex; gap: 8px; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #F8FAFC; color: var(--text-dark); border: 1px solid var(--border-color); text-decoration: none; }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.delete:hover { background: #0F172A; color: white; border-color: #0F172A; }
        .msg-alert { padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 24px; border: 1px solid rgba(11,36,71,0.04); }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; background: rgba(11,36,71,0.06); color: #19376D; border: 1px solid rgba(11,36,71,0.06); white-space: nowrap; }
        .btn-primary { background: var(--primary); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap; box-sizing: border-box; }
        .btn-primary:hover { background: #0B2447; }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }
        .skill-row, .company-row { flex-wrap: wrap; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .topbar { height: 56px; padding: 0 12px; justify-content: space-between; }
            .mobile-menu-btn { display: block; }
            .content-area { padding: 12px; }
            .page-header h2 { font-size: 1.3rem; gap: 8px; }
            .form-section { padding: 16px; }
            .form-grid { grid-template-columns: 1fr; gap: 12px; }
            .form-group { margin-bottom: 14px; }
            .form-group label { font-size: 0.85rem; margin-bottom: 6px; }
            .form-control { padding: 9px 12px; font-size: 0.9rem; }
            .tabs-nav { gap: 4px; margin-bottom: 16px; }
            .tab-link { padding: 6px 12px; font-size: 0.78rem; }
            .btn-primary { width: 100%; text-align: center; justify-content: center; }
            .skill-row, .company-row { flex-direction: column; align-items: stretch; }
            .skill-row .form-control, .company-row .form-control { width: 100% !important; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
            .form-section { padding: 12px; border-radius: 12px; }
            .page-header h2 { font-size: 1.1rem; }
            .tabs-nav { gap: 3px; }
            .tab-link { padding: 5px 10px; font-size: 0.74rem; }
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

                <form action="" method="POST" enctype="multipart/form-data" class="form-section">
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
                            <label>Skills Required</label>
                            <div id="skills-container">
                                <?php 
                                $skills = [];
                                if ($edit && !empty($edit['skills_required'])) {
                                    $skills = json_decode($edit['skills_required'] ?? '[]', true);
                                }
                                if (!$skills || !is_array($skills)) $skills = [];
                                if (empty($skills)) $skills[] = '';
                                foreach ($skills as $skill): 
                                ?>
                                <div class="skill-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
                                    <input type="text" name="skill[]" class="form-control" placeholder="Skill (e.g. Java)" value="<?php echo htmlspecialchars($skill); ?>" style="flex: 1;">
                                    <button type="button" class="btn btn-danger remove-skill" style="padding: 0 15px; border-radius: 8px; border: 1px solid rgba(15,23,42,0.06); background: rgba(15,23,42,0.06); color: #0B2447; cursor: pointer;">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-secondary" id="add-skill" style="margin-top: 10px; padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border-color); background: #fff; cursor: pointer;">+ Add Skill</button>
                        </div>
                        <div class="form-group full">
                            <label>Top Companies</label>
                            <div id="companies-container">
                                <?php 
                                $companies = [];
                                if ($edit && !empty($edit['top_companies'])) {
                                    $companies = json_decode($edit['top_companies'] ?? '[]', true);
                                }
                                if (!$companies || !is_array($companies)) $companies = [];
                                if (empty($companies)) $companies[] = ['name' => '', 'logo' => ''];
                                foreach ($companies as $company): 
                                ?>
                                <div class="company-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                    <input type="text" name="company_name[]" class="form-control" placeholder="Company Name" value="<?php echo htmlspecialchars($company['name'] ?? ''); ?>" style="flex: 1;">
                                    <input type="hidden" name="company_logo[]" value="<?php echo htmlspecialchars($company['logo'] ?? ''); ?>">
                                    <input type="file" name="company_logo_file[]" class="form-control" style="flex: 1;" accept="image/*">
                                    <?php if(!empty($company['logo'])): ?>
                                    <img src="../<?php echo htmlspecialchars($company['logo']); ?>" alt="logo" style="height: 30px; object-fit: contain;">
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-danger remove-company" style="padding: 0 15px; height: 100%; border-radius: 8px; border: 1px solid rgba(15,23,42,0.06); background: rgba(15,23,42,0.06); color: #0B2447; cursor: pointer;">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-secondary" id="add-company" style="margin-top: 10px; padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border-color); background: #fff; cursor: pointer;">+ Add Company</button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Skills dynamic list
            $('#add-skill').click(function() {
                var row = `
                <div class="skill-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
                    <input type="text" name="skill[]" class="form-control" placeholder="Skill (e.g. Java)" style="flex: 1;">
                    <button type="button" class="btn btn-danger remove-skill" style="padding: 0 15px; border-radius: 8px; border: 1px solid rgba(15,23,42,0.06); background: rgba(15,23,42,0.06); color: #0B2447; cursor: pointer;">&times;</button>
                </div>`;
                $('#skills-container').append(row);
            });

            $(document).on('click', '.remove-skill', function() {
                if ($('.skill-row').length > 1) {
                    $(this).closest('.skill-row').remove();
                } else {
                    $(this).closest('.skill-row').find('input').val('');
                }
            });

            // Top Companies dynamic list
            $('#add-company').click(function() {
                var row = `
                <div class="company-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                    <input type="text" name="company_name[]" class="form-control" placeholder="Company Name" style="flex: 1;">
                    <input type="hidden" name="company_logo[]" value="">
                    <input type="file" name="company_logo_file[]" class="form-control" style="flex: 1;" accept="image/*">
                    <button type="button" class="btn btn-danger remove-company" style="padding: 0 15px; height: 100%; border-radius: 8px; border: 1px solid rgba(15,23,42,0.06); background: rgba(15,23,42,0.06); color: #0B2447; cursor: pointer;">&times;</button>
                </div>`;
                $('#companies-container').append(row);
            });

            $(document).on('click', '.remove-company', function() {
                if ($('.company-row').length > 1) {
                    $(this).closest('.company-row').remove();
                } else {
                    $(this).closest('.company-row').find('input').val('');
                }
            });
        });
    </script>
</body>
</html>
