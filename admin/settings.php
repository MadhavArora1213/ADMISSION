<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    // Collect settings to update based on the tab
    $fields = [];
    $values = [];
    
    // Whitelist of allowed columns for basic update
    $allowed_cols = [
        'site_name', 'site_url', 'logo_url', 'favicon_url', 'maintenance_mode', 'maintenance_message',
        'smtp_host', 'smtp_port', 'smtp_user', 'from_email', 'from_name',
        'storage_provider', 'storage_bucket', 'cdn_url', 'payment_gateway', 'gst_rate', 'currency_default',
        'ai_provider', 'mfa_enabled', 'session_timeout_mins', 'max_login_attempts', 'api_rate_limit_per_min', 'backup_schedule', 'backup_retention_days'
    ];
    
    // Handle file uploads
    $upload_dir = '../uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $_POST['logo_url'] = $_POST['existing_logo_url'] ?? '';
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == 0) {
        $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
        $filename = 'site_logo_' . time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_dir . $filename)) {
            $_POST['logo_url'] = '/ADMISSION/uploads/' . $filename;
        }
    }
    
    $_POST['favicon_url'] = $_POST['existing_favicon_url'] ?? '';
    if (isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] == 0) {
        $ext = pathinfo($_FILES['favicon_file']['name'], PATHINFO_EXTENSION);
        $filename = 'site_favicon_' . time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['favicon_file']['tmp_name'], $upload_dir . $filename)) {
            $_POST['favicon_url'] = '/ADMISSION/uploads/' . $filename;
        }
    }

    foreach ($allowed_cols as $col) {
        if (isset($_POST[$col])) {
            $fields[] = "$col = ?";
            // Handle booleans
            if ($_POST[$col] === 'true' || $_POST[$col] === '1') $values[] = 1;
            elseif ($_POST[$col] === 'false' || $_POST[$col] === '0') $values[] = 0;
            else $values[] = $_POST[$col];
        }
    }
    
    // Encrypted fields (For demo we just store them directly, in real app encrypt them)
    $secure_cols = ['smtp_password', 'gateway_key', 'gateway_secret', 'openai_api_key', 'gemini_api_key'];
    foreach ($secure_cols as $col) {
        if (!empty($_POST[$col]) && $_POST[$col] !== '********') {
            $fields[] = "$col = ?";
            $values[] = $_POST[$col];
        }
    }
    
    // IP Whitelist
    if (isset($_POST['ip_whitelist'])) {
        $fields[] = "ip_whitelist = ?";
        $ips = array_map('trim', explode(',', $_POST['ip_whitelist']));
        $values[] = json_encode($ips);
    }
    
    if (count($fields) > 0) {
        $fields[] = "updated_by = ?";
        $values[] = $_SESSION['admin_id'];
        
        $sql = "UPDATE system_config SET " . implode(", ", $fields) . " WHERE id = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
    }
    
    header("Location: settings.php?tab=$tab&msg=updated");
    exit;
}

// Handle Generate API Key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_api_key') {
    $api_key_name = trim($_POST['api_key_name']);
    $raw_key = bin2hex(random_bytes(32)); // The key to show to the user once
    $hashed_key = password_hash($raw_key, PASSWORD_DEFAULT);
    
    $scopes = isset($_POST['scopes']) ? json_encode($_POST['scopes']) : json_encode([]);
    $expires = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    
    $stmt = $pdo->prepare("INSERT INTO api_keys (id, api_key_name, api_key_hash, api_scope, api_expires_at, created_by) VALUES (UUID(), ?, ?, ?, ?, ?)");
    $stmt->execute([$api_key_name, $hashed_key, $scopes, $expires, $_SESSION['admin_id']]);
    
    $_SESSION['new_api_key'] = $raw_key; // Pass to UI to show once
    header("Location: settings.php?tab=api_keys");
    exit;
}

// Handle Revoke API Key
if (isset($_GET['action']) && $_GET['action'] === 'revoke_key' && isset($_GET['id'])) {
    $pdo->prepare("UPDATE api_keys SET is_active = FALSE WHERE id = ?")->execute([$_GET['id']]);
    header("Location: settings.php?tab=api_keys&msg=revoked");
    exit;
}

