<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // header('Location: index.php');
    // exit;
}
require_once 'db.php';

// Ensure table exists
try {
    $pdo->query("SELECT 1 FROM cookie_consents LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS cookie_consents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(128) DEFAULT NULL,
        user_id INT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        consent_action ENUM('accepted_all','rejected_all','custom','closed') NOT NULL,
        necessary TINYINT(1) DEFAULT 1,
        analytics TINYINT(1) DEFAULT 0,
        marketing TINYINT(1) DEFAULT 0,
        preferences TINYINT(1) DEFAULT 0,
        page_url VARCHAR(500) DEFAULT NULL,
        country VARCHAR(100) DEFAULT NULL,
        city VARCHAR(100) DEFAULT NULL,
        device_type VARCHAR(20) DEFAULT NULL,
        browser VARCHAR(100) DEFAULT NULL,
        os VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at),
        INDEX idx_consent_action (consent_action)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// ── Filters ──
$actionFilter = $_GET['action'] ?? '';
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';
$deviceFilter = $_GET['device'] ?? '';
$browserFilter = $_GET['browser'] ?? '';

$where = [];
$params = [];

if ($actionFilter !== '') { $where[] = "consent_action = ?"; $params[] = $actionFilter; }
if ($dateFrom !== '') { $where[] = "created_at >= ?"; $params[] = $dateFrom . ' 00:00:00'; }
if ($dateTo !== '') { $where[] = "created_at <= ?"; $params[] = $dateTo . ' 23:59:59'; }
if ($deviceFilter !== '') { $where[] = "device_type = ?"; $params[] = $deviceFilter; }
if ($browserFilter !== '') { $where[] = "browser = ?"; $params[] = $browserFilter; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── KPI Stats ──
$totalConsents = 0;
$acceptedAll = 0;
$rejectedAll = 0;
$customConsents = 0;
$closedCount = 0;
$analyticsOn = 0;
$marketingOn = 0;
$preferencesOn = 0;

try {
    $totalConsents = (int)$pdo->prepare("SELECT COUNT(*) FROM cookie_consents $whereSql")->execute($params) ? 0 : 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cookie_consents $whereSql");
    $stmt->execute($params);
    $totalConsents = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT consent_action, COUNT(*) AS cnt FROM cookie_consents $whereSql GROUP BY consent_action");
    $stmt->execute($params);
    $actionCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $acceptedAll = (int)($actionCounts['accepted_all'] ?? 0);
    $rejectedAll = (int)($actionCounts['rejected_all'] ?? 0);
    $customConsents = (int)($actionCounts['custom'] ?? 0);
    $closedCount = (int)($actionCounts['closed'] ?? 0);

    $stmt = $pdo->prepare("SELECT
        SUM(analytics) AS a,
        SUM(marketing) AS m,
        SUM(preferences) AS p
        FROM cookie_consents $whereSql");
    $stmt->execute($params);
    $catRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $analyticsOn = (int)($catRow['a'] ?? 0);
    $marketingOn = (int)($catRow['m'] ?? 0);
    $preferencesOn = (int)($catRow['p'] ?? 0);
} catch (Exception $e) {}

// ── Daily trend (last 30 days) ──
$dailyTrend = [];
try {
    $dailyTrend = $pdo->query("SELECT DATE(created_at) AS day, consent_action, COUNT(*) AS cnt FROM cookie_consents WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(created_at), consent_action ORDER BY day ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Browser breakdown ──
$browserStats = [];
try {
    $browserStats = $pdo->query("SELECT browser, COUNT(*) AS cnt FROM cookie_consents GROUP BY browser ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Device breakdown ──
$deviceStats = [];
try {
    $deviceStats = $pdo->query("SELECT device_type, COUNT(*) AS cnt FROM cookie_consents GROUP BY device_type ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Paginated records ──
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$records = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM cookie_consents $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$totalPages = max(1, (int)ceil($totalConsents / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookie Consents | AdmissionSeason Admin</title>
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

        .kpi-green { background: rgba(5,150,105,0.1); color: #059669; }
        .kpi-red { background: rgba(220,38,38,0.1); color: #DC2626; }
        .kpi-blue { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .kpi-amber { background: rgba(217,119,6,0.1); color: #D97706; }
        .kpi-purple { background: rgba(139,92,246,0.1); color: #8b5cf6; }

        .filter-bar { background: #fff; border: 1px solid rgba(15,23,42,0.08); border-radius: 12px; padding: 16px 20px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
        .filter-bar label { font-size: 0.8rem; font-weight: 600; color: rgba(15,23,42,0.5); display: block; margin-bottom: 4px; }
        .filter-bar input, .filter-bar select { padding: 8px 12px; border: 1.5px solid rgba(15,23,42,0.12); border-radius: 8px; font-size: 0.85rem; font-family: inherit; box-sizing: border-box; }
        .filter-bar button { padding: 8px 20px; border-radius: 8px; border: none; background: #0f172a; color: #fff; font-weight: 700; font-size: 0.85rem; cursor: pointer; white-space: nowrap; }
        .filter-clear { padding: 8px 16px; border-radius: 8px; background: rgba(15,23,42,0.06); color: #0f172a; text-decoration: none; font-size: .85rem; font-weight: 600; white-space: nowrap; }

        .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card-box { background: #fff; border: 1px solid rgba(15,23,42,0.08); border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card-box-header { padding: 16px 20px; border-bottom: 1px solid rgba(15,23,42,0.08); font-weight: 700; font-size: 0.95rem; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .card-box-body { padding: 20px; }

        .bar-chart { display: flex; align-items: flex-end; gap: 4px; height: 140px; }
        .bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .bar-stack { width: 100%; display: flex; flex-direction: column; gap: 0; border-radius: 4px 4px 0 0; overflow: hidden; }
        .bar-seg { width: 100%; transition: height 0.3s; min-height: 1px; }
        .bar-label { font-size: 0.65rem; color: rgba(15,23,42,0.4); white-space: nowrap; }
        .bar-accepted { background: #059669; }
        .bar-rejected { background: #DC2626; }
        .bar-custom { background: #3b82f6; }
        .bar-closed { background: #94a3b8; }

        .donut-wrap { display: flex; align-items: center; justify-content: center; gap: 24px; padding: 10px 0; }
        .donut-legend { display: flex; flex-direction: column; gap: 8px; }
        .donut-legend-item { display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: #334155; }
        .donut-legend-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

        .fb-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .fb-table { width: 100%; border-collapse: collapse; }
        .fb-table th { padding: 10px 16px; font-size: 0.78rem; color: rgba(15,23,42,0.45); font-weight: 600; border-bottom: 2px solid rgba(15,23,42,0.08); text-align: left; white-space: nowrap; }
        .fb-table td { padding: 12px 16px; font-size: 0.88rem; border-bottom: 1px solid #F8FAFC; color: #334155; }
        .fb-table tr:hover td { background: #f8fafc; }

        .status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
        .status-accepted { background: rgba(5,150,105,0.1); color: #059669; }
        .status-rejected { background: rgba(220,38,38,0.1); color: #DC2626; }
        .status-custom { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .status-closed { background: rgba(148,163,184,0.1); color: #64748b; }

        .chip-toggle { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }
        .chip-on { background: rgba(5,150,105,0.1); color: #059669; }
        .chip-off { background: rgba(148,163,184,0.08); color: #94a3b8; }

        .pager { display: flex; gap: 6px; justify-content: center; margin: 16px; flex-wrap: wrap; }
        .pager a { padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(15,23,42,0.1); text-decoration: none; color: #0f172a; font-size: 0.85rem; font-weight: 600; }
        .pager a.active { background: #0f172a; color: #fff; border-color: #0f172a; }

        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.4rem; cursor: pointer; color: #0f172a; padding: 4px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

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
            .charts-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        <div class="sidebar-overlay" onclick="document.querySelector('.sidebar').classList.remove('open');this.classList.remove('show')"></div>
        <main class="main-content">
            <header class="topbar">
                <div style="display:flex;align-items:center;gap:12px;">
                    <button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('open');document.querySelector('.sidebar-overlay').classList.toggle('show')"><i class="ph ph-list"></i></button>
                    <div class="page-title"><i class="ph ph-cookie"></i> Cookie Consents</div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <a href="cookie_consents.php" class="filter-clear" style="text-decoration:none;"><i class="ph ph-arrow-clockwise"></i> Refresh</a>
                </div>
            </header>

            <div class="content-area">
                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <div class="kpi-title">Total Consents</div>
                            <div class="kpi-icon kpi-blue"><i class="ph ph-cookie"></i></div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalConsents) ?></div>
                        <div class="kpi-sub">All time records</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <div class="kpi-title">Accepted All</div>
                            <div class="kpi-icon kpi-green"><i class="ph ph-check-circle"></i></div>
                        </div>
                        <div class="kpi-value"><?= number_format($acceptedAll) ?></div>
                        <div class="kpi-sub"><?= $totalConsents > 0 ? round($acceptedAll/$totalConsents*100) : 0 ?>% acceptance rate</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <div class="kpi-title">Rejected All</div>
                            <div class="kpi-icon kpi-red"><i class="ph ph-x-circle"></i></div>
                        </div>
                        <div class="kpi-value"><?= number_format($rejectedAll) ?></div>
                        <div class="kpi-sub"><?= $totalConsents > 0 ? round($rejectedAll/$totalConsents*100) : 0 ?>% rejection rate</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <div class="kpi-title">Custom Preferences</div>
                            <div class="kpi-icon kpi-purple"><i class="ph ph-sliders"></i></div>
                        </div>
                        <div class="kpi-value"><?= number_format($customConsents) ?></div>
                        <div class="kpi-sub">Users customized settings</div>
                    </div>
                </div>

                <!-- Category Stats -->
                <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <div class="kpi-title">Analytics Enabled</div>
                            <div class="kpi-icon kpi-green"><i class="ph ph-chart-line-up"></i></div>
                        </div>
                        <div class="kpi-value"><?= number_format($analyticsOn) ?></div>
                        <div class="kpi-sub">Users opted into analytics</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <div class="kpi-title">Marketing Enabled</div>
                            <div class="kpi-icon kpi-amber"><i class="ph ph-megaphone"></i></div>
                        </div>
                        <div class="kpi-value"><?= number_format($marketingOn) ?></div>
                        <div class="kpi-sub">Users opted into marketing</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <div class="kpi-title">Preferences Enabled</div>
                            <div class="kpi-icon kpi-purple"><i class="ph ph-gear"></i></div>
                        </div>
                        <div class="kpi-value"><?= number_format($preferencesOn) ?></div>
                        <div class="kpi-sub">Users opted into preferences</div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="charts-grid">
                    <div class="card-box">
                        <div class="card-box-header"><i class="ph ph-chart-bar"></i> Daily Consent Trend (30 Days)</div>
                        <div class="card-box-body">
                            <?php
                            $chartData = [];
                            foreach ($dailyTrend as $row) {
                                $day = $row['day'];
                                if (!isset($chartData[$day])) $chartData[$day] = ['accepted_all'=>0,'rejected_all'=>0,'custom'=>0,'closed'=>0];
                                $chartData[$day][$row['consent_action']] = (int)$row['cnt'];
                            }
                            ksort($chartData);
                            $maxVal = 1;
                            foreach ($chartData as $d) {
                                $sum = array_sum($d);
                                if ($sum > $maxVal) $maxVal = $sum;
                            }
                            ?>
                            <?php if (!empty($chartData)): ?>
                            <div class="bar-chart">
                                <?php foreach (array_slice($chartData, -14) as $day => $vals):
                                    $total = array_sum($vals);
                                    $h = round($total / $maxVal * 120);
                                ?>
                                <div class="bar-col" title="<?= $day ?>: A <?= $vals['accepted_all'] ?> / R <?= $vals['rejected_all'] ?> / C <?= $vals['custom'] ?>">
                                    <div class="bar-stack" style="height:<?= $h ?>px;">
                                        <?php if ($vals['accepted_all']): ?><div class="bar-seg bar-accepted" style="height:<?= round($vals['accepted_all']/$total*100) ?>%"></div><?php endif; ?>
                                        <?php if ($vals['custom']): ?><div class="bar-seg bar-custom" style="height:<?= round($vals['custom']/$total*100) ?>%"></div><?php endif; ?>
                                        <?php if ($vals['rejected_all']): ?><div class="bar-seg bar-rejected" style="height:<?= round($vals['rejected_all']/$total*100) ?>%"></div><?php endif; ?>
                                        <?php if ($vals['closed']): ?><div class="bar-seg bar-closed" style="height:<?= round($vals['closed']/$total*100) ?>%"></div><?php endif; ?>
                                    </div>
                                    <div class="bar-label"><?= date('d', strtotime($day)) ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="display:flex;gap:16px;margin-top:12px;font-size:.75rem;color:rgba(15,23,42,0.5);flex-wrap:wrap;">
                                <span style="display:flex;align-items:center;gap:4px;"><span class="donut-legend-dot" style="background:#059669;"></span> Accepted</span>
                                <span style="display:flex;align-items:center;gap:4px;"><span class="donut-legend-dot" style="background:#3b82f6;"></span> Custom</span>
                                <span style="display:flex;align-items:center;gap:4px;"><span class="donut-legend-dot" style="background:#DC2626;"></span> Rejected</span>
                                <span style="display:flex;align-items:center;gap:4px;"><span class="donut-legend-dot" style="background:#94a3b8;"></span> Closed</span>
                            </div>
                            <?php else: ?>
                            <p style="text-align:center;color:rgba(15,23,42,0.3);padding:40px 0;">No data yet</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:20px;">
                        <div class="card-box">
                            <div class="card-box-header"><i class="ph ph-device-mobile"></i> Devices</div>
                            <div class="card-box-body">
                                <?php if (!empty($deviceStats)): ?>
                                <div style="display:flex;flex-direction:column;gap:10px;">
                                    <?php foreach ($deviceStats as $ds): ?>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="flex:1;">
                                            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                                <span style="font-size:.82rem;font-weight:600;color:#334155;text-transform:capitalize;"><?= htmlspecialchars($ds['device_type'] ?? 'Unknown') ?></span>
                                                <span style="font-size:.82rem;font-weight:700;color:#0f172a;"><?= $ds['cnt'] ?></span>
                                            </div>
                                            <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                                                <div style="height:100%;width:<?= $totalConsents > 0 ? round($ds['cnt']/$totalConsents*100) : 0 ?>%;background:#3b82f6;border-radius:3px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p style="text-align:center;color:rgba(15,23,42,0.3);padding:20px 0;">No data</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-box">
                            <div class="card-box-header"><i class="ph ph-globe"></i> Browsers</div>
                            <div class="card-box-body">
                                <?php if (!empty($browserStats)): ?>
                                <div style="display:flex;flex-direction:column;gap:10px;">
                                    <?php foreach (array_slice($browserStats, 0, 5) as $bs): ?>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="flex:1;">
                                            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                                <span style="font-size:.82rem;font-weight:600;color:#334155;"><?= htmlspecialchars($bs['browser'] ?? 'Unknown') ?></span>
                                                <span style="font-size:.82rem;font-weight:700;color:#0f172a;"><?= $bs['cnt'] ?></span>
                                            </div>
                                            <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                                                <div style="height:100%;width:<?= $totalConsents > 0 ? round($bs['cnt']/$totalConsents*100) : 0 ?>%;background:#8b5cf6;border-radius:3px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p style="text-align:center;color:rgba(15,23,42,0.3);padding:20px 0;">No data</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <form class="filter-bar" method="GET">
                    <div>
                        <label>Action</label>
                        <select name="action">
                            <option value="">All Actions</option>
                            <option value="accepted_all" <?= $actionFilter === 'accepted_all' ? 'selected' : '' ?>>Accepted All</option>
                            <option value="rejected_all" <?= $actionFilter === 'rejected_all' ? 'selected' : '' ?>>Rejected All</option>
                            <option value="custom" <?= $actionFilter === 'custom' ? 'selected' : '' ?>>Custom</option>
                            <option value="closed" <?= $actionFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                    <div>
                        <label>Device</label>
                        <select name="device">
                            <option value="">All Devices</option>
                            <option value="desktop" <?= $deviceFilter === 'desktop' ? 'selected' : '' ?>>Desktop</option>
                            <option value="mobile" <?= $deviceFilter === 'mobile' ? 'selected' : '' ?>>Mobile</option>
                            <option value="tablet" <?= $deviceFilter === 'tablet' ? 'selected' : '' ?>>Tablet</option>
                        </select>
                    </div>
                    <div>
                        <label>Browser</label>
                        <select name="browser">
                            <option value="">All Browsers</option>
                            <?php foreach ($browserStats as $bs): ?>
                            <option value="<?= htmlspecialchars($bs['browser']) ?>" <?= $browserFilter === $bs['browser'] ? 'selected' : '' ?>><?= htmlspecialchars($bs['browser'] ?? 'Unknown') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>From</label>
                        <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div>
                        <label>To</label>
                        <input type="date" name="to" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <button type="submit"><i class="ph ph-magnifying-glass"></i> Filter</button>
                    <a href="cookie_consents.php" class="filter-clear">Clear</a>
                </form>

                <!-- Records Table -->
                <div class="card-box">
                    <div class="card-box-header"><i class="ph ph-table"></i> Consent Records (<?= number_format($totalConsents) ?> total)</div>
                    <div class="card-box-body">
                        <div class="fb-table-wrap">
                            <table class="fb-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date & Time</th>
                                        <th>Action</th>
                                        <th>Categories</th>
                                        <th>Browser</th>
                                        <th>OS</th>
                                        <th>Device</th>
                                        <th>IP Address</th>
                                        <th>Page URL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($records)): ?>
                                    <?php foreach ($records as $i => $rec): ?>
                                    <tr>
                                        <td style="font-weight:600;color:rgba(15,23,42,0.4);"><?= $offset + $i + 1 ?></td>
                                        <td style="white-space:nowrap;font-size:.82rem;"><?= date('M d, Y H:i', strtotime($rec['created_at'])) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $rec['consent_action'] ?>">
                                                <?= str_replace('_', ' ', ucfirst(str_replace('_', ' ', $rec['consent_action']))) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="chip-toggle <?= $rec['analytics'] ? 'chip-on' : 'chip-off' ?>">A</span>
                                            <span class="chip-toggle <?= $rec['marketing'] ? 'chip-on' : 'chip-off' ?>">M</span>
                                            <span class="chip-toggle <?= $rec['preferences'] ? 'chip-on' : 'chip-off' ?>">P</span>
                                        </td>
                                        <td style="font-size:.85rem;"><?= htmlspecialchars($rec['browser'] ?? '-') ?></td>
                                        <td style="font-size:.85rem;"><?= htmlspecialchars($rec['os'] ?? '-') ?></td>
                                        <td style="font-size:.85rem;text-transform:capitalize;"><?= htmlspecialchars($rec['device_type'] ?? '-') ?></td>
                                        <td style="font-size:.82rem;font-family:monospace;color:rgba(15,23,42,0.5);"><?= htmlspecialchars($rec['ip_address'] ?? '-') ?></td>
                                        <td style="font-size:.8rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($rec['page_url'] ?? '') ?>"><?= htmlspecialchars($rec['page_url'] ?? '-') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr><td colspan="9" style="text-align:center;padding:40px;color:rgba(15,23,42,0.3);">No cookie consent records found</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                        <div class="pager">
                            <?php
                            $qp = $_GET;
                            if ($page > 1) { $qp['page'] = $page - 1; echo '<a href="?'.http_build_query($qp).'">&laquo; Prev</a>'; }
                            for ($p = max(1, $page - 3); $p <= min($totalPages, $page + 3); $p++) {
                                $qp['page'] = $p;
                                echo '<a href="?'.http_build_query($qp).'" class="'.($p === $page ? 'active' : '').'">'.$p.'</a>';
                            }
                            if ($page < $totalPages) { $qp['page'] = $page + 1; echo '<a href="?'.http_build_query($qp).'">Next &raquo;</a>'; }
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
