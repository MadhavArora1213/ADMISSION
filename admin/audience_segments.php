<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = $_POST['id'] ?? null;
    $data = [
        $_POST['segment_name'], $_POST['filters_json'], $_POST['refresh_schedule']
    ];
    if ($id) {
        $data[] = $id;
        $pdo->prepare("UPDATE audience_segments SET segment_name=?, filters_json=?, refresh_schedule=? WHERE id=?")->execute($data);
    } else {
        $pdo->prepare("INSERT INTO audience_segments (segment_name, filters_json, refresh_schedule, user_count) VALUES (?, ?, ?, 0)")->execute($data);
    }
    header("Location: audience_segments.php?msg=saved"); exit;
}

// Handle deleting
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM audience_segments WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: audience_segments.php?msg=deleted"); exit;
}

// Fetch all segments
$segments = $pdo->query("SELECT * FROM audience_segments ORDER BY created_at DESC")->fetchAll();
$edit_seg = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM audience_segments WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_seg = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audience Segments | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.sub-links{display:flex;gap:8px;margin-bottom:20px}.sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:rgba(0,0,0,.05);color:var(--primary)}.form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.95rem;box-sizing:border-box}.form-group{margin-bottom:16px}.form-group label{display:block;font-weight:600;margin-bottom:7px;font-size:.9rem;color:var(--text-muted)}.msg-alert{padding:14px 20px;border-radius:8px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;margin-bottom:20px}
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" style="margin-left:16px;color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-users-three" style="color:var(--primary);"></i> Audience Segments</h2>
                    <p style="color:var(--text-muted);">Define target segments for your notification campaigns.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="notifications_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="notification_templates.php" class="sub-link"><i class="ph ph-file-text"></i> Templates</a>
                <a href="audience_segments.php" class="sub-link active"><i class="ph ph-users-three"></i> Segments</a>
                <a href="notification_campaigns.php" class="sub-link"><i class="ph ph-megaphone"></i> Campaigns</a>
                <a href="notification_logs.php" class="sub-link"><i class="ph ph-list-dashes"></i> Logs</a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Operation successful.</div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 350px 1fr; gap:24px;">
                <!-- Add/Edit Form -->
                <div class="panel">
                    <h3><?php echo $edit_seg ? 'Edit Segment' : 'Add New Segment'; ?></h3>
                    <form method="POST" action="audience_segments.php">
                        <input type="hidden" name="action" value="save">
                        <?php if($edit_seg): ?><input type="hidden" name="id" value="<?php echo $edit_seg['id']; ?>"><?php endif; ?>

                        <div class="form-group">
                            <label>Segment Name *</label>
                            <input type="text" name="segment_name" class="form-control" value="<?php echo htmlspecialchars($edit_seg['segment_name']??''); ?>" required placeholder="Engineering Aspirants">
                        </div>
                        
                        <div class="form-group">
                            <label>Filters JSON *</label>
                            <textarea name="filters_json" class="form-control" rows="4" required placeholder='{"stream":"engineering", "state":"maharashtra"}'><?php echo htmlspecialchars($edit_seg['filters_json']??''); ?></textarea>
                            <div style="font-size:0.75rem; color:#64748b; margin-top:4px;">Define query conditions to filter users.</div>
                        </div>

                        <div class="form-group">
                            <label>Refresh Schedule (Cron)</label>
                            <input type="text" name="refresh_schedule" class="form-control" value="<?php echo htmlspecialchars($edit_seg['refresh_schedule']??'0 0 * * *'); ?>" placeholder="0 0 * * *">
                            <div style="font-size:0.75rem; color:#64748b; margin-top:4px;">How often to re-evaluate this segment.</div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;"><i class="ph ph-floppy-disk"></i> Save Segment</button>
                        <?php if($edit_seg): ?>
                        <a href="audience_segments.php" class="btn" style="display:block; text-align:center; margin-top:10px; background:#f1f5f9; text-decoration:none; color:var(--text-color); padding:10px; border-radius:8px;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- List Panel -->
                <div class="panel">
                    <h3>Active Segments (<?php echo count($segments); ?>)</h3>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead><tr><th>Name</th><th>Users (Computed)</th><th>Schedule</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($segments as $s): ?>
                                <tr>
                                    <td style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($s['segment_name']); ?></td>
                                    <td><span class="badge" style="background:#e0e7ff;color:#3730a3;"><i class="ph ph-users"></i> <?php echo number_format($s['user_count']); ?></span></td>
                                    <td style="font-family:monospace; font-size:0.8rem; color:#64748b;"><?php echo htmlspecialchars($s['refresh_schedule']); ?></td>
                                    <td>
                                        <a href="?edit_id=<?php echo $s['id']; ?>" style="color:var(--primary); margin-right:8px;"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?delete_id=<?php echo $s['id']; ?>" onclick="return confirm('Delete segment?');" style="color:#dc2626;"><i class="ph ph-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($segments)): ?>
                                <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No segments configured.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
