<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;
if (!$exam_id) { header('Location: exams.php'); exit; }

$stmt = $pdo->prepare("SELECT exam_name FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();
if (!$exam) { header('Location: exams.php'); exit; }

// Handle Save Year Profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_year') {
    $year = $_POST['year'];
    
    // Check if year profile exists for this exam
    $stmtCheck = $pdo->prepare("SELECT id FROM exam_dates WHERE exam_id = ? AND year = ?");
    $stmtCheck->execute([$exam_id, $year]);
    $existing = $stmtCheck->fetch();
    
    $is_tentative = isset($_POST['is_tentative']) ? 1 : 0;
    
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE exam_dates SET 
            event_name = ?, event_date = ?, application_start = ?, application_end = ?, exam_date = ?, 
            result_date = ?, admit_card_date = ?, counselling_start = ?, 
            answer_key_date = ?, is_tentative = ? 
            WHERE id = ?");
        $stmt->execute([
            $_POST['event_name'] ?: null, $_POST['event_date'] ?: null,
            $_POST['application_start'] ?: null, $_POST['application_end'] ?: null, 
            $_POST['exam_date'] ?: null, $_POST['result_date'] ?: null, 
            $_POST['admit_card_date'] ?: null, $_POST['counselling_start'] ?: null, 
            $_POST['answer_key_date'] ?: null, $is_tentative, $existing['id']
        ]);
    } else {
        $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        $stmt = $pdo->prepare("INSERT INTO exam_dates 
            (id, exam_id, year, event_name, event_date, application_start, application_end, exam_date, result_date, admit_card_date, counselling_start, answer_key_date, is_tentative) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id, $exam_id, $year,
            $_POST['event_name'] ?: null, $_POST['event_date'] ?: null,
            $_POST['application_start'] ?: null, $_POST['application_end'] ?: null, 
            $_POST['exam_date'] ?: null, $_POST['result_date'] ?: null, 
            $_POST['admit_card_date'] ?: null, $_POST['counselling_start'] ?: null, 
            $_POST['answer_key_date'] ?: null, $is_tentative
        ]);
    }
    header("Location: exam_dates.php?exam_id=$exam_id&msg=saved");
    exit;
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM exam_dates WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: exam_dates.php?exam_id=$exam_id&msg=deleted");
    exit;
}

$dates = $pdo->prepare("SELECT * FROM exam_dates WHERE exam_id = ? ORDER BY year DESC");
$dates->execute([$exam_id]);
$datesList = $dates->fetchAll();

