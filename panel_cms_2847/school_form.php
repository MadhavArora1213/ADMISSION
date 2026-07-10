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

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

function jsonToLines($jsonStr) {
    if (empty($jsonStr)) return '';
    $data = json_decode($jsonStr, true);
    if (!is_array($data)) return htmlspecialchars($jsonStr);
    return htmlspecialchars(implode("\n", $data));
}

function linesToJsonList($text) {
    if (empty(trim($text))) return null;
    $lines = array_filter(array_map('trim', explode("\n", $text)), 'strlen');
    return empty($lines) ? null : json_encode(array_values($lines));
}

// Handle POST - Identity tab
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'identity') {
    $slug = !empty($_POST['slug']) ? $_POST['slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])));
    
    $slugCheckQ = "SELECT id FROM schools WHERE slug = :slug";
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
            
            $schoolData = [
                'name' => $_POST['name'],
                'slug' => $slug,
                'school_type' => $_POST['school_type'] ?: null,
                'ownership' => $_POST['ownership'] ?: null,
                'board_affiliation' => $_POST['board_affiliation'] ?: null,
                'board_state_name' => $_POST['board_affiliation'] === 'State' ? ($_POST['board_state_name'] ?: null) : null,
                'status' => $_POST['status'],
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'is_verified' => isset($_POST['is_verified']) ? 1 : 0,
                'featured_order' => $_POST['featured_order'] ?: 0,
                'city_id' => !empty($_POST['city_id']) ? $_POST['city_id'] : null,
                'state_id' => !empty($_POST['state_id']) ? $_POST['state_id'] : null,
                'established_year' => $_POST['established_year'] ?: null,
                'total_students' => $_POST['total_students'] ?: null,
                'total_faculty' => $_POST['total_faculty'] ?: null,
                'campus_area_acres' => $_POST['campus_area_acres'] ?: null,
                'publish_status' => $_POST['publish_status'] ?: 'draft',
            ];

            if ($is_edit) {
                $fields = [];
                foreach ($schoolData as $k => $v) $fields[] = "$k = :$k";
                $schoolData['id'] = $id;
                $stmt = $pdo->prepare("UPDATE schools SET " . implode(", ", $fields) . " WHERE id = :id");
                $stmt->execute($schoolData);
            } else {
                $id = generateUUID();
                $schoolData['id'] = $id;
                $keys = array_keys($schoolData);
                $stmt = $pdo->prepare("INSERT INTO schools (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")");
                $stmt->execute($schoolData);
            }

            // Handle file uploads
            $upload_dir = '../uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $logo_url = $_POST['existing_logo_url'] ?? '';
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == 0) {
                $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
                $filename = 'school_logo_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_dir . $filename)) {
                    $logo_url = 'uploads/' . $filename;
                    require_once __DIR__ . '/upload_sync.php';
                    sync_to_github('uploads/' . $filename);
                }
            }

            $cover_url = $_POST['existing_cover_image_url'] ?? '';
            if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] == 0) {
                $ext = pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION);
                $filename = 'school_cover_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['cover_file']['tmp_name'], $upload_dir . $filename)) {
                    $cover_url = 'uploads/' . $filename;
                    require_once __DIR__ . '/upload_sync.php';
                    sync_to_github('uploads/' . $filename);
                }
            }

            // School Media
            $mediaCheck = $pdo->prepare("SELECT id FROM school_media WHERE school_id = ? AND image_type IS NULL");
            $mediaCheck->execute([$id]);
            if ($mediaCheck->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE school_media SET logo_url = ?, cover_image_url = ? WHERE school_id = ? AND image_type IS NULL");
                $stmt->execute([$logo_url, $cover_url, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO school_media (id, school_id, logo_url, cover_image_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([generateUUID(), $id, $logo_url, $cover_url]);
            }

            // School Contacts
            $contactData = [
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'address' => $_POST['address'],
                'latitude' => $_POST['latitude'] ?: null,
                'longitude' => $_POST['longitude'] ?: null,
                'website_url' => $_POST['website_url'],
                'pincode' => $_POST['pincode'],
                'google_maps_embed_url' => $_POST['google_maps_embed_url'],
            ];

            $contactCheck = $pdo->prepare("SELECT id FROM school_contacts WHERE school_id = ?");
            $contactCheck->execute([$id]);
            if ($contactCheck->rowCount() > 0) {
                $fields = []; foreach ($contactData as $k => $v) $fields[] = "$k = :$k";
                $contactData['school_id'] = $id;
                $stmt = $pdo->prepare("UPDATE school_contacts SET " . implode(", ", $fields) . " WHERE school_id = :school_id");
                $stmt->execute($contactData);
            } else {
                $contactData['id'] = generateUUID();
                $contactData['school_id'] = $id;
                $keys = array_keys($contactData);
                $stmt = $pdo->prepare("INSERT INTO school_contacts (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")");
                $stmt->execute($contactData);
            }

            $pdo->commit();
            header("Location: school_form.php?id=$id&tab=identity&msg=saved");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error saving identity details: " . $e->getMessage();
        }
    }
}

