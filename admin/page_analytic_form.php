<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM page_analytics WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { die("Record not found."); }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $page_url = trim($_POST['page_url']);
    $date = $_POST['date'];
    $page_views = (int)$_POST['page_views'];
    $unique_visitors = (int)$_POST['unique_visitors'];
    $bounce_rate = (float)$_POST['bounce_rate'];
    $avg_time_seconds = (int)$_POST['avg_time_seconds'];
    $traffic_source = $_POST['traffic_source'];
    $device_type = $_POST['device_type'];
    $country = trim($_POST['country']);
    $utm_campaign = trim($_POST['utm_campaign']);
    $utm_medium = trim($_POST['utm_medium']);
    
    if (empty($page_url) || empty($date)) {
        $error = "Page URL and Date are required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE page_analytics SET page_url=?, date=?, page_views=?, unique_visitors=?, bounce_rate=?, avg_time_seconds=?, traffic_source=?, device_type=?, country=?, utm_campaign=?, utm_medium=? WHERE id=?");
            $stmt->execute([$page_url, $date, $page_views, $unique_visitors, $bounce_rate, $avg_time_seconds, $traffic_source, $device_type, $country, $utm_campaign, $utm_medium, $id]);
            $success = "Record updated successfully.";
            $stmt = $pdo->prepare("SELECT * FROM page_analytics WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("INSERT INTO page_analytics (page_url, date, page_views, unique_visitors, bounce_rate, avg_time_seconds, traffic_source, device_type, country, utm_campaign, utm_medium) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$page_url, $date, $page_views, $unique_visitors, $bounce_rate, $avg_time_seconds, $traffic_source, $device_type, $country, $utm_campaign, $utm_medium]);
            $id = $pdo->lastInsertId();
            header("Location: page_analytic_form.php?id=$id&msg=created");
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
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Traffic Record | Admin</title>
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
        .content-area { padding: 32px; max-width: 700px; margin: 0 auto; width: 100%; }
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
                    <a href="page_analytics.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> 
                    <?php echo $id ? 'Edit Traffic Record' : 'Add Traffic Record'; ?>
                </h2>
                <a href="page_analytics.php" class="btn-secondary">Cancel</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?><div class="msg-alert alert-success">Record added successfully!</div><?php endif; ?>
            <?php if ($success): ?><div class="msg-alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg-alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <div class="form-panel">
                <form method="POST">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Page URL *</label>
                            <input type="text" name="page_url" class="form-control" required value="<?php echo htmlspecialchars($row['page_url'] ?? '/'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Date *</label>
                            <input type="date" name="date" class="form-control" required value="<?php echo htmlspecialchars($row['date'] ?? date('Y-m-d')); ?>">
                        </div>
                    </div>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Page Views</label>
                            <input type="number" name="page_views" class="form-control" value="<?php echo htmlspecialchars($row['page_views'] ?? '0'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Unique Visitors</label>
                            <input type="number" name="unique_visitors" class="form-control" value="<?php echo htmlspecialchars($row['unique_visitors'] ?? '0'); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Bounce Rate (%)</label>
                            <input type="number" step="0.01" name="bounce_rate" class="form-control" value="<?php echo htmlspecialchars($row['bounce_rate'] ?? '0.0'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Avg Time (Seconds)</label>
                            <input type="number" name="avg_time_seconds" class="form-control" value="<?php echo htmlspecialchars($row['avg_time_seconds'] ?? '0'); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Traffic Source</label>
                            <select name="traffic_source" class="form-control">
                                <?php foreach(['organic','direct','referral','social','email','paid'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo (isset($row['traffic_source']) && $row['traffic_source']==$t) ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Device Type</label>
                            <select name="device_type" class="form-control">
                                <?php foreach(['desktop','mobile','tablet'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo (isset($row['device_type']) && $row['device_type']==$t) ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Country (2-letter code)</label>
                        <input type="text" name="country" class="form-control" maxlength="2" placeholder="e.g. US, IN" value="<?php echo htmlspecialchars($row['country'] ?? ''); ?>">
                    </div>

                    <hr style="margin:20px 0; border:0; border-top:1px solid #eee;">
                    <h3 style="font-size:1rem; margin-bottom:15px; color:var(--text-dark);">Attribution</h3>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label>UTM Campaign</label>
                            <input type="text" name="utm_campaign" class="form-control" value="<?php echo htmlspecialchars($row['utm_campaign'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>UTM Medium</label>
                            <input type="text" name="utm_medium" class="form-control" value="<?php echo htmlspecialchars($row['utm_medium'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top:10px;">Save Record</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
