<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // header('Location: index.php');
    // exit;
}
require_once 'db.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$is_edit = $id !== null;
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'identity';
$msg = '';
$error = '';

// Helpers for parsing text to JSON and back
function linesToJsonList($text) {
    if (empty(trim($text))) return null;
    $lines = array_filter(array_map('trim', explode("\n", $text)), 'strlen');
    return empty($lines) ? null : json_encode(array_values($lines));
}
function linesToJsonObject($text) {
    if (empty(trim($text))) return null;
    $lines = array_filter(array_map('trim', explode("\n", $text)), 'strlen');
    $obj = [];
    foreach($lines as $line) {
        $parts = explode(':', $line, 2);
        if (count($parts) == 2) {
            $obj[trim($parts[0])] = trim($parts[1]);
        }
    }
    return empty($obj) ? null : json_encode($obj);
}
function jsonToLines($jsonStr, $isObject = false) {
    if (empty($jsonStr)) return '';
    $data = json_decode($jsonStr, true);
    if (!is_array($data)) return htmlspecialchars($jsonStr);
    if ($isObject) {
        $lines = [];
        foreach($data as $k => $v) $lines[] = "$k: $v";
        return htmlspecialchars(implode("\n", $lines));
    }
    return htmlspecialchars(implode("\n", $data));
}


