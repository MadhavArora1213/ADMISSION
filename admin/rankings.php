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
        $sub_scores = $_POST['sub_scores'] ?: null;
        $source_url = $_POST['source_url'];
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
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}
        .form-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;}
        .form-group{margin-bottom:16px;}
        .form-group label{display:block;font-size:.85rem;font-weight:700;color:var(--text-main);margin-bottom:8px;}
        .form-control{width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:8px;font-size:.9rem;font-family:inherit;}
        .btn-primary{background:var(--primary);color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:600;cursor:pointer;transition:all .2s;}
        .btn-primary:hover{background:#1e3a8a;}
        .btn-danger{background:#dc2626;color:#fff;border:none;padding:6px 12px;border-radius:6px;font-weight:600;cursor:pointer;text-decoration:none;font-size:0.75rem;}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:0.9rem;font-weight:600;}
        .alert-success{background:#dcfce7;color:#166534;}
        .delta-up{color:#16a34a;font-weight:bold;}
        .delta-down{color:#dc2626;font-weight:bold;}
        .delta-none{color:var(--text-muted);}
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
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
                <form method="POST">
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
                            <select name="ranking_body" class="form-control" required>
                                <option value="NIRF">NIRF</option>
                                <option value="QS">QS</option>
                                <option value="Times">Times</option>
                                <option value="Outlook">Outlook</option>
                                <option value="IndiaToday">IndiaToday</option>
                                <option value="NAAC">NAAC</option>
                                <option value="Careers360">Careers360</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Year</label>
                            <input type="number" name="ranking_year" class="form-control" value="<?php echo date('Y'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" class="form-control" required>
                                <option value="Overall">Overall</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Management">Management</option>
                                <option value="Medical">Medical</option>
                                <option value="Law">Law</option>
                                <option value="Arts">Arts</option>
                            </select>
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
                            <label>Sub-scores (JSON format)</label>
                            <input type="text" name="sub_scores" class="form-control" placeholder='{"teaching": 88.5, "research": 76.2}'>
                        </div>
                        <div class="form-group" style="grid-column: 1 / span 3;">
                            <label>Source URL</label>
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
</body>
</html>
