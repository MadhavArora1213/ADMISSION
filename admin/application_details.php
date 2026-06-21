<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

if (!isset($_GET['id'])) { header('Location: applications.php'); exit; }
$id = $_GET['id'];

// Handle application status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    $payment_status = $_POST['payment_status'];
    $remarks = $_POST['remarks'];
    
    $stmt = $pdo->prepare("UPDATE applications SET status = ?, payment_status = ?, remarks = ? WHERE id = ?");
    $stmt->execute([$status, $payment_status, $remarks, $id]);
    $msg = "Application status updated successfully.";
}

// Fetch application data
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone, c.name AS college_name, cr.course_name 
    FROM applications a 
    LEFT JOIN users u ON a.user_id = u.id 
    LEFT JOIN colleges c ON a.college_id = c.id
    LEFT JOIN courses cr ON a.course_id = cr.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) { header('Location: applications.php'); exit; }

// Fetch documents
$stmt = $pdo->prepare("SELECT * FROM application_documents WHERE application_id = ?");
$stmt->execute([$id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch payments
$stmt = $pdo->prepare("SELECT * FROM payments WHERE application_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details | AdmissionSeason Admin</title>
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
        
        .grid-container { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm); }
        .panel h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 8px; }
        
        .info-group { margin-bottom: 16px; }
        .info-label { font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 4px; display: block; }
        .info-val { font-size: 0.95rem; font-weight: 600; color: var(--text-dark); }
        
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; font-family: inherit; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 20px; border: 1px solid rgba(11,36,71,0.04); font-weight:500; }
        
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; text-transform: capitalize; }
        
        .doc-card { border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .doc-info { flex: 1; }
        .doc-actions { display: flex; gap: 8px; }
        .btn-sm { padding: 6px 10px; font-size: 0.75rem; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-dark); }
        
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.72rem; background: #f8fafc; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></span>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <a href="applications.php" style="font-size:0.85rem; color:var(--primary); text-decoration:none; display:flex; align-items:center; gap:4px; margin-bottom:8px;"><i class="ph ph-arrow-left"></i> Back to Applications</a>
                    <h2>Application #<?php echo htmlspecialchars($app['application_number']); ?></h2>
                </div>
            </div>
            
            <?php if(isset($msg)): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> <?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <div class="grid-container">
                <div class="main-column">
                    <div class="panel">
                        <h3><i class="ph ph-user"></i> Applicant Information</h3>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                            <div class="info-group">
                                <span class="info-label">Full Name</span>
                                <span class="info-val"><?php echo htmlspecialchars($app['user_name'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Email Address</span>
                                <span class="info-val"><?php echo htmlspecialchars($app['user_email'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Applied Date</span>
                                <span class="info-val"><?php echo date('d M Y, h:i A', strtotime($app['applied_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <h3><i class="ph ph-buildings"></i> Course & College</h3>
                        <div class="info-group">
                            <span class="info-label">College</span>
                            <span class="info-val" style="color:var(--primary);"><?php echo htmlspecialchars($app['college_name']); ?></span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Course</span>
                            <span class="info-val"><?php echo htmlspecialchars($app['course_name']); ?></span>
                        </div>
                    </div>

                    <div class="panel">
                        <h3><i class="ph ph-files"></i> Uploaded Documents</h3>
                        <?php if(empty($documents)): ?>
                            <p style="color:var(--text-muted); font-size:0.9rem;">No documents uploaded yet.</p>
                        <?php else: ?>
                            <?php foreach($documents as $doc): ?>
                            <div class="doc-card">
                                <div class="doc-info">
                                    <div style="font-weight:700; margin-bottom:4px; text-transform:uppercase; font-size:0.8rem;"><?php echo str_replace('_', ' ', $doc['doc_type']); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">Uploaded: <?php echo date('d M Y', strtotime($doc['created_at'])); ?></div>
                                    <div style="margin-top:6px;">
                                        <span class="badge" style="background: <?php echo $doc['verification_status']=='verified'?'rgba(11,36,71,0.04)':($doc['verification_status']=='rejected'?'rgba(15,23,42,0.06)':'rgba(11,36,71,0.04)'); ?>; color: <?php echo $doc['verification_status']=='verified'?'#0B2447':($doc['verification_status']=='rejected'?'#0B2447':'#0F172A'); ?>;">
                                            <?php echo $doc['verification_status']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="doc-actions">
                                    <a href="<?php echo htmlspecialchars($doc['doc_url']); ?>" target="_blank" class="btn-sm btn-outline"><i class="ph ph-eye"></i> View</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="panel">
                        <h3><i class="ph ph-credit-card"></i> Payment History</h3>
                        <?php if(empty($payments)): ?>
                            <p style="color:var(--text-muted); font-size:0.9rem;">No payments found.</p>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Txn ID</th>
                                        <th>Gateway</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($payments as $pay): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($pay['created_at'])); ?></td>
                                        <td style="font-family:monospace;"><?php echo htmlspecialchars($pay['gateway_txn_id'] ?: '-'); ?></td>
                                        <td style="text-transform:capitalize;"><?php echo htmlspecialchars($pay['gateway']); ?></td>
                                        <td style="font-weight:600;">₹<?php echo number_format($pay['amount'], 2); ?></td>
                                        <td><span class="badge" style="background:#F8FAFC;"><?php echo $pay['payment_status']; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="side-column">
                    <div class="panel">
                        <h3><i class="ph ph-pencil-simple"></i> Update Application</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Application Status</label>
                                <select name="status" class="form-control">
                                    <?php foreach(['draft','submitted','under_review','waitlisted','admitted','rejected'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo $app['status'] == $st ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Payment Status</label>
                                <select name="payment_status" class="form-control">
                                    <?php foreach(['pending','paid','refunded','waived'] as $pst): ?>
                                    <option value="<?php echo $pst; ?>" <?php echo $app['payment_status'] == $pst ? 'selected' : ''; ?>><?php echo ucfirst($pst); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Admin Remarks</label>
                                <textarea name="remarks" class="form-control" rows="4"><?php echo htmlspecialchars($app['remarks']); ?></textarea>
                            </div>
                            <button type="submit" name="update_status" class="btn-primary">Update Application</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
