<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

$id = $_GET['id'] ?? '';
$career = null;
$error = '';
$success = '';

if ($id !== '') {
    // Fetch existing details for edit
    $stmt = $pdo->prepare("SELECT * FROM careers WHERE id = ?");
    $stmt->execute([$id]);
    $career = $stmt->fetch();
    if (!$career) {
        header('Location: careers.php');
        exit;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $stream = $_POST['stream'] ?? 'Science';
    $sub_stream = trim($_POST['sub_stream'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $job_profile = trim($_POST['job_profile'] ?? '');
    $how_to_get_there = trim($_POST['how_to_get_there'] ?? '');
    $salary_range = trim($_POST['salary_range'] ?? '');
    $skills_required = trim($_POST['skills_required'] ?? '');
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;
    $image_url = trim($_POST['image_url'] ?? '');

    // Basic Validation
    if (empty($name) || empty($sub_stream)) {
        $error = 'Career Name and Interest Category (Sub-stream) are required.';
    } else {
        // Generate Slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        
        try {
            if ($career) {
                // Update
                $stmt = $pdo->prepare("
                    UPDATE careers SET 
                        name = :name, slug = :slug, stream = :stream, sub_stream = :sub_stream, 
                        short_description = :short_description, job_profile = :job_profile, 
                        how_to_get_there = :how_to_get_there, salary_range = :salary_range, 
                        skills_required = :skills_required, is_popular = :is_popular, image_url = :image_url
                    WHERE id = :id
                ");
                $stmt->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'stream' => $stream,
                    'sub_stream' => $sub_stream,
                    'short_description' => $short_description,
                    'job_profile' => $job_profile,
                    'how_to_get_there' => $how_to_get_there,
                    'salary_range' => $salary_range,
                    'skills_required' => $skills_required,
                    'is_popular' => $is_popular,
                    'image_url' => $image_url,
                    'id' => $id
                ]);
                header('Location: careers.php?msg=updated');
                exit;
            } else {
                // Insert New
                $newId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                    mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                    mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));

                $stmt = $pdo->prepare("
                    INSERT INTO careers (
                        id, name, slug, stream, sub_stream, short_description, job_profile, how_to_get_there, salary_range, skills_required, is_popular, image_url
                    ) VALUES (
                        :id, :name, :slug, :stream, :sub_stream, :short_description, :job_profile, :how_to_get_there, :salary_range, :skills_required, :is_popular, :image_url
                    )
                ");
                $stmt->execute([
                    'id' => $newId,
                    'name' => $name,
                    'slug' => $slug,
                    'stream' => $stream,
                    'sub_stream' => $sub_stream,
                    'short_description' => $short_description,
                    'job_profile' => $job_profile,
                    'how_to_get_there' => $how_to_get_there,
                    'salary_range' => $salary_range,
                    'skills_required' => $skills_required,
                    'is_popular' => $is_popular,
                    'image_url' => $image_url
                ]);
                header('Location: careers.php?msg=created');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Database write failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $career ? 'Edit' : 'Add'; ?> Career Path | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; font-weight: 700; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 14px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none; font-size: 0.92rem; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .user-profile { display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .content-area { padding: 32px; max-width: 1000px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 2rem; font-weight: 800; color: #0f172a; }
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); max-width: 800px; overflow-x: auto; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 700; margin-bottom: 8px; font-size: 0.9rem; color: #334155; }
        .form-control { width: 100%; min-width: 0; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.95rem; outline: none; background: #fff; box-sizing: border-box; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(25, 55, 109, 0.1); }
        textarea.form-control { min-height: 120px; resize: vertical; line-height: 1.5; }
        .checkbox-group { display: flex; align-items: center; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
        .checkbox-group input { width: 18px; height: 18px; cursor: pointer; }
        .checkbox-group label { font-weight: 600; font-size: 0.92rem; color: #334155; cursor: pointer; margin-bottom: 0; }
        .btn-group { display: flex; gap: 12px; margin-top: 30px; }
        .btn-submit { background: #19376d; color: #fff; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.95rem; white-space: nowrap; box-sizing: border-box; }
        .btn-submit:hover { background: #0b2447; }
        .btn-cancel { background: #fff; color: #475569; border: 1px solid var(--border-color); padding: 12px 30px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.95rem; text-decoration: none; display: inline-block; text-align: center; white-space: nowrap; box-sizing: border-box; }
        .btn-cancel:hover { background: #f8fafc; }
        .error-alert { padding: 16px; border-radius: 8px; background: #fee2e2; color: #991b1b; margin-bottom: 24px; border: 1px solid #fecaca; display: flex; align-items: center; gap: 8px; font-weight: 600; }
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
            .page-header h2 { font-size: 1.4rem; }
            .panel { padding: 16px; max-width: none; }
            .form-group label { font-size: 0.85rem; margin-bottom: 6px; }
            .form-control { padding: 9px 12px; font-size: 0.9rem; }
            .btn-group { flex-direction: column; gap: 10px; }
            .btn-submit, .btn-cancel { width: 100%; text-align: center; justify-content: center; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
            .panel { padding: 12px; border-radius: 12px; }
            .page-header h2 { font-size: 1.2rem; }
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
                    <span style="font-weight:700; color:#334155;"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                    <a href="logout.php" style="margin-left: 16px; color: #ef4444;" title="Logout"><i class="ph ph-sign-out" style="font-size: 1.4rem;"></i></a>
                </div>
            </header>
            
            <div class="content-area">
                <div class="page-header">
                    <h2><?php echo $career ? 'Edit' : 'Add New'; ?> Career Path</h2>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-alert"><i class="ph ph-warning-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="panel">
                    <form method="POST">
                        <div class="form-group">
                            <label>Career Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Aeronautical Engineer" required value="<?php echo htmlspecialchars($career['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group" style="max-width: 300px;">
                            <label>Academic Stream *</label>
                            <select name="stream" class="form-control">
                                <option value="Science" <?php echo (isset($career['stream']) && $career['stream'] === 'Science') ? 'selected' : ''; ?>>Science</option>
                                <option value="Commerce" <?php echo (isset($career['stream']) && $career['stream'] === 'Commerce') ? 'selected' : ''; ?>>Commerce</option>
                                <option value="Humanities" <?php echo (isset($career['stream']) && $career['stream'] === 'Humanities') ? 'selected' : ''; ?>>Humanities</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Interest Category (Sub-stream) *</label>
                            <input type="text" name="sub_stream" class="form-control" placeholder="e.g. Aviation & Aerospace, Software & IT, Finance & Accounting" required value="<?php echo htmlspecialchars($career['sub_stream'] ?? ''); ?>">
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px; display: block;">This connects the career path to the Selector Wizard check options.</span>
                        </div>
                        
                        <div class="form-group">
                            <label>Short Description (Summary)</label>
                            <textarea name="short_description" class="form-control" placeholder="Provide a brief summary of the career (approx. 2 sentences)..."><?php echo htmlspecialchars($career['short_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Detailed Job Profile</label>
                            <textarea name="job_profile" class="form-control" placeholder="Describe the responsibilities, work environment, and daily activities..."><?php echo htmlspecialchars($career['job_profile'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>How to Get There (Step-by-Step Path)</label>
                            <textarea name="how_to_get_there" class="form-control" placeholder="Enter steps separated by new lines. Use format: **Title**: description..."><?php echo htmlspecialchars($career['how_to_get_there'] ?? ''); ?></textarea>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px; display: block;">Format Example:<br>1. **Schooling**: Physics and Math at 10+2 level.<br>2. **Graduation**: Secure a Bachelor's degree.</span>
                        </div>
                        
                        <div class="form-group">
                            <label>Salary Range (Starting Annual)</label>
                            <input type="text" name="salary_range" class="form-control" placeholder="e.g. 6 - 18 LPA" value="<?php echo htmlspecialchars($career['salary_range'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Required Skills (Comma separated)</label>
                            <input type="text" name="skills_required" class="form-control" placeholder="e.g. Calculus, Aerodynamics, MATLAB, Physics" value="<?php echo htmlspecialchars($career['skills_required'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Image URL</label>
                            <input type="text" name="image_url" class="form-control" placeholder="Unsplash image URL or local path" value="<?php echo htmlspecialchars($career['image_url'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_popular" name="is_popular" <?php echo (isset($career['is_popular']) && $career['is_popular']) ? 'checked' : ''; ?>>
                                <label for="is_popular">Show in Navbar Popular Careers list</label>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn-submit"><?php echo $career ? 'Save Changes' : 'Create Career'; ?></button>
                            <a href="careers.php" class="btn-cancel">Cancel</a>
                        </div>
                    </form>
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
