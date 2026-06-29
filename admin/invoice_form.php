<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$inv = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$inv) { die("Invoice not found."); }
}

// Fetch colleges for dropdown
$colleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $invoice_number = trim($_POST['invoice_number']);
    $college_id = trim($_POST['college_id']);
    $invoice_description = trim($_POST['invoice_description'] ?? '');
    
    $subtotal_amount = $_POST['subtotal_amount'] !== '' ? (float)$_POST['subtotal_amount'] : 0.00;
    $discount_amount = $_POST['discount_amount'] !== '' ? (float)$_POST['discount_amount'] : 0.00;
    $gst_amount = $_POST['gst_amount'] !== '' ? (float)$_POST['gst_amount'] : 0.00;
    $total_amount = ($subtotal_amount - $discount_amount) + $gst_amount;
    
    $invoice_date = $_POST['invoice_date'];
    $due_date = $_POST['due_date'];
    $payment_status = $_POST['payment_status'];
    $payment_method = $_POST['payment_method'];
    
    // File Upload
    $invoice_file = $inv['invoice_file'] ?? null;
    if (isset($_FILES['invoice_file']) && $_FILES['invoice_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['invoice_file']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/invoices/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_ext = strtolower(pathinfo($_FILES['invoice_file']['name'], PATHINFO_EXTENSION));
            $new_name = uniqid('INV_') . '.' . $file_ext;
            if (move_uploaded_file($_FILES['invoice_file']['tmp_name'], $upload_dir . $new_name)) {
                $invoice_file = 'uploads/invoices/' . $new_name;
            } else {
                $error = "Failed to save the uploaded invoice file.";
            }
        } else {
            $error = "File upload failed with error code: " . $_FILES['invoice_file']['error'];
        }
    }
    
    if (empty($invoice_number) || empty($college_id)) {
        $error = "Invoice Number and College are required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE invoices SET invoice_number=?, college_id=?, invoice_description=?, subtotal_amount=?, discount_amount=?, gst_amount=?, total_amount=?, invoice_date=?, due_date=?, payment_status=?, payment_method=?, invoice_file=? WHERE id=?");
            $stmt->execute([$invoice_number, $college_id, $invoice_description, $subtotal_amount, $discount_amount, $gst_amount, $total_amount, $invoice_date, $due_date, $payment_status, $payment_method, $invoice_file, $id]);
            $success = "Invoice updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
            $stmt->execute([$id]);
            $inv = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, college_id, invoice_description, subtotal_amount, discount_amount, gst_amount, total_amount, invoice_date, due_date, payment_status, payment_method, invoice_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$invoice_number, $college_id, $invoice_description, $subtotal_amount, $discount_amount, $gst_amount, $total_amount, $invoice_date, $due_date, $payment_status, $payment_method, $invoice_file]);
                $id = $pdo->lastInsertId();
                header("Location: invoice_form.php?id=$id&msg=created");
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Invoice Number already exists.";
                } else {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Invoice | Admin</title>
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
        .content-area { padding: 32px; max-width: 700px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 1.8rem; font-weight: 800; display: flex; align-items: center; gap: 10px; }
        .btn-secondary { background: #fff; border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; color: var(--text-dark); display: inline-flex; align-items: center; gap: 6px; font-weight: 600; white-space: nowrap; box-sizing: border-box; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; white-space: nowrap; box-sizing: border-box; }
        .form-panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); overflow-x: auto; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); font-size: 0.9rem; }
        .form-control { width: 100%; min-width: 0; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; box-sizing: border-box; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .msg-alert { padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid transparent; font-weight: 500; }
        .alert-success { background: rgba(11,36,71,0.04); color: #0B2447; border-color: rgba(11,36,71,0.04); }
        .alert-error { background: rgba(15,23,42,0.06); color: #0B2447; border-color: rgba(15,23,42,0.06); }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-dark); padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .topbar { height: 56px; padding: 0 12px; justify-content: space-between; }
            .mobile-menu-btn { display: block; }
            .content-area { padding: 12px; max-width: none; }
            .page-header h2 { font-size: 1.3rem; }
            .form-panel { padding: 16px; }
            .grid-2 { grid-template-columns: 1fr; gap: 12px; }
            .form-group { margin-bottom: 14px; }
            .form-group label { font-size: 0.85rem; margin-bottom: 6px; }
            .form-control { padding: 9px 12px; font-size: 0.9rem; }
            .btn-primary { width: 100%; text-align: center; justify-content: center; }
        }
        @media (max-width: 480px) {
            .content-area { padding: 8px; }
            .form-panel { padding: 12px; }
            .page-header h2 { font-size: 1.1rem; }
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
            <div class="user-profile"><span>Admin</span></div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <h2>
                    <a href="invoices.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                    <?php echo $id ? 'Edit Invoice' : 'Add New Invoice'; ?>
                </h2>
                <a href="invoices.php" class="btn-secondary">Cancel</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?><div class="msg-alert alert-success">Invoice created!</div><?php endif; ?>
            <?php if ($success): ?><div class="msg-alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg-alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <div class="form-panel">
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Invoice Number *</label>
                            <input type="text" name="invoice_number" class="form-control" required value="<?php echo htmlspecialchars($inv['invoice_number'] ?? 'INV-'.time()); ?>">
                        </div>
                        <div class="form-group">
                            <label>Billed To (College) *</label>
                            <select name="college_id" class="form-control" required>
                                <option value="">-- Select College --</option>
                                <?php foreach($colleges as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo (isset($inv['college_id']) && $inv['college_id']==$c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description / Line Items</label>
                        <textarea name="invoice_description" class="form-control" rows="3" placeholder="e.g. Premium Listing for 2026"><?php echo htmlspecialchars($inv['invoice_description'] ?? ''); ?></textarea>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Subtotal Amount</label>
                            <input type="number" step="0.01" name="subtotal_amount" class="form-control" required value="<?php echo htmlspecialchars($inv['subtotal_amount'] ?? '0.00'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Discount Amount</label>
                            <input type="number" step="0.01" name="discount_amount" class="form-control" value="<?php echo htmlspecialchars($inv['discount_amount'] ?? '0.00'); ?>">
                        </div>
                    </div>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label>GST Amount</label>
                            <input type="number" step="0.01" name="gst_amount" class="form-control" value="<?php echo htmlspecialchars($inv['gst_amount'] ?? '0.00'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Total Amount (Auto-Calculated)</label>
                            <input type="text" class="form-control" readonly style="background:#f8fafc;" value="<?php echo htmlspecialchars($inv['total_amount'] ?? '0.00'); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Invoice Date</label>
                            <input type="date" name="invoice_date" class="form-control" required value="<?php echo htmlspecialchars($inv['invoice_date'] ?? date('Y-m-d')); ?>">
                        </div>
                        <div class="form-group">
                            <label>Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="<?php echo htmlspecialchars($inv['due_date'] ?? date('Y-m-d', strtotime('+15 days'))); ?>">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Payment Status</label>
                            <select name="payment_status" class="form-control">
                                <?php foreach(['pending','paid','overdue'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo (isset($inv['payment_status']) && $inv['payment_status']==$t) ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method" class="form-control">
                                <option value="bank_transfer" <?php echo (isset($inv['payment_method']) && $inv['payment_method']=='bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="card" <?php echo (isset($inv['payment_method']) && $inv['payment_method']=='card') ? 'selected' : ''; ?>>Credit Card</option>
                                <option value="upi" <?php echo (isset($inv['payment_method']) && $inv['payment_method']=='upi') ? 'selected' : ''; ?>>UPI</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Upload Invoice PDF (Optional)</label>
                        <input type="file" name="invoice_file" class="form-control" accept="application/pdf">
                        <?php if(!empty($inv['invoice_file'])): ?>
                            <div style="margin-top: 10px;">
                                <a href="../<?php echo htmlspecialchars($inv['invoice_file']); ?>" target="_blank" style="color:var(--primary); font-weight:600; text-decoration:none;"><i class="ph ph-file-pdf"></i> View Current PDF</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="margin-top:10px;">Save Invoice</button>
                </form>
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
