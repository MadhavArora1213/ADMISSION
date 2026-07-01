<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Fetch indices
$indices = $pdo->query("SELECT * FROM search_indices ORDER BY entity_type ASC")->fetchAll();

// Fetch synonyms
$synonyms = $pdo->query("SELECT * FROM search_synonyms ORDER BY canonical ASC")->fetchAll();

// Fetch suggestions
$suggestions = $pdo->query("SELECT * FROM search_suggestions ORDER BY frequency DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Configuration | Admin</title>
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
        .panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}
        .panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:0;display:flex;align-items:center;gap:8px}
        .panel-header{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        .panel-body{margin-top:20px;overflow-x:auto;-webkit-overflow-scrolling:touch}
        table{width:100%;border-collapse:collapse;font-size:.88rem}
        th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}
        th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}
        tr:hover{background:rgba(0,0,0,.015)}
        .badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700;display:inline-block;white-space:nowrap}
        .sub-links{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
        .sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s}
        .sub-link:hover,.sub-link.active{background:var(--primary);color:#fff}
        .btn{padding:8px 16px;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;border:none;display:inline-flex;align-items:center;gap:6px;text-decoration:none;white-space:nowrap}
        .btn-primary{background:var(--primary);color:#fff}
        .btn-primary:hover{opacity:.9}
        .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:24px}
        .json-block{font-family:monospace;font-size:.75rem;background:rgba(15,23,42,.04);padding:4px 8px;border-radius:4px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .tag-pill{display:inline-block;background:rgba(11,36,71,0.06);color:#19376D;padding:3px 8px;border-radius:12px;font-size:.75rem;margin:2px 4px 2px 0;white-space:nowrap}

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
            .panel{padding:16px;border-radius:12px}
            .panel-header{flex-direction:column;align-items:flex-start}
            .panel h3{font-size:1rem}
            .grid-2{grid-template-columns:1fr;gap:16px}
            th,td{padding:8px 10px;font-size:.8rem}
            .json-block{max-width:120px;font-size:.7rem}
        }
        @media(max-width:480px){
            .content-area{padding:8px}
            .page-header h2{font-size:1.1rem}
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
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" style="margin-left:16px;color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-sliders" style="color:var(--primary);"></i> Search Configuration</h2>
                    <p style="color:var(--text-muted);">Manage search indices, weights, synonyms, and autocomplete suggestions.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="search_analytics.php" class="sub-link"><i class="ph ph-chart-bar"></i> Analytics</a>
                <a href="search_config.php" class="sub-link active"><i class="ph ph-sliders"></i> Search Configuration</a>
            </div>

            <!-- Indices Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class="ph ph-database"></i> Search Indices Configuration</h3>
                    <button class="btn btn-primary"><i class="ph ph-arrows-clockwise"></i> Reindex All</button>
                </div>
                <div class="panel-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Index Name</th>
                                <th>Entity Type</th>
                                <th>Doc Count</th>
                                <th>Lang</th>
                                <th>Weights Config</th>
                                <th>Facets</th>
                                <th>Last Indexed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($indices as $idx): ?>
                            <tr>
                                <td style="font-weight:600;"><?php echo htmlspecialchars($idx['index_name']); ?></td>
                                <td><span class="badge" style="background:rgba(11,36,71,0.06);color:#19376D;"><?php echo ucfirst($idx['entity_type']); ?></span></td>
                                <td><?php echo number_format($idx['document_count']); ?></td>
                                <td><?php echo strtoupper($idx['language']); ?></td>
                                <td><div class="json-block"><?php echo htmlspecialchars($idx['search_weight_config']); ?></div></td>
                                <td><div class="json-block"><?php echo htmlspecialchars($idx['facets_config']); ?></div></td>
                                <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo $idx['indexed_at'] ? date('d M Y, H:i', strtotime($idx['indexed_at'])) : 'Never'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($indices)): ?>
                            <tr><td colspan="7" style="text-align:center; color:var(--text-muted);">No indices configured.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid-2">
                <!-- Synonyms Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h3><i class="ph ph-books"></i> Synonyms Dictionary</h3>
                        <button class="btn" style="padding:4px 8px; font-size:0.8rem; background:#F8FAFC; border:1px solid var(--border-color);"><i class="ph ph-plus"></i> Add</button>
                    </div>
                    <div class="panel-body">
                        <table>
                            <thead><tr><th>Canonical Term</th><th>Synonyms</th></tr></thead>
                            <tbody>
                                <?php foreach($synonyms as $syn): ?>
                                <tr>
                                    <td style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($syn['canonical']); ?></td>
                                    <td>
                                        <?php 
                                        $arr = json_decode($syn['synonyms'], true) ?: []; 
                                        foreach($arr as $s) echo '<span class="tag-pill">'.htmlspecialchars($s).'</span>';
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($synonyms)): ?>
                                <tr><td colspan="2" style="text-align:center; color:var(--text-muted);">No synonyms defined.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Suggestions Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h3><i class="ph ph-lightning"></i> Autocomplete Suggestions</h3>
                        <button class="btn" style="padding:4px 8px; font-size:0.8rem; background:#F8FAFC; border:1px solid var(--border-color);"><i class="ph ph-plus"></i> Add</button>
                    </div>
                    <div class="panel-body">
                        <table>
                            <thead><tr><th>Suggestion Text</th><th>Type</th><th>Frequency</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach($suggestions as $sug): ?>
                                <tr>
                                    <td style="font-weight:600;"><?php echo htmlspecialchars($sug['suggestion_text']); ?></td>
                                    <td><span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65);"><?php echo ucfirst($sug['suggestion_type']); ?></span></td>
                                    <td><?php echo number_format($sug['frequency']); ?></td>
                                    <td>
                                        <?php if($sug['is_active']): ?>
                                            <i class="ph-fill ph-check-circle" style="color:#0B2447;"></i> Active
                                        <?php else: ?>
                                            <i class="ph-fill ph-minus-circle" style="color:#0F172A;"></i> Inactive
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($suggestions)): ?>
                                <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No suggestions available.</td></tr>
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
