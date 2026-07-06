<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM retention_cohorts WHERE id = ?")->execute([$_GET['id']]);
    header("Location: retention_cohorts.php?msg=deleted");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM retention_cohorts ORDER BY cohort_date DESC");
$stmt->execute();
$cohorts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retention Cohorts | AdmissionSeason Admin</title>
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
        .action-btn { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #F8FAFC; color: var(--text-dark); border: 1px solid var(--border-color); text-decoration: none; }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 20px; border: 1px solid rgba(11,36,71,0.04); font-weight:500; }
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; background: #F8FAFC; color: var(--text-dark); }
        .retention-high { color: #0B2447; font-weight: 600; }
        .retention-med { color: #19376D; font-weight: 600; }
        .retention-low { color: #0B2447; font-weight: 600; }
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
                    <h2><i class="ph ph-users-three" style="color:var(--primary);"></i> Retention Cohorts</h2>
                    <p style="color:var(--text-muted); margin-top:4px;">Monitor how well you retain users over time.</p>
                </div>
                <a href="retention_cohort_form.php" class="btn-primary"><i class="ph ph-plus"></i> Add Cohort</a>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="msg-alert">Cohort deleted successfully.</div>
            <?php endif; ?>

            <div class="panel">
                <?php if(empty($cohorts)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 40px;">No cohorts found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Cohort Date</th>
                                <th>Segment</th>
                                <th>Users</th>
                                <th>Day 1 Retention</th>
                                <th>Day 7 Retention</th>
                                <th>Day 30 Retention</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cohorts as $row): 
                                function getRetClass($val) {
                                    if ($val >= 40) return 'retention-high';
                                    if ($val >= 15) return 'retention-med';
                                    return 'retention-low';
                                }
                            ?>
                            <tr>
                                <td><div style="font-weight:600;"><?php echo date('Y-m-d', strtotime($row['cohort_date'])); ?></div></td>
                                <td><?php echo htmlspecialchars($row['segment']); ?></td>
                                <td><?php echo number_format($row['users_in_cohort']); ?></td>
                                <td><span class="<?php echo getRetClass($row['day_1_retention']); ?>"><?php echo number_format($row['day_1_retention'], 1); ?>%</span></td>
                                <td><span class="<?php echo getRetClass($row['day_7_retention']); ?>"><?php echo number_format($row['day_7_retention'], 1); ?>%</span></td>
                                <td><span class="<?php echo getRetClass($row['day_30_retention']); ?>"><?php echo number_format($row['day_30_retention'], 1); ?>%</span></td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="retention_cohort_form.php?id=<?php echo $row['id']; ?>" class="action-btn"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?action=delete&id=<?php echo $row['id']; ?>" class="action-btn" onclick="return confirm('Delete this cohort?');"><i class="ph ph-trash"></i></a>
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
