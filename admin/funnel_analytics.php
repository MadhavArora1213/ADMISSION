<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM funnel_analytics WHERE id = ?")->execute([$_GET['id']]);
    header("Location: funnel_analytics.php?msg=deleted");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM funnel_analytics ORDER BY date DESC, funnel_step ASC");
$stmt->execute();
$funnels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funnel Analytics | AdmissionSeason Admin</title>
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
        .page-header h2 { font-size: 2rem; font-weight: 800; display:flex; align-items:center; gap:10px; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items:center; gap:6px; }
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.05em; background: #f8fafc; }
        tr:hover { background-color: #f8fafc; }
        .action-btn { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--text-dark); border: 1px solid var(--border-color); text-decoration: none; }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 20px; border: 1px solid #bbf7d0; font-weight:500; }
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; text-transform: capitalize; background: #f1f5f9; color: var(--text-dark); }
        .drop-high { color: #b91c1c; font-weight: 700; }
        .drop-low { color: #166534; font-weight: 700; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar"><div class="user-profile"><span>Admin</span></div></header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-funnel" style="color:var(--primary);"></i> Funnel Analytics</h2>
                    <p style="color:var(--text-muted); margin-top:4px;">Analyze user drop-off across key stages.</p>
                </div>
                <a href="funnel_analytic_form.php" class="btn-primary"><i class="ph ph-plus"></i> Add Record</a>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="msg-alert">Record deleted successfully.</div>
            <?php endif; ?>

            <div class="panel">
                <?php if(empty($funnels)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 40px;">No funnel records found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Segment</th>
                                <th>Step</th>
                                <th>Entered</th>
                                <th>Completed</th>
                                <th>Drop-off Rate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($funnels as $row): ?>
                            <tr>
                                <td><div style="font-weight:600;"><?php echo date('Y-m-d', strtotime($row['date'])); ?></div></td>
                                <td><?php echo htmlspecialchars($row['segment']); ?></td>
                                <td><span class="badge"><?php echo str_replace('_', ' ', htmlspecialchars($row['funnel_step'])); ?></span></td>
                                <td><?php echo number_format($row['users_entered']); ?></td>
                                <td><?php echo number_format($row['users_completed']); ?></td>
                                <td>
                                    <?php 
                                    $rate = $row['drop_off_rate'] ?? 0;
                                    $class = $rate > 50 ? 'drop-high' : 'drop-low';
                                    echo "<span class='$class'>" . number_format($rate, 1) . "%</span>";
                                    ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="funnel_analytic_form.php?id=<?php echo $row['id']; ?>" class="action-btn"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?action=delete&id=<?php echo $row['id']; ?>" class="action-btn" onclick="return confirm('Delete this record?');"><i class="ph ph-trash"></i></a>
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
