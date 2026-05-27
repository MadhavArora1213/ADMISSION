<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;
if (!$exam_id) { header('Location: exams.php'); exit; }

$stmt = $pdo->prepare("SELECT name FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();
if (!$exam) { header('Location: exams.php'); exit; }

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $stmt = $pdo->prepare("INSERT INTO exam_dates (id, exam_id, event_name, event_date, is_tentative, year) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $exam_id, $_POST['event_name'], $_POST['event_date'], isset($_POST['is_tentative']) ? 1 : 0, $_POST['year']]);
    header("Location: exam_dates.php?exam_id=$exam_id&msg=added");
    exit;
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM exam_dates WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: exam_dates.php?exam_id=$exam_id&msg=deleted");
    exit;
}

$dates = $pdo->prepare("SELECT * FROM exam_dates WHERE exam_id = ? ORDER BY year DESC, event_date ASC");
$dates->execute([$exam_id]);
$datesList = $dates->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Dates | AdmissionSeason Admin</title>
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
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; padding-bottom: 60px; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 1000px; margin: 0 auto; width: 100%; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 12px; }
        .form-section { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); font-size: 0.95rem; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <main class="main-content" style="margin-left: 0;">
            <div class="content-area">
                <div class="page-header">
                    <h2><a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=basic" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> Exam Dates: <?php echo htmlspecialchars($exam['name']); ?></h2>
                </div>
                
                <div class="tabs-nav">
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=basic" class="tab-link">Basic Info</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=dates" class="tab-link">Important Dates</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=eligibility" class="tab-link">Eligibility & Pattern</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=links" class="tab-link">Fees & Links</a>
                    <a href="exam_dates.php?exam_id=<?php echo $exam_id; ?>" class="tab-link active">All Dates & Events</a>
                    <a href="exam_syllabus.php?exam_id=<?php echo $exam_id; ?>" class="tab-link">Syllabus</a>
                    <a href="exam_cutoffs.php?exam_id=<?php echo $exam_id; ?>" class="tab-link">Cutoffs</a>
                </div>

                <form action="" method="POST" class="form-section">
                    <input type="hidden" name="action" value="add">
                    <h3>Add New Event Date</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Event Name</label>
                            <input type="text" name="event_name" class="form-control" required placeholder="e.g. Registration Phase 1">
                        </div>
                        <div class="form-group">
                            <label>Event Date</label>
                            <input type="date" name="event_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Year</label>
                            <input type="number" name="year" class="form-control" required value="<?php echo date('Y'); ?>">
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:8px; padding-top:28px;">
                            <input type="checkbox" name="is_tentative" id="tentative">
                            <label for="tentative" style="margin:0;">Is Tentative?</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Event</button>
                </form>

                <div class="form-section">
                    <h3>Events List</h3>
                    <table>
                        <tr>
                            <th>Year</th>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Tentative</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach($datesList as $d): ?>
                        <tr>
                            <td><?php echo $d['year']; ?></td>
                            <td><?php echo htmlspecialchars($d['event_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($d['event_date'])); ?></td>
                            <td><?php echo $d['is_tentative'] ? 'Yes' : 'No'; ?></td>
                            <td><a href="?exam_id=<?php echo $exam_id; ?>&action=delete&id=<?php echo $d['id']; ?>" class="action-btn"><i class="ph ph-trash"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
