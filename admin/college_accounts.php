<?php
session_start();
require_once __DIR__ . '/db.php';
if (empty($_SESSION['admin_id'])) { header('Location: /ADMISSION/admin/index.php'); exit; }

$msg = '';

$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
  $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $k = trim($k);
    $v = trim($v);
    if ($k !== '' && !isset($_ENV[$k])) $_ENV[$k] = $v;
  }
}

function sendCollegeAccountEmail($toEmail, $toName, $subject, $htmlContent) {
  $apiKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? '');
  $senderEmail = getenv('BREVO_SENDER_EMAIL') ?: ($_ENV['BREVO_SENDER_EMAIL'] ?? '');
  $senderName = getenv('BREVO_SENDER_NAME') ?: ($_ENV['BREVO_SENDER_NAME'] ?? 'AdmissionSeason');

  if (empty($apiKey) || empty($senderEmail) || empty($toEmail)) {
    return false;
  }

  $payload = [
    'sender' => ['email' => $senderEmail, 'name' => $senderName],
    'to' => [['email' => $toEmail, 'name' => $toName ?: 'Institute']],
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $id = $_POST['account_id'] ?? '';

    if ($act === 'approve' && $id) {
        $stmt = $pdo->prepare("SELECT * FROM college_accounts WHERE id=?");
        $stmt->execute([$id]);
        $acc = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($acc) {
        $loginPassword = trim((string)($acc['temp_password'] ?? ''));
        $hashToSave = $acc['password_hash'];

        // Backward compatibility for records created before temp_password was stored.
        if ($loginPassword === '') {
          $loginPassword = 'AS' . mt_rand(100000, 999999);
          $hashToSave = password_hash($loginPassword, PASSWORD_DEFAULT);
        }

            if (!$acc['college_id']) {
                $collegeId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
                $slug = strtolower(preg_replace('/[^a-z0-9]+/','-',$acc['institute_name']));
                $ins = $pdo->prepare("INSERT INTO colleges (id,name,slug,status,publish_status,created_at) VALUES (?,?,?,'active','published',NOW())");
                $ins->execute([$collegeId, $acc['institute_name'], $slug]);
                $upd = $pdo->prepare("UPDATE college_accounts SET college_id=?,password_hash=?,temp_password=?,status='approved',approved_by=?,approved_at=NOW() WHERE id=?");
          $upd->execute([$collegeId, $hashToSave, $loginPassword, $_SESSION['admin_id'], $id]);
            } else {
                $upd = $pdo->prepare("UPDATE college_accounts SET password_hash=?,temp_password=?,status='approved',approved_by=?,approved_at=NOW() WHERE id=?");
          $upd->execute([$hashToSave, $loginPassword, $_SESSION['admin_id'], $id]);
            }

        $toName = $acc['contact_person'] ?: $acc['institute_name'];
        $loginUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/ADMISSION/college/login.php';
        $safeInstitute = htmlspecialchars($acc['institute_name'] ?? 'Your Institute');
        $safeEmail = htmlspecialchars($acc['email'] ?? '');
        $safePass = htmlspecialchars($loginPassword);
        
        $approvalHtml = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:40px 16px">
<tr><td align="center">
<table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04),0 8px 32px rgba(0,0,0,0.06)">
  <tr>
    <td style="background:#0B2447;padding:40px 48px 36px;text-align:center">
      <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.45);margin-bottom:12px">AdmissionSeason</div>
      <h1 style="margin:0;color:#fff;font-size:24px;font-weight:800;line-height:1.3">Account Approved</h1>
      <div style="width:40px;height:2px;background:rgba(255,255,255,0.25);margin:16px auto;border-radius:1px"></div>
      <p style="margin:0;color:rgba(255,255,255,0.65);font-size:14px;font-weight:500">Welcome to AdmissionSeason Portal</p>
    </td>
  </tr>
  <tr>
    <td style="padding:32px 48px 24px">
      <p style="margin:0;font-size:15px;color:#0f172a;line-height:1.6">Dear <strong>{$toName}</strong>,</p>
      <p style="margin:10px 0 0;font-size:14px;color:#64748b;line-height:1.6">We are pleased to inform you that your institute account for <strong style="color:#0f172a">{$safeInstitute}</strong> has been approved by our verification team.</p>
      <p style="margin:16px 0 0;font-size:14px;color:#64748b;line-height:1.6">You can now log in to your dashboard using the credentials you set during registration:</p>
      
      <div style="background:#f1f5f9;border-radius:10px;padding:16px;margin:20px 0;border:1px solid #e2e8f0">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding:4px 0;font-size:13px;color:#64748b;width:80px"><strong>Email:</strong></td>
            <td style="padding:4px 0;font-size:13px;color:#0f172a"><code>{$safeEmail}</code></td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:13px;color:#64748b"><strong>Password:</strong></td>
            <td style="padding:4px 0;font-size:13px;color:#0f172a"><code>{$safePass}</code></td>
          </tr>
        </table>
      </div>
    </td>
  </tr>
  <tr>
    <td style="padding:0 48px 32px;text-align:center">
      <a href="{$loginUrl}" style="display:inline-block;background:#0B2447;color:#fff;padding:14px 40px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;letter-spacing:0.3px">Login to Dashboard</a>
    </td>
  </tr>
  <tr>
    <td style="background:#f8fafc;padding:24px 48px;text-align:center;border-top:1px solid #f1f5f9">
      <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.6">
        Sent by <strong style="color:#64748b">AdmissionSeason</strong>
      </p>
    </td>
  </tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;

        $emailSent = sendCollegeAccountEmail($acc['email'] ?? '', $toName, 'Your college account is approved - AdmissionSeason', $approvalHtml);
        $msg = $emailSent ? 'Account approved and approval email sent.' : 'Account approved, but email could not be sent (check Brevo config).';
        }
    }

    if ($act === 'reject' && $id) {
        $reason = trim($_POST['rejection_reason'] ?? '');
      if ($reason === '') {
        $msg = 'Rejection reason is required.';
      } else {
        $stmt = $pdo->prepare("SELECT * FROM college_accounts WHERE id=?");
        $stmt->execute([$id]);
        $acc = $stmt->fetch(PDO::FETCH_ASSOC);

        $upd = $pdo->prepare("UPDATE college_accounts SET status='rejected',rejection_reason=? WHERE id=?");
        $upd->execute([$reason, $id]);

        if ($acc) {
          $toName = $acc['contact_person'] ?: $acc['institute_name'];
          $safeInstitute = htmlspecialchars($acc['institute_name'] ?? 'Your Institute');
          $safeReason = nl2br(htmlspecialchars($reason));
          
          $rejectHtml = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:40px 16px">
<tr><td align="center">
<table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04),0 8px 32px rgba(0,0,0,0.06)">
  <tr>
    <td style="background:#991b1b;padding:40px 48px 36px;text-align:center">
      <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.45);margin-bottom:12px">AdmissionSeason</div>
      <h1 style="margin:0;color:#fff;font-size:24px;font-weight:800;line-height:1.3">Account Verification Status</h1>
      <div style="width:40px;height:2px;background:rgba(255,255,255,0.25);margin:16px auto;border-radius:1px"></div>
      <p style="margin:0;color:rgba(255,255,255,0.65);font-size:14px;font-weight:500">Update on your institute registration</p>
    </td>
  </tr>
  <tr>
    <td style="padding:32px 48px 32px">
      <p style="margin:0;font-size:15px;color:#0f172a;line-height:1.6">Dear <strong>{$toName}</strong>,</p>
      <p style="margin:10px 0 0;font-size:14px;color:#64748b;line-height:1.6">Thank you for registering <strong style="color:#0f172a">{$safeInstitute}</strong> on AdmissionSeason. We have reviewed your documents and details.</p>
      <p style="margin:16px 0 0;font-size:14px;color:#64748b;line-height:1.6">Unfortunately, we are unable to approve your account at this time due to the following reason:</p>
      
      <div style="background:#fef2f2;border-radius:10px;padding:16px;margin:20px 0;border:1px solid #fecaca;color:#991b1b;font-size:14px;line-height:1.5;font-weight:500">
        {$safeReason}
      </div>
      
      <p style="margin:0;font-size:14px;color:#64748b;line-height:1.6">Please review the reason and submit a new request with correct details and valid documents, or contact our support team if you believe this was an error.</p>
    </td>
  </tr>
  <tr>
    <td style="background:#f8fafc;padding:24px 48px;text-align:center;border-top:1px solid #f1f5f9">
      <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.6">
        Sent by <strong style="color:#64748b">AdmissionSeason</strong>
      </p>
    </td>
  </tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;

          $emailSent = sendCollegeAccountEmail($acc['email'] ?? '', $toName, 'Your college account review update - AdmissionSeason', $rejectHtml);
          $msg = $emailSent ? 'Account rejected and rejection email sent.' : 'Account rejected, but email could not be sent (check Brevo config).';
        } else {
          $msg = 'Account rejected.';
        }
      }
    }
}

