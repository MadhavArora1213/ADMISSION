<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$test = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM ab_tests WHERE id = ?");
    $stmt->execute([$id]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$test) { die("Test not found."); }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_name = trim($_POST['test_name']);
    $metric = $_POST['metric'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'] ? $_POST['start_date'] : null;
    $end_date = $_POST['end_date'] ? $_POST['end_date'] : null;
    
    $variant_a_views = (int)$_POST['variant_a_views'];
    $variant_a_conv = (int)$_POST['variant_a_conv'];
    $variant_b_views = (int)$_POST['variant_b_views'];
    $variant_b_conv = (int)$_POST['variant_b_conv'];
    
    $winner = $_POST['winner'] ? $_POST['winner'] : null;
    $confidence_pct = $_POST['confidence_pct'] !== '' ? (float)$_POST['confidence_pct'] : null;
    
    // Attempt JSON validation
    $variant_a = trim($_POST['variant_a']);
    $variant_b = trim($_POST['variant_b']);
    
    if($variant_a && !json_decode($variant_a)) { $error = "Variant A is not valid JSON."; }
    if($variant_b && !json_decode($variant_b)) { $error = "Variant B is not valid JSON."; }
    
    if (empty($test_name)) {
        $error = "Test Name is required.";
    } elseif (!$error) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE ab_tests SET test_name=?, variant_a=?, variant_b=?, metric=?, winner=?, confidence_pct=?, status=?, start_date=?, end_date=?, variant_a_views=?, variant_a_conv=?, variant_b_views=?, variant_b_conv=? WHERE id=?");
            $stmt->execute([$test_name, $variant_a ?: null, $variant_b ?: null, $metric, $winner, $confidence_pct, $status, $start_date, $end_date, $variant_a_views, $variant_a_conv, $variant_b_views, $variant_b_conv, $id]);
            $success = "Test updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM ab_tests WHERE id = ?");
            $stmt->execute([$id]);
            $test = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("INSERT INTO ab_tests (test_name, variant_a, variant_b, metric, winner, confidence_pct, status, start_date, end_date, variant_a_views, variant_a_conv, variant_b_views, variant_b_conv) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$test_name, $variant_a ?: null, $variant_b ?: null, $metric, $winner, $confidence_pct, $status, $start_date, $end_date, $variant_a_views, $variant_a_conv, $variant_b_views, $variant_b_conv]);
            $id = $pdo->lastInsertId();
            header("Location: ab_test_form.php?id=$id&msg=created");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? 'Edit' : 'Add'; ?> A/B Test | Admin</title>
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
        .content-area { padding: 32px; max-width: 800px; margin: 0 auto; width: 100%; }
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
        .alert-success { background: rgba(11,36,71,0.04); color: #0B2447; border-color: rgba(11,36,71,0.04); }
        .alert-error { background: rgba(15,23,42,0.06); color: #0B2447; border-color: rgba(15,23,42,0.06); }
        textarea.form-control { min-height: 100px; font-family: monospace; font-size: 0.85rem; }
        .section-title { font-size: 1.1rem; border-bottom: 1px solid #eee; padding-bottom: 10px; margin: 30px 0 20px 0; font-weight: 700; color: var(--text-dark); display:flex; justify-content:space-between;}
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
                    <a href="ab_tests.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                    <?php echo $id ? 'Edit Test' : 'Create A/B Test'; ?>
                </h2>
                <a href="ab_tests.php" class="btn-secondary">Cancel</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?><div class="msg-alert alert-success">Test created successfully!</div><?php endif; ?>
            <?php if ($success): ?><div class="msg-alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg-alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <div class="form-panel">
                <form method="POST">
                    <div class="form-group">
                        <label>Test Name *</label>
                        <input type="text" name="test_name" class="form-control" required value="<?php echo htmlspecialchars($test['test_name'] ?? ''); ?>">
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Primary Metric</label>
                            <select name="metric" class="form-control">
                                <?php foreach(['ctr','conversion','lead_rate','time_on_page'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo (isset($test['metric']) && $test['metric']==$t) ? 'selected' : ''; ?>><?php echo str_replace('_', ' ', ucfirst($t)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <?php foreach(['running','completed','paused'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo (isset($test['status']) && $test['status']==$t) ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($test['start_date'] ?? date('Y-m-d')); ?>">
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($test['end_date'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="section-title">Variant Configuration (JSON)</div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Variant A (Control)</label>
                            <textarea name="variant_a" class="form-control" placeholder='{"color":"blue"}'><?php echo htmlspecialchars($test['variant_a'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Variant B (Test)</label>
                            <textarea name="variant_b" class="form-control" placeholder='{"color":"red"}'><?php echo htmlspecialchars($test['variant_b'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="section-title">Test Results (Raw Numbers)</div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Variant A Views</label>
                            <input type="number" name="variant_a_views" class="form-control" value="<?php echo htmlspecialchars($test['variant_a_views'] ?? '0'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Variant B Views</label>
                            <input type="number" name="variant_b_views" class="form-control" value="<?php echo htmlspecialchars($test['variant_b_views'] ?? '0'); ?>">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Variant A Conversions</label>
                            <input type="number" name="variant_a_conv" class="form-control" value="<?php echo htmlspecialchars($test['variant_a_conv'] ?? '0'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Variant B Conversions</label>
                            <input type="number" name="variant_b_conv" class="form-control" value="<?php echo htmlspecialchars($test['variant_b_conv'] ?? '0'); ?>">
                        </div>
                    </div>

                    <div class="section-title">Analysis Outcome</div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Winner</label>
                            <select name="winner" class="form-control">
                                <option value="">-- Pending --</option>
                                <option value="a" <?php echo (isset($test['winner']) && $test['winner']=='a') ? 'selected' : ''; ?>>Variant A</option>
                                <option value="b" <?php echo (isset($test['winner']) && $test['winner']=='b') ? 'selected' : ''; ?>>Variant B</option>
                                <option value="inconclusive" <?php echo (isset($test['winner']) && $test['winner']=='inconclusive') ? 'selected' : ''; ?>>Inconclusive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Confidence (%)</label>
                            <input type="number" step="0.1" name="confidence_pct" class="form-control" placeholder="e.g. 95.0" value="<?php echo htmlspecialchars($test['confidence_pct'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top:10px;">Save Test</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
