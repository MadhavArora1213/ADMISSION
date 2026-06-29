<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$is_edit = $id !== null;
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'basic';
$error = '';

$catsStmt = $pdo->query("SELECT id, category_name FROM course_categories ORDER BY category_name ASC");
$categories = $catsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'basic') {
    $slug = !empty($_POST['course_slug']) ? $_POST['course_slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['course_name'])));
    
    $slugCheckQ = "SELECT id FROM courses WHERE course_slug = :slug";
    if ($is_edit) $slugCheckQ .= " AND id != :id";
    $slugCheckStmt = $pdo->prepare($slugCheckQ);
    $slugCheckParams = ['slug' => $slug];
    if ($is_edit) $slugCheckParams['id'] = $id;
    $slugCheckStmt->execute($slugCheckParams);
    
    if ($slugCheckStmt->rowCount() > 0) {
        $error = "The slug '$slug' is already in use.";
    } else {
        try {
            $data = [
                'course_name' => $_POST['course_name'],
                'course_slug' => $slug,
                'course_level' => $_POST['course_level'] ?: null,
                'category_id' => $_POST['category_id'] ?: null,
                'duration_years' => $_POST['duration_years'] ?: null,
                'is_popular' => isset($_POST['is_popular']) ? 1 : 0,
                'status' => $_POST['status'] ?: 'active'
            ];

            if ($is_edit) {
                $fields = [];
                foreach ($data as $key => $val) { $fields[] = "$key = :$key"; }
                $sql = "UPDATE courses SET " . implode(", ", $fields) . " WHERE id = :id";
                $data['id'] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
            } else {
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
                $data['id'] = $id;
                $keys = array_keys($data);
                $sql = "INSERT INTO courses (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
            }
            header("Location: course_form.php?id=$id&tab=basic&msg=saved");
            exit;
        } catch (Exception $e) { $error = "Error saving: " . $e->getMessage(); }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'details') {
    try {
        $data = [
            'id' => $id,
            'description' => $_POST['description'] ?: null,
            'eligibility' => $_POST['eligibility'] ?: null,
            'career_scope' => $_POST['career_scope'] ?: null
        ];
        $fields = [];
        foreach($data as $key => $val) { if($key=='id') continue; $fields[] = "$key = :$key"; }
        $stmt = $pdo->prepare("UPDATE courses SET " . implode(', ', $fields) . " WHERE id = :id");
        $stmt->execute($data);
        header("Location: course_form.php?id=$id&tab=details&msg=saved");
        exit;
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'salary') {
    try {
        $top_recruiters_json = null;
        if (isset($_POST['recruiter_name']) && is_array($_POST['recruiter_name'])) {
            $recruiters = [];
            $upload_dir = '../uploads/recruiters/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($_POST['recruiter_name'] as $index => $name) {
                $name = trim($name);
                $logo = trim($_POST['recruiter_logo'][$index] ?? '');
                
                if (isset($_FILES['recruiter_logo_file']['name'][$index]) && $_FILES['recruiter_logo_file']['error'][$index] == 0) {
                    $tmp_name = $_FILES['recruiter_logo_file']['tmp_name'][$index];
                    $file_name = time() . '_' . mt_rand(100, 999) . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_FILES['recruiter_logo_file']['name'][$index]);
                    $target_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $logo = 'uploads/recruiters/' . $file_name;
                    }
                }

                if ($name !== '') {
                    $recruiters[] = ['name' => $name, 'logo' => $logo];
                }
            }
            if (!empty($recruiters)) {
                $top_recruiters_json = json_encode($recruiters);
            }
        } elseif (isset($_POST['top_recruiters'])) {
            $top_recruiters_json = $_POST['top_recruiters'] ?: null;
        }

        $data = [
            'id' => $id,
            'avg_salary_lpa' => $_POST['avg_salary_lpa'] ?: null,
            'salary_range_min' => $_POST['salary_range_min'] ?: null,
            'salary_range_max' => $_POST['salary_range_max'] ?: null,
            'top_recruiters' => $top_recruiters_json
        ];
        $fields = [];
        foreach($data as $key => $val) { if($key=='id') continue; $fields[] = "$key = :$key"; }
        $stmt = $pdo->prepare("UPDATE courses SET " . implode(', ', $fields) . " WHERE id = :id");
        $stmt->execute($data);
        header("Location: course_form.php?id=$id&tab=salary&msg=saved");
        exit;
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
}

$course = [];
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$course) { header('Location: courses.php'); exit; }
}

