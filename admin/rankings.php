<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_ranking') {
        $college_id = $_POST['college_id'] ?: null;
        $ranking_body = $_POST['ranking_body'];
        $ranking_year = $_POST['ranking_year'];
        $category = $_POST['category'];
        $rank_position = $_POST['rank_position'] ?: null;
        $rank_band = $_POST['rank_band'];
        $score = $_POST['score'] ?: null;
        $sub_scores_json = null;
        if (!empty(trim($_POST['sub_scores']))) {
            $scores = array_map('trim', explode(',', $_POST['sub_scores']));
            $scores = array_filter($scores, 'strlen');
            if (!empty($scores)) {
                $formatted = [];
                foreach($scores as $s) {
                    $parts = explode(':', $s);
                    $name = trim($parts[0] ?? '');
                    $val = trim($parts[1] ?? '0');
                    if ($name) {
                        $formatted[$name] = (float)$val;
                    }
                }
                $sub_scores_json = json_encode($formatted);
            }
        }
        $sub_scores = $sub_scores_json;

        $source_url = !empty($_POST['source_url']) ? $_POST['source_url'] : null;
        if (isset($_FILES['source_file']) && $_FILES['source_file']['error'] == 0) {
            $upload_dir = '../uploads/rankings/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['source_file']['name']));
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['source_file']['tmp_name'], $target_file)) {
                $source_url = 'uploads/rankings/' . $file_name;
            }
        }
        $published_date = $_POST['published_date'] ?: null;
        $previous_year_rank = $_POST['previous_year_rank'] ?: null;
        
        $rank_delta = null;
        if ($rank_position && $previous_year_rank) {
            $rank_delta = $previous_year_rank - $rank_position; // positive means improved
        }

        $stmt = $pdo->prepare("INSERT INTO rankings (college_id, ranking_body, ranking_year, category, rank_position, rank_band, score, sub_scores, source_url, published_date, previous_year_rank, rank_delta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$college_id, $ranking_body, $ranking_year, $category, $rank_position, $rank_band, $score, $sub_scores, $source_url, $published_date, $previous_year_rank, $rank_delta]);
        
        header("Location: rankings.php?msg=added");
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM rankings WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: rankings.php?msg=deleted");
    exit;
}

// Fetch Rankings
$rankings = $pdo->query("SELECT r.*, c.name as college_name FROM rankings r LEFT JOIN colleges c ON r.college_id = c.id ORDER BY r.ranking_year DESC, r.rank_position ASC")->fetchAll();

