<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle Action (Suspend/Activate)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    if ($action === 'suspend') {
        $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?")->execute([$id]);
        $msg = "User suspended.";
    } elseif ($action === 'activate') {
        $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$id]);
        $msg = "User activated.";
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        $msg = "User deleted.";
    }
    header("Location: users.php?msg=" . urlencode($msg));
    exit;
}

// Handle Create User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_id = $_POST['role_id'] ?: null;
    $is_super_admin = isset($_POST['is_super_admin']) ? 1 : 0;
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header("Location: users.php?msg=" . urlencode("Error: Email already exists"));
        exit;
    }
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, password_hash, role_id, is_super_admin, status, auth_provider) VALUES (UUID(), ?, ?, ?, ?, ?, 'active', 'email')");
    $stmt->execute([$full_name, $email, $password_hash, $role_id, $is_super_admin]);
    
    header("Location: users.php?msg=" . urlencode("Admin user created successfully"));
    exit;
}

// Handle Role Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_role') {
    $user_id = $_POST['user_id'];
    $role_id = $_POST['role_id'];
    $is_super_admin = isset($_POST['is_super_admin']) ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE users SET role_id = ?, is_super_admin = ? WHERE id = ?");
    $stmt->execute([$role_id ?: null, $is_super_admin, $user_id]);
    
    header("Location: users.php?msg=Role assigned successfully");
    exit;
}

// Fetch Roles for dropdown
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll();

