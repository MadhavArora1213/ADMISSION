<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

$college_id = isset($_GET['college_id']) ? $_GET['college_id'] : null;
if (!$college_id) {
    header('Location: colleges.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, name FROM colleges WHERE id = ?");
$stmt->execute([$college_id]);
$college = $stmt->fetch();
if (!$college) {
    header('Location: colleges.php');
    exit;
}

$msg = '';
$error = '';

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add_course') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO college_courses 
                (id, college_id, course_name, course_level, duration_years, total_fee, semester_fee, annual_fee, seats_available, specializations, eligibility_criteria, application_fee, emi_available) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                generateUUID(),
                $college_id,
                $_POST['course_name'],
                $_POST['course_level'],
                $_POST['duration_years'] ?: null,
                $_POST['total_fee'] ?: null,
                $_POST['semester_fee'] ?: null,
                $_POST['annual_fee'] ?: null,
                $_POST['seats_available'] ?: null,
                $_POST['specializations'] ?: null,
                $_POST['eligibility_criteria'] ?: null,
                $_POST['application_fee'] ?: null,
                isset($_POST['emi_available']) ? 1 : 0
            ]);
            header("Location: college_courses.php?college_id=$college_id&msg=added");
            exit;
        } catch (Exception $e) {
            $error = "Error adding course: " . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete_course') {
        $stmt = $pdo->prepare("DELETE FROM college_courses WHERE id = ? AND college_id = ?");
        $stmt->execute([$_POST['cc_id'], $college_id]);
        header("Location: college_courses.php?college_id=$college_id&msg=deleted");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM college_courses WHERE college_id = ? ORDER BY course_name ASC");
$stmt->execute([$college_id]);
$college_courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses | AdmissionSeason</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
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
        .form-group.full { grid-column: 1 / -1; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; }
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
                    <span>Admin</span>
                </div>
            </header>
            <div class="content-area">
                <div class="page-header">
                    <h2>
                        <a href="colleges.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                        Edit College: <?php echo htmlspecialchars($college['name']); ?>
                    </h2>
                </div>
                <div class="tabs-nav">
                    <a href="college_form.php?id=<?php echo $college_id; ?>&tab=identity" class="tab-link">Identity & Contact</a>
                    <a href="college_form.php?id=<?php echo $college_id; ?>&tab=about" class="tab-link">About & Amenities</a>
                    <a href="college_form.php?id=<?php echo $college_id; ?>&tab=seo" class="tab-link">SEO & Publish</a>
                    <a href="college_courses.php?college_id=<?php echo $college_id; ?>" class="tab-link active">Courses & Fees</a>
                    <a href="college_placements.php?college_id=<?php echo $college_id; ?>" class="tab-link">Placements</a>
                    <a href="college_cutoffs.php?college_id=<?php echo $college_id; ?>" class="tab-link">Cutoffs</a>
                    <a href="college_media.php?college_id=<?php echo $college_id; ?>" class="tab-link">Media & Gallery</a>
                    <a href="college_faqs.php?college_id=<?php echo $college_id; ?>" class="tab-link">FAQs</a>
                    <a href="college_faculty.php?college_id=<?php echo $college_id; ?>" class="tab-link">Faculty</a>
                    <a href="college_scholarships.php?college_id=<?php echo $college_id; ?>" class="tab-link">Scholarships</a>
                </div>

                <?php if(isset($_GET['msg'])): ?>
                    <div style="padding: 16px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 24px; border: 1px solid #bbf7d0;">
                        Action completed successfully!
                    </div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div style="padding: 16px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 24px; border: 1px solid #fecaca;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="panel">
                    <h3><i class="ph ph-plus-circle"></i> Add Course to College</h3>
                    <form action="" method="POST" style="margin-top:16px;">
                        <input type="hidden" name="action" value="add_course">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Course Name *</label>
                                <input type="text" name="course_name" class="form-control" required placeholder="e.g. B.Tech Computer Science">
                            </div>
                            <div class="form-group">
                                <label>Course Level *</label>
                                <select name="course_level" class="form-control" required>
                                    <option value="">Select Level</option>
                                    <option value="UG">Undergraduate (UG)</option>
                                    <option value="PG">Postgraduate (PG)</option>
                                    <option value="Diploma">Diploma</option>
                                    <option value="PhD">PhD</option>
                                    <option value="Certificate">Certificate</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Duration (Years)</label>
                                <input type="number" step="0.5" name="duration_years" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Total Intake (Seats)</label>
                                <input type="number" name="seats_available" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Total Fee (Rs)</label>
                                <input type="number" step="0.01" name="total_fee" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Semester Fee (Rs)</label>
                                <input type="number" step="0.01" name="semester_fee" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Annual Fee (Rs)</label>
                                <input type="number" step="0.01" name="annual_fee" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Application Fee (Rs)</label>
                                <input type="number" step="0.01" name="application_fee" class="form-control">
                            </div>
                            <div class="form-group full">
                                <label>Eligibility Criteria</label>
                                <textarea name="eligibility_criteria" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Specializations (JSON List)</label>
                                <input type="text" name="specializations" class="form-control" placeholder='["CSE", "IT", "Data Science"]'>
                            </div>
                            <div class="form-group full" style="display:flex; gap:8px; align-items:center;">
                                <input type="checkbox" name="emi_available" id="emi_available">
                                <label for="emi_available" style="margin:0; font-weight:normal;">EMI Options Available for Fees</label>
                            </div>
                        </div>
                        <div style="text-align: right; margin-top:16px;">
                            <button type="submit" class="btn btn-primary">Add Course</button>
                        </div>
                    </form>
                </div>

                <div class="panel">
                    <h3><i class="ph ph-list"></i> Associated Courses</h3>
                    <?php if(empty($college_courses)): ?>
                        <p style="color:var(--text-muted); margin-top:16px;">No courses added yet.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Course Name</th>
                                        <th>Level</th>
                                        <th>Duration</th>
                                        <th>Total Fee</th>
                                        <th>Seats</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($college_courses as $cc): ?>
                                    <tr>
                                        <td style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($cc['course_name']); ?></td>
                                        <td><?php echo htmlspecialchars($cc['course_level']); ?></td>
                                        <td><?php echo $cc['duration_years'] ? $cc['duration_years'].' Yrs' : '-'; ?></td>
                                        <td><?php echo $cc['total_fee'] ? '₹'.number_format($cc['total_fee'], 2) : '-'; ?></td>
                                        <td><?php echo $cc['seats_available'] ?: '-'; ?></td>
                                        <td>
                                            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Delete this course?');">
                                                <input type="hidden" name="action" value="delete_course">
                                                <input type="hidden" name="cc_id" value="<?php echo $cc['id']; ?>">
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
