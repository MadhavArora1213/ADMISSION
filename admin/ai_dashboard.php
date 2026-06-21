<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle tab selection
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'config';

// Handle Config Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_config') {
    $stmt = $pdo->prepare("UPDATE ai_config SET 
        model_name = ?, system_prompt = ?, temperature = ?, max_tokens = ?, 
        fallback_response = ?, session_memory = ?, escalation_keywords = ?, 
        lead_capture_enabled = ?, whatsapp_bot_enabled = ?, response_language = ?, 
        spam_threshold = ?, auto_approve_threshold = ?, auto_reject_threshold = ?
        WHERE id = ?");
    
    // assuming id 1 is the primary config
    $stmt->execute([
        $_POST['model_name'], $_POST['system_prompt'], $_POST['temperature'], $_POST['max_tokens'],
        $_POST['fallback_response'], isset($_POST['session_memory']) ? 1 : 0, $_POST['escalation_keywords'],
        isset($_POST['lead_capture_enabled']) ? 1 : 0, isset($_POST['whatsapp_bot_enabled']) ? 1 : 0, $_POST['response_language'],
        $_POST['spam_threshold'], $_POST['auto_approve_threshold'], $_POST['auto_reject_threshold'], 1
    ]);
    header("Location: ai_dashboard.php?tab=config&msg=updated");
    exit;
}

// Handle Recommendations Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_recs') {
    $stmt = $pdo->prepare("UPDATE ai_recommendations SET 
        algo_type = ?, feature_weights = ?, user_profile_fields = ?, recommendation_limit = ?, 
        personalization_enabled = ?, model_version = ?, retrain_schedule = ?, ab_test_variant = ?
        WHERE id = ?");
    
    // assuming id 1 is the primary config
    $stmt->execute([
        $_POST['algo_type'], $_POST['feature_weights'], $_POST['user_profile_fields'], $_POST['recommendation_limit'],
        isset($_POST['personalization_enabled']) ? 1 : 0, $_POST['model_version'], $_POST['retrain_schedule'], $_POST['ab_test_variant'], 1
    ]);
    header("Location: ai_dashboard.php?tab=recommendations&msg=updated");
    exit;
}

// Fetch Data
$config = $pdo->query("SELECT * FROM ai_config LIMIT 1")->fetch();
$recs = $pdo->query("SELECT * FROM ai_recommendations LIMIT 1")->fetch();

