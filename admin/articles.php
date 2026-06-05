<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$_GET['id']]);
    header("Location: articles.php?msg=deleted");
    exit;
}

$where = [];
$params = [];
if ($type_filter !== 'all') { $where[] = "a.article_type = ?"; $params[] = $type_filter; }
if ($status_filter !== 'all') { $where[] = "a.status = ?"; $params[] = $status_filter; }
if ($search !== '') { $where[] = "(a.article_title LIKE ? OR a.excerpt LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("SELECT a.*, COALESCE(u.full_name, a.custom_author_name) AS author_name, ac.category_name FROM articles a LEFT JOIN users u ON a.author_id = u.id LEFT JOIN article_categories ac ON a.category_id = ac.id $whereSQL ORDER BY a.created_at DESC LIMIT 100");
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Count stats
$stats = $pdo->query("SELECT status, COUNT(*) AS cnt FROM articles GROUP BY status")->fetchAll();
$counts = ['draft'=>0,'pending_review'=>0,'published'=>0,'archived'=>0];
foreach($stats as $s) $counts[$s['status']] = $s['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles & CMS | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #f8fafc; border-radius: 12px; border: 1px solid var(--border-color); padding: 20px; box-shadow: var(--shadow-sm); }
        .stat-card .num { font-size: 2rem; font-weight: 800; }
        .stat-card .label { font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-top: 4px; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .tab-link { padding: 7px 14px; font-weight: 600; color: var(--text-muted); border-radius: 8px; border: 1px solid var(--border-color); background: #f8fafc; font-size: 0.85rem; text-decoration: none; transition: all 0.2s; white-space: nowrap; }
        .tab-link:hover, .tab-link.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; background: #f1f5f9; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap; }
        .s-draft { background:#f1f5f9; color:#475569; }
        .s-pending_review { background:#fef9c3; color:#854d0e; }
        .s-published { background:#dcfce7; color:#166534; }
        .s-archived { background:#f1f5f9; color:#94a3b8; }
        .t-blog { background:#ede9fe; color:#5b21b6; }
        .t-news { background:#dbeafe; color:#1e40af; }
        .t-guide { background:#dcfce7; color:#166534; }
        .t-exam_update { background:#ffedd5; color:#c2410c; }
        .t-opinion { background:#fce7f3; color:#9d174d; }
        .t-ranking { background:#fef9c3; color:#854d0e; }
        .action-btn { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--text-dark); border: 1px solid var(--border-color); text-decoration: none; }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.delete:hover { background: #dc2626; border-color: #dc2626; }
        .search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 7px 14px; }
        .search-box input { border: none; outline: none; font-size: 0.9rem; width: 220px; }
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 20px; border: 1px solid #bbf7d0; }
        .sub-links { display: flex; gap: 8px; margin-bottom: 20px; }
        .sub-link { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); text-decoration: none; padding: 5px 10px; border-radius: 6px; transition: all 0.2s; }
        .sub-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" style="margin-left:16px; color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-newspaper" style="color:var(--primary);"></i> Articles & CMS</h2>
                    <p style="color:var(--text-muted);">Create, manage and schedule all content.</p>
                </div>
                <a href="article_form.php" class="btn btn-primary"><i class="ph ph-plus"></i> New Article</a>
            </div>

            <div class="sub-links">
                <a href="articles.php" class="sub-link" style="color:var(--primary); font-weight:700;"><i class="ph ph-article"></i> Articles</a>
                <a href="article_categories.php" class="sub-link"><i class="ph ph-folders"></i> Categories</a>
                <a href="media_library.php" class="sub-link"><i class="ph ph-images"></i> Media Library</a>
                <a href="tags.php" class="sub-link"><i class="ph ph-tag"></i> Tags</a>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg']=='deleted'): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Article deleted.</div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card"><div class="num"><?php echo array_sum($counts); ?></div><div class="label">Total Articles</div></div>
                <div class="stat-card"><div class="num" style="color:#166534;"><?php echo $counts['published']; ?></div><div class="label">Published</div></div>
                <div class="stat-card"><div class="num" style="color:#854d0e;"><?php echo $counts['pending_review']; ?></div><div class="label">Pending Review</div></div>
                <div class="stat-card"><div class="num" style="color:#475569;"><?php echo $counts['draft']; ?></div><div class="label">Drafts</div></div>
            </div>

            <div class="filter-bar">
                <a href="?status=all&type=<?php echo $type_filter; ?>" class="tab-link <?php echo $status_filter=='all'?'active':''; ?>">All Status</a>
                <a href="?status=draft&type=<?php echo $type_filter; ?>" class="tab-link <?php echo $status_filter=='draft'?'active':''; ?>">Draft</a>
                <a href="?status=pending_review&type=<?php echo $type_filter; ?>" class="tab-link <?php echo $status_filter=='pending_review'?'active':''; ?>">Pending</a>
                <a href="?status=published&type=<?php echo $type_filter; ?>" class="tab-link <?php echo $status_filter=='published'?'active':''; ?>">Published</a>
                <a href="?status=archived&type=<?php echo $type_filter; ?>" class="tab-link <?php echo $status_filter=='archived'?'active':''; ?>">Archived</a>
                <div style="width:1px; height:28px; background:var(--border-color); margin:0 4px;"></div>
                <a href="?type=all&status=<?php echo $status_filter; ?>" class="tab-link <?php echo $type_filter=='all'?'active':''; ?>">All Types</a>
                <?php foreach(['blog','news','guide','exam_update','opinion','ranking'] as $t): ?>
                <a href="?type=<?php echo $t; ?>&status=<?php echo $status_filter; ?>" class="tab-link <?php echo $type_filter==$t?'active':''; ?>"><?php echo ucfirst(str_replace('_',' ',$t)); ?></a>
                <?php endforeach; ?>
                <form method="GET" style="margin-left:auto; display:flex; gap:8px; align-items:center;">
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                    <input type="hidden" name="type" value="<?php echo $type_filter; ?>">
                    <div class="search-box"><i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i><input type="text" name="q" placeholder="Search articles..." value="<?php echo htmlspecialchars($search); ?>"></div>
                    <button type="submit" class="btn btn-primary" style="padding:8px 14px;">Go</button>
                </form>
            </div>

            <div class="panel">
                <?php if(empty($articles)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:40px;">No articles found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Author</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Published At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($articles as $a): ?>
                            <tr>
                                <td style="max-width:280px;">
                                    <div style="font-weight:600; color:var(--primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:280px;" title="<?php echo htmlspecialchars($a['article_title']); ?>"><?php echo htmlspecialchars($a['article_title']); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">/<?php echo htmlspecialchars($a['article_slug']); ?></div>
                                </td>
                                <td><span class="badge t-<?php echo $a['article_type']; ?>"><?php echo ucfirst(str_replace('_',' ',$a['article_type'])); ?></span></td>
                                <td style="font-size:0.85rem;"><?php echo htmlspecialchars($a['category_name'] ?: '—'); ?></td>
                                <td style="font-size:0.85rem;"><?php echo htmlspecialchars($a['author_name'] ?: '—'); ?></td>
                                <td><span class="badge s-<?php echo $a['status']; ?>"><?php echo ucfirst(str_replace('_',' ',$a['status'])); ?></span></td>
                                <td style="font-size:0.85rem;"><?php echo number_format($a['view_count']); ?></td>
                                <td style="font-size:0.82rem; white-space:nowrap;"><?php echo $a['publish_at'] ? date('d M Y', strtotime($a['publish_at'])) : '—'; ?></td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="article_form.php?id=<?php echo $a['id']; ?>" class="action-btn" title="Edit"><i class="ph ph-pencil-simple"></i></a>
                                        <a href="?action=delete&id=<?php echo $a['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this article?')" title="Delete"><i class="ph ph-trash"></i></a>
                                    </div>
                                </td>
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
