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
    $slug = !empty($_POST['slug']) ? $_POST['slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])));
    
    $slugCheckQ = "SELECT id FROM exams WHERE slug = :slug";
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
                'name' => $_POST['name'],
                'slug' => $slug,
                'conducting_body' => $_POST['conducting_body'],
                'level' => $_POST['level'],
                'exam_mode' => $_POST['exam_mode'] ?: null,
                'frequency' => $_POST['frequency'] ?: null,
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
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'dates') {
    try {
        $data = [
            'id' => $id,
            'application_start' => !empty($_POST['application_start']) ? $_POST['application_start'] : null,
            'application_end' => !empty($_POST['application_end']) ? $_POST['application_end'] : null,
            'exam_date' => !empty($_POST['exam_date']) ? $_POST['exam_date'] : null,
            'result_date' => !empty($_POST['result_date']) ? $_POST['result_date'] : null,
            'admit_card_date' => !empty($_POST['admit_card_date']) ? $_POST['admit_card_date'] : null,
            'counselling_start' => !empty($_POST['counselling_start']) ? $_POST['counselling_start'] : null,
            'answer_key_date' => !empty($_POST['answer_key_date']) ? $_POST['answer_key_date'] : null,
            'is_tentative' => isset($_POST['is_tentative']) ? 1 : 0
        ];
        $fields = [];
        foreach($data as $key => $val) { if($key=='id') continue; $fields[] = "$key = :$key"; }
        $stmt = $pdo->prepare("UPDATE exams SET " . implode(', ', $fields) . " WHERE id = :id");
        $stmt->execute($data);
        header("Location: exam_form.php?id=$id&tab=dates&msg=saved");
        exit;
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $current_tab == 'eligibility') {
    try {
        $data = [
            'id' => $id,
            'age_min' => $_POST['age_min'] ?: null,
            'age_max' => $_POST['age_max'] ?: null,
            'min_percentage_required' => $_POST['min_percentage_required'] ?: null,
            'qualifying_exam' => $_POST['qualifying_exam'],
            'total_marks' => $_POST['total_marks'] ?: null,
            'total_questions' => $_POST['total_questions'] ?: null,
            'duration_minutes' => $_POST['duration_minutes'] ?: null,
            'subjects_json' => $_POST['subjects_json'],
            'marking_scheme' => $_POST['marking_scheme']
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
        $data = [
            'id' => $id,
            'application_fee_general' => $_POST['application_fee_general'] ?: null,
            'application_fee_obc' => $_POST['application_fee_obc'] ?: null,
            'application_fee_sc_st' => $_POST['application_fee_sc_st'] ?: null,
            'application_url' => $_POST['application_url'],
            'official_website' => $_POST['official_website'],
            'syllabus_pdf_url' => $_POST['syllabus_pdf_url'],
            'result_url' => $_POST['result_url'],
            'scorecard_url' => $_POST['scorecard_url'],
            'counselling_authority' => $_POST['counselling_authority'],
            'counselling_rounds' => $_POST['counselling_rounds'] ?: null,
            'merit_list_url' => $_POST['merit_list_url']
        ];
        $fields = [];
        foreach($data as $key => $val) { if($key=='id') continue; $fields[] = "$key = :$key"; }
        $stmt = $pdo->prepare("UPDATE exams SET " . implode(', ', $fields) . " WHERE id = :id");
        $stmt->execute($data);
        header("Location: exam_form.php?id=$id&tab=links&msg=saved");
        exit;
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
}

$exam = [];
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$exam) {
        header('Location: exams.php');
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
    <title><?php echo $is_edit ? 'Edit Exam' : 'Add New Exam'; ?> | AdmissionSeason Admin</title>
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
        .msg-alert { padding: 16px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 24px; border: 1px solid #bbf7d0; }
        
        .form-actions { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
        
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
                        <a href="exams.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                        <?php echo $is_edit ? 'Edit Exam: ' . htmlspecialchars($exam['name']) : 'Add New Exam'; ?>
                    </h2>
                </div>

                <?php if($is_edit): ?>
                <div class="tabs-nav">
                    <a href="?id=<?php echo $id; ?>&tab=basic" class="tab-link <?php echo $current_tab=='basic'?'active':''; ?>">Basic Info</a>
                    <a href="?id=<?php echo $id; ?>&tab=dates" class="tab-link <?php echo $current_tab=='dates'?'active':''; ?>">Important Dates</a>
                    <a href="?id=<?php echo $id; ?>&tab=eligibility" class="tab-link <?php echo $current_tab=='eligibility'?'active':''; ?>">Eligibility & Pattern</a>
                    <a href="?id=<?php echo $id; ?>&tab=links" class="tab-link <?php echo $current_tab=='links'?'active':''; ?>">Fees & Links</a>
                    
                    <a href="exam_dates.php?exam_id=<?php echo $id; ?>" class="tab-link">All Dates & Events</a>
                    <a href="exam_syllabus.php?exam_id=<?php echo $id; ?>" class="tab-link">Syllabus</a>
                    <a href="exam_cutoffs.php?exam_id=<?php echo $id; ?>" class="tab-link">Cutoffs</a>
                </div>
                <?php else: ?>
                <div class="tabs-nav">
                    <span class="tab-link active">Basic Info</span>
                    <span class="tab-link disabled">Important Dates</span>
                    <span class="tab-link disabled">Eligibility & Pattern</span>
                    <span class="tab-link disabled">Fees & Links</span>
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
                        <h3><i class="ph ph-exam"></i> Basic Details</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Exam Name *</label>
                                <input type="text" name="name" class="form-control" required value="<?php echo getValue($exam, 'name'); ?>">
                            </div>
                            <div class="form-group">
                                <label>URL Slug (Leave blank to auto-generate)</label>
                                <input type="text" name="slug" class="form-control" value="<?php echo getValue($exam, 'slug'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Conducting Body</label>
                                <input type="text" name="conducting_body" class="form-control" value="<?php echo getValue($exam, 'conducting_body'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Exam Level *</label>
                                <select name="level" class="form-control" required>
                                    <option value="National" <?php echo getValue($exam, 'level') == 'National' ? 'selected' : ''; ?>>National</option>
                                    <option value="State" <?php echo getValue($exam, 'level') == 'State' ? 'selected' : ''; ?>>State</option>
                                    <option value="University" <?php echo getValue($exam, 'level') == 'University' ? 'selected' : ''; ?>>University</option>
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
                                <select name="frequency" class="form-control">
                                    <option value="">Select</option>
                                    <option value="annual" <?php echo getValue($exam, 'frequency') == 'annual' ? 'selected' : ''; ?>>Annual</option>
                                    <option value="biannual" <?php echo getValue($exam, 'frequency') == 'biannual' ? 'selected' : ''; ?>>Biannual</option>
                                    <option value="monthly" <?php echo getValue($exam, 'frequency') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" <?php echo getValue($exam, 'status') == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="upcoming" <?php echo getValue($exam, 'status') == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                    <option value="completed" <?php echo getValue($exam, 'status') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
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

                <?php elseif($current_tab == 'dates'): ?>
                <form action="" method="POST">
                    <div class="form-section">
                        <h3><i class="ph ph-calendar"></i> Important Dates</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Application Start Date</label>
                                <input type="date" name="application_start" class="form-control" value="<?php echo getValue($exam, 'application_start'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Application End Date</label>
                                <input type="date" name="application_end" class="form-control" value="<?php echo getValue($exam, 'application_end'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Admit Card Release Date</label>
                                <input type="date" name="admit_card_date" class="form-control" value="<?php echo getValue($exam, 'admit_card_date'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Exam Date</label>
                                <input type="date" name="exam_date" class="form-control" value="<?php echo getValue($exam, 'exam_date'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Answer Key Release Date</label>
                                <input type="date" name="answer_key_date" class="form-control" value="<?php echo getValue($exam, 'answer_key_date'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Result Date</label>
                                <input type="date" name="result_date" class="form-control" value="<?php echo getValue($exam, 'result_date'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Counselling Start Date</label>
                                <input type="date" name="counselling_start" class="form-control" value="<?php echo getValue($exam, 'counselling_start'); ?>">
                            </div>
                            
                            <div class="form-group checkbox-group" style="grid-column: 1 / -1;">
                                <input type="checkbox" id="is_tentative" name="is_tentative" <?php echo !empty($exam['is_tentative']) ? 'checked' : ''; ?>>
                                <label for="is_tentative">Are these dates Tentative?</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Dates</button>
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
                                <label>Subjects (JSON Array)</label>
                                <textarea name="subjects_json" class="form-control" rows="3" placeholder='["Physics", "Chemistry", "Maths"]'><?php echo getValue($exam, 'subjects_json'); ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Marking Scheme (JSON Object)</label>
                                <textarea name="marking_scheme" class="form-control" rows="3" placeholder='{"correct": 4, "incorrect": -1}'><?php echo getValue($exam, 'marking_scheme'); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Eligibility</button>
                    </div>
                </form>

                <?php elseif($current_tab == 'links'): ?>
                <form action="" method="POST">
                    <div class="form-section">
                        <h3><i class="ph ph-currency-inr"></i> Fees & Official Links</h3>
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
                            
                            <div class="form-group full">
                                <label>Official Website</label>
                                <input type="url" name="official_website" class="form-control" value="<?php echo getValue($exam, 'official_website'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Application URL</label>
                                <input type="url" name="application_url" class="form-control" value="<?php echo getValue($exam, 'application_url'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Syllabus PDF URL</label>
                                <input type="url" name="syllabus_pdf_url" class="form-control" value="<?php echo getValue($exam, 'syllabus_pdf_url'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Result URL</label>
                                <input type="url" name="result_url" class="form-control" value="<?php echo getValue($exam, 'result_url'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Scorecard URL</label>
                                <input type="url" name="scorecard_url" class="form-control" value="<?php echo getValue($exam, 'scorecard_url'); ?>">
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
                                <label>Merit List URL</label>
                                <input type="url" name="merit_list_url" class="form-control" value="<?php echo getValue($exam, 'merit_list_url'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Fees & Links</button>
                    </div>
                </form>
                <?php endif; ?>

            </div>
        </main>
    </div>

</body>
</html>