// Fetch Logs for display
$predictor_logs = $pdo->query("SELECT p.*, e.exam_name as exam_name, c.course_name as course_name FROM predictor_submissions p LEFT JOIN exams e ON p.predictor_exam_id = e.id LEFT JOIN courses c ON p.input_course_pref = c.id ORDER BY p.created_at DESC LIMIT 50")->fetchAll();
$chat_logs = $pdo->query("SELECT * FROM ai_chat_sessions ORDER BY created_at DESC LIMIT 50")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Systems | Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700}.sub-links{display:flex;gap:8px;margin-bottom:20px; border-bottom:1px solid var(--border-color); padding-bottom:12px;}.sub-link{font-size:.95rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:8px 16px;border-radius:6px;transition:all .2s}.sub-link:hover,.sub-link.active{background:var(--primary);color:#fff;}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
        .form-group{margin-bottom:16px;}
        .form-group label{display:block;font-size:.85rem;font-weight:700;color:var(--text-main);margin-bottom:8px;}
        .form-control{width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:8px;font-size:.9rem;font-family:inherit;}
        .btn-primary{background:var(--primary);color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:600;cursor:pointer;transition:all .2s;}
        .btn-primary:hover{background:#0B2447;}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:0.9rem;font-weight:600;}
        .alert-success{background:rgba(11,36,71,0.04);color:#0B2447;}
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></span>
                <a href="logout.php" style="margin-left:16px;color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-robot" style="color:var(--primary);"></i> AI Engine Control Center</h2>
                    <p style="color:var(--text-muted);">Manage AI counsellor configurations, recommendations, and view interaction logs.</p>
                </div>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
                <div class="alert alert-success">Settings updated successfully!</div>
            <?php endif; ?>

            <div class="sub-links">
                <a href="?tab=config" class="sub-link <?php echo $active_tab == 'config' ? 'active' : ''; ?>"><i class="ph ph-sliders"></i> AI Counsellor Config</a>
                <a href="?tab=recommendations" class="sub-link <?php echo $active_tab == 'recommendations' ? 'active' : ''; ?>"><i class="ph ph-magic-wand"></i> Recommendation Engine</a>
                <a href="?tab=predictor" class="sub-link <?php echo $active_tab == 'predictor' ? 'active' : ''; ?>"><i class="ph ph-chart-line-up"></i> College Predictor Logs</a>
                <a href="?tab=chat" class="sub-link <?php echo $active_tab == 'chat' ? 'active' : ''; ?>"><i class="ph ph-chats"></i> AI Chat Logs</a>
            </div>

            <?php if ($active_tab === 'config' && $config): ?>
            <div class="panel">
                <h3><i class="ph ph-sliders"></i> Core Model Configuration</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_config">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Model Name</label>
                            <input type="text" name="model_name" class="form-control" value="<?php echo htmlspecialchars($config['model_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Response Language</label>
                            <select name="response_language" class="form-control">
                                <option value="en" <?php echo ($config['response_language']??'') == 'en' ? 'selected':''; ?>>English</option>
                                <option value="hi" <?php echo ($config['response_language']??'') == 'hi' ? 'selected':''; ?>>Hindi</option>
                                <option value="en_hi_mix" <?php echo ($config['response_language']??'') == 'en_hi_mix' ? 'selected':''; ?>>Hinglish (Mix)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Temperature (0 to 1)</label>
                            <input type="number" step="0.1" name="temperature" class="form-control" value="<?php echo htmlspecialchars($config['temperature'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Max Tokens</label>
                            <input type="number" name="max_tokens" class="form-control" value="<?php echo htmlspecialchars($config['max_tokens'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>System Prompt</label>
                            <textarea name="system_prompt" class="form-control" rows="5"><?php echo htmlspecialchars($config['system_prompt'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Fallback Response</label>
                            <textarea name="fallback_response" class="form-control" rows="2"><?php echo htmlspecialchars($config['fallback_response'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Escalation Keywords</label>
                            <input type="hidden" name="escalation_keywords" id="escalation_keywords" value="<?php echo htmlspecialchars($config['escalation_keywords'] ?? '[]'); ?>">
                            <div style="display:flex; gap:10px; margin-bottom:10px;">
                                <input type="text" id="new_keyword" class="form-control" placeholder="Add a keyword (e.g., help, agent)">
                                <button type="button" class="btn-primary" onclick="addKeyword()" style="padding: 10px 15px;">Add</button>
                            </div>
                            <div id="keywords_list" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
                        </div>
                        <div class="form-group">
                            <label>Spam Threshold (0 to 1)</label>
                            <input type="number" step="0.1" name="spam_threshold" class="form-control" value="<?php echo htmlspecialchars($config['spam_threshold'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Auto-Approve Threshold (0 to 1)</label>
                            <input type="number" step="0.1" name="auto_approve_threshold" class="form-control" value="<?php echo htmlspecialchars($config['auto_approve_threshold'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Auto-Reject Threshold (0 to 1)</label>
                            <input type="number" step="0.1" name="auto_reject_threshold" class="form-control" value="<?php echo htmlspecialchars($config['auto_reject_threshold'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group" style="display:flex; align-items:center; gap:20px; grid-column: 1 / -1; margin-top:10px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="session_memory" <?php echo ($config['session_memory']??0) ? 'checked' : ''; ?>> Enable Session Memory
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="lead_capture_enabled" <?php echo ($config['lead_capture_enabled']??0) ? 'checked' : ''; ?>> Enable Lead Capture in Chat
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="whatsapp_bot_enabled" <?php echo ($config['whatsapp_bot_enabled']??0) ? 'checked' : ''; ?>> Enable WhatsApp Bot Integration
                            </label>
                        </div>
                    </div>
                    <div style="margin-top:24px; text-align:right; border-top:1px solid var(--border-color); padding-top:20px;">
                        <button type="submit" class="btn-primary"><i class="ph ph-floppy-disk"></i> Save Configuration</button>
                    </div>
                </form>
            </div>
            <?php elseif ($active_tab === 'recommendations' && $recs): ?>
            <div class="panel">
                <h3><i class="ph ph-magic-wand"></i> Recommendation Engine Tuning</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_recs">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Algorithm Type</label>
                            <select name="algo_type" class="form-control">
                                <option value="collaborative" <?php echo ($recs['algo_type']??'') == 'collaborative' ? 'selected':''; ?>>Collaborative Filtering</option>
                                <option value="content" <?php echo ($recs['algo_type']??'') == 'content' ? 'selected':''; ?>>Content-Based</option>
                                <option value="hybrid" <?php echo ($recs['algo_type']??'') == 'hybrid' ? 'selected':''; ?>>Hybrid Model</option>
                                <option value="llm_ranked" <?php echo ($recs['algo_type']??'') == 'llm_ranked' ? 'selected':''; ?>>LLM Ranked (GPT/Gemini)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Recommendation Limit</label>
                            <input type="number" name="recommendation_limit" class="form-control" value="<?php echo htmlspecialchars($recs['recommendation_limit'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Algorithm Prioritization (Weights)</label>
                            <input type="hidden" name="feature_weights" id="feature_weights" value="<?php echo htmlspecialchars($recs['feature_weights'] ?? '{}'); ?>">
                            <div style="display:flex; gap:10px; margin-bottom:10px;">
                                <input type="text" id="fw_name" class="form-control" placeholder="Feature (e.g., nirf_rank, fee)" style="flex:2;">
                                <input type="number" id="fw_weight" class="form-control" placeholder="Weight (0.1 to 1.0)" step="0.1" style="flex:1;">
                                <button type="button" class="btn-primary" onclick="addFeatureWeight()" style="padding: 10px 15px;">Add</button>
                            </div>
                            <div id="fw_list" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>User Profile Data Fields to Analyze</label>
                            <input type="hidden" name="user_profile_fields" id="user_profile_fields" value="<?php echo htmlspecialchars($recs['user_profile_fields'] ?? '[]'); ?>">
                            <div style="display:flex; gap:10px; margin-bottom:10px;">
                                <input type="text" id="new_upf" class="form-control" placeholder="Add field (e.g., location, budget)">
                                <button type="button" class="btn-primary" onclick="addUPF()" style="padding: 10px 15px;">Add</button>
                            </div>
                            <div id="upf_list" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
                        </div>
                        <div class="form-group">
                            <label>Model Version</label>
                            <input type="text" name="model_version" class="form-control" value="<?php echo htmlspecialchars($recs['model_version'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Automatic Retrain Schedule</label>
                            <select name="retrain_schedule" class="form-control">
                                <?php $cron = $recs['retrain_schedule'] ?? ''; ?>
                                <option value="0 * * * *" <?php echo $cron=='0 * * * *'?'selected':''; ?>>Every Hour</option>
                                <option value="0 0 * * *" <?php echo $cron=='0 0 * * *'?'selected':''; ?>>Daily at Midnight</option>
                                <option value="0 0 * * 0" <?php echo $cron=='0 0 * * 0'?'selected':''; ?>>Weekly on Sunday</option>
                                <option value="0 0 1 * *" <?php echo $cron=='0 0 1 * *'?'selected':''; ?>>Monthly on the 1st</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>A/B Test Variant</label>
                            <input type="text" name="ab_test_variant" class="form-control" value="<?php echo htmlspecialchars($recs['ab_test_variant'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; margin-top:20px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="personalization_enabled" <?php echo ($recs['personalization_enabled']??0) ? 'checked' : ''; ?>> Enable Deep Personalization
                            </label>
                        </div>
                    </div>
                    <div style="margin-top:24px; text-align:right; border-top:1px solid var(--border-color); padding-top:20px;">
                        <button type="submit" class="btn-primary"><i class="ph ph-floppy-disk"></i> Save Engine Settings</button>
                    </div>
                </form>
            </div>
            <?php elseif ($active_tab === 'predictor'): ?>
            <div class="panel">
                <h3><i class="ph ph-chart-line-up"></i> College Predictor History (Recent 50)</h3>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Exam</th>
                                <th>Score/Rank</th>
                                <th>Category / State</th>
                                <th>Preferred Course</th>
                                <th>Confidence</th>
                                <th>Results</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($predictor_logs as $log): ?>
                            <tr>
                                <td style="font-size:0.8rem;"><?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?></td>
                                <td style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($log['exam_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo $log['input_score'] ? $log['input_score'].' (Score)' : $log['input_rank'].' (Rank)'; ?></td>
                                <td><?php echo htmlspecialchars($log['input_category'] ?? ''); ?> / <?php echo htmlspecialchars($log['input_state'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($log['course_name'] ?? '-'); ?></td>
                                <td>
                                    <?php 
                                        $conf = $log['confidence_score'] * 100; 
                                        $color = $conf > 80 ? 'green' : ($conf > 50 ? 'orange' : 'red');
                                    ?>
                                    <span style="color:<?php echo $color; ?>; font-weight:700;"><?php echo round($conf); ?>%</span>
                                </td>
                                <td><button class="btn" style="padding:4px 8px; font-size:0.75rem;" onclick="alert('Raw Data: ' + <?php echo htmlspecialchars(json_encode($log['predicted_colleges'] ?? '[]')); ?>)">View Match</button></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($predictor_logs)): ?>
                            <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No predictor submissions yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php elseif ($active_tab === 'chat'): ?>
            <div class="panel">
                <h3><i class="ph ph-chats"></i> AI Moderation & Chat Logs</h3>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Started At</th>
                                <th>Session Token</th>
                                <th>Lead Captured</th>
                                <th>Context Entity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($chat_logs as $log): ?>
                            <tr>
                                <td style="font-size:0.8rem;"><?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?></td>
                                <td style="font-family:monospace;"><?php echo substr(htmlspecialchars($log['session_token']), 0, 15); ?>...</td>
                                <td>
                                    <?php if($log['lead_captured']): ?>
                                        <span class="badge" style="background:rgba(11,36,71,0.04); color:#0B2447;">Yes</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:rgba(15,23,42,0.06); color:#0B2447;">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><pre style="font-size:0.75rem; margin:0; max-width:200px; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($log['entity_context']); ?></pre></td>
                                <td><button class="btn" style="padding:4px 8px; font-size:0.75rem; background:var(--primary); color:#fff; border:none; border-radius:4px;">View Transcript</button></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($chat_logs)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">No active chat sessions found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>
// Escalation Keywords Manager
let keywords = [];
try {
    let rawKeys = document.getElementById('escalation_keywords').value;
    if(rawKeys) keywords = JSON.parse(rawKeys);
    if(!Array.isArray(keywords)) keywords = [];
} catch(e) {
    keywords = [];
}

function renderKeywords() {
    const list = document.getElementById('keywords_list');
    list.innerHTML = '';
    keywords.forEach((kw, idx) => {
        const badge = document.createElement('div');
        badge.style.cssText = 'background:var(--primary); color:#fff; padding:6px 12px; border-radius:20px; font-size:0.85rem; display:flex; align-items:center; gap:8px;';
        badge.innerHTML = `<span>${kw}</span> <button type="button" onclick="removeKeyword(${idx})" style="background:none; border:none; color:#fff; cursor:pointer; font-weight:bold;">&times;</button>`;
        list.appendChild(badge);
    });
    document.getElementById('escalation_keywords').value = JSON.stringify(keywords);
}

function addKeyword() {
    const input = document.getElementById('new_keyword');
    const val = input.value.trim();
    if(val && !keywords.includes(val)) {
        keywords.push(val);
        input.value = '';
        renderKeywords();
    }
}

function removeKeyword(idx) {
    keywords.splice(idx, 1);
    renderKeywords();
}

// Initial Render
if(document.getElementById('escalation_keywords')) {
    renderKeywords();
    document.getElementById('new_keyword').addEventListener('keypress', function(e) {
        if(e.key === 'Enter') {
            e.preventDefault();
            addKeyword();
        }
    });
}

// User Profile Fields Manager
let upf = [];
try {
    let rawUPF = document.getElementById('user_profile_fields')?.value;
    if(rawUPF) upf = JSON.parse(rawUPF);
    if(!Array.isArray(upf)) upf = [];
} catch(e) { upf = []; }

function renderUPF() {
    const list = document.getElementById('upf_list');
    if(!list) return;
    list.innerHTML = '';
    upf.forEach((f, idx) => {
        const badge = document.createElement('div');
        badge.style.cssText = 'background:#19376D; color:#fff; padding:6px 12px; border-radius:20px; font-size:0.85rem; display:flex; align-items:center; gap:8px;';
        badge.innerHTML = `<span>${f}</span> <button type="button" onclick="removeUPF(${idx})" style="background:none; border:none; color:#fff; cursor:pointer; font-weight:bold;">&times;</button>`;
        list.appendChild(badge);
    });
    document.getElementById('user_profile_fields').value = JSON.stringify(upf);
}

function addUPF() {
    const input = document.getElementById('new_upf');
    const val = input.value.trim();
    if(val && !upf.includes(val)) {
        upf.push(val);
        input.value = '';
        renderUPF();
    }
}

function removeUPF(idx) {
    upf.splice(idx, 1);
    renderUPF();
}

if(document.getElementById('user_profile_fields')) {
    renderUPF();
    document.getElementById('new_upf').addEventListener('keypress', function(e) {
        if(e.key === 'Enter') { e.preventDefault(); addUPF(); }
    });
}

// Feature Weights Manager
let featureWeights = {};
try {
    let rawFW = document.getElementById('feature_weights')?.value;
    if(rawFW) featureWeights = JSON.parse(rawFW);
    if(typeof featureWeights !== 'object' || featureWeights === null) featureWeights = {};
} catch(e) { featureWeights = {}; }

function renderFW() {
    const list = document.getElementById('fw_list');
    if(!list) return;
    list.innerHTML = '';
    for(const [key, val] of Object.entries(featureWeights)) {
        const badge = document.createElement('div');
        badge.style.cssText = 'background:#19376D; color:#fff; padding:6px 12px; border-radius:20px; font-size:0.85rem; display:flex; align-items:center; gap:8px;';
        badge.innerHTML = `<span>${key}: <strong>${val}</strong></span> <button type="button" onclick="removeFW('${key}')" style="background:none; border:none; color:#fff; cursor:pointer; font-weight:bold;">&times;</button>`;
        list.appendChild(badge);
    }
    document.getElementById('feature_weights').value = JSON.stringify(featureWeights);
}

function addFeatureWeight() {
    const nameInput = document.getElementById('fw_name');
    const weightInput = document.getElementById('fw_weight');
    const name = nameInput.value.trim();
    const weight = parseFloat(weightInput.value);
    
    if(name && !isNaN(weight)) {
        featureWeights[name] = weight;
        nameInput.value = '';
        weightInput.value = '';
        renderFW();
    }
}

function removeFW(key) {
    delete featureWeights[key];
    renderFW();
}

if(document.getElementById('feature_weights')) {
    renderFW();
}
</script>
</body>
</html>
