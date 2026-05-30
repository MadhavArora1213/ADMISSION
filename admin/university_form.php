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
    $slugCheckQ = "SELECT id FROM universities WHERE slug = :slug";
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
            
            $universityData = [
                'name' => $_POST['name'],
                'slug' => $slug,
                'university_type' => $_POST['university_type'] ?: null,
                'ownership' => $_POST['ownership'] ?: null,
                'status' => $_POST['status'],
                'logo_url' => $_POST['logo_url'],
                'cover_image_url' => $_POST['cover_image_url'],
                'founded_year' => $_POST['founded_year'] ?: null,
                'autonomous' => isset($_POST['autonomous']) ? 1 : 0,
                'ugc_approved' => isset($_POST['ugc_approved']) ? 1 : 0,
                'aicte_approved' => isset($_POST['aicte_approved']) ? 1 : 0,
                'total_students' => $_POST['total_students'] ?: null,
                'total_faculty' => $_POST['total_faculty'] ?: null,
                'campus_area_acres' => $_POST['campus_area_acres'] ?: null,
                'city_id' => !empty($_POST['city_id']) ? $_POST['city_id'] : null,
                'state_id' => !empty($_POST['state_id']) ? $_POST['state_id'] : null,
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
                'import_batch_id' => !empty($_POST['import_batch_id']) ? $_POST['import_batch_id'] : null,
                'type_label' => $_POST['type_label'] ?: null,
                'campus_type' => $_POST['campus_type'] ?: null
            ];

            if ($is_edit) {
                // Update University
                $updateFields = [];
                foreach ($universityData as $key => $val) {
                    $updateFields[] = "$key = :$key";
                }
                $sql = "UPDATE universities SET " . implode(", ", $updateFields) . " WHERE id = :id";
                $universityData['id'] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($universityData);
            } else {
                // Insert University
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $universityData['id'] = $id;
                $keys = array_keys($universityData);
                $sql = "INSERT INTO universities (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($universityData);
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
            $contactCheck = $pdo->prepare("SELECT id FROM university_contacts WHERE university_id = ?");
            $contactCheck->execute([$id]);
            
            if ($contactCheck->rowCount() > 0) {
                $contactFields = [];
                foreach ($contactData as $key => $val) {
                    $contactFields[] = "$key = :$key";
                }
                $contactData['university_id'] = $id;
                $sql = "UPDATE university_contacts SET " . implode(", ", $contactFields) . " WHERE university_id = :university_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($contactData);
            } else {
                $contactId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $contactData['id'] = $contactId;
                $contactData['university_id'] = $id;
                $keys = array_keys($contactData);
                $sql = "INSERT INTO university_contacts (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($contactData);
            }

            $pdo->commit();
            header('Location: university_form.php?id=' . $id . '&tab=identity&msg=saved');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error saving university: " . $e->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'about') {
    try {
        $pdo->beginTransaction();
        
        // 1. university_content
        $contentData = [
            'about_text' => $_POST['about_text'],
            'highlights_json' => $_POST['highlights_json'],
            'accreditations_json' => $_POST['accreditations'],
            'rankings_json' => $_POST['rankings_json'],
            'awards_json' => $_POST['awards_json']
        ];
        $chk = $pdo->prepare("SELECT id FROM university_content WHERE university_id = ?"); $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($contentData as $k=>$v) $fields[] = "$k = :$k";
            $contentData['university_id'] = $id;
            $pdo->prepare("UPDATE university_content SET " . implode(", ", $fields) . " WHERE university_id = :university_id")->execute($contentData);
        } else {
            $contentData['id'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); 
            $contentData['university_id'] = $id;
            $keys = array_keys($contentData);
            $pdo->prepare("INSERT INTO university_content (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($contentData);
        }

        // 2. university_admissions
        $admData = [
            'admission_process' => $_POST['admission_process'],
            'accepted_exams' => $_POST['accepted_exams'],
            'admission_start_date' => !empty($_POST['admission_start_date']) ? $_POST['admission_start_date'] : null,
            'admission_end_date' => !empty($_POST['admission_end_date']) ? $_POST['admission_end_date'] : null,
            'merit_based' => isset($_POST['merit_based']) ? 1 : 0,
            'direct_admission' => isset($_POST['direct_admission']) ? 1 : 0,
            'management_quota_seats' => $_POST['management_quota_seats'] ?: 0,
            'nri_quota_seats' => $_POST['nri_quota_seats'] ?: 0
        ];
        $chk = $pdo->prepare("SELECT id FROM university_admissions WHERE university_id = ?"); $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($admData as $k=>$v) $fields[] = "$k = :$k";
            $admData['university_id'] = $id;
            $pdo->prepare("UPDATE university_admissions SET " . implode(", ", $fields) . " WHERE university_id = :university_id")->execute($admData);
        } else {
            $admData['id'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); 
            $admData['university_id'] = $id;
            $keys = array_keys($admData);
            $pdo->prepare("INSERT INTO university_admissions (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($admData);
        }

        // 3. university_infrastructure
        $infData = [
            'library' => isset($_POST['library']) ? 1 : 0,
            'auditorium' => isset($_POST['auditorium']) ? 1 : 0,
            'cafeteria' => isset($_POST['cafeteria']) ? 1 : 0,
            'wifi' => isset($_POST['wifi']) ? 1 : 0,
            'medical_facility' => isset($_POST['medical_facility']) ? 1 : 0,
            'transport' => isset($_POST['transport']) ? 1 : 0,
            'sports_facilities' => $_POST['sports_facilities'],
            'labs' => $_POST['labs']
        ];
        $chk = $pdo->prepare("SELECT id FROM university_infrastructure WHERE university_id = ?"); $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($infData as $k=>$v) $fields[] = "$k = :$k";
            $infData['university_id'] = $id;
            $pdo->prepare("UPDATE university_infrastructure SET " . implode(", ", $fields) . " WHERE university_id = :university_id")->execute($infData);
        } else {
            $infData['id'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); 
            $infData['university_id'] = $id;
            $keys = array_keys($infData);
            $pdo->prepare("INSERT INTO university_infrastructure (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($infData);
        }

        // 4. university_hostels
        $hstData = [
            'hostel_available' => isset($_POST['hostel_available']) ? 1 : 0,
            'hostel_type' => $_POST['hostel_type'] ?: null,
            'hostel_capacity' => $_POST['hostel_capacity'] ?: null,
            'hostel_fee_annual' => $_POST['hostel_fee_annual'] ?: null,
            'mess_available' => isset($_POST['mess_available']) ? 1 : 0,
            'mess_type' => $_POST['mess_type'] ?: null,
            'ac_available' => isset($_POST['ac_available']) ? 1 : 0
        ];
        $chk = $pdo->prepare("SELECT id FROM university_hostels WHERE university_id = ?"); $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($hstData as $k=>$v) $fields[] = "$k = :$k";
            $hstData['university_id'] = $id;
            $pdo->prepare("UPDATE university_hostels SET " . implode(", ", $fields) . " WHERE university_id = :university_id")->execute($hstData);
        } else {
            $hstData['id'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); 
            $hstData['university_id'] = $id;
            $keys = array_keys($hstData);
            $pdo->prepare("INSERT INTO university_hostels (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($hstData);
        }

        $pdo->commit();
        
        header('Location: university_form.php?id=' . $id . '&tab=about&msg=saved');
        exit;
    } catch (Exception $e) {
        $error = "Error saving About details: " . $e->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'seo') {
    try {
        $publish_status = $_POST['publish_status'];
        $published_at = ($publish_status == 'published' && getValue($university, 'publish_status') != 'published') ? date('Y-m-d H:i:s') : getValue($university, 'published_at');
        
        $pdo->beginTransaction();
        
        $pdo->prepare("UPDATE universities SET publish_status = ? WHERE id = ?")->execute([$_POST['publish_status'], $id]);
        
        $seoData = [
            'meta_title' => $_POST['meta_title'],
            'meta_description' => $_POST['meta_description'],
            'og_image_url' => $_POST['og_image_url'],
            'canonical_url' => $_POST['canonical_url'],
            'schema_markup' => $_POST['schema_markup'],
            'noindex' => isset($_POST['noindex']) ? 1 : 0
        ];
        
        $chk = $pdo->prepare("SELECT id FROM seo_meta WHERE entity_type = 'university' AND entity_id = ?"); 
        $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($seoData as $k=>$v) $fields[] = "$k = :$k";
            $seoData['entity_id'] = $id;
            $pdo->prepare("UPDATE seo_meta SET " . implode(", ", $fields) . " WHERE entity_type = 'university' AND entity_id = :entity_id")->execute($seoData);
        } else {
            $seoData['id'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); 
            $seoData['entity_type'] = 'university';
            $seoData['entity_id'] = $id;
            $keys = array_keys($seoData);
            $pdo->prepare("INSERT INTO seo_meta (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($seoData);
        }
        
        $pdo->commit();
        
        header('Location: university_form.php?id=' . $id . '&tab=seo&msg=saved');
        exit;
    } catch (Exception $e) {
        $error = "Error saving SEO details: " . $e->getMessage();
    }
}

// Fetch Reference Data for Dropdowns
$states = $pdo->query("SELECT * FROM states ORDER BY name ASC")->fetchAll();
$cities = $pdo->query("SELECT * FROM cities ORDER BY name ASC")->fetchAll();
$universities = $pdo->query("SELECT * FROM universities ORDER BY name ASC")->fetchAll();
$users = $pdo->query("SELECT id, full_name as name FROM users ORDER BY full_name ASC")->fetchAll();
$allUniversities = $pdo->query("SELECT id, name FROM universities ORDER BY name ASC")->fetchAll();

// Fetch existing data if edit
$university = [];
$contact = [];
if ($is_edit) {
    $query = "
        SELECT 
            c.*, 
            cc.website_url, cc.email, cc.phone, cc.address, cc.latitude, cc.longitude, cc.pincode, cc.google_maps_embed_url as google_maps_url, cc.nearest_railway_km, cc.nearest_airport_km,
            ct.about_text, ct.highlights_json, ct.accreditations_json AS accreditations, ct.rankings_json, ct.awards_json,
            ca.admission_process, ca.accepted_exams, ca.admission_start_date, ca.admission_end_date, ca.merit_based, ca.direct_admission, ca.management_quota_seats, ca.nri_quota_seats, ca.lateral_entry_available, ca.application_mode,
            ci.library, ci.auditorium, ci.cafeteria, ci.wifi, ci.medical_facility, ci.transport, ci.ev_charging, ci.solar_power, ci.sports_facilities, ci.labs,
            ch.hostel_available, ch.hostel_type, ch.hostel_capacity, ch.hostel_fee_annual, ch.mess_available, ch.mess_type, ch.ac_available, ch.laundry_available,
            sm.meta_title, sm.meta_description, sm.og_image_url, sm.canonical_url, sm.schema_markup, sm.noindex
        FROM universities c
        LEFT JOIN university_contacts cc ON c.id = cc.university_id
        LEFT JOIN university_content ct ON c.id = ct.university_id
        LEFT JOIN university_admissions ca ON c.id = ca.university_id
        LEFT JOIN university_infrastructure ci ON c.id = ci.university_id
        LEFT JOIN university_hostels ch ON c.id = ch.university_id
        LEFT JOIN seo_meta sm ON c.id = sm.entity_id AND sm.entity_type = 'university'
        WHERE c.id = ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $university = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fallback for $contact variables since we merged everything into $university row
    $contact = $university;
    
    if (!$university) {
        header('Location: universities.php');
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
    <title><?php echo $is_edit ? 'Edit University' : 'Add New University'; ?> | AdmissionSeason Admin</title>
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
                        <a href="universities.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                        <?php echo $is_edit ? 'Edit University: ' . htmlspecialchars($university['name']) : 'Add New University'; ?>
                    </h2>
                </div>

                <?php if($is_edit): ?>
                <div class="tabs-nav">
                    <a href="?id=<?php echo $id; ?>&tab=identity" class="tab-link <?php echo $current_tab=='identity'?'active':''; ?>">Identity & Contact</a>
                    <a href="?id=<?php echo $id; ?>&tab=about" class="tab-link <?php echo $current_tab=='about'?'active':''; ?>">About & Amenities</a>
                    <a href="?id=<?php echo $id; ?>&tab=seo" class="tab-link <?php echo $current_tab=='seo'?'active':''; ?>">SEO & Publish</a>
                    <a href="university_courses.php?university_id=<?php echo $id; ?>" class="tab-link">Courses & Fees</a>
                    <a href="university_placements.php?university_id=<?php echo $id; ?>" class="tab-link">Placements</a>
                    <a href="university_cutoffs.php?university_id=<?php echo $id; ?>" class="tab-link">Cutoffs</a>
                    <a href="university_media.php?university_id=<?php echo $id; ?>" class="tab-link">Media & Gallery</a>
                    <a href="university_faqs.php?university_id=<?php echo $id; ?>" class="tab-link">FAQs</a>
                    <a href="university_faculty.php?university_id=<?php echo $id; ?>" class="tab-link">Faculty</a>
                    <a href="university_scholarships.php?university_id=<?php echo $id; ?>" class="tab-link">Scholarships</a>
                </div>
                <?php else: ?>
                <div class="tabs-nav">
                    <span class="tab-link active">Identity & Contact</span>
                    <span class="tab-link disabled" title="Save university first to unlock">About & Amenities</span>
                    <span class="tab-link disabled" title="Save university first to unlock">SEO & Publish</span>
                    <span class="tab-link disabled" title="Save university first to unlock">Courses & Fees</span>
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
                        <h3><i class="ph ph-identification-card"></i> University Identity</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>University Name *</label>
                                <input type="text" name="name" class="form-control" required value="<?php echo getValue($university, 'name'); ?>">
                            </div>
                            <div class="form-group">
                                <label>URL Slug (Leave blank to auto-generate)</label>
                                <input type="text" name="slug" class="form-control" value="<?php echo getValue($university, 'slug'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Status *</label>
                                <select name="status" class="form-control" required>
                                    <option value="pending" <?php echo getValue($university, 'status') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="active" <?php echo getValue($university, 'status') == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="archived" <?php echo getValue($university, 'status') == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>University Type</label>
                                <select name="university_type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="govt" <?php echo getValue($university, 'university_type') == 'govt' ? 'selected' : ''; ?>>Government</option>
                                    <option value="private" <?php echo getValue($university, 'university_type') == 'private' ? 'selected' : ''; ?>>Private</option>
                                    <option value="deemed" <?php echo getValue($university, 'university_type') == 'deemed' ? 'selected' : ''; ?>>Deemed</option>
                                    <option value="autonomous" <?php echo getValue($university, 'university_type') == 'autonomous' ? 'selected' : ''; ?>>Autonomous</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ownership</label>
                                <select name="ownership" class="form-control">
                                    <option value="">Select Ownership</option>
                                    <option value="public" <?php echo getValue($university, 'ownership') == 'public' ? 'selected' : ''; ?>>Public</option>
                                    <option value="private" <?php echo getValue($university, 'ownership') == 'private' ? 'selected' : ''; ?>>Private</option>
                                    <option value="trust" <?php echo getValue($university, 'ownership') == 'trust' ? 'selected' : ''; ?>>Trust</option>
                                    <option value="society" <?php echo getValue($university, 'ownership') == 'society' ? 'selected' : ''; ?>>Society</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Type Label (Display Text)</label>
                                <input type="text" name="type_label" class="form-control" value="<?php echo getValue($university, 'type_label'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Founded Year</label>
                                <input type="number" name="founded_year" class="form-control" min="1800" max="2099" value="<?php echo getValue($university, 'founded_year'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Campus Type</label>
                                <select name="campus_type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="urban" <?php echo getValue($university, 'campus_type') == 'urban' ? 'selected' : ''; ?>>Urban</option>
                                    <option value="semi-urban" <?php echo getValue($university, 'campus_type') == 'semi-urban' ? 'selected' : ''; ?>>Semi-Urban</option>
                                    <option value="rural" <?php echo getValue($university, 'campus_type') == 'rural' ? 'selected' : ''; ?>>Rural</option>
                                </select>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="is_verified" name="is_verified" <?php echo !empty($university['is_verified']) ? 'checked' : ''; ?>>
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
                                <input type="url" name="logo_url" class="form-control" placeholder="https://..." value="<?php echo getValue($university, 'logo_url'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Cover Image URL</label>
                                <input type="url" name="cover_image_url" class="form-control" placeholder="https://..." value="<?php echo getValue($university, 'cover_image_url'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>NAAC Grade</label>
                                <select name="naac_grade" class="form-control">
                                    <option value="None">None</option>
                                    <?php 
                                    $grades = ['A++', 'A+', 'A', 'B++', 'B+', 'B', 'C'];
                                    foreach($grades as $g) {
                                        $sel = getValue($university, 'naac_grade') == $g ? 'selected' : '';
                                        echo "<option value='$g' $sel>$g</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>NIRF Rank</label>
                                <input type="number" name="nirf_rank" class="form-control" value="<?php echo getValue($university, 'nirf_rank'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <div class="checkbox-group" style="margin-top:0;">
                                    <input type="checkbox" id="autonomous" name="autonomous" <?php echo !empty($university['autonomous']) ? 'checked' : ''; ?>>
                                    <label for="autonomous">Autonomous</label>
                                </div>
                                <div class="checkbox-group" style="margin-top:10px;">
                                    <input type="checkbox" id="ugc_approved" name="ugc_approved" <?php echo !empty($university['ugc_approved']) ? 'checked' : ''; ?>>
                                    <label for="ugc_approved">UGC Approved</label>
                                </div>
                                <div class="checkbox-group" style="margin-top:10px;">
                                    <input type="checkbox" id="aicte_approved" name="aicte_approved" <?php echo !empty($university['aicte_approved']) ? 'checked' : ''; ?>>
                                    <label for="aicte_approved">AICTE Approved</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Total Students</label>
                                <input type="number" name="total_students" class="form-control" value="<?php echo getValue($university, 'total_students'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Total Faculty</label>
                                <input type="number" name="total_faculty" class="form-control" value="<?php echo getValue($university, 'total_faculty'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Campus Area (Acres)</label>
                                <input type="number" step="0.1" name="campus_area_acres" class="form-control" value="<?php echo getValue($university, 'campus_area_acres'); ?>">
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
                                        <option value="<?php echo $s['id']; ?>" <?php echo getValue($university, 'state_id') == $s['id'] ? 'selected' : ''; ?>>
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
                                        <option value="<?php echo $c['id']; ?>" <?php echo getValue($university, 'city_id') == $c['id'] ? 'selected' : ''; ?>>
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
                                <input type="checkbox" id="is_featured" name="is_featured" <?php echo !empty($university['is_featured']) ? 'checked' : ''; ?>>
                                <label for="is_featured">Feature this university on homepage/listings</label>
                            </div>
                            <div class="form-group">
                                <label>Featured Order (Higher means top priority)</label>
                                <input type="number" name="featured_order" class="form-control" value="<?php echo getValue($university, 'featured_order', 0); ?>">
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
                                    <option value="pending" <?php echo getValue($university, 'verification_status') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="verified" <?php echo getValue($university, 'verification_status') == 'verified' ? 'selected' : ''; ?>>Verified</option>
                                    <option value="rejected" <?php echo getValue($university, 'verification_status') == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Verified By</label>
                                <select name="verified_by" class="form-control">
                                    <option value="">Select User</option>
                                    <?php foreach($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo getValue($university, 'verified_by') == $u['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Verified At</label>
                                <input type="datetime-local" name="verified_at" class="form-control" value="<?php echo !empty($university['verified_at']) ? date('Y-m-d\TH:i', strtotime($university['verified_at'])) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Data Quality Score (0-100)</label>
                                <input type="number" name="data_quality_score" class="form-control" min="0" max="100" value="<?php echo getValue($university, 'data_quality_score', 0); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Rejection Reason</label>
                                <textarea name="rejection_reason" class="form-control" rows="2"><?php echo getValue($university, 'rejection_reason'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Duplicate Of</label>
                                <select name="duplicate_of" class="form-control">
                                    <option value="">Select University (If Duplicate)</option>
                                    <?php foreach($allUniversities as $ac): 
                                        if($ac['id'] == $id) continue; // Skip self
                                    ?>
                                        <option value="<?php echo $ac['id']; ?>" <?php echo getValue($university, 'duplicate_of') == $ac['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ac['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Import Batch ID (UUID)</label>
                                <input type="text" name="import_batch_id" class="form-control" value="<?php echo getValue($university, 'import_batch_id'); ?>" placeholder="e.g. 123e4567-e89b-12d3-a456-426614174000">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="universities.php" class="btn btn-outline">Cancel</a>
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
                                <textarea name="about_text" class="form-control" rows="5"><?php echo getValue($university, 'about_text'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Highlights (JSON List)</label>
                                <textarea name="highlights_json" class="form-control" rows="3" placeholder='["Excellent Campus", "Top Recruiters"]'><?php echo getValue($university, 'highlights_json'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Accreditations (JSON List)</label>
                                <textarea name="accreditations" class="form-control" rows="3"><?php echo getValue($university, 'accreditations'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Rankings (JSON Object)</label>
                                <textarea name="rankings_json" class="form-control" rows="3" placeholder='{"NIRF": 12, "India Today": 5}'><?php echo getValue($university, 'rankings_json'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Awards (JSON List)</label>
                                <textarea name="awards_json" class="form-control" rows="3"><?php echo getValue($university, 'awards_json'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-door-open"></i> Admissions</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Admission Process Description</label>
                                <textarea name="admission_process" class="form-control" rows="4"><?php echo getValue($university, 'admission_process'); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Accepted Exams (JSON List)</label>
                                <input type="text" name="accepted_exams" class="form-control" value="<?php echo getValue($university, 'accepted_exams'); ?>" placeholder='["JEE Main", "SAT"]'>
                            </div>
                            <div class="form-group">
                                <label>Admission Start Date</label>
                                <input type="date" name="admission_start_date" class="form-control" value="<?php echo getValue($university, 'admission_start_date'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Admission End Date</label>
                                <input type="date" name="admission_end_date" class="form-control" value="<?php echo getValue($university, 'admission_end_date'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Management Quota Seats</label>
                                <input type="number" name="management_quota_seats" class="form-control" value="<?php echo getValue($university, 'management_quota_seats', 0); ?>">
                            </div>
                            <div class="form-group">
                                <label>NRI Quota Seats</label>
                                <input type="number" name="nri_quota_seats" class="form-control" value="<?php echo getValue($university, 'nri_quota_seats', 0); ?>">
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="merit_based" name="merit_based" <?php echo !empty($university['merit_based']) ? 'checked' : ''; ?>>
                                <label for="merit_based">Merit Based Admission</label>
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="direct_admission" name="direct_admission" <?php echo !empty($university['direct_admission']) ? 'checked' : ''; ?>>
                                <label for="direct_admission">Direct Admission Available</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-buildings"></i> Infrastructure</h3>
                        <div class="form-grid">
                            <div class="form-group checkbox-group"><input type="checkbox" name="library" <?php echo !empty($university['library'])?'checked':''; ?>> <label>Library</label></div>
                            <div class="form-group checkbox-group"><input type="checkbox" name="auditorium" <?php echo !empty($university['auditorium'])?'checked':''; ?>> <label>Auditorium</label></div>
                            <div class="form-group checkbox-group"><input type="checkbox" name="cafeteria" <?php echo !empty($university['cafeteria'])?'checked':''; ?>> <label>Cafeteria</label></div>
                            <div class="form-group checkbox-group"><input type="checkbox" name="wifi" <?php echo !empty($university['wifi'])?'checked':''; ?>> <label>Wi-Fi Campus</label></div>
                            <div class="form-group checkbox-group"><input type="checkbox" name="medical_facility" <?php echo !empty($university['medical_facility'])?'checked':''; ?>> <label>Medical Facility</label></div>
                            <div class="form-group checkbox-group"><input type="checkbox" name="transport" <?php echo !empty($university['transport'])?'checked':''; ?>> <label>Transport</label></div>
                            
                            <div class="form-group full" style="margin-top: 16px;">
                                <label>Sports Facilities (JSON List)</label>
                                <input type="text" name="sports_facilities" class="form-control" value="<?php echo getValue($university, 'sports_facilities'); ?>" placeholder='["Cricket", "Football", "Basketball"]'>
                            </div>
                            <div class="form-group full">
                                <label>Labs (JSON List)</label>
                                <input type="text" name="labs" class="form-control" value="<?php echo getValue($university, 'labs'); ?>" placeholder='["Computer Lab", "Physics Lab"]'>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-house-line"></i> Hostel & Mess</h3>
                        <div class="form-grid">
                            <div class="form-group checkbox-group full">
                                <input type="checkbox" name="hostel_available" <?php echo !empty($university['hostel_available'])?'checked':''; ?>> 
                                <label style="font-weight:700;">Hostel Facility Available</label>
                            </div>
                            <div class="form-group">
                                <label>Hostel Type</label>
                                <select name="hostel_type" class="form-control">
                                    <option value="">Select</option>
                                    <option value="boys" <?php echo getValue($university, 'hostel_type')=='boys'?'selected':''; ?>>Boys Only</option>
                                    <option value="girls" <?php echo getValue($university, 'hostel_type')=='girls'?'selected':''; ?>>Girls Only</option>
                                    <option value="both" <?php echo getValue($university, 'hostel_type')=='both'?'selected':''; ?>>Both</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Hostel Capacity (Total Beds)</label>
                                <input type="number" name="hostel_capacity" class="form-control" value="<?php echo getValue($university, 'hostel_capacity', 0); ?>">
                            </div>
                            <div class="form-group">
                                <label>Hostel Fee (Annual)</label>
                                <input type="number" step="0.01" name="hostel_fee_annual" class="form-control" value="<?php echo getValue($university, 'hostel_fee_annual'); ?>">
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" name="ac_available" <?php echo !empty($university['ac_available'])?'checked':''; ?>> <label>AC Rooms Available</label>
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" name="mess_available" <?php echo !empty($university['mess_available'])?'checked':''; ?>> <label>Mess Available</label>
                            </div>
                            <div class="form-group">
                                <label>Mess Food Type</label>
                                <select name="mess_type" class="form-control">
                                    <option value="">Select</option>
                                    <option value="veg" <?php echo getValue($university, 'mess_type')=='veg'?'selected':''; ?>>Veg Only</option>
                                    <option value="non-veg" <?php echo getValue($university, 'mess_type')=='non-veg'?'selected':''; ?>>Non-Veg Only</option>
                                    <option value="both" <?php echo getValue($university, 'mess_type')=='both'?'selected':''; ?>>Both</option>
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
                                <input type="text" name="meta_title" class="form-control" value="<?php echo getValue($university, 'meta_title'); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Meta Description (Max 160 chars)</label>
                                <textarea name="meta_description" class="form-control" rows="2"><?php echo getValue($university, 'meta_description'); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Meta Keywords</label>
                                <input type="text" name="meta_keywords" class="form-control" value="<?php echo getValue($university, 'meta_keywords'); ?>" placeholder="university, admission, engineering...">
                            </div>
                            <div class="form-group">
                                <label>OG Image URL</label>
                                <input type="url" name="og_image_url" class="form-control" value="<?php echo getValue($university, 'og_image_url'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Canonical URL</label>
                                <input type="url" name="canonical_url" class="form-control" value="<?php echo getValue($university, 'canonical_url'); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Schema Markup (JSON-LD)</label>
                                <textarea name="schema_markup" class="form-control" rows="4"><?php echo getValue($university, 'schema_markup'); ?></textarea>
                            </div>
                            <div class="form-group checkbox-group full">
                                <input type="checkbox" id="noindex" name="noindex" <?php echo !empty($university['noindex']) ? 'checked' : ''; ?>>
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
                                    <option value="draft" <?php echo getValue($university, 'publish_status') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?php echo getValue($university, 'publish_status') == 'published' ? 'selected' : ''; ?>>Published</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Published At</label>
                                <input type="text" class="form-control" value="<?php echo !empty($university['published_at']) ? date('M d, Y H:i', strtotime($university['published_at'])) : 'Not published yet'; ?>" disabled>
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
