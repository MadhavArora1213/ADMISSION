<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Hardcoded modules for demonstration
$modules = [
    'colleges' => 'Colleges & Universities',
    'courses' => 'Courses & Exams',
    'articles' => 'CMS & Articles',
    'users' => 'User Management',
    'moderation' => 'Security & Moderation',
    'community' => 'Community & Q&A',
    'billing' => 'Billing & Revenue',
    'analytics' => 'Analytics & Reports'
];

$actions = ['read', 'write', 'delete'];

// Handle Role Creation/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_role') {
    $role_name = trim($_POST['role_name']);
    $role_id = $_POST['role_id'] ?? null;
    
    // Build JSON permissions
    $permissions = [];
    if (isset($_POST['perms']) && is_array($_POST['perms'])) {
        foreach ($_POST['perms'] as $module => $mod_actions) {
            $permissions[$module] = array_keys($mod_actions);
        }
    }
    $permissions_json = json_encode($permissions);
    
    if ($role_id) {
        $stmt = $pdo->prepare("UPDATE roles SET role_name = ?, permissions = ? WHERE id = ?");
        $stmt->execute([$role_name, $permissions_json, $role_id]);
        $msg = "Role updated.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO roles (id, role_name, permissions) VALUES (UUID(), ?, ?)");
        $stmt->execute([$role_name, $permissions_json]);
        $msg = "Role created.";
    }
    header("Location: roles.php?msg=" . urlencode($msg));
    exit;
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: roles.php?msg=deleted");
    exit;
}

// Fetch all roles
$stmt = $pdo->query("SELECT * FROM roles ORDER BY created_at DESC");
$roles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles & Permissions | Admin Panel</title>
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
        
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px;}
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top;}
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; background: #f1f5f9; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 20px; border: 1px solid #bbf7d0; }
        
        .btn-primary { padding: 10px 20px; font-size: 0.9rem; background: var(--primary); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;}
        .btn-primary:hover { opacity: 0.9; }
        .btn-action { padding: 6px 10px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; color: var(--text-dark); text-decoration: none;}
        .btn-action:hover { background: #f1f5f9; }
        .btn-danger:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; padding: 30px; border-radius: 12px; width: 600px; max-width: 90%; max-height: 90vh; overflow-y: auto;}
        .modal-header { font-size: 1.25rem; font-weight: 800; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;}
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;}
        .form-group input[type="text"] { width: 100%; padding: 10px; font-size: 0.9rem; border: 1px solid var(--border-color); border-radius: 8px; }
        
        .perm-grid { display: grid; grid-template-columns: 1fr auto; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 10px; margin-top: 10px;}
        .perm-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed var(--border-color);}
        .perm-checks { display: flex; gap: 15px; }
        .perm-checks label { display: flex; align-items: center; gap: 4px; font-size: 0.85rem; text-transform: capitalize; font-weight: 400; color: var(--text-dark);}
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
                    <h2><i class="ph ph-shield-star" style="color:var(--primary);"></i> Roles & Permissions</h2>
                    <p style="color:var(--text-muted);">Define custom roles and their access levels across the system.</p>
                </div>
                <button class="btn-primary" onclick="openModal()"><i class="ph ph-plus"></i> Create Role</button>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>

            <div class="panel">
                <?php if(empty($roles)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:40px;">No roles defined.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th>Modules Accessed</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($roles as $r): 
                                $perms = json_decode($r['permissions'], true) ?? [];
                                $mod_names = [];
                                foreach($perms as $m => $acts) {
                                    if(isset($modules[$m]) && !empty($acts)) {
                                        $mod_names[] = $modules[$m];
                                    }
                                }
                            ?>
                            <tr>
                                <td style="font-weight:700; color: var(--primary);"><?php echo htmlspecialchars($r['role_name']); ?></td>
                                <td style="font-size:0.8rem; color:var(--text-muted); line-height:1.5;">
                                    <?php 
                                    if (isset($perms['all'])) {
                                        echo '<span style="color:#166534; font-weight:700;">Full Access (Super Admin)</span>';
                                    } else {
                                        echo implode(', ', $mod_names) ?: 'None'; 
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                                <td>
                                    <?php if(!isset($perms['all'])): ?>
                                        <button class="btn-action" onclick="editRole('<?php echo $r['id']; ?>', '<?php echo htmlspecialchars($r['role_name']); ?>', '<?php echo htmlspecialchars($r['permissions'], ENT_QUOTES); ?>')"><i class="ph ph-pencil-simple"></i> Edit</button>
                                        <a href="?action=delete&id=<?php echo $r['id']; ?>" class="btn-action btn-danger" onclick="return confirm('Delete this role?')"><i class="ph ph-trash"></i> Delete</a>
                                    <?php else: ?>
                                        <span style="font-size:0.8rem; color:var(--text-muted);">System Role</span>
                                    <?php endif; ?>
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

<!-- Modal -->
<div id="roleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span id="modalTitle">Create New Role</span>
            <button onclick="closeModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>
        <form method="POST" id="roleForm">
            <input type="hidden" name="action" value="save_role">
            <input type="hidden" name="role_id" id="role_id" value="">
            
            <div class="form-group">
                <label>Role Name</label>
                <input type="text" name="role_name" id="role_name" required placeholder="e.g. Content Moderator">
            </div>
            
            <div class="form-group" style="margin-top:20px;">
                <label>Module Permissions</label>
                <div class="perm-grid">
                    <?php foreach($modules as $key => $label): ?>
                    <div class="perm-row">
                        <div style="font-weight:600; font-size:0.9rem;"><?php echo $label; ?></div>
                        <div class="perm-checks">
                            <?php foreach($actions as $act): ?>
                            <label>
                                <input type="checkbox" name="perms[<?php echo $key; ?>][<?php echo $act; ?>]" id="chk_<?php echo $key; ?>_<?php echo $act; ?>">
                                <?php echo ucfirst($act); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div style="margin-top:24px; text-align:right;">
                <button type="button" class="btn-action" style="padding:10px 20px;" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary" style="margin-left:10px;">Save Role</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('roleModal').classList.add('active');
    document.getElementById('modalTitle').innerText = 'Create New Role';
    document.getElementById('role_id').value = '';
    document.getElementById('role_name').value = '';
    document.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);
}

function closeModal() {
    document.getElementById('roleModal').classList.remove('active');
}

function editRole(id, name, permsJson) {
    openModal();
    document.getElementById('modalTitle').innerText = 'Edit Role';
    document.getElementById('role_id').value = id;
    document.getElementById('role_name').value = name;
    
    try {
        const perms = JSON.parse(permsJson);
        for (const module in perms) {
            perms[module].forEach(act => {
                const chk = document.getElementById(`chk_${module}_${act}`);
                if (chk) chk.checked = true;
            });
        }
    } catch(e) {}
}
</script>
</body>
</html>
