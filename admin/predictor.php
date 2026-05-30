<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'configs';

// Handle Config Creation/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
    $config_id = !empty($_POST['config_id']) ? $_POST['config_id'] : null;
    $exam_id = $_POST['exam_id'];
    $data_year = $_POST['data_year'];
    
    // JSON inputs
    $model_weights = $_POST['model_weights'];
    if (empty(json_decode($model_weights))) $model_weights = '{"rank": 0.5, "category": 0.3, "state": 0.2}';
    
    $category_adjustments = !empty($_POST['category_adjustments']) ? $_POST['category_adjustments'] : null;
    if ($category_adjustments && empty(json_decode($category_adjustments))) $category_adjustments = null;
    
    $min_score = (int)$_POST['min_score'];
    $max_score = (int)$_POST['max_score'];
    
    $state_quota_enabled = isset($_POST['state_quota_enabled']) ? 1 : 0;
    $home_state_quota_pct = (float)$_POST['home_state_quota_pct'];
    $counselling_round_model = (int)$_POST['counselling_round_model'];

    if ($config_id) {
        $stmt = $pdo->prepare("
            UPDATE predictor_config 
            SET exam_id=?, data_year=?, model_weights=?, category_adjustments=?, 
                min_score=?, max_score=?, state_quota_enabled=?, home_state_quota_pct=?, counselling_round_model=?
            WHERE id=?
        ");
        $stmt->execute([
            $exam_id, $data_year, $model_weights, $category_adjustments, 
            $min_score, $max_score, $state_quota_enabled, $home_state_quota_pct, $counselling_round_model, 
            $config_id
        ]);
        $msg = "Configuration updated.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO predictor_config (id, exam_id, data_year, model_weights, category_adjustments, min_score, max_score, state_quota_enabled, home_state_quota_pct, counselling_round_model)
            VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        try {
            $stmt->execute([
                $exam_id, $data_year, $model_weights, $category_adjustments, 
                $min_score, $max_score, $state_quota_enabled, $home_state_quota_pct, $counselling_round_model
            ]);
            $msg = "Configuration created.";
        } catch(PDOException $e) {
            $msg = "Error: Config for this Exam and Year already exists.";
        }
    }
    header("Location: predictor.php?tab=configs&msg=" . urlencode($msg));
    exit;
}

// Fetch Exams for dropdown
$exams = $pdo->query("SELECT id, exam_name FROM exams ORDER BY exam_name")->fetchAll();

