<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$is_edit = $id !== null;
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'basic';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'basic') {
    $slug = !empty($_POST['exam_slug']) ? $_POST['exam_slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['exam_name'])));
    
    $slugCheckQ = "SELECT id FROM exams WHERE exam_slug = :slug";
    if ($is_edit) $slugCheckQ .= " AND id != :id";
    $slugCheckStmt = $pdo->prepare($slugCheckQ);
    $slugCheckParams = ['slug' => $slug];
    if ($is_edit) $slugCheckParams['id'] = $id;
    $slugCheckStmt->execute($slugCheckParams);
    
    if ($slugCheckStmt->rowCount() > 0) {
        $error = "The slug '$slug' is already in use.";
    } else {
        try {
            $logo_url = !empty($_POST['existing_conducting_body_logo']) ? $_POST['existing_conducting_body_logo'] : null;
            if (isset($_FILES['conducting_body_logo_file']) && $_FILES['conducting_body_logo_file']['error'] == 0) {
                $target_dir = "../uploads/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $file_extension = strtolower(pathinfo($_FILES["conducting_body_logo_file"]["name"], PATHINFO_EXTENSION));
                $new_filename = uniqid('exam_logo_') . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($_FILES["conducting_body_logo_file"]["tmp_name"], $target_file)) {
                    $logo_url = "uploads/" . $new_filename;
                    require_once __DIR__ . '/upload_sync.php';
                    sync_to_github('uploads/' . $new_filename);
                }
            }

            $data = [
                'exam_name' => $_POST['exam_name'],
                'exam_slug' => $slug,
                'exam_abbreviation' => $_POST['exam_abbreviation'] ?: null,
                'conducting_body' => $_POST['conducting_body'] ?: null,
                'conducting_body_logo' => $logo_url,
                'exam_level' => $_POST['exam_level'] ?: null,
                'exam_mode' => $_POST['exam_mode'] ?: null,
                'exam_frequency' => $_POST['exam_frequency'] ?: null,
                'participating_colleges_count' => $_POST['participating_colleges_count'] ?: 0,
                'applicants_last_year' => $_POST['applicants_last_year'] ?: 0,
                'status' => $_POST['status'] ?: 'upcoming',
                'is_national' => isset($_POST['is_national']) ? 1 : 0
            ];

            if ($is_edit) {
                $fields = [];
                foreach ($data as $key => $val) { $fields[] = "$key = :$key"; }
                $sql = "UPDATE exams SET " . implode(", ", $fields) . " WHERE id = :id";
                $data['id'] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
            } else {
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $data['id'] = $id;
                $keys = array_keys($data);
                $sql = "INSERT INTO exams (" . implode(", ", $keys) . ") VALUES (:" . implode(", :", $keys) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
            }
            header("Location: exam_form.php?id=$id&tab=basic&msg=saved");
            exit;
        } catch (Exception $e) {
            $error = "Error saving: " . $e->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'eligibility') {
    try {
        $data = [
            'id' => $id,
            'age_min' => $_POST['age_min'] ?: null,
            'age_max' => $_POST['age_max'] ?: null,
            'min_percentage_required' => $_POST['min_percentage_required'] ?: null,
            'qualifying_exam' => $_POST['qualifying_exam'] ?: null,
            'nationality' => $_POST['nationality'] ?: null,
            'total_marks' => $_POST['total_marks'] ?: null,
            'total_questions' => $_POST['total_questions'] ?: null,
            'duration_minutes' => $_POST['duration_minutes'] ?: null,
            'subjects_json' => $_POST['subjects_json'] ?: null,
            'marking_scheme' => $_POST['marking_scheme'] ?: null,
            'sections' => $_POST['sections'] ?: null,
            'language_options' => $_POST['language_options'] ?: null
        ];
        $fields = [];
        foreach($data as $key => $val) { if($key=='id') continue; $fields[] = "$key = :$key"; }
        $stmt = $pdo->prepare("UPDATE exams SET " . implode(', ', $fields) . " WHERE id = :id");
        $stmt->execute($data);
        header("Location: exam_form.php?id=$id&tab=eligibility&msg=saved");
        exit;
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'links') {
    try {
        $handle_upload = function($file_input_name) {
            $existing_val = !empty($_POST['existing_' . str_replace('_file', '_url', $file_input_name)]) ? $_POST['existing_' . str_replace('_file', '_url', $file_input_name)] : null;
            if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
                $target_dir = "../uploads/";
                if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
                $file_extension = strtolower(pathinfo($_FILES[$file_input_name]["name"], PATHINFO_EXTENSION));
                $new_filename = uniqid('exam_doc_') . '.' . $file_extension;
                if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_dir . $new_filename)) {
                    return "uploads/" . $new_filename;
                }
            }
            return $existing_val;
        };

        $data = [
            'id' => $id,
            'application_fee_general' => $_POST['application_fee_general'] ?: null,
            'application_fee_obc' => $_POST['application_fee_obc'] ?: null,
            'application_fee_sc_st' => $_POST['application_fee_sc_st'] ?: null,
            'application_fee_pwd' => $_POST['application_fee_pwd'] ?: null,
            'application_fee_female' => $_POST['application_fee_female'] ?: null,
            'application_url' => $_POST['application_url'] ?: null,
            'official_website' => $_POST['official_website'] ?: null,
            'syllabus_pdf_url' => $handle_upload('syllabus_pdf_file'),
            'result_url' => $handle_upload('result_file'),
            'scorecard_url' => $handle_upload('scorecard_file'),
            'counselling_authority' => $_POST['counselling_authority'] ?: null,
            'counselling_rounds' => $_POST['counselling_rounds'] ?: null,
            'merit_list_url' => $handle_upload('merit_list_file'),
            'normalisation_method' => $_POST['normalisation_method'] ?: null
        ];
        $fields = [];
        foreach($data as $key => $val) { if($key=='id') continue; $fields[] = "$key = :$key"; }
        $stmt = $pdo->prepare("UPDATE exams SET " . implode(', ', $fields) . " WHERE id = :id");
        $stmt->execute($data);
        header("Location: exam_form.php?id=$id&tab=links&msg=saved");
        exit;
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'resources') {
    try {
        $sample_papers = [];
        // Handle existing ones that were not deleted
        if (isset($_POST['existing_sp_years']) && is_array($_POST['existing_sp_years'])) {
            foreach ($_POST['existing_sp_years'] as $index => $year) {
                if (!empty($_POST['existing_sp_urls'][$index])) {
                    $sample_papers[] = [
                        'year' => $year,
                        'subject' => $_POST['existing_sp_subjects'][$index] ?? '',
                        'description' => $_POST['existing_sp_descriptions'][$index] ?? '',
                        'url' => $_POST['existing_sp_urls'][$index]
                    ];
                }
            }
        }
        
        // Handle newly uploaded files
        if (isset($_FILES['new_sp_files']) && is_array($_FILES['new_sp_files']['name'])) {
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            
            foreach ($_FILES['new_sp_files']['name'] as $index => $name) {
                if ($_FILES['new_sp_files']['error'][$index] == 0 && !empty($_POST['new_sp_years'][$index])) {
                    $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $new_filename = uniqid('sp_') . '.' . $file_extension;
                    if (move_uploaded_file($_FILES['new_sp_files']['tmp_name'][$index], $target_dir . $new_filename)) {
                        $sample_papers[] = [
                            'year' => $_POST['new_sp_years'][$index],
                            'subject' => $_POST['new_sp_subjects'][$index] ?? '',
                            'description' => $_POST['new_sp_descriptions'][$index] ?? '',
                            'url' => "uploads/" . $new_filename
                        ];
                    }
                }
            }
        }
        
        $sample_papers_json = !empty($sample_papers) ? json_encode($sample_papers) : null;

        $stmtCheck = $pdo->prepare("SELECT id FROM exam_resources WHERE exam_id = ?");
        $stmtCheck->execute([$id]);
        if ($stmtCheck->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE exam_resources SET sample_papers_json = ? WHERE exam_id = ?");
            $stmt->execute([$sample_papers_json, $id]);
        } else {
            $newId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $stmt = $pdo->prepare("INSERT INTO exam_resources (id, exam_id, sample_papers_json) VALUES (?, ?, ?)");
            $stmt->execute([$newId, $id, $sample_papers_json]);
        }
        header("Location: exam_form.php?id=$id&tab=resources&msg=saved");
        exit;
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'results_data') {
    try {
        $cutoff_pdfs = [];
        if (isset($_POST['existing_co_years']) && is_array($_POST['existing_co_years'])) {
            foreach ($_POST['existing_co_years'] as $index => $year) {
                if (!empty($_POST['existing_co_urls'][$index])) {
                    $cutoff_pdfs[] = [
                        'year' => $year,
                        'subject' => $_POST['existing_co_subjects'][$index] ?? '',
                        'description' => $_POST['existing_co_descriptions'][$index] ?? '',
                        'url' => $_POST['existing_co_urls'][$index]
                    ];
                }
            }
        }
        
        if (isset($_FILES['new_co_files']) && is_array($_FILES['new_co_files']['name'])) {
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            
            foreach ($_FILES['new_co_files']['name'] as $index => $name) {
                if ($_FILES['new_co_files']['error'][$index] == 0 && !empty($_POST['new_co_years'][$index])) {
                    $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $new_filename = uniqid('co_') . '.' . $file_extension;
                    if (move_uploaded_file($_FILES['new_co_files']['tmp_name'][$index], $target_dir . $new_filename)) {
                        $cutoff_pdfs[] = [
                            'year' => $_POST['new_co_years'][$index],
                            'subject' => $_POST['new_co_subjects'][$index] ?? '',
                            'description' => $_POST['new_co_descriptions'][$index] ?? '',
                            'url' => "uploads/" . $new_filename
                        ];
                    }
                }
            }
        }
        
        $cutoff_pdfs_json = !empty($cutoff_pdfs) ? json_encode($cutoff_pdfs) : null;

        $stmtCheck = $pdo->prepare("SELECT id FROM exam_results WHERE exam_id = ?");
        $stmtCheck->execute([$id]);
        if ($stmtCheck->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE exam_results SET percentile_vs_marks_json = ?, cutoff_pdfs_json = ? WHERE exam_id = ?");
            $stmt->execute([$_POST['percentile_vs_marks_json'] ?: null, $cutoff_pdfs_json, $id]);
        } else {
            $newId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $stmt = $pdo->prepare("INSERT INTO exam_results (id, exam_id, percentile_vs_marks_json, cutoff_pdfs_json) VALUES (?, ?, ?, ?)");
            $stmt->execute([$newId, $id, $_POST['percentile_vs_marks_json'] ?: null, $cutoff_pdfs_json]);
        }
        header("Location: exam_form.php?id=$id&tab=results_data&msg=saved");
        exit;
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
}

$exam = [];
$exam_resources = [];
$exam_results = [];
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$exam) {
        header('Location: exams.php');
        exit;
    }

    $stmtRes = $pdo->prepare("SELECT sample_papers_json FROM exam_resources WHERE exam_id = ?");
    $stmtRes->execute([$id]);
    if ($res = $stmtRes->fetch(PDO::FETCH_ASSOC)) {
        $exam_resources = $res;
    }

    $stmtRst = $pdo->prepare("SELECT percentile_vs_marks_json, cutoff_pdfs_json FROM exam_results WHERE exam_id = ?");
    $stmtRst->execute([$id]);
    if ($rst = $stmtRst->fetch(PDO::FETCH_ASSOC)) {
        $exam_results = $rst;
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
    <title><?php echo $is_edit ? 'Edit Exam' : 'Add New Exam'; ?> | AdmissionSeason Admin</title>
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
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); font-size: 0.95rem; }
        .form-control { width: 100%; min-width: 0; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; color: var(--text-dark); background: #fff; transition: all 0.3s ease; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(11, 36, 71, 0.1); }
        .checkbox-group { display: flex; align-items: center; gap: 8px; margin-top: 32px; flex-wrap: wrap; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .checkbox-group label { margin-bottom: 0; cursor: pointer; }
        .error-alert { padding: 16px; background: rgba(15,23,42,0.06); color: #0B2447; border-radius: 8px; margin-bottom: 24px; border: 1px solid rgba(15,23,42,0.06); }
        .msg-alert { padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 24px; border: 1px solid rgba(11,36,71,0.04); }
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

        .subject-row, .section-row, .sp-row, .co-row, .pvm-row {
            flex-wrap: wrap;
        }

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
            .subject-row, .section-row, .sp-row, .co-row, .pvm-row {
                flex-direction: column;
                align-items: stretch;
            }
            .subject-row .form-control,
            .section-row .form-control {
                width: 100% !important;
            }
            .sp-row, .co-row {
                gap: 8px;
            }
            .sp-row .form-control, .co-row .form-control {
                width: 100% !important;
            }
            .marking-scheme-grid {
                flex-direction: column;
            }
            .marking-scheme-grid > div {
                width: 100%;
            }
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
                    <h2>
                        <a href="exams.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                        <?php echo $is_edit ? 'Edit Exam: ' . htmlspecialchars($exam['exam_name']) : 'Add New Exam'; ?>
                    </h2>
                </div>

                <?php if($is_edit): ?>
                <div class="tabs-nav">
                    <a href="?id=<?php echo $id; ?>&tab=basic" class="tab-link <?php echo $current_tab=='basic'?'active':''; ?>">Basic Info</a>
                    <a href="?id=<?php echo $id; ?>&tab=eligibility" class="tab-link <?php echo $current_tab=='eligibility'?'active':''; ?>">Eligibility & Pattern</a>
                    <a href="?id=<?php echo $id; ?>&tab=links" class="tab-link <?php echo $current_tab=='links'?'active':''; ?>">Fees & Links</a>
                    <a href="?id=<?php echo $id; ?>&tab=resources" class="tab-link <?php echo $current_tab=='resources'?'active':''; ?>">Resources</a>
                    <a href="?id=<?php echo $id; ?>&tab=results_data" class="tab-link <?php echo $current_tab=='results_data'?'active':''; ?>">Results Data</a>
                    
                    <a href="exam_dates.php?exam_id=<?php echo $id; ?>" class="tab-link">All Dates</a>
                    <a href="exam_syllabus.php?exam_id=<?php echo $id; ?>" class="tab-link">Syllabus</a>
                </div>
                <?php else: ?>
                <div class="tabs-nav">
                    <span class="tab-link active">Basic Info</span>
                    <span class="tab-link disabled">Eligibility & Pattern</span>
                    <span class="tab-link disabled">Fees & Links</span>
                    <span class="tab-link disabled">Resources</span>
                    <span class="tab-link disabled">Results Data</span>
                    <span class="tab-link disabled">All Dates</span>
                    <span class="tab-link disabled">Syllabus</span>
                </div>
                <?php endif; ?>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> Details saved successfully!</div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="error-alert"><i class="ph-fill ph-warning-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if($current_tab == 'basic'): ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3><i class="ph ph-exam"></i> Basic Details</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Exam Name *</label>
                                <input type="text" name="exam_name" class="form-control" required value="<?php echo getValue($exam, 'exam_name'); ?>">
                            </div>
                            <div class="form-group">
                                <label>URL Slug (Leave blank to auto-generate)</label>
                                <input type="text" name="exam_slug" class="form-control" value="<?php echo getValue($exam, 'exam_slug'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Exam Abbreviation</label>
                                <input type="text" name="exam_abbreviation" class="form-control" value="<?php echo getValue($exam, 'exam_abbreviation'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Conducting Body</label>
                                <input type="text" name="conducting_body" class="form-control" value="<?php echo getValue($exam, 'conducting_body'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Conducting Body Logo</label>
                                <input type="hidden" name="existing_conducting_body_logo" value="<?php echo getValue($exam, 'conducting_body_logo'); ?>">
                                <input type="file" name="conducting_body_logo_file" class="form-control" accept="image/*">
                                <?php if(getValue($exam, 'conducting_body_logo')): ?>
                                    <div style="margin-top: 8px;">
                                        <img src="../<?php echo getValue($exam, 'conducting_body_logo'); ?>" alt="Logo" style="max-height: 50px; border-radius: 4px;">
                                        <small class="text-muted" style="display:block; margin-top: 4px;">Current logo</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label>Exam Level</label>
                                <select name="exam_level" class="form-control">
                                    <option value="">Select</option>
                                    <option value="national" <?php echo getValue($exam, 'exam_level') == 'national' ? 'selected' : ''; ?>>National</option>
                                    <option value="state" <?php echo getValue($exam, 'exam_level') == 'state' ? 'selected' : ''; ?>>State</option>
                                    <option value="university" <?php echo getValue($exam, 'exam_level') == 'university' ? 'selected' : ''; ?>>University</option>
                                    <option value="institute" <?php echo getValue($exam, 'exam_level') == 'institute' ? 'selected' : ''; ?>>Institute</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Exam Mode</label>
                                <select name="exam_mode" class="form-control">
                                    <option value="">Select</option>
                                    <option value="online" <?php echo getValue($exam, 'exam_mode') == 'online' ? 'selected' : ''; ?>>Online</option>
                                    <option value="offline" <?php echo getValue($exam, 'exam_mode') == 'offline' ? 'selected' : ''; ?>>Offline</option>
                                    <option value="both" <?php echo getValue($exam, 'exam_mode') == 'both' ? 'selected' : ''; ?>>Both</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Frequency</label>
                                <select name="exam_frequency" class="form-control">
                                    <option value="">Select</option>
                                    <option value="annual" <?php echo getValue($exam, 'exam_frequency') == 'annual' ? 'selected' : ''; ?>>Annual</option>
                                    <option value="biannual" <?php echo getValue($exam, 'exam_frequency') == 'biannual' ? 'selected' : ''; ?>>Biannual</option>
                                    <option value="quarterly" <?php echo getValue($exam, 'exam_frequency') == 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                    <option value="monthly" <?php echo getValue($exam, 'exam_frequency') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" <?php echo getValue($exam, 'status') == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="upcoming" <?php echo getValue($exam, 'status') == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                    <option value="completed" <?php echo getValue($exam, 'status') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo getValue($exam, 'status') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Participating Colleges Count</label>
                                <input type="number" name="participating_colleges_count" class="form-control" value="<?php echo getValue($exam, 'participating_colleges_count'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Applicants Last Year</label>
                                <input type="number" name="applicants_last_year" class="form-control" value="<?php echo getValue($exam, 'applicants_last_year'); ?>">
                            </div>
                            
                            <div class="form-group checkbox-group" style="grid-column: 1 / -1;">
                                <input type="checkbox" id="is_national" name="is_national" <?php echo !empty($exam['is_national']) ? 'checked' : ''; ?>>
                                <label for="is_national">Is National Level Exam?</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Basic Details</button>
                    </div>
                </form>

                <?php elseif($current_tab == 'eligibility'): ?>
                <form action="" method="POST">
                    <div class="form-section">
                        <h3><i class="ph ph-student"></i> Eligibility & Pattern</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Minimum Age</label>
                                <input type="number" name="age_min" class="form-control" value="<?php echo getValue($exam, 'age_min'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Maximum Age</label>
                                <input type="number" name="age_max" class="form-control" value="<?php echo getValue($exam, 'age_max'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Qualifying Exam (e.g. 10+2, Graduation)</label>
                                <input type="text" name="qualifying_exam" class="form-control" value="<?php echo getValue($exam, 'qualifying_exam'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Min Percentage Required (%)</label>
                                <input type="number" step="0.1" name="min_percentage_required" class="form-control" value="<?php echo getValue($exam, 'min_percentage_required'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Nationality</label>
                                <select name="nationality" class="form-control">
                                    <option value="">Select</option>
                                    <option value="indian" <?php echo getValue($exam, 'nationality') == 'indian' ? 'selected' : ''; ?>>Indian</option>
                                    <option value="nri" <?php echo getValue($exam, 'nationality') == 'nri' ? 'selected' : ''; ?>>NRI</option>
                                    <option value="both" <?php echo getValue($exam, 'nationality') == 'both' ? 'selected' : ''; ?>>Both</option>
                                </select>
                            </div>
                            <div class="form-group"></div>
                            
                            <div class="form-group">
                                <label>Total Marks</label>
                                <input type="number" name="total_marks" class="form-control" value="<?php echo getValue($exam, 'total_marks'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Total Questions</label>
                                <input type="number" name="total_questions" class="form-control" value="<?php echo getValue($exam, 'total_questions'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Duration (Minutes)</label>
                                <input type="number" name="duration_minutes" class="form-control" value="<?php echo getValue($exam, 'duration_minutes'); ?>">
                            </div>
                            
                            <div class="form-group full">
                                <label>Language Options (Comma separated)</label>
                                <input type="text" id="ui_language_options" class="form-control" placeholder="e.g. English, Hindi" value="">
                                <input type="hidden" name="language_options" id="language_options" value='<?php echo getValue($exam, 'language_options'); ?>'>
                            </div>
                            <div class="form-group full">
                                <label>Subjects</label>
                                <div id="subjects_container" style="margin-bottom: 10px;"></div>
                                <button type="button" class="btn btn-sm" onclick="addSubject()" style="background:rgba(15,23,42,0.08); border:1px solid rgba(15,23,42,0.15); padding: 5px 10px; border-radius: 4px; cursor: pointer;">+ Add Subject</button>
                                <input type="hidden" name="subjects_json" id="subjects_json" value='<?php echo getValue($exam, 'subjects_json'); ?>'>
                            </div>
                            <div class="form-group full">
                                <label>Sections</label>
                                <div id="sections_container" style="margin-bottom: 10px;"></div>
                                <button type="button" class="btn btn-sm" onclick="addSection()" style="background:rgba(15,23,42,0.08); border:1px solid rgba(15,23,42,0.15); padding: 5px 10px; border-radius: 4px; cursor: pointer;">+ Add Section</button>
                                <input type="hidden" name="sections" id="sections" value='<?php echo getValue($exam, 'sections'); ?>'>
                            </div>
                            <div class="form-group full">
                                <label>Marking Scheme</label>
                                <div class="marking-scheme-grid" style="display:flex; gap: 15px; align-items: center;">
                                    <div><small style="display:block; margin-bottom:4px; color:var(--text-muted);">Correct</small><input type="number" id="ms_correct" class="form-control" step="0.1"></div>
                                    <div><small style="display:block; margin-bottom:4px; color:var(--text-muted);">Wrong</small><input type="number" id="ms_wrong" class="form-control" step="0.1"></div>
                                    <div><small style="display:block; margin-bottom:4px; color:var(--text-muted);">Unattempted</small><input type="number" id="ms_unattempted" class="form-control" step="0.1"></div>
                                </div>
                                <input type="hidden" name="marking_scheme" id="marking_scheme" value='<?php echo getValue($exam, 'marking_scheme'); ?>'>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Eligibility</button>
                    </div>
                </form>

                <?php elseif($current_tab == 'links'): ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3><i class="ph ph-currency-inr"></i> Fees & Links</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Application Fee (General) ₹</label>
                                <input type="number" step="0.01" name="application_fee_general" class="form-control" value="<?php echo getValue($exam, 'application_fee_general'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Application Fee (OBC) ₹</label>
                                <input type="number" step="0.01" name="application_fee_obc" class="form-control" value="<?php echo getValue($exam, 'application_fee_obc'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Application Fee (SC/ST) ₹</label>
                                <input type="number" step="0.01" name="application_fee_sc_st" class="form-control" value="<?php echo getValue($exam, 'application_fee_sc_st'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Application Fee (PwD) ₹</label>
                                <input type="number" step="0.01" name="application_fee_pwd" class="form-control" value="<?php echo getValue($exam, 'application_fee_pwd'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Application Fee (Female) ₹</label>
                                <input type="number" step="0.01" name="application_fee_female" class="form-control" value="<?php echo getValue($exam, 'application_fee_female'); ?>">
                            </div>
                            <div class="form-group"></div>
                            
                            <div class="form-group full">
                                <label>Official Website</label>
                                <input type="url" name="official_website" class="form-control" value="<?php echo getValue($exam, 'official_website'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Application URL</label>
                                <input type="url" name="application_url" class="form-control" value="<?php echo getValue($exam, 'application_url'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Syllabus PDF File</label>
                                <input type="hidden" name="existing_syllabus_pdf_url" value="<?php echo getValue($exam, 'syllabus_pdf_url'); ?>">
                                <input type="file" name="syllabus_pdf_file" class="form-control" accept=".pdf,.doc,.docx">
                                <?php if(getValue($exam, 'syllabus_pdf_url')): ?>
                                    <small style="display:block; margin-top: 4px;"><a href="../<?php echo getValue($exam, 'syllabus_pdf_url'); ?>" target="_blank">View Current Syllabus File</a></small>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Result File</label>
                                <input type="hidden" name="existing_result_url" value="<?php echo getValue($exam, 'result_url'); ?>">
                                <input type="file" name="result_file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx">
                                <?php if(getValue($exam, 'result_url')): ?>
                                    <small style="display:block; margin-top: 4px;"><a href="../<?php echo getValue($exam, 'result_url'); ?>" target="_blank">View Current Result File</a></small>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Scorecard File</label>
                                <input type="hidden" name="existing_scorecard_url" value="<?php echo getValue($exam, 'scorecard_url'); ?>">
                                <input type="file" name="scorecard_file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx">
                                <?php if(getValue($exam, 'scorecard_url')): ?>
                                    <small style="display:block; margin-top: 4px;"><a href="../<?php echo getValue($exam, 'scorecard_url'); ?>" target="_blank">View Current Scorecard File</a></small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group full" style="margin-top: 16px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                                <label style="font-size: 1.1rem; color: var(--primary);">Counselling Information</label>
                            </div>
                            <div class="form-group">
                                <label>Counselling Authority</label>
                                <input type="text" name="counselling_authority" class="form-control" value="<?php echo getValue($exam, 'counselling_authority'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Total Rounds</label>
                                <input type="number" name="counselling_rounds" class="form-control" value="<?php echo getValue($exam, 'counselling_rounds'); ?>">
                            </div>
                            <div class="form-group full">
                                <label>Merit List File</label>
                                <input type="hidden" name="existing_merit_list_url" value="<?php echo getValue($exam, 'merit_list_url'); ?>">
                                <input type="file" name="merit_list_file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx">
                                <?php if(getValue($exam, 'merit_list_url')): ?>
                                    <small style="display:block; margin-top: 4px;"><a href="../<?php echo getValue($exam, 'merit_list_url'); ?>" target="_blank">View Current Merit List File</a></small>
                                <?php endif; ?>
                            </div>
                            <div class="form-group full">
                                <label>Normalisation Method</label>
                                <textarea name="normalisation_method" class="form-control" rows="3"><?php echo getValue($exam, 'normalisation_method'); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Fees & Links</button>
                    </div>
                </form>
                
                <?php elseif($current_tab == 'resources'): ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3><i class="ph ph-files"></i> Resources</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Sample Papers</label>
                                <div id="sp_container" style="margin-bottom: 10px;">
                                    <?php 
                                    $raw_json = isset($exam_resources['sample_papers_json']) ? $exam_resources['sample_papers_json'] : '[]';
                                    $sps = json_decode($raw_json ?: '[]', true);
                                    if (!is_array($sps)) $sps = [];
                                    foreach($sps as $idx => $sp): 
                                    ?>
                                    <div class="sp-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center; background: #fff; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px;">
                                        <input type="text" name="existing_sp_years[]" class="form-control" placeholder="Year (e.g. 2023)" value="<?php echo htmlspecialchars($sp['year']); ?>" style="width: 120px;">
                                        <input type="text" name="existing_sp_subjects[]" class="form-control" placeholder="Subject" value="<?php echo htmlspecialchars($sp['subject'] ?? ''); ?>" style="width: 150px;">
                                        <input type="text" name="existing_sp_descriptions[]" class="form-control" placeholder="Description" value="<?php echo htmlspecialchars($sp['description'] ?? ''); ?>" style="flex:1;">
                                        <input type="hidden" name="existing_sp_urls[]" value="<?php echo htmlspecialchars($sp['url']); ?>">
                                        <div>
                                            <a href="../<?php echo htmlspecialchars($sp['url']); ?>" target="_blank" style="color:var(--primary); text-decoration:none; font-weight: 600;"><i class="ph ph-file-pdf"></i> View File</a>
                                        </div>
                                        <button type="button" onclick="this.parentElement.remove()" style="background:rgba(15,23,42,0.06); color:#0B2447; border:1px solid rgba(15,23,42,0.06); padding:10px; border-radius:4px; cursor:pointer;" title="Remove"><i class="ph ph-trash"></i></button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm" onclick="addSamplePaper()" style="background:rgba(15,23,42,0.08); border:1px solid rgba(15,23,42,0.15); padding: 5px 10px; border-radius: 4px; cursor: pointer;">+ Add New Sample Paper</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Resources</button>
                    </div>
                </form>
                
                <?php elseif($current_tab == 'results_data'): ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3><i class="ph ph-chart-bar"></i> Results Data</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Percentile vs Marks</label>
                                <div id="pvm_container" style="margin-bottom: 10px;"></div>
                                <button type="button" class="btn btn-sm" onclick="addPvmYear()" style="background:rgba(15,23,42,0.08); border:1px solid rgba(15,23,42,0.15); padding: 5px 10px; border-radius: 4px; cursor: pointer;">+ Add Year Data</button>
                                <input type="hidden" name="percentile_vs_marks_json" id="percentile_vs_marks_json" value='<?php echo getValue($exam_results, 'percentile_vs_marks_json'); ?>'>
                            </div>
                        </div>
                        <div class="form-grid" style="margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                            <div class="form-group full">
                                <label style="font-size: 1.1rem; color: var(--primary);">Previous Year Cutoffs & Statistics (PDFs)</label>
                                <div id="co_container" style="margin-bottom: 10px; margin-top:10px;">
                                    <?php 
                                    $raw_co_json = isset($exam_results['cutoff_pdfs_json']) ? $exam_results['cutoff_pdfs_json'] : '[]';
                                    $cos = json_decode($raw_co_json ?: '[]', true);
                                    if (!is_array($cos)) $cos = [];
                                    foreach($cos as $idx => $co): 
                                    ?>
                                    <div class="co-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center; background: #fff; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px;">
                                        <input type="text" name="existing_co_years[]" class="form-control" placeholder="Year (e.g. 2023)" value="<?php echo htmlspecialchars($co['year']); ?>" style="width: 120px;">
                                        <input type="text" name="existing_co_subjects[]" class="form-control" placeholder="Subject" value="<?php echo htmlspecialchars($co['subject'] ?? ''); ?>" style="width: 150px;">
                                        <input type="text" name="existing_co_descriptions[]" class="form-control" placeholder="Description" value="<?php echo htmlspecialchars($co['description'] ?? ''); ?>" style="flex:1;">
                                        <input type="hidden" name="existing_co_urls[]" value="<?php echo htmlspecialchars($co['url']); ?>">
                                        <div>
                                            <a href="../<?php echo htmlspecialchars($co['url']); ?>" target="_blank" style="color:var(--primary); text-decoration:none; font-weight: 600;"><i class="ph ph-file-pdf"></i> View File</a>
                                        </div>
                                        <button type="button" onclick="this.parentElement.remove()" style="background:rgba(15,23,42,0.06); color:#0B2447; border:1px solid rgba(15,23,42,0.06); padding:10px; border-radius:4px; cursor:pointer;" title="Remove"><i class="ph ph-trash"></i></button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm" onclick="addCutoffFile()" style="background:rgba(15,23,42,0.08); border:1px solid rgba(15,23,42,0.15); padding: 5px 10px; border-radius: 4px; cursor: pointer;">+ Add New Document</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Results Data</button>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.querySelector('input[name="exam_name"]');
            const slugInput = document.querySelector('input[name="exam_slug"]');
            
            if (nameInput && slugInput) {
                let autoGenerate = slugInput.value === '';
                
                slugInput.addEventListener('input', function() {
                    autoGenerate = false;
                    if (this.value === '') {
                        autoGenerate = true;
                    }
                });

                nameInput.addEventListener('input', function() {
                    if (autoGenerate) {
                        let val = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                        slugInput.value = val;
                    }
                });
            }

            // --- Eligibility UI Logic ---
            const langInput = document.getElementById('ui_language_options');
            const langHidden = document.getElementById('language_options');
            
            const msCorrect = document.getElementById('ms_correct');
            const msWrong = document.getElementById('ms_wrong');
            const msUnattempted = document.getElementById('ms_unattempted');
            const msHidden = document.getElementById('marking_scheme');
            
            if (langInput && langHidden) {
                // Init languages
                try {
                    let langs = JSON.parse(langHidden.value || '[]');
                    langInput.value = langs.join(', ');
                } catch(e) {}
                
                // Init marking scheme
                try {
                    let ms = JSON.parse(msHidden.value || '{}');
                    msCorrect.value = ms.correct !== undefined ? ms.correct : '';
                    msWrong.value = ms.wrong !== undefined ? ms.wrong : '';
                    msUnattempted.value = ms.unattempted !== undefined ? ms.unattempted : '';
                } catch(e) {}

                // Form submit hook
                const eligibilityForm = langInput.closest('form');
                if (eligibilityForm) {
                    eligibilityForm.addEventListener('submit', function() {
                        // Serialize languages
                        let langs = langInput.value.split(',').map(s => s.trim()).filter(s => s);
                        langHidden.value = JSON.stringify(langs);
                        
                        // Serialize marking scheme
                        let ms = {
                            correct: msCorrect.value !== '' ? Number(msCorrect.value) : 0,
                            wrong: msWrong.value !== '' ? Number(msWrong.value) : 0,
                            unattempted: msUnattempted.value !== '' ? Number(msUnattempted.value) : 0
                        };
                        msHidden.value = JSON.stringify(ms);
                        
                        // Serialize subjects
                        let subjects = [];
                        document.querySelectorAll('.subject-row').forEach(row => {
                            let name = row.querySelector('.sub-name').value;
                            let qs = row.querySelector('.sub-qs').value;
                            let marks = row.querySelector('.sub-marks').value;
                            if(name) {
                                subjects.push({
                                    subject: name,
                                    questions: qs !== '' ? Number(qs) : 0,
                                    marks: marks !== '' ? Number(marks) : 0
                                });
                            }
                        });
                        document.getElementById('subjects_json').value = JSON.stringify(subjects);
                        
                        // Serialize sections
                        let sections = [];
                        document.querySelectorAll('.section-row').forEach(row => {
                            let name = row.querySelector('.sec-name').value;
                            let qs = row.querySelector('.sec-qs').value;
                            let time = row.querySelector('.sec-time').value;
                            if(name) {
                                sections.push({
                                    name: name,
                                    questions: qs !== '' ? Number(qs) : 0,
                                    time: time !== '' ? Number(time) : 0
                                });
                            }
                        });
                        document.getElementById('sections').value = JSON.stringify(sections);
                    });
                }
                
                // Init subjects
                try {
                    let subjects = JSON.parse(document.getElementById('subjects_json').value || '[]');
                    subjects.forEach(s => window.addSubject(s.subject, s.questions, s.marks));
                } catch(e) {}
                if(document.querySelectorAll('.subject-row').length === 0) window.addSubject();
                
                // Init sections
                try {
                    let sections = JSON.parse(document.getElementById('sections').value || '[]');
                    sections.forEach(s => window.addSection(s.name, s.questions, s.time));
                } catch(e) {}
                if(document.querySelectorAll('.section-row').length === 0) window.addSection();
            }

            // --- Results Data UI Logic ---
            const pvmHidden = document.getElementById('percentile_vs_marks_json');
            if (pvmHidden) {
                const resultsForm = pvmHidden.closest('form');
                if (resultsForm) {
                    resultsForm.addEventListener('submit', function() {
                        let pvmData = {};
                        document.querySelectorAll('.pvm-year-section').forEach(section => {
                            let year = section.querySelector('.pvm-year-input').value.trim();
                            if (year) {
                                pvmData[year] = [];
                                section.querySelectorAll('.pvm-row').forEach(row => {
                                    let marks = row.querySelector('.pvm-marks').value;
                                    let perc = row.querySelector('.pvm-perc').value;
                                    if (marks !== '' || perc !== '') {
                                        pvmData[year].push({
                                            marks: marks !== '' ? Number(marks) : null,
                                            percentile: perc !== '' ? Number(perc) : null
                                        });
                                    }
                                });
                            }
                        });
                        pvmHidden.value = JSON.stringify(pvmData);
                    });
                }

                // Init data
                try {
                    let raw_pvm = pvmHidden.value;
                    // Fix htmlspecialchars escaping if necessary
                    if(raw_pvm.includes('&quot;')) {
                        raw_pvm = raw_pvm.replace(/&quot;/g, '"');
                    }
                    let data = JSON.parse(raw_pvm || '{}');
                    if (Object.keys(data).length > 0) {
                        for (let year in data) {
                            let sec = window.addPvmYear(year);
                            if (data[year] && data[year].length > 0) {
                                data[year].forEach(row => {
                                    window.addPvmRow(sec, row.marks, row.percentile);
                                });
                            } else {
                                // Add an empty row if none existed
                                window.addPvmRow(sec);
                            }
                        }
                    } else {
                        window.addPvmYear();
                    }
                } catch(e) {
                    window.addPvmYear();
                }
            }
        });

        window.addSubject = function(name='', qs='', marks='') {
            const container = document.getElementById('subjects_container');
            if(!container) return;
            const div = document.createElement('div');
            div.className = 'subject-row';
            div.style.cssText = 'display:flex; gap:10px; margin-bottom:10px; align-items:center;';
            div.innerHTML = `
                <input type="text" class="form-control sub-name" placeholder="Subject Name" value="${name}">
                <input type="number" class="form-control sub-qs" placeholder="Questions" value="${qs}" style="width:100px;">
                <input type="number" class="form-control sub-marks" placeholder="Marks" value="${marks}" style="width:100px;">
                <button type="button" onclick="this.parentElement.remove()" style="background:rgba(15,23,42,0.06); color:#0B2447; border:1px solid rgba(15,23,42,0.06); padding:10px; border-radius:4px; cursor:pointer;" title="Remove"><i class="ph ph-trash"></i></button>
            `;
            container.appendChild(div);
        };

        window.addSection = function(name='', qs='', time='') {
            const container = document.getElementById('sections_container');
            if(!container) return;
            const div = document.createElement('div');
            div.className = 'section-row';
            div.style.cssText = 'display:flex; gap:10px; margin-bottom:10px; align-items:center;';
            div.innerHTML = `
                <input type="text" class="form-control sec-name" placeholder="Section Name" value="${name}">
                <input type="number" class="form-control sec-qs" placeholder="Questions" value="${qs}" style="width:100px;">
                <input type="number" class="form-control sec-time" placeholder="Time (Mins)" value="${time}" style="width:120px;">
                <button type="button" onclick="this.parentElement.remove()" style="background:rgba(15,23,42,0.06); color:#0B2447; border:1px solid rgba(15,23,42,0.06); padding:10px; border-radius:4px; cursor:pointer;" title="Remove"><i class="ph ph-trash"></i></button>
            `;
            container.appendChild(div);
        };

        window.addSamplePaper = function() {
            const container = document.getElementById('sp_container');
            if(!container) return;
            const div = document.createElement('div');
            div.className = 'sp-row';
            div.style.cssText = 'display:flex; gap:10px; margin-bottom:10px; align-items:center; background: #fff; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px;';
            div.innerHTML = `
                <input type="text" name="new_sp_years[]" class="form-control" placeholder="Year (e.g. 2024)" style="width: 120px;" required>
                <input type="text" name="new_sp_subjects[]" class="form-control" placeholder="Subject" style="width: 150px;" required>
                <input type="text" name="new_sp_descriptions[]" class="form-control" placeholder="Description" style="flex:1;">
                <input type="file" name="new_sp_files[]" class="form-control" accept=".pdf,.doc,.docx" required style="width: 200px;">
                <button type="button" onclick="this.parentElement.remove()" style="background:rgba(15,23,42,0.06); color:#0B2447; border:1px solid rgba(15,23,42,0.06); padding:10px; border-radius:4px; cursor:pointer;" title="Remove"><i class="ph ph-trash"></i></button>
            `;
            container.appendChild(div);
        };

        window.addCutoffFile = function() {
            const container = document.getElementById('co_container');
            if(!container) return;
            const div = document.createElement('div');
            div.className = 'co-row';
            div.style.cssText = 'display:flex; gap:10px; margin-bottom:10px; align-items:center; background: #fff; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px;';
            div.innerHTML = `
                <input type="text" name="new_co_years[]" class="form-control" placeholder="Year (e.g. 2024)" style="width: 120px;" required>
                <input type="text" name="new_co_subjects[]" class="form-control" placeholder="Subject" style="width: 150px;" required>
                <input type="text" name="new_co_descriptions[]" class="form-control" placeholder="Description" style="flex:1;">
                <input type="file" name="new_co_files[]" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx" required style="width: 200px;">
                <button type="button" onclick="this.parentElement.remove()" style="background:rgba(15,23,42,0.06); color:#0B2447; border:1px solid rgba(15,23,42,0.06); padding:10px; border-radius:4px; cursor:pointer;" title="Remove"><i class="ph ph-trash"></i></button>
            `;
            container.appendChild(div);
        };

        window.addPvmYear = function(year='') {
            const container = document.getElementById('pvm_container');
            if(!container) return null;
            const sec = document.createElement('div');
            sec.className = 'pvm-year-section';
            sec.style.cssText = 'background: #fff; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 15px;';
            sec.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px; border-bottom: 1px solid #F8FAFC; padding-bottom: 10px;">
                    <input type="text" class="form-control pvm-year-input" placeholder="Year (e.g. 2023)" value="${year}" style="width: 150px; font-weight:bold;">
                    <button type="button" onclick="this.closest('.pvm-year-section').remove()" style="background:rgba(15,23,42,0.06); color:#0B2447; border:1px solid rgba(15,23,42,0.06); padding:6px 12px; border-radius:4px; cursor:pointer; font-size:0.85rem;"><i class="ph ph-trash"></i> Remove Year</button>
                </div>
                <div class="pvm-rows-container"></div>
                <button type="button" onclick="window.addPvmRow(this.closest('.pvm-year-section'))" style="background:rgba(11,36,71,0.04); color:#19376D; border:1px solid rgba(11,36,71,0.06); padding:4px 8px; border-radius:4px; cursor:pointer; font-size:0.85rem; margin-top:5px;">+ Add Row</button>
            `;
            container.appendChild(sec);
            if(!year) window.addPvmRow(sec);
            return sec;
        };

        window.addPvmRow = function(sectionElement, marks='', perc='') {
            const container = sectionElement.querySelector('.pvm-rows-container');
            const div = document.createElement('div');
            div.className = 'pvm-row';
            div.style.cssText = 'display:flex; gap:10px; margin-bottom:8px; align-items:center;';
            const mVal = marks !== null && marks !== undefined ? marks : '';
            const pVal = perc !== null && perc !== undefined ? perc : '';
            div.innerHTML = `
                <input type="number" class="form-control pvm-marks" placeholder="Marks" value="${mVal}" style="width:120px;" step="0.1">
                <input type="number" class="form-control pvm-perc" placeholder="Percentile" value="${pVal}" style="width:120px;" step="0.01">
                <button type="button" onclick="this.parentElement.remove()" style="background:#F8FAFC; color:rgba(15,23,42,0.45); border:1px solid rgba(15,23,42,0.15); padding:8px; border-radius:4px; cursor:pointer;"><i class="ph ph-x"></i></button>
            `;
            container.appendChild(div);
        };
    </script>
</body>
</html>
