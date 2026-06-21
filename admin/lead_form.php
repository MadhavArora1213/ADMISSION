<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$is_edit = $id !== null;
$error = '';

// Dropdowns
$collegesStmt = $pdo->query("SELECT id, name FROM colleges ORDER BY name ASC");
$colleges = $collegesStmt->fetchAll();
$coursesStmt = $pdo->query("SELECT id, course_name FROM courses ORDER BY course_name ASC");
$courses = $coursesStmt->fetchAll();
$counsellorsStmt = $pdo->query("SELECT id, full_name as name FROM users ORDER BY full_name ASC");
$counsellors = $counsellorsStmt->fetchAll();

// Handle Save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tab = $_POST['_tab'] ?? 'lead';
    try {
        if ($tab == 'lead') {
            $newId = $id ?: sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));

            $data = [
                'name'            => $_POST['name'] ?: null,
                'lead_type'       => $_POST['lead_type'] ?: null,
                'phone'           => $_POST['phone'],
                'email'           => $_POST['email'],
                'city'            => $_POST['city'] ?: null,
                'state'           => $_POST['state'] ?: null,
                'college_id'      => $_POST['college_id'] ?: null,
                'course_id'       => $_POST['course_id'] ?: null,
                'class_12_score'  => $_POST['class_12_score'] ?: null,
                'target_year'     => $_POST['target_year'] ?: null,
                'preferred_budget'=> $_POST['preferred_budget'] ?: null,
                'source_page'     => $_POST['source_page'] ?: null,
                'utm_source'      => $_POST['utm_source'] ?: null,
                'utm_medium'      => $_POST['utm_medium'] ?: null,
                'utm_campaign'    => $_POST['utm_campaign'] ?: null,
                'gclid'           => $_POST['gclid'] ?: null,
            ];
            if ($is_edit) {
                $sets = [];
                foreach($data as $k=>$v) $sets[] = "$k = :$k";
                $data['id'] = $id;
                $pdo->prepare("UPDATE leads SET ".implode(', ',$sets)." WHERE id = :id")->execute($data);
            } else {
                $data['id'] = $newId;
                $data['lead_status'] = 'new';
                $data['priority'] = 'medium';
                $data['delivery_status'] = 'pending';
                $keys = array_keys($data);
                $pdo->prepare("INSERT INTO leads (".implode(',',$keys).") VALUES (:".implode(', :',$keys).")")->execute($data);
                $id = $newId;
                $is_edit = true;
            }
        } elseif ($tab == 'crm') {
            $data = [
                'lead_status'       => $_POST['lead_status'] ?: 'new',
                'assigned_to'       => $_POST['assigned_to'] ?: null,
                'priority'          => $_POST['priority'] ?: 'medium',
                'disposition'       => $_POST['disposition'] ?: null,
                'call_attempts'     => $_POST['call_attempts'] ?: 0,
                'counsellor_notes'  => $_POST['counsellor_notes'] ?: null,
                'next_followup_at'  => $_POST['next_followup_at'] ?: null,
                'id'                => $id,
            ];
            $pdo->prepare("UPDATE leads SET lead_status=:lead_status, assigned_to=:assigned_to, priority=:priority, disposition=:disposition, call_attempts=:call_attempts, counsellor_notes=:counsellor_notes, next_followup_at=:next_followup_at WHERE id=:id")->execute($data);
        } elseif ($tab == 'delivery') {
            $data = [
                'delivery_status'      => $_POST['delivery_status'] ?: 'pending',
                'dispute_reason'       => $_POST['dispute_reason'] ?: null,
                'dispute_outcome'      => $_POST['dispute_outcome'] ?: null,
                'is_blacklisted'       => isset($_POST['is_blacklisted']) ? 1 : 0,
                'blacklist_reason'     => $_POST['blacklist_reason'] ?: null,
                'id'                   => $id,
            ];
            $pdo->prepare("UPDATE leads SET delivery_status=:delivery_status, dispute_reason=:dispute_reason, dispute_outcome=:dispute_outcome, is_blacklisted=:is_blacklisted, blacklist_reason=:blacklist_reason WHERE id=:id")->execute($data);
        } elseif ($tab == 'call') {
            // Add call log
            $callId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
            $pdo->prepare("INSERT INTO lead_call_logs (id, lead_id, call_at, duration_seconds, outcome, recording_url, notes, called_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$callId, $id, $_POST['call_at']?: date('Y-m-d H:i:s'), $_POST['duration_seconds']?:null, $_POST['outcome']?:null, $_POST['recording_url']?:null, $_POST['notes']?:null, $_SESSION['admin_id']]);
            // Update last_contacted_at
            $pdo->prepare("UPDATE leads SET last_contacted_at = NOW(), call_attempts = call_attempts + 1 WHERE id = ?")->execute([$id]);
        }
        header("Location: lead_form.php?id=$id&tab=" . ($_POST['_tab']??'lead') . "&msg=saved");
        exit;
    } catch(Exception $e) { $error = "Error: " . $e->getMessage(); }
}

