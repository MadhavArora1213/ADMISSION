<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = $_GET['id'] ?? '';
$school_id = $_GET['school_id'] ?? '';
if (!$id || !$school_id) { header('Location: schools.php'); exit; }

$course = $pdo->prepare("SELECT * FROM school_courses WHERE id = ? AND school_id = ?");
$course->execute([$id, $school_id]);
$course = $course->fetch(PDO::FETCH_ASSOC);
if (!$course) { header("Location: school_courses.php?school_id=$school_id"); exit; }

$schoolName = $pdo->prepare("SELECT name FROM schools WHERE id = ?");
$schoolName->execute([$school_id]);
$schoolName = $schoolName->fetchColumn() ?: 'School';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:#f1f5f9;margin:0;font-family:'Plus Jakarta Sans',sans-serif}
        .wrap{max-width:700px;margin:0 auto;padding:24px 20px}
        .top-bar{display:flex;align-items:center;gap:10px;margin-bottom:24px}
        .top-bar h1{font-size:1.3rem;font-weight:800;margin:0}
        .top-bar a{color:#64748b;text-decoration:none;font-size:1.1rem}
        .form-section{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px}
        .fg{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .fg.f1{grid-template-columns:1fr}
        .fi{margin-bottom:14px}
        .fi label{display:block;font-weight:600;font-size:.82rem;color:#334155;margin-bottom:5px}
        .fi input,.fi select{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.88rem;font-family:inherit;box-sizing:border-box}
        .fi input:focus,.fi select:focus{outline:none;border-color:#19376D}
        .btn-row{display:flex;gap:10px;margin-top:16px}
        .btn{padding:10px 20px;border-radius:8px;font-weight:700;font-size:.85rem;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
        .btn-p{background:linear-gradient(135deg,#0B2447,#19376D);color:#fff}
        .btn-e{background:none;color:#334155;border:1.5px solid #e2e8f0}
    </style>
</head>
<body>
<div class="wrap">
    <div class="top-bar">
        <a href="school_courses.php?school_id=<?= $school_id ?>"><i class="ph ph-arrow-left"></i></a>
        <h1>Edit: <?= htmlspecialchars($course['class_name']) ?></h1>
    </div>
    <div class="form-section">
        <form method="POST" action="school_course_save.php">
            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
            <input type="hidden" name="school_id" value="<?= htmlspecialchars($school_id) ?>">
            <div class="fg">
                <div class="fi"><label>Class/Section Name *</label><input type="text" name="class_name" required value="<?= htmlspecialchars($course['class_name']) ?>"></div>
                <div class="fi"><label>Level</label><select name="class_level"><option value="">Select</option><?php foreach(['nursery'=>'Nursery','lkg'=>'LKG','ukg'=>'UKG','primary'=>'Primary (1-5)','upper_primary'=>'Upper Primary (6-8)','secondary'=>'Secondary (9-10)','senior_secondary'=>'Senior Secondary (11-12)'] as $v=>$l): ?><option value="<?=$v?>" <?= $course['class_level']===$v?'selected':'' ?>><?=$l?></option><?php endforeach; ?></select></div>
                <div class="fi"><label>Annual Fee (₹)</label><input type="number" name="annual_fee" step="0.01" value="<?= htmlspecialchars($course['annual_fee'] ?? '') ?>"></div>
                <div class="fi"><label>Semester Fee (₹)</label><input type="number" name="semester_fee" step="0.01" value="<?= htmlspecialchars($course['semester_fee'] ?? '') ?>"></div>
                <div class="fi"><label>Total Fee (₹)</label><input type="number" name="total_fee" step="0.01" value="<?= htmlspecialchars($course['total_fee'] ?? '') ?>"></div>
                <div class="fi"><label>Seats Available</label><input type="number" name="seats_available" value="<?= htmlspecialchars($course['seats_available'] ?? '') ?>"></div>
                <div class="fi"><label>Session Year</label><input type="text" name="session_year" value="<?= htmlspecialchars($course['session_year'] ?? '') ?>"></div>
                <div class="fi"><label>Sort Order</label><input type="number" name="sort_order" value="<?= (int)$course['sort_order'] ?>"></div>
            </div>
            <div class="btn-row">
                <button type="submit" class="btn btn-p"><i class="ph ph-floppy-disk"></i> Save</button>
                <a href="school_courses.php?school_id=<?= $school_id ?>" class="btn btn-e">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
