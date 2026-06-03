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

$colleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name ASC")->fetchAll();
$courses = $pdo->query("SELECT id, course_name as name FROM courses ORDER BY course_name ASC")->fetchAll();

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $stmt = $pdo->prepare("INSERT INTO exam_cutoffs (id, exam_id, college_id, course_id, year, category, opening_rank, closing_rank, round) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $college_id = !empty($_POST['college_id']) ? $_POST['college_id'] : null;
    $course_id = !empty($_POST['course_id']) ? $_POST['course_id'] : null;
    $stmt->execute([$id, $exam_id, $college_id, $course_id, $_POST['year'], $_POST['category'], $_POST['opening_rank'] ?: null, $_POST['closing_rank'] ?: null, $_POST['round'] ?: null]);
    header("Location: exam_cutoffs.php?exam_id=$exam_id&msg=added");
    exit;
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM exam_cutoffs WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: exam_cutoffs.php?exam_id=$exam_id&msg=deleted");
    exit;
}

$cutoffsQ = $pdo->prepare("
    SELECT ec.*, c.name as college_name, cr.course_name as course_name 
    FROM exam_cutoffs ec
    LEFT JOIN colleges c ON ec.college_id = c.id
    LEFT JOIN courses cr ON ec.course_id = cr.id
    WHERE ec.exam_id = ?
    ORDER BY ec.year DESC, c.name ASC
");
$cutoffsQ->execute([$exam_id]);
$list = $cutoffsQ->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Cutoffs | AdmissionSeason Admin</title>
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
        .main-content { flex: 1; display: flex; flex-direction: column; padding-bottom: 60px; }
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
        <main class="main-content">
            <div class="content-area">
                <div class="page-header">
                    <h2><a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=basic" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> Cutoffs: <?php echo htmlspecialchars($exam['name']); ?></h2>
                </div>
                
                <div class="tabs-nav">
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=basic" class="tab-link">Basic Info</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=dates" class="tab-link">Important Dates</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=eligibility" class="tab-link">Eligibility & Pattern</a>
                    <a href="exam_form.php?id=<?php echo $exam_id; ?>&tab=links" class="tab-link">Fees & Links</a>
                    <a href="exam_dates.php?exam_id=<?php echo $exam_id; ?>" class="tab-link">All Dates & Events</a>
                    <a href="exam_syllabus.php?exam_id=<?php echo $exam_id; ?>" class="tab-link">Syllabus</a>
                    <a href="exam_cutoffs.php?exam_id=<?php echo $exam_id; ?>" class="tab-link active">Cutoffs</a>
                </div>

                <form action="" method="POST" class="form-section">
                    <input type="hidden" name="action" value="add">
                    <h3>Add Cutoff Data</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>College (Optional)</label>
                            <select name="college_id" class="form-control">
                                <option value="">-- No Specific College (General Exam Cutoff) --</option>
                                <?php foreach($colleges as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Course (Optional)</label>
                            <select name="course_id" class="form-control">
                                <option value="">-- No Specific Course --</option>
                                <?php foreach($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Year</label>
                            <input type="number" name="year" class="form-control" required value="<?php echo date('Y'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" class="form-control" required>
                                <option value="General">General</option>
                                <option value="OBC">OBC</option>
                                <option value="SC">SC</option>
                                <option value="ST">ST</option>
                                <option value="EWS">EWS</option>
                                <option value="PWD">PWD</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Round</label>
                            <input type="number" name="round" class="form-control" placeholder="e.g. 1">
                        </div>
                        <div class="form-group">
                            <label>Opening Rank</label>
                            <input type="number" name="opening_rank" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Closing Rank</label>
                            <input type="number" name="closing_rank" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:16px;">Add Cutoff</button>
                </form>

                <div class="form-section">
                    <h3>Cutoffs List</h3>
                    <div style="overflow-x:auto;">
                        <table>
                            <tr>
                                <th>Year</th>
                                <th>College</th>
                                <th>Course</th>
                                <th>Category</th>
                                <th>Ranks (Op-Cl)</th>
                                <th>Action</th>
                            </tr>
                            <?php foreach($list as $d): ?>
                            <tr>
                                <td><?php echo $d['year']; ?></td>
                                <td><?php echo $d['college_name'] ? htmlspecialchars($d['college_name']) : '<span style="color:#94a3b8; font-style:italic;">General Exam</span>'; ?></td>
                                <td><?php echo $d['course_name'] ? htmlspecialchars($d['course_name']) : '<span style="color:#94a3b8; font-style:italic;">Any / All</span>'; ?></td>
                                <td><?php echo $d['category']; ?> <?php if($d['round']) echo "(R{$d['round']})"; ?></td>
                                <td><?php echo $d['opening_rank'].' - '.$d['closing_rank']; ?></td>
                                <td><a href="?exam_id=<?php echo $exam_id; ?>&action=delete&id=<?php echo $d['id']; ?>" class="action-btn"><i class="ph ph-trash"></i></a></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