function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'identity') {
    $slug = !empty($_POST['slug']) ? $_POST['slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])));
    
    $slugCheckQ = "SELECT id FROM colleges WHERE slug = :slug";
    if ($is_edit) $slugCheckQ .= " AND id != :id";
    $slugCheckStmt = $pdo->prepare($slugCheckQ);
    $slugCheckParams = ['slug' => $slug];
    if ($is_edit) $slugCheckParams['id'] = $id;
    $slugCheckStmt->execute($slugCheckParams);
    
    if ($slugCheckStmt->rowCount() > 0) {
        $error = "The slug '$slug' is already in use.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Colleges Table
            $collegeData = [
                'name' => $_POST['name'],
                'slug' => $slug,
                'college_type' => $_POST['college_type'] ?: null,
                'ownership' => $_POST['ownership'] ?: null,
                'status' => $_POST['status'],
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'is_verified' => isset($_POST['is_verified']) ? 1 : 0,
                'featured_order' => $_POST['featured_order'] ?: 0,
                'ranking_nirf' => $_POST['ranking_nirf'] ?: null,
                'city_id' => !empty($_POST['city_id']) ? $_POST['city_id'] : null,
                'state_id' => !empty($_POST['state_id']) ? $_POST['state_id'] : null,
                'founded_year' => $_POST['founded_year'] ?: null,
                'data_quality_score' => $_POST['data_quality_score'] ?: 0,
                'university_id' => !empty($_POST['university_id']) ? $_POST['university_id'] : null,
                'autonomous' => isset($_POST['autonomous']) ? 1 : 0,
                'naac_grade' => $_POST['naac_grade'] ?: null,
                'ugc_approved' => isset($_POST['ugc_approved']) ? 1 : 0,
                'aicte_approved' => isset($_POST['aicte_approved']) ? 1 : 0,
                'total_students' => $_POST['total_students'] ?: null,
                'total_faculty' => $_POST['total_faculty'] ?: null,
                'campus_area_acres' => $_POST['campus_area_acres'] ?: null,
                'verification_status' => $_POST['verification_status'] ?: 'unverified',
                'verified_by' => !empty($_POST['verified_by']) ? $_POST['verified_by'] : null,
                'verified_at' => !empty($_POST['verified_at']) ? $_POST['verified_at'] : null,
                'rejection_reason' => $_POST['rejection_reason'] ?: null,
                'duplicate_of' => !empty($_POST['duplicate_of']) ? $_POST['duplicate_of'] : null,
                'import_batch_id' => !empty($_POST['import_batch_id']) ? $_POST['import_batch_id'] : null,
                'type_label' => $_POST['type_label'] ?: null,
                'campus_type' => $_POST['campus_type'] ?: null
            ];

            if ($is_edit) {
                $fields = [];
                foreach ($collegeData as $k => $v) $fields[] = "$k = :$k";
                $collegeData['id'] = $id;
                $stmt = $pdo->prepare("UPDATE colleges SET " . implode(", ", $fields) . " WHERE id = :id");
                $stmt->execute($collegeData);
            } else {
                $id = generateUUID();
                $collegeData['id'] = $id;
                $keys = array_keys($collegeData);
                $stmt = $pdo->prepare("INSERT INTO colleges (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")");
                $stmt->execute($collegeData);
            }

            // Handle file uploads
            $upload_dir = '../uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $_POST['logo_url'] = $_POST['existing_logo_url'] ?? '';
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == 0) {
                $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
                $filename = 'college_logo_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_dir . $filename)) {
                    $_POST['logo_url'] = 'uploads/' . $filename;
                    require_once __DIR__ . '/upload_sync.php';
                    sync_to_github('uploads/' . $filename);
                }
            }

            $_POST['cover_image_url'] = $_POST['existing_cover_image_url'] ?? '';
            if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] == 0) {
                $ext = pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION);
                $filename = 'college_cover_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['cover_file']['tmp_name'], $upload_dir . $filename)) {
                    $_POST['cover_image_url'] = 'uploads/' . $filename;
                    require_once __DIR__ . '/upload_sync.php';
                    sync_to_github('uploads/' . $filename);
                }
            }

            // 2. College Media (Logo & Cover)
            $mediaCheck = $pdo->prepare("SELECT id FROM college_media WHERE college_id = ? AND image_type IS NULL");
            $mediaCheck->execute([$id]);
            if ($mediaCheck->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE college_media SET logo_url = ?, cover_image_url = ? WHERE college_id = ? AND image_type IS NULL");
                $stmt->execute([$_POST['logo_url'], $_POST['cover_image_url'], $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO college_media (id, college_id, logo_url, cover_image_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([generateUUID(), $id, $_POST['logo_url'], $_POST['cover_image_url']]);
            }

            // 3. College Contacts
            $contactData = [
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'address' => $_POST['address'],
                'latitude' => $_POST['latitude'] ?: null,
                'longitude' => $_POST['longitude'] ?: null,
                'website_url' => $_POST['website_url'],
                'pincode' => $_POST['pincode'],
                'google_maps_embed_url' => $_POST['google_maps_embed_url'],
                'nearest_railway_km' => $_POST['nearest_railway_km'] ?: null,
                'nearest_airport_km' => $_POST['nearest_airport_km'] ?: null
            ];

            $contactCheck = $pdo->prepare("SELECT id FROM college_contacts WHERE college_id = ?");
            $contactCheck->execute([$id]);
            if ($contactCheck->rowCount() > 0) {
                $fields = []; foreach ($contactData as $k => $v) $fields[] = "$k = :$k";
                $contactData['college_id'] = $id;
                $stmt = $pdo->prepare("UPDATE college_contacts SET " . implode(", ", $fields) . " WHERE college_id = :college_id");
                $stmt->execute($contactData);
            } else {
                $contactData['id'] = generateUUID();
                $contactData['college_id'] = $id;
                $keys = array_keys($contactData);
                $stmt = $pdo->prepare("INSERT INTO college_contacts (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")");
                $stmt->execute($contactData);
            }

            $pdo->commit();
            header("Location: college_form.php?id=$id&tab=identity&msg=saved");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error saving identity details: " . $e->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'about') {
    try {
        $pdo->beginTransaction();
        
        // 1. college_content
        $contentData = [
            'about_text' => $_POST['about_text'],
            'highlights_json' => linesToJsonList($_POST['highlights_json']),
            'accreditations_json' => linesToJsonList($_POST['accreditations']),
            'rankings_json' => linesToJsonObject($_POST['rankings_json']),
            'awards_json' => linesToJsonList($_POST['awards_json'])
        ];
        $chk = $pdo->prepare("SELECT id FROM college_content WHERE college_id = ?"); $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($contentData as $k=>$v) $fields[] = "$k = :$k";
            $contentData['college_id'] = $id;
            $pdo->prepare("UPDATE college_content SET " . implode(", ", $fields) . " WHERE college_id = :college_id")->execute($contentData);
        } else {
            $contentData['id'] = generateUUID(); $contentData['college_id'] = $id;
            $keys = array_keys($contentData);
            $pdo->prepare("INSERT INTO college_content (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($contentData);
        }

        // 2. college_admissions
        $admData = [
            'admission_process' => $_POST['admission_process'],
            'accepted_exams' => linesToJsonList($_POST['accepted_exams']),
            'admission_start_date' => $_POST['admission_start_date'] ?: null,
            'admission_end_date' => $_POST['admission_end_date'] ?: null,
            'merit_based' => isset($_POST['merit_based']) ? 1 : 0,
            'direct_admission' => isset($_POST['direct_admission']) ? 1 : 0,
            'management_quota_seats' => $_POST['management_quota_seats'] ?: 0,
            'nri_quota_seats' => $_POST['nri_quota_seats'] ?: 0,
            'lateral_entry_available' => isset($_POST['lateral_entry_available']) ? 1 : 0,
            'application_mode' => $_POST['application_mode'] ?: null
        ];
        $chk = $pdo->prepare("SELECT id FROM college_admissions WHERE college_id = ?"); $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($admData as $k=>$v) $fields[] = "$k = :$k";
            $admData['college_id'] = $id;
            $pdo->prepare("UPDATE college_admissions SET " . implode(", ", $fields) . " WHERE college_id = :college_id")->execute($admData);
        } else {
            $admData['id'] = generateUUID(); $admData['college_id'] = $id;
            $keys = array_keys($admData);
            $pdo->prepare("INSERT INTO college_admissions (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($admData);
        }

        // 3. college_infrastructure
        $infData = [
            'library' => isset($_POST['library']) ? 1 : 0,
            'auditorium' => isset($_POST['auditorium']) ? 1 : 0,
            'cafeteria' => isset($_POST['cafeteria']) ? 1 : 0,
            'wifi' => isset($_POST['wifi']) ? 1 : 0,
            'medical_facility' => isset($_POST['medical_facility']) ? 1 : 0,
            'transport' => isset($_POST['transport']) ? 1 : 0,
            'ev_charging' => isset($_POST['ev_charging']) ? 1 : 0,
            'solar_power' => isset($_POST['solar_power']) ? 1 : 0,
            'sports_facilities' => linesToJsonList($_POST['sports_facilities']),
            'labs' => linesToJsonList($_POST['labs'])
        ];
        $chk = $pdo->prepare("SELECT id FROM college_infrastructure WHERE college_id = ?"); $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($infData as $k=>$v) $fields[] = "$k = :$k";
            $infData['college_id'] = $id;
            $pdo->prepare("UPDATE college_infrastructure SET " . implode(", ", $fields) . " WHERE college_id = :college_id")->execute($infData);
        } else {
            $infData['id'] = generateUUID(); $infData['college_id'] = $id;
            $keys = array_keys($infData);
            $pdo->prepare("INSERT INTO college_infrastructure (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($infData);
        }

        // 4. college_hostels
        $hstData = [
            'hostel_available' => isset($_POST['hostel_available']) ? 1 : 0,
            'hostel_type' => $_POST['hostel_type'] ?: null,
            'hostel_capacity' => $_POST['hostel_capacity'] ?: null,
            'hostel_fee_annual' => $_POST['hostel_fee_annual'] ?: null,
            'mess_available' => isset($_POST['mess_available']) ? 1 : 0,
            'mess_type' => $_POST['mess_type'] ?: null,
            'ac_available' => isset($_POST['ac_available']) ? 1 : 0,
            'laundry_available' => isset($_POST['laundry_available']) ? 1 : 0
        ];
        $chk = $pdo->prepare("SELECT id FROM college_hostels WHERE college_id = ?"); $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($hstData as $k=>$v) $fields[] = "$k = :$k";
            $hstData['college_id'] = $id;
            $pdo->prepare("UPDATE college_hostels SET " . implode(", ", $fields) . " WHERE college_id = :college_id")->execute($hstData);
        } else {
            $hstData['id'] = generateUUID(); $hstData['college_id'] = $id;
            $keys = array_keys($hstData);
            $pdo->prepare("INSERT INTO college_hostels (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($hstData);
        }

        $pdo->commit();
        header("Location: college_form.php?id=$id&tab=about&msg=saved");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error saving About details: " . $e->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'seo') {
    try {
        $pdo->beginTransaction();
        
        $pdo->prepare("UPDATE colleges SET publish_status = ? WHERE id = ?")->execute([$_POST['publish_status'], $id]);
        
        // Handle SEO image upload
        $upload_dir = '../uploads/';
        $_POST['og_image_url'] = $_POST['existing_og_image_url'] ?? '';
        if (isset($_FILES['og_image_file']) && $_FILES['og_image_file']['error'] == 0) {
            $ext = pathinfo($_FILES['og_image_file']['name'], PATHINFO_EXTENSION);
            $filename = 'college_og_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['og_image_file']['tmp_name'], $upload_dir . $filename)) {
                $_POST['og_image_url'] = 'uploads/' . $filename;
                require_once __DIR__ . '/upload_sync.php';
                sync_to_github('uploads/' . $filename);
            }
        }
        
        $schema_markup = $_POST['schema_markup'];
        if (empty(trim($schema_markup))) {
            $uStmt = $pdo->prepare("SELECT c.name, cm.logo_url, ct.about_text, cc.address, cc.website_url, cc.phone 
                                    FROM colleges c 
                                    LEFT JOIN college_media cm ON c.id = cm.college_id AND cm.image_type IS NULL
                                    LEFT JOIN college_contacts cc ON c.id = cc.college_id 
                                    LEFT JOIN college_content ct ON c.id = ct.college_id 
                                    WHERE c.id = ?");
            $uStmt->execute([$id]);
            $uData = $uStmt->fetch(PDO::FETCH_ASSOC);
            if ($uData) {
                $schema = [
                    "@context" => "https://schema.org",
                    "@type" => "CollegeOrUniversity",
                    "name" => $uData['name'],
                    "description" => $uData['about_text'] ? substr(strip_tags($uData['about_text']), 0, 160) : '',
                    "url" => $uData['website_url'] ?: '',
                    "telephone" => $uData['phone'] ?: '',
                    "address" => [
                        "@type" => "PostalAddress",
                        "streetAddress" => $uData['address'] ?: '',
                    ]
                ];
                if ($uData['logo_url']) {
                    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                    $schema["logo"] = "https://".$host."/".ltrim($uData['logo_url'], '/');
                }
                $schema_markup = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
        }
        
        $seoData = [
            'meta_title' => $_POST['meta_title'],
            'meta_description' => $_POST['meta_description'],
            'og_image_url' => $_POST['og_image_url'],
            'canonical_url' => $_POST['canonical_url'],
            'schema_markup' => $schema_markup,
            'noindex' => isset($_POST['noindex']) ? 1 : 0
        ];
        
        $chk = $pdo->prepare("SELECT id FROM seo_meta WHERE page_type = 'college' AND page_id = ?"); 
        $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($seoData as $k=>$v) $fields[] = "$k = :$k";
            $seoData['page_id'] = $id;
            $pdo->prepare("UPDATE seo_meta SET " . implode(", ", $fields) . " WHERE page_type = 'college' AND page_id = :page_id")->execute($seoData);
        } else {
            $seoData['id'] = generateUUID(); 
            $seoData['page_type'] = 'college';
            $seoData['page_id'] = $id;
            $keys = array_keys($seoData);
            $pdo->prepare("INSERT INTO seo_meta (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($seoData);
        }
        
        $pdo->commit();
        header("Location: college_form.php?id=$id&tab=seo&msg=saved");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error saving SEO details: " . $e->getMessage();
    }
}

// Fetch Reference Data
$states = $pdo->query("SELECT * FROM states ORDER BY name ASC")->fetchAll();

$universities = $pdo->query("SELECT * FROM universities ORDER BY name ASC")->fetchAll();
$users = $pdo->query("SELECT id, full_name as name FROM users ORDER BY full_name ASC")->fetchAll();
$allColleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name ASC")->fetchAll();

$college = [];
if ($is_edit) {
    // Huge join to pull everything for the form
    $query = "
        SELECT 
            c.*, 
            cm.logo_url, cm.cover_image_url,
            cc.website_url, cc.email, cc.phone, cc.address, cc.latitude, cc.longitude, cc.pincode, cc.google_maps_embed_url, cc.nearest_railway_km, cc.nearest_airport_km,
            ct.about_text, ct.highlights_json, ct.accreditations_json, ct.rankings_json, ct.awards_json,
            ca.admission_process, ca.accepted_exams, ca.admission_start_date, ca.admission_end_date, ca.merit_based, ca.direct_admission, ca.management_quota_seats, ca.nri_quota_seats, ca.lateral_entry_available, ca.application_mode,
            ci.library, ci.auditorium, ci.cafeteria, ci.wifi, ci.medical_facility, ci.transport, ci.ev_charging, ci.solar_power, ci.sports_facilities, ci.labs,
            ch.hostel_available, ch.hostel_type, ch.hostel_capacity, ch.hostel_fee_annual, ch.mess_available, ch.mess_type, ch.ac_available, ch.laundry_available,
            sm.meta_title, sm.meta_description, sm.og_image_url, sm.canonical_url, sm.schema_markup, sm.noindex
        FROM colleges c
        LEFT JOIN college_media cm ON c.id = cm.college_id AND cm.image_type IS NULL
        LEFT JOIN college_contacts cc ON c.id = cc.college_id
        LEFT JOIN college_content ct ON c.id = ct.college_id
        LEFT JOIN college_admissions ca ON c.id = ca.college_id
        LEFT JOIN college_infrastructure ci ON c.id = ci.college_id
        LEFT JOIN college_hostels ch ON c.id = ch.college_id
        LEFT JOIN seo_meta sm ON c.id = sm.page_id AND sm.page_type = 'college'
        WHERE c.id = ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $college = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$college) {
        header('Location: colleges.php');
        exit;
    }
}

$cities = [];
$currentStateId = getValue($college, 'state_id');
if (!$currentStateId && !empty($_POST['state_id'])) {
    $currentStateId = $_POST['state_id'];
}
if ($currentStateId) {
    $stmt = $pdo->prepare("SELECT * FROM cities WHERE state_id = ? ORDER BY name ASC");
    $stmt->execute([$currentStateId]);
    $cities = $stmt->fetchAll();
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
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        html, body { overflow-x: auto !important; }
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 50; transition: transform 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; padding-bottom: 60px; min-width: 0; }
        .topbar { height: 64px; background: #fff; border-bottom: 1px solid rgba(15,23,42,0.08); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 40; }
        .header-left { display: flex; align-items: center; gap: 12px; }
        .header-right { display: flex; align-items: center; gap: 16px; }
        #topbarToggle { display:none; background:none; border:none; font-size:1.4rem; cursor:pointer; color:#0f172a; padding:4px; }
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:49; }
        .content-area { padding: 32px; max-width: 1000px; margin: 0 auto; width: 100%; }

        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 12px; }
        
        .form-section { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .form-section h3 { font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 24px; display: flex; align-items: center; gap: 8px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); font-size: 0.95rem; }
        .form-control { width: 100%; min-width: 0; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; color: var(--text-dark); background: #fff; transition: all 0.3s ease; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(11, 36, 71, 0.1); }
        
        .checkbox-group { display: flex; align-items: center; gap: 8px; margin-top: 32px; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .checkbox-group label { margin-bottom: 0; cursor: pointer; }
        
        .error-alert { padding: 16px; background: rgba(15,23,42,0.06); color: #0B2447; border-radius: 8px; margin-bottom: 24px; border: 1px solid rgba(15,23,42,0.06); }
        
        .form-actions { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
        .form-actions .btn { white-space: nowrap; min-width: 0; box-sizing: border-box; }
        
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; -webkit-overflow-scrolling: touch; scrollbar-width: thin; }
        .tabs-nav::-webkit-scrollbar { height: 6px; }
        .tabs-nav::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 3px; }
        .tabs-nav::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 3px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; flex-shrink: 0; font-size: 0.88rem; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        .tab-link.disabled { opacity: 0.5; cursor: not-allowed; }

        @media(max-width:1024px){
            .sidebar { transform:translateX(-100%) !important; }
            .sidebar.open { transform:translateX(0) !important; }
            .sidebar-overlay.show { display:block; }
            #topbarToggle { display:inline-flex !important; }
            .main-content { margin-left:0 !important; }
            .content-area { padding:16px !important; max-width:none !important; }
            .page-header { flex-wrap:wrap !important; gap:10px !important; }
            .page-header h2 { font-size:1.4rem !important; }
        }
        @media(max-width:768px){
            .topbar { height:56px !important; padding:0 12px !important; }
            .content-area { padding:12px !important; }
            .form-section { padding:16px !important; }
            .form-section h3 { font-size:1rem !important; margin-bottom:16px !important; }
            .form-grid { grid-template-columns:1fr !important; gap:12px !important; }
            .form-group { margin-bottom:14px !important; }
            .form-group label { font-size:0.85rem !important; margin-bottom:6px !important; }
            .form-control { padding:10px 12px !important; font-size:0.9rem !important; }
            .form-actions { flex-direction:column !important; gap:10px !important; }
            .form-actions .btn { width:100% !important; text-align:center !important; padding:14px 16px !important; font-size:0.9rem !important; justify-content:center !important; }
            .tabs-nav { gap:6px !important; margin-bottom:16px !important; }
            .tab-link { padding:7px 14px !important; font-size:0.82rem !important; }
            .page-header h2 { font-size:1.2rem !important; }
            .checkbox-group { flex-wrap:wrap !important; gap:6px !important; margin-top:16px !important; }
        }
        @media(max-width:480px){
            .content-area { padding:8px !important; }
            .form-section { padding:12px !important; border-radius:12px !important; }
            .form-section h3 { font-size:0.92rem !important; }
            .form-grid { gap:8px !important; }
            .form-group { margin-bottom:10px !important; }
            .form-group label { font-size:0.82rem !important; }
            .form-control { padding:9px 10px !important; font-size:0.85rem !important; }
            .tabs-nav { gap:4px !important; margin-bottom:12px !important; }
            .tab-link { padding:6px 10px !important; font-size:0.76rem !important; }
            .form-actions .btn { padding:12px 14px !important; font-size:0.85rem !important; }
            .page-header h2 { font-size:1.05rem !important; gap:8px !important; }
        }
    </style>
</head>
<body>

    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="topbar">
                <div class="header-left">
                    <button onclick="toggleSidebar()" id="topbarToggle"><i class="ph ph-list"></i></button>
                    <div style="font-weight:700; color:#0f172a;"><?php echo $is_edit ? 'Edit College' : 'Add New College'; ?></div>
                </div>
                <div class="header-right">
                    <span style="font-size:0.88rem; color:rgba(15,23,42,0.65);">Admin</span>
                    <a href="logout.php" style="color:#0f172a; font-size:1.2rem;"><i class="ph ph-sign-out"></i></a>
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
                    <a href="college_updates.php?college_id=<?php echo $id; ?>" class="tab-link">News & Updates</a>
                    <a href="college_qna.php?college_id=<?php echo $id; ?>" class="tab-link">Student Q&A</a>
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
                <div class="msg-alert" style="padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 24px; border: 1px solid rgba(11,36,71,0.04);">
                    <i class="ph ph-check-circle"></i> Details saved successfully!
                </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="error-alert"><i class="ph-fill ph-warning-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if($current_tab == 'identity'): ?>
                <form action="" method="POST" enctype="multipart/form-data">
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
                                    <option value="central" <?php echo getValue($college, 'ownership') == 'central' ? 'selected' : ''; ?>>Central</option>
                                    <option value="state" <?php echo getValue($college, 'ownership') == 'state' ? 'selected' : ''; ?>>State</option>
                                    <option value="private_trust" <?php echo getValue($college, 'ownership') == 'private_trust' ? 'selected' : ''; ?>>Private Trust</option>
                                    <option value="minority" <?php echo getValue($college, 'ownership') == 'minority' ? 'selected' : ''; ?>>Minority</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Type Label (Display Text)</label>
                                <input type="text" name="type_label" class="form-control" value="<?php echo getValue($college, 'type_label'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Founded Year</label>
                                <input type="number" name="founded_year" class="form-control" min="1800" max="2099" value="<?php echo getValue($college, 'founded_year'); ?>">
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="is_verified" name="is_verified" <?php echo !empty($college['is_verified']) ? 'checked' : ''; ?>>
                                <label for="is_verified">Mark as Verified <i class="ph-fill ph-seal-check" style="color:var(--primary);"></i></label>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-image"></i> Media & Accreditations</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Logo Image</label>
                                <?php if(getValue($college, 'logo_url')): ?>
                                    <div style="margin-bottom: 8px;">
                                        <?php 
                                            $logoSrc = getValue($college, 'logo_url');
                                            if (strpos($logoSrc, 'http') !== 0) $logoSrc = '../' . $logoSrc;
                                        ?>
                                        <img src="<?php echo $logoSrc; ?>" style="height: 50px; border-radius: 4px; border: 1px solid #ccc;">
                                    </div>
                                <?php endif; ?>
                                <input type="hidden" name="existing_logo_url" value="<?php echo getValue($college, 'logo_url'); ?>">
                                <input type="file" name="logo_file" class="form-control" accept="image/*">
                            </div>
                            <div class="form-group">
                                <label>Cover Image</label>
                                <?php if(getValue($college, 'cover_image_url')): ?>
                                    <div style="margin-bottom: 8px;">
                                        <?php 
                                            $coverSrc = getValue($college, 'cover_image_url');
                                            if (strpos($coverSrc, 'http') !== 0) $coverSrc = '../' . $coverSrc;
                                        ?>
                                        <img src="<?php echo $coverSrc; ?>" style="height: 50px; border-radius: 4px; border: 1px solid #ccc;">
                                    </div>
                                <?php endif; ?>
                                <input type="hidden" name="existing_cover_image_url" value="<?php echo getValue($college, 'cover_image_url'); ?>">
                                <input type="file" name="cover_file" class="form-control" accept="image/*">
                            </div>
                            
                            <div class="form-group">
                                <label>University Affiliation</label>
                                <select name="university_id" class="form-control">
                                    <option value="">Select University</option>
                                    <?php foreach($universities as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo getValue($college, 'university_id') == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>NAAC Grade</label>
                                <select name="naac_grade" class="form-control">
                                    <option value="">None</option>
                                    <?php foreach(['A++', 'A+', 'A', 'B++', 'B+', 'B', 'C'] as $g): ?>
                                        <option value="<?php echo $g; ?>" <?php echo getValue($college, 'naac_grade') == $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>NIRF Rank</label>
                                <input type="number" name="ranking_nirf" class="form-control" value="<?php echo getValue($college, 'ranking_nirf'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <div class="checkbox-group" style="margin-top:0;"><input type="checkbox" name="autonomous" <?php echo !empty($college['autonomous']) ? 'checked' : ''; ?>> <label>Autonomous</label></div>
                                <div class="checkbox-group" style="margin-top:10px;"><input type="checkbox" name="ugc_approved" <?php echo !empty($college['ugc_approved']) ? 'checked' : ''; ?>> <label>UGC Approved</label></div>
                                <div class="checkbox-group" style="margin-top:10px;"><input type="checkbox" name="aicte_approved" <?php echo !empty($college['aicte_approved']) ? 'checked' : ''; ?>> <label>AICTE Approved</label></div>
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
                            <div class="form-group">
                                <label>Campus Type</label>
                                <select name="campus_type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="urban" <?php echo getValue($college, 'campus_type') == 'urban' ? 'selected' : ''; ?>>Urban</option>
                                    <option value="semi-urban" <?php echo getValue($college, 'campus_type') == 'semi-urban' ? 'selected' : ''; ?>>Semi-Urban</option>
                                    <option value="rural" <?php echo getValue($college, 'campus_type') == 'rural' ? 'selected' : ''; ?>>Rural</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-map-pin"></i> Contact & Location</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>State</label>
                                <select name="state_id" id="state_id" class="form-control">
                                    <option value="">Select State</option>
                                    <?php foreach($states as $s): ?>
                                        <option value="<?php echo $s['id']; ?>" <?php echo getValue($college, 'state_id') == $s['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>City</label>
                                <select name="city_id" id="city_id" class="form-control">
                                    <option value="">Select City</option>
                                    <?php foreach($cities as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo getValue($college, 'city_id') == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group full">
                                <label>Address</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo getValue($college, 'address'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo getValue($college, 'email'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo getValue($college, 'phone'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Website URL</label>
                                <input type="url" name="website_url" class="form-control" value="<?php echo getValue($college, 'website_url'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Pincode</label>
                                <input type="text" name="pincode" class="form-control" value="<?php echo getValue($college, 'pincode'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="number" step="0.000001" name="latitude" class="form-control" value="<?php echo getValue($college, 'latitude'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="number" step="0.000001" name="longitude" class="form-control" value="<?php echo getValue($college, 'longitude'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Nearest Railway (km)</label>
                                <input type="number" step="0.1" name="nearest_railway_km" class="form-control" value="<?php echo getValue($college, 'nearest_railway_km'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Nearest Airport (km)</label>
                                <input type="number" step="0.1" name="nearest_airport_km" class="form-control" value="<?php echo getValue($college, 'nearest_airport_km'); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Google Maps URL</label>
                                <input type="url" name="google_maps_embed_url" class="form-control" value="<?php echo getValue($college, 'google_maps_embed_url'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="ph ph-star"></i> Feature Settings</h3>
                        <div class="form-grid">
                            <div class="form-group checkbox-group" style="margin-top:0;">
                                <input type="checkbox" id="is_featured" name="is_featured" <?php echo !empty($college['is_featured']) ? 'checked' : ''; ?>>
                                <label for="is_featured">Feature this college on homepage</label>
                            </div>
                            <div class="form-group">
                                <label>Featured Order</label>
                                <input type="number" name="featured_order" class="form-control" value="<?php echo getValue($college, 'featured_order', 0); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-shield-check"></i> Verification</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Verification Status</label>
                                <select name="verification_status" class="form-control">
                                    <option value="unverified" <?php echo getValue($college, 'verification_status') == 'unverified' ? 'selected' : ''; ?>>Unverified</option>
                                    <option value="pending" <?php echo getValue($college, 'verification_status') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="verified" <?php echo getValue($college, 'verification_status') == 'verified' ? 'selected' : ''; ?>>Verified</option>
                                    <option value="disputed" <?php echo getValue($college, 'verification_status') == 'disputed' ? 'selected' : ''; ?>>Disputed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Verified By</label>
                                <select name="verified_by" class="form-control">
                                    <option value="">Select User</option>
                                    <?php foreach($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo getValue($college, 'verified_by') == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Data Quality Score (0-100)</label>
                                <input type="number" name="data_quality_score" class="form-control" min="0" max="100" value="<?php echo getValue($college, 'data_quality_score', 0); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Rejection Reason</label>
                                <textarea name="rejection_reason" class="form-control" rows="2"><?php echo getValue($college, 'rejection_reason'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="colleges.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Identity Details</button>
                    </div>
                </form>

                <?php elseif($current_tab == 'about'): ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3><i class="ph ph-info"></i> Basic Info & About</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>About Text</label>
                                <textarea name="about_text" class="form-control" rows="5"><?php echo getValue($college, 'about_text'); ?></textarea>
                            </div>
                                                        <div class="form-group">
                                <label>Highlights (One per line)</label>
                                <textarea name="highlights_json" class="form-control" rows="3" placeholder="Excellent Campus&#10;Top Recruiters"><?php echo jsonToLines($college['highlights_json'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Accreditations (One per line)</label>
                                <textarea name="accreditations" class="form-control" rows="3" placeholder="UGC&#10;AICTE"><?php echo jsonToLines($college['accreditations_json'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Rankings (Format: Name: Rank)</label>
                                <textarea name="rankings_json" class="form-control" rows="3" placeholder="NIRF: 12&#10;India Today: 5"><?php echo jsonToLines($college['rankings_json'] ?? '', true); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Awards (One per line)</label>
                                <textarea name="awards_json" class="form-control" rows="3" placeholder="Best Engineering College 2023"><?php echo jsonToLines($college['awards_json'] ?? ''); ?></textarea>
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
                                <label>Accepted Exams (One per line)</label>
                                <textarea name="accepted_exams" class="form-control" rows="2" placeholder="JEE Main&#10;SAT"><?php echo jsonToLines($college['accepted_exams'] ?? ''); ?></textarea>
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
                            <div class="form-group">
                                <label>Application Mode</label>
                                <select name="application_mode" class="form-control">
                                    <option value="">Select Mode</option>
                                    <option value="online" <?php echo getValue($college, 'application_mode') == 'online' ? 'selected' : ''; ?>>Online</option>
                                    <option value="offline" <?php echo getValue($college, 'application_mode') == 'offline' ? 'selected' : ''; ?>>Offline</option>
                                    <option value="both" <?php echo getValue($college, 'application_mode') == 'both' ? 'selected' : ''; ?>>Both</option>
                                </select>
                            </div>
                            <div class="form-group checkbox-group" style="grid-column: 1 / -1; display:flex; gap:20px;">
                                <div><input type="checkbox" name="merit_based" <?php echo !empty($college['merit_based']) ? 'checked' : ''; ?>> <label>Merit Based</label></div>
                                <div><input type="checkbox" name="direct_admission" <?php echo !empty($college['direct_admission']) ? 'checked' : ''; ?>> <label>Direct Admission</label></div>
                                <div><input type="checkbox" name="lateral_entry_available" <?php echo !empty($college['lateral_entry_available']) ? 'checked' : ''; ?>> <label>Lateral Entry</label></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-buildings"></i> Infrastructure & Campus</h3>
                        <div class="form-grid">
                            <div class="form-group checkbox-group" style="grid-column: 1 / -1; display:flex; gap:20px; flex-wrap:wrap;">
                                <div><input type="checkbox" name="library" <?php echo !empty($college['library'])?'checked':''; ?>> <label>Library</label></div>
                                <div><input type="checkbox" name="auditorium" <?php echo !empty($college['auditorium'])?'checked':''; ?>> <label>Auditorium</label></div>
                                <div><input type="checkbox" name="cafeteria" <?php echo !empty($college['cafeteria'])?'checked':''; ?>> <label>Cafeteria</label></div>
                                <div><input type="checkbox" name="wifi" <?php echo !empty($college['wifi'])?'checked':''; ?>> <label>Wi-Fi</label></div>
                                <div><input type="checkbox" name="medical_facility" <?php echo !empty($college['medical_facility'])?'checked':''; ?>> <label>Medical</label></div>
                                <div><input type="checkbox" name="transport" <?php echo !empty($college['transport'])?'checked':''; ?>> <label>Transport</label></div>
                                <div><input type="checkbox" name="ev_charging" <?php echo !empty($college['ev_charging'])?'checked':''; ?>> <label>EV Charging</label></div>
                                <div><input type="checkbox" name="solar_power" <?php echo !empty($college['solar_power'])?'checked':''; ?>> <label>Solar Power</label></div>
                            </div>
                                                        <div class="form-group">
                                <label>Sports Facilities (One per line)</label>
                                <textarea name="sports_facilities" class="form-control" rows="3" placeholder="Cricket&#10;Football"><?php echo jsonToLines($college['sports_facilities'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Labs (One per line)</label>
                                <textarea name="labs" class="form-control" rows="3" placeholder="Computer Lab&#10;Physics Lab"><?php echo jsonToLines($college['labs'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="ph ph-bed"></i> Hostel</h3>
                        <div class="form-grid">
                            <div class="form-group checkbox-group">
                                <input type="checkbox" name="hostel_available" <?php echo !empty($college['hostel_available'])?'checked':''; ?>> 
                                <label>Hostel Available</label>
                            </div>
                            <div class="form-group">
                                <label>Hostel Type</label>
                                <select name="hostel_type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="boys" <?php echo getValue($college, 'hostel_type') == 'boys' ? 'selected' : ''; ?>>Boys</option>
                                    <option value="girls" <?php echo getValue($college, 'hostel_type') == 'girls' ? 'selected' : ''; ?>>Girls</option>
                                    <option value="both" <?php echo getValue($college, 'hostel_type') == 'both' ? 'selected' : ''; ?>>Both</option>
                                    <option value="co-ed" <?php echo getValue($college, 'hostel_type') == 'co-ed' ? 'selected' : ''; ?>>Co-Ed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Hostel Capacity</label>
                                <input type="number" name="hostel_capacity" class="form-control" value="<?php echo getValue($college, 'hostel_capacity'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Hostel Fee (Annual)</label>
                                <input type="number" name="hostel_fee_annual" class="form-control" value="<?php echo getValue($college, 'hostel_fee_annual'); ?>">
                            </div>
                            <div class="form-group checkbox-group" style="grid-column: 1 / -1; display:flex; gap:20px;">
                                <div><input type="checkbox" name="mess_available" <?php echo !empty($college['mess_available'])?'checked':''; ?>> <label>Mess Available</label></div>
                                <div><input type="checkbox" name="ac_available" <?php echo !empty($college['ac_available'])?'checked':''; ?>> <label>AC Available</label></div>
                                <div><input type="checkbox" name="laundry_available" <?php echo !empty($college['laundry_available'])?'checked':''; ?>> <label>Laundry Available</label></div>
                            </div>
                            <div class="form-group">
                                <label>Mess Type</label>
                                <select name="mess_type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="veg" <?php echo getValue($college, 'mess_type') == 'veg' ? 'selected' : ''; ?>>Veg</option>
                                    <option value="non-veg" <?php echo getValue($college, 'mess_type') == 'non-veg' ? 'selected' : ''; ?>>Non-Veg</option>
                                    <option value="both" <?php echo getValue($college, 'mess_type') == 'both' ? 'selected' : ''; ?>>Both</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="colleges.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save About Details</button>
                    </div>
                </form>

                <?php elseif($current_tab == 'seo'): ?>
                <!-- SEO & PUBLISH TAB -->
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3><i class="ph ph-globe"></i> SEO & Meta Data</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Publish Status</label>
                                <select name="publish_status" class="form-control" style="max-width:300px;">
                                    <option value="draft" <?php echo getValue($college, 'publish_status') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?php echo getValue($college, 'publish_status') == 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="archived" <?php echo getValue($college, 'publish_status') == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                            <div class="form-group full">
                                <label>Meta Title</label>
                                <input type="text" name="meta_title" class="form-control" value="<?php echo getValue($college, 'meta_title'); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Meta Description</label>
                                <textarea name="meta_description" class="form-control" rows="3"><?php echo getValue($college, 'meta_description'); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Canonical URL</label>
                                <input type="url" name="canonical_url" class="form-control" value="<?php echo getValue($college, 'canonical_url'); ?>">
                            </div>
                            <div class="form-group full">
                                <label>OG Image</label>
                                <?php if(getValue($college, 'og_image_url')): ?>
                                    <div style="margin-bottom: 8px;">
                                        <?php 
                                            $ogSrc = getValue($college, 'og_image_url');
                                            if (strpos($ogSrc, 'http') !== 0) $ogSrc = '../' . $ogSrc;
                                        ?>
                                        <img src="<?php echo $ogSrc; ?>" style="height: 50px; border-radius: 4px; border: 1px solid #ccc;">
                                    </div>
                                <?php endif; ?>
                                <input type="hidden" name="existing_og_image_url" value="<?php echo getValue($college, 'og_image_url'); ?>">
                                <input type="file" name="og_image_file" class="form-control" accept="image/*">
                            </div>
                            <div class="form-group full">
                                <label>Schema Markup (Leave blank to auto-generate)</label>
                                <textarea name="schema_markup" class="form-control" rows="6"><?php echo getValue($college, 'schema_markup'); ?></textarea>
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="noindex" name="noindex" <?php echo !empty($college['noindex']) ? 'checked' : ''; ?>>
                                <label for="noindex">No Index (Hide from search engines)</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="colleges.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save SEO Settings</button>
                    </div>
                </form>
                <?php endif; ?>

            </div>
        </main>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const stateSelect = document.getElementById('state_id');
        const citySelect = document.getElementById('city_id');
        
        if (stateSelect && citySelect) {
            stateSelect.addEventListener('change', function() {
                const stateId = this.value;
                citySelect.innerHTML = '<option value="">Select City</option>';
                if (stateId) {
                    fetch('api/get_cities.php?state_id=' + stateId)
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.length > 0) {
                                data.forEach(city => {
                                    const option = document.createElement('option');
                                    option.value = city.id;
                                    option.textContent = city.name;
                                    citySelect.appendChild(option);
                                });
                            }
                        })
                        .catch(error => console.error('Error fetching cities:', error));
                }
            });
        }
    });
    </script>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    <script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }
    </script>
</body>
</html>
