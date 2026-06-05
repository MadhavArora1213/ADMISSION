<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle Delete Partner
if (isset($_GET['action']) && $_GET['action'] === 'delete_partner' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM partners WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: partners.php?msg=" . urlencode("Partner deleted successfully."));
    exit;
}

// Handle Add/Edit Partner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_partner') {
    $partner_id = !empty($_POST['partner_id']) ? $_POST['partner_id'] : null;
    $partner_college_id = $_POST['partner_college_id'];
    $contact_person = $_POST['contact_person'];
    $designation = $_POST['designation'];
    $leads_quota = (int)$_POST['leads_quota'];
    $contract_start = !empty($_POST['contract_start']) ? $_POST['contract_start'] : null;
    $contract_end = !empty($_POST['contract_end']) ? $_POST['contract_end'] : null;
    $onboarding_status = $_POST['onboarding_status'];
    $status = $_POST['status'];

    if ($partner_id) {
        $stmt = $pdo->prepare("
            UPDATE partners 
            SET contact_person=?, designation=?, leads_quota=?, contract_start=?, contract_end=?, onboarding_status=?, status=?
            WHERE id=?
        ");
        $stmt->execute([$contact_person, $designation, $leads_quota, $contract_start, $contract_end, $onboarding_status, $status, $partner_id]);
        $msg = "Partner updated successfully.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO partners (id, partner_college_id, contact_person, designation, leads_quota, contract_start, contract_end, onboarding_status, status)
            VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        try {
            $stmt->execute([$partner_college_id, $contact_person, $designation, $leads_quota, $contract_start, $contract_end, $onboarding_status, $status]);
            $msg = "Partner created successfully.";
        } catch(PDOException $e) {
            $msg = "Error: Partner account for this college already exists.";
        }
    }
    header("Location: partners.php?msg=" . urlencode($msg));
    exit;
}

// Fetch all colleges for dropdown
$colleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name")->fetchAll();

