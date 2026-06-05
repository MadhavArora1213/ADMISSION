<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $id = $_POST['id'] ?? null;
    $raw_json = trim($_POST['variables_json'] ?? '');
    json_decode($raw_json);
    $variables_json = (json_last_error() === JSON_ERROR_NONE && !empty($raw_json)) ? $raw_json : '[]';
    $data = [
        $_POST['template_name'], $_POST['channel'], $_POST['subject'],
        $_POST['body_html'], $_POST['body_text'], $variables_json,
        $_POST['language'], isset($_POST['is_active']) ? 1 : 0, $_POST['category']
    ];
    if ($id) {
        $data[] = $id;
        $pdo->prepare("UPDATE notification_templates SET template_name=?, channel=?, subject=?, body_html=?, body_text=?, variables_json=?, language=?, is_active=?, category=? WHERE id=?")->execute($data);
    } else {
        $pdo->prepare("INSERT INTO notification_templates (template_name, channel, subject, body_html, body_text, variables_json, language, is_active, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute($data);
    }
    header("Location: notification_templates.php?msg=saved"); exit;
}

// Handle deleting
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM notification_templates WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: notification_templates.php?msg=deleted"); exit;
}

// Handle toggle active
if (isset($_GET['toggle_active_id'])) {
    $pdo->prepare("UPDATE notification_templates SET is_active = NOT is_active WHERE id = ?")->execute([$_GET['toggle_active_id']]);
    header("Location: notification_templates.php"); exit;
}

// Fetch all templates
$templates = $pdo->query("SELECT * FROM notification_templates ORDER BY created_at DESC")->fetchAll();
$edit_tpl = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM notification_templates WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_tpl = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Templates | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.sub-links{display:flex;gap:8px;margin-bottom:20px}.sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:rgba(0,0,0,.05);color:var(--primary)}.form-control{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-family:inherit;font-size:.95rem;box-sizing:border-box}.form-group{margin-bottom:16px}.form-group label{display:block;font-weight:600;margin-bottom:7px;font-size:.9rem;color:var(--text-muted)}.msg-alert{padding:14px 20px;border-radius:8px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;margin-bottom:20px}.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
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
                    <h2><i class="ph ph-file-text" style="color:var(--primary);"></i> Notification Templates</h2>
                    <p style="color:var(--text-muted);">Create reusable templates for emails, SMS, push, and WhatsApp.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="notifications_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="notification_templates.php" class="sub-link active"><i class="ph ph-file-text"></i> Templates</a>
                <a href="audience_segments.php" class="sub-link"><i class="ph ph-users-three"></i> Segments</a>
                <a href="notification_campaigns.php" class="sub-link"><i class="ph ph-megaphone"></i> Campaigns</a>
                <a href="notification_logs.php" class="sub-link"><i class="ph ph-list-dashes"></i> Logs</a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Operation successful.</div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 450px 1fr; gap:24px;">
                <!-- Add/Edit Form -->
                <div class="panel">
                    <h3><?php echo $edit_tpl ? 'Edit Template' : 'Add New Template'; ?></h3>
                    <form method="POST" action="notification_templates.php">
                        <input type="hidden" name="action" value="save">
                        <?php if($edit_tpl): ?><input type="hidden" name="id" value="<?php echo $edit_tpl['id']; ?>"><?php endif; ?>

                        <div class="form-group">
                            <label>Template Name *</label>
                            <input type="text" name="template_name" class="form-control" value="<?php echo htmlspecialchars($edit_tpl['template_name']??''); ?>" required placeholder="Welcome Email">
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Channel</label>
                                <select name="channel" class="form-control">
                                    <?php foreach(['email','sms','push','whatsapp','in_app'] as $opt): ?>
                                    <option value="<?php echo $opt; ?>" <?php echo ($edit_tpl['channel']??'') == $opt ? 'selected' : ''; ?>><?php echo ucfirst($opt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category" class="form-control">
                                    <?php foreach(['transactional','marketing','alert'] as $opt): ?>
                                    <option value="<?php echo $opt; ?>" <?php echo ($edit_tpl['category']??'') == $opt ? 'selected' : ''; ?>><?php echo ucfirst($opt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Language</label>
                            <select name="language" class="form-control">
                                <option value="en" <?php echo ($edit_tpl['language']??'') == 'en' ? 'selected' : ''; ?>>English</option>
                                <option value="hi" <?php echo ($edit_tpl['language']??'') == 'hi' ? 'selected' : ''; ?>>Hindi</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Subject (for Email/Push)</label>
                            <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($edit_tpl['subject']??''); ?>" placeholder="Welcome to Admission.com!">
                        </div>

                        <div class="form-group">
                            <label>Body (HTML / Handlebars)</label>
                            <textarea name="body_html" id="body_html" class="form-control" rows="4" placeholder="<h1>Hi {{name}}</h1>..."><?php echo htmlspecialchars($edit_tpl['body_html']??''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Body Text (SMS Fallback)</label>
                            <textarea name="body_text" class="form-control" rows="3" placeholder="Hi {{name}}, welcome to Admission.com..."><?php echo htmlspecialchars($edit_tpl['body_text']??''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Variables JSON Example</label>
                            <textarea name="variables_json" class="form-control" rows="2" placeholder='[{"name": "John"}]'><?php echo htmlspecialchars($edit_tpl['variables_json']??''); ?></textarea>
                        </div>

                        <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="is_active" id="is_active" <?php echo !isset($edit_tpl) || $edit_tpl['is_active'] ? 'checked' : ''; ?>>
                            <label for="is_active" style="margin:0;">Active Template</label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;"><i class="ph ph-floppy-disk"></i> Save Template</button>
                        <?php if($edit_tpl): ?>
                        <a href="notification_templates.php" class="btn" style="display:block; text-align:center; margin-top:10px; background:#f1f5f9; text-decoration:none; color:var(--text-color); padding:10px; border-radius:8px;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- List Panel -->
                <div class="panel">
                    <h3>Active Templates (<?php echo count($templates); ?>)</h3>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead><tr><th>Name</th><th>Channel</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($templates as $t): ?>
                                <tr>
                                    <td style="font-weight:600; color:var(--primary);">
                                        <?php echo htmlspecialchars($t['template_name']); ?>
                                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:400;"><?php echo htmlspecialchars($t['subject']??''); ?></div>
                                    </td>
                                    <td><span class="badge" style="background:#e0e7ff;color:#3730a3;"><?php echo ucfirst($t['channel']); ?></span></td>
                                    <td><span class="badge" style="background:#f1f5f9;color:#475569;"><?php echo ucfirst($t['category']); ?></span></td>
                                    <td>
                                        <a href="?toggle_active_id=<?php echo $t['id']; ?>" style="text-decoration:none;">
                                            <?php if($t['is_active']): ?><i class="ph-fill ph-check-circle" style="color:#166534;font-size:1.2rem;"></i>
                                            <?php else: ?><i class="ph-fill ph-minus-circle" style="color:#dc2626;font-size:1.2rem;"></i><?php endif; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="?edit_id=<?php echo $t['id']; ?>" style="color:var(--primary); margin-right:8px;"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?delete_id=<?php echo $t['id']; ?>" onclick="return confirm('Delete template?');" style="color:#dc2626;"><i class="ph ph-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($templates)): ?>
                                <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No templates configured.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
<script>
    $('#body_html').trumbowyg({
        semantic: false
    });
</script>
</body>
</html>