if ($tab === 'configs') {
    $stmt = $pdo->query("SELECT p.*, e.exam_name FROM predictor_config p LEFT JOIN exams e ON p.exam_id = e.id ORDER BY p.data_year DESC, e.exam_name ASC");
    $configs = $stmt->fetchAll();
} else {
    // Submissions
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $where = "1=1";
    $params = [];
    if ($search) {
        $where .= " AND (s.preferred_state LIKE ? OR s.category LIKE ? OR e.exam_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $stmt = $pdo->prepare("
        SELECT s.*, e.exam_name, u.full_name, u.email, u.phone 
        FROM predictor_submissions s 
        LEFT JOIN exams e ON s.exam_id = e.id 
        LEFT JOIN users u ON s.user_id = u.id 
        WHERE $where 
        ORDER BY s.created_at DESC 
        LIMIT 100
    ");
    $stmt->execute($params);
    $submissions = $stmt->fetchAll();
    
    // Stats
    $stat_total = $pdo->query("SELECT COUNT(*) FROM predictor_submissions")->fetchColumn();
    $stat_leads = $pdo->query("SELECT COUNT(*) FROM predictor_submissions WHERE lead_captured = 1")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Predictor | Admin Panel</title>
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
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;}
        .tab-link { padding: 10px 20px; font-weight: 600; color: var(--text-muted); font-size: 0.9rem; text-decoration: none; transition: all 0.2s; border-bottom: 3px solid transparent; }
        .tab-link:hover { color: var(--primary); }
        .tab-link.active { color: var(--primary); border-bottom-color: var(--primary); }
        
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); padding: 0; margin-bottom: 24px; overflow: hidden;}
        
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top;}
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; background: #f8fafc; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap;}
        .b-blue { background:#dbeafe; color:#1e40af; }
        .b-green { background:#dcfce7; color:#166534; }
        .b-gray { background:#f1f5f9; color:#475569; }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 20px; border: 1px solid #bbf7d0; }
        
        .btn-primary { padding: 10px 20px; font-size: 0.9rem; background: var(--primary); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;}
        .btn-primary:hover { opacity: 0.9; }
        .btn-action { padding: 6px 10px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; color: var(--text-dark); text-decoration: none;}
        .btn-action:hover { background: #f1f5f9; }
        
        .search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 7px 14px; width: 250px;}
        .search-box input { border: none; outline: none; font-size: 0.9rem; width: 100%; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; padding: 30px; border-radius: 12px; width: 700px; max-width: 90%; max-height: 90vh; overflow-y: auto;}
        .modal-header { font-size: 1.25rem; font-weight: 800; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;}
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;}
        .form-group input, .form-group select, .form-group textarea { padding: 10px; font-size: 0.9rem; border: 1px solid var(--border-color); border-radius: 8px; background: #fff; font-family: inherit;}
        .form-group textarea { font-family: monospace; font-size: 0.8rem; resize: vertical;}
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-magic-wand" style="color:var(--primary);"></i> College Predictor</h2>
                    <p style="color:var(--text-muted);">Manage AI weights, prediction rules, and view generated leads.</p>
                </div>
                <?php if($tab === 'configs'): ?>
                    <button class="btn-primary" onclick="openModal()"><i class="ph ph-plus"></i> Add Config</button>
                <?php endif; ?>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-info"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>

            <div class="filter-bar">
                <a href="?tab=configs" class="tab-link <?php echo $tab=='configs'?'active':''; ?>">Predictor Configurations</a>
                <a href="?tab=leads" class="tab-link <?php echo $tab=='leads'?'active':''; ?>">Submissions & Leads</a>
            </div>

            <?php if($tab === 'configs'): ?>
                <div class="panel">
                    <?php if(empty($configs)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:40px;">No configs found.</p>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Exam & Year</th>
                                    <th>Score Range</th>
                                    <th>State Quota</th>
                                    <th>AI Weights (JSON)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($configs as $c): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--primary);"><?php echo htmlspecialchars($c['exam_name']); ?></div>
                                        <div style="font-size:0.85rem; color:var(--text-muted);">Year: <?php echo $c['data_year']; ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size:0.85rem;">Min: <?php echo $c['min_score']; ?></div>
                                        <div style="font-size:0.85rem;">Max: <?php echo $c['max_score']; ?></div>
                                    </td>
                                    <td>
                                        <?php if($c['state_quota_enabled']): ?>
                                            <span class="badge b-blue">Enabled</span>
                                            <div style="font-size:0.8rem; margin-top:4px;">Home: <?php echo $c['home_state_quota_pct']; ?>%</div>
                                        <?php else: ?>
                                            <span class="badge b-gray">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="background:#f1f5f9; padding:6px; border-radius:6px; font-family:monospace; font-size:0.75rem; max-width: 250px; white-space:pre-wrap; word-break:break-all;">
                                            <?php echo htmlspecialchars($c['model_weights']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn-action" onclick='editConfig(<?php echo json_encode($c); ?>)'><i class="ph ph-pencil-simple"></i> Edit</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="display:flex; justify-content:space-between; margin-bottom:15px; align-items:center;">
                    <div style="display:flex; gap:15px;">
                        <div style="background:#fff; border:1px solid var(--border-color); padding:10px 20px; border-radius:8px;">
                            <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase;">Total Submissions</div>
                            <div style="font-size:1.5rem; font-weight:800;"><?php echo number_format($stat_total); ?></div>
                        </div>
                        <div style="background:#fff; border:1px solid var(--border-color); padding:10px 20px; border-radius:8px;">
                            <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase;">Leads Captured</div>
                            <div style="font-size:1.5rem; font-weight:800; color:#166534;"><?php echo number_format($stat_leads); ?></div>
                        </div>
                    </div>
                    <form method="GET">
                        <input type="hidden" name="tab" value="leads">
                        <div class="search-box">
                            <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                            <input type="text" name="q" placeholder="Search state, exam, category..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </form>
                </div>

                <div class="panel">
                    <?php if(empty($submissions)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:40px;">No submissions found.</p>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student/Lead</th>
                                    <th>Exam & Profile</th>
                                    <th>Score Details</th>
                                    <th>Lead Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($submissions as $s): ?>
                                <tr>
                                    <td style="font-size:0.85rem; white-space:nowrap;"><?php echo date('M d, Y h:i A', strtotime($s['created_at'])); ?></td>
                                    <td>
                                        <?php if($s['user_id']): ?>
                                            <div style="font-weight:700; color:var(--primary);"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                            <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($s['email']); ?></div>
                                            <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($s['phone']); ?></div>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted); font-size:0.85rem; font-style:italic;">Anonymous Visitor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight:700;"><?php echo htmlspecialchars($s['exam_name']); ?></div>
                                        <div style="font-size:0.8rem; margin-top:4px;">Cat: <span class="badge b-gray"><?php echo htmlspecialchars($s['category']); ?></span></div>
                                        <div style="font-size:0.8rem; margin-top:4px;">State: <?php echo htmlspecialchars($s['preferred_state'] ?: 'Any'); ?></div>
                                    </td>
                                    <td>
                                        <?php if($s['score']): ?><div style="font-size:0.85rem;"><strong>Score:</strong> <?php echo $s['score']; ?></div><?php endif; ?>
                                        <?php if($s['rank']): ?><div style="font-size:0.85rem;"><strong>Rank:</strong> <?php echo number_format($s['rank']); ?></div><?php endif; ?>
                                        <?php if($s['percentile']): ?><div style="font-size:0.85rem;"><strong>%ile:</strong> <?php echo $s['percentile']; ?></div><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($s['lead_captured']): ?>
                                            <span class="badge b-green">Captured</span>
                                        <?php else: ?>
                                            <span class="badge b-gray">Not Captured</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal -->
<div id="configModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span id="modalTitle">Add Predictor Configuration</span>
            <button onclick="closeModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_config">
            <input type="hidden" name="config_id" id="config_id" value="">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Exam</label>
                    <select name="exam_id" id="exam_id" required>
                        <option value="">-- Select Exam --</option>
                        <?php foreach($exams as $e): ?>
                        <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['exam_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Data Year</label>
                    <input type="number" name="data_year" id="data_year" required placeholder="e.g. 2026" value="<?php echo date('Y'); ?>">
                </div>
                
                <div class="form-group">
                    <label>Min Score</label>
                    <input type="number" name="min_score" id="min_score" value="0">
                </div>
                <div class="form-group">
                    <label>Max Score</label>
                    <input type="number" name="max_score" id="max_score" required placeholder="e.g. 300 or 720">
                </div>

                <div class="form-group full">
                    <label>Model Weights (JSON)</label>
                    <textarea name="model_weights" id="model_weights" rows="3" required>{"rank": 0.5, "category": 0.3, "state": 0.2}</textarea>
                    <span style="font-size:0.7rem; color:var(--text-muted);">Adjust the priority of factors in the prediction algorithm.</span>
                </div>
                
                <div class="form-group full">
                    <label>Category Adjustments (JSON) [Optional]</label>
                    <textarea name="category_adjustments" id="category_adjustments" rows="3" placeholder='{"OBC": 0.95, "SC": 0.80}'></textarea>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:8px; margin-top:28px; cursor:pointer;">
                        <input type="checkbox" name="state_quota_enabled" id="state_quota_enabled" value="1">
                        Enable State Quota
                    </label>
                </div>
                <div class="form-group">
                    <label>Home State Quota %</label>
                    <input type="number" step="0.1" name="home_state_quota_pct" id="home_state_quota_pct" value="85.0">
                </div>
                
                <div class="form-group full">
                    <label>Counselling Rounds to Simulate</label>
                    <input type="number" name="counselling_round_model" id="counselling_round_model" value="3">
                </div>
            </div>
            
            <div style="margin-top:24px; text-align:right; border-top:1px solid var(--border-color); padding-top:15px;">
                <button type="button" class="btn-action" style="padding:10px 20px;" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary" style="margin-left:10px;">Save Configuration</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('configModal').classList.add('active');
    document.getElementById('modalTitle').innerText = 'Add Predictor Configuration';
    document.getElementById('config_id').value = '';
    document.getElementById('exam_id').value = '';
    document.getElementById('data_year').value = new Date().getFullYear();
    document.getElementById('min_score').value = 0;
    document.getElementById('max_score').value = '';
    document.getElementById('model_weights').value = '{"rank": 0.5, "category": 0.3, "state": 0.2}';
    document.getElementById('category_adjustments').value = '';
    document.getElementById('state_quota_enabled').checked = false;
    document.getElementById('home_state_quota_pct').value = 0;
    document.getElementById('counselling_round_model').value = 1;
}

function closeModal() {
    document.getElementById('configModal').classList.remove('active');
}

function editConfig(c) {
    document.getElementById('configModal').classList.add('active');
    document.getElementById('modalTitle').innerText = 'Edit Configuration';
    document.getElementById('config_id').value = c.id;
    document.getElementById('exam_id').value = c.exam_id;
    document.getElementById('data_year').value = c.data_year;
    document.getElementById('min_score').value = c.min_score;
    document.getElementById('max_score').value = c.max_score;
    document.getElementById('model_weights').value = c.model_weights;
    document.getElementById('category_adjustments').value = c.category_adjustments || '';
    document.getElementById('state_quota_enabled').checked = (c.state_quota_enabled == 1);
    document.getElementById('home_state_quota_pct').value = c.home_state_quota_pct;
    document.getElementById('counselling_round_model').value = c.counselling_round_model;
}
</script>
</body>
</html>
