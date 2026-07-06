<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;
if (!$exam_id) { header('Location: exams.php'); exit; }

$stmt = $pdo->prepare("SELECT exam_name as name FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();
if (!$exam) { header('Location: exams.php'); exit; }

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    
    $chapter_pdf_url = null;
    if (isset($_FILES['chapter_pdf_file']) && $_FILES['chapter_pdf_file']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_extension = strtolower(pathinfo($_FILES['chapter_pdf_file']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid('syl_') . '.' . $file_extension;
        if (move_uploaded_file($_FILES['chapter_pdf_file']['tmp_name'], $target_dir . $new_filename)) {
            $chapter_pdf_url = "uploads/" . $new_filename;
            require_once __DIR__ . '/upload_sync.php';
            sync_to_github('uploads/' . $new_filename);
        }
    }

    $stmt = $pdo->prepare("INSERT INTO exam_syllabus (id, exam_id, subject, topic, subtopics, weightage_pct, chapter_pdf_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $exam_id, $_POST['subject'], $_POST['topic'], $_POST['subtopics'], $_POST['weightage_pct'] ?: null, $chapter_pdf_url]);
    header("Location: exam_syllabus.php?exam_id=$exam_id&msg=added");
    exit;
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM exam_syllabus WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: exam_syllabus.php?exam_id=$exam_id&msg=deleted");
    exit;
}

$syl = $pdo->prepare("SELECT * FROM exam_syllabus WHERE exam_id = ? ORDER BY subject ASC, topic ASC");
$syl->execute([$exam_id]);
$list = $syl->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Syllabus | AdmissionSeason Admin</title>
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
        .main-content { flex: 1; display: flex; flex-direction: column; padding-bottom: 60px; }
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
        table { width: 100%; border-collapse: collapse; margin-top: 16px; min-width: 450px; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(15,23,42,0.06); color: #0B2447; border: 1px solid rgba(15,23,42,0.06); text-decoration: none; }
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; -webkit-overflow-scrolling: touch; scrollbar-width: thin; flex-wrap: nowrap; }
        .tabs-nav::-webkit-scrollbar { height: 5px; }
        .tabs-nav::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 3px; }
        .tabs-nav::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 3px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; flex-shrink: 0; font-size: 0.88rem; text-decoration: none; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        .btn-primary { background: var(--primary); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap; box-sizing: border-box; }
        .btn-primary:hover { background: #0B2447; }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }
        .subtopic-row { flex-wrap: wrap; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
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
            .subtopic-row { flex-direction: column; }
            .subtopic-row .form-control { width: 100% !important; }
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
            <div class="content-area">
                <div class="page-header">
                    <h2><a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=basic" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> Syllabus: <?php echo htmlspecialchars($exam['name']); ?></h2>
                </div>
                
                <div class="tabs-nav">
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=basic" class="tab-link">Basic Info</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=dates" class="tab-link">Important Dates</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=eligibility" class="tab-link">Eligibility & Pattern</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=links" class="tab-link">Fees & Links</a>
                    <a href="exam_dates.php?exam_id=<?php echo $exam_id; ?>" class="tab-link">All Dates & Events</a>
                    <a href="exam_syllabus.php?exam_id=<?php echo $exam_id; ?>" class="tab-link active">Syllabus</a>
                    <a href="exam_cutoffs.php?exam_id=<?php echo $exam_id; ?>" class="tab-link">Cutoffs</a>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" class="form-section" id="syllabusForm">
                    <input type="hidden" name="action" value="add">
                    <h3>Add Syllabus Topic</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" class="form-control" required placeholder="e.g. Physics">
                        </div>
                        <div class="form-group">
                            <label>Topic</label>
                            <input type="text" name="topic" class="form-control" required placeholder="e.g. Mechanics">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Subtopics</label>
                            <div id="subtopics_container" style="margin-bottom:10px;">
                                <div style="display:flex; gap:10px; margin-bottom:8px;">
                                    <input type="text" class="form-control subtopic-input" placeholder="Subtopic Name">
                                    <button type="button" onclick="this.parentElement.remove()" style="background:rgba(15,23,42,0.06); color:#0B2447; border:1px solid rgba(15,23,42,0.06); padding:10px; border-radius:4px; cursor:pointer;" title="Remove"><i class="ph ph-trash"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm" onclick="addSubtopic()" style="background:rgba(15,23,42,0.08); border:1px solid rgba(15,23,42,0.15); padding: 5px 10px; border-radius: 4px; cursor: pointer;">+ Add Subtopic</button>
                            <input type="hidden" name="subtopics" id="subtopics_json">
                        </div>
                        <div class="form-group">
                            <label>Weightage (%)</label>
                            <input type="number" step="0.1" name="weightage_pct" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Chapter PDF File</label>
                            <input type="file" name="chapter_pdf_file" class="form-control" accept=".pdf,.doc,.docx">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:16px;">Add Topic</button>
                </form>

                <div class="form-section">
                    <h3>Syllabus List</h3>
                    <table>
                        <tr>
                            <th>Subject</th>
                            <th>Topic</th>
                            <th>Weightage</th>
                            <th>PDF</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach($list as $d): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($d['subject']); ?></td>
                            <td><?php echo htmlspecialchars($d['topic']); ?></td>
                            <td><?php echo $d['weightage_pct'] ? $d['weightage_pct'].'%' : '-'; ?></td>
                            <td><?php echo $d['chapter_pdf_url'] ? '<a href="../'.htmlspecialchars($d['chapter_pdf_url']).'" target="_blank"><i class="ph ph-file-pdf"></i> View</a>' : '-'; ?></td>
                            <td><a href="?exam_id=<?php echo $exam_id; ?>&action=delete&id=<?php echo $d['id']; ?>" class="action-btn"><i class="ph ph-trash"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </main>
    </div>
<script>
document.getElementById('sidebar-toggle') && document.getElementById('sidebar-toggle').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('open');
    document.getElementById('sidebar-overlay').classList.toggle('show');
});
document.getElementById('sidebar-overlay').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.remove('open');
    this.classList.remove('show');
});
</script>
    <script>
        function addSubtopic() {
            const container = document.getElementById('subtopics_container');
            const div = document.createElement('div');
            div.style.cssText = 'display:flex; gap:10px; margin-bottom:8px;';
            div.innerHTML = `
                <input type="text" class="form-control subtopic-input" placeholder="Subtopic Name">
                <button type="button" onclick="this.parentElement.remove()" style="background:rgba(15,23,42,0.06); color:#0B2447; border:1px solid rgba(15,23,42,0.06); padding:10px; border-radius:4px; cursor:pointer;" title="Remove"><i class="ph ph-trash"></i></button>
            `;
            container.appendChild(div);
        }

        const syllabusForm = document.getElementById('syllabusForm');
        if (syllabusForm) {
            syllabusForm.addEventListener('submit', function(e) {
                let subs = [];
                document.querySelectorAll('.subtopic-input').forEach(inp => {
                    if(inp.value.trim() !== '') {
                        subs.push(inp.value.trim());
                    }
                });
                document.getElementById('subtopics_json').value = JSON.stringify(subs);
            });
        }
    </script>
</body>
</html>