// Handle POST - About tab
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'about') {
    try {
        $pdo->beginTransaction();
        
        $contentData = [
            'about_text' => $_POST['about_text'],
            'highlights_json' => linesToJsonList($_POST['highlights_json']),
            'admission_process' => $_POST['admission_process'],
            'accepted_exams' => linesToJsonList($_POST['accepted_exams']),
            'admission_start_date' => $_POST['admission_start_date'] ?: null,
            'admission_end_date' => $_POST['admission_end_date'] ?: null,
        ];
        $chk = $pdo->prepare("SELECT id FROM school_content WHERE school_id = ?"); $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($contentData as $k=>$v) $fields[] = "$k = :$k";
            $contentData['school_id'] = $id;
            $pdo->prepare("UPDATE school_content SET " . implode(", ", $fields) . " WHERE school_id = :school_id")->execute($contentData);
        } else {
            $contentData['id'] = generateUUID(); $contentData['school_id'] = $id;
            $keys = array_keys($contentData);
            $pdo->prepare("INSERT INTO school_content (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($contentData);
        }

        // Infrastructure
        $infraData = [
            'library' => isset($_POST['library']) ? 1 : 0,
            'auditorium' => isset($_POST['auditorium']) ? 1 : 0,
            'cafeteria' => isset($_POST['cafeteria']) ? 1 : 0,
            'wifi' => isset($_POST['wifi']) ? 1 : 0,
            'medical_facility' => isset($_POST['medical_facility']) ? 1 : 0,
            'transport' => isset($_POST['transport']) ? 1 : 0,
            'playground' => isset($_POST['playground']) ? 1 : 0,
            'swimming_pool' => isset($_POST['swimming_pool']) ? 1 : 0,
            'labs' => isset($_POST['labs']) ? 1 : 0,
            'smart_classrooms' => isset($_POST['smart_classrooms']) ? 1 : 0,
        ];
        $chk = $pdo->prepare("SELECT id FROM school_infrastructure WHERE school_id = ?"); $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($infraData as $k=>$v) $fields[] = "$k = :$k";
            $infraData['school_id'] = $id;
            $pdo->prepare("UPDATE school_infrastructure SET " . implode(", ", $fields) . " WHERE school_id = :school_id")->execute($infraData);
        } else {
            $infraData['id'] = generateUUID(); $infraData['school_id'] = $id;
            $keys = array_keys($infraData);
            $pdo->prepare("INSERT INTO school_infrastructure (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($infraData);
        }

        $pdo->commit();
        header("Location: school_form.php?id=$id&tab=about&msg=saved");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error saving about details: " . $e->getMessage();
    }
}

// Handle POST - Courses tab
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'courses') {
    try {
        $action = $_POST['course_action'] ?? '';
        if ($action == 'add') {
            $courseId = generateUUID();
            $pdo->prepare("INSERT INTO school_courses (id, school_id, class_name, class_level, annual_fee, semester_fee, total_fee, seats_available, session_year, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
                $courseId, $id, $_POST['class_name'], $_POST['class_level'] ?: null,
                $_POST['annual_fee'] ?: null, $_POST['semester_fee'] ?: null, $_POST['total_fee'] ?: null,
                $_POST['seats_available'] ?: null, $_POST['session_year'] ?: null, $_POST['sort_order'] ?? 0
            ]);
        } elseif ($action == 'delete' && !empty($_POST['course_id'])) {
            $pdo->prepare("DELETE FROM school_courses WHERE id = ? AND school_id = ?")->execute([$_POST['course_id'], $id]);
        }
        header("Location: school_form.php?id=$id&tab=courses&msg=saved");
        exit;
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle POST - News tab
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'news') {
    try {
        $action = $_POST['news_action'] ?? '';
        if ($action == 'add') {
            // Handle image upload
            $image_url = null;
            $upload_dir = '../uploads/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            if (isset($_FILES['news_image']) && $_FILES['news_image']['error'] == 0) {
                $ext = pathinfo($_FILES['news_image']['name'], PATHINFO_EXTENSION);
                $fn = 'school_news_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['news_image']['tmp_name'], $upload_dir . $fn)) {
                    $image_url = 'uploads/' . $fn;
                    require_once __DIR__ . '/upload_sync.php';
                    sync_to_github('uploads/' . $fn);
                }
            }
            $newsId = generateUUID();
            $pdo->prepare("INSERT INTO school_news (id, school_id, title, excerpt, content, image_url, event_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")->execute([
                $newsId, $id, $_POST['title'], $_POST['excerpt'] ?: null, $_POST['content'] ?: null,
                $image_url, $_POST['event_date'] ?: null, $_POST['status'] ?? 'draft'
            ]);
        } elseif ($action == 'delete' && !empty($_POST['news_id'])) {
            $pdo->prepare("DELETE FROM school_news WHERE id = ? AND school_id = ?")->execute([$_POST['news_id'], $id]);
        } elseif ($action == 'toggle' && !empty($_POST['news_id'])) {
            $pdo->prepare("UPDATE school_news SET status = IF(status='published','draft','published') WHERE id = ? AND school_id = ?")->execute([$_POST['news_id'], $id]);
        }
        header("Location: school_form.php?id=$id&tab=news&msg=saved");
        exit;
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle POST - SEO tab
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'seo') {
    try {
        $pdo->beginTransaction();
        
        $pdo->prepare("UPDATE schools SET publish_status = ? WHERE id = ?")->execute([$_POST['publish_status'], $id]);
        
        $seoData = [
            'meta_title' => $_POST['meta_title'],
            'meta_description' => $_POST['meta_description'],
            'canonical_url' => $_POST['canonical_url'],
            'noindex' => isset($_POST['noindex']) ? 1 : 0
        ];
        
        $chk = $pdo->prepare("SELECT id FROM seo_meta WHERE page_type = 'school' AND page_id = ?"); 
        $chk->execute([$id]);
        if($chk->rowCount() > 0) {
            $fields = []; foreach($seoData as $k=>$v) $fields[] = "$k = :$k";
            $seoData['page_id'] = $id;
            $pdo->prepare("UPDATE seo_meta SET " . implode(", ", $fields) . " WHERE page_type = 'school' AND page_id = :page_id")->execute($seoData);
        } else {
            $seoData['id'] = generateUUID(); 
            $seoData['page_type'] = 'school';
            $seoData['page_id'] = $id;
            $keys = array_keys($seoData);
            $pdo->prepare("INSERT INTO seo_meta (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")")->execute($seoData);
        }
        
        $pdo->commit();
        header("Location: school_form.php?id=$id&tab=seo&msg=saved");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error saving SEO details: " . $e->getMessage();
    }
}

