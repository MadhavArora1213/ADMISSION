<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

$msg = '';
$error = '';

// Handle form submissions — PRG pattern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    if ($action === 'toggle') {
        $collegeId = isset($_POST['college_id']) ? (int) $_POST['college_id'] : 0;
        if ($collegeId > 0) {
            $checkStmt = $pdo->prepare("SELECT is_featured, featured_order FROM colleges WHERE id = ?");
            $checkStmt->execute([$collegeId]);
            $college = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($college) {
                $newFeatured = $college['is_featured'] ? 0 : 1;
                $newOrder = $newFeatured ? 0 : null;
                $updateStmt = $pdo->prepare("UPDATE colleges SET is_featured = ?, featured_order = ? WHERE id = ?");
                $updateStmt->execute([$newFeatured, $newOrder, $collegeId]);
                $msg = $newFeatured ? "College marked as featured!" : "College removed from featured.";
            } else {
                $error = "College not found in database.";
            }
        } else {
            $error = "Invalid college ID. POST=" . json_encode($_POST);
        }
        $loc = "featured_colleges.php";
        if ($msg) $loc .= "?msg=" . urlencode($msg);
        elseif ($error) $loc .= "?err=" . urlencode($error);
        header("Location: $loc");
        exit;
    }

    if ($action === 'set_order') {
        $collegeId = isset($_POST['college_id']) ? (int) $_POST['college_id'] : 0;
        $order = isset($_POST['featured_order']) ? (int) $_POST['featured_order'] : 0;
        if ($collegeId > 0 && $order >= 0 && $order <= 6) {
            if ($order > 0) {
                $dupStmt = $pdo->prepare("SELECT id, name FROM colleges WHERE featured_order = ? AND id != ? AND is_featured = 1");
                $dupStmt->execute([$order, $collegeId]);
                $dup = $dupStmt->fetch(PDO::FETCH_ASSOC);
                if ($dup) {
                    $error = "Order #{$order} is already taken by {$dup['name']}.";
                    header("Location: featured_colleges.php?err=" . urlencode($error));
                    exit;
                }
            }
            $updateStmt = $pdo->prepare("UPDATE colleges SET featured_order = ? WHERE id = ?");
            $updateStmt->execute([$order > 0 ? $order : null, $collegeId]);
            $msg = "Featured order updated!";
        } else {
            $error = "Invalid data.";
        }
        $loc = "featured_colleges.php";
        if ($msg) $loc .= "?msg=" . urlencode($msg);
        elseif ($error) $loc .= "?err=" . urlencode($error);
        header("Location: $loc");
        exit;
    }

    if ($action === 'clear_all') {
        $pdo->exec("UPDATE colleges SET is_featured = 0, featured_order = NULL");
        $msg = "All colleges removed from featured.";
        header("Location: featured_colleges.php?msg=" . urlencode($msg));
        exit;
    }
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];
if (isset($_GET['err'])) $error = $_GET['err'];

