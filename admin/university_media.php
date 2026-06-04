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
            $media_type = $_POST['media_type'];
            
            $image_url = null;
            $image_type = null;
            $video_url = null;
            $video_type = null;
            $document_url = null;
            $document_type = null;
            $tour_360_url = null;

            $final_url = !empty($_POST['url']) ? $_POST['url'] : null;

            if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
                $upload_dir = '../uploads/media/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['media_file']['name']));
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['media_file']['tmp_name'], $target_file)) {
                    $final_url = 'uploads/media/' . $file_name;
                }
            }

            if($media_type == 'image') {
                $image_url = $final_url;
                $image_type = $_POST['sub_type'] ?: null;
            } elseif ($media_type == 'video') {
                $video_url = $final_url;
                $video_type = $_POST['sub_type'] ?: null;
            } elseif ($media_type == 'document') {
                $document_url = $final_url;
                $document_type = $_POST['sub_type'] ?: null;
            } elseif ($media_type == '360') {
                $tour_360_url = $final_url;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO university_media (id, university_id, image_url, image_type, video_url, video_type, document_url, document_type, `360_tour_url`, caption, sort_order) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                generateUUID(),
                $university_id,
                $image_url,
                $image_type,
                $video_url,
                $video_type,
                $document_url,
                $document_type,
                $tour_360_url,
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
    } elseif (isset($_POST['action']) && $_POST['action'] == 'update_tour') {
        $enabled = isset($_POST['virtual_tour_enabled']) ? 1 : 0;
        $url = !empty($_POST['tour_url']) ? $_POST['tour_url'] : null;
        
        $chk = $pdo->prepare("SELECT id FROM university_media WHERE university_id = ? AND image_url IS NULL AND video_url IS NULL AND document_url IS NULL LIMIT 1");
        $chk->execute([$university_id]);
        $row = $chk->fetch();
        
        if ($row) {
            $stmt = $pdo->prepare("UPDATE university_media SET virtual_tour_enabled = ?, `360_tour_url` = ? WHERE id = ?");
            $stmt->execute([$enabled, $url, $row['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO university_media (id, university_id, virtual_tour_enabled, `360_tour_url`) VALUES (?, ?, ?, ?)");
            $stmt->execute([generateUUID(), $university_id, $enabled, $url]);
        }
        
        header("Location: university_media.php?university_id=$university_id&msg=updated");
        exit;
    }
}

// Fetch all media items
$stmt = $pdo->prepare("SELECT * FROM university_media WHERE university_id = ? AND (image_url IS NOT NULL OR video_url IS NOT NULL OR document_url IS NOT NULL OR (`360_tour_url` IS NOT NULL AND virtual_tour_enabled IS NULL)) ORDER BY sort_order ASC");
$stmt->execute([$university_id]);
$media = $stmt->fetchAll();

// Fetch virtual tour setting
$stmtTour = $pdo->prepare("SELECT virtual_tour_enabled, `360_tour_url` FROM university_media WHERE university_id = ? AND image_url IS NULL AND video_url IS NULL AND document_url IS NULL LIMIT 1");
$stmtTour->execute([$university_id]);
$tourSetting = $stmtTour->fetch();
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
                    <form action="" method="POST" style="display:flex; flex-wrap:wrap; gap:16px; align-items:center;">
                        <input type="hidden" name="action" value="update_tour">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="virtual_tour_enabled" id="vt" <?php echo !empty($tourSetting['virtual_tour_enabled']) ? 'checked' : ''; ?> onchange="document.getElementById('tourUrlInput').style.display = this.checked ? 'block' : 'none';">
                            <label for="vt" style="font-weight:600; cursor:pointer;">Virtual Tour Enabled</label>
                        </div>
                        <div id="tourUrlInput" style="display: <?php echo !empty($tourSetting['virtual_tour_enabled']) ? 'block' : 'none'; ?>; flex: 1; min-width: 250px;">
                            <input type="url" name="tour_url" placeholder="Virtual Tour URL (https://...)" class="form-control" value="<?php echo htmlspecialchars($tourSetting['360_tour_url'] ?? ''); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.9rem;">Save Setting</button>
                    </form>
                </div>

                <div class="panel">
                    <h3><i class="ph ph-plus-circle"></i> Add Media/Document</h3>
                    <form action="" method="POST" enctype="multipart/form-data" style="margin-top:16px;">
                        <input type="hidden" name="action" value="add">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Media Type</label>
                                <select name="media_type" class="form-control" required id="media_type_select">
                                    <option value="image">Image</option>
                                    <option value="video">Video</option>
                                    <option value="document">Document</option>
                                    <option value="360">360 Tour Embed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Sub Type / Category</label>
                                <select name="sub_type" class="form-control">
                                    <option value="">None</option>
                                    <optgroup label="Images">
                                        <option value="campus">Campus</option><option value="lab">Lab</option>
                                        <option value="hostel">Hostel</option><option value="event">Event</option>
                                        <option value="classroom">Classroom</option>
                                    </optgroup>
                                    <optgroup label="Videos">
                                        <option value="tour">Tour</option><option value="placement">Placement</option>
                                        <option value="event">Event</option><option value="alumni_talk">Alumni Talk</option>
                                    </optgroup>
                                    <optgroup label="Documents">
                                        <option value="brochure">Brochure</option><option value="prospectus">Prospectus</option>
                                        <option value="annual_report">Annual Report</option><option value="ranking_cert">Ranking Cert</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="form-group full" id="file_group">
                                <label>Upload File</label>
                                <input type="file" name="media_file" id="media_file_input" class="form-control" accept="image/*">
                            </div>
                            <div class="form-group full" id="url_group">
                                <label>OR Direct Link / Embed URL (e.g., YouTube, Matterport)</label>
                                <input type="url" name="url" id="media_url_input" class="form-control">
                            </div>
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
                                <thead><tr><th>Preview</th><th>Category</th><th>Sub-Type</th><th>Caption</th><th>Order</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach($media as $m): 
                                        $typeStr = '';
                                        $subStr = '';
                                        $link = '';
                                        if ($m['image_url']) { $typeStr = 'Image'; $subStr = $m['image_type']; $link = $m['image_url']; }
                                        elseif ($m['video_url']) { $typeStr = 'Video'; $subStr = $m['video_type']; $link = $m['video_url']; }
                                        elseif ($m['document_url']) { $typeStr = 'Document'; $subStr = $m['document_type']; $link = $m['document_url']; }
                                        elseif ($m['360_tour_url']) { $typeStr = '360 Tour'; $link = $m['360_tour_url']; }
                                        
                                        $display_link = preg_match('/^https?:\/\//', $link) ? $link : '../' . $link;
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if($typeStr == 'Image'): ?>
                                                <img src="<?php echo htmlspecialchars($display_link); ?>" alt="" style="width:60px; height:60px; object-fit:cover; border-radius:4px;">
                                            <?php elseif($typeStr == 'Document'): ?>
                                                <i class="ph ph-file-pdf" style="font-size:2rem; color:var(--text-muted);"></i>
                                            <?php else: ?>
                                                <i class="ph ph-video-camera" style="font-size:2rem; color:var(--text-muted);"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-transform:capitalize;"><?php echo $typeStr; ?></td>
                                        <td style="text-transform:capitalize;"><?php echo htmlspecialchars($subStr?:'-'); ?></td>
                                        <td><?php echo htmlspecialchars($m['caption']); ?></td>
                                        <td><?php echo htmlspecialchars($m['sort_order']); ?></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($display_link); ?>" target="_blank" style="margin-right:8px; color:var(--primary);"><i class="ph ph-eye" style="font-size:1.2rem;"></i></a>
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
    <script>
        document.getElementById('media_type_select').addEventListener('change', function() {
            var type = this.value;
            var fileGroup = document.getElementById('file_group');
            var urlGroup = document.getElementById('url_group');
            var fileInput = document.getElementById('media_file_input');
            
            if (type === '360') {
                fileGroup.style.display = 'none';
                fileInput.removeAttribute('accept');
                urlGroup.style.display = 'block';
            } else {
                fileGroup.style.display = 'block';
                urlGroup.style.display = 'block';
                if (type === 'image') {
                    fileInput.setAttribute('accept', 'image/*');
                } else if (type === 'video') {
                    fileInput.setAttribute('accept', 'video/*');
                } else if (type === 'document') {
                    fileInput.setAttribute('accept', '.pdf,.doc,.docx');
                }
            }
        });
        document.getElementById('media_type_select').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
