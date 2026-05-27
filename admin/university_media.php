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
                INSERT INTO university_media (id, university_id, media_type, sub_type, url, thumbnail_url, caption, sort_order) 
                VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $university_id,
                $_POST['media_type'],
                $_POST['sub_type'] ?: null,
                $_POST['url'],
                $_POST['thumbnail_url'] ?: null,
                $_POST['caption'] ?: null,
                $_POST['sort_order'] ?: 0
            ]);
            header("Location: university_media.php?university_id=$university_id&msg=added");
            exit;
        } catch (Exception $e) {
            $error = "Error adding media: " . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM university_media WHERE id = ? AND university_id = ?");
        $stmt->execute([$_POST['m_id'], $university_id]);
        header("Location: university_media.php?university_id=$university_id&msg=deleted");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM university_media WHERE university_id = ? ORDER BY media_type ASC, sort_order ASC");
$stmt->execute([$university_id]);
$media = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Media</title>
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
                    <a href="university_media.php?university_id=<?php echo $university_id; ?>" class="tab-link active">Media & Gallery</a>
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
                    <h3><i class="ph ph-plus-circle"></i> Add Media/Document</h3>
                    <form action="" method="POST" style="margin-top:16px;">
                        <input type="hidden" name="action" value="add">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Media Type</label>
                                <select name="media_type" class="form-control" required>
                                    <option value="image">Image</option><option value="video">Video</option><option value="document">Document</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Sub Type / Category</label>
                                <select name="sub_type" class="form-control">
                                    <option value="">None</option>
                                    <option value="campus">Campus</option><option value="lab">Lab</option>
                                    <option value="hostel">Hostel</option><option value="event">Event</option>
                                    <option value="tour">Tour</option><option value="placement">Placement</option>
                                    <option value="brochure">Brochure</option><option value="prospectus">Prospectus</option>
                                    <option value="ranking">Ranking</option>
                                </select>
                            </div>
                            <div class="form-group full"><label>URL (Direct Link)</label><input type="url" name="url" class="form-control" required></div>
                            <div class="form-group full"><label>Thumbnail URL (For Videos)</label><input type="url" name="thumbnail_url" class="form-control"></div>
                            <div class="form-group full"><label>Caption / Title</label><input type="text" name="caption" class="form-control"></div>
                            <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-control" value="0"></div>
                        </div>
                        <div style="text-align: right; margin-top:16px;"><button type="submit" class="btn btn-primary">Add Media</button></div>
                    </form>
                </div>

                <div class="panel">
                    <h3><i class="ph ph-list"></i> Media Gallery</h3>
                    <?php if(empty($media)): ?>
                        <p style="color:var(--text-muted); margin-top:16px;">No media added yet.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table>
                                <thead><tr><th>Preview</th><th>Type</th><th>Sub-Type</th><th>Caption</th><th>Order</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach($media as $m): ?>
                                    <tr>
                                        <td>
                                            <?php if($m['media_type'] == 'image'): ?>
                                                <img src="<?php echo htmlspecialchars($m['url']); ?>" alt="" style="width:60px; height:60px; object-fit:cover; border-radius:4px;">
                                            <?php else: ?>
                                                <i class="ph ph-file-text" style="font-size:2rem; color:var(--text-muted);"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-transform:capitalize;"><?php echo htmlspecialchars($m['media_type']); ?></td>
                                        <td style="text-transform:capitalize;"><?php echo htmlspecialchars($m['sub_type']?:'-'); ?></td>
                                        <td><?php echo htmlspecialchars($m['caption']); ?></td>
                                        <td><?php echo htmlspecialchars($m['sort_order']); ?></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($m['url']); ?>" target="_blank" style="margin-right:8px; color:var(--primary);"><i class="ph ph-eye" style="font-size:1.2rem;"></i></a>
                                            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                                                <input type="hidden" name="action" value="delete"><input type="hidden" name="m_id" value="<?php echo $m['id']; ?>">
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
