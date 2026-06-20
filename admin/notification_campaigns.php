<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? 'draft';
    $data = [
        $_POST['campaign_name'], $_POST['template_id'], $_POST['audience_segment_id'],
        $_POST['scheduled_at'] ?: null, $status
    ];
    if ($id) {
        $data[] = $id;
        $pdo->prepare("UPDATE notification_campaigns SET campaign_name=?, template_id=?, audience_segment_id=?, scheduled_at=?, status=? WHERE id=?")->execute($data);
    } else {
        $pdo->prepare("INSERT INTO notification_campaigns (campaign_name, template_id, audience_segment_id, scheduled_at, status) VALUES (?, ?, ?, ?, ?)")->execute($data);
    }
    header("Location: notification_campaigns.php?msg=saved"); exit;
}

// Handle deleting
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM notification_campaigns WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: notification_campaigns.php?msg=deleted"); exit;
}

// Fetch templates and segments for dropdowns
$templates = $pdo->query("SELECT id, template_name, channel FROM notification_templates WHERE is_active=1 ORDER BY template_name")->fetchAll();
$segments = $pdo->query("SELECT id, segment_name FROM audience_segments ORDER BY segment_name")->fetchAll();

// Fetch all campaigns
$campaigns = $pdo->query("SELECT c.*, t.template_name, t.channel, s.segment_name 
    FROM notification_campaigns c 
    LEFT JOIN notification_templates t ON c.template_id = t.id 
    LEFT JOIN audience_segments s ON c.audience_segment_id = s.id 
    ORDER BY c.created_at DESC")->fetchAll();

$edit_cmp = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM notification_campaigns WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_cmp = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaigns | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.sub-links{display:flex;gap:8px;margin-bottom:20px}.sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:rgba(0,0,0,.05);color:var(--primary)}.form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.95rem;box-sizing:border-box}.form-group{margin-bottom:16px}.form-group label{display:block;font-weight:600;margin-bottom:7px;font-size:.9rem;color:var(--text-muted)}.msg-alert{padding:14px 20px;border-radius:8px;background:rgba(11,36,71,0.04);color:#0B2447;border:1px solid rgba(11,36,71,0.04);margin-bottom:20px}
        .stats-micro {display:flex; gap:8px; font-size:0.75rem; color:rgba(15,23,42,0.45); margin-top:4px;}
        .stats-micro span {display:flex; align-items:center; gap:3px;}
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
                    <h2><i class="ph ph-megaphone" style="color:var(--primary);"></i> Campaigns</h2>
                    <p style="color:var(--text-muted);">Schedule and manage notification campaigns.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="notifications_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="notification_templates.php" class="sub-link"><i class="ph ph-file-text"></i> Templates</a>
                <a href="audience_segments.php" class="sub-link"><i class="ph ph-users-three"></i> Segments</a>
                <a href="notification_campaigns.php" class="sub-link active"><i class="ph ph-megaphone"></i> Campaigns</a>
                <a href="notification_logs.php" class="sub-link"><i class="ph ph-list-dashes"></i> Logs</a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Operation successful.</div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 350px 1fr; gap:24px;">
                <!-- Add/Edit Form -->
                <div class="panel">
                    <h3><?php echo $edit_cmp ? 'Edit Campaign' : 'Create Campaign'; ?></h3>
                    <form method="POST" action="notification_campaigns.php">
                        <input type="hidden" name="action" value="save">
                        <?php if($edit_cmp): ?><input type="hidden" name="id" value="<?php echo $edit_cmp['id']; ?>"><?php endif; ?>

                        <div class="form-group">
                            <label>Campaign Name *</label>
                            <input type="text" name="campaign_name" class="form-control" value="<?php echo htmlspecialchars($edit_cmp['campaign_name']??''); ?>" required placeholder="Diwali Discount Blast">
                        </div>
                        
                        <div class="form-group">
                            <label>Template *</label>
                            <select name="template_id" class="form-control" required>
                                <option value="">-- Select Template --</option>
                                <?php foreach($templates as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo ($edit_cmp['template_id']??'') == $t['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['template_name']); ?> (<?php echo $t['channel']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Audience Segment *</label>
                            <select name="audience_segment_id" class="form-control" required>
                                <option value="">-- Select Segment --</option>
                                <?php foreach($segments as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo ($edit_cmp['audience_segment_id']??'') == $s['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['segment_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Scheduled At</label>
                            <input type="datetime-local" name="scheduled_at" class="form-control" value="<?php echo isset($edit_cmp['scheduled_at']) ? date('Y-m-d\TH:i', strtotime($edit_cmp['scheduled_at'])) : ''; ?>">
                            <div style="font-size:0.75rem; color:rgba(15,23,42,0.45); margin-top:4px;">Leave blank to send immediately upon launch.</div>
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <?php foreach(['draft','scheduled','sending','sent','cancelled'] as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo ($edit_cmp['status']??'draft') == $opt ? 'selected' : ''; ?>><?php echo ucfirst($opt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;"><i class="ph ph-floppy-disk"></i> Save Campaign</button>
                        <?php if($edit_cmp): ?>
                        <a href="notification_campaigns.php" class="btn" style="display:block; text-align:center; margin-top:10px; background:#F8FAFC; text-decoration:none; color:var(--text-color); padding:10px; border-radius:8px;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- List Panel -->
                <div class="panel">
                    <h3>All Campaigns (<?php echo count($campaigns); ?>)</h3>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead><tr><th>Campaign</th><th>Template / Segment</th><th>Status</th><th>Performance</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($campaigns as $c): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($c['campaign_name']); ?></div>
                                        <div style="font-size:0.75rem; color:var(--text-muted);">
                                            <i class="ph ph-clock"></i> <?php echo $c['scheduled_at'] ? date('d M Y, H:i', strtotime($c['scheduled_at'])) : 'Immediate'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size:0.8rem;">
                                            <span style="color:var(--text-muted);">Tpl:</span> <?php echo htmlspecialchars($c['template_name']); ?> 
                                            <span class="badge" style="background:rgba(11,36,71,0.06);color:#19376D; padding:1px 4px; font-size:0.6rem;"><?php echo $c['channel']; ?></span>
                                        </div>
                                        <div style="font-size:0.8rem; margin-top:3px;">
                                            <span style="color:var(--text-muted);">Seg:</span> <?php echo htmlspecialchars($c['segment_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                            $color = 'rgba(15,23,42,0.65)'; $bg = '#F8FAFC';
                                            if($c['status'] == 'sent') { $color = '#0B2447'; $bg = 'rgba(11,36,71,0.04)'; }
                                            if($c['status'] == 'cancelled') { $color = '#0F172A'; $bg = 'rgba(15,23,42,0.06)'; }
                                            if($c['status'] == 'scheduled' || $c['status'] == 'sending') { $color = '#19376D'; $bg = 'rgba(11,36,71,0.04)'; }
                                        ?>
                                        <span class="badge" style="background:<?php echo $bg; ?>;color:<?php echo $color; ?>;"><?php echo ucfirst($c['status']); ?></span>
                                    </td>
                                    <td>
                                        <div class="stats-micro">
                                            <span title="Sent"><i class="ph ph-paper-plane-right" style="color:#19376D;"></i> <?php echo number_format($c['sent_count']); ?></span>
                                            <span title="Delivered"><i class="ph ph-check-circle" style="color:#0B2447;"></i> <?php echo number_format($c['delivered_count']); ?></span>
                                            <span title="Opened"><i class="ph ph-envelope-open" style="color:#0B2447;"></i> <?php echo number_format($c['opened_count']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="?edit_id=<?php echo $c['id']; ?>" style="color:var(--primary); margin-right:8px;"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?delete_id=<?php echo $c['id']; ?>" onclick="return confirm('Delete campaign?');" style="color:#0F172A;"><i class="ph ph-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($campaigns)): ?>
                                <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No campaigns configured.</td></tr>
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
