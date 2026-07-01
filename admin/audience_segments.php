<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = $_POST['id'] ?? null;
    $raw_json = trim($_POST['filters_json'] ?? '');
    json_decode($raw_json);
    $filters_json = (json_last_error() === JSON_ERROR_NONE && !empty($raw_json)) ? $raw_json : '{}';

    $data = [
        $_POST['segment_name'], $filters_json, $_POST['refresh_schedule']
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
        body{background:var(--bg-light);margin:0}
        .admin-layout{display:flex;min-height:100vh}
        .sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto;z-index:100;transition:transform .3s ease}
        .sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}
        .sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}
        .sidebar-nav{padding:24px 0;flex:1}
        .sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s;text-decoration:none}
        .sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}
        .main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;min-width:0;padding-bottom:60px}
        .topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}
        .content-area{padding:32px}
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:12px;flex-wrap:wrap}
        .page-header h2{font-size:2rem;font-weight:800}
        .panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}
        .panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}
        .panel-body{margin-top:0;overflow-x:auto;-webkit-overflow-scrolling:touch}
        .form-layout{display:grid;grid-template-columns:350px 1fr;gap:24px}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-weight:600;margin-bottom:6px;font-size:.85rem;color:var(--text-muted)}
        .form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.9rem;box-sizing:border-box}
        table{width:100%;border-collapse:collapse;font-size:.88rem}
        th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}
        th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}
        tr:hover{background:rgba(0,0,0,.015)}
        .badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700;display:inline-block;white-space:nowrap}
        .sub-links{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
        .sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}
        .sub-link:hover,.sub-link.active{background:var(--primary);color:#fff}
        .btn{padding:10px 20px;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;border:none;display:inline-flex;align-items:center;gap:6px;text-decoration:none;white-space:nowrap;box-sizing:border-box}
        .btn-primary{background:var(--primary);color:#fff}
        .btn-primary:hover{opacity:.9}
        .msg-alert{padding:14px 20px;border-radius:8px;background:rgba(11,36,71,0.04);color:#0B2447;border:1px solid rgba(11,36,71,0.04);margin-bottom:20px}

        .mobile-menu-btn{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#0f172a;padding:4px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:90}

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .sidebar-overlay.show{display:block}
            .main-content{margin-left:0}
            .mobile-menu-btn{display:block}
            .topbar{height:auto;min-height:56px;padding:10px 12px;justify-content:space-between}
            .content-area{padding:12px}
            .page-header{flex-direction:column;align-items:flex-start}
            .page-header h2{font-size:1.3rem}
            .form-layout{grid-template-columns:1fr;gap:16px}
            .panel{padding:14px;border-radius:12px;overflow:hidden}
            .panel h3{font-size:1rem}
            th,td{padding:8px 10px;font-size:.8rem}
        }
        @media(max-width:480px){
            .content-area{padding:8px}
            .page-header h2{font-size:1.1rem}
            .panel{padding:12px}
            th,td{padding:6px 8px;font-size:.75rem}
        }
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

            <div class="form-layout">
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
                            <label>Targeting Filters</label>
                            <input type="hidden" name="filters_json" id="filters_json" value="<?php echo htmlspecialchars($edit_seg['filters_json'] ?? '{}'); ?>">
                            <div style="display:flex; gap:10px; margin-bottom:10px;">
                                <input type="text" id="f_key" class="form-control" placeholder="Property (e.g. stream)" style="flex:1;">
                                <input type="text" id="f_val" class="form-control" placeholder="Value (e.g. engineering)" style="flex:1;">
                                <button type="button" class="btn-primary" onclick="addFilter()" style="padding: 10px 15px;">Add</button>
                            </div>
                            <div id="filters_list" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
                            <div style="font-size:0.75rem; color:rgba(15,23,42,0.45); margin-top:4px;">Define properties to filter users (e.g., state, stream).</div>
                        </div>

                        <div class="form-group">
                            <label>Automatic Refresh Schedule</label>
                            <select name="refresh_schedule" class="form-control">
                                <?php $cron = $edit_seg['refresh_schedule'] ?? '0 0 * * *'; ?>
                                <option value="0 * * * *" <?php echo $cron=='0 * * * *'?'selected':''; ?>>Every Hour</option>
                                <option value="0 0 * * *" <?php echo $cron=='0 0 * * *'?'selected':''; ?>>Daily at Midnight</option>
                                <option value="0 0 * * 0" <?php echo $cron=='0 0 * * 0'?'selected':''; ?>>Weekly on Sunday</option>
                                <option value="0 0 1 * *" <?php echo $cron=='0 0 1 * *'?'selected':''; ?>>Monthly on the 1st</option>
                            </select>
                            <div style="font-size:0.75rem; color:rgba(15,23,42,0.45); margin-top:4px;">How often the system should recalculate who is in this segment.</div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;"><i class="ph ph-floppy-disk"></i> Save Segment</button>
                        <?php if($edit_seg): ?>
                        <a href="audience_segments.php" class="btn" style="display:block; text-align:center; margin-top:10px; background:#F8FAFC; text-decoration:none; color:var(--text-color); padding:10px; border-radius:8px;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- List Panel -->
                <div class="panel">
                    <h3>Active Segments (<?php echo count($segments); ?>)</h3>
                    <div class="panel-body">
                        <table style="min-width:400px;">
                            <thead><tr><th>Name</th><th>Users (Computed)</th><th>Schedule</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($segments as $s): ?>
                                <tr>
                                    <td style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($s['segment_name']); ?></td>
                                    <td><span class="badge" style="background:rgba(11,36,71,0.06);color:#19376D;"><i class="ph ph-users"></i> <?php echo number_format($s['user_count']); ?></span></td>
                                    <td style="font-family:monospace; font-size:0.8rem; color:rgba(15,23,42,0.45);"><?php echo htmlspecialchars($s['refresh_schedule']); ?></td>
                                    <td>
                                        <a href="?edit_id=<?php echo $s['id']; ?>" style="color:var(--primary); margin-right:8px;"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?delete_id=<?php echo $s['id']; ?>" onclick="return confirm('Delete segment?');" style="color:#0F172A;"><i class="ph ph-trash"></i></a>
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
<script>
// Filters JSON Manager
let filtersObj = {};
try {
    let rawF = document.getElementById('filters_json')?.value;
    if(rawF) filtersObj = JSON.parse(rawF);
    if(typeof filtersObj !== 'object' || filtersObj === null) filtersObj = {};
} catch(e) { filtersObj = {}; }

function renderFilters() {
    const list = document.getElementById('filters_list');
    if(!list) return;
    list.innerHTML = '';
    for(const [key, val] of Object.entries(filtersObj)) {
        const badge = document.createElement('div');
        badge.style.cssText = 'background:#19376D; color:#fff; padding:6px 12px; border-radius:20px; font-size:0.85rem; display:flex; align-items:center; gap:8px;';
        badge.innerHTML = `<span>${key}: <strong>${val}</strong></span> <button type="button" onclick="removeFilter('${key}')" style="background:none; border:none; color:#fff; cursor:pointer; font-weight:bold;">&times;</button>`;
        list.appendChild(badge);
    }
    document.getElementById('filters_json').value = JSON.stringify(filtersObj);
}

function addFilter() {
    const keyInput = document.getElementById('f_key');
    const valInput = document.getElementById('f_val');
    const key = keyInput.value.trim();
    const val = valInput.value.trim();
    
    if(key && val) {
        filtersObj[key] = val;
        keyInput.value = '';
        valInput.value = '';
        renderFilters();
    }
}

function removeFilter(key) {
    delete filtersObj[key];
    renderFilters();
}

if(document.getElementById('filters_json')) {
    renderFilters();
}
</script>
<script>
document.getElementById('mobile-menu-btn').addEventListener('click',function(){document.querySelector('.sidebar').classList.toggle('open');document.getElementById('sidebar-overlay').classList.toggle('show');});
document.getElementById('sidebar-overlay').addEventListener('click',function(){document.querySelector('.sidebar').classList.remove('open');this.classList.remove('show');});
</script>
</body>
</html>