// Fetch Partners
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = "1=1";
$params = [];
if ($search) {
    $where .= " AND (c.name LIKE ? OR p.contact_person LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT p.*, c.name as college_name 
    FROM partners p 
    JOIN colleges c ON p.partner_college_id = c.id 
    WHERE $where 
    ORDER BY p.created_at DESC 
    LIMIT 100
");
$stmt->execute($params);
$partners = $stmt->fetchAll();

// Stats
$stat_active = $pdo->query("SELECT COUNT(*) FROM partners WHERE status = 'active'")->fetchColumn();
$stat_trial = $pdo->query("SELECT COUNT(*) FROM partners WHERE status = 'trial'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Management | Admin Panel</title>
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
        
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); padding: 0; margin-bottom: 24px; overflow: hidden;}
        
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top;}
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; background: #f8fafc; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap; text-transform: uppercase;}
        .s-active { background:#dcfce7; color:#166534; }
        .s-suspended { background:#fee2e2; color:#dc2626; }
        .s-trial { background:#fefce8; color:#ca8a04; }
        .s-churned { background:#f1f5f9; color:#475569; }
        
        .o-pending { background:#fee2e2; color:#dc2626; }
        .o-in_progress { background:#dbeafe; color:#1e40af; }
        .o-completed { background:#dcfce7; color:#166534; }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 20px; border: 1px solid #bbf7d0; }
        
        .btn-primary { padding: 10px 20px; font-size: 0.9rem; background: var(--primary); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;}
        .btn-primary:hover { opacity: 0.9; }
        .btn-action { padding: 6px 10px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; color: var(--text-dark); text-decoration: none;}
        .btn-action:hover { background: #f1f5f9; }
        
        .search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 7px 14px; width: 250px;}
        .search-box input { border: none; outline: none; font-size: 0.9rem; width: 100%; }
        
        .progress-bar { height: 6px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 6px; }
        .progress-fill { height: 100%; background: var(--primary); }
        .progress-fill.danger { background: #dc2626; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; padding: 30px; border-radius: 12px; width: 600px; max-width: 90%; max-height: 90vh; overflow-y: auto;}
        .modal-header { font-size: 1.25rem; font-weight: 800; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;}
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;}
        .form-group input, .form-group select { padding: 10px; font-size: 0.9rem; border: 1px solid var(--border-color); border-radius: 8px; background: #fff;}
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
                    <h2><i class="ph ph-handshake" style="color:var(--primary);"></i> Partner Accounts</h2>
                    <p style="color:var(--text-muted);">Manage B2B college accounts, quotas, and onboarding status.</p>
                </div>
                <button class="btn-primary" onclick="openModal()"><i class="ph ph-plus"></i> Add Partner</button>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-info"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>

            <div style="display:flex; justify-content:space-between; margin-bottom:15px; align-items:center;">
                <div style="display:flex; gap:15px;">
                    <div style="background:#fff; border:1px solid var(--border-color); padding:10px 20px; border-radius:8px;">
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase;">Active Partners</div>
                        <div style="font-size:1.5rem; font-weight:800; color:#166534;"><?php echo number_format($stat_active); ?></div>
                    </div>
                    <div style="background:#fff; border:1px solid var(--border-color); padding:10px 20px; border-radius:8px;">
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase;">On Trial</div>
                        <div style="font-size:1.5rem; font-weight:800; color:#ca8a04;"><?php echo number_format($stat_trial); ?></div>
                    </div>
                </div>
                <form method="GET">
                    <div class="search-box">
                        <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                        <input type="text" name="q" placeholder="Search college or contact..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </form>
            </div>

            <div class="panel">
                <?php if(empty($partners)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:40px;">No partners found.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>College & Contact</th>
                                <th>Status & Onboarding</th>
                                <th>Leads Quota</th>
                                <th>Contract Period</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($partners as $p): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:var(--primary); font-size:1rem;"><?php echo htmlspecialchars($p['college_name']); ?></div>
                                    <div style="font-size:0.85rem; color:var(--text-muted); margin-top:4px;"><i class="ph ph-user"></i> <?php echo htmlspecialchars($p['contact_person']); ?></div>
                                    <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($p['designation']); ?></div>
                                </td>
                                <td>
                                    <div style="margin-bottom:6px;"><span class="badge s-<?php echo $p['status']; ?>"><?php echo $p['status']; ?></span></div>
                                    <div><span class="badge o-<?php echo $p['onboarding_status']; ?>"><i class="ph ph-rocket"></i> <?php echo str_replace('_',' ',$p['onboarding_status']); ?></span></div>
                                </td>
                                <td>
                                    <?php 
                                        $quota = $p['leads_quota'];
                                        $used = $p['leads_used'];
                                        $pct = $quota > 0 ? min(100, ($used/$quota)*100) : 0;
                                        $is_danger = $pct > 90;
                                    ?>
                                    <div style="font-size:0.85rem; font-weight:700;"><?php echo number_format($used); ?> / <?php echo $quota ? number_format($quota) : '&infin;'; ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">Leads Used</div>
                                    <div class="progress-bar">
                                        <div class="progress-fill <?php echo $is_danger?'danger':''; ?>" style="width:<?php echo $pct; ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:0.85rem;"><strong>Start:</strong> <?php echo $p['contract_start'] ? date('M d, Y', strtotime($p['contract_start'])) : '—'; ?></div>
                                    <div style="font-size:0.85rem; margin-top:4px;"><strong>End:</strong> <?php echo $p['contract_end'] ? date('M d, Y', strtotime($p['contract_end'])) : '—'; ?></div>
                                </td>
                                <td style="display: flex; gap: 8px;">
                                    <button class="btn-action" onclick='editPartner(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8"); ?>)'><i class="ph ph-pencil-simple"></i> Edit</button>
                                    <a href="?action=delete_partner&id=<?php echo urlencode($p['id']); ?>" class="btn-action" style="color:#dc2626; border-color:#fca5a5;" onclick="return confirm('Are you sure you want to delete this partner account? This action cannot be undone.');"><i class="ph ph-trash"></i> Delete</a>
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
<div id="partnerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span id="modalTitle">Add Partner</span>
            <button onclick="closeModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_partner">
            <input type="hidden" name="partner_id" id="partner_id" value="">
            
            <div class="form-grid">
                <div class="form-group full">
                    <label>Linked College</label>
                    <select name="partner_college_id" id="partner_college_id" required>
                        <option value="">-- Select College --</option>
                        <?php foreach($colleges as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Primary Contact Person</label>
                    <input type="text" name="contact_person" id="contact_person" required>
                </div>
                <div class="form-group">
                    <label>Designation</label>
                    <input type="text" name="designation" id="designation">
                </div>

                <div class="form-group">
                    <label>Account Status</label>
                    <select name="status" id="status">
                        <option value="trial">Trial</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="churned">Churned</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Onboarding Status</label>
                    <select name="onboarding_status" id="onboarding_status">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label>Monthly Leads Quota (0 = Unlimited)</label>
                    <input type="number" name="leads_quota" id="leads_quota" value="100">
                </div>

                <div class="form-group">
                    <label>Contract Start Date</label>
                    <input type="date" name="contract_start" id="contract_start">
                </div>
                <div class="form-group">
                    <label>Contract End Date</label>
                    <input type="date" name="contract_end" id="contract_end">
                </div>
            </div>
            
            <div style="margin-top:24px; text-align:right; border-top:1px solid var(--border-color); padding-top:15px;">
                <button type="button" class="btn-action" style="padding:10px 20px;" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary" style="margin-left:10px;">Save Partner</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('partnerModal').classList.add('active');
    document.getElementById('modalTitle').innerText = 'Add Partner Account';
    document.getElementById('partner_id').value = '';
    document.getElementById('partner_college_id').value = '';
    document.getElementById('contact_person').value = '';
    document.getElementById('designation').value = '';
    document.getElementById('status').value = 'trial';
    document.getElementById('onboarding_status').value = 'pending';
    document.getElementById('leads_quota').value = '100';
    document.getElementById('contract_start').value = '';
    document.getElementById('contract_end').value = '';
    // Prevent editing the college link if updating
    document.getElementById('partner_college_id').disabled = false;
}

function closeModal() {
    document.getElementById('partnerModal').classList.remove('active');
}

function editPartner(p) {
    document.getElementById('partnerModal').classList.add('active');
    document.getElementById('modalTitle').innerText = 'Edit Partner Account';
    document.getElementById('partner_id').value = p.id;
    document.getElementById('partner_college_id').value = p.partner_college_id;
    document.getElementById('contact_person').value = p.contact_person;
    document.getElementById('designation').value = p.designation;
    document.getElementById('status').value = p.status;
    document.getElementById('onboarding_status').value = p.onboarding_status;
    document.getElementById('leads_quota').value = p.leads_quota;
    document.getElementById('contract_start').value = p.contract_start;
    document.getElementById('contract_end').value = p.contract_end;
    // Don't allow changing the attached college once created
    document.getElementById('partner_college_id').disabled = true;
}

// Enable the select on submit if disabled
document.querySelector('#partnerModal form').addEventListener('submit', function() {
    document.getElementById('partner_college_id').disabled = false;
});
</script>
</body>
</html>
