<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Load .env file
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!empty($key) && !isset($_ENV[$key])) {
            $_ENV[$key] = $value;
        }
    }
}

// Email helper function using Brevo API
function sendModerationEmail($toEmail, $toName, $subject, $htmlContent) {
    $apiKey = $_ENV['BREVO_API_KEY'] ?? '';
    $senderEmail = $_ENV['BREVO_SENDER_EMAIL'] ?? '';
    $senderName = $_ENV['BREVO_SENDER_NAME'] ?? 'AdmissionSeason';

    if (empty($apiKey) || empty($senderEmail)) {
        return false;
    }

    $payload = [
        'sender' => ['email' => $senderEmail, 'name' => $senderName],
        'to' => [['email' => $toEmail, 'name' => $toName]],
        'subject' => $subject,
        'htmlContent' => $htmlContent,
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($response !== false && $httpCode >= 200 && $httpCode < 300);
}

// Handle Moderation Action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'moderate') {
    $report_id = $_POST['report_id'];
    $mod_action = $_POST['moderation_action'];
    
    // Update the report
    $pdo->prepare("UPDATE qa_reports SET moderation_action = ? WHERE id = ?")->execute([$mod_action, $report_id]);
    
    // Fetch the report to see what it's attached to
    $report = $pdo->prepare("SELECT * FROM qa_reports WHERE id = ?");
    $report->execute([$report_id]);
    $r = $report->fetch();
    
    // Get content author info for email
    $authorEmail = null;
    $authorName = null;
    $contentSnippet = '';
    $contentType = '';
    
    // Check answer_id FIRST (since answer reports also have question_id)
    if ($r['answer_id']) {
        $contentType = 'Answer';
        $aStmt = $pdo->prepare("SELECT a.answer_text, u.full_name, u.email FROM answers a LEFT JOIN users u ON a.answered_by = u.id WHERE a.id = ?");
        $aStmt->execute([$r['answer_id']]);
        $aRow = $aStmt->fetch(PDO::FETCH_ASSOC);
        if ($aRow) {
            $authorEmail = $aRow['email'];
            $authorName = $aRow['full_name'];
            $contentSnippet = mb_strimwidth(strip_tags($aRow['answer_text'] ?? ''), 0, 80, '...');
        }
    } elseif ($r['question_id']) {
        $contentType = 'Question';
        $qStmt = $pdo->prepare("SELECT q.question_text, u.full_name, u.email FROM questions q LEFT JOIN users u ON q.asked_by = u.id WHERE q.id = ?");
        $qStmt->execute([$r['question_id']]);
        $qRow = $qStmt->fetch(PDO::FETCH_ASSOC);
        if ($qRow) {
            $authorEmail = $qRow['email'];
            $authorName = $qRow['full_name'];
            $contentSnippet = mb_strimwidth(strip_tags($qRow['question_text'] ?? ''), 0, 80, '...');
        }
    }
    
    // Get reporter info for email
    $reporterEmail = null;
    $reporterName = null;
    if (!empty($r['reported_by']) && $r['reported_by'] !== 'user-1234-uuid') {
        $rStmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
        $rStmt->execute([$r['reported_by']]);
        $rRow = $rStmt->fetch(PDO::FETCH_ASSOC);
        if ($rRow) {
            $reporterEmail = $rRow['email'];
            $reporterName = $rRow['full_name'];
        }
    }
    
    $siteUrl = 'https://localhost/ADMISSION';
    
    // If the action is 'remove', delete the content
    if ($mod_action == 'remove') {
        // Check answer_id FIRST (since answer reports also have question_id)
        if ($r['answer_id']) {
            $ansRow = $pdo->prepare("SELECT question_id FROM answers WHERE id = ?");
            $ansRow->execute([$r['answer_id']]);
            $ansInfo = $ansRow->fetch();
            $pdo->prepare("DELETE FROM answers WHERE id = ?")->execute([$r['answer_id']]);
            if ($ansInfo) {
                $pdo->prepare("UPDATE questions SET answer_count = GREATEST(0, answer_count - 1) WHERE id = ?")->execute([$ansInfo['question_id']]);
            }
        } elseif ($r['question_id']) {
            $pdo->prepare("UPDATE questions SET status = 'removed' WHERE id = ?")->execute([$r['question_id']]);
            $pdo->prepare("DELETE FROM answers WHERE question_id = ?")->execute([$r['question_id']]);
        }
        
        // Notify reporter that their report was accepted
        if ($reporterEmail) {
            $reporterNameSafe = htmlspecialchars($reporterName ?? 'User');
            sendModerationEmail($reporterEmail, $reporterName ?? 'User',
                'Your Report Has Been Reviewed - AdmissionSeason',
                "<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;'>
                    <h2 style='color:#0B2447;'>Report Review Update</h2>
                    <p>Hi {$reporterNameSafe},</p>
                    <p>Thank you for helping us maintain a safe community. Your report regarding a <strong>{$contentType}</strong> has been reviewed and the content has been <strong>removed</strong>.</p>
                    <p style='color:#64748b;font-size:0.9rem;'>Content: \"{$contentSnippet}\"</p>
                    <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0;'>
                    <p style='color:#64748b;font-size:0.8rem;'>AdmissionSeason Moderation Team</p>
                </div>"
            );
        }
    }
    
    // Warn Author & Keep Content - send warning email to author
    if ($mod_action == 'warn_user' && $authorEmail) {
        $authorNameSafe = htmlspecialchars($authorName ?? 'User');
        sendModerationEmail($authorEmail, $authorName ?? 'User',
            'Content Warning Notice - AdmissionSeason',
            "<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;'>
                <h2 style='color:#0B2447;'>Content Warning</h2>
                <p>Hi {$authorNameSafe},</p>
                <p>Your {$contentType} on AdmissionSeason has been flagged by a community member for review.</p>
                <p style='color:#64748b;font-size:0.9rem;'>Content: \"{$contentSnippet}\"</p>
                <p>After review, we've decided to keep your content but wanted to give you a friendly reminder to follow our <a href='{$siteUrl}' style='color:#19376D;'>community guidelines</a>.</p>
                <p>Repeated reports may result in content removal or account restrictions.</p>
                <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0;'>
                <p style='color:#64748b;font-size:0.8rem;'>AdmissionSeason Moderation Team</p>
            </div>"
        );
    }
    
    // Reject Report - notify reporter that report was rejected
    if ($mod_action == 'reject' && $reporterEmail) {
        $reporterNameSafe = htmlspecialchars($reporterName ?? 'User');
        sendModerationEmail($reporterEmail, $reporterName ?? 'User',
            'Your Report Has Been Reviewed - AdmissionSeason',
            "<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;'>
                <h2 style='color:#0B2447;'>Report Review Update</h2>
                <p>Hi {$reporterNameSafe},</p>
                <p>Thank you for reporting content on AdmissionSeason. After reviewing your report regarding a <strong>{$contentType}</strong>, we've determined that the content does not violate our community guidelines.</p>
                <p style='color:#64748b;font-size:0.9rem;'>Content: \"{$contentSnippet}\"</p>
                <p>The report has been dismissed and the content will remain visible.</p>
                <p>If you believe this content violates our guidelines, please feel free to report it again with additional details.</p>
                <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0;'>
                <p style='color:#64748b;font-size:0.8rem;'>AdmissionSeason Moderation Team</p>
            </div>"
        );
    }
    
    header("Location: qa_moderation.php?msg=moderated"); exit;
}