// Fetch Config
$config = $pdo->query("SELECT * FROM system_config WHERE id = 1")->fetch() ?: [];

// Fetch API Keys
$api_keys = $pdo->query("SELECT a.*, u.full_name as creator_name FROM api_keys a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Admin Panel</title>
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
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;}
        .tab-link { padding: 10px 20px; font-weight: 600; color: var(--text-muted); font-size: 0.9rem; text-decoration: none; transition: all 0.2s; border-bottom: 3px solid transparent; }
        .tab-link:hover { color: var(--primary); }
        .tab-link.active { color: var(--primary); border-bottom-color: var(--primary); }
        
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); padding: 24px; margin-bottom: 24px;}
        
        .form-section { margin-bottom: 30px; }
        .form-section h3 { font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 20px; color: var(--text-dark);}
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;}
        .form-group input[type="text"], .form-group input[type="password"], .form-group input[type="number"], .form-group select, .form-group textarea { padding: 12px; font-size: 0.95rem; border: 1px solid var(--border-color); border-radius: 8px; background: #fff;}
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary); outline: none; }
        
        .btn-primary { padding: 12px 24px; font-size: 0.95rem; background: var(--primary); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;}
        .btn-primary:hover { opacity: 0.9; }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 20px; border: 1px solid #bbf7d0; }
        .msg-warning { padding: 14px 20px; border-radius: 8px; background: #fef9c3; color: #854d0e; margin-bottom: 20px; border: 1px solid #fde047; }
        
        /* API Keys Table */
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top;}
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; background: #f8fafc; }
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block;}
        .s-active { background:#dcfce7; color:#166534; }
        .s-revoked { background:#fee2e2; color:#dc2626; }
        .btn-action { padding: 6px 10px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; color: var(--text-dark); text-decoration: none;}
        .btn-action:hover { background: #f1f5f9; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-gear" style="color:var(--primary);"></i> System Settings</h2>
                    <p style="color:var(--text-muted);">Manage global configurations and API access keys.</p>
                </div>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg']=='updated'): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Settings updated successfully.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg']=='revoked'): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> API Key revoked successfully.</div>
            <?php endif; ?>

            <div class="filter-bar">
                <a href="?tab=general" class="tab-link <?php echo $tab=='general'?'active':''; ?>">General & SMTP</a>
                <a href="?tab=storage" class="tab-link <?php echo $tab=='storage'?'active':''; ?>">Storage & Payment</a>
                <a href="?tab=security" class="tab-link <?php echo $tab=='security'?'active':''; ?>">AI & Security</a>
                <a href="?tab=api_keys" class="tab-link <?php echo $tab=='api_keys'?'active':''; ?>">API Keys</a>
            </div>

            <?php if($tab !== 'api_keys'): ?>
            <form method="POST" class="panel" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_settings">
                
                <?php if($tab === 'general'): ?>
                <div class="form-section">
                    <h3>General Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Site Name</label>
                            <input type="text" name="site_name" value="<?php echo htmlspecialchars($config['site_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Site URL</label>
                            <input type="text" name="site_url" value="<?php echo htmlspecialchars($config['site_url'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Logo URL</label>
                            <?php if(!empty($config['logo_url'])): ?>
                                <div style="margin-bottom: 8px;"><img src="<?php echo htmlspecialchars($config['logo_url']); ?>" style="height: 40px; border-radius: 4px; border: 1px solid #ccc;"></div>
                            <?php endif; ?>
                            <input type="hidden" name="existing_logo_url" value="<?php echo htmlspecialchars($config['logo_url'] ?? ''); ?>">
                            <input type="file" name="logo_file" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>Favicon URL</label>
                            <?php if(!empty($config['favicon_url'])): ?>
                                <div style="margin-bottom: 8px;"><img src="<?php echo htmlspecialchars($config['favicon_url']); ?>" style="height: 32px; border-radius: 4px; border: 1px solid #ccc;"></div>
                            <?php endif; ?>
                            <input type="hidden" name="existing_favicon_url" value="<?php echo htmlspecialchars($config['favicon_url'] ?? ''); ?>">
                            <input type="file" name="favicon_file" accept="image/*">
                        </div>
                        <div class="form-group full">
                            <label style="display:flex; align-items:center; gap:8px;">
                                <input type="checkbox" name="maintenance_mode" value="1" <?php echo !empty($config['maintenance_mode']) ? 'checked' : ''; ?>>
                                Enable Maintenance Mode
                            </label>
                        </div>
                        <div class="form-group full">
                            <label>Maintenance Message</label>
                            <textarea name="maintenance_message" rows="3"><?php echo htmlspecialchars($config['maintenance_message'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>SMTP Configuration</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($config['smtp_host'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>SMTP Port</label>
                            <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($config['smtp_port'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>SMTP User</label>
                            <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($config['smtp_user'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>SMTP Password</label>
                            <input type="password" name="smtp_password" value="<?php echo !empty($config['smtp_password']) ? '********' : ''; ?>" placeholder="Leave blank to keep unchanged">
                        </div>
                        <div class="form-group">
                            <label>From Email</label>
                            <input type="text" name="from_email" value="<?php echo htmlspecialchars($config['from_email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>From Name</label>
                            <input type="text" name="from_name" value="<?php echo htmlspecialchars($config['from_name'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($tab === 'storage'): ?>
                <div class="form-section">
                    <h3>Storage & CDN</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Storage Provider</label>
                            <select name="storage_provider">
                                <?php foreach(['local','s3','cloudinary','gcs','r2'] as $v): ?>
                                <option value="<?php echo $v; ?>" <?php echo ($config['storage_provider']??'')==$v?'selected':''; ?>><?php echo strtoupper($v); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Storage Bucket (if applicable)</label>
                            <input type="text" name="storage_bucket" value="<?php echo htmlspecialchars($config['storage_bucket'] ?? ''); ?>">
                        </div>
                        <div class="form-group full">
                            <label>CDN URL</label>
                            <input type="text" name="cdn_url" value="<?php echo htmlspecialchars($config['cdn_url'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Payment Gateways</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Primary Gateway</label>
                            <select name="payment_gateway">
                                <?php foreach(['none','razorpay','stripe','paytm','cashfree'] as $v): ?>
                                <option value="<?php echo $v; ?>" <?php echo ($config['payment_gateway']??'')==$v?'selected':''; ?>><?php echo ucfirst($v); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Currency</label>
                            <input type="text" name="currency_default" value="<?php echo htmlspecialchars($config['currency_default'] ?? 'INR'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Gateway API Key</label>
                            <input type="password" name="gateway_key" value="<?php echo !empty($config['gateway_key']) ? '********' : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Gateway API Secret</label>
                            <input type="password" name="gateway_secret" value="<?php echo !empty($config['gateway_secret']) ? '********' : ''; ?>">
                        </div>
                        <div class="form-group full">
                            <label>Default GST Rate (Decimal e.g. 0.18)</label>
                            <input type="text" name="gst_rate" value="<?php echo htmlspecialchars($config['gst_rate'] ?? '0.18'); ?>">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($tab === 'security'): ?>
                <div class="form-section">
                    <h3>AI Integrations</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Active AI Provider</label>
                            <select name="ai_provider">
                                <?php foreach(['openai','gemini','anthropic','ollama'] as $v): ?>
                                <option value="<?php echo $v; ?>" <?php echo ($config['ai_provider']??'')==$v?'selected':''; ?>><?php echo ucfirst($v); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <!-- spacer -->
                        </div>
                        <div class="form-group">
                            <label>OpenAI API Key</label>
                            <input type="password" name="openai_api_key" value="<?php echo !empty($config['openai_api_key']) ? '********' : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Gemini API Key</label>
                            <input type="password" name="gemini_api_key" value="<?php echo !empty($config['gemini_api_key']) ? '********' : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Security & Limits</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label style="display:flex; align-items:center; gap:8px;">
                                <input type="checkbox" name="mfa_enabled" value="1" <?php echo !empty($config['mfa_enabled']) ? 'checked' : ''; ?>>
                                Require MFA for Admins
                            </label>
                        </div>
                        <div class="form-group">
                            <label>Session Timeout (Minutes)</label>
                            <input type="number" name="session_timeout_mins" value="<?php echo htmlspecialchars($config['session_timeout_mins'] ?? '60'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Max Login Attempts</label>
                            <input type="number" name="max_login_attempts" value="<?php echo htmlspecialchars($config['max_login_attempts'] ?? '5'); ?>">
                        </div>
                        <div class="form-group">
                            <label>API Rate Limit (per min)</label>
                            <input type="number" name="api_rate_limit_per_min" value="<?php echo htmlspecialchars($config['api_rate_limit_per_min'] ?? '60'); ?>">
                        </div>
                        <div class="form-group full">
                            <label>Admin IP Whitelist (Comma separated)</label>
                            <?php 
                                $ips = json_decode($config['ip_whitelist'] ?? '[]', true) ?: [];
                                $ips_str = implode(', ', $ips);
                            ?>
                            <textarea name="ip_whitelist" rows="2" placeholder="Leave blank to allow all IPs"><?php echo htmlspecialchars($ips_str); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>System Backups</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Backup Schedule (Cron)</label>
                            <input type="text" name="backup_schedule" value="<?php echo htmlspecialchars($config['backup_schedule'] ?? '0 0 * * *'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Retention Days</label>
                            <input type="number" name="backup_retention_days" value="<?php echo htmlspecialchars($config['backup_retention_days'] ?? '30'); ?>">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div style="text-align: right;">
                    <button type="submit" class="btn-primary"><i class="ph ph-floppy-disk"></i> Save Settings</button>
                </div>
            </form>
            <?php endif; ?>

            <?php if($tab === 'api_keys'): ?>
                <?php if(isset($_SESSION['new_api_key'])): ?>
                <div class="msg-warning" style="background:#fefce8; border-color:#ca8a04; color:#854d0e;">
                    <strong><i class="ph ph-warning"></i> IMPORTANT!</strong> Please copy your new API Key now. You will not be able to see it again!
                    <div style="font-family:monospace; background:#fff; padding:10px; margin-top:10px; font-size:1.1rem; border:1px solid #fde047; word-break:break-all;">
                        <?php echo $_SESSION['new_api_key']; ?>
                    </div>
                </div>
                <?php unset($_SESSION['new_api_key']); endif; ?>

                <div class="panel" style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h3 style="margin-bottom:4px;">Manage API Keys</h3>
                        <p style="font-size:0.85rem; color:var(--text-muted);">Create keys for external integrations (Zapier, mobile apps, etc.)</p>
                    </div>
                    <form method="POST" style="display:flex; gap:10px;">
                        <input type="hidden" name="action" value="generate_api_key">
                        <input type="text" name="api_key_name" placeholder="Key Name (e.g. Zapier)" required style="padding:10px; border:1px solid var(--border-color); border-radius:8px;">
                        <button type="submit" class="btn-primary"><i class="ph ph-key"></i> Generate Key</button>
                    </form>
                </div>

                <div class="panel">
                    <?php if(empty($api_keys)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:40px;">No API keys generated.</p>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Key Name</th>
                                    <th>Prefix</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($api_keys as $k): ?>
                                <tr>
                                    <td style="font-weight:700;"><?php echo htmlspecialchars($k['api_key_name']); ?></td>
                                    <td style="font-family:monospace; color:var(--text-muted);">******</td>
                                    <td>
                                        <?php if($k['is_active']): ?>
                                            <span class="badge s-active">Active</span>
                                        <?php else: ?>
                                            <span class="badge s-revoked">Revoked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($k['creator_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($k['created_at'])); ?></td>
                                    <td>
                                        <?php if($k['is_active']): ?>
                                            <a href="?tab=api_keys&action=revoke_key&id=<?php echo $k['id']; ?>" class="btn-action" onclick="return confirm('Revoke this API Key? It will immediately stop working.')" style="color:#dc2626; border-color:#fca5a5;">Revoke</a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
