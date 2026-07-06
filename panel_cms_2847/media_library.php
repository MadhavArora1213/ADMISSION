<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM media_files WHERE id=?")->execute([$_GET['id']]);
    header("Location: media_library.php?msg=deleted"); exit;
}

// Handle upload metadata save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $alt_text = $_POST['alt_text'] ?? '';
    
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/media/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $original_name = $_FILES['media_file']['name'];
        $tmp_name = $_FILES['media_file']['tmp_name'];
        $file_size = round($_FILES['media_file']['size'] / 1024, 2);
        
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        
        $file_type = 'doc';
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) $file_type = 'image';
        elseif (in_array($ext, ['mp4','webm','mov'])) $file_type = 'video';
        elseif ($ext === 'pdf') $file_type = 'pdf';
        elseif ($ext === 'svg') $file_type = 'svg';
        
        $new_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $original_name);
        $destination = $upload_dir . $new_name;
        
        if (move_uploaded_file($tmp_name, $destination)) {
            require_once __DIR__ . '/upload_sync.php';
            sync_to_github('uploads/media/' . $new_name);
            $file_url = '../uploads/media/' . $new_name;
            $mid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
            
            $pdo->prepare("INSERT INTO media_files (id, file_name, file_url, cdn_url, file_type, file_size_kb, alt_text, folder_path, uploaded_by) VALUES (?,?,?,?,?,?,?,?,?)")->execute([
                $mid,
                $original_name,
                $file_url,
                null,
                $file_type,
                $file_size,
                $alt_text,
                '/uploads/media/',
                $_SESSION['admin_id']
            ]);
            header("Location: media_library.php?msg=added"); exit;
        } else {
            header("Location: media_library.php?msg=error"); exit;
        }
    } else {
        header("Location: media_library.php?msg=error"); exit;
    }
}