function getValue($arr, $key, $default = '') {
    return isset($arr[$key]) ? htmlspecialchars($arr[$key]) : $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Course' : 'Add New Course'; ?> | AdmissionSeason Admin</title>
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
        .form-section { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); margin-bottom: 24px; overflow-x: auto; }
        .form-section h3 { font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 24px; display: flex; align-items: center; gap: 8px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; }
        .form-control { width: 100%; min-width: 0; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        .checkbox-group { display: flex; align-items: center; gap: 8px; margin-top: 32px; flex-wrap: wrap; }
        .error-alert, .msg-alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; border: 1px solid; }
        .error-alert { background: rgba(15,23,42,0.06); color: #0B2447; border-color: rgba(15,23,42,0.06); }
        .msg-alert { background: rgba(11,36,71,0.04); color: #0B2447; border-color: rgba(11,36,71,0.04); }
        .form-actions { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
        .form-actions .btn { white-space: nowrap; box-sizing: border-box; }
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; -webkit-overflow-scrolling: touch; scrollbar-width: thin; flex-wrap: nowrap; }
        .tabs-nav::-webkit-scrollbar { height: 5px; }
        .tabs-nav::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 3px; }
        .tabs-nav::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 3px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; flex-shrink: 0; font-size: 0.88rem; text-decoration: none; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        .tab-link.disabled { opacity: 0.5; cursor: not-allowed; }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }
        .recruiter-row { flex-wrap: wrap; }

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
            .form-section h3 { font-size: 1rem; margin-bottom: 16px; }
            .form-grid { grid-template-columns: 1fr; gap: 12px; }
            .form-group { margin-bottom: 14px; }
            .form-group label { font-size: 0.85rem; margin-bottom: 6px; }
            .form-control { padding: 10px 12px; font-size: 0.9rem; }
            .form-actions { flex-direction: column; gap: 10px; }
            .form-actions .btn { width: 100%; text-align: center; padding: 14px 16px; justify-content: center; }
            .tabs-nav { gap: 4px; margin-bottom: 16px; }
            .tab-link { padding: 6px 12px; font-size: 0.78rem; }
            .checkbox-group { margin-top: 16px; }
            .recruiter-row { flex-direction: column; align-items: stretch; }
            .recruiter-row .form-control { width: 100% !important; }
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
                    <h2><a href="courses.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> <?php echo $is_edit ? 'Edit Course: ' . htmlspecialchars($course['course_name']) : 'Add New Course'; ?></h2>
                </div>

                <?php if($is_edit): ?>
                <div class="tabs-nav">
                    <a href="?id=<?php echo $id; ?>&tab=basic" class="tab-link <?php echo $current_tab=='basic'?'active':''; ?>">Basic Info</a>
                    <a href="?id=<?php echo $id; ?>&tab=details" class="tab-link <?php echo $current_tab=='details'?'active':''; ?>">Descriptions & Scope</a>
                    <a href="?id=<?php echo $id; ?>&tab=salary" class="tab-link <?php echo $current_tab=='salary'?'active':''; ?>">Salary & Recruiters</a>
                    <a href="course_specializations.php?course_id=<?php echo $id; ?>" class="tab-link">Specializations</a>
                    <a href="course_career_paths.php?course_id=<?php echo $id; ?>" class="tab-link">Career Paths</a>
                </div>
                <?php else: ?>
                <div class="tabs-nav">
                    <span class="tab-link active">Basic Info</span>
                    <span class="tab-link disabled">Descriptions & Scope</span>
                    <span class="tab-link disabled">Salary & Recruiters</span>
                    <span class="tab-link disabled">Specializations</span>
                    <span class="tab-link disabled">Career Paths</span>
                </div>
                <?php endif; ?>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> Details saved successfully!</div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="error-alert"><i class="ph-fill ph-warning-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if($current_tab == 'basic'): ?>
                <form action="" method="POST">
                    <div class="form-section">
                        <h3><i class="ph ph-info"></i> Basic Details</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Course Name *</label>
                                <input type="text" name="course_name" class="form-control" required value="<?php echo getValue($course, 'course_name'); ?>">
                            </div>
                            <div class="form-group">
                                <label>URL Slug (Leave blank to auto-generate)</label>
                                <input type="text" name="course_slug" class="form-control" value="<?php echo getValue($course, 'course_slug'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Course Level</label>
                                <select name="course_level" class="form-control">
                                    <option value="">Select Level</option>
                                    <option value="UG" <?php echo getValue($course, 'course_level') == 'UG' ? 'selected' : ''; ?>>UG (Undergraduate)</option>
                                    <option value="PG" <?php echo getValue($course, 'course_level') == 'PG' ? 'selected' : ''; ?>>PG (Postgraduate)</option>
                                    <option value="Diploma" <?php echo getValue($course, 'course_level') == 'Diploma' ? 'selected' : ''; ?>>Diploma</option>
                                    <option value="Certificate" <?php echo getValue($course, 'course_level') == 'Certificate' ? 'selected' : ''; ?>>Certificate</option>
                                    <option value="PhD" <?php echo getValue($course, 'course_level') == 'PhD' ? 'selected' : ''; ?>>PhD</option>
                                    <option value="Integrated" <?php echo getValue($course, 'course_level') == 'Integrated' ? 'selected' : ''; ?>>Integrated</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id" class="form-control">
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo getValue($course, 'category_id') == $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['category_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Duration (Years)</label>
                                <input type="number" step="0.5" name="duration_years" class="form-control" value="<?php echo getValue($course, 'duration_years'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" <?php echo getValue($course, 'status') == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo getValue($course, 'status') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="form-group checkbox-group" style="grid-column: 1 / -1;">
                                <input type="checkbox" id="is_popular" name="is_popular" <?php echo !empty($course['is_popular']) ? 'checked' : ''; ?>>
                                <label for="is_popular">Is Popular Course?</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Basic Details</button>
                    </div>
                </form>

                <?php elseif($current_tab == 'details'): ?>
                <form action="" method="POST">
                    <div class="form-section">
                        <h3><i class="ph ph-article"></i> Descriptions & Scope</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Description (Rich HTML)</label>
                                <textarea name="description" class="form-control" rows="8"><?php echo getValue($course, 'description'); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Eligibility</label>
                                <textarea name="eligibility" class="form-control" rows="4"><?php echo getValue($course, 'eligibility'); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Career Scope</label>
                                <textarea name="career_scope" class="form-control" rows="4"><?php echo getValue($course, 'career_scope'); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Details</button>
                    </div>
                </form>

                <?php elseif($current_tab == 'salary'): ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3><i class="ph ph-money"></i> Salary & Recruiters</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Average Salary (LPA)</label>
                                <input type="number" step="0.01" name="avg_salary_lpa" class="form-control" value="<?php echo getValue($course, 'avg_salary_lpa'); ?>">
                            </div>
                            <div class="form-group"></div>
                            <div class="form-group">
                                <label>Salary Range Min (LPA)</label>
                                <input type="number" step="0.01" name="salary_range_min" class="form-control" value="<?php echo getValue($course, 'salary_range_min'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Salary Range Max (LPA)</label>
                                <input type="number" step="0.01" name="salary_range_max" class="form-control" value="<?php echo getValue($course, 'salary_range_max'); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Top Recruiters</label>
                                <div id="recruiters-container">
                                    <?php 
                                    $recruiters = json_decode($course['top_recruiters'] ?? '[]', true);
                                    if (!$recruiters || !is_array($recruiters)) $recruiters = [];
                                    if (empty($recruiters)) $recruiters[] = ['name' => '', 'logo' => ''];
                                    foreach ($recruiters as $recruiter): 
                                    ?>
                                    <div class="recruiter-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                        <input type="text" name="recruiter_name[]" class="form-control" placeholder="Company Name" value="<?php echo htmlspecialchars($recruiter['name'] ?? ''); ?>" style="flex: 1;">
                                        <input type="hidden" name="recruiter_logo[]" value="<?php echo htmlspecialchars($recruiter['logo'] ?? ''); ?>">
                                        <input type="file" name="recruiter_logo_file[]" class="form-control" style="flex: 1;" accept="image/*">
                                        <?php if(!empty($recruiter['logo'])): ?>
                                        <img src="../<?php echo htmlspecialchars($recruiter['logo']); ?>" alt="logo" style="height: 30px; object-fit: contain;">
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-danger remove-recruiter" style="padding: 0 15px; height: 100%; border-radius: 8px; border: 1px solid rgba(15,23,42,0.06); background: rgba(15,23,42,0.06); color: #0B2447; cursor: pointer;">&times;</button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-secondary" id="add-recruiter" style="margin-top: 10px; padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border-color); background: #fff; cursor: pointer;">+ Add Recruiter</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Salary & Recruiters</button>
                    </div>
                </form>
                <?php endif; ?>

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
    <!-- jQuery and Trumbowyg -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
    <script>
        $(document).ready(function() {
            if ($('textarea[name="description"]').length) {
                $('textarea[name="description"]').trumbowyg({
                    semantic: true,
                    removeformatPasted: true,
                    resetCss: true
                });
            }
            
            // Top Recruiters dynamic list
            $('#add-recruiter').click(function() {
                var row = `
                <div class="recruiter-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                    <input type="text" name="recruiter_name[]" class="form-control" placeholder="Company Name" style="flex: 1;">
                    <input type="hidden" name="recruiter_logo[]" value="">
                    <input type="file" name="recruiter_logo_file[]" class="form-control" style="flex: 1;" accept="image/*">
                    <button type="button" class="btn btn-danger remove-recruiter" style="padding: 0 15px; height: 100%; border-radius: 8px; border: 1px solid rgba(15,23,42,0.06); background: rgba(15,23,42,0.06); color: #0B2447; cursor: pointer;">&times;</button>
                </div>`;
                $('#recruiters-container').append(row);
            });

            $(document).on('click', '.remove-recruiter', function() {
                if ($('.recruiter-row').length > 1) {
                    $(this).closest('.recruiter-row').remove();
                } else {
                    $(this).closest('.recruiter-row').find('input').val('');
                }
            });
        });
    </script>
</body>
</html>