// Fetch Reference Data
$states = $pdo->query("SELECT * FROM states ORDER BY name ASC")->fetchAll();

$school = [];
if ($is_edit) {
    $query = "
        SELECT s.*, sm.logo_url, sm.cover_image_url,
               sc.website_url, sc.email, sc.phone, sc.address, sc.latitude, sc.longitude, sc.pincode, sc.google_maps_embed_url,
               sct.about_text, sct.highlights_json, sct.admission_process, sct.accepted_exams, sct.admission_start_date, sct.admission_end_date,
               si.library, si.auditorium, si.cafeteria, si.wifi, si.medical_facility, si.transport, si.playground, si.swimming_pool, si.labs, si.smart_classrooms,
               smeta.meta_title, smeta.meta_description, smeta.canonical_url, smeta.noindex
        FROM schools s
        LEFT JOIN school_media sm ON s.id = sm.school_id AND sm.image_type IS NULL
        LEFT JOIN school_contacts sc ON s.id = sc.school_id
        LEFT JOIN school_content sct ON s.id = sct.school_id
        LEFT JOIN school_infrastructure si ON s.id = si.school_id
        LEFT JOIN seo_meta smeta ON s.id = smeta.page_id AND smeta.page_type = 'school'
        WHERE s.id = ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $school = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$school) {
        header('Location: schools.php');
        exit;
    }
}

