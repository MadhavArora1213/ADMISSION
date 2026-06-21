<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
$is_edit = $id !== '';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $provider_logo = $_POST['provider_logo'] ?? '';
    
    // Handle file upload
    if (isset($_FILES['provider_logo_file']) && $_FILES['provider_logo_file']['error'] == 0) {
        $upload_dir = '../uploads/scholarships/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $tmp_name = $_FILES['provider_logo_file']['tmp_name'];
        $file_name = time() . '_' . mt_rand(100, 999) . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_FILES['provider_logo_file']['name']);
        
        if (move_uploaded_file($tmp_name, $upload_dir . $file_name)) {
            $provider_logo = 'uploads/scholarships/' . $file_name;
        }
    }

    $slug = !empty($_POST['scholarship_slug']) ? $_POST['scholarship_slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['scholarship_name'])));

    $data = [
        'scholarship_name' => $_POST['scholarship_name'],
        'scholarship_slug' => $slug,
        'provider_name' => $_POST['provider_name'],
        'provider_logo' => $provider_logo,
        'scholarship_type' => $_POST['scholarship_type'],
        'amount' => !empty($_POST['amount']) ? $_POST['amount'] : null,
        'amount_type' => $_POST['amount_type'],
        'eligibility_criteria' => $_POST['eligibility_criteria'],
        'min_percentage' => !empty($_POST['min_percentage']) ? $_POST['min_percentage'] : null,
        'income_limit' => !empty($_POST['income_limit']) ? $_POST['income_limit'] : null,
        'gender' => $_POST['gender'],
        'category' => $_POST['category'],
        'state_specific' => !empty($_POST['state_specific']) ? $_POST['state_specific'] : null,
        'course_levels' => isset($_POST['course_levels']) ? json_encode($_POST['course_levels']) : null,
        'apply_start' => !empty($_POST['apply_start']) ? $_POST['apply_start'] : null,
        'apply_end' => !empty($_POST['apply_end']) ? $_POST['apply_end'] : null,
        'official_link' => $_POST['official_link'],
        'renewable' => isset($_POST['renewable']) ? 1 : 0,
        'renewable_conditions' => $_POST['renewable_conditions'],
        'status' => $_POST['status']
    ];

    if ($is_edit) {
        $sql = "UPDATE scholarships SET 
            scholarship_name=:scholarship_name, scholarship_slug=:scholarship_slug, provider_name=:provider_name, 
            provider_logo=:provider_logo, scholarship_type=:scholarship_type, amount=:amount, amount_type=:amount_type, 
            eligibility_criteria=:eligibility_criteria, min_percentage=:min_percentage, income_limit=:income_limit, 
            gender=:gender, category=:category, state_specific=:state_specific, course_levels=:course_levels, 
            apply_start=:apply_start, apply_end=:apply_end, official_link=:official_link, renewable=:renewable, 
            renewable_conditions=:renewable_conditions, status=:status 
            WHERE id=:id";
        $data['id'] = $id;
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute($data);
            $msg = "Scholarship updated successfully.";
        } catch(PDOException $e) {
            $msg = "Error updating: " . $e->getMessage();
        }
    } else {
        $data['id'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        $sql = "INSERT INTO scholarships (id, scholarship_name, scholarship_slug, provider_name, provider_logo, scholarship_type, amount, amount_type, eligibility_criteria, min_percentage, income_limit, gender, category, state_specific, course_levels, apply_start, apply_end, official_link, renewable, renewable_conditions, status) VALUES (:id, :scholarship_name, :scholarship_slug, :provider_name, :provider_logo, :scholarship_type, :amount, :amount_type, :eligibility_criteria, :min_percentage, :income_limit, :gender, :category, :state_specific, :course_levels, :apply_start, :apply_end, :official_link, :renewable, :renewable_conditions, :status)";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute($data);
            header("Location: scholarships.php");
            exit;
        } catch(PDOException $e) {
            $msg = "Error creating: " . $e->getMessage();
        }
    }
}

$sch = [];
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM scholarships WHERE id = ?");
    $stmt->execute([$id]);
    $sch = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sch) { header('Location: scholarships.php'); exit; }
}

