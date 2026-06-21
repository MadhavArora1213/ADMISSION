<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM invoices WHERE id = ?")->execute([$_GET['id']]);
    header("Location: invoices.php?msg=deleted");
    exit;
}

$stmt = $pdo->prepare("
    SELECT i.*, c.name as college_name 
    FROM invoices i
    LEFT JOIN colleges c ON i.college_id = c.id
    ORDER BY i.created_at DESC
");
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices | AdmissionSeason Admin</title>
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
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; text-transform: capitalize; background: #F8FAFC; color: var(--text-dark); }
        .b-paid { background: rgba(11,36,71,0.04); color: #0B2447; }
        .b-overdue { background: rgba(15,23,42,0.06); color: #0B2447; }
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
                    <h2><i class="ph ph-receipt" style="color:var(--primary);"></i> Invoices</h2>
                    <p style="color:var(--text-muted); margin-top:4px;">Manage client billing.</p>
                </div>
                <a href="invoice_form.php" class="btn-primary"><i class="ph ph-plus"></i> Create Invoice</a>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="msg-alert">Invoice deleted successfully.</div>
            <?php endif; ?>

            <div class="panel">
                <?php if(empty($invoices)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 40px;">No invoices found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>College</th>
                                <th>Description</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($invoices as $inv): ?>
                            <tr>
                                <td><div style="font-weight:600;"><?php echo htmlspecialchars($inv['invoice_number']); ?></div></td>
                                <td>
                                    <div style="font-weight:600; color:var(--text-dark);">
                                        <?php echo htmlspecialchars($inv['college_name'] ?? 'College ID: ' . $inv['college_id']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars(substr($inv['invoice_description'], 0, 30)) . (strlen($inv['invoice_description']) > 30 ? '...' : ''); ?></td>
                                <td>
                                    $<?php echo number_format($inv['total_amount'], 2); ?>
                                    <?php if($inv['discount_amount'] > 0): ?>
                                        <div style="font-size:0.75rem; color:var(--text-muted);">incl. $<?php echo number_format($inv['discount_amount'], 2); ?> discount</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($inv['invoice_date'])); ?></td>
                                <td><span class="badge b-<?php echo $inv['payment_status']; ?>"><?php echo htmlspecialchars($inv['payment_status']); ?></span></td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <?php if(!empty($inv['invoice_file'])): ?>
                                            <a href="../<?php echo htmlspecialchars($inv['invoice_file']); ?>" target="_blank" class="action-btn" title="View PDF"><i class="ph ph-file-pdf"></i></a>
                                        <?php endif; ?>
                                        <a href="invoice_form.php?id=<?php echo $inv['id']; ?>" class="action-btn" title="Edit"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?action=delete&id=<?php echo $inv['id']; ?>" class="action-btn" title="Delete" onclick="return confirm('Delete?');"><i class="ph ph-trash"></i></a>
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