$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$where = $type_filter !== 'all' ? "WHERE file_type = '$type_filter'" : '';
$files = $pdo->query("SELECT mf.*, u.full_name as uploader FROM media_files mf LEFT JOIN users u ON mf.uploaded_by = u.id $where ORDER BY mf.created_at DESC LIMIT 200")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Library | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#f8fafc;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.filter-bar{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;align-items:center}.tab-link{padding:7px 14px;font-weight:600;color:var(--text-muted);border-radius:8px;border:1px solid var(--border-color);background:#f8fafc;font-size:.85rem;text-decoration:none;transition:all .2s}.tab-link:hover,.tab-link.active{background:var(--primary);color:#fff;border-color:var(--primary)}.media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px}.media-card{background:#fff;border:1px solid var(--border-color);border-radius:10px;overflow:hidden;transition:box-shadow .2s}.media-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.1)}.media-thumb{width:100%;height:130px;object-fit:cover;display:block;background:#F8FAFC}.media-thumb-icon{height:130px;display:flex;align-items:center;justify-content:center;background:#F8FAFC;font-size:3rem;color:var(--text-muted)}.media-info{padding:10px}.media-info .name{font-size:.8rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text-dark)}.media-info .meta{font-size:.72rem;color:var(--text-muted);margin-top:3px}.media-actions{display:flex;gap:4px;margin-top:8px}.msg-alert{padding:14px 20px;border-radius:8px;background:rgba(11,36,71,0.04);color:#0B2447;border:1px solid rgba(11,36,71,0.04);margin-bottom:20px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.form-group{margin-bottom:14px}.form-group label{display:block;font-weight:600;margin-bottom:6px;font-size:.88rem;color:var(--text-muted)}.form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.95rem;box-sizing:border-box}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}
    </style>
    <style>
        .mobile-menu-btn{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#0f172a;padding:4px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:90}
        @media(max-width:768px){.sidebar{transform:translateX(-100%);z-index:100;transition:transform .3s}.sidebar.open{transform:translateX(0)}.sidebar-overlay.show{display:block}.main-content{margin-left:0}.mobile-menu-btn{display:block}.topbar{height:auto;min-height:56px;padding:10px 12px;justify-content:space-between}.content-area{padding:12px}.page-header{flex-direction:column;align-items:flex-start;gap:8px}.page-header h2{font-size:1.3rem}.media-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px}.filter-bar{gap:4px}.tab-link{padding:5px 10px;font-size:.8rem}}
        @media(max-width:480px){.media-grid{grid-template-columns:repeat(auto-fill,minmax(120px,1fr))}}
    </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <button class="mobile-menu-btn" id="mobile-menu-btn"><i class="ph ph-list"></i></button>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                <a href="logout.php" style="margin-left:16px;color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-images" style="color:var(--primary);"></i> Media Library</h2>
                    <p style="color:var(--text-muted);"><?php echo count($files); ?> files</p>
                </div>
                <button onclick="document.getElementById('addModal').style.display='flex'" class="btn btn-primary"><i class="ph ph-plus"></i> Add File</button>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> <?php echo $_GET['msg']=='added'?'File added to library.':'File removed.'; ?></div>
            <?php endif; ?>

            <div class="filter-bar">
                <a href="?type=all" class="tab-link <?php echo $type_filter=='all'?'active':''; ?>">All</a>
                <?php foreach(['image','video','pdf','doc','svg'] as $ft): ?>
                <a href="?type=<?php echo $ft; ?>" class="tab-link <?php echo $type_filter==$ft?'active':''; ?>"><?php echo ucfirst($ft); ?></a>
                <?php endforeach; ?>
            </div>

            <?php if(empty($files)): ?>
            <div class="panel" style="text-align:center; padding:60px;">
                <i class="ph ph-images" style="font-size:4rem; color:var(--text-muted);"></i>
                <p style="color:var(--text-muted); margin-top:12px; font-size:1.1rem;">No media files yet.</p>
                <button onclick="document.getElementById('addModal').style.display='flex'" class="btn btn-primary" style="margin-top:16px;">Add First File</button>
            </div>
            <?php else: ?>
            <div class="media-grid">
                <?php foreach($files as $f): ?>
                <div class="media-card">
                    <?php if($f['file_type'] == 'image' && $f['file_url']): ?>
                    <img src="<?php echo htmlspecialchars($f['file_url']); ?>" class="media-thumb" alt="<?php echo htmlspecialchars($f['alt_text']); ?>">
                    <?php else: ?>
                    <div class="media-thumb-icon">
                        <?php
                        $icons = ['video'=>'ph-video','pdf'=>'ph-file-pdf','doc'=>'ph-file-doc','svg'=>'ph-file-svg'];
                        echo '<i class="ph '.($icons[$f['file_type']] ?? 'ph-file').'"></i>';
                        ?>
                    </div>
                    <?php endif; ?>
                    <div class="media-info">
                        <div class="name" title="<?php echo htmlspecialchars($f['file_name']); ?>"><?php echo htmlspecialchars($f['file_name']); ?></div>
                        <div class="meta">
                            <span class="badge" style="background:rgba(11,36,71,0.06);color:#19376D;"><?php echo strtoupper($f['file_type']); ?></span>
                            <?php if($f['file_size_kb']): ?> &bull; <?php echo $f['file_size_kb'] > 1024 ? round($f['file_size_kb']/1024,1).'MB' : $f['file_size_kb'].'KB'; ?><?php endif; ?>
                        </div>
                        <div class="media-actions">
                            <a href="<?php echo htmlspecialchars($f['file_url']); ?>" target="_blank" class="tab-link" style="font-size:.75rem; padding:4px 8px;"><i class="ph ph-arrow-square-out"></i> View</a>
                            <a href="?action=delete&id=<?php echo $f['id']; ?>" class="tab-link" style="font-size:.75rem; padding:4px 8px; color:#0F172A; border-color:rgba(15,23,42,0.06);" onclick="return confirm('Delete?')"><i class="ph ph-trash"></i></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Add File Modal -->
<div id="addModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:32px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h3 style="font-size:1.2rem; font-weight:700;">Add File to Library</h3>
            <button onclick="document.getElementById('addModal').style.display='none'" style="background:none; border:none; cursor:pointer; font-size:1.5rem; color:var(--text-muted);">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <div class="form-grid">
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Select File *</label>
                    <input type="file" name="media_file" class="form-control" required style="padding: 12px; background: #f8fafc; border: 2px dashed var(--border-color); cursor: pointer;">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Alt Text (Optional)</label>
                    <input type="text" name="alt_text" class="form-control" placeholder="Descriptive alt text for SEO / Accessibility...">
                </div>
            </div>
            <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                <button type="button" onclick="document.getElementById('addModal').style.display='none'" class="btn" style="background:#F8FAFC;color:rgba(15,23,42,0.65);">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="ph ph-upload-simple"></i> Upload to Library</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('mobile-menu-btn').addEventListener('click',function(){document.querySelector('.sidebar').classList.toggle('open');document.getElementById('sidebar-overlay').classList.toggle('show');});
document.getElementById('sidebar-overlay').addEventListener('click',function(){document.querySelector('.sidebar').classList.remove('open');this.classList.remove('show');});
</script>
</body>
</html>