$course_levels = ['UG','PG','Diploma','PhD','Certificate','Integrated'];
$selected_levels = isset($sch['course_levels']) && $sch['course_levels'] ? json_decode($sch['course_levels'], true) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Scholarship | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none;}
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 1000px; margin: 0 auto; width: 100%; }
        
        .page-header { margin-bottom: 24px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; display:flex; align-items:center; gap:10px; }
        
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm); }
        .panel h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); display:flex; align-items:center; gap:8px; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; font-family: inherit; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary:hover { background: #19376D; }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 20px; border: 1px solid rgba(11,36,71,0.04); font-weight:500; }
        .msg-error { background: rgba(15,23,42,0.06); color: #0B2447; border-color: rgba(15,23,42,0.06); }
        
        .checkbox-group { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px; }
        .checkbox-label { display: flex; align-items: center; gap: 6px; font-size: 0.9rem; cursor: pointer; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></span>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <a href="scholarships.php" style="font-size:0.85rem; color:var(--primary); text-decoration:none; display:flex; align-items:center; gap:4px; margin-bottom:8px;"><i class="ph ph-arrow-left"></i> Back to Scholarships</a>
                    <h2><i class="ph ph-<?php echo $is_edit ? 'pencil' : 'plus'; ?>" style="color:var(--primary);"></i> <?php echo $is_edit ? 'Edit' : 'Add'; ?> Scholarship</h2>
                </div>
            </div>
            
            <?php if($msg): ?>
            <div class="msg-alert <?php echo strpos($msg, 'Error') !== false ? 'msg-error' : ''; ?>"><i class="ph ph-info"></i> <?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="panel">
                    <h3><i class="ph ph-info"></i> Basic Information</h3>
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Scholarship Name *</label>
                            <input type="text" name="scholarship_name" class="form-control" value="<?php echo htmlspecialchars($sch['scholarship_name']??''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Scholarship Slug (Leave blank to auto-generate)</label>
                            <input type="text" name="scholarship_slug" class="form-control" value="<?php echo htmlspecialchars($sch['scholarship_slug']??''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Provider Name</label>
                            <input type="text" name="provider_name" class="form-control" value="<?php echo htmlspecialchars($sch['provider_name']??''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Provider Logo</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="hidden" name="provider_logo" value="<?php echo htmlspecialchars($sch['provider_logo']??''); ?>">
                                <input type="file" name="provider_logo_file" class="form-control" accept="image/*" style="flex: 1;">
                                <?php if(!empty($sch['provider_logo'])): ?>
                                <img src="../<?php echo htmlspecialchars($sch['provider_logo']); ?>" alt="logo" style="height: 40px; object-fit: contain; border-radius: 4px;">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <h3><i class="ph ph-money"></i> Financial & Timeline Details</h3>
                    <div class="grid-3">
                        <div class="form-group">
                            <label class="form-label">Scholarship Type</label>
                            <select name="scholarship_type" class="form-control">
                                <?php foreach(['government','private','college','abroad','sports','minority'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($sch['scholarship_type']??'') == $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Amount Type</label>
                            <select name="amount_type" class="form-control">
                                <?php foreach(['fixed','percentage','full_tuition','stipend'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($sch['amount_type']??'') == $t ? 'selected' : ''; ?>><?php echo str_replace('_',' ',ucfirst($t)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Amount (₹ or %)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo htmlspecialchars($sch['amount']??''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Application Start Date</label>
                            <input type="date" name="apply_start" class="form-control" value="<?php echo htmlspecialchars($sch['apply_start']??''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Application End Date</label>
                            <input type="date" name="apply_end" class="form-control" value="<?php echo htmlspecialchars($sch['apply_end']??''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <?php foreach(['upcoming','active','expired'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($sch['status']??'') == $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <h3><i class="ph ph-list-checks"></i> Eligibility & Criteria</h3>
                    <div class="grid-3">
                        <div class="form-group">
                            <label class="form-label">Minimum Percentage (%)</label>
                            <input type="number" step="0.1" name="min_percentage" class="form-control" value="<?php echo htmlspecialchars($sch['min_percentage']??''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Max Family Income (₹)</label>
                            <input type="number" step="0.01" name="income_limit" class="form-control" value="<?php echo htmlspecialchars($sch['income_limit']??''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State Specific (Leave blank for National)</label>
                            <input type="text" name="state_specific" class="form-control" value="<?php echo htmlspecialchars($sch['state_specific']??''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender Restriction</label>
                            <select name="gender" class="form-control">
                                <?php foreach(['all','male','female','transgender'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($sch['gender']??'all') == $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Category Restriction</label>
                            <select name="category" class="form-control">
                                <?php foreach(['all','sc','st','obc','ews','minority','pwd'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($sch['category']??'all') == $t ? 'selected' : ''; ?>><?php echo strtoupper($t)=='ALL'?ucfirst($t):strtoupper($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top:16px;">
                        <label class="form-label">Applicable Course Levels</label>
                        <div class="checkbox-group">
                            <?php foreach($course_levels as $lvl): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="course_levels[]" value="<?php echo $lvl; ?>" <?php echo in_array($lvl, $selected_levels) ? 'checked' : ''; ?>>
                                <?php echo $lvl; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Detailed Eligibility Criteria</label>
                        <textarea name="eligibility_criteria" class="form-control" rows="4"><?php echo htmlspecialchars($sch['eligibility_criteria']??''); ?></textarea>
                    </div>
                </div>

                <div class="panel">
                    <h3><i class="ph ph-link"></i> Additional Info</h3>
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Official Apply Link</label>
                            <input type="url" name="official_link" class="form-control" value="<?php echo htmlspecialchars($sch['official_link']??''); ?>">
                        </div>
                        <div class="form-group" style="display:flex; align-items:center;">
                            <label class="checkbox-label" style="font-weight:600;">
                                <input type="checkbox" name="renewable" value="1" <?php echo !empty($sch['renewable']) ? 'checked' : ''; ?> style="width:18px; height:18px;">
                                Is this scholarship renewable?
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Renewable Conditions (If applicable)</label>
                        <textarea name="renewable_conditions" class="form-control" rows="3"><?php echo htmlspecialchars($sch['renewable_conditions']??''); ?></textarea>
                    </div>
                </div>

                <div style="text-align: right; margin-bottom: 40px;">
                    <a href="scholarships.php" style="color:var(--text-muted); font-weight:600; text-decoration:none; margin-right:16px;">Cancel</a>
                    <button type="submit" class="btn-primary"><i class="ph ph-floppy-disk"></i> Save Scholarship</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