// Fetch Colleges for dropdown
$colleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name ASC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rankings Management | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}
        .sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto;z-index:100;transition:transform 0.3s ease}
        .sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}
        .sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s;text-decoration:none}
        .sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.sidebar-nav a i{font-size:1.25rem}
        .main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}
        .topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}
        .user-profile{display:flex;align-items:center;gap:12px;font-weight:500}
        .content-area{padding:32px;max-width:1200px;margin:0 auto;width:100%;box-sizing:border-box}
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:12px;flex-wrap:wrap}
        .page-header h2{font-size:2rem;font-weight:800}
        .panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px;overflow-x:auto}
        .panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}
        table{width:100%;border-collapse:collapse;font-size:.88rem;min-width:600px}
        th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}
        th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}
        tr:hover{background:rgba(0,0,0,.015)}
        .form-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-size:.85rem;font-weight:700;color:var(--text-main);margin-bottom:8px}
        .form-control{width:100%;min-width:0;padding:10px 12px;border:1px solid var(--border-color);border-radius:8px;font-size:.9rem;font-family:inherit;box-sizing:border-box}
        .form-control:focus{outline:none;border-color:var(--primary)}
        .btn-primary{background:var(--primary);color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap}
        .btn-primary:hover{background:#0B2447}
        .btn-danger{background:#0F172A;color:#fff;border:none;padding:6px 12px;border-radius:6px;font-weight:600;cursor:pointer;text-decoration:none;font-size:0.75rem}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:0.9rem;font-weight:600}
        .alert-success{background:rgba(11,36,71,0.04);color:#0B2447}
        .delta-up{color:#0B2447;font-weight:bold}.delta-down{color:#0F172A;font-weight:bold}.delta-none{color:var(--text-muted)}
        .mobile-menu-btn{display:none;background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-dark);padding:4px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:90}

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}
            .sidebar-overlay.show{display:block}.main-content{margin-left:0}
            .topbar{height:56px;padding:0 12px;justify-content:space-between}
            .mobile-menu-btn{display:block}.content-area{padding:12px}
            .page-header{flex-direction:column;align-items:flex-start}.page-header h2{font-size:1.4rem}
            .form-grid{grid-template-columns:1fr;gap:12px}
            .form-group label{font-size:.82rem}.form-control{padding:9px 12px;font-size:.88rem}
            .panel{padding:14px}.panel h3{font-size:1rem}
            .btn-primary{width:100%;text-align:center;justify-content:center}
        }
        @media(max-width:480px){
            .content-area{padding:8px}.panel{padding:10px}
            .page-header h2{font-size:1.2rem}
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
            <div class="user-profile">
                <span><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></span>
                <a href="logout.php" style="margin-left:16px;color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-medal" style="color:var(--primary);"></i> Rankings Management</h2>
                    <p style="color:var(--text-muted);">Manage college rankings across various bodies (NIRF, QS, etc.).</p>
                </div>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success">Action completed successfully!</div>
            <?php endif; ?>

            <div class="panel">
                <h3><i class="ph ph-plus-circle"></i> Add New Ranking</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_ranking">
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / span 2;">
                            <label>College</label>
                            <select name="college_id" class="form-control" required>
                                <option value="">-- Select College --</option>
                                <?php foreach($colleges as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ranking Body</label>
                            <input type="text" name="ranking_body" class="form-control" list="ranking_body_list" placeholder="e.g. NIRF, QS" required>
                            <datalist id="ranking_body_list">
                                <option value="NIRF">
                                <option value="QS">
                                <option value="Times">
                                <option value="Outlook">
                                <option value="IndiaToday">
                                <option value="NAAC">
                                <option value="Careers360">
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label>Year</label>
                            <input type="number" name="ranking_year" class="form-control" value="<?php echo date('Y'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" class="form-control" list="category_list" placeholder="e.g. Overall, Engineering, Pharmacy" required>
                            <datalist id="category_list">
                                <option value="Overall">
                                <option value="Engineering">
                                <option value="Management">
                                <option value="Medical">
                                <option value="Law">
                                <option value="Arts">
                                <option value="Pharmacy">
                                <option value="Dental">
                                <option value="Architecture">
                                <option value="Agriculture">
                                <option value="Design">
                                <option value="Science">
                                <option value="Commerce">
                                <option value="Mass Communication">
                                <option value="Hotel Management">
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label>Rank Position</label>
                            <input type="number" name="rank_position" class="form-control" placeholder="e.g. 15">
                        </div>
                        <div class="form-group">
                            <label>Rank Band</label>
                            <input type="text" name="rank_band" class="form-control" placeholder="e.g. 101-150">
                        </div>
                        <div class="form-group">
                            <label>Score</label>
                            <input type="number" step="0.01" name="score" class="form-control" placeholder="e.g. 85.5">
                        </div>
                        <div class="form-group">
                            <label>Previous Year Rank</label>
                            <input type="number" name="previous_year_rank" class="form-control" placeholder="e.g. 20">
                        </div>
                        <div class="form-group" style="grid-column: 1 / span 3;">
                            <label>Sub-scores (Comma-separated Name:Score)</label>
                            <input type="text" name="sub_scores" class="form-control" placeholder='e.g. Teaching: 88.5, Research: 76.2'>
                        </div>
                        <div class="form-group" style="grid-column: 1 / span 2;">
                            <label>Upload Ranking Document/PDF</label>
                            <input type="file" name="source_file" class="form-control" accept=".pdf,.doc,.docx,image/*">
                        </div>
                        <div class="form-group">
                            <label>OR Source URL</label>
                            <input type="url" name="source_url" class="form-control" placeholder="https://...">
                        </div>
                    </div>
                    <div style="margin-top:24px; text-align:right; border-top:1px solid var(--border-color); padding-top:20px;">
                        <button type="submit" class="btn-primary"><i class="ph ph-floppy-disk"></i> Save Ranking</button>
                    </div>
                </form>
            </div>
            
            <div class="panel">
                <h3><i class="ph ph-list"></i> Saved Rankings</h3>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>College</th>
                                <th>Body</th>
                                <th>Category</th>
                                <th>Year</th>
                                <th>Rank</th>
                                <th>Score</th>
                                <th>Delta (YoY)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rankings as $row): ?>
                            <tr>
                                <td style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($row['college_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($row['ranking_body']); ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo htmlspecialchars($row['ranking_year']); ?></td>
                                <td style="font-weight:bold; font-size:1.1rem;"><?php echo $row['rank_position'] ? '#'.$row['rank_position'] : htmlspecialchars($row['rank_band']); ?></td>
                                <td><?php echo htmlspecialchars($row['score'] ?? '-'); ?></td>
                                <td>
                                    <?php 
                                        if($row['rank_delta'] > 0) echo "<span class='delta-up'>+".$row['rank_delta']." <i class='ph-bold ph-trend-up'></i></span>";
                                        elseif($row['rank_delta'] < 0) echo "<span class='delta-down'>".$row['rank_delta']." <i class='ph-bold ph-trend-down'></i></span>";
                                        else echo "<span class='delta-none'>-</span>";
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $link = $row['source_url'] ?? '';
                                    if ($link) {
                                        $display_link = preg_match('/^https?:\/\//', $link) ? $link : '../' . $link;
                                        echo "<a href='" . htmlspecialchars($display_link) . "' target='_blank' style='margin-right:8px; color:var(--primary);'><i class='ph ph-eye'></i></a>";
                                    }
                                    ?>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn-danger" onclick="return confirm('Delete this ranking?');"><i class="ph ph-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($rankings)): ?>
                            <tr><td colspan="8" style="text-align:center; padding:30px; color:var(--text-muted);">No rankings added yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