$featuredColleges = $pdo->query("SELECT c.id AS college_id, c.name, c.slug, c.college_type, c.is_featured, c.featured_order,
    c.overall_rating_avg, c.ranking_nirf, c.naac_grade,
    s.name AS state_name, ci.name AS city_name, cm.cover_image_url
    FROM colleges c
    LEFT JOIN states s ON c.state_id=s.id
    LEFT JOIN cities ci ON c.city_id=ci.id
    LEFT JOIN college_media cm ON cm.college_id=c.id AND (cm.image_type='cover' OR cm.image_type IS NULL)
    WHERE c.status='active' AND c.is_featured=1
    ORDER BY c.featured_order ASC, c.overall_rating_avg DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

$availableColleges = $pdo->query("SELECT c.id AS college_id, c.name, ci.name AS city_name, c.overall_rating_avg, c.ranking_nirf
    FROM colleges c LEFT JOIN cities ci ON c.city_id=ci.id
    WHERE c.status='active' AND (c.is_featured=0 OR c.is_featured IS NULL)
    ORDER BY c.overall_rating_avg DESC, c.ranking_nirf ASC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Colleges | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background-color:var(--bg-light)}
        .admin-layout{display:flex;min-height:100vh}
        .sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto;z-index:50;transition:transform .3s ease}
        .sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,.1)}
        .sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}
        .sidebar-nav{padding:24px 0;flex:1}
        .sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s ease}
        .sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}
        .sidebar-nav a i{font-size:1.25rem}
        .main-content{flex:1;margin-left:280px;display:flex;flex-direction:column}
        .topbar{height:64px;background:#fff;border-bottom:1px solid rgba(15,23,42,.08);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:40}
        .header-left{display:flex;align-items:center;gap:12px}
        .header-right{display:flex;align-items:center;gap:16px}
        #topbarToggle{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#0f172a;padding:4px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:49}
        .content-area{padding:32px}
        .page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;gap:12px;flex-wrap:wrap}
        .page-header h2{font-size:2rem;font-weight:800}
        .panel{background:#f8fafc;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}
        .section-title{font-size:1.1rem;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
        .featured-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
        .featured-item{background:#fff;border:2px solid var(--border-color);border-radius:12px;overflow:hidden;transition:all .2s;position:relative}
        .featured-item.ordered{border-color:#22c55e}
        .featured-item-img{width:100%;height:140px;object-fit:cover}
        .featured-item-body{padding:14px 16px 16px}
        .featured-item-name{font-size:.95rem;font-weight:700;color:var(--text-dark);margin-bottom:4px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
        .featured-item-meta{font-size:.78rem;color:var(--text-muted);margin-bottom:8px}
        .featured-item-tags{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:10px}
        .featured-item-tag{font-size:.62rem;font-weight:700;padding:2px 6px;border-radius:4px;text-transform:uppercase}
        .tag-college{background:#dbeafe;color:#1e40af}
        .tag-rating{background:#fef3c7;color:#92400e}
        .tag-nirf{background:#e0e7ff;color:#4338ca}
        .tag-naac{background:#d1fae5;color:#065f46}
        .featured-item-actions{display:flex;gap:8px;align-items:center}
        .featured-item-actions select{padding:6px 8px;border:1px solid var(--border-color);border-radius:6px;font-size:.8rem;background:#fff}
        .btn{padding:10px 20px;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer;text-decoration:none;border:none;transition:all .2s;display:inline-flex;align-items:center;gap:6px}
        .btn-primary{background:var(--primary);color:#fff}
        .btn-primary:hover{background:var(--primary-dark)}
        .btn-danger{background:#ef4444;color:#fff}
        .btn-danger:hover{background:#dc2626}
        .btn-sm{padding:6px 12px;font-size:.78rem}
        .msg-alert{padding:16px;border-radius:8px;background:rgba(34,197,94,.08);color:#166534;margin-bottom:24px;border:1px solid rgba(34,197,94,.2);display:flex;align-items:center;gap:8px}
        .msg-error{padding:16px;border-radius:8px;background:rgba(239,68,68,.04);color:#991b1b;margin-bottom:24px;border:1px solid rgba(239,68,68,.1);display:flex;align-items:center;gap:8px}
        .hint-text{font-size:.88rem;color:var(--text-muted);margin-bottom:20px;line-height:1.6}
        .order-badge{position:absolute;top:8px;left:8px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;font-size:.65rem;font-weight:800;padding:4px 10px;border-radius:6px;z-index:2}
        @media(max-width:1024px){.sidebar{transform:translateX(-100%)!important}.sidebar.open{transform:translateX(0)!important}.sidebar-overlay.show{display:block}#topbarToggle{display:inline-flex!important}.main-content{margin-left:0!important}.content-area{padding:16px!important}.featured-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:768px){.topbar{height:56px!important;padding:0 12px!important}.content-area{padding:12px!important}.page-header h2{font-size:1.2rem!important}.panel{padding:16px!important;border-radius:12px!important}.featured-grid{grid-template-columns:1fr;gap:12px}}
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        <main class="main-content">
            <header class="topbar">
                <div class="header-left">
                    <button onclick="toggleSidebar()" id="topbarToggle"><i class="ph ph-list"></i></button>
                    <div style="font-weight:700;color:#0f172a"><i class="ph ph-trophy"></i> Featured Colleges</div>
                </div>
                <div class="header-right">
                    <span style="font-size:.88rem;color:rgba(15,23,42,.65)"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
                    <a href="logout.php" style="color:#0f172a;font-size:1.2rem"><i class="ph ph-sign-out"></i></a>
                </div>
            </header>
            <div class="content-area">
                <div class="page-header">
                    <div>
                        <h2>Featured Colleges</h2>
                        <p style="color:var(--text-muted)">Manage which colleges appear on the homepage "Curated Institutions" section.</p>
                    </div>
                </div>
                <?php if ($msg): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="msg-error"><i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="panel">
                    <div class="section-title"><i class="ph ph-trophy"></i> Current Featured (<?= count($featuredColleges) ?> / 6)</div>
                    <p class="hint-text">These colleges appear on the homepage. Set order 1-6 to control display order.</p>
                    <?php if (!empty($featuredColleges)): ?>
                    <div class="featured-grid">
                        <?php foreach ($featuredColleges as $fc): ?>
                        <div class="featured-item <?= !empty($fc['featured_order']) ? 'ordered' : '' ?>">
                            <?php if (!empty($fc['featured_order'])): ?>
                            <div class="order-badge">#<?= (int)$fc['featured_order'] ?></div>
                            <?php endif; ?>
                            <?php
                            $imgUrl = 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80';
                            if (!empty($fc['cover_image_url'])) {
                                $imgUrl = str_starts_with($fc['cover_image_url'], 'http') ? $fc['cover_image_url'] : '../' . ltrim($fc['cover_image_url'], '/');
                            }
                            ?>
                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="" class="featured-item-img">
                            <div class="featured-item-body">
                                <div class="featured-item-tags">
                                    <span class="featured-item-tag tag-college"><?= ucfirst(htmlspecialchars($fc['college_type'] ?? 'College')) ?></span>
                                    <?php if (!empty($fc['overall_rating_avg']) && (float)$fc['overall_rating_avg'] > 0): ?>
                                    <span class="featured-item-tag tag-rating"><i class="ph-fill ph-star"></i> <?= number_format((float)$fc['overall_rating_avg'], 1) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($fc['ranking_nirf'])): ?>
                                    <span class="featured-item-tag tag-nirf">#<?= htmlspecialchars($fc['ranking_nirf']) ?> NIRF</span>
                                    <?php endif; ?>
                                    <?php if (!empty($fc['naac_grade'])): ?>
                                    <span class="featured-item-tag tag-naac">NAAC <?= htmlspecialchars($fc['naac_grade']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="featured-item-name"><?= htmlspecialchars($fc['name']) ?></div>
                                <div class="featured-item-meta"><?= htmlspecialchars(($fc['city_name'] ?? '') . (($fc['city_name'] ?? '') && ($fc['state_name'] ?? '') ? ', ' : '') . ($fc['state_name'] ?? '')) ?></div>
                                <div class="featured-item-actions">
                                    <form method="POST" action="featured_colleges.php">
                                        <input type="hidden" name="action" value="set_order">
                                        <input type="hidden" name="college_id" value="<?= (int)$fc['college_id'] ?>">
                                        <select name="featured_order" onchange="this.form.submit()">
                                            <option value="0" <?= empty($fc['featured_order']) ? 'selected' : '' ?>>Auto</option>
                                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                            <option value="<?= $i ?>" <?= (int)($fc['featured_order'] ?? 0) === $i ? 'selected' : '' ?>>Order <?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </form>
                                    <form method="POST" action="featured_colleges.php" onsubmit="return confirm('Remove from featured?')">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="college_id" value="<?= (int)$fc['college_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="ph ph-x"></i> Remove</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:16px">
                        <form method="POST" action="featured_colleges.php" onsubmit="return confirm('Remove ALL from featured?')">
                            <input type="hidden" name="action" value="clear_all">
                            <button type="submit" class="btn btn-danger"><i class="ph ph-trash"></i> Clear All Featured</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div style="text-align:center;padding:40px;color:#94a3b8">
                        <i class="ph ph-trophy" style="font-size:2.5rem;display:block;margin-bottom:12px;opacity:.15"></i>
                        <p>No featured colleges yet. Add some below!</p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="section-title"><i class="ph ph-plus-circle"></i> Add College to Featured</div>
                    <p class="hint-text">Select a college below and mark it as featured. Maximum 6 featured colleges.</p>
                    <?php if (count($featuredColleges) < 6): ?>
                    <div style="max-height:400px;overflow-y:auto;border:1px solid var(--border-color);border-radius:8px">
                        <table style="width:100%;border-collapse:collapse;font-size:.88rem">
                            <thead style="position:sticky;top:0;background:#fff;z-index:1">
                                <tr style="border-bottom:1px solid var(--border-color);text-align:left">
                                    <th style="padding:12px 16px;font-weight:700;color:var(--text-muted)">College Name</th>
                                    <th style="padding:12px 16px;font-weight:700;color:var(--text-muted)">Location</th>
                                    <th style="padding:12px 16px;font-weight:700;color:var(--text-muted)">Rating</th>
                                    <th style="padding:12px 16px;font-weight:700;color:var(--text-muted)">NIRF</th>
                                    <th style="padding:12px 16px;font-weight:700;color:var(--text-muted)">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($availableColleges as $ac): ?>
                                <tr style="border-bottom:1px solid #f1f5f9">
                                    <td style="padding:10px 16px;font-weight:600"><?= htmlspecialchars($ac['name']) ?></td>
                                    <td style="padding:10px 16px;color:var(--text-muted);font-size:.82rem"><?= htmlspecialchars($ac['city_name'] ?? '') ?></td>
                                    <td style="padding:10px 16px;font-size:.82rem"><?= !empty($ac['overall_rating_avg']) && (float)$ac['overall_rating_avg'] > 0 ? number_format((float)$ac['overall_rating_avg'], 1) : '—' ?></td>
                                    <td style="padding:10px 16px;font-size:.82rem"><?= !empty($ac['ranking_nirf']) ? '#' . htmlspecialchars($ac['ranking_nirf']) : '—' ?></td>
                                    <td style="padding:10px 16px">
                                        <!-- DEBUG: <?= htmlspecialchars(json_encode(array_keys($ac))) ?> -->
                                        <form method="POST" action="featured_colleges.php">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="college_id" value="<?= (int)($ac['college_id'] ?? $ac['id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-primary btn-sm"><i class="ph ph-plus"></i> Feature</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div style="text-align:center;padding:24px;color:#f59e0b;background:#fef3c7;border-radius:8px">
                        <i class="ph ph-warning-circle" style="font-size:1.2rem"></i> Maximum 6 featured colleges reached.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
<script>
function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show')}
</script>
</body>
</html>
