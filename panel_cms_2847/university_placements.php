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

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        try {
            $top_recruiters_json = null;
            if (!empty(trim($_POST['top_recruiters']))) {
                $recruiters = array_map('trim', explode(',', $_POST['top_recruiters']));
                $recruiters = array_filter($recruiters, 'strlen');
                if (!empty($recruiters)) {
                    $formatted = array_map(function($r) { return ["name" => $r]; }, array_values($recruiters));
                    $top_recruiters_json = json_encode($formatted);
                }
            }
            
            $sector_wise_json = null;
            if (!empty(trim($_POST['sector_wise_json']))) {
                $sectors = array_map('trim', explode(',', $_POST['sector_wise_json']));
                $sectors = array_filter($sectors, 'strlen');
                if (!empty($sectors)) {
                    $formatted = [];
                    foreach($sectors as $s) {
                        $parts = explode(':', $s);
                        $name = trim($parts[0] ?? '');
                        $pct = trim($parts[1] ?? '0');
                        if ($name) {
                            $formatted[] = ["sector" => $name, "pct" => (int)$pct];
                        }
                    }
                    $sector_wise_json = json_encode($formatted);
                }
            }

            $placement_report_pdf = null;
            if (isset($_FILES['placement_report_pdf']) && $_FILES['placement_report_pdf']['error'] == 0) {
                $uploadDir = '../uploads/placements/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", basename($_FILES['placement_report_pdf']['name']));
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['placement_report_pdf']['tmp_name'], $targetFile)) {
                    $placement_report_pdf = 'uploads/placements/' . $fileName;
                    require_once __DIR__ . '/upload_sync.php';
                    sync_to_github('uploads/placements/' . $fileName);
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO university_placements (id, university_id, placement_year, avg_package_lpa, highest_package_lpa, median_package_lpa, placement_percentage, students_placed, international_placements, top_recruiters, sector_wise_json, placement_report_pdf) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                generateUUID(),
                $university_id,
                $_POST['placement_year'],
                $_POST['avg_package_lpa'] ?: null,
                $_POST['highest_package_lpa'] ?: null,
                $_POST['median_package_lpa'] ?: null,
                $_POST['placement_percentage'] ?: null,
                $_POST['students_placed'] ?: null,
                $_POST['international_placements'] ?: 0,
                $top_recruiters_json,
                $sector_wise_json,
                $placement_report_pdf
            ]);
            header("Location: university_placements.php?university_id=$university_id&msg=added");
            exit;
        } catch (Exception $e) {
            $error = "Error adding placement: " . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM university_placements WHERE id = ? AND university_id = ?");
        $stmt->execute([$_POST['p_id'], $university_id]);
        header("Location: university_placements.php?university_id=$university_id&msg=deleted");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM university_placements WHERE university_id = ? ORDER BY placement_year DESC");
$stmt->execute([$university_id]);
$placements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Placements</title>
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
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none;}
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; min-width: 0; }
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
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid rgba(15,23,42,0.15); border-radius: 8px; font-family: inherit; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; }
        
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        @media (max-width: 768px) { 
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .topbar { justify-content: space-between; padding: 0 16px; }
            .mobile-menu-btn { display: block; }
            .content-area { padding: 16px; }
            .form-grid { grid-template-columns: 1fr; }
            .page-header h2 { font-size: 1.5rem; }
            .panel { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <header class="topbar">
                <button class="mobile-menu-btn" id="mobile-menu-btn">
                    <i class="ph ph-list"></i>
                </button>
                <div class="user-profile">
                    <span>Admin</span>
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
                    <a href="university_placements.php?university_id=<?php echo $university_id; ?>" class="tab-link active">Placements</a>
                    <a href="university_cutoffs.php?university_id=<?php echo $university_id; ?>" class="tab-link">Cutoffs</a>
                    <a href="university_media.php?university_id=<?php echo $university_id; ?>" class="tab-link">Media & Gallery</a>
                    <a href="university_faqs.php?university_id=<?php echo $university_id; ?>" class="tab-link">FAQs</a>
                    <a href="university_faculty.php?university_id=<?php echo $university_id; ?>" class="tab-link">Faculty</a>
                    <a href="university_scholarships.php?university_id=<?php echo $university_id; ?>" class="tab-link">Scholarships</a>
                </div>

                <?php if(isset($_GET['msg'])): ?>
                    <div style="padding: 16px; background: rgba(11,36,71,0.04); color: #0B2447; border-radius: 8px; margin-bottom: 24px; border: 1px solid rgba(11,36,71,0.04);">Action completed successfully!</div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div style="padding: 16px; background: rgba(15,23,42,0.06); color: #0B2447; border-radius: 8px; margin-bottom: 24px; border: 1px solid rgba(15,23,42,0.06);"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="panel">
                    <h3><i class="ph ph-plus-circle"></i> Add Placement Record</h3>
                    <form action="" method="POST" enctype="multipart/form-data" style="margin-top:16px;">
                        <input type="hidden" name="action" value="add">
                        <div class="form-grid">
                            <div class="form-group"><label>Placement Year *</label><input type="number" name="placement_year" class="form-control" required></div>
                            <div class="form-group"><label>Average Package (LPA)</label><input type="number" step="0.01" name="avg_package_lpa" class="form-control"></div>
                            <div class="form-group"><label>Highest Package (LPA)</label><input type="number" step="0.01" name="highest_package_lpa" class="form-control"></div>
                            <div class="form-group"><label>Median Package (LPA)</label><input type="number" step="0.01" name="median_package_lpa" class="form-control"></div>
                            <div class="form-group"><label>Placement Percentage (%)</label><input type="number" step="0.01" name="placement_percentage" class="form-control"></div>
                            <div class="form-group"><label>Students Placed</label><input type="number" name="students_placed" class="form-control"></div>
                            <div class="form-group"><label>International Placements</label><input type="number" name="international_placements" class="form-control" value="0"></div>
                            <div class="form-group"><label>Placement Report PDF</label><input type="file" accept="application/pdf" name="placement_report_pdf" class="form-control"></div>
                            <div class="form-group full"><label>Top Recruiters (Comma-separated)</label><input type="text" name="top_recruiters" class="form-control" placeholder='e.g. Amazon, Google, Microsoft'></div>
                            <div class="form-group full"><label>Sector Wise Distribution (Comma-separated, Sector:Percentage)</label><input type="text" name="sector_wise_json" class="form-control" placeholder='e.g. IT:45, Finance:20, Consulting:15'></div>
                        </div>
                        <div style="text-align: right; margin-top:16px;"><button type="submit" class="btn btn-primary">Add Placement</button></div>
                    </form>
                </div>

                <div class="panel">
                    <h3><i class="ph ph-list"></i> Placement Records</h3>
                    <?php if(empty($placements)): ?>
                        <p style="color:var(--text-muted); margin-top:16px;">No records yet.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table>
                                <thead><tr><th>Year</th><th>Avg LPA</th><th>Highest LPA</th><th>Placed %</th><th>Students</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach($placements as $p): ?>
                                    <tr>
                                        <td style="font-weight:600;"><?php echo htmlspecialchars($p['placement_year']); ?></td>
                                        <td><?php echo $p['avg_package_lpa'] ? '₹'.$p['avg_package_lpa'].' L' : '-'; ?></td>
                                        <td><?php echo $p['highest_package_lpa'] ? '₹'.$p['highest_package_lpa'].' L' : '-'; ?></td>
                                        <td><?php echo $p['placement_percentage'] ? $p['placement_percentage'].'%' : '-'; ?></td>
                                        <td><?php echo $p['students_placed'] ?: '-'; ?></td>
                                        <td>
                                            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Delete this record?');">
                                                <input type="hidden" name="action" value="delete"><input type="hidden" name="p_id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" style="background:none; border:none; color:#0F172A; cursor:pointer;"><i class="ph ph-trash" style="font-size:1.2rem;"></i></button>
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
</body>
</html>
