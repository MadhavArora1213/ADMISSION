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
            $photo_url = null;
            if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] == 0) {
                $upload_dir = '../uploads/faculty/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['photo_file']['name']));
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['photo_file']['tmp_name'], $target_file)) {
                    $photo_url = 'uploads/faculty/' . $file_name;
                }
            }
            if (!$photo_url && !empty($_POST['photo_url'])) {
                $photo_url = $_POST['photo_url'];
            }

            $stmt = $pdo->prepare("
                INSERT INTO university_faculty (id, university_id, faculty_name, designation, department, qualification, experience_years, photo_url, research_papers) 
                VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $university_id,
                $_POST['faculty_name'],
                $_POST['designation'] ?: null,
                $_POST['department'] ?: null,
                $_POST['qualification'] ?: null,
                $_POST['experience_years'] ?: null,
                $photo_url,
                $_POST['research_papers'] ?: 0
            ]);
            header("Location: university_faculty.php?university_id=$university_id&msg=added");
            exit;
        } catch (Exception $e) {
            $error = "Error adding faculty: " . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM university_faculty WHERE id = ? AND university_id = ?");
        $stmt->execute([$_POST['f_id'], $university_id]);
        header("Location: university_faculty.php?university_id=$university_id&msg=deleted");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM university_faculty WHERE university_id = ? ORDER BY faculty_name ASC");
$stmt->execute([$university_id]);
$faculty = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculty</title>
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
        .main-content { flex: 1; margin-left: 280px; max-width: calc(100% - 280px); display: flex; flex-direction: column; }
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
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full { grid-column: 1 / -1; }
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
                    <a href="university_cutoffs.php?university_id=<?php echo $university_id; ?>" class="tab-link">Cutoffs</a>
                    <a href="university_media.php?university_id=<?php echo $university_id; ?>" class="tab-link">Media & Gallery</a>
                    <a href="university_faqs.php?university_id=<?php echo $university_id; ?>" class="tab-link">FAQs</a>
                    <a href="university_faculty.php?university_id=<?php echo $university_id; ?>" class="tab-link active">Faculty</a>
                    <a href="university_scholarships.php?university_id=<?php echo $university_id; ?>" class="tab-link">Scholarships</a>
                </div>

                <?php if(isset($_GET['msg'])): ?>
                    <div style="padding: 16px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 24px; border: 1px solid #bbf7d0;">Action completed successfully!</div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div style="padding: 16px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 24px; border: 1px solid #fecaca;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="panel">
                    <h3><i class="ph ph-plus-circle"></i> Add Faculty Member</h3>
                    <form action="" method="POST" enctype="multipart/form-data" style="margin-top:16px;">
                        <input type="hidden" name="action" value="add">
                        <div class="form-grid">
                            <div class="form-group"><label>Name</label><input type="text" name="faculty_name" class="form-control" required></div>
                            <div class="form-group"><label>Designation</label><input type="text" name="designation" class="form-control" placeholder="e.g. Professor"></div>
                            <div class="form-group"><label>Department</label><input type="text" name="department" class="form-control" placeholder="e.g. Computer Science"></div>
                            <div class="form-group"><label>Qualification</label><input type="text" name="qualification" class="form-control" placeholder="e.g. Ph.D"></div>
                            <div class="form-group"><label>Experience (Years)</label><input type="number" name="experience_years" class="form-control"></div>
                            <div class="form-group"><label>Research Papers</label><input type="number" name="research_papers" class="form-control" value="0"></div>
                            <div class="form-group"><label>Upload Photo</label><input type="file" name="photo_file" class="form-control" accept="image/*"></div>
                            <div class="form-group"><label>OR Photo URL</label><input type="url" name="photo_url" class="form-control"></div>
                        </div>
                        <div style="text-align: right; margin-top:16px;"><button type="submit" class="btn btn-primary">Add Faculty</button></div>
                    </form>
                </div>

                <div class="panel">
                    <h3><i class="ph ph-users"></i> Faculty Directory</h3>
                    <?php if(empty($faculty)): ?>
                        <p style="color:var(--text-muted); margin-top:16px;">No faculty added yet.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table>
                                <thead><tr><th>Name</th><th>Designation</th><th>Department</th><th>Qualification</th><th>Exp (Yrs)</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach($faculty as $f): ?>
                                    <tr>
                                        <td style="font-weight:600;"><?php echo htmlspecialchars($f['faculty_name']); ?></td>
                                        <td><?php echo htmlspecialchars($f['designation']?:'-'); ?></td>
                                        <td><?php echo htmlspecialchars($f['department']?:'-'); ?></td>
                                        <td><?php echo htmlspecialchars($f['qualification']?:'-'); ?></td>
                                        <td><?php echo htmlspecialchars($f['experience_years']?:'-'); ?></td>
                                        <td>
                                            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                                                <input type="hidden" name="action" value="delete"><input type="hidden" name="f_id" value="<?php echo $f['id']; ?>">
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

