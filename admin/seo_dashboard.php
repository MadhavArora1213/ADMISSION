<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Fetch SEO templates
$templates = $pdo->query("SELECT * FROM seo_templates ORDER BY created_at DESC")->fetchAll();

// Fetch Sitemaps
$sitemaps = $pdo->query("SELECT * FROM sitemaps ORDER BY last_generated_at DESC")->fetchAll();

// General Stats
$indexedCount = $pdo->query("SELECT COUNT(*) FROM seo_meta WHERE google_index_status = 'indexed'")->fetchColumn();
$notIndexedCount = $pdo->query("SELECT COUNT(*) FROM seo_meta WHERE google_index_status = 'not_indexed'")->fetchColumn();
$brokenLinks = $pdo->query("SELECT COUNT(*) FROM internal_links WHERE is_broken = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Dashboard | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light);margin:0}
        .admin-layout{display:flex;min-height:100vh}
        .sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto;z-index:100;transition:transform .3s ease}
        .sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}
        .sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}
        .sidebar-nav{padding:24px 0;flex:1}
        .sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s;text-decoration:none}
        .sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}
        .main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;min-width:0;padding-bottom:60px}
        .topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}
        .content-area{padding:32px}
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:12px;flex-wrap:wrap}
        .page-header h2{font-size:2rem;font-weight:800}
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
        .stat-card{background:#fff;border-radius:12px;border:1px solid var(--border-color);padding:20px;box-shadow:var(--shadow-sm)}
        .stat-card .num{font-size:2rem;font-weight:800;color:var(--primary)}
        .stat-card .label{font-size:.8rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-top:4px}
        .panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}
        .panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:0;display:flex;align-items:center;gap:8px}
        .panel-header{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        .panel-body{margin-top:20px;overflow-x:auto;-webkit-overflow-scrolling:touch}
        .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:24px}
        table{width:100%;border-collapse:collapse;font-size:.88rem}
        th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}
        th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}
        tr:hover{background:rgba(0,0,0,.015)}
        .badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700;display:inline-block;white-space:nowrap}
        .sub-links{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
        .sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}
        .sub-link:hover,.sub-link.active{background:var(--primary);color:#fff}
        .btn{padding:6px 12px;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer;border:none;display:inline-flex;align-items:center;gap:6px;text-decoration:none;white-space:nowrap}
        .btn-primary{background:var(--primary);color:#fff}
        .btn-primary:hover{opacity:.9}

        .mobile-menu-btn{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#0f172a;padding:4px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:90}

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .sidebar-overlay.show{display:block}
            .main-content{margin-left:0}
            .mobile-menu-btn{display:block}
            .topbar{height:auto;min-height:56px;padding:10px 12px;justify-content:space-between}
            .content-area{padding:12px}
            .page-header{flex-direction:column;align-items:flex-start}
            .page-header h2{font-size:1.3rem}
            .stats-grid{grid-template-columns:repeat(2,1fr);gap:12px}
            .stat-card{padding:14px}
            .stat-card .num{font-size:1.4rem}
            .panel{padding:16px;border-radius:12px}
            .panel-header{flex-direction:column;align-items:flex-start}
            .panel h3{font-size:1rem}
            .grid-2{grid-template-columns:1fr;gap:16px}
            th,td{padding:8px 10px;font-size:.8rem}
        }
        @media(max-width:480px){
            .content-area{padding:8px}
            .page-header h2{font-size:1.1rem}
            .stats-grid{grid-template-columns:1fr}
            .panel{padding:12px}
            th,td{padding:6px 8px;font-size:.75rem}
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
                <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                <a href="logout.php" style="margin-left:16px;color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-globe" style="color:var(--primary);"></i> SEO Dashboard</h2>
                    <p style="color:var(--text-muted);">Manage templates, sitemaps, and view indexing stats.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="seo_dashboard.php" class="sub-link active"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="seo_meta.php" class="sub-link"><i class="ph ph-tag"></i> Meta Tags & Schema</a>
                <a href="redirects.php" class="sub-link"><i class="ph ph-arrows-left-right"></i> Redirects</a>
                <a href="sitemaps.php" class="sub-link"><i class="ph ph-map-trifold"></i> Sitemaps</a>
                <a href="internal_links.php" class="sub-link"><i class="ph ph-link-break"></i> Internal Links</a>
                <a href="seo_templates.php" class="sub-link"><i class="ph ph-file-code"></i> SEO Templates</a>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="num"><?php echo number_format($indexedCount); ?></div><div class="label">Indexed Pages</div></div>
                <div class="stat-card"><div class="num" style="color:#19376D;"><?php echo number_format($notIndexedCount); ?></div><div class="label">Not Indexed</div></div>
                <div class="stat-card"><div class="num" style="color:#0F172A;"><?php echo number_format($brokenLinks); ?></div><div class="label">Broken Internal Links</div></div>
                <div class="stat-card"><div class="num"><?php echo count($templates); ?></div><div class="label">Active SEO Templates</div></div>
            </div>

            <div class="grid-2">
                <!-- SEO Templates Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h3><i class="ph ph-file-code"></i> Programmatic SEO Templates</h3>
                        <button class="btn btn-primary"><i class="ph ph-plus"></i> Add Template</button>
                    </div>
                    <div class="panel-body">
                        <table>
                            <thead><tr><th>Template Name</th><th>Data Source</th><th>Generated</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach($templates as $tpl): ?>
                                <tr>
                                    <td style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($tpl['template_name']); ?><div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($tpl['template_slug_pattern']); ?></div></td>
                                    <td><span class="badge" style="background:rgba(11,36,71,0.06);color:#19376D;"><?php echo ucfirst($tpl['data_source']); ?></span></td>
                                    <td><?php echo number_format($tpl['pages_generated']); ?></td>
                                    <td>
                                        <?php if($tpl['is_active']): ?><i class="ph-fill ph-check-circle" style="color:#0B2447;"></i> Active
                                        <?php else: ?><i class="ph-fill ph-minus-circle" style="color:#0F172A;"></i> Inactive<?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($templates)): ?>
                                <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No programmatic SEO templates configured.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sitemaps Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h3><i class="ph ph-map-trifold"></i> XML Sitemaps</h3>
                        <button class="btn" style="background:#F8FAFC; border:1px solid var(--border-color);"><i class="ph ph-arrows-clockwise"></i> Generate All</button>
                    </div>
                    <div class="panel-body">
                        <table>
                            <thead><tr><th>Sitemap</th><th>Type</th><th>URLs</th><th>Last Generated</th></tr></thead>
                            <tbody>
                                <?php foreach($sitemaps as $sm): ?>
                                <tr>
                                    <td style="font-weight:600;"><a href="<?php echo htmlspecialchars($sm['sitemap_url']); ?>" target="_blank" style="color:var(--primary); text-decoration:none;"><?php echo htmlspecialchars($sm['sitemap_name']); ?></a></td>
                                    <td><span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65);"><?php echo ucfirst($sm['sitemap_type']); ?></span></td>
                                    <td><?php echo number_format($sm['url_count']); ?></td>
                                    <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo $sm['last_generated_at'] ? date('d M Y, H:i', strtotime($sm['last_generated_at'])) : 'Never'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($sitemaps)): ?>
                                <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No sitemaps available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
