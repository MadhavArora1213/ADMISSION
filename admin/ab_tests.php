<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM ab_tests WHERE id = ?")->execute([$_GET['id']]);
    header("Location: ab_tests.php?msg=deleted");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM ab_tests ORDER BY created_at DESC");
$stmt->execute();
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A/B Tests | AdmissionSeason Admin</title>
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
        .w-a { background: #dbeafe; color: #1e3a8a; }
        .w-b { background: #fef08a; color: #854d0e; }
        .w-inconclusive { background: #e5e7eb; color: #374151; }
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
                    <h2><i class="ph ph-flask" style="color:var(--primary);"></i> A/B Tests</h2>
                    <p style="color:var(--text-muted); margin-top:4px;">Manage optimization experiments and variants.</p>
                </div>
                <a href="ab_test_form.php" class="btn-primary"><i class="ph ph-plus"></i> Create Test</a>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="msg-alert">Test deleted successfully.</div>
            <?php endif; ?>

            <div class="panel">
                <?php if(empty($tests)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 40px;">No tests found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Test Name</th>
                                <th>Metric</th>
                                <th>Dates</th>
                                <th>Status</th>
                                <th>Winner</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tests as $test): ?>
                            <tr>
                                <td><div style="font-weight:600;"><?php echo htmlspecialchars($test['test_name']); ?></div></td>
                                <td><span class="badge"><?php echo htmlspecialchars($test['metric']); ?></span></td>
                                <td>
                                    <?php echo $test['start_date'] ? date('M d', strtotime($test['start_date'])) : '-'; ?> 
                                    to 
                                    <?php echo $test['end_date'] ? date('M d', strtotime($test['end_date'])) : '-'; ?>
                                </td>
                                <td><span class="badge"><?php echo htmlspecialchars($test['status']); ?></span></td>
                                <td>
                                    <?php if($test['winner']): ?>
                                        <span class="badge w-<?php echo $test['winner']; ?>">Variant <?php echo strtoupper($test['winner']); ?></span>
                                        <div style="font-size:0.75rem; margin-top:4px; color:var(--text-muted);"><?php echo $test['confidence_pct']; ?>% Conf.</div>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.8rem;">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="ab_test_form.php?id=<?php echo $test['id']; ?>" class="action-btn"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?action=delete&id=<?php echo $test['id']; ?>" class="action-btn" onclick="return confirm('Delete this test?');"><i class="ph ph-trash"></i></a>
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
