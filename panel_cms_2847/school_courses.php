<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$school_id = $_GET['school_id'] ?? '';
if (!$school_id) { header('Location: schools.php'); exit; }

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM school_courses WHERE id = ? AND school_id = ?")->execute([$_GET['id'], $school_id]);
    header("Location: school_courses.php?school_id=$school_id&msg=deleted");
    exit;
}

// Fetch school name
$schoolName = $pdo->prepare("SELECT name FROM schools WHERE id = ?");
$schoolName->execute([$school_id]);
$schoolName = $schoolName->fetchColumn() ?: 'School';
if (!$schoolName) { header('Location: schools.php'); exit; }

$courses = $pdo->prepare("SELECT * FROM school_courses WHERE school_id = ? ORDER BY sort_order ASC, class_name ASC");
$courses->execute([$school_id]);
$courses = $courses->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage School Courses | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:#f1f5f9;margin:0;font-family:'Plus Jakarta Sans',sans-serif}
        .wrap{max-width:1000px;margin:0 auto;padding:24px 20px}
        .top-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;gap:12px;flex-wrap:wrap}
        .top-bar h1{font-size:1.4rem;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
        .top-bar h1 a{color:#64748b;text-decoration:none;font-size:1.1rem}
        .msg{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:.9rem;font-weight:600}
        .msg.ok{background:rgba(22,163,74,.1);color:#16a34a;border:1px solid rgba(22,163,74,.2)}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:16px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:12px 16px;text-align:left;border-bottom:1px solid #f1f5f9;font-size:.88rem}
        th{font-weight:700;color:#64748b;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px}
        tr:hover{background:#f8fafc}
        .badge{padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
        .badge-green{background:rgba(22,163,74,.1);color:#16a34a}
        .badge-gray{background:rgba(100,116,139,.1);color:#64748b}
        .btn{padding:10px 20px;border-radius:8px;font-weight:700;font-size:.85rem;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;transition:all .2s}
        .btn-p{background:linear-gradient(135deg,#0B2447,#19376D);color:#fff}
        .btn-p:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(25,55,109,.3)}
        .btn-d{background:none;color:#dc2626;border:1px solid #fecaca;padding:6px 12px;font-size:.8rem}
        .btn-d:hover{background:#fef2f2}
        .btn-e{background:none;color:#19376D;border:1px solid #e2e8f0;padding:6px 12px;font-size:.8rem}
        .btn-e:hover{background:#f0f4ff}
        .empty{text-align:center;padding:40px;color:#94a3b8}
        .empty i{font-size:2.5rem;display:block;margin-bottom:8px}
        .form-section{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:16px}
        .form-section h3{font-size:1rem;font-weight:700;margin:0 0 16px;color:#0f172a}
        .fg{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .fg.f1{grid-template-columns:1fr}
        .fi{margin-bottom:14px}
        .fi label{display:block;font-weight:600;font-size:.82rem;color:#334155;margin-bottom:5px}
        .fi input,.fi select,.fi textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.88rem;font-family:inherit;box-sizing:border-box}
        .fi input:focus,.fi select:focus{outline:none;border-color:#19376D}
        .btn-row{display:flex;gap:10px;margin-top:16px}
        @media(max-width:768px){.fg{grid-template-columns:1fr!important}.wrap{padding:16px 12px}}
    </style>
</head>
<body>
<div class="wrap">
    <div class="top-bar">
        <h1><a href="school_form.php?id=<?= htmlspecialchars($school_id) ?>&tab=identity"><i class="ph ph-arrow-left"></i></a> Courses: <?= htmlspecialchars($schoolName) ?></h1>
        <button class="btn btn-p" onclick="document.getElementById('addForm').style.display=document.getElementById('addForm').style.display==='none'?'block':'none'"><i class="ph ph-plus"></i> Add Class</button>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg']=='saved'): ?><div class="msg ok">Saved successfully!</div><?php endif; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg']=='deleted'): ?><div class="msg ok">Deleted successfully!</div><?php endif; ?>

    <!-- Add Form (hidden by default) -->
    <div id="addForm" style="display:none">
        <div class="form-section">
            <h3><i class="ph ph-plus-circle"></i> Add New Class/Course</h3>
            <form method="POST" action="school_course_save.php">
                <input type="hidden" name="school_id" value="<?= htmlspecialchars($school_id) ?>">
                <div class="fg">
                    <div class="fi"><label>Class/Section Name *</label><input type="text" name="class_name" required placeholder="e.g. Nursery, Class 1, Class 10"></div>
                    <div class="fi"><label>Level</label><select name="class_level"><option value="">Select</option><?php foreach(['nursery'=>'Nursery','lkg'=>'LKG','ukg'=>'UKG','primary'=>'Primary (1-5)','upper_primary'=>'Upper Primary (6-8)','secondary'=>'Secondary (9-10)','senior_secondary'=>'Senior Secondary (11-12)'] as $v=>$l): ?><option value="<?=$v?>"><?=$l?></option><?php endforeach; ?></select></div>
                    <div class="fi"><label>Annual Fee (₹)</label><input type="number" name="annual_fee" step="0.01" placeholder="e.g. 50000"></div>
                    <div class="fi"><label>Semester Fee (₹)</label><input type="number" name="semester_fee" step="0.01"></div>
                    <div class="fi"><label>Total Fee (₹)</label><input type="number" name="total_fee" step="0.01"></div>
                    <div class="fi"><label>Seats Available</label><input type="number" name="seats_available"></div>
                    <div class="fi"><label>Session Year</label><input type="text" name="session_year" placeholder="e.g. 2026-27"></div>
                    <div class="fi"><label>Sort Order</label><input type="number" name="sort_order" value="0"></div>
                </div>
                <div class="btn-row">
                    <button type="submit" class="btn btn-p"><i class="ph ph-floppy-disk"></i> Save</button>
                    <button type="button" class="btn btn-e" onclick="document.getElementById('addForm').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <?php if(empty($courses)): ?>
            <div class="empty"><i class="ph ph-book-open"></i>No courses added yet. Click "Add Class" to start.</div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table>
                    <thead><tr><th>Class Name</th><th>Level</th><th>Annual Fee</th><th>Semester Fee</th><th>Seats</th><th>Session</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach($courses as $c): ?>
                    <tr>
                        <td style="font-weight:600"><?= htmlspecialchars($c['class_name']) ?></td>
                        <td style="text-transform:capitalize"><?= htmlspecialchars($c['class_level'] ?? '—') ?></td>
                        <td><?= $c['annual_fee'] ? '₹'.number_format((float)$c['annual_fee']) : '—' ?></td>
                        <td><?= $c['semester_fee'] ? '₹'.number_format((float)$c['semester_fee']) : '—' ?></td>
                        <td><?= $c['seats_available'] ?: '—' ?></td>
                        <td><?= htmlspecialchars($c['session_year'] ?? '—') ?></td>
                        <td><span class="badge <?= $c['is_active'] ? 'badge-green' : 'badge-gray' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px">
                                <a href="school_course_form.php?id=<?= $c['id'] ?>&school_id=<?= $school_id ?>" class="btn btn-e"><i class="ph ph-pencil-simple"></i> Edit</a>
                                <a href="school_courses.php?school_id=<?= $school_id ?>&action=delete&id=<?= $c['id'] ?>" class="btn btn-d" onclick="return confirm('Delete this class?')"><i class="ph ph-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
