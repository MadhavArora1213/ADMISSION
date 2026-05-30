<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';

// Handle Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review_request') {
    $request_id = $_POST['request_id'];
    $status = $_POST['status']; // approved or rejected
    $review_notes = $_POST['review_notes'];
    
    $stmt = $pdo->prepare("UPDATE partner_content_requests SET status = ?, review_notes = ?, reviewed_by = ? WHERE id = ?");
    $stmt->execute([$status, $review_notes, $_SESSION['admin_id'], $request_id]);
    
    // NOTE: In V1, we just update the status. In V2, an "approved" status would automatically parse `submitted_data` JSON and UPDATE the colleges table.
    
    header("Location: partner_requests.php?tab=$tab&msg=Request " . ucfirst($status));
    exit;
}

// Fetch Requests
$where = "r.status = ?";
$params = [$tab];

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($search) {
    $where .= " AND (c.name LIKE ? OR r.content_type LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT r.*, c.name as college_name, pu.full_name as requested_by_name, u.full_name as reviewed_by_name
    FROM partner_content_requests r
    JOIN colleges c ON r.college_id = c.id
    JOIN partner_users pu ON r.requested_by = pu.id
    LEFT JOIN users u ON r.reviewed_by = u.id
    WHERE $where
    ORDER BY r.created_at " . ($tab === 'pending' ? 'ASC' : 'DESC') . "
    LIMIT 100
");
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Stats
$stat_pending = $pdo->query("SELECT COUNT(*) FROM partner_content_requests WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Requests | Admin Panel</title>
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
        .tab-link { padding: 10px 20px; font-weight: 600; color: var(--text-muted); font-size: 0.9rem; text-decoration: none; transition: all 0.2s; border-bottom: 3px solid transparent; display:flex; align-items:center; gap:8px;}
        .tab-link:hover { color: var(--primary); }
        .tab-link.active { color: var(--primary); border-bottom-color: var(--primary); }
        
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); padding: 0; margin-bottom: 24px; overflow: hidden;}
        
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top;}
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; background: #f8fafc; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap; text-transform: uppercase;}
        .t-info { background:#dbeafe; color:#1e40af; }
        .t-photo { background:#f3e8ff; color:#7e22ce; }
        .t-placement { background:#dcfce7; color:#166534; }
        .t-course { background:#ffedd5; color:#c2410c; }
        .t-ranking { background:#fce7f3; color:#be185d; }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 20px; border: 1px solid #bbf7d0; }
        
        .btn-primary { padding: 10px 20px; font-size: 0.9rem; background: var(--primary); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;}
        .btn-primary:hover { opacity: 0.9; }
        .btn-action { padding: 6px 10px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; color: var(--text-dark); text-decoration: none;}
        .btn-action:hover { background: #f1f5f9; }
        
        .search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 7px 14px; width: 250px;}
        .search-box input { border: none; outline: none; font-size: 0.9rem; width: 100%; }
        
        .json-viewer { background:#1e293b; color:#a5b4fc; padding:15px; border-radius:8px; font-family:monospace; font-size:0.8rem; white-space:pre-wrap; max-height:200px; overflow-y:auto; margin-top:10px; border:1px solid #0f172a;}
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; padding: 30px; border-radius: 12px; width: 800px; max-width: 90%; max-height: 90vh; overflow-y: auto;}
        .modal-header { font-size: 1.25rem; font-weight: 800; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;}
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
                    <h2><i class="ph ph-envelope-open" style="color:var(--primary);"></i> Content Requests</h2>
                    <p style="color:var(--text-muted);">Review and approve profile updates submitted by partner colleges.</p>
                </div>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div class="filter-bar" style="margin-bottom:0; border-bottom:none;">
                    <a href="?tab=pending" class="tab-link <?php echo $tab=='pending'?'active':''; ?>">
                        Pending Review
                        <?php if($stat_pending > 0): ?>
                            <span style="background:#dc2626; color:#fff; border-radius:10px; padding:2px 8px; font-size:0.7rem;"><?php echo $stat_pending; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=approved" class="tab-link <?php echo $tab=='approved'?'active':''; ?>">Approved</a>
                    <a href="?tab=rejected" class="tab-link <?php echo $tab=='rejected'?'active':''; ?>">Rejected</a>
                </div>
                <form method="GET">
                    <input type="hidden" name="tab" value="<?php echo $tab; ?>">
                    <div class="search-box">
                        <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                        <input type="text" name="q" placeholder="Search college..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </form>
            </div>

            <div class="panel" style="margin-top:20px;">
                <?php if(empty($requests)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:40px;">No <?php echo $tab; ?> requests found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>College & Requester</th>
                                <th>Update Type</th>
                                <th>Date Submitted</th>
                                <?php if($tab !== 'pending'): ?><th>Reviewed By</th><?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($requests as $r): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:var(--primary); font-size:1rem;"><?php echo htmlspecialchars($r['college_name']); ?></div>
                                    <div style="font-size:0.85rem; color:var(--text-muted); margin-top:4px;">By: <?php echo htmlspecialchars($r['requested_by_name']); ?></div>
                                </td>
                                <td>
                                    <span class="badge t-<?php echo $r['content_type']; ?>"><?php echo $r['content_type']; ?> Update</span>
                                </td>
                                <td>
                                    <div style="font-size:0.85rem;"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo date('h:i A', strtotime($r['created_at'])); ?></div>
                                </td>
                                <?php if($tab !== 'pending'): ?>
                                <td>
                                    <div style="font-size:0.85rem;"><?php echo htmlspecialchars($r['reviewed_by_name']); ?></div>
                                    <?php if($r['review_notes']): ?>
                                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px; max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($r['review_notes']); ?>">Note: <?php echo htmlspecialchars($r['review_notes']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <button class="btn-action" onclick='openReviewModal(<?php echo json_encode($r); ?>)'><i class="ph ph-eye"></i> View Details</button>
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

<!-- Modal -->
<div id="reviewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <div id="modalTitle" style="font-size:1.25rem;">Review Content Update</div>
                <div id="modalSubtitle" style="font-size:0.85rem; color:var(--text-muted); font-weight:400; margin-top:4px;"></div>
            </div>
            <button onclick="closeModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>
        
        <div>
            <h4 style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase;">Requested Changes (JSON Data)</h4>
            <div id="jsonViewer" class="json-viewer"></div>
        </div>
        
        <form method="POST" style="margin-top:20px;">
            <input type="hidden" name="action" value="review_request">
            <input type="hidden" name="request_id" id="request_id" value="">
            
            <div style="display:flex; flex-direction:column; gap:6px;">
                <label style="font-size:0.85rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Admin Review Notes</label>
                <textarea name="review_notes" id="review_notes" rows="3" style="padding:10px; border:1px solid var(--border-color); border-radius:8px; font-family:inherit; font-size:0.9rem;" placeholder="Enter notes about why this was approved or rejected..."></textarea>
            </div>
            
            <div style="margin-top:24px; display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-color); padding-top:15px;">
                <button type="button" class="btn-action" style="padding:10px 20px;" onclick="closeModal()">Cancel</button>
                
                <div style="display:flex; gap:10px;" id="actionButtons">
                    <button type="submit" name="status" value="rejected" class="btn-action" style="color:#dc2626; border-color:#fca5a5;"><i class="ph ph-x"></i> Reject</button>
                    <button type="submit" name="status" value="approved" class="btn-primary" style="background:#166534;"><i class="ph ph-check"></i> Approve Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openReviewModal(r) {
    document.getElementById('reviewModal').classList.add('active');
    document.getElementById('modalSubtitle').innerText = r.college_name + " - " + r.content_type.toUpperCase() + " Update";
    document.getElementById('request_id').value = r.id;
    document.getElementById('review_notes').value = r.review_notes || '';
    
    // Format JSON
    try {
        let parsed = JSON.parse(r.submitted_data);
        document.getElementById('jsonViewer').innerText = JSON.stringify(parsed, null, 2);
    } catch(e) {
        document.getElementById('jsonViewer').innerText = r.submitted_data;
    }
    
    // Hide action buttons if not pending
    if (r.status !== 'pending') {
        document.getElementById('actionButtons').style.display = 'none';
        document.getElementById('review_notes').readOnly = true;
    } else {
        document.getElementById('actionButtons').style.display = 'flex';
        document.getElementById('review_notes').readOnly = false;
    }
}

function closeModal() {
    document.getElementById('reviewModal').classList.remove('active');
}
</script>
</body>
</html>
