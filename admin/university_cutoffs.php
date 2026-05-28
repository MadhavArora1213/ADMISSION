<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

$university_id = isset($_GET['university_id']) ? $_GET['university_id'] : null;
if (!$university_id) { header('Location: universities.php'); exit; }

$stmt = $pdo->prepare("SELECT id, name FROM universities WHERE id = ?");
$stmt->execute([$university_id]);
$university = $stmt->fetch();
if (!$university) { header('Location: universities.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO university_cutoffs (id, university_id, exam_id, course_id, cutoff_year, category, round_number, opening_rank, closing_rank) 
                VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $university_id,
                $_POST['exam_id'],
                $_POST['course_id'],
                $_POST['cutoff_year'],
                $_POST['category'],
                $_POST['round_number'] ?: null,
                $_POST['opening_rank'] ?: null,
                $_POST['closing_rank'] ?: null
            ]);
            header("Location: university_cutoffs.php?university_id=$university_id&msg=added");
            exit;
        } catch (Exception $e) {
            $error = "Error adding cutoff: " . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM university_cutoffs WHERE id = ? AND university_id = ?");
        $stmt->execute([$_POST['c_id'], $university_id]);
        header("Location: university_cutoffs.php?university_id=$university_id&msg=deleted");
        exit;
    }
}

// Fetch dropdown data
$all_exams = $pdo->query("SELECT id, name FROM exams ORDER BY name ASC")->fetchAll();
// Fetch courses specifically linked to this university
$university_courses_list = $pdo->prepare("
    SELECT cc.course_id, c.name as course_name 
    FROM university_courses cc 
    JOIN courses c ON cc.course_id = c.id 
    WHERE cc.university_id = ?
    ORDER BY c.name ASC
");
$university_courses_list->execute([$university_id]);
$my_courses = $university_courses_list->fetchAll();

// Fetch cutoffs
$stmt = $pdo->prepare("
    SELECT cu.*, e.name as exam_name, c.name as course_name 
    FROM university_cutoffs cu
    JOIN exams e ON cu.exam_id = e.id
    JOIN courses c ON cu.course_id = c.id
    WHERE cu.university_id = ?
    ORDER BY cu.cutoff_year DESC, e.name ASC
");
$stmt->execute([$university_id]);
$cutoffs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cutoffs</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .user-profile { display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .content-area { padding: 32px; max-width: 1200px; margin: 0 auto; width: 100%; }
        .page-header { margin-bottom: 24px; }
        .page-header h2 { font-size: 1.8rem; font-weight: 700; display:flex; align-items:center; gap: 12px; }
        
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; }
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
                    <h2><a href="universities.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> Edit University: <?php echo htmlspecialchars($university['name']); ?></h2>
                </div>

                <div class="tabs-nav">
                    <a href="university_form.php?id=<?php echo $university_id; ?>&tab=identity" class="tab-link">Identity & Contact</a>
                    <a href="university_form.php?id=<?php echo $university_id; ?>&tab=about" class="tab-link">About & Amenities</a>
                    <a href="university_form.php?id=<?php echo $university_id; ?>&tab=seo" class="tab-link">SEO & Publish</a>
                    <a href="university_courses.php?university_id=<?php echo $university_id; ?>" class="tab-link">Courses & Fees</a>
                    <a href="university_placements.php?university_id=<?php echo $university_id; ?>" class="tab-link">Placements</a>
                    <a href="university_cutoffs.php?university_id=<?php echo $university_id; ?>" class="tab-link active">Cutoffs</a>
                    <a href="university_media.php?university_id=<?php echo $university_id; ?>" class="tab-link">Media & Gallery</a>
                    <a href="university_faqs.php?university_id=<?php echo $university_id; ?>" class="tab-link">FAQs</a>
                    <a href="university_faculty.php?university_id=<?php echo $university_id; ?>" class="tab-link">Faculty</a>
                    <a href="university_scholarships.php?university_id=<?php echo $university_id; ?>" class="tab-link">Scholarships</a>
                </div>

                <?php if(isset($_GET['msg'])): ?>
                    <div style="padding: 16px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 24px; border: 1px solid #bbf7d0;">Action completed successfully!</div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div style="padding: 16px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 24px; border: 1px solid #fecaca;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="panel">
                    <h3><i class="ph ph-plus-circle"></i> Add Cutoff</h3>
                    <form action="" method="POST" style="margin-top:16px;">
                        <input type="hidden" name="action" value="add">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Exam</label>
                                <select name="exam_id" class="form-control" required>
                                    <option value="">Select Exam</option>
                                    <?php foreach($all_exams as $e): ?>
                                        <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Course</label>
                                <select name="course_id" class="form-control" required>
                                    <option value="">Select Course</option>
                                    <?php foreach($my_courses as $c): ?>
                                        <option value="<?php echo $c['course_id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Year</label><input type="number" name="cutoff_year" class="form-control" required></div>
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category" class="form-control" required>
                                    <option value="General">General</option><option value="OBC">OBC</option>
                                    <option value="SC">SC</option><option value="ST">ST</option>
                                    <option value="EWS">EWS</option><option value="PWD">PWD</option>
                                </select>
                            </div>
                            <div class="form-group"><label>Round</label><input type="number" name="round_number" class="form-control"></div>
                            <div class="form-group"><label>Opening Rank</label><input type="number" name="opening_rank" class="form-control"></div>
                            <div class="form-group"><label>Closing Rank</label><input type="number" name="closing_rank" class="form-control"></div>
                        </div>
                        <div style="text-align: right; margin-top:16px;"><button type="submit" class="btn btn-primary">Add Cutoff</button></div>
                    </form>
                </div>

                <div class="panel">
                    <h3><i class="ph ph-list"></i> Cutoff Records</h3>
                    <?php if(empty($cutoffs)): ?>
                        <p style="color:var(--text-muted); margin-top:16px;">No cutoffs added yet.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table>
                                <thead><tr><th>Year</th><th>Exam</th><th>Course</th><th>Category</th><th>Round</th><th>Ranks (Op-Cl)</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach($cutoffs as $c): ?>
                                    <tr>
                                        <td style="font-weight:600;"><?php echo htmlspecialchars($c['cutoff_year']); ?></td>
                                        <td><?php echo htmlspecialchars($c['exam_name']); ?></td>
                                        <td><?php echo htmlspecialchars($c['course_name']); ?></td>
                                        <td><?php echo htmlspecialchars($c['category']); ?></td>
                                        <td><?php echo htmlspecialchars($c['round_number']); ?></td>
                                        <td><?php echo $c['opening_rank'] . ' - ' . $c['closing_rank']; ?></td>
                                        <td>
                                            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                                                <input type="hidden" name="action" value="delete"><input type="hidden" name="c_id" value="<?php echo $c['id']; ?>">
                                                <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer;"><i class="ph ph-trash" style="font-size:1.2rem;"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>
</body>
</html>

