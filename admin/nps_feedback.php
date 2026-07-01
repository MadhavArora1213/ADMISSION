<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // header('Location: index.php');
    // exit;
}
require_once 'db.php';

// ── Filters ──
$dateFrom = $_GET['from'] ?? '';
$dateTo   = $_GET['to'] ?? '';
$scoreMin = isset($_GET['score_min']) ? (int)$_GET['score_min'] : '';
$scoreMax = isset($_GET['score_max']) ? (int)$_GET['score_max'] : '';

$where = [];
$params = [];

if ($dateFrom !== '') { $where[] = "created_at >= ?"; $params[] = $dateFrom . ' 00:00:00'; }
if ($dateTo !== '')   { $where[] = "created_at <= ?"; $params[] = $dateTo . ' 23:59:59'; }
if ($scoreMin !== '') { $where[] = "score >= ?"; $params[] = $scoreMin; }
if ($scoreMax !== '') { $where[] = "score <= ?"; $params[] = $scoreMax; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── KPI Stats ──
$totalResponses = (int)$pdo->prepare("SELECT COUNT(*) FROM nps_feedback $whereSql")->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM nps_feedback $whereSql") : null;
$totalCount = 0;
$avgScore = 0;
$promoters = 0;
$passives = 0;
$detractors = 0;
$npsScore = 0;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM nps_feedback $whereSql");
    $stmt->execute($params);
    $totalCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT AVG(score) FROM nps_feedback $whereSql");
    $stmt->execute($params);
    $avgScore = round((float)$stmt->fetchColumn(), 1);

    // NPS = % Promoters (9-10) - % Detractors (0-6)
    $stmt = $pdo->prepare("SELECT
        SUM(CASE WHEN score >= 9 THEN 1 ELSE 0 END) AS promoters,
        SUM(CASE WHEN score >= 7 AND score <= 8 THEN 1 ELSE 0 END) AS passives,
        SUM(CASE WHEN score <= 6 THEN 1 ELSE 0 END) AS detractors
    FROM nps_feedback $whereSql");
    $stmt->execute($params);
    $npsRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $promoters = (int)($npsRow['promoters'] ?? 0);
    $passives = (int)($npsRow['passives'] ?? 0);
    $detractors = (int)($npsRow['detractors'] ?? 0);
    if ($totalCount > 0) {
        $npsScore = round(($promoters / $totalCount - $detractors / $totalCount) * 100);
    }
} catch (Exception $e) {}

// ── Score distribution ──
$distribution = [];
try {
    $stmt = $pdo->prepare("SELECT score, COUNT(*) AS cnt FROM nps_feedback $whereSql GROUP BY score ORDER BY score");
    $stmt->execute($params);
    $distribution = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {}

// ── Recent feedback ──
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$feedback = [];
try {
    $stmt = $pdo->prepare("SELECT nf.*, u.full_name AS user_name
        FROM nps_feedback nf
        LEFT JOIN users u ON u.id = nf.user_id
        $whereSql
        ORDER BY nf.created_at DESC
        LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$totalPages = max(1, (int)ceil($totalCount / $perPage));

// ── Daily trend (last 30 days) ──
$dailyTrend = [];
try {
    $dailyTrend = $pdo->query("SELECT DATE(created_at) AS day, COUNT(*) AS cnt, ROUND(AVG(score),1) AS avg_score FROM nps_feedback WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY day ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NPS Feedback | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: #F8FAFC; margin: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 50; transition: transform 0.3s ease; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-header .logo { font-size: 1.2rem; color: #f8fafc; display:flex; align-items:center; gap:8px; font-weight:700; }
        .sidebar-nav { padding: 16px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.6); transition: all 0.2s; font-size:0.95rem; text-decoration:none; }
        .sidebar-nav a:hover, .sidebar-nav a.active { color: #fff; background: rgba(255,255,255,0.05); border-left: 3px solid #19376D; }
        .sidebar-nav a i { font-size: 1.2rem; }
        .main-content { flex: 1; margin-left: 260px; display: flex; flex-direction: column; min-width: 0; }
        .topbar { height: 64px; background: #fff; border-bottom: 1px solid rgba(15,23,42,0.08); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 40; }
        .content-area { padding: 24px; display: flex; flex-direction: column; gap: 24px; }
        .page-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px; }

        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .kpi-card { background: #fff; border: 1px solid rgba(15,23,42,0.08); border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .kpi-title { font-size: 0.85rem; font-weight: 600; color: rgba(15,23,42,0.45); text-transform: uppercase; letter-spacing: 0.05em; }
        .kpi-icon { padding: 8px; border-radius: 8px; font-size: 1.25rem; }
        .kpi-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; }
        .kpi-sub { font-size: 0.82rem; color: rgba(15,23,42,0.5); margin-top: 4px; }

        .nps-giant { font-size: 4rem; font-weight: 900; text-align: center; line-height: 1; }
        .nps-good { color: #059669; }
        .nps-ok { color: #D97706; }
        .nps-bad { color: #DC2626; }

        .filter-bar { background: #fff; border: 1px solid rgba(15,23,42,0.08); border-radius: 12px; padding: 16px 20px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
        .filter-bar label { font-size: 0.8rem; font-weight: 600; color: rgba(15,23,42,0.5); display: block; margin-bottom: 4px; }
        .filter-bar input, .filter-bar select { padding: 8px 12px; border: 1.5px solid rgba(15,23,42,0.12); border-radius: 8px; font-size: 0.85rem; font-family: inherit; box-sizing: border-box; }
        .filter-bar button { padding: 8px 20px; border-radius: 8px; border: none; background: #0f172a; color: #fff; font-weight: 700; font-size: 0.85rem; cursor: pointer; white-space: nowrap; }
        .filter-clear { padding: 8px 16px; border-radius: 8px; background: rgba(15,23,42,0.06); color: #0f172a; text-decoration: none; font-size: .85rem; font-weight: 600; white-space: nowrap; }

        .score-dist { display: flex; gap: 4px; align-items: flex-end; height: 120px; margin-top: 16px; }
        .score-bar { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .score-bar-fill { width: 100%; border-radius: 4px 4px 0 0; min-height: 2px; transition: height 0.3s; }
        .score-bar-label { font-size: 0.7rem; font-weight: 700; color: rgba(15,23,42,0.6); }
        .score-bar-count { font-size: 0.65rem; color: rgba(15,23,42,0.4); }
        .bar-green { background: #059669; }
        .bar-amber { background: #D97706; }
        .bar-red { background: #DC2626; }

        .fb-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .fb-table { width: 100%; border-collapse: collapse; }
        .fb-table th { padding: 10px 16px; font-size: 0.78rem; color: rgba(15,23,42,0.45); font-weight: 600; border-bottom: 2px solid rgba(15,23,42,0.08); text-align: left; white-space: nowrap; }
        .fb-table td { padding: 12px 16px; font-size: 0.88rem; border-bottom: 1px solid #F8FAFC; }
        .fb-score { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; font-weight: 800; font-size: 0.9rem; }
        .fb-promoter { background: rgba(5,150,105,0.1); color: #059669; }
        .fb-passive { background: rgba(217,119,6,0.1); color: #D97706; }
        .fb-detractor { background: rgba(220,38,38,0.1); color: #DC2626; }

        .pager { display: flex; gap: 6px; justify-content: center; margin: 16px; flex-wrap: wrap; }
        .pager a { padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(15,23,42,0.1); text-decoration: none; color: #0f172a; font-size: 0.85rem; font-weight: 600; }
        .pager a.active { background: #0f172a; color: #fff; border-color: #0f172a; }

        .legend { display: flex; gap: 16px; font-size: 0.8rem; color: rgba(15,23,42,0.5); margin-top: 8px; flex-wrap: wrap; }
        .legend span { display: flex; align-items: center; gap: 4px; }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }

        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.4rem; cursor: pointer; color: #0f172a; padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        .card-box { background: #fff; border: 1px solid rgba(15,23,42,0.08); border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card-box-header { padding: 16px 20px; border-bottom: 1px solid rgba(15,23,42,0.08); background: #f8fafc; }
        .card-box-body { overflow: hidden; }

        @media(max-width:768px){
            .sidebar { transform: translateX(-100%); z-index: 100; }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; }
            .topbar { height: auto; min-height: 56px; padding: 10px 12px; }
            .content-area { padding: 12px; gap: 16px; }
            .page-title { font-size: 1.1rem; }
            .kpi-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .kpi-card { padding: 14px; }
            .kpi-value { font-size: 1.3rem; }
            .nps-giant { font-size: 2.5rem; }
            .filter-bar { flex-direction: column; align-items: stretch; gap: 10px; padding: 14px; }
            .filter-bar > div { width: 100%; }
            .filter-bar input, .filter-bar select { width: 100%; }
            .filter-bar-actions { display: flex; gap: 8px; }
            .filter-bar button, .filter-clear { flex: 1; text-align: center; }
            .card-box { padding: 16px; border-radius: 10px; }
            .card-box-header { padding: 12px 16px; flex-direction: column; gap: 4px; }
            .legend { gap: 10px; }
            .score-dist { height: 90px; }
            .fb-table th, .fb-table td { padding: 8px 10px; font-size: 0.8rem; }
        }
        @media(max-width:480px){
            .kpi-grid { grid-template-columns: 1fr; }
            .topbar { padding: 8px 12px; }
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
                <div style="font-weight:700;color:#0f172a;">NPS Feedback Dashboard</div>
                <div></div>
            </header>

            <div class="content-area">
                <div class="page-title"><i class="ph ph-smiley"></i> Net Promoter Score (NPS) Feedback</div>

                <!-- Filters -->
                <form class="filter-bar" method="get">
                    <div>
                        <label>From</label>
                        <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div>
                        <label>To</label>
                        <input type="date" name="to" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <div>
                        <label>Min Score</label>
                        <input type="number" name="score_min" min="1" max="10" value="<?= htmlspecialchars((string)$scoreMin) ?>">
                    </div>
                    <div>
                        <label>Max Score</label>
                        <input type="number" name="score_max" min="1" max="10" value="<?= htmlspecialchars((string)$scoreMax) ?>">
                    </div>
                    <div class="filter-bar-actions">
                        <button type="submit"><i class="ph ph-funnel"></i> Filter</button>
                        <a href="nps_feedback.php" class="filter-clear">Clear</a>
                    </div>
                </form>

                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">NPS Score</span>
                            <i class="ph-fill ph-smiley kpi-icon" style="background:rgba(5,150,105,0.08);color:#059669;"></i>
                        </div>
                        <div class="nps-giant <?= $npsScore >= 50 ? 'nps-good' : ($npsScore >= 0 ? 'nps-ok' : 'nps-bad') ?>">
                            <?= $npsScore > 0 ? '+' . $npsScore : $npsScore ?>
                        </div>
                        <div class="kpi-sub">Scale: -100 to +100</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Total Responses</span>
                            <i class="ph-fill ph-chart-bar kpi-icon" style="background:rgba(11,36,71,0.06);color:#19376D;"></i>
                        </div>
                        <div class="kpi-value"><?= number_format($totalCount) ?></div>
                        <div class="kpi-sub">Avg Score: <?= $avgScore ?>/10</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Promoters (9-10)</span>
                            <i class="ph-fill ph-thumbs-up kpi-icon" style="background:rgba(5,150,105,0.08);color:#059669;"></i>
                        </div>
                        <div class="kpi-value" style="color:#059669;"><?= $totalCount > 0 ? round($promoters/$totalCount*100) : 0 ?>%</div>
                        <div class="kpi-sub"><?= number_format($promoters) ?> responses</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Detractors (0-6)</span>
                            <i class="ph-fill ph-thumbs-down kpi-icon" style="background:rgba(220,38,38,0.06);color:#DC2626;"></i>
                        </div>
                        <div class="kpi-value" style="color:#DC2626;"><?= $totalCount > 0 ? round($detractors/$totalCount*100) : 0 ?>%</div>
                        <div class="kpi-sub"><?= number_format($detractors) ?> responses</div>
                    </div>
                </div>

                <!-- Score Distribution -->
                <div class="card-box">
                    <div style="font-weight:700;font-size:1rem;color:#0f172a;margin-bottom:4px;">Score Distribution</div>
                    <div class="legend">
                        <span><span class="legend-dot" style="background:#059669"></span> Promoter (9-10)</span>
                        <span><span class="legend-dot" style="background:#D97706"></span> Passive (7-8)</span>
                        <span><span class="legend-dot" style="background:#DC2626"></span> Detractor (0-6)</span>
                    </div>
                    <div class="score-dist">
                        <?php for ($i = 1; $i <= 10; $i++):
                            $count = (int)($distribution[$i] ?? 0);
                            $maxCount = max(1, ...array_values($distribution));
                            $height = $totalCount > 0 ? max(2, ($count / $maxCount) * 100) : 2;
                            $barClass = $i >= 9 ? 'bar-green' : ($i >= 7 ? 'bar-amber' : 'bar-red');
                        ?>
                        <div class="score-bar">
                            <span class="score-bar-count"><?= $count ?></span>
                            <div class="score-bar-fill <?= $barClass ?>" style="height:<?= $height ?>%"></div>
                            <span class="score-bar-label"><?= $i ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Feedback Table -->
                <div class="card-box">
                    <div class="card-box-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-weight:700;font-size:1rem;color:#0f172a;">All Feedback</span>
                        <span style="font-size:.82rem;color:rgba(15,23,42,0.45);">Showing <?= number_format($offset+1) ?>–<?= min($offset+$perPage, $totalCount) ?> of <?= number_format($totalCount) ?></span>
                    </div>
                    <div class="card-box-body">
                    <?php if (empty($feedback)): ?>
                    <div style="padding:48px;text-align:center;color:rgba(15,23,42,0.3);">
                        <i class="ph ph-smiley" style="font-size:3rem;display:block;margin-bottom:12px;"></i>
                        <p>No feedback submissions yet.</p>
                    </div>
                    <?php else: ?>
                    <div class="fb-table-wrap">
                    <table class="fb-table">
                        <thead>
                            <tr>
                                <th>Score</th>
                                <th>Category</th>
                                <th>User</th>
                                <th>Page</th>
                                <th>IP Address</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedback as $fb):
                                $cat = $fb['score'] >= 9 ? 'Promoter' : ($fb['score'] >= 7 ? 'Passive' : 'Detractor');
                                $catClass = $fb['score'] >= 9 ? 'fb-promoter' : ($fb['score'] >= 7 ? 'fb-passive' : 'fb-detractor');
                                $fbDate = date('d M Y, h:i A', strtotime($fb['created_at']));
                            ?>
                            <tr>
                                <td><span class="fb-score <?= $catClass ?>"><?= $fb['score'] ?></span></td>
                                <td><span class="fb-score <?= $catClass ?>" style="width:auto;padding:3px 10px;font-size:.75rem;"><?= $cat ?></span></td>
                                <td><?= htmlspecialchars($fb['user_name'] ?? 'Anonymous') ?></td>
                                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($fb['page_url'] ?? '') ?>"><?= htmlspecialchars($fb['article_slug'] ?? $fb['page_url'] ?? '—') ?></td>
                                <td><code style="font-size:.8rem;background:rgba(15,23,42,0.04);padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($fb['ip_address'] ?? '') ?></code></td>
                                <td style="white-space:nowrap;font-size:.82rem;color:rgba(15,23,42,0.5);"><?= $fbDate ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <div class="pager">
                        <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>">← Prev</a>
                        <?php endif; ?>
                        <?php for ($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>">Next →</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
<script>
document.getElementById('mobile-menu-btn').addEventListener('click',function(){document.querySelector('.sidebar').classList.toggle('open');document.getElementById('sidebar-overlay').classList.toggle('show');});
document.getElementById('sidebar-overlay').addEventListener('click',function(){document.querySelector('.sidebar').classList.remove('open');this.classList.remove('show');});
</script>
</body>
</html>