$lead = [];
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
    $stmt->execute([$id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lead) { header('Location: leads.php'); exit; }
}

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'lead';

// Call logs
$callLogs = [];
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT cl.*, u.full_name as caller_name FROM lead_call_logs cl LEFT JOIN users u ON cl.called_by = u.id WHERE cl.lead_id = ? ORDER BY cl.call_at DESC");
    $stmt->execute([$id]);
    $callLogs = $stmt->fetchAll();
}

function v($arr, $key, $def='') { return isset($arr[$key]) ? htmlspecialchars($arr[$key]) : $def; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Lead' : 'Add Lead'; ?> | AdmissionSeason Admin</title>
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
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; padding-bottom: 60px; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 1000px; margin: 0 auto; width: 100%; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 12px; }
        .tabs-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); overflow-x: auto; padding-bottom: 12px; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; transition: all 0.2s; white-space: nowrap; text-decoration: none; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        .tab-link.disabled { opacity: 0.5; cursor: not-allowed; }
        .form-section { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 32px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
        .form-section h3 { font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 24px; display: flex; align-items: center; gap: 8px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.9rem; color: var(--text-muted); }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem; box-sizing: border-box; }
        .form-actions { display: flex; justify-content: flex-end; gap: 16px; margin-top: 24px; }
        .error-alert { padding: 16px; border-radius: 8px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; margin-bottom: 24px; }
        .msg-alert { padding: 16px; border-radius: 8px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; margin-bottom: 24px; }
        .call-log-item { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; margin-bottom: 12px; }
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
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
                <h2><a href="leads.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left"></i></a> <?php echo $is_edit ? 'Edit Lead' : 'Add New Lead'; ?></h2>
            </div>

            <?php if($is_edit): ?>
            <div class="tabs-nav">
                <a href="?id=<?php echo $id; ?>&tab=lead" class="tab-link <?php echo $current_tab=='lead'?'active':''; ?>"><i class="ph ph-user"></i> Lead Info</a>
                <a href="?id=<?php echo $id; ?>&tab=crm" class="tab-link <?php echo $current_tab=='crm'?'active':''; ?>"><i class="ph ph-kanban"></i> CRM & Assignment</a>
                <a href="?id=<?php echo $id; ?>&tab=delivery" class="tab-link <?php echo $current_tab=='delivery'?'active':''; ?>"><i class="ph ph-truck"></i> Delivery & Disputes</a>
                <a href="?id=<?php echo $id; ?>&tab=attribution" class="tab-link <?php echo $current_tab=='attribution'?'active':''; ?>"><i class="ph ph-git-branch"></i> Attribution</a>
                <a href="?id=<?php echo $id; ?>&tab=calls" class="tab-link <?php echo $current_tab=='calls'?'active':''; ?>"><i class="ph ph-phone"></i> Call Logs</a>
            </div>
            <?php else: ?>
            <div class="tabs-nav"><span class="tab-link active">Lead Info</span></div>
            <?php endif; ?>

            <?php if($error): ?><div class="error-alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg']=='saved'): ?><div class="msg-alert"><i class="ph ph-check-circle"></i> Saved successfully!</div><?php endif; ?>

            <?php if($current_tab == 'lead'): ?>
            <form method="POST">
                <input type="hidden" name="_tab" value="lead">
                <div class="form-section">
                    <h3><i class="ph ph-user-circle"></i> Contact Details</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Full Name</label><input type="text" name="name" class="form-control" value="<?php echo v($lead,'name'); ?>"></div>
                        <div class="form-group"><label>Phone *</label><input type="text" name="phone" class="form-control" required value="<?php echo v($lead,'phone'); ?>"></div>
                        <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required value="<?php echo v($lead,'email'); ?>"></div>
                        <div class="form-group"><label>City</label><input type="text" name="city" class="form-control" value="<?php echo v($lead,'city'); ?>"></div>
                        <div class="form-group"><label>State</label><input type="text" name="state" class="form-control" value="<?php echo v($lead,'state'); ?>"></div>
                    </div>
                </div>
                <div class="form-section">
                    <h3><i class="ph ph-graduation-cap"></i> Academic Preferences</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Lead Type</label>
                            <select name="lead_type" class="form-control">
                                <option value="">-- Select --</option>
                                <?php foreach(['inquiry','callback','download','apply','chat_exit'] as $lt): ?>
                                <option value="<?php echo $lt; ?>" <?php echo v($lead,'lead_type')==$lt?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$lt)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Target Year</label><input type="number" name="target_year" class="form-control" value="<?php echo v($lead,'target_year'); ?>" placeholder="2025"></div>
                        <div class="form-group"><label>College Interest</label>
                            <select name="college_id" class="form-control">
                                <option value="">-- None --</option>
                                <?php foreach($colleges as $col): ?>
                                <option value="<?php echo $col['id']; ?>" <?php echo v($lead,'college_id')==$col['id']?'selected':''; ?>><?php echo htmlspecialchars($col['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Course Interest</label>
                            <select name="course_id" class="form-control">
                                <option value="">-- None --</option>
                                <?php foreach($courses as $cr): ?>
                                <option value="<?php echo $cr['id']; ?>" <?php echo v($lead,'course_id')==$cr['id']?'selected':''; ?>><?php echo htmlspecialchars($cr['course_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Class 12 Score (%)</label><input type="number" step="0.01" name="class_12_score" class="form-control" value="<?php echo v($lead,'class_12_score'); ?>"></div>
                        <div class="form-group"><label>Preferred Budget (₹)</label><input type="number" step="0.01" name="preferred_budget" class="form-control" value="<?php echo v($lead,'preferred_budget'); ?>"></div>
                    </div>
                </div>
                <div class="form-section">
                    <h3><i class="ph ph-link"></i> UTM / Source Tracking</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Source Page</label><input type="text" name="source_page" class="form-control" value="<?php echo v($lead,'source_page'); ?>"></div>
                        <div class="form-group"><label>UTM Source</label><input type="text" name="utm_source" class="form-control" value="<?php echo v($lead,'utm_source'); ?>"></div>
                        <div class="form-group"><label>UTM Medium</label><input type="text" name="utm_medium" class="form-control" value="<?php echo v($lead,'utm_medium'); ?>"></div>
                        <div class="form-group"><label>UTM Campaign</label><input type="text" name="utm_campaign" class="form-control" value="<?php echo v($lead,'utm_campaign'); ?>"></div>
                        <div class="form-group"><label>GCLID (Google Click ID)</label><input type="text" name="gclid" class="form-control" value="<?php echo v($lead,'gclid'); ?>"></div>
                    </div>
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Lead Info</button></div>
            </form>

            <?php elseif($current_tab == 'crm'): ?>
            <form method="POST">
                <input type="hidden" name="_tab" value="crm">
                <div class="form-section">
                    <h3><i class="ph ph-kanban"></i> CRM & Assignment</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Lead Status</label>
                            <select name="lead_status" class="form-control">
                                <?php foreach(['new','contacted','qualified','converted','lost','invalid'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo v($lead,'lead_status')==$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Priority</label>
                            <select name="priority" class="form-control">
                                <?php foreach(['low','medium','high','urgent'] as $p): ?>
                                <option value="<?php echo $p; ?>" <?php echo v($lead,'priority')==$p?'selected':''; ?>><?php echo ucfirst($p); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Assign To (Counsellor)</label>
                            <select name="assigned_to" class="form-control">
                                <option value="">-- Unassigned --</option>
                                <?php foreach($counsellors as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo v($lead,'assigned_to')==$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Disposition</label>
                            <select name="disposition" class="form-control">
                                <option value="">-- Select --</option>
                                <?php foreach(['not_reachable','interested','not_interested','wrong_number'] as $d): ?>
                                <option value="<?php echo $d; ?>" <?php echo v($lead,'disposition')==$d?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$d)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Call Attempts</label><input type="number" name="call_attempts" class="form-control" value="<?php echo v($lead,'call_attempts','0'); ?>"></div>
                        <div class="form-group"><label>Next Follow-up At</label><input type="datetime-local" name="next_followup_at" class="form-control" value="<?php echo $lead['next_followup_at'] ? date('Y-m-d\TH:i', strtotime($lead['next_followup_at'])) : ''; ?>"></div>
                        <div class="form-group full"><label>Counsellor Notes</label><textarea name="counsellor_notes" class="form-control" rows="4"><?php echo v($lead,'counsellor_notes'); ?></textarea></div>
                    </div>
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save CRM Data</button></div>
            </form>

            <?php elseif($current_tab == 'delivery'): ?>
            <form method="POST">
                <input type="hidden" name="_tab" value="delivery">
                <div class="form-section">
                    <h3><i class="ph ph-truck"></i> Delivery & Disputes</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Delivery Status</label>
                            <select name="delivery_status" class="form-control">
                                <?php foreach(['pending','delivered','failed','disputed'] as $ds): ?>
                                <option value="<?php echo $ds; ?>" <?php echo v($lead,'delivery_status')==$ds?'selected':''; ?>><?php echo ucfirst($ds); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Dispute Outcome</label>
                            <select name="dispute_outcome" class="form-control">
                                <option value="">-- None --</option>
                                <option value="credited" <?php echo v($lead,'dispute_outcome')=='credited'?'selected':''; ?>>Credited</option>
                                <option value="rejected" <?php echo v($lead,'dispute_outcome')=='rejected'?'selected':''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="form-group full"><label>Dispute Reason</label><textarea name="dispute_reason" class="form-control" rows="3"><?php echo v($lead,'dispute_reason'); ?></textarea></div>
                        <div class="form-group full" style="display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" name="is_blacklisted" id="is_blacklisted" <?php echo !empty($lead['is_blacklisted'])?'checked':''; ?>>
                            <label for="is_blacklisted" style="margin:0; cursor:pointer;">Blacklist this lead</label>
                        </div>
                        <div class="form-group full"><label>Blacklist Reason</label><textarea name="blacklist_reason" class="form-control" rows="2"><?php echo v($lead,'blacklist_reason'); ?></textarea></div>
                    </div>
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Delivery Info</button></div>
            </form>

            <?php elseif($current_tab == 'attribution'): ?>
            <div class="form-section">
                <h3><i class="ph ph-git-branch"></i> Attribution Data</h3>
                <div style="display:flex; flex-direction:column; gap:0;">
                    <?php
                    $attrs = [
                        'Attribution Model' => 'attribution_model',
                        'First Touch Source' => 'first_touch_source',
                        'Last Touch Source' => 'last_touch_source',
                        'Revenue Attributed' => 'revenue_attributed',
                    ];
                    foreach($attrs as $label => $key):
                    ?>
                    <div style="display:flex; padding:12px 0; border-bottom:1px solid var(--border-color);">
                        <div style="width:40%; font-weight:600; color:var(--text-muted); font-size:0.9rem;"><?php echo $label; ?></div>
                        <div style="width:60%; font-weight:500;"><?php echo v($lead,$key) ?: '—'; ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(!empty($lead['touchpoints_json'])): ?>
                    <div style="margin-top:20px;">
                        <h4 style="font-size:0.9rem; color:var(--text-muted); text-transform:uppercase; margin-bottom:8px;">Touchpoints JSON</h4>
                        <pre style="background:#f1f5f9; padding:16px; border-radius:8px; font-size:0.85rem; overflow:auto;"><?php echo htmlspecialchars(json_encode(json_decode($lead['touchpoints_json']), JSON_PRETTY_PRINT)); ?></pre>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php elseif($current_tab == 'calls'): ?>
            <form method="POST">
                <input type="hidden" name="_tab" value="call">
                <div class="form-section">
                    <h3><i class="ph ph-phone-plus"></i> Log a New Call</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Call At</label><input type="datetime-local" name="call_at" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>"></div>
                        <div class="form-group"><label>Duration (seconds)</label><input type="number" name="duration_seconds" class="form-control" placeholder="120"></div>
                        <div class="form-group"><label>Outcome</label>
                            <select name="outcome" class="form-control">
                                <option value="">-- Select --</option>
                                <option value="answered">Answered</option>
                                <option value="no_answer">No Answer</option>
                                <option value="voicemail">Voicemail</option>
                                <option value="busy">Busy</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Recording URL</label><input type="url" name="recording_url" class="form-control" placeholder="https://..."></div>
                        <div class="form-group full"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="ph ph-phone"></i> Log Call</button>
                </div>
            </form>

            <div class="form-section">
                <h3><i class="ph ph-clock-countdown"></i> Call History</h3>
                <?php if(empty($callLogs)): ?>
                    <p style="color:var(--text-muted);">No calls logged yet.</p>
                <?php else: foreach($callLogs as $cl): ?>
                <div class="call-log-item">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <div style="font-weight:700;"><?php echo date('d M Y, H:i', strtotime($cl['call_at'])); ?></div>
                        <span class="badge" style="background:#e0e7ff;color:#3730a3;"><?php echo ucfirst(str_replace('_',' ',$cl['outcome'])); ?></span>
                    </div>
                    <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:6px;">Duration: <?php echo $cl['duration_seconds'] ? $cl['duration_seconds'].'s' : 'N/A'; ?> &nbsp;|&nbsp; By: <?php echo htmlspecialchars($cl['caller_name'] ?: 'Admin'); ?></div>
                    <?php if($cl['notes']): ?><div style="font-size:0.9rem;"><?php echo nl2br(htmlspecialchars($cl['notes'])); ?></div><?php endif; ?>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>
</body>
</html>
