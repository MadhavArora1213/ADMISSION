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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $invoice_number = trim($_POST['invoice_number']);
    $college_id = (int)$_POST['college_id'];
    $subtotal_amount = $_POST['subtotal_amount'] !== '' ? (float)$_POST['subtotal_amount'] : 0.00;
    $gst_amount = $_POST['gst_amount'] !== '' ? (float)$_POST['gst_amount'] : 0.00;
    $total_amount = $subtotal_amount + $gst_amount;
    $invoice_date = $_POST['invoice_date'];
    $due_date = $_POST['due_date'];
    $payment_status = $_POST['payment_status'];
    $payment_method = $_POST['payment_method'];
    
    if (empty($invoice_number) || empty($college_id)) {
        $error = "Invoice Number and College ID are required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE invoices SET invoice_number=?, college_id=?, subtotal_amount=?, gst_amount=?, total_amount=?, invoice_date=?, due_date=?, payment_status=?, payment_method=? WHERE id=?");
            $stmt->execute([$invoice_number, $college_id, $subtotal_amount, $gst_amount, $total_amount, $invoice_date, $due_date, $payment_status, $payment_method, $id]);
            $success = "Invoice updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
            $stmt->execute([$id]);
            $inv = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, college_id, subtotal_amount, gst_amount, total_amount, invoice_date, due_date, payment_status, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$invoice_number, $college_id, $subtotal_amount, $gst_amount, $total_amount, $invoice_date, $due_date, $payment_status, $payment_method]);
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
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 600px; margin: 0 auto; width: 100%; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h2 { font-size: 1.8rem; font-weight: 800; display:flex; align-items:center; gap:10px; }
        .btn-secondary { background: #fff; border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; color: var(--text-dark); display:inline-flex; align-items:center; gap:6px; font-weight:600;}
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .form-panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); font-size: 0.9rem; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .msg-alert { padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid transparent; font-weight:500; }
        .alert-success { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar"><div class="user-profile"><span>Admin</span></div></header>
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
                <form method="POST">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Invoice Number *</label>
                            <input type="text" name="invoice_number" class="form-control" required value="<?php echo htmlspecialchars($inv['invoice_number'] ?? 'INV-'.time()); ?>">
                        </div>
                        <div class="form-group">
                            <label>College ID *</label>
                            <input type="number" name="college_id" class="form-control" required value="<?php echo htmlspecialchars($inv['college_id'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Subtotal Amount</label>
                            <input type="number" step="0.01" name="subtotal_amount" class="form-control" required value="<?php echo htmlspecialchars($inv['subtotal_amount'] ?? '0.00'); ?>">
                        </div>
                        <div class="form-group">
                            <label>GST Amount</label>
                            <input type="number" step="0.01" name="gst_amount" class="form-control" value="<?php echo htmlspecialchars($inv['gst_amount'] ?? '0.00'); ?>">
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
                    <button type="submit" class="btn-primary" style="margin-top:10px;">Save Invoice</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