$filterStatus = $_GET['status'] ?? '';
$sql = "SELECT * FROM college_accounts";
$params = [];
if ($filterStatus) { $sql .= " WHERE status=?"; $params[] = $filterStatus; }
$sql .= " ORDER BY FIELD(status,'pending','approved','rejected','active'), created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$detail = null;
if (isset($_GET['view'])) {
    $d = $pdo->prepare("SELECT * FROM college_accounts WHERE id=?");
    $d->execute([$_GET['view']]);
    $detail = $d->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>College Accounts – Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<style>
body { background-color: var(--bg-light); }
.admin-layout { display: flex; min-height: 100vh; }
.sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; }
.sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
.sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
.sidebar-nav { padding: 24px 0; flex: 1; }
.sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none; }
.sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
.sidebar-nav a i { font-size: 1.25rem; }

.main-content { flex: 1; margin-left: 280px; max-width: calc(100% - 280px); display: flex; flex-direction: column; }
.topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
.user-profile { display: flex; align-items: center; gap: 12px; font-weight: 500; }
.avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; }

.content-area { padding: 32px; }

.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.page-header h2{font-size:2rem;font-weight:800;color:#0B2447}
.msg{padding:12px 16px;border-radius:10px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;font-size:.8rem;margin-bottom:16px}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;margin-bottom:16px;box-shadow: var(--shadow-sm);}

