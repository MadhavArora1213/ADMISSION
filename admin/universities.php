<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

// Handle Delete/Archive
if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE universities SET status = 'archived', archived_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: universities.php?msg=archived');
    exit;
}

// Fetch Universities with City and State Names
$query = "
    SELECT c.id, c.name, c.university_type, c.status, c.is_verified, 
           ci.name as city_name, s.name as state_name 
    FROM universities c
    LEFT JOIN cities ci ON c.city_id = ci.id
    LEFT JOIN states s ON c.state_id = s.id
    WHERE c.status != 'archived'
    ORDER BY c.created_at DESC
";
$stmt = $pdo->query($query);
$universities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Universities | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        
        /* Sidebar styles matching dashboard */
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }
        
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .user-profile { display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; }
        
        .content-area { padding: 32px; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; }
        tr:hover { background-color: rgba(0,0,0,0.02); }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef08a; color: #854d0e; }
        .status-archived { background: #fee2e2; color: #991b1b; }
        
        .verified-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: #e0e7ff; color: #3730a3; }
        
        .action-links { display: flex; gap: 8px; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; color: var(--text-dark); background: #f1f5f9; border: 1px solid var(--border-color); }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.delete:hover { background: #dc2626; color: white; border-color: #dc2626; }
        
        .msg-alert { padding: 16px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 24px; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>

    <div class="admin-layout">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="topbar">
                <div class="user-profile">
                    <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <div class="avatar"><?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?></div>
                    <a href="logout.php" style="margin-left: 16px; color: #19376d;" title="Logout">
                        <i class="ph ph-sign-out" style="font-size: 1.5rem;"></i>
                    </a>
                </div>
            </header>

            <div class="content-area">
                <div class="page-header">
                    <div>
                        <h2>Manage Universities</h2>
                        <p style="color: var(--text-muted);">View, add, and manage university listings.</p>
                    </div>
                    <a href="university_form.php" class="btn btn-primary">
                        <i class="ph ph-plus"></i> Add New University
                    </a>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'archived'): ?>
                <div class="msg-alert">
                    <i class="ph ph-check-circle"></i> University has been archived successfully.
                </div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="msg-alert">
                    <i class="ph ph-check-circle"></i> University details saved successfully.
                </div>
                <?php endif; ?>

                <div class="panel">
                    <?php if(empty($universities)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding: 40px;">No universities found. Click "Add New University" to create one.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Verification</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($universities as $university): ?>
                                    <tr>
                                        <td style="font-weight: 500; color: var(--primary);">
                                            <?php echo htmlspecialchars($university['name']); ?>
                                        </td>
                                        <td style="text-transform: capitalize;">
                                            <?php echo htmlspecialchars($university['university_type']); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $loc = [];
                                            if($university['city_name']) $loc[] = $university['city_name'];
                                            if($university['state_name']) $loc[] = $university['state_name'];
                                            echo htmlspecialchars(implode(', ', $loc));
                                            if(empty($loc)) echo '<span style="color:#94a3b8;">Not set</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $university['status']; ?>">
                                                <?php echo htmlspecialchars($university['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($university['is_verified']): ?>
                                                <span class="verified-badge"><i class="ph-fill ph-seal-check"></i> Verified</span>
                                            <?php else: ?>
                                                <span style="color:#94a3b8; font-size:0.85rem;">Unverified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-links">
                                                <a href="university_form.php?id=<?php echo $university['id']; ?>" class="action-btn" title="Edit">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </a>
                                                <a href="universities.php?action=archive&id=<?php echo $university['id']; ?>" class="action-btn delete" title="Archive" onclick="return confirm('Are you sure you want to archive this university?');">
                                                    <i class="ph ph-archive"></i>
                                                </a>
                                            </div>
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