// Fetch Pending Reports (skip removed content)
$pending = $pdo->query("SELECT r.*, q.question_text, a.answer_text 
    FROM qa_reports r 
    LEFT JOIN questions q ON r.question_id = q.id AND q.status != 'removed'
    LEFT JOIN answers a ON r.answer_id = a.id 
    WHERE r.moderation_action IS NULL 
    ORDER BY r.created_at ASC")->fetchAll();

// Fetch Resolved Reports (Limit 50)
$resolved = $pdo->query("SELECT r.*, q.question_text, a.answer_text 
    FROM qa_reports r 
    LEFT JOIN questions q ON r.question_id = q.id 
    LEFT JOIN answers a ON r.answer_id = a.id 
    WHERE r.moderation_action IS NOT NULL 
    ORDER BY r.updated_at DESC LIMIT 50")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderation Queue | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-responsive.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}
        .sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto;z-index:100;transition:transform 0.3s ease}
        .sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}
        .sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s;text-decoration:none}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}
        .main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}
        .topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}
        .content-area{padding:32px;max-width:1400px;margin:0 auto;width:100%;box-sizing:border-box}
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:12px;flex-wrap:wrap}.page-header h2{font-size:2rem;font-weight:800}
        .sub-links{display:flex;gap:8px;margin-bottom:20px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:thin;flex-wrap:nowrap;border-bottom:1px solid var(--border-color);padding-bottom:12px}
        .sub-links::-webkit-scrollbar{height:5px}.sub-links::-webkit-scrollbar-track{background:#e2e8f0;border-radius:3px}.sub-links::-webkit-scrollbar-thumb{background:var(--primary);border-radius:3px}
        .sub-link{font-size:.85rem;font-weight:600;color:var(--text-muted);text-decoration:none;padding:5px 10px;border-radius:6px;transition:all .2s;white-space:nowrap;flex-shrink:0;display:flex;align-items:center;gap:4px}.sub-link:hover,.sub-link.active{color:var(--primary);background:rgba(0,0,0,.03)}
        .panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px;overflow-x:auto}
        .panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}
        table{width:100%;border-collapse:collapse;font-size:.88rem;min-width:650px}
        th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}
        tr:hover{background:rgba(0,0,0,.015)}.badge{padding:3px 8px;border-radius:5px;font-size:.7rem;font-weight:700;white-space:nowrap}
        .msg-alert{padding:14px 20px;border-radius:8px;background:rgba(11,36,71,0.04);color:#0B2447;margin-bottom:20px;border:1px solid rgba(11,36,71,0.04)}
        .form-group{margin-bottom:16px}.form-group label{display:block;font-weight:600;margin-bottom:6px;font-size:.9rem}
        .form-control{width:100%;min-width:0;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-size:.9rem;font-family:inherit;box-sizing:border-box}
        .form-control:focus{outline:none;border-color:var(--primary)}
        .btn{padding:10px 16px;border-radius:8px;font-weight:600;font-size:.9rem;cursor:pointer;border:none;display:inline-flex;align-items:center;justify-content:center;gap:6px;box-sizing:border-box;text-decoration:none;white-space:nowrap}
        .btn-primary{background:var(--primary);color:#fff}.btn-primary:hover{background:#19376D}
        .content-box{background:#f8fafc;padding:12px;border-radius:8px;font-size:.85rem;border:1px solid var(--border-color);margin-top:8px;word-break:break-word}
        .mod-form{display:flex;gap:8px;flex-wrap:wrap}
        .mobile-menu-btn{display:none;background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-dark);padding:4px}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:90}

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}
            .sidebar-overlay.show{display:block}.main-content{margin-left:0}
            .topbar{height:56px;padding:0 12px;justify-content:space-between}
            .mobile-menu-btn{display:block}.content-area{padding:12px}
            .page-header{flex-direction:column;align-items:flex-start}.page-header h2{font-size:1.4rem}
            .panel{padding:14px}.panel h3{font-size:1rem}
            .sub-links{gap:4px;margin-bottom:14px}.sub-link{padding:5px 8px;font-size:.78rem}
            .mod-form{flex-direction:column}.mod-form .form-control{width:100%}
            .mod-form .btn{width:100%;text-align:center}
        }
        @media(max-width:480px){.content-area{padding:8px}.panel{padding:10px}.page-header h2{font-size:1.2rem}}
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
                    <h2><i class="ph ph-shield-warning" style="color:var(--primary);"></i> Moderation Queue</h2>
                    <p style="color:var(--text-muted);">Review and act on reported questions and answers.</p>
                </div>
            </div>

            <div class="sub-links">
                <a href="community_dashboard.php" class="sub-link"><i class="ph ph-squares-four"></i> Overview</a>
                <a href="questions_manager.php" class="sub-link"><i class="ph ph-question"></i> Questions</a>
                <a href="answers_manager.php" class="sub-link"><i class="ph ph-chat-text"></i> Answers</a>
                <a href="experts.php" class="sub-link"><i class="ph ph-user-check"></i> Experts</a>
                <a href="qa_moderation.php" class="sub-link active"><i class="ph ph-shield-warning"></i> Moderation</a>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> Moderation action applied successfully.</div>
            <?php endif; ?>

            <div class="panel">
                <h3><i class="ph ph-clock"></i> Pending Review (<?php echo count($pending); ?>)</h3>
                <div style="overflow-x:auto;">
                    <table>
                        <thead><tr><th>Report Reason</th><th>Reported Content</th><th>Reporter ID</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach($pending as $p): ?>
                            <tr>
                                <td>
                                    <span class="badge" style="background:rgba(15,23,42,0.06);color:#0F172A; font-size:0.8rem;"><i class="ph ph-warning-circle"></i> <?php echo ucfirst($p['report_reason']); ?></span>
                                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;"><?php echo date('d M, H:i', strtotime($p['created_at'])); ?></div>
                                </td>
                                <td style="width:50%;">
                                    <?php if($p['question_id']): ?>
                                    <span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65);">Question</span>
                                    <div class="content-box"><?php echo htmlspecialchars($p['question_text'] ?? '[Content Deleted]'); ?></div>
                                    <?php elseif($p['answer_id']): ?>
                                    <span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65);">Answer</span>
                                    <div class="content-box"><?php echo htmlspecialchars(strip_tags($p['answer_text'] ?? '[Content Deleted]')); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-family:monospace; font-size:0.8rem; color:rgba(15,23,42,0.45);"><?php echo substr($p['reported_by'], 0, 12).'...'; ?></td>
                                <td>
                                    <form method="POST" action="qa_moderation.php" class="mod-form">
                                        <input type="hidden" name="action" value="moderate">
                                        <input type="hidden" name="report_id" value="<?php echo $p['id']; ?>">
                                        <select name="moderation_action" class="form-control" required>
                                            <option value="">-- Select Action --</option>
                                            <option value="reject">Reject Report (Keep Content)</option>
                                            <option value="remove">Remove Content (Delete)</option>
                                            <option value="warn_user">Warn Author & Keep Content</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary" style="padding:8px 12px; font-size:0.85rem;">Apply</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($pending)): ?>
                            <tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:40px 0;"><i class="ph ph-check-circle" style="font-size:2rem; color:#0B2447; margin-bottom:10px; display:block;"></i>All caught up! No pending reports.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel" style="margin-top:24px;">
                <h3 style="color:rgba(15,23,42,0.45);"><i class="ph ph-archive"></i> Recently Resolved</h3>
                <div style="overflow-x:auto;">
                    <table>
                        <thead><tr><th>Report Reason</th><th>Content Snapshot</th><th>Decision</th><th>Resolved At</th></tr></thead>
                        <tbody>
                            <?php foreach($resolved as $r): ?>
                            <tr>
                                <td><span class="badge" style="background:#F8FAFC;color:rgba(15,23,42,0.65);"><?php echo ucfirst($r['report_reason']); ?></span></td>
                                <td style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:0.85rem; color:var(--text-muted);">
                                    <?php 
                                        if($r['question_id']) echo htmlspecialchars($r['question_text'] ?? '[Deleted]');
                                        elseif($r['answer_id']) echo htmlspecialchars(strip_tags($r['answer_text'] ?? '[Deleted]'));
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        $color = 'rgba(15,23,42,0.65)'; $bg = '#F8FAFC';
                                        if($r['moderation_action'] == 'remove') { $color = '#0F172A'; $bg = 'rgba(15,23,42,0.06)'; }
                                        if($r['moderation_action'] == 'reject') { $color = '#0B2447'; $bg = 'rgba(11,36,71,0.04)'; }
                                        if($r['moderation_action'] == 'warn_user') { $color = '#19376D'; $bg = 'rgba(11,36,71,0.06)'; }
                                    ?>
                                    <span class="badge" style="background:<?php echo $bg; ?>;color:<?php echo $color; ?>;"><?php echo ucfirst(str_replace('_', ' ', $r['moderation_action'])); ?></span>
                                </td>
                                <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo date('M d, H:i', strtotime($r['updated_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>
<script>
document.getElementById('mobile-menu-btn').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.add('open');
    document.getElementById('sidebar-overlay').classList.add('show');
});
document.getElementById('sidebar-overlay').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.remove('open');
    this.classList.remove('show');
});
</script>
</body>
</html>
