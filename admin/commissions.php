<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM commissions WHERE id = ?")->execute([$_GET['id']]);
    header("Location: commissions.php?msg=deleted");
    exit;
}

$stmt = $pdo->prepare("
    SELECT * 
    FROM commissions
    ORDER BY created_at DESC
");
$stmt->execute();
$commissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commissions | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; }
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
        .s-paid { background: #dcfce7; color: #166534; }
        .s-disputed { background: #fee2e2; color: #b91c1c; }
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
                    <h2><i class="ph ph-money" style="color:var(--primary);"></i> Commissions</h2>
                    <p style="color:var(--text-muted); margin-top:4px;">Manage payouts and earned commissions.</p>
                </div>
                <a href="commission_form.php" class="btn-primary"><i class="ph ph-plus"></i> Add Commission</a>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="msg-alert">Commission deleted successfully.</div>
            <?php endif; ?>

            <div class="panel">
                <?php if(empty($commissions)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 40px;">No commissions found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>College / Consultant</th>
                                <th>Application ID</th>
                                <th>Pct (%)</th>
                                <th>Earned</th>
                                <th>Payout Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($commissions as $comm): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;">C: <?php echo htmlspecialchars($comm['college_id']); ?></div>
                                    <?php if($comm['consultant_id']): ?>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">Consultant: <?php echo htmlspecialchars($comm['consultant_id']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($comm['application_id']); ?></td>
                                <td><?php echo htmlspecialchars($comm['commission_pct']); ?>%</td>
                                <td style="font-weight:700; color:var(--primary);">$<?php echo number_format($comm['commission_earned'], 2); ?></td>
                                <td><?php echo $comm['payout_date'] ? date('Y-m-d', strtotime($comm['payout_date'])) : '-'; ?></td>
                                <td><span class="badge s-<?php echo $comm['commission_status']; ?>"><?php echo htmlspecialchars($comm['commission_status']); ?></span></td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="commission_form.php?id=<?php echo $comm['id']; ?>" class="action-btn"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?action=delete&id=<?php echo $comm['id']; ?>" class="action-btn" onclick="return confirm('Delete?');"><i class="ph ph-trash"></i></a>
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
