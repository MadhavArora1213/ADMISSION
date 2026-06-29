<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: exams.php?msg=deleted');
    exit;
}

// Fetch Exams
$query = "SELECT * FROM exams ORDER BY created_at DESC";
$stmt = $pdo->query($query);
$exams = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .user-profile { display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
        .content-area { padding: 32px; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; min-width: 550px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; }
        tr:hover { background-color: rgba(0,0,0,0.02); }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; white-space: nowrap; }
        .status-active { background: rgba(11,36,71,0.04); color: #0B2447; }
        .status-upcoming { background: rgba(11,36,71,0.06); color: #19376D; }
        .status-completed { background: #F8FAFC; color: rgba(15,23,42,0.65); }
        .level-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: rgba(11,36,71,0.04); color: #19376D; border: 1px solid rgba(11,36,71,0.08); white-space: nowrap; }
        .action-links { display: flex; gap: 8px; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; color: var(--text-dark); background: #F8FAFC; border: 1px solid var(--border-color); }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.delete:hover { background: #0F172A; color: white; border-color: #0F172A; }
        .msg-alert { padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 24px; border: 1px solid rgba(11,36,71,0.04); }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .topbar { height: 56px; padding: 0 12px; justify-content: space-between; }
            .mobile-menu-btn { display: block; }
            .content-area { padding: 12px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .page-header h2 { font-size: 1.4rem; }
            .page-header .btn { width: 100%; justify-content: center; }
            .panel { padding: 14px; }
            th, td { padding: 10px 12px; font-size: 0.85rem; }
            .avatar { width: 34px; height: 34px; font-size: 0.85rem; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
            .panel { padding: 10px; }
            .page-header h2 { font-size: 1.2rem; }
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
                    <div class="avatar"><?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?></div>
                    <a href="logout.php" style="margin-left: 16px; color: #19376d;" title="Logout">
                        <i class="ph ph-sign-out" style="font-size: 1.5rem;"></i>
                    </a>
                </div>
            </header>

            <div class="content-area">
                <div class="page-header">
                    <div>
                        <h2>Manage Exams</h2>
                        <p style="color: var(--text-muted);">View, add, and manage entrance exams.</p>
                    </div>
                    <a href="exam_form.php" class="btn btn-primary">
                        <i class="ph ph-plus"></i> Add New Exam
                    </a>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="msg-alert">
                    <i class="ph ph-check-circle"></i> Exam has been deleted successfully.
                </div>
                <?php endif; ?>

                <div class="panel">
                    <?php if(empty($exams)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding: 40px;">No exams found. Click "Add New Exam" to create one.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Exam Name</th>
                                        <th>Level</th>
                                        <th>Exam Date</th>
                                        <th>Mode</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($exams as $exam): ?>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--primary);">
                                            <?php echo htmlspecialchars($exam['exam_name']); ?><br>
                                            <span style="font-size: 0.8rem; font-weight: normal; color: var(--text-muted);"><?php echo htmlspecialchars($exam['conducting_body']); ?></span>
                                        </td>
                                        <td>
                                            <span class="level-badge"><?php echo htmlspecialchars($exam['exam_level']); ?></span>
                                        </td>
                                        <td>
                                            <!-- Date is managed via Dates Table per year -->
                                            <a href="exam_dates.php?exam_id=<?php echo $exam['id']; ?>" style="font-size: 0.85rem; color: var(--primary);">View Dates</a>
                                        </td>
                                        <td style="text-transform: capitalize;">
                                            <?php echo htmlspecialchars($exam['exam_mode']); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $exam['status'] ?: 'upcoming'; ?>">
                                                <?php echo htmlspecialchars($exam['status'] ?: 'Upcoming'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-links">
                                                <a href="exam_form.php?id=<?php echo $exam['id']; ?>" class="action-btn" title="Edit">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </a>
                                                <a href="exams.php?action=delete&id=<?php echo $exam['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirm('Are you sure you want to delete this exam?');">
                                                    <i class="ph ph-trash"></i>
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
<script>
document.getElementById('mobile-menu-btn').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.add('open');
    document.getElementById('sidebar-overlay').classList.add('show');
});
document.getElementById('sidebar-overlay').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.remove('open');
    this.classList.remove('show');
});
</script>
</body>
</html>