.filters{display:flex;gap:8px;margin-bottom:16px}
.filters a{padding:6px 14px;border-radius:8px;font-size:.75rem;font-weight:600;text-decoration:none;color:#64748b;background:#fff;border:1px solid #e2e8f0}
.filters a.active{background:#0B2447;color:#fff;border-color:#0B2447}

table{width:100%;border-collapse:collapse;font-size:.78rem}
th{text-align:left;padding:12px 10px;background:#f8fafc;color:#64748b;font-weight:600;border-bottom:1px solid #e2e8f0;font-size:.7rem;text-transform:uppercase}
td{padding:12px 10px;border-bottom:1px solid #f1f5f9;color:#334155}
.badge{display:inline-flex;padding:3px 8px;border-radius:5px;font-size:.65rem;font-weight:600}
.badge-green{background:#dcfce7;color:#166534}
.badge-yellow{background:#fef3c7;color:#92400e}
.badge-red{background:#fef2f2;color:#991b1b}
.badge-blue{background:#eff6ff;color:#1d4ed8}
.btn{padding:8px 16px;border:none;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;font-family:inherit;transition:all 0.2s}
.btn-green{background:#16a34a;color:#fff}.btn-green:hover{background:#15803d}
.btn-red{background:#dc2626;color:#fff}.btn-red:hover{background:#b91c1c}
.btn-sm{padding:6px 10px;font-size:.7rem}
.btn-ghost{background:#f1f5f9;color:#334155;border:1px solid #e2e8f0}

.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center}
.modal-bg.show{display:flex}
.modal{background:#fff;border-radius:14px;padding:24px;width:100%;max-width:450px}
.modal h3{font-size:1rem;font-weight:700;margin-bottom:12px;color:#0B2447}
.modal textarea{width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.8rem;font-family:inherit;margin-bottom:12px}
.modal .btns{display:flex;gap:8px;justify-content:flex-end}

.detail-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;margin-bottom:20px;box-shadow: var(--shadow-sm);}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.detail-item label{font-size:.68rem;color:#94a3b8;text-transform:uppercase;font-weight:600;display:block;margin-bottom:2px}
.detail-item span{font-size:.82rem;color:#0B2447;font-weight:600}
.doc-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:16px}
.doc-card{border:1px solid #e2e8f0;border-radius:10px;padding:12px;text-align:center;transition:all 0.2s}
.doc-card:hover{border-color:var(--primary);background:rgba(0,0,0,0.01);}
.doc-card a{display:block;text-decoration:none}
.doc-card i{font-size:2rem;color:#19376D;margin-bottom:6px}
.doc-card p{font-size:.72rem;color:#334155;font-weight:600}
.doc-card small{font-size:.65rem;color:#94a3b8}
.kyc-badge{display:inline-flex;padding:2px 6px;border-radius:4px;font-size:.62rem;font-weight:600;margin-left:4px}
.kyc-verified{background:#dcfce7;color:#166534}
.kyc-pending{background:#fef3c7;color:#92400e}

@media(max-width:768px){.detail-grid,.doc-grid{grid-template-columns:1fr 1fr}table{font-size:.7rem}}
</style>
</head>
<body>

    <div class="admin-layout">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="topbar">
                <div class="user-profile">
                    <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                    <div class="avatar"><?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?></div>
                    <a href="logout.php" style="margin-left: 16px; color: #19376d;" title="Logout">
                        <i class="ph ph-sign-out" style="font-size: 1.5rem;"></i>
                    </a>
                </div>
            </header>

            <div class="content-area">
                <div class="page-header">
                  <h2><i class="ph ph-graduation-cap"></i> College Accounts</h2>
                </div>

<?php if($msg): ?><div class="msg"><?=$msg?></div><?php endif;?>

<?php if($detail): ?>
<div class="detail-panel">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <h2 style="font-size:1.1rem;font-weight:700;color:#0B2447"><?=htmlspecialchars($detail['institute_name'])?></h2>
    <a href="/ADMISSION/admin/college_accounts.php" style="font-size:.78rem;color:#64748b;text-decoration:none"><i class="ph ph-x"></i> Close</a>
  </div>

  <div class="detail-grid">
    <div class="detail-item"><label>Type</label><span><?=ucfirst($detail['institute_type'])?></span></div>
    <div class="detail-item"><label>Status</label><span class="badge <?=($detail['status']==='approved'?'badge-green':($detail['status']==='rejected'?'badge-red':'badge-yellow'))?>"><?=ucfirst($detail['status'])?></span></div>
    <div class="detail-item"><label>Contact Person</label><span><?=htmlspecialchars($detail['contact_person'])?></span></div>
    <div class="detail-item"><label>Designation</label><span><?=htmlspecialchars($detail['designation'] ?? '—')?></span></div>
    <div class="detail-item"><label>Email</label><span><?=htmlspecialchars($detail['email'])?></span></div>
    <div class="detail-item"><label>Phone</label><span><?=htmlspecialchars($detail['phone'] ?? '—')?></span></div>
    <div class="detail-item"><label>Website</label><span><?=htmlspecialchars($detail['website'] ?? '—')?></span></div>
    <div class="detail-item"><label>State</label><span><?=htmlspecialchars($detail['city'] ?? '—')?></span></div>
    <div class="detail-item"><label>Established</label><span><?=htmlspecialchars($detail['established_year'] ?? '—')?></span></div>
    <div class="detail-item"><label>Affiliation</label><span><?=htmlspecialchars($detail['affiliation_details'] ?? '—')?></span></div>
  </div>

  <div style="margin-top:16px;padding-top:12px;border-top:1px solid #e2e8f0">
    <h3 style="font-size:.88rem;font-weight:700;color:#0B2447;margin-bottom:12px"><i class="ph ph-shield-check"></i> KYC Details</h3>
    <div class="detail-grid">
      <div class="detail-item"><label>PAN Number</label><span><?=htmlspecialchars($detail['pan_number'])?> <span class="kyc-badge kyc-verified">Verified</span></span></div>
      <div class="detail-item"><label>Aadhar Number</label><span><?=substr($detail['aadhar_number'],0,4).' **** '.substr($detail['aadhar_number'],-4)?></span></div>
      <div class="detail-item"><label>GSTIN</label><span><?=htmlspecialchars($detail['gst_number'] ?: 'Not provided')?></span></div>
    </div>
  </div>

  <div style="margin-top:16px;padding-top:12px;border-top:1px solid #e2e8f0">
    <h3 style="font-size:.88rem;font-weight:700;color:#0B2447;margin-bottom:12px"><i class="ph ph-file-text"></i> Uploaded Documents</h3>
    <div class="doc-grid">
      <div class="doc-card">
        <?php if($detail['pan_doc']): ?>
        <a href="/ADMISSION/uploads/college_docs/<?=htmlspecialchars($detail['pan_doc'])?>" target="_blank"><i class="ph ph-identification-card"></i><p>PAN Card</p><small>Click to view</small></a>
        <?php else: ?>
        <i class="ph ph-x-circle" style="color:#dc2626"></i><p style="color:#dc2626">Not uploaded</p>
        <?php endif; ?>
      </div>
      <div class="doc-card">
        <?php if($detail['aadhar_doc']): ?>
        <a href="/ADMISSION/uploads/college_docs/<?=htmlspecialchars($detail['aadhar_doc'])?>" target="_blank"><i class="ph ph-card"></i><p>Aadhar Card</p><small>Click to view</small></a>
        <?php else: ?>
        <i class="ph ph-x-circle" style="color:#dc2626"></i><p style="color:#dc2626">Not uploaded</p>
        <?php endif; ?>
      </div>
      <div class="doc-card">
        <?php if($detail['gst_doc']): ?>
        <a href="/ADMISSION/uploads/college_docs/<?=htmlspecialchars($detail['gst_doc'])?>" target="_blank"><i class="ph ph-receipt"></i><p>GST Certificate</p><small>Click to view</small></a>
        <?php else: ?>
        <i class="ph ph-minus-circle" style="color:#94a3b8"></i><p style="color:#94a3b8">Optional</p>
        <?php endif; ?>
      </div>
      <div class="doc-card">
        <?php if($detail['affiliation_doc']): ?>
        <a href="/ADMISSION/uploads/college_docs/<?=htmlspecialchars($detail['affiliation_doc'])?>" target="_blank"><i class="ph ph-certificate"></i><p>Affiliation Proof</p><small>Click to view</small></a>
        <?php else: ?>
        <i class="ph ph-minus-circle" style="color:#94a3b8"></i><p style="color:#94a3b8">Optional</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if($detail['status']==='pending'): ?>
  <div style="margin-top:16px;padding-top:12px;border-top:1px solid #e2e8f0;display:flex;gap:8px">
    <form method="POST"><input type="hidden" name="action" value="approve"><input type="hidden" name="account_id" value="<?=$detail['id']?>"><button class="btn btn-green"><i class="ph ph-check"></i> Approve Account</button></form>
    <button class="btn btn-red" onclick="document.getElementById('rejectModal').classList.add('show')"><i class="ph ph-x"></i> Reject</button>
  </div>
  <?php endif; ?>
</div>

<div class="modal-bg" id="rejectModal">
<div class="modal">
  <h3>Reject Account</h3>
  <form method="POST">
    <input type="hidden" name="action" value="reject">
    <input type="hidden" name="account_id" value="<?=$detail['id']?>">
    <textarea name="rejection_reason" rows="3" placeholder="Reason for rejection (required)" required></textarea>
    <div class="btns">
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('rejectModal').classList.remove('show')">Cancel</button>
      <button type="submit" class="btn btn-red">Reject</button>
    </div>
  </form>
</div>
</div>

<?php else: ?>

<div class="filters">
  <a href="/ADMISSION/admin/college_accounts.php" class="<?=!$filterStatus?'active':''?>">All</a>
  <a href="/ADMISSION/admin/college_accounts.php?status=pending" class="<?=$filterStatus==='pending'?'active':''?>">Pending</a>
  <a href="/ADMISSION/admin/college_accounts.php?status=approved" class="<?=$filterStatus==='approved'?'active':''?>">Approved</a>
  <a href="/ADMISSION/admin/college_accounts.php?status=rejected" class="<?=$filterStatus==='rejected'?'active':''?>">Rejected</a>
</div>

<div class="card">
<table>
<thead>
<tr><th>Institute</th><th>Type</th><th>Contact</th><th>PAN</th><th>Status</th><th>Date</th><th>Actions</th></tr>
</thead>
<tbody>
<?php if(empty($accounts)): ?>
<tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:24px">No accounts found.</td></tr>
<?php endif; ?>
<?php foreach($accounts as $a): ?>
<tr>
  <td style="font-weight:600"><?=htmlspecialchars($a['institute_name'])?></td>
  <td><span class="badge badge-blue"><?=ucfirst($a['institute_type'])?></span></td>
  <td><?=htmlspecialchars($a['contact_person'])?><br><span style="font-size:.68rem;color:#94a3b8"><?=htmlspecialchars($a['designation'] ?? '')?></span></td>
  <td><span style="font-family:monospace;font-size:.72rem"><?=htmlspecialchars($a['pan_number'] ?? '—')?></span></td>
  <td><span class="badge <?=($a['status']==='approved'?'badge-green':($a['status']==='rejected'?'badge-red':($a['status']==='pending'?'badge-yellow':'badge-blue')))?>"><?=ucfirst($a['status'])?></span></td>
  <td style="font-size:.72rem"><?=date('d M Y', strtotime($a['created_at']))?></td>
  <td>
    <a href="/ADMISSION/admin/college_accounts.php?view=<?=$a['id']?>" class="btn btn-ghost btn-sm"><i class="ph ph-eye"></i> View</a>
    <?php if($a['status']==='pending'): ?>
    <form method="POST" style="display:inline"><input type="hidden" name="action" value="approve"><input type="hidden" name="account_id" value="<?=$a['id']?>"><button class="btn btn-green btn-sm"><i class="ph ph-check"></i></button></form>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach;?>
</tbody>
</table>
</div>
<?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
