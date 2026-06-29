<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$consultant = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM consultants WHERE id = ?");
    $stmt->execute([$id]);
    $consultant = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$consultant) { die("Consultant not found."); }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $consultant_name = trim($_POST['consultant_name']);
    
    // New Fields
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug) && !empty($consultant_name)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $consultant_name)));
    }
    
    $specializations = trim($_POST['specializations'] ?? '');
    $office_location = trim($_POST['office_location'] ?? '');
    $languages_spoken = trim($_POST['languages_spoken'] ?? '');
    $website_url = trim($_POST['website_url'] ?? '');
    $bio = $_POST['bio'] ?? '';
    
    $consultant_rating = $_POST['consultant_rating'] !== '' ? (float)$_POST['consultant_rating'] : null;
    $verified_consultant = isset($_POST['verified_consultant']) ? 1 : 0;
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $experience_years = $_POST['experience_years'] !== '' ? (int)$_POST['experience_years'] : null;
    $success_rate_percent = $_POST['success_rate_percent'] !== '' ? (float)$_POST['success_rate_percent'] : null;
    
    // Profile Picture Upload
    $profile_picture = $consultant['profile_picture'] ?? null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $upload_dir = '../uploads/consultants/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $new_name = uniqid() . '.' . $file_ext;
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_name)) {
            $profile_picture = 'uploads/consultants/' . $new_name;
        }
    }
    
    if (empty($consultant_name)) {
        $error = "Consultant Name is required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE consultants SET consultant_name=?, slug=?, profile_picture=?, specializations=?, office_location=?, languages_spoken=?, website_url=?, bio=?, consultant_rating=?, verified_consultant=?, contact_email=?, contact_phone=?, experience_years=?, success_rate_percent=? WHERE id=?");
            $stmt->execute([$consultant_name, $slug, $profile_picture, $specializations, $office_location, $languages_spoken, $website_url, $bio, $consultant_rating, $verified_consultant, $contact_email, $contact_phone, $experience_years, $success_rate_percent, $id]);
            $success = "Consultant updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM consultants WHERE id = ?");
            $stmt->execute([$id]);
            $consultant = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("INSERT INTO consultants (consultant_name, slug, profile_picture, specializations, office_location, languages_spoken, website_url, bio, consultant_rating, verified_consultant, contact_email, contact_phone, experience_years, success_rate_percent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$consultant_name, $slug, $profile_picture, $specializations, $office_location, $languages_spoken, $website_url, $bio, $consultant_rating, $verified_consultant, $contact_email, $contact_phone, $experience_years, $success_rate_percent]);
            $id = $pdo->lastInsertId();
            header("Location: consultant_form.php?id=$id&msg=created");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Consultant | Admin</title>
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
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 900px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 1.8rem; font-weight: 800; display: flex; align-items: center; gap: 10px; }
        .btn-secondary { background: #fff; border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; color: var(--text-dark); display: inline-flex; align-items: center; gap: 6px; font-weight: 600; white-space: nowrap; box-sizing: border-box; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; white-space: nowrap; box-sizing: border-box; }
        .form-panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); overflow-x: auto; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); font-size: 0.9rem; }
        .form-control { width: 100%; min-width: 0; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; transition: border-color 0.2s; box-sizing: border-box; }
        .form-control:focus { border-color: var(--primary); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .checkbox-label { display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer; flex-wrap: wrap; }
        .checkbox-label input { width: 18px; height: 18px; }
        .msg-alert { padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid transparent; font-weight: 500; }
        .alert-success { background: rgba(11,36,71,0.04); color: #0B2447; border-color: rgba(11,36,71,0.04); }
        .alert-error { background: rgba(15,23,42,0.06); color: #0B2447; border-color: rgba(15,23,42,0.06); }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .topbar { height: 56px; padding: 0 12px; justify-content: space-between; }
            .mobile-menu-btn { display: block; }
            .content-area { padding: 12px; max-width: none; }
            .page-header h2 { font-size: 1.3rem; }
            .form-panel { padding: 16px; }
            .grid-2 { grid-template-columns: 1fr; gap: 12px; }
            .form-group { margin-bottom: 14px; }
            .form-group label { font-size: 0.85rem; margin-bottom: 6px; }
            .form-control { padding: 9px 12px; font-size: 0.9rem; }
            .btn-primary { width: 100%; text-align: center; justify-content: center; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
            .form-panel { padding: 12px; }
            .page-header h2 { font-size: 1.1rem; }
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
            <div class="user-profile"><span>Admin</span></div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <h2>
                    <a href="consultants.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                    <?php echo $id ? 'Edit Consultant' : 'Add New Consultant'; ?>
                </h2>
                <a href="consultants.php" class="btn-secondary">Cancel</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?>
                <div class="msg-alert alert-success">Consultant created successfully!</div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="msg-alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="msg-alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-panel">
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Consultant Name / Agency Name *</label>
                            <input type="text" name="consultant_name" class="form-control" required value="<?php echo htmlspecialchars($consultant['consultant_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Slug (URL) (Leave blank to auto-generate)</label>
                            <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($consultant['slug'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Profile Picture / Agency Logo</label>
                        <input type="file" name="profile_picture" class="form-control" accept="image/*">
                        <?php if(!empty($consultant['profile_picture'])): ?>
                            <div style="margin-top: 10px;">
                                <img src="../<?php echo htmlspecialchars($consultant['profile_picture']); ?>" alt="Profile" style="height: 60px; border-radius: 6px; border: 1px solid var(--border-color);">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($consultant['contact_email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($consultant['contact_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Office Location (City, Country)</label>
                            <input type="text" name="office_location" class="form-control" value="<?php echo htmlspecialchars($consultant['office_location'] ?? ''); ?>" placeholder="e.g. New York, USA">
                        </div>
                        <div class="form-group">
                            <label>Languages Spoken</label>
                            <input type="text" name="languages_spoken" class="form-control" value="<?php echo htmlspecialchars($consultant['languages_spoken'] ?? ''); ?>" placeholder="e.g. English, Hindi, Spanish">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Specializations</label>
                        <input type="text" name="specializations" class="form-control" value="<?php echo htmlspecialchars($consultant['specializations'] ?? ''); ?>" placeholder="e.g. Study Abroad, Visa Processing, MBA">
                    </div>
                    
                    <div class="form-group">
                        <label>Website URL</label>
                        <input type="url" name="website_url" class="form-control" value="<?php echo htmlspecialchars($consultant['website_url'] ?? ''); ?>" placeholder="https://...">
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Experience (Years)</label>
                            <input type="number" name="experience_years" class="form-control" value="<?php echo htmlspecialchars($consultant['experience_years'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Success Rate (%)</label>
                            <input type="number" step="0.1" name="success_rate_percent" class="form-control" value="<?php echo htmlspecialchars($consultant['success_rate_percent'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Rating (out of 5.0)</label>
                        <input type="number" step="0.1" max="5" min="0" name="consultant_rating" class="form-control" value="<?php echo htmlspecialchars($consultant['consultant_rating'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Bio / About Consultant</label>
                        <textarea name="bio" class="form-control" rows="5"><?php echo htmlspecialchars($consultant['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 30px;">
                        <label class="checkbox-label">
                            <input type="checkbox" name="verified_consultant" <?php echo (isset($consultant['verified_consultant']) && $consultant['verified_consultant']) ? 'checked' : ''; ?>>
                            Verified Consultant / Recommended by Platform
                        </label>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 10px;">Save Consultant</button>
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
<!-- jQuery and Trumbowyg -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
<script>
    $(document).ready(function() {
        $('textarea[name="bio"]').trumbowyg();
    });
</script>
</body>
</html>