$cities = [];
$currentStateId = getValue($school, 'state_id');
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
    <title><?= $is_edit ? 'Edit School' : 'Add New School'; ?> | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
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
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; }
        .tabs-nav::-webkit-scrollbar { height: 6px; }
        .tabs-nav::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 3px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; flex-shrink: 0; font-size: 0.88rem; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        .tab-link.disabled { opacity: 0.5; cursor: not-allowed; }
        .msg-alert { padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 24px; border: 1px solid rgba(11,36,71,0.04); }
        @media(max-width:1024px){
            .sidebar { transform:translateX(-100%) !important; }
            .sidebar.open { transform:translateX(0) !important; }
            .sidebar-overlay.show { display:block; }
            #topbarToggle { display:inline-flex !important; }
            .main-content { margin-left:0 !important; }
            .content-area { padding:16px !important; max-width:none !important; }
        }
        @media(max-width:768px){
            .topbar { height:56px !important; padding:0 12px !important; }
            .content-area { padding:12px !important; }
            .form-section { padding:16px !important; }
            .form-grid { grid-template-columns:1fr !important; gap:12px !important; }
            .form-actions { flex-direction:column !important; gap:10px !important; }
            .form-actions .btn { width:100% !important; text-align:center !important; padding:14px 16px !important; }
            .tabs-nav { gap:6px !important; }
            .tab-link { padding:7px 14px !important; font-size:0.82rem !important; }
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
                    <div style="font-weight:700; color:#0f172a;"><?= $is_edit ? 'Edit School' : 'Add New School'; ?></div>
                </div>
                <div class="header-right">
                    <span style="font-size:0.88rem; color:rgba(15,23,42,0.65);">Admin</span>
                    <a href="logout.php" style="color:#0f172a; font-size:1.2rem;"><i class="ph ph-sign-out"></i></a>
                </div>
            </header>

            <div class="content-area">
                <div class="page-header">
                    <h2>
                        <a href="schools.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                        <?= $is_edit ? 'Edit School: ' . htmlspecialchars($school['name']) : 'Add New School'; ?>
                    </h2>
                </div>

                <?php if($is_edit): ?>
                <div class="tabs-nav">
                    <a href="?id=<?= $id; ?>&tab=identity" class="tab-link <?= $current_tab=='identity'?'active':''; ?>">Identity & Contact</a>
                    <a href="?id=<?= $id; ?>&tab=about" class="tab-link <?= $current_tab=='about'?'active':''; ?>">About & Amenities</a>
                    <a href="?id=<?= $id; ?>&tab=courses" class="tab-link <?= $current_tab=='courses'?'active':''; ?>">Courses & Fees</a>
                    <a href="?id=<?= $id; ?>&tab=news" class="tab-link <?= $current_tab=='news'?'active':''; ?>">News & Updates</a>
                    <a href="?id=<?= $id; ?>&tab=seo" class="tab-link <?= $current_tab=='seo'?'active':''; ?>">SEO & Publish</a>
                </div>
                <?php else: ?>
                <div class="tabs-nav">
                    <span class="tab-link active">Identity & Contact</span>
                    <span class="tab-link disabled" title="Save school first to unlock">About & Amenities</span>
                    <span class="tab-link disabled" title="Save school first to unlock">SEO & Publish</span>
                </div>
                <?php endif; ?>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> Details saved successfully!</div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="error-alert"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if($current_tab == 'identity'): ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3><i class="ph ph-identification-card"></i> School Identity</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>School Name *</label>
                                <input type="text" name="name" class="form-control" required value="<?= getValue($school, 'name'); ?>">
                            </div>
                            <div class="form-group">
                                <label>URL Slug (Leave blank to auto-generate)</label>
                                <input type="text" name="slug" class="form-control" value="<?= getValue($school, 'slug'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Status *</label>
                                <select name="status" class="form-control" required>
                                    <option value="pending" <?= getValue($school, 'status') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="active" <?= getValue($school, 'status') == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="archived" <?= getValue($school, 'status') == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>School Type</label>
                                <select name="school_type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="govt" <?= getValue($school, 'school_type') == 'govt' ? 'selected' : ''; ?>>Government</option>
                                    <option value="private" <?= getValue($school, 'school_type') == 'private' ? 'selected' : ''; ?>>Private</option>
                                    <option value="aided" <?= getValue($school, 'school_type') == 'aided' ? 'selected' : ''; ?>>Aided</option>
                                    <option value="unaided" <?= getValue($school, 'school_type') == 'unaided' ? 'selected' : ''; ?>>Unaided</option>
                                    <option value="international" <?= getValue($school, 'school_type') == 'international' ? 'selected' : ''; ?>>International</option>
                                    <option value="boarding" <?= getValue($school, 'school_type') == 'boarding' ? 'selected' : ''; ?>>Boarding</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ownership</label>
                                <select name="ownership" class="form-control">
                                    <option value="">Select Ownership</option>
                                    <option value="central" <?= getValue($school, 'ownership') == 'central' ? 'selected' : ''; ?>>Central</option>
                                    <option value="state" <?= getValue($school, 'ownership') == 'state' ? 'selected' : ''; ?>>State</option>
                                    <option value="private_trust" <?= getValue($school, 'ownership') == 'private_trust' ? 'selected' : ''; ?>>Private Trust</option>
                                    <option value="minority" <?= getValue($school, 'ownership') == 'minority' ? 'selected' : ''; ?>>Minority</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Board Affiliation</label>
                                <select name="board_affiliation" id="board_affiliation" class="form-control">
                                    <option value="">Select Board</option>
                                    <option value="CBSE" <?= getValue($school, 'board_affiliation') == 'CBSE' ? 'selected' : ''; ?>>CBSE</option>
                                    <option value="ICSE" <?= getValue($school, 'board_affiliation') == 'ICSE' ? 'selected' : ''; ?>>ICSE</option>
                                    <option value="State" <?= getValue($school, 'board_affiliation') == 'State' ? 'selected' : ''; ?>>State Board</option>
                                    <option value="IB" <?= getValue($school, 'board_affiliation') == 'IB' ? 'selected' : ''; ?>>IB (International Baccalaureate)</option>
                                    <option value="IGCSE" <?= getValue($school, 'board_affiliation') == 'IGCSE' ? 'selected' : ''; ?>>IGCSE</option>
                                    <option value="NIOS" <?= getValue($school, 'board_affiliation') == 'NIOS' ? 'selected' : ''; ?>>NIOS</option>
                                </select>
                            </div>
                            <div class="form-group" id="board_state_group" style="display:<?= getValue($school, 'board_affiliation') == 'State' ? 'block' : 'none' ?>;">
                                <label>Which State Board?</label>
                                <select name="board_state_name" class="form-control">
                                    <option value="">Select State Board</option>
                                    <?php
                                    $stateBoards = [
                                        'Andhra Pradesh Board','Assam Board','Bihar Board','Chhattisgarh Board',
                                        'Goa Board','Gujarat Board','Haryana Board','Himachal Pradesh Board',
                                        'Jharkhand Board','Karnataka Board','Kerala Board','Madhya Pradesh Board',
                                        'Maharashtra Board','Manipur Board','Meghalaya Board','Mizoram Board',
                                        'Nagaland Board','Odisha Board','Punjab Board','Rajasthan Board',
                                        'Sikkim Board','Tamil Nadu Board','Telangana Board','Tripura Board',
                                        'Uttar Pradesh Board','Uttarakhand Board','West Bengal Board',
                                        'Delhi Board','J&K Board','Chandigarh Board'
                                    ];
                                    foreach ($stateBoards as $sb): ?>
                                        <option value="<?= htmlspecialchars($sb) ?>" <?= getValue($school, 'board_state_name') == $sb ? 'selected' : ''; ?>><?= htmlspecialchars($sb) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Established Year</label>
                                <input type="number" name="established_year" class="form-control" min="1800" max="2099" value="<?= getValue($school, 'established_year'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Publish Status</label>
                                <select name="publish_status" class="form-control">
                                    <option value="draft" <?= getValue($school, 'publish_status') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?= getValue($school, 'publish_status') == 'published' ? 'selected' : ''; ?>>Published</option>
                                </select>
                            </div>
                            <div class="form-group checkbox-group" style="margin-top:0;">
                                <input type="checkbox" id="is_verified" name="is_verified" <?= !empty($school['is_verified']) ? 'checked' : ''; ?>>
                                <label for="is_verified">Mark as Verified</label>
                            </div>
                            <div class="form-group checkbox-group" style="margin-top:0;">
                                <input type="checkbox" id="is_featured" name="is_featured" <?= !empty($school['is_featured']) ? 'checked' : ''; ?>>
                                <label for="is_featured">Feature on homepage</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-image"></i> Media</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Logo Image</label>
                                <?php if(getValue($school, 'logo_url')): ?>
                                    <div style="margin-bottom: 8px;">
                                        <?php 
                                            $logoSrc = getValue($school, 'logo_url');
                                            if (strpos($logoSrc, 'http') !== 0) $logoSrc = '../' . $logoSrc;
                                        ?>
                                        <img src="<?= $logoSrc; ?>" style="height: 50px; border-radius: 4px; border: 1px solid #ccc;">
                                    </div>
                                <?php endif; ?>
                                <input type="hidden" name="existing_logo_url" value="<?= getValue($school, 'logo_url'); ?>">
                                <input type="file" name="logo_file" class="form-control" accept="image/*">
                            </div>
                            <div class="form-group">
                                <label>Cover Image</label>
                                <?php if(getValue($school, 'cover_image_url')): ?>
                                    <div style="margin-bottom: 8px;">
                                        <?php 
                                            $coverSrc = getValue($school, 'cover_image_url');
                                            if (strpos($coverSrc, 'http') !== 0) $coverSrc = '../' . $coverSrc;
                                        ?>
                                        <img src="<?= $coverSrc; ?>" style="height: 50px; border-radius: 4px; border: 1px solid #ccc;">
                                    </div>
                                <?php endif; ?>
                                <input type="hidden" name="existing_cover_image_url" value="<?= getValue($school, 'cover_image_url'); ?>">
                                <input type="file" name="cover_file" class="form-control" accept="image/*">
                            </div>
                            <div class="form-group">
                                <label>Total Students</label>
                                <input type="number" name="total_students" class="form-control" value="<?= getValue($school, 'total_students'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Total Faculty</label>
                                <input type="number" name="total_faculty" class="form-control" value="<?= getValue($school, 'total_faculty'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Campus Area (Acres)</label>
                                <input type="number" step="0.1" name="campus_area_acres" class="form-control" value="<?= getValue($school, 'campus_area_acres'); ?>">
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
                                        <option value="<?= $s['id']; ?>" <?= getValue($school, 'state_id') == $s['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($s['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>City</label>
                                <select name="city_id" id="city_id" class="form-control">
                                    <option value="">Select City</option>
                                    <?php foreach($cities as $c): ?>
                                        <option value="<?= $c['id']; ?>" <?= getValue($school, 'city_id') == $c['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($c['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group full">
                                <label>Address</label>
                                <textarea name="address" class="form-control" rows="3"><?= getValue($school, 'address'); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?= getValue($school, 'email'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= getValue($school, 'phone'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Website URL</label>
                                <input type="url" name="website_url" class="form-control" value="<?= getValue($school, 'website_url'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Pincode</label>
                                <input type="text" name="pincode" class="form-control" value="<?= getValue($school, 'pincode'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="number" step="0.000001" name="latitude" class="form-control" value="<?= getValue($school, 'latitude'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="number" step="0.000001" name="longitude" class="form-control" value="<?= getValue($school, 'longitude'); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Google Maps URL</label>
                                <input type="url" name="google_maps_embed_url" class="form-control" value="<?= getValue($school, 'google_maps_embed_url'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="schools.php" style="padding:12px 24px; border:1px solid var(--border-color); border-radius:8px; text-decoration:none; color:var(--text-dark); font-weight:600;">Cancel</a>
                        <button type="submit" class="btn btn-primary" style="padding:12px 32px; font-size:1rem;"><i class="ph ph-floppy-disk"></i> Save Identity Details</button>
                    </div>
                </form>

                <?php elseif($current_tab == 'about'): ?>
                <form action="" method="POST">
                    <div class="form-section">
                        <h3><i class="ph ph-info"></i> About & Highlights</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>About the School</label>
                                <textarea name="about_text" class="form-control" rows="6" style="display:none"><?= getValue($school, 'about_text'); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Highlights (one per line)</label>
                                <textarea name="highlights_json" class="form-control" rows="4" placeholder="e.g. 100% pass rate&#10;Smart classrooms&#10;Olympiad winners"><?= jsonToLines($school['highlights_json'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-paper-plane-tilt"></i> Admissions</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Admission Process</label>
                                <textarea name="admission_process" class="form-control" rows="4" style="display:none"><?= getValue($school, 'admission_process'); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Accepted Entrance Exams (one per line)</label>
                                <textarea name="accepted_exams" class="form-control" rows="3" placeholder="e.g. Nursery Interview&#10;Class 1 Written Test"><?= jsonToLines($school['accepted_exams'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Admission Start Date</label>
                                <input type="date" name="admission_start_date" class="form-control" value="<?= getValue($school, 'admission_start_date'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Admission End Date</label>
                                <input type="date" name="admission_end_date" class="form-control" value="<?= getValue($school, 'admission_end_date'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="ph ph-buildings"></i> Infrastructure & Facilities</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <div class="checkbox-group" style="margin-top:0;"><input type="checkbox" name="library" <?= !empty($school['library']) ? 'checked' : ''; ?>> <label>Library</label></div>
                                <div class="checkbox-group" style="margin-top:10px;"><input type="checkbox" name="auditorium" <?= !empty($school['auditorium']) ? 'checked' : ''; ?>> <label>Auditorium</label></div>
                                <div class="checkbox-group" style="margin-top:10px;"><input type="checkbox" name="cafeteria" <?= !empty($school['cafeteria']) ? 'checked' : ''; ?>> <label>Cafeteria</label></div>
                                <div class="checkbox-group" style="margin-top:10px;"><input type="checkbox" name="wifi" <?= !empty($school['wifi']) ? 'checked' : ''; ?>> <label>WiFi Campus</label></div>
                                <div class="checkbox-group" style="margin-top:10px;"><input type="checkbox" name="medical_facility" <?= !empty($school['medical_facility']) ? 'checked' : ''; ?>> <label>Medical Facility</label></div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group" style="margin-top:0;"><input type="checkbox" name="transport" <?= !empty($school['transport']) ? 'checked' : ''; ?>> <label>Transport</label></div>
                                <div class="checkbox-group" style="margin-top:10px;"><input type="checkbox" name="playground" <?= !empty($school['playground']) ? 'checked' : ''; ?>> <label>Playground</label></div>
                                <div class="checkbox-group" style="margin-top:10px;"><input type="checkbox" name="swimming_pool" <?= !empty($school['swimming_pool']) ? 'checked' : ''; ?>> <label>Swimming Pool</label></div>
                                <div class="checkbox-group" style="margin-top:10px;"><input type="checkbox" name="labs" <?= !empty($school['labs']) ? 'checked' : ''; ?>> <label>Science Labs</label></div>
                                <div class="checkbox-group" style="margin-top:10px;"><input type="checkbox" name="smart_classrooms" <?= !empty($school['smart_classrooms']) ? 'checked' : ''; ?>> <label>Smart Classrooms</label></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="schools.php" style="padding:12px 24px; border:1px solid var(--border-color); border-radius:8px; text-decoration:none; color:var(--text-dark); font-weight:600;">Cancel</a>
                        <button type="submit" class="btn btn-primary" style="padding:12px 32px; font-size:1rem;"><i class="ph ph-floppy-disk"></i> Save About Details</button>
                    </div>
                </form>

                <?php elseif($current_tab == 'courses'): ?>
                <?php
                $schoolCourses = $pdo->prepare("SELECT * FROM school_courses WHERE school_id = ? ORDER BY sort_order ASC, class_name ASC");
                $schoolCourses->execute([$id]);
                $schoolCourses = $schoolCourses->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <form action="" method="POST">
                    <input type="hidden" name="course_action" value="add">
                    <div class="form-section">
                        <h3><i class="ph ph-plus-circle"></i> Add Class / Course</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Class / Section Name *</label>
                                <input type="text" name="class_name" class="form-control" required placeholder="e.g. Nursery, Class 1, Class 10">
                            </div>
                            <div class="form-group">
                                <label>Level</label>
                                <select name="class_level" class="form-control">
                                    <option value="">Select</option>
                                    <?php foreach(['nursery'=>'Nursery','lkg'=>'LKG','ukg'=>'UKG','primary'=>'Primary (1-5)','upper_primary'=>'Upper Primary (6-8)','secondary'=>'Secondary (9-10)','senior_secondary'=>'Senior Secondary (11-12)'] as $v=>$l): ?>
                                        <option value="<?= $v ?>"><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Annual Fee (₹)</label>
                                <input type="number" name="annual_fee" class="form-control" step="0.01" placeholder="e.g. 50000">
                            </div>
                            <div class="form-group">
                                <label>Semester Fee (₹)</label>
                                <input type="number" name="semester_fee" class="form-control" step="0.01">
                            </div>
                            <div class="form-group">
                                <label>Total Fee (₹)</label>
                                <input type="number" name="total_fee" class="form-control" step="0.01">
                            </div>
                            <div class="form-group">
                                <label>Seats</label>
                                <input type="number" name="seats_available" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Session Year</label>
                                <input type="text" name="session_year" class="form-control" placeholder="e.g. 2026-27">
                            </div>
                            <div class="form-group">
                                <label>Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" style="padding:12px 32px; font-size:1rem;"><i class="ph ph-plus"></i> Add Class</button>
                        </div>
                    </div>
                </form>

                <?php if(!empty($schoolCourses)): ?>
                <div class="form-section">
                    <h3><i class="ph ph-list"></i> Added Classes (<?= count($schoolCourses) ?>)</h3>
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead><tr style="border-bottom:2px solid var(--border-color);">
                                <th style="padding:12px 16px;text-align:left;font-size:.82rem;color:var(--text-muted);text-transform:uppercase;">Class</th>
                                <th style="padding:12px 16px;text-align:left;font-size:.82rem;color:var(--text-muted);text-transform:uppercase;">Level</th>
                                <th style="padding:12px 16px;text-align:left;font-size:.82rem;color:var(--text-muted);text-transform:uppercase;">Annual Fee</th>
                                <th style="padding:12px 16px;text-align:left;font-size:.82rem;color:var(--text-muted);text-transform:uppercase;">Seats</th>
                                <th style="padding:12px 16px;text-align:left;font-size:.82rem;color:var(--text-muted);text-transform:uppercase;">Session</th>
                                <th style="padding:12px 16px;text-align:left;font-size:.82rem;color:var(--text-muted);text-transform:uppercase;">Actions</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach($schoolCourses as $sc): ?>
                            <tr style="border-bottom:1px solid var(--border-color);">
                                <td style="padding:12px 16px;font-weight:600;"><?= htmlspecialchars($sc['class_name']) ?></td>
                                <td style="padding:12px 16px;text-transform:capitalize;"><?= htmlspecialchars($sc['class_level'] ?? '—') ?></td>
                                <td style="padding:12px 16px;"><?= $sc['annual_fee'] ? '₹'.number_format((float)$sc['annual_fee']) : '—' ?></td>
                                <td style="padding:12px 16px;"><?= $sc['seats_available'] ?: '—' ?></td>
                                <td style="padding:12px 16px;"><?= htmlspecialchars($sc['session_year'] ?? '—') ?></td>
                                <td style="padding:12px 16px;">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this class?')">
                                        <input type="hidden" name="course_action" value="delete">
                                        <input type="hidden" name="course_id" value="<?= htmlspecialchars($sc['id']) ?>">
                                        <button type="submit" style="background:none;border:1px solid #fecaca;color:#dc2626;padding:4px 12px;border-radius:6px;font-size:.8rem;cursor:pointer;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php elseif($current_tab == 'news'): ?>
                <?php
                $schoolNews = $pdo->prepare("SELECT * FROM school_news WHERE school_id = ? ORDER BY event_date DESC, created_at DESC");
                $schoolNews->execute([$id]);
                $schoolNews = $schoolNews->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="news_action" value="add">
                    <div class="form-section">
                        <h3><i class="ph ph-plus-circle"></i> Add News / Update</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Title *</label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. Annual Day Celebration">
                            </div>
                            <div class="form-group">
                                <label>Event Date</label>
                                <input type="date" name="event_date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                            <div class="form-group full">
                                <label>News Image</label>
                                <input type="file" name="news_image" class="form-control" accept="image/*">
                            </div>
                            <div class="form-group full">
                                <label>Summary</label>
                                <textarea name="excerpt" class="form-control" rows="2" placeholder="Short description..."></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Full Content</label>
                                <textarea name="content" class="form-control" id="newsContentEditor" style="display:none"></textarea>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" style="padding:12px 32px; font-size:1rem;"><i class="ph ph-plus"></i> Add News</button>
                        </div>
                    </div>
                </form>

                <?php if(!empty($schoolNews)): ?>
                <div class="form-section">
                    <h3><i class="ph ph-list"></i> Published News (<?= count($schoolNews) ?>)</h3>
                    <?php foreach($schoolNews as $n): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border-color);gap:12px;flex-wrap:wrap;">
                        <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:200px;">
                            <?php if(!empty($n['image_url'])): ?>
                                <?php $imgSrc = strpos($n['image_url'],'http')===0 ? $n['image_url'] : '../'.$n['image_url']; ?>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" style="width:60px;height:45px;object-fit:cover;border-radius:6px;border:1px solid var(--border-color);flex-shrink:0;">
                            <?php endif; ?>
                            <div>
                                <div style="font-weight:600;"><?= htmlspecialchars($n['title']) ?></div>
                                <div style="font-size:.82rem;color:var(--text-muted);">
                                    <?= $n['event_date'] ? date('d M Y', strtotime($n['event_date'])) : 'No date' ?>
                                    · <span style="color:<?= $n['status']==='published' ? '#16a34a' : '#64748b' ?>"><?= ucfirst($n['status']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div style="display:flex;gap:6px;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="news_action" value="toggle">
                                <input type="hidden" name="news_id" value="<?= htmlspecialchars($n['id']) ?>">
                                <button type="submit" style="background:none;border:1px solid var(--border-color);color:var(--text-dark);padding:4px 12px;border-radius:6px;font-size:.8rem;cursor:pointer;"><?= $n['status']==='published' ? 'Unpublish' : 'Publish' ?></button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this news?')">
                                <input type="hidden" name="news_action" value="delete">
                                <input type="hidden" name="news_id" value="<?= htmlspecialchars($n['id']) ?>">
                                <button type="submit" style="background:none;border:1px solid #fecaca;color:#dc2626;padding:4px 12px;border-radius:6px;font-size:.8rem;cursor:pointer;">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php elseif($current_tab == 'seo'): ?>
                <form action="" method="POST">
                    <div class="form-section">
                        <h3><i class="ph ph-magnifying-glass"></i> Publish & Search Settings</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="publish_status" class="form-control">
                                    <option value="draft" <?= getValue($school, 'publish_status') == 'draft' ? 'selected' : ''; ?>>Draft (Not Visible)</option>
                                    <option value="published" <?= getValue($school, 'publish_status') == 'published' ? 'selected' : ''; ?>>Published (Live)</option>
                                </select>
                            </div>
                            <div class="form-group checkbox-group" style="margin-top:0;">
                                <input type="checkbox" id="noindex" name="noindex" <?= !empty($school['noindex']) ? 'checked' : ''; ?>>
                                <label for="noindex">Hide from Google Search</label>
                            </div>
                            <div class="form-group full">
                                <label>Page Title (what shows on Google)</label>
                                <input type="text" name="meta_title" class="form-control" value="<?= getValue($school, 'meta_title'); ?>" placeholder="School Name: Fees, Admission, Reviews">
                                <small style="color:#64748b;font-size:.8rem">Keep under 60 characters for best results</small>
                            </div>
                            <div class="form-group full">
                                <label>Search Description (what shows below title on Google)</label>
                                <textarea name="meta_description" class="form-control" rows="3" placeholder="Explore School Name — fees, admissions, courses, reviews..."><?= getValue($school, 'meta_description'); ?></textarea>
                                <small style="color:#64748b;font-size:.8rem">Keep under 160 characters for best results</small>
                            </div>
                            <div class="form-group full">
                                <label>Website Link (leave blank to auto-fill)</label>
                                <input type="url" name="canonical_url" class="form-control" value="<?= getValue($school, 'canonical_url'); ?>" placeholder="https://yourschool.com">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="schools.php" style="padding:12px 24px; border:1px solid var(--border-color); border-radius:8px; text-decoration:none; color:var(--text-dark); font-weight:600;">Cancel</a>
                        <button type="submit" class="btn btn-primary" style="padding:12px 32px; font-size:1rem;"><i class="ph ph-floppy-disk"></i> Save</button>
                    </div>
                </form>
                <?php endif; ?>

            </div>
        </main>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    <script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }
    // Board affiliation toggle
    function toggleBoardState() {
        const boardSelect = document.getElementById('board_affiliation');
        const stateGroup = document.getElementById('board_state_group');
        if (boardSelect && stateGroup) {
            stateGroup.style.display = boardSelect.value === 'State' ? 'block' : 'none';
        }
    }
    document.getElementById('board_affiliation')?.addEventListener('change', toggleBoardState);

    // State/City cascade
    function loadCities(stateId, selectedCityId) {
        const citySelect = document.getElementById('city_id');
        if (!stateId) { citySelect.innerHTML = '<option value="">Select City</option>'; return; }
        fetch('api/get_cities.php?state_id=' + stateId)
            .then(r => r.json())
            .then(data => {
                citySelect.innerHTML = '<option value="">Select City</option>';
                data.forEach(c => {
                    const selected = (selectedCityId && c.id == selectedCityId) ? ' selected' : '';
                    citySelect.innerHTML += '<option value="' + c.id + '"' + selected + '>' + c.name + '</option>';
                });
            })
            .catch(err => { console.error('Failed to load cities:', err); });
    }

    document.getElementById('state_id')?.addEventListener('change', function() {
        loadCities(this.value, null);
    });

    // Load cities on page load if state is pre-selected (edit mode)
    (function() {
        const stateEl = document.getElementById('state_id');
        const cityEl = document.getElementById('city_id');
        if (stateEl && stateEl.value) {
            const currentCity = cityEl?.value || '';
            loadCities(stateEl.value, currentCity);
        }
    })();
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
    <script>
    $(document).ready(function() {
        $('textarea[name="about_text"]').trumbowyg({
            btns: [
                ['viewHTML'],
                ['formatting'],
                ['strong', 'em', 'del'],
                ['superscript', 'subscript'],
                ['link'],
                ['unorderedList', 'orderedList'],
                ['horizontalRule'],
                ['removeformat'],
                ['fullscreen']
            ],
            autogrow: true
        });
        $('textarea[name="admission_process"]').trumbowyg({
            btns: [
                ['viewHTML'],
                ['formatting'],
                ['strong', 'em', 'del'],
                ['link'],
                ['unorderedList', 'orderedList'],
                ['horizontalRule'],
                ['removeformat'],
                ['fullscreen']
            ],
            autogrow: true
        });
        if ($('#newsContentEditor').length) {
            $('#newsContentEditor').trumbowyg({
                btns: [
                    ['viewHTML'],
                    ['formatting'],
                    ['strong', 'em', 'del'],
                    ['link'],
                    ['unorderedList', 'orderedList'],
                    ['horizontalRule'],
                    ['removeformat'],
                    ['fullscreen']
                ],
                autogrow: true
            });
        }
    });
    </script>
</html>