// Fetch Users
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = "1=1";
$params = [];
if ($search) {
    $where .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT u.*, r.role_name 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    WHERE $where 
    ORDER BY u.created_at DESC 
    LIMIT 100
");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Admin Panel</title>
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
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; background: #F8FAFC; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 20px; border: 1px solid rgba(11,36,71,0.04); }
        
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap; }
        .s-active { background:rgba(11,36,71,0.04); color:#0B2447; }
        .s-suspended { background:rgba(15,23,42,0.06); color:#0F172A; }
        .s-pending_verification { background:rgba(11,36,71,0.04); color:#0F172A; }
        
        .btn-action { padding: 6px 10px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; color: var(--text-dark); text-decoration: none;}
        .btn-action:hover { background: #F8FAFC; }
        
        .search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 7px 14px; width: 300px;}
        .search-box input { border: none; outline: none; font-size: 0.9rem; width: 100%; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; padding: 30px; border-radius: 12px; width: 400px; max-width: 90%; }
        .modal-header { font-size: 1.25rem; font-weight: 800; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;}
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;}
        .form-group select { width: 100%; padding: 10px; font-size: 0.9rem; border: 1px solid var(--border-color); border-radius: 8px; background: #fff;}
        .btn-primary { padding: 10px 20px; font-size: 0.9rem; background: var(--primary); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;}
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
                    <h2><i class="ph ph-users" style="color:var(--primary);"></i> User Management</h2>
                    <p style="color:var(--text-muted);">Manage all users and assign administrative roles.</p>
                </div>
                <div style="display:flex; align-items:center; gap: 16px;">
                    <button class="btn-primary" onclick="openCreateUserModal()"><i class="ph ph-plus"></i> Create Admin User</button>
                    <form method="GET">
                        <div class="search-box">
                            <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                            <input type="text" name="q" placeholder="Search name or email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </form>
                </div>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>

            <div class="panel">
                <?php if(empty($users)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:40px;">No users found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Status & Role</th>
                                <th>Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:var(--primary);"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">Auth: <?php echo ucfirst($u['auth_provider']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($u['email']); ?> <?php if($u['email_verified']) echo '<i class="ph-fill ph-check-circle" style="color:#0B2447;" title="Verified"></i>'; ?></div>
                                    <div style="font-size:0.85rem; color:var(--text-muted);"><?php echo htmlspecialchars($u['phone']); ?></div>
                                </td>
                                <td>
                                    <div style="margin-bottom:6px;">
                                        <span class="badge s-<?php echo $u['status']; ?>"><?php echo ucfirst(str_replace('_',' ',$u['status'])); ?></span>
                                    </div>
                                    <div>
                                        <?php if($u['is_super_admin']): ?>
                                            <span style="font-weight:700; color:#0B2447; font-size:0.85rem;">Super Admin</span>
                                        <?php elseif($u['role_name']): ?>
                                            <span style="font-weight:700; color:#19376D; font-size:0.85rem;"><?php echo htmlspecialchars($u['role_name']); ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted); font-size:0.85rem;">Standard User</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:0.85rem;">Logins: <?php echo number_format($u['login_count']); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">Last: <?php echo $u['last_login_at'] ? date('M d, Y', strtotime($u['last_login_at'])) : 'Never'; ?></div>
                                </td>
                                <td>
                                    <button class="btn-action" onclick="openRoleModal('<?php echo $u['id']; ?>', '<?php echo htmlspecialchars($u['full_name']); ?>', '<?php echo $u['role_id']; ?>', <?php echo $u['is_super_admin'] ? 'true' : 'false'; ?>)"><i class="ph ph-shield-star"></i> Assign Role</button>
                                    
                                    <?php if($u['status'] === 'active'): ?>
                                        <a href="?action=suspend&id=<?php echo $u['id']; ?>" class="btn-action" onclick="return confirm('Suspend this user?')" style="color:#0F172A; border-color:rgba(15,23,42,0.06);"><i class="ph ph-prohibit"></i> Suspend</a>
                                    <?php else: ?>
                                        <a href="?action=activate&id=<?php echo $u['id']; ?>" class="btn-action" style="color:#0B2447; border-color:rgba(11,36,71,0.06);"><i class="ph ph-check-circle"></i> Activate</a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?php echo $u['id']; ?>" class="btn-action" onclick="return confirm('Are you sure you want to permanently delete this user?')" style="color:#0B2447; border-color:rgba(15,23,42,0.06);"><i class="ph ph-trash"></i> Delete</a>
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

<!-- Assign Role Modal -->
<div id="roleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>Assign Role</span>
            <button onclick="closeModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="assign_role">
            <input type="hidden" name="user_id" id="modal_user_id" value="">
            
            <p style="margin-bottom:15px; font-weight:600;" id="modal_user_name"></p>
            
            <div class="form-group">
                <label>Select Role</label>
                <select name="role_id" id="modal_role_id">
                    <option value="">-- No Admin Role (Standard User) --</option>
                    <?php foreach($roles as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['role_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-top:20px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="is_super_admin" id="modal_is_super_admin" value="1">
                    <span style="color:#0B2447; font-weight:800;">Grant Super Admin Access</span>
                </label>
                <p style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">Warning: Super Admins bypass all role restrictions and have full access to the system.</p>
            </div>
            
            <div style="margin-top:24px; text-align:right;">
                <button type="button" class="btn-action" style="padding:10px 20px;" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary" style="margin-left:10px;">Save Role</button>
            </div>
        </form>
    </div>
</div>

<!-- Create User Modal -->
<div id="createUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>Create Admin User</span>
            <button onclick="closeCreateUserModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_user">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--border-color); font-size:0.9rem;">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--border-color); font-size:0.9rem;">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--border-color); font-size:0.9rem;">
            </div>
            <div class="form-group">
                <label>Assign Role</label>
                <select name="role_id" required>
                    <option value="">-- Select an Admin Role --</option>
                    <?php foreach($roles as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['role_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-top:20px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="is_super_admin" value="1">
                    <span style="color:#0B2447; font-weight:800; text-transform:none;">Grant Super Admin Access</span>
                </label>
                <p style="font-size:0.75rem; color:var(--text-muted); margin-top:4px; text-transform:none;">Super Admins bypass role restrictions and have full system access.</p>
            </div>
            
            <div style="margin-top:24px; text-align:right;">
                <button type="button" class="btn-action" style="padding:10px 20px;" onclick="closeCreateUserModal()">Cancel</button>
                <button type="submit" class="btn-primary" style="margin-left:10px;">Create User</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRoleModal(userId, userName, roleId, isSuperAdmin) {
    document.getElementById('roleModal').classList.add('active');
    document.getElementById('modal_user_id').value = userId;
    document.getElementById('modal_user_name').innerText = "User: " + userName;
    document.getElementById('modal_role_id').value = roleId || '';
    document.getElementById('modal_is_super_admin').checked = isSuperAdmin;
}

function closeModal() {
    document.getElementById('roleModal').classList.remove('active');
}

function openCreateUserModal() {
    document.getElementById('createUserModal').classList.add('active');
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').classList.remove('active');
}
</script>
</body>
</html>
