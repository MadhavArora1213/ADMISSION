<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$is_edit = $id !== null;
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'identity';
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'identity') {
    // Generate slug from name if empty
    $slug = !empty($_POST['slug']) ? $_POST['slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])));
    
    // Check if slug is unique
    $slugCheckQ = "SELECT id FROM colleges WHERE slug = :slug";
    if ($is_edit) $slugCheckQ .= " AND id != :id";
    $slugCheckStmt = $pdo->prepare($slugCheckQ);
    $slugCheckParams = ['slug' => $slug];
    if ($is_edit) $slugCheckParams['id'] = $id;
    $slugCheckStmt->execute($slugCheckParams);
    
    if ($slugCheckStmt->rowCount() > 0) {
        $error = "The slug '$slug' is already in use. Please provide a unique slug.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $collegeData = [
                'name' => $_POST['name'],
                'slug' => $slug,
                'college_type' => $_POST['college_type'] ?: null,
                'ownership' => $_POST['ownership'] ?: null,
                'status' => $_POST['status'],
                'logo_url' => $_POST['logo_url'],
                'cover_image_url' => $_POST['cover_image_url'],
                'established_year' => $_POST['established_year'] ?: null,
                'autonomous' => isset($_POST['autonomous']) ? 1 : 0,
                'ugc_approved' => isset($_POST['ugc_approved']) ? 1 : 0,
                'aicte_approved' => isset($_POST['aicte_approved']) ? 1 : 0,
                'total_students' => $_POST['total_students'] ?: null,
                'total_faculty' => $_POST['total_faculty'] ?: null,
                'campus_area_acres' => $_POST['campus_area_acres'] ?: null,
                'city_id' => !empty($_POST['city_id']) ? $_POST['city_id'] : null,
                'state_id' => !empty($_POST['state_id']) ? $_POST['state_id'] : null,
                'university_id' => !empty($_POST['university_id']) ? $_POST['university_id'] : null,
                'naac_grade' => $_POST['naac_grade'] ?: 'None',
                'nirf_rank' => $_POST['nirf_rank'] ?: null,
                'is_verified' => isset($_POST['is_verified']) ? 1 : 0,
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'featured_order' => $_POST['featured_order'] ?: 0,
                'verification_status' => $_POST['verification_status'] ?: 'pending',
                'verified_by' => !empty($_POST['verified_by']) ? $_POST['verified_by'] : null,
                'verified_at' => !empty($_POST['verified_at']) ? $_POST['verified_at'] : null,
                'rejection_reason' => $_POST['rejection_reason'] ?: null,
                'duplicate_of' => !empty($_POST['duplicate_of']) ? $_POST['duplicate_of'] : null,
                'data_quality_score' => $_POST['data_quality_score'] ?: 0,
                'import_batch_id' => !empty($_POST['import_batch_id']) ? $_POST['import_batch_id'] : null
            ];

            if ($is_edit) {
                // Update College
                $updateFields = [];
                foreach ($collegeData as $key => $val) {
                    $updateFields[] = "$key = :$key";
                }
                $sql = "UPDATE colleges SET " . implode(", ", $updateFields) . " WHERE id = :id";
                $collegeData['id'] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($collegeData);
            } else {
                // Insert College
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $collegeData['id'] = $id;
                $keys = array_keys($collegeData);
                $sql = "INSERT INTO colleges (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($collegeData);
            }

            // Handle Contacts
            $contactData = [
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'address' => $_POST['address'],
                'latitude' => $_POST['latitude'] ?: null,
                'longitude' => $_POST['longitude'] ?: null,
                'website_url' => $_POST['website_url'],
                'pincode' => $_POST['pincode'],
                'google_maps_url' => $_POST['google_maps_url']
            ];

            // Check if contact exists
            $contactCheck = $pdo->prepare("SELECT id FROM college_contacts WHERE college_id = ?");
            $contactCheck->execute([$id]);
            
            if ($contactCheck->rowCount() > 0) {
                $contactFields = [];
                foreach ($contactData as $key => $val) {
                    $contactFields[] = "$key = :$key";
                }
                $contactData['college_id'] = $id;
                $sql = "UPDATE college_contacts SET " . implode(", ", $contactFields) . " WHERE college_id = :college_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($contactData);
            } else {
                $contactId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $contactData['id'] = $contactId;
                $contactData['college_id'] = $id;
                $keys = array_keys($contactData);
                $sql = "INSERT INTO college_contacts (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($contactData);
            }

            $pdo->commit();
            header('Location: college_form.php?id=' . $id . '&tab=identity&msg=saved');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error saving college: " . $e->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'about') {
    try {
        $updateData = [
            'id' => $id,
            'about_text' => $_POST['about_text'],
            'highlights_json' => $_POST['highlights_json'],
            'accreditations' => $_POST['accreditations'],
            'rankings_json' => $_POST['rankings_json'],
            'awards_json' => $_POST['awards_json'],
            'admission_process' => $_POST['admission_process'],
            'accepted_exams' => $_POST['accepted_exams'],
            'admission_start_date' => !empty($_POST['admission_start_date']) ? $_POST['admission_start_date'] : null,
            'admission_end_date' => !empty($_POST['admission_end_date']) ? $_POST['admission_end_date'] : null,
            'merit_based' => isset($_POST['merit_based']) ? 1 : 0,
            'direct_admission' => isset($_POST['direct_admission']) ? 1 : 0,
            'management_quota_seats' => $_POST['management_quota_seats'] ?: 0,
            'nri_quota_seats' => $_POST['nri_quota_seats'] ?: 0,
            'library' => isset($_POST['library']) ? 1 : 0,
            'sports_facilities' => $_POST['sports_facilities'],
            'labs' => $_POST['labs'],
            'auditorium' => isset($_POST['auditorium']) ? 1 : 0,
            'cafeteria' => isset($_POST['cafeteria']) ? 1 : 0,
            'wifi' => isset($_POST['wifi']) ? 1 : 0,
            'medical_facility' => isset($_POST['medical_facility']) ? 1 : 0,
            'transport' => isset($_POST['transport']) ? 1 : 0,
            'hostel_available' => isset($_POST['hostel_available']) ? 1 : 0,
            'hostel_type' => $_POST['hostel_type'] ?: null,
            'hostel_capacity' => $_POST['hostel_capacity'] ?: 0,
            'hostel_fee_annual' => $_POST['hostel_fee_annual'] ?: null,
            'mess_available' => isset($_POST['mess_available']) ? 1 : 0,
            'mess_type' => $_POST['mess_type'] ?: null,
            'ac_available' => isset($_POST['ac_available']) ? 1 : 0
        ];
        
        $fields = [];
        foreach($updateData as $key => $val) {
            if($key == 'id') continue;
            $fields[] = "$key = :$key";
        }
        $sql = "UPDATE colleges SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateData);
        
        header('Location: college_form.php?id=' . $id . '&tab=about&msg=saved');
        exit;
    } catch (Exception $e) {
        $error = "Error saving About details: " . $e->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'seo') {
    try {
        $publish_status = $_POST['publish_status'];
        $published_at = ($publish_status == 'published' && getValue($college, 'publish_status') != 'published') ? date('Y-m-d H:i:s') : getValue($college, 'published_at');
        
        $updateData = [
            'id' => $id,
            'meta_title' => $_POST['meta_title'],
            'meta_description' => $_POST['meta_description'],
            'meta_keywords' => $_POST['meta_keywords'],
            'og_image_url' => $_POST['og_image_url'],
            'canonical_url' => $_POST['canonical_url'],
            'schema_markup' => $_POST['schema_markup'],
            'publish_status' => $publish_status,
            'published_at' => $published_at,
            'noindex' => isset($_POST['noindex']) ? 1 : 0
        ];
        
        $fields = [];
        foreach($updateData as $key => $val) {
            if($key == 'id') continue;
            $fields[] = "$key = :$key";
        }
        $sql = "UPDATE colleges SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateData);
        
        header('Location: college_form.php?id=' . $id . '&tab=seo&msg=saved');
        exit;
    } catch (Exception $e) {
        $error = "Error saving SEO details: " . $e->getMessage();
    }
}

// Fetch Reference Data for Dropdowns
$states = $pdo->query("SELECT * FROM states ORDER BY name ASC")->fetchAll();
$cities = $pdo->query("SELECT * FROM cities ORDER BY name ASC")->fetchAll();
$universities = $pdo->query("SELECT * FROM universities ORDER BY name ASC")->fetchAll();
$users = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll();
$allColleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name ASC")->fetchAll();

// Fetch existing data if edit
$college = [];
$contact = [];
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM colleges WHERE id = ?");
    $stmt->execute([$id]);
    $college = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $cStmt = $pdo->prepare("SELECT * FROM college_contacts WHERE college_id = ?");
    $cStmt->execute([$id]);
    $contact = $cStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$college) {
        header('Location: colleges.php');
        exit;
    }
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
    <title><?php echo $is_edit ? 'Edit College' : 'Add New College'; ?> | AdmissionSeason Admin</title>
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
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; padding-bottom: 60px; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 1000px; margin: 0 auto; width: 100%; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 12px; }
        
        .form-section { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .form-section h3 { font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 24px; display: flex; align-items: center; gap: 8px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); font-size: 0.95rem; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; color: var(--text-dark); background: #fff; transition: all 0.3s ease; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(11, 36, 71, 0.1); }
        
        .checkbox-group { display: flex; align-items: center; gap: 8px; margin-top: 32px; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .checkbox-group label { margin-bottom: 0; cursor: pointer; }
        
        .error-alert { padding: 16px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 24px; border: 1px solid #fecaca; }
        
        .form-actions { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
        
        /* Tabs Styling */
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        .tab-link.disabled { opacity: 0.5; cursor: not-allowed; }
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
                    <h2>
                        <a href="colleges.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                        <?php echo $is_edit ? 'Edit College: ' . htmlspecialchars($college['name']) : 'Add New College'; ?>
                    </h2>
                </div>

                <?php if($is_edit): ?>
                <div class="tabs-nav">
                    <a href="?id=<?php echo $id; ?>&tab=identity" class="tab-link <?php echo $current_tab=='identity'?'active':''; ?>">Identity & Contact</a>
                    <a href="?id=<?php echo $id; ?>&tab=about" class="tab-link <?php echo $current_tab=='about'?'active':''; ?>">About & Amenities</a>
                    <a href="?id=<?php echo $id; ?>&tab=seo" class="tab-link <?php echo $current_tab=='seo'?'active':''; ?>">SEO & Publish</a>
                    <a href="college_courses.php?college_id=<?php echo $id; ?>" class="tab-link">Courses & Fees</a>
                    <a href="college_placements.php?college_id=<?php echo $id; ?>" class="tab-link">Placements</a>
                    <a href="college_cutoffs.php?college_id=<?php echo $id; ?>" class="tab-link">Cutoffs</a>
                    <a href="college_media.php?college_id=<?php echo $id; ?>" class="tab-link">Media & Gallery</a>
                    <a href="college_faqs.php?college_id=<?php echo $id; ?>" class="tab-link">FAQs</a>
                    <a href="college_faculty.php?college_id=<?php echo $id; ?>" class="tab-link">Faculty</a>
                    <a href="college_scholarships.php?college_id=<?php echo $id; ?>" class="tab-link">Scholarships</a>
                </div>
                <?php else: ?>
                <div class="tabs-nav">
                    <span class="tab-link active">Identity & Contact</span>
                    <span class="tab-link disabled" title="Save college first to unlock">About & Amenities</span>
                    <span class="tab-link disabled" title="Save college first to unlock">SEO & Publish</span>
                    <span class="tab-link disabled" title="Save college first to unlock">Courses & Fees</span>
                </div>
                <?php endif; ?>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert" style="padding: 16px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 24px; border: 1px solid #bbf7d0;">
                    <i class="ph ph-check-circle"></i> Details saved successfully!
                </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="error-alert"><i class="ph-fill ph-warning-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if($current_tab == 'identity'): ?>
                <form action="" method="POST">
                    
                    <!-- Section: Identity & Core Details -->
                    <div class="form-section">
                        <h3><i class="ph ph-identification-card"></i> College Identity</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>College Name *</label>
                                <input type="text" name="name" class="form-control" required value="<?php echo getValue($college, 'name'); ?>">
                            </div>
                            <div class="form-group">
                                <label>URL Slug (Leave blank to auto-generate)</label>
                                <input type="text" name="slug" class="form-control" value="<?php echo getValue($college, 'slug'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Status *</label>
                                <select name="status" class="form-control" required>
                                    <option value="pending" <?php echo getValue($college, 'status') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="active" <?php echo getValue($college, 'status') == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="archived" <?php echo getValue($college, 'status') == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>College Type</label>
                                <select name="college_type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="govt" <?php echo getValue($college, 'college_type') == 'govt' ? 'selected' : ''; ?>>Government</option>
                                    <option value="private" <?php echo getValue($college, 'college_type') == 'private' ? 'selected' : ''; ?>>Private</option>
                                    <option value="deemed" <?php echo getValue($college, 'college_type') == 'deemed' ? 'selected' : ''; ?>>Deemed</option>
                                    <option value="autonomous" <?php echo getValue($college, 'college_type') == 'autonomous' ? 'selected' : ''; ?>>Autonomous</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ownership</label>
                                <select name="ownership" class="form-control">
                                    <option value="">Select Ownership</option>
                                    <option value="public" <?php echo getValue($college, 'ownership') == 'public' ? 'selected' : ''; ?>>Public</option>
                                    <option value="private" <?php echo getValue($college, 'ownership') == 'private' ? 'selected' : ''; ?>>Private</option>
                                    <option value="trust" <?php echo getValue($college, 'ownership') == 'trust' ? 'selected' : ''; ?>>Trust</option>
                                    <option value="society" <?php echo getValue($college, 'ownership') == 'society' ? 'selected' : ''; ?>>Society</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Established Year</label>
                                <input type="number" name="established_year" class="form-control" min="1800" max="2099" value="<?php echo getValue($college, 'established_year'); ?>">
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="is_verified" name="is_verified" <?php echo !empty($college['is_verified']) ? 'checked' : ''; ?>>
                                <label for="is_verified">Mark as Verified <i class="ph-fill ph-seal-check" style="color:var(--primary);"></i></label>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Media & Affiliation -->
                    <div class="form-section">
                        <h3><i class="ph ph-image"></i> Media & Accreditations</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Logo URL</label>
                                <input type="url" name="logo_url" class="form-control" placeholder="https://..." value="<?php echo getValue($college, 'logo_url'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Cover Image URL</label>
                                <input type="url" name="cover_image_url" class="form-control" placeholder="https://..." value="<?php echo getValue($college, 'cover_image_url'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>University Affiliation</label>
                                <select name="university_id" class="form-control">
                                    <option value="">Select University</option>
                                    <?php foreach($universities as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo getValue($college, 'university_id') == $u['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>NAAC Grade</label>
                                <select name="naac_grade" class="form-control">
                                    <option value="None">None</option>
                                    <?php 
                                    $grades = ['A++', 'A+', 'A', 'B++', 'B+', 'B', 'C'];
                                    foreach($grades as $g) {
                                        $sel = getValue($college, 'naac_grade') == $g ? 'selected' : '';
                                        echo "<option value='$g' $sel>$g</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>NIRF Rank</label>
                                <input type="number" name="nirf_rank" class="form-control" value="<?php echo getValue($college, 'nirf_rank'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <div class="checkbox-group" style="margin-top:0;">
                                    <input type="checkbox" id="autonomous" name="autonomous" <?php echo !empty($college['autonomous']) ? 'checked' : ''; ?>>
                                    <label for="autonomous">Autonomous</label>
                                </div>
                                <div class="checkbox-group" style="margin-top:10px;">
                                    <input type="checkbox" id="ugc_approved" name="ugc_approved" <?php echo !empty($college['ugc_approved']) ? 'checked' : ''; ?>>
                                    <label for="ugc_approved">UGC Approved</label>
                                </div>
                                <div class="checkbox-group" style="margin-top:10px;">
                                    <input type="checkbox" id="aicte_approved" name="aicte_approved" <?php echo !empty($college['aicte_approved']) ? 'checked' : ''; ?>>
                                    <label for="aicte_approved">AICTE Approved</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Total Students</label>
                                <input type="number" name="total_students" class="form-control" value="<?php echo getValue($college, 'total_students'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Total Faculty</label>
                                <input type="number" name="total_faculty" class="form-control" value="<?php echo getValue($college, 'total_faculty'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Campus Area (Acres)</label>
                                <input type="number" step="0.1" name="campus_area_acres" class="form-control" value="<?php echo getValue($college, 'campus_area_acres'); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Contact & Location -->
                    <div class="form-section">
                        <h3><i class="ph ph-map-pin"></i> Contact & Location</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>State</label>
                                <select name="state_id" class="form-control">
                                    <option value="">Select State</option>
                                    <?php foreach($states as $s): ?>
                                        <option value="<?php echo $s['id']; ?>" <?php echo getValue($college, 'state_id') == $s['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>City</label>
                                <select name="city_id" class="form-control">
                                    <option value="">Select City</option>
                                    <?php foreach($cities as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo getValue($college, 'city_id') == $c['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group full">
                                <label>Address</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo getValue($contact, 'address'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo getValue($contact, 'email'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo getValue($contact, 'phone'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Website URL</label>
                                <input type="url" name="website_url" class="form-control" value="<?php echo getValue($contact, 'website_url'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Pincode</label>
                                <input type="text" name="pincode" class="form-control" value="<?php echo getValue($contact, 'pincode'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="number" step="0.000001" name="latitude" class="form-control" value="<?php echo getValue($contact, 'latitude'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="number" step="0.000001" name="longitude" class="form-control" value="<?php echo getValue($contact, 'longitude'); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Google Maps URL</label>
                                <input type="url" name="google_maps_url" class="form-control" value="<?php echo getValue($contact, 'google_maps_url'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section: Features -->
                    <div class="form-section">
                        <h3><i class="ph ph-star"></i> Feature Settings</h3>
                        <div class="form-grid">
                            <div class="form-group checkbox-group" style="margin-top:0;">
                                <input type="checkbox" id="is_featured" name="is_featured" <?php echo !empty($college['is_featured']) ? 'checked' : ''; ?>>
                                <label for="is_featured">Feature this college on homepage/listings</label>
                            </div>
                            <div class="form-group">
                                <label>Featured Order (Higher means top priority)</label>
                                <input type="number" name="featured_order" class="form-control" value="<?php echo getValue($college, 'featured_order', 0); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Verification & Data Quality -->
                    <div class="form-section">
                        <h3><i class="ph ph-shield-check"></i> Verification & Data Quality</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Verification Status</label>
                                <select name="verification_status" class="form-control">
                                    <option value="pending" <?php echo getValue($college, 'verification_status') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="verified" <?php echo getValue($college, 'verification_status') == 'verified' ? 'selected' : ''; ?>>Verified</option>
                                    <option value="rejected" <?php echo getValue($college, 'verification_status') == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Verified By</label>
                                <select name="verified_by" class="form-control">
                                    <option value="">Select User</option>
                                    <?php foreach($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo getValue($college, 'verified_by') == $u['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Verified At</label>
                                <input type="datetime-local" name="verified_at" class="form-control" value="<?php echo !empty($college['verified_at']) ? date('Y-m-d\TH:i', strtotime($college['verified_at'])) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Data Quality Score (0-100)</label>
                                <input type="number" name="data_quality_score" class="form-control" min="0" max="100" value="<?php echo getValue($college, 'data_quality_score', 0); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Rejection Reason</label>
                                <textarea name="rejection_reason" class="form-control" rows="2"><?php echo getValue($college, 'rejection_reason'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Duplicate Of</label>
                                <select name="duplicate_of" class="form-control">
                                    <option value="">Select College (If Duplicate)</option>
                                    <?php foreach($allColleges as $ac): 
                                        if($ac['id'] == $id) continue; // Skip self
                                    ?>
                                        <option value="<?php echo $ac['id']; ?>" <?php echo getValue($college, 'duplicate_of') == $ac['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ac['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Import Batch ID (UUID)</label>
                                <input type="text" name="import_batch_id" class="form-control" value="<?php echo getValue($college, 'import_batch_id'); ?>" placeholder="e.g. 123e4567-e89b-12d3-a456-426614174000">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="colleges.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ph ph-floppy-disk"></i> Save Identity Details
                        </button>
                    </div>

                </form>

                <?php elseif($current_tab == 'about'): ?>
                <!-- ABOUT & AMENITIES TAB -->
                <form action="" method="POST">
                    <div class="form-section">
                        <h3><i class="ph ph-info"></i> Basic Info & About</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>About Text</label>
                                <textarea name="about_text" class="form-control" rows="5"><?php echo getValue($college, 'about_text'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Highlights (JSON List)</label>
                                <textarea name="highlights_json" class="form-control" rows="3" placeholder='["Excellent Campus", "Top Recruiters"]'><?php echo getValue($college, 'highlights_json'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Accreditations (JSON List)</label>
                                <textarea name="accreditations" class="form-control" rows="3"><?php echo getValue($college, 'accreditations'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Rankings (JSON Object)</label>
                                <textarea name="rankings_json" class="form-control" rows="3" placeholder='{"NIRF": 12, "India Today": 5}'><?php echo getValue($college, 'rankings_json'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Awards (JSON List)</label>
                                <textarea name="awards_json" class="form-control" rows="3"><?php echo getValue($college, 'awards_json'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-door-open"></i> Admissions</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Admission Process Description</label>
                                <textarea name="admission_process" class="form-control" rows="4"><?php echo getValue($college, 'admission_process'); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Accepted Exams (JSON List)</label>
                                <input type="text" name="accepted_exams" class="form-control" value="<?php echo getValue($college, 'accepted_exams'); ?>" placeholder='["JEE Main", "SAT"]'>
                            </div>
                            <div class="form-group">
                                <label>Admission Start Date</label>
                                <input type="date" name="admission_start_date" class="form-control" value="<?php echo getValue($college, 'admission_start_date'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Admission End Date</label>
                                <input type="date" name="admission_end_date" class="form-control" value="<?php echo getValue($college, 'admission_end_date'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Management Quota Seats</label>
                                <input type="number" name="management_quota_seats" class="form-control" value="<?php echo getValue($college, 'management_quota_seats', 0); ?>">
                            </div>
                            <div class="form-group">
                                <label>NRI Quota Seats</label>
                                <input type="number" name="nri_quota_seats" class="form-control" value="<?php echo getValue($college, 'nri_quota_seats', 0); ?>">
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="merit_based" name="merit_based" <?php echo !empty($college['merit_based']) ? 'checked' : ''; ?>>
                                <label for="merit_based">Merit Based Admission</label>
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="direct_admission" name="direct_admission" <?php echo !empty($college['direct_admission']) ? 'checked' : ''; ?>>
                                <label for="direct_admission">Direct Admission Available</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-buildings"></i> Infrastructure</h3>
                        <div class="form-grid">
                            <div class="form-group checkbox-group"><input type="checkbox" name="library" <?php echo !empty($college['library'])?'checked':''; ?>> <label>Library</label></div>
                            <div class="form-group checkbox-group"><input type="checkbox" name="auditorium" <?php echo !empty($college['auditorium'])?'checked':''; ?>> <label>Auditorium</label></div>
                            <div class="form-group checkbox-group"><input type="checkbox" name="cafeteria" <?php echo !empty($college['cafeteria'])?'checked':''; ?>> <label>Cafeteria</label></div>
                            <div class="form-group checkbox-group"><input type="checkbox" name="wifi" <?php echo !empty($college['wifi'])?'checked':''; ?>> <label>Wi-Fi Campus</label></div>
                            <div class="form-group checkbox-group"><input type="checkbox" name="medical_facility" <?php echo !empty($college['medical_facility'])?'checked':''; ?>> <label>Medical Facility</label></div>
                            <div class="form-group checkbox-group"><input type="checkbox" name="transport" <?php echo !empty($college['transport'])?'checked':''; ?>> <label>Transport</label></div>
                            
                            <div class="form-group full" style="margin-top: 16px;">
                                <label>Sports Facilities (JSON List)</label>
                                <input type="text" name="sports_facilities" class="form-control" value="<?php echo getValue($college, 'sports_facilities'); ?>" placeholder='["Cricket", "Football", "Basketball"]'>
                            </div>
                            <div class="form-group full">
                                <label>Labs (JSON List)</label>
                                <input type="text" name="labs" class="form-control" value="<?php echo getValue($college, 'labs'); ?>" placeholder='["Computer Lab", "Physics Lab"]'>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-house-line"></i> Hostel & Mess</h3>
                        <div class="form-grid">
                            <div class="form-group checkbox-group full">
                                <input type="checkbox" name="hostel_available" <?php echo !empty($college['hostel_available'])?'checked':''; ?>> 
                                <label style="font-weight:700;">Hostel Facility Available</label>
                            </div>
                            <div class="form-group">
                                <label>Hostel Type</label>
                                <select name="hostel_type" class="form-control">
                                    <option value="">Select</option>
                                    <option value="boys" <?php echo getValue($college, 'hostel_type')=='boys'?'selected':''; ?>>Boys Only</option>
                                    <option value="girls" <?php echo getValue($college, 'hostel_type')=='girls'?'selected':''; ?>>Girls Only</option>
                                    <option value="both" <?php echo getValue($college, 'hostel_type')=='both'?'selected':''; ?>>Both</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Hostel Capacity (Total Beds)</label>
                                <input type="number" name="hostel_capacity" class="form-control" value="<?php echo getValue($college, 'hostel_capacity', 0); ?>">
                            </div>
                            <div class="form-group">
                                <label>Hostel Fee (Annual)</label>
                                <input type="number" step="0.01" name="hostel_fee_annual" class="form-control" value="<?php echo getValue($college, 'hostel_fee_annual'); ?>">
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" name="ac_available" <?php echo !empty($college['ac_available'])?'checked':''; ?>> <label>AC Rooms Available</label>
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" name="mess_available" <?php echo !empty($college['mess_available'])?'checked':''; ?>> <label>Mess Available</label>
                            </div>
                            <div class="form-group">
                                <label>Mess Food Type</label>
                                <select name="mess_type" class="form-control">
                                    <option value="">Select</option>
                                    <option value="veg" <?php echo getValue($college, 'mess_type')=='veg'?'selected':''; ?>>Veg Only</option>
                                    <option value="non-veg" <?php echo getValue($college, 'mess_type')=='non-veg'?'selected':''; ?>>Non-Veg Only</option>
                                    <option value="both" <?php echo getValue($college, 'mess_type')=='both'?'selected':''; ?>>Both</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save About Details</button>
                    </div>
                </form>

                <?php elseif($current_tab == 'seo'): ?>
                <!-- SEO & PUBLISHING TAB -->
                <form action="" method="POST">
                    <div class="form-section">
                        <h3><i class="ph ph-globe"></i> SEO Settings</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Meta Title (Max 70 chars)</label>
                                <input type="text" name="meta_title" class="form-control" value="<?php echo getValue($college, 'meta_title'); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Meta Description (Max 160 chars)</label>
                                <textarea name="meta_description" class="form-control" rows="2"><?php echo getValue($college, 'meta_description'); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Meta Keywords</label>
                                <input type="text" name="meta_keywords" class="form-control" value="<?php echo getValue($college, 'meta_keywords'); ?>" placeholder="college, admission, engineering...">
                            </div>
                            <div class="form-group">
                                <label>OG Image URL</label>
                                <input type="url" name="og_image_url" class="form-control" value="<?php echo getValue($college, 'og_image_url'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Canonical URL</label>
                                <input type="url" name="canonical_url" class="form-control" value="<?php echo getValue($college, 'canonical_url'); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Schema Markup (JSON-LD)</label>
                                <textarea name="schema_markup" class="form-control" rows="4"><?php echo getValue($college, 'schema_markup'); ?></textarea>
                            </div>
                            <div class="form-group checkbox-group full">
                                <input type="checkbox" id="noindex" name="noindex" <?php echo !empty($college['noindex']) ? 'checked' : ''; ?>>
                                <label for="noindex" style="color:#dc2626;">Noindex (Hide from search engines)</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-paper-plane-right"></i> Publishing</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Publish Status</label>
                                <select name="publish_status" class="form-control">
                                    <option value="draft" <?php echo getValue($college, 'publish_status') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?php echo getValue($college, 'publish_status') == 'published' ? 'selected' : ''; ?>>Published</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Published At</label>
                                <input type="text" class="form-control" value="<?php echo !empty($college['published_at']) ? date('M d, Y H:i', strtotime($college['published_at'])) : 'Not published yet'; ?>" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save SEO & Publish Settings</button>
                    </div>
                </form>

                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>
