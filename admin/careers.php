<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM careers WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header('Location: careers.php?msg=deleted');
        exit;
    } catch (Exception $e) {
        $error = 'Failed to delete career: ' . $e->getMessage();
    }
}

// Fetch Careers (with search)
$search = trim($_GET['q'] ?? '');
try {
    if ($search !== '') {
        $stmt = $pdo->prepare("SELECT * FROM careers WHERE name LIKE ? OR stream LIKE ? OR sub_stream LIKE ? ORDER BY name ASC");
        $stmt->execute(['%' . $search . '%', '%' . $search . '%', '%' . $search . '%']);
    } else {
        $stmt = $pdo->query("SELECT * FROM careers ORDER BY stream ASC, name ASC");
    }
    $careers = $stmt->fetchAll();
} catch (Exception $e) {
    $careers = [];
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Careers | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; font-weight: 700; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 14px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none; font-size: 0.92rem; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }
        
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; text-align: left; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; color: #0f172a; }
        
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); }
        .search-bar { max-width: 400px; display: flex; gap: 10px; margin-bottom: 20px; }
        .search-bar input { flex: 1; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; outline: none; }
        .search-bar button { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 650; cursor: pointer; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 0.92rem; }
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; background: rgba(0,0,0,0.01); }
        tr:hover { background-color: rgba(0,0,0,0.01); }
        
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
        .stream-badge.science { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .stream-badge.commerce { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .stream-badge.humanities { background: #faf5ff; color: #6b21a8; border: 1px solid #e9d5ff; }
        
        .action-links { display: flex; gap: 8px; }
        .action-btn { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #fff; color: var(--text-dark); border: 1px solid var(--border-color); text-decoration: none; transition: all 0.2s; }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.delete:hover { background: #dc2626; color: white; border-color: #dc2626; }
        
        .msg-alert { padding: 16px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 24px; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 8px; font-weight: 600; }
        .error-alert { padding: 16px; border-radius: 8px; background: #fee2e2; color: #991b1b; margin-bottom: 24px; border: 1px solid #fecaca; display: flex; align-items: center; gap: 8px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <header class="topbar">
                <div class="user-profile">
                    <span style="font-weight:700; color:#334155;"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" style="margin-left: 16px; color: #ef4444;" title="Logout"><i class="ph ph-sign-out" style="font-size: 1.4rem;"></i></a>
                </div>
            </header>
            
            <div class="content-area">
                <div class="page-header">
                    <div>
                        <h2>Manage Career Pathways</h2>
                        <p style="color: var(--text-muted); margin-top: 4px;">Create, update, and manage student career tracks for the Counselling wizard.</p>
                    </div>
                    <a href="career_form.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #19376d; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 700;"><i class="ph ph-plus"></i> Add New Career</a>
                </div>
                
                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
                    <div class="msg-alert"><i class="ph ph-check-circle"></i> Career path deleted successfully.</div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="error-alert"><i class="ph ph-warning-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="panel">
                    <!-- Search Filter -->
                    <form method="GET" action="careers.php" class="search-bar">
                        <input type="text" name="q" placeholder="Search by name, stream, or sub-stream..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                    </form>
                    
                    <?php if (empty($careers)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding: 40px; font-weight: 600;">No career paths found.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Career Name</th>
                                        <th>Stream</th>
                                        <th>Interest Category</th>
                                        <th>Salary Range</th>
                                        <th>Is Popular</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($careers as $c): ?>
                                    <tr>
                                        <td style="font-weight: 700; color: #0b2447;">
                                            <?php echo htmlspecialchars($c['name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge stream-badge <?php echo strtolower($c['stream']); ?>">
                                                <?php echo htmlspecialchars($c['stream']); ?>
                                            </span>
                                        </td>
                                        <td style="font-weight: 600; color: #475569;">
                                            <?php echo htmlspecialchars($c['sub_stream']); ?>
                                        </td>
                                        <td style="font-weight: 650; color: #10b981;">
                                            ₹ <?php echo htmlspecialchars($c['salary_range']); ?>
                                        </td>
                                        <td>
                                            <?php if ($c['is_popular']): ?>
                                                <span style="color: #fbbf24; font-size: 1.15rem;" title="Yes"><i class="ph-fill ph-star"></i></span>
                                            <?php else: ?>
                                                <span style="color: #cbd5e1; font-size: 1.15rem;" title="No"><i class="ph ph-star"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-links">
                                                <a href="career_form.php?id=<?php echo $c['id']; ?>" class="action-btn" title="Edit"><i class="ph ph-pencil-simple"></i></a>
                                                <a href="careers.php?action=delete&id=<?php echo $c['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirm('Are you sure you want to delete this career?');"><i class="ph ph-trash"></i></a>
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