// Determine max year to default to
$defaultYear = date('Y');
if (!empty($datesList)) {
    $defaultYear = $datesList[0]['year'] + 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Dates | AdmissionSeason Admin</title>
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
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); font-size: 0.95rem; }
        .form-control { width: 100%; min-width: 0; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; min-width: 550px; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(15,23,42,0.06); color: #0B2447; border: 1px solid rgba(15,23,42,0.06); text-decoration: none; }
        .action-btn.edit { background: rgba(11,36,71,0.06); color: #19376D; border-color: rgba(11,36,71,0.06); margin-right: 8px; }
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; -webkit-overflow-scrolling: touch; scrollbar-width: thin; flex-wrap: nowrap; }
        .tabs-nav::-webkit-scrollbar { height: 5px; }
        .tabs-nav::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 3px; }
        .tabs-nav::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 3px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; flex-shrink: 0; font-size: 0.88rem; text-decoration: none; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        .msg-alert { padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 24px; border: 1px solid rgba(11,36,71,0.04); }
        .btn-primary { background: var(--primary); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap; box-sizing: border-box; }
        .btn-primary:hover { background: #0B2447; }
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
            .page-header h2 { font-size: 1.3rem; gap: 8px; }
            .form-section { padding: 16px; }
            .form-grid { grid-template-columns: 1fr; gap: 12px; }
            .form-group { margin-bottom: 14px; }
            .form-group label { font-size: 0.85rem; margin-bottom: 6px; }
            .form-control { padding: 10px 12px; font-size: 0.9rem; }
            .tabs-nav { gap: 4px; margin-bottom: 16px; }
            .tab-link { padding: 6px 12px; font-size: 0.78rem; }
            .btn-primary { width: 100%; text-align: center; justify-content: center; }
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
                    <h2><a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=basic" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> All Dates: <?php echo htmlspecialchars($exam['exam_name']); ?></h2>
                </div>
                
                <div class="tabs-nav">
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=basic" class="tab-link">Basic Info</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=eligibility" class="tab-link">Eligibility & Pattern</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=links" class="tab-link">Fees & Links</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=resources" class="tab-link">Resources</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=results_data" class="tab-link">Results Data</a>
                    <a href="exam_dates.php?exam_id=<?php echo $exam_id; ?>" class="tab-link active">All Dates</a>
                    <a href="exam_syllabus.php?exam_id=<?php echo $exam_id; ?>" class="tab-link">Syllabus</a>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> Year dates saved successfully!</div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> Year dates deleted successfully!</div>
                <?php endif; ?>

                <form action="" method="POST" class="form-section" id="dateForm">
                    <input type="hidden" name="action" value="save_year">
                    <h3>Add/Update Year Profile</h3>
                    <p style="color: var(--text-muted); margin-bottom: 24px; font-size: 0.9rem;">Select a year to set its dates. If the year already exists, it will be updated.</p>
                    
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Year</label>
                            <input type="number" name="year" id="yearInput" class="form-control" required value="<?php echo $defaultYear; ?>" style="max-width: 200px;">
                        </div>
                        <div class="form-group">
                            <label>Event Name (e.g. Session 1)</label>
                            <input type="text" name="event_name" id="ev_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Event Date (Specific date if applicable)</label>
                            <input type="date" name="event_date" id="ev_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Application Start Date</label>
                            <input type="date" name="application_start" id="app_start" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Application End Date</label>
                            <input type="date" name="application_end" id="app_end" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Exam Date</label>
                            <input type="date" name="exam_date" id="exam_d" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Admit Card Release Date</label>
                            <input type="date" name="admit_card_date" id="admit_d" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Answer Key Release Date</label>
                            <input type="date" name="answer_key_date" id="ans_key_d" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Result Date</label>
                            <input type="date" name="result_date" id="res_d" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Counselling Start Date</label>
                            <input type="date" name="counselling_start" id="counselling_d" class="form-control">
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:8px; padding-top:28px;">
                            <input type="checkbox" name="is_tentative" id="tentative">
                            <label for="tentative" style="margin:0;">Are these dates Tentative?</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:16px;">Save Year Dates</button>
                </form>

                <div class="form-section">
                    <h3>Existing Year Profiles</h3>
                    <?php if (empty($datesList)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding: 20px;">No dates added yet.</p>
                    <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <tr>
                                <th>Year</th>
                                <th>Event Name</th>
                                <th>Event Date</th>
                                <th>App Start</th>
                                <th>Exam Date</th>
                                <th>Tentative</th>
                                <th>Action</th>
                            </tr>
                            <?php foreach($datesList as $d): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--primary);"><?php echo $d['year']; ?></td>
                                <td><?php echo htmlspecialchars($d['event_name'] ?: '-'); ?></td>
                                <td><?php echo $d['event_date'] ? date('M d, Y', strtotime($d['event_date'])) : '-'; ?></td>
                                <td><?php echo $d['application_start'] ? date('M d, Y', strtotime($d['application_start'])) : '-'; ?></td>
                                <td><?php echo $d['exam_date'] ? date('M d, Y', strtotime($d['exam_date'])) : '-'; ?></td>
                                <td><?php echo $d['is_tentative'] ? '<span style="color:#19376D;">Yes</span>' : 'No'; ?></td>
                                <td>
                                    <button type="button" class="action-btn edit" onclick='editYear(<?php echo json_encode($d); ?>)' title="Edit"><i class="ph ph-pencil-simple"></i></button>
                                    <a href="?exam_id=<?php echo $exam_id; ?>&action=delete&id=<?php echo $d['id']; ?>" class="action-btn" onclick="return confirm('Delete this year profile?');" title="Delete"><i class="ph ph-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <?php endif; ?>
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
    <script>
    function editYear(data) {
        document.getElementById('yearInput').value = data.year || '';
        document.getElementById('ev_name').value = data.event_name || '';
        document.getElementById('ev_date').value = data.event_date || '';
        document.getElementById('app_start').value = data.application_start || '';
        document.getElementById('app_end').value = data.application_end || '';
        document.getElementById('exam_d').value = data.exam_date || '';
        document.getElementById('admit_d').value = data.admit_card_date || '';
        document.getElementById('ans_key_d').value = data.answer_key_date || '';
        document.getElementById('res_d').value = data.result_date || '';
        document.getElementById('counselling_d').value = data.counselling_start || '';
        document.getElementById('tentative').checked = data.is_tentative == 1;
        
        window.scrollTo({top: document.getElementById('dateForm').offsetTop - 100, behavior: 'smooth'});
    }
    </script>
</body>
</html>
