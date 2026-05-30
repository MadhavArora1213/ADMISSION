<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle configuration update
$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_config'])) {
    $max_entities = (int)$_POST['max_entities'];
    $compare_fields_config = $_POST['compare_fields_config'];
    
    // Validate JSON
    json_decode($compare_fields_config);
    if (json_last_error() === JSON_ERROR_NONE) {
        // Check if config exists
        $stmt = $pdo->query("SELECT id FROM compare_config LIMIT 1");
        if ($stmt->rowCount() > 0) {
            $update = $pdo->prepare("UPDATE compare_config SET max_entities = ?, compare_fields_config = ?");
            $update->execute([$max_entities, $compare_fields_config]);
        } else {
            $insert = $pdo->prepare("INSERT INTO compare_config (max_entities, compare_fields_config) VALUES (?, ?)");
            $insert->execute([$max_entities, $compare_fields_config]);
        }
        $msg = "Configuration updated successfully.";
    } else {
        $msg = "Invalid JSON format for fields configuration.";
    }
}

// Fetch current config
$config = $pdo->query("SELECT * FROM compare_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$config) {
    $config = [
        'max_entities' => 4,
        'compare_fields_config' => json_encode([
            "college" => ["Overview", "Fees", "Placements", "Reviews"],
            "course" => ["Eligibility", "Syllabus", "Duration", "Fees"],
            "exam" => ["Important Dates", "Syllabus", "Pattern", "Cutoff"]
        ], JSON_PRETTY_PRINT)
    ];
}

// Fetch compare sessions
$stmt = $pdo->prepare("
    SELECT cs.*, u.full_name as user_name 
    FROM compare_sessions cs 
    LEFT JOIN users u ON cs.user_id = u.id 
    ORDER BY cs.created_at DESC 
    LIMIT 50
");
$stmt->execute();
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Engine | AdmissionSeason Admin</title>
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
        .page-header { margin-bottom: 24px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; display:flex; align-items:center; gap:10px; }
        
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm); }
        .panel h3 { font-size: 1.25rem; font-weight: 700; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;}
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; font-family: inherit; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        textarea.form-control { resize: vertical; font-family: monospace; }
        
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-primary:hover { background: #1d4ed8; }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: #dbeafe; color: #1e40af; margin-bottom: 20px; border: 1px solid #bfdbfe; font-weight:500; }
        
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; background: #f8fafc; }
        tr:hover { background-color: #f8fafc; }
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .badge-college { background: #dbeafe; color: #1e40af; }
        .badge-course { background: #dcfce7; color: #166534; }
        .badge-exam { background: #fef9c3; color: #854d0e; }
        
        .code-block { background: #f1f5f9; padding: 12px; border-radius: 8px; font-family: monospace; font-size: 0.8rem; overflow-x: auto; border: 1px solid var(--border-color); }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></span>
                <a href="logout.php" style="margin-left:16px; color:var(--text-dark);"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <h2><i class="ph ph-scales" style="color:var(--primary);"></i> Compare Engine</h2>
                <p style="color:var(--text-muted); margin-top:8px;">Manage comparison configurations and monitor user sessions.</p>
            </div>
            
            <?php if($msg): ?>
            <div class="msg-alert"><i class="ph ph-info"></i> <?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <div class="panel">
                <h3>Global Configuration</h3>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Max Entities to Compare</label>
                        <input type="number" name="max_entities" class="form-control" value="<?php echo htmlspecialchars($config['max_entities']); ?>" min="2" max="10" required style="max-width: 200px;">
                        <small style="color:var(--text-muted); display:block; margin-top:4px;">Maximum number of items a user can compare at once (default is 4).</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Compare Fields Configuration (JSON)</label>
                        <textarea name="compare_fields_config" class="form-control" rows="10" required><?php 
                            // Format JSON nicely if it's valid
                            $json = json_decode($config['compare_fields_config']);
                            echo $json ? json_encode($json, JSON_PRETTY_PRINT) : htmlspecialchars($config['compare_fields_config']); 
                        ?></textarea>
                        <small style="color:var(--text-muted); display:block; margin-top:4px;">Define the field groups and ordered lists for each comparison type (college, course, exam).</small>
                    </div>
                    
                    <button type="submit" name="update_config" class="btn-primary"><i class="ph ph-floppy-disk"></i> Save Configuration</button>
                </form>
            </div>
            
            <div class="panel">
                <h3>Recent Compare Sessions</h3>
                <?php if(empty($sessions)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding: 20px;">No comparison sessions found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Type</th>
                                <th>User</th>
                                <th>Entities Compared</th>
                                <th>Saved/Shared</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($sessions as $session): ?>
                            <tr>
                                <td style="font-family:monospace; font-size:0.8rem;"><?php echo htmlspecialchars(substr($session['id'], 0, 8)) . '...'; ?></td>
                                <td><span class="badge badge-<?php echo htmlspecialchars($session['comparison_type']); ?>"><?php echo ucfirst($session['comparison_type']); ?></span></td>
                                <td>
                                    <?php if($session['user_name']): ?>
                                        <span style="font-weight:600;"><?php echo htmlspecialchars($session['user_name']); ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-style:italic;">Anonymous<br><small><?php echo htmlspecialchars($session['session_id'] ?: 'N/A'); ?></small></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $entities = json_decode($session['entity_ids'], true);
                                    if(is_array($entities)) {
                                        echo count($entities) . " items";
                                        echo "<div style='font-size:0.75rem; color:var(--text-muted); margin-top:4px; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;' title='".htmlspecialchars(implode(', ', $entities))."'>".htmlspecialchars(implode(', ', $entities))."</div>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if($session['is_saved']): ?>
                                        <i class="ph-fill ph-bookmark" style="color:var(--primary); font-size:1.1rem; margin-right:8px;" title="Saved"></i>
                                    <?php endif; ?>
                                    <?php if($session['share_token']): ?>
                                        <i class="ph ph-share-network" style="color:#10b981; font-size:1.1rem;" title="Shared: <?php echo htmlspecialchars($session['share_token']); ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:0.85rem; color:var(--text-muted);"><?php echo date('d M Y H:i', strtotime($session['created_at'])); ?></td>
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
