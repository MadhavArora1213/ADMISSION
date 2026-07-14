<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../panel_cms_2847/db.php';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (!empty($_SESSION['college_account_id'])) { header('Location: ' . BASE_URL . '/college/dashboard.php'); exit; }

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$error = '';
$success = '';

// CSRF
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// IP rate limiting
$rlKey = sys_get_temp_dir() . '/college_signup_rl_' . md5($ip);
$attempts = file_exists($rlKey) ? json_decode(file_get_contents($rlKey), true) ?? [] : [];
$attempts = array_filter($attempts, fn($t) => (time() - $t) < 3600);
$blocked = count($attempts) >= 3;

function uploadDoc(array $file, string $prefix, bool $required = false): array {
    $dir = __DIR__ . '/../uploads/college_docs/';
  if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    return [null, 'Unable to create upload directory.'];
  }

  $allowedMimeToExt = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
  ];

  if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    return $required ? [null, 'Required document missing.'] : [null, null];
  }

  if ((int)$file['error'] !== UPLOAD_ERR_OK) {
    return [null, 'Upload failed. Please try again.'];
  }

  if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
    return [null, 'Invalid uploaded file.'];
  }

  if ((int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > 5 * 1024 * 1024) {
    return [null, 'File size must be less than 5MB.'];
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = (string)$finfo->file($file['tmp_name']);
  if (!isset($allowedMimeToExt[$mime])) {
    return [null, 'Only JPG, PNG, WEBP and PDF files are allowed.'];
  }

  $ext = $allowedMimeToExt[$mime];
  $name = $prefix . '_' . bin2hex(random_bytes(10)) . '.' . $ext;
  if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
    return [null, 'Could not save uploaded file.'];
  }

  // Sync to GitHub
  require_once __DIR__ . '/../panel_cms_2847/upload_sync.php';
  sync_to_github('uploads/college_docs/' . $name);

  return [$name, null];
}
function validatePan($n) { return preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $n); }
function validateAadhar($n) { return preg_match('/^\d{12}$/', preg_replace('/[\s-]/', '', $n)); }
function validateGstin($n) { return preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z]\d[Z][A-Z\d]$/', strtoupper($n)); }
function validatePhone($n) {
  $digits = preg_replace('/\D+/', '', $n);
  return strlen($digits) >= 10 && strlen($digits) <= 15;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh.';
    } elseif ($blocked) {
        $error = 'Too many registration attempts. Please try again later.';
    } else {
        $type=trim($_POST['institute_type']??'');$name=trim($_POST['institute_name']??'');$website=trim($_POST['website']??'');$stateId=(int)($_POST['state_id']??0);$city=trim($_POST['city']??'');$estYear=(int)($_POST['established_year']??0);$affiliation=trim($_POST['affiliation_details']??'');$person=trim($_POST['contact_person']??'');$designation=trim($_POST['designation']??'');$email=trim($_POST['email']??'');$phone=trim($_POST['phone']??'');$password=$_POST['password']??'';$pan=strtoupper(trim($_POST['pan_number']??''));$aadhar=preg_replace('/[\s-]/','',$_POST['aadhar_number']??'');$gstin=strtoupper(trim($_POST['gst_number']??''));$upiRef=trim($_POST['upi_transaction_id']??'');

        if (!$type||!$name||!$person||!$designation||!$email||!$password||!$phone||!$pan||!$aadhar||!$upiRef) $error='All required fields must be filled.';
        elseif (strlen($password)<8) $error='Password must be at least 8 characters.';
        elseif (!preg_match('/[A-Z]/',$password)||!preg_match('/[a-z]/',$password)||!preg_match('/[0-9]/',$password)) $error='Password must contain uppercase, lowercase and a number.';
        elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Invalid email address.';
        elseif ($website && !filter_var($website, FILTER_VALIDATE_URL)) $error='Invalid website URL.';
        elseif (!validatePhone($phone)) $error='Invalid phone number.';
        elseif ($estYear && ($estYear < 1800 || $estYear > (int)date('Y'))) $error='Invalid established year.';
        elseif (!validatePan($pan)) $error='Invalid PAN format. Expected: ABCDE1234F';
        elseif (!validateAadhar($aadhar)) $error='Aadhar must be exactly 12 digits.';
        elseif ($gstin&&!validateGstin($gstin)) $error='Invalid GSTIN format.';
        else {
            $chk=$pdo->prepare("SELECT id FROM college_accounts WHERE email=?");$chk->execute([$email]);
            if($chk->fetch()) $error='An account with this email already exists.';
            else {
                $chkPan=$pdo->prepare("SELECT id FROM college_accounts WHERE pan_number=?");$chkPan->execute([$pan]);
                if($chkPan->fetch()) $error='This PAN number is already registered.';
                else {
                [$panDoc, $panErr] = uploadDoc($_FILES['pan_doc'] ?? [], 'pan', true);
                [$aadharDoc, $aadharErr] = uploadDoc($_FILES['aadhar_doc'] ?? [], 'aadhar', true);
                [$gstDoc, $gstErr] = uploadDoc($_FILES['gst_doc'] ?? [], 'gst', false);
                [$affDoc, $affErr] = uploadDoc($_FILES['affiliation_doc'] ?? [], 'aff', false);
                [$payDoc, $payErr] = uploadDoc($_FILES['payment_screenshot'] ?? [], 'payment', true);

                if ($panErr || $aadharErr || $gstErr || $affErr || $payErr) {
                  $error = $payErr ? ('Payment: ' . $payErr) : ($panErr ? ('PAN: ' . $panErr) : ($aadharErr ? ('Aadhar: ' . $aadharErr) : ($gstErr ? ('GST: ' . $gstErr) : ('Affiliation: ' . $affErr))));
                } else {
                    $id=sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
                    $hash=password_hash($password,PASSWORD_DEFAULT);
                  $pdo->prepare("INSERT INTO college_accounts (id,institute_type,institute_name,contact_person,designation,email,phone,website,state_id,city,established_year,affiliation_details,pan_number,aadhar_number,gst_number,pan_doc,aadhar_doc,gst_doc,affiliation_doc,payment_screenshot,upi_transaction_id,password_hash,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$id,$type,$name,$person,$designation,$email,$phone,$website?:null,$stateId?:null,$city?:null,$estYear?:null,$affiliation?:null,$pan,$aadhar,$gstin?:null,$panDoc,$aadharDoc,$gstDoc,$affDoc,$payDoc,$upiRef,$hash,'pending']);
                    $attempts[] = time();
                    file_put_contents($rlKey, json_encode(array_values($attempts)), LOCK_EX);
                    $success='Registration submitted! Our team will verify your documents within 24-48 hours.';
                }
            }
        }
    }
}
}
$states=$pdo->query("SELECT id,name FROM states ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$siteBase = defined('BASE_URL') ? BASE_URL : '/ADMISSION';
$canonicalUrl = $siteBase . '/college/signup.php';
$pageTitle = 'Register Your College/Institute - Free Listing | AdmissionSeason';
$metaDesc = 'Register your college or institute on AdmissionSeason for free. Get listed, attract students, manage applications and grow your campus visibility across India.';
$metaKeywords = 'register college, college registration, list college online, institute registration, college listing india, add college AdmissionSeason, free college listing, college marketing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
<meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<link rel="canonical" href="<?= $canonicalUrl ?>">
<meta name="author" content="AdmissionSeason">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-2JZX2204BL"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-2JZX2204BL');
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;min-height:100vh;background:#0B2447;overflow-x:hidden}

.page{display:grid;grid-template-columns:1fr 1fr;min-height:100vh}

/* ═══ LEFT PANEL ═══ */
.left{background:linear-gradient(160deg,#0B2447 0%,#19376D 40%,#0d3b66 100%);display:flex;flex-direction:column;padding:40px 52px;position:relative;overflow:hidden;gap:0}
.left::before{content:'';position:absolute;top:-150px;right:-100px;width:400px;height:400px;background:radial-gradient(circle,rgba(96,165,250,.08) 0%,transparent 70%);pointer-events:none}
.left::after{content:'';position:absolute;bottom:-80px;left:-60px;width:280px;height:280px;background:radial-gradient(circle,rgba(139,92,246,.06) 0%,transparent 70%);pointer-events:none}

.brand{display:flex;align-items:center;gap:10px;margin-bottom:20px}
.brand-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#60a5fa,#818cf8);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff}
.brand span{font-size:1.15rem;font-weight:800;color:#fff;letter-spacing:-.3px}

.hero{margin-bottom:0}
.hero h1{font-size:2.4rem;font-weight:800;color:#fff;line-height:1.2;letter-spacing:-.5px;margin-bottom:12px}
.hero h1 em{font-style:normal;background:linear-gradient(135deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:.95rem;color:rgba(255,255,255,.55);line-height:1.65;max-width:420px}

.stats-row{display:flex;gap:28px;margin-top:36px}
.stat{display:flex;flex-direction:column}
.stat-num{font-size:1.6rem;font-weight:800;color:#fff}
.stat-label{font-size:.72rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.5px;margin-top:2px}

.trust-bar{display:flex;align-items:center;gap:20px;margin-top:auto;padding-top:28px;border-top:1px solid rgba(255,255,255,.08)}
.trust-item{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.45);font-size:.75rem}
.trust-item i{font-size:1rem;color:rgba(255,255,255,.3)}

.deco-dots{position:absolute;top:200px;right:40px;display:grid;grid-template-columns:repeat(5,8px);gap:12px;opacity:.12}
.deco-dots span{width:4px;height:4px;border-radius:50%;background:#fff}

/* ═══ RIGHT PANEL ═══ */
.right{background:#fff;display:flex;align-items:center;justify-content:center;padding:32px;position:relative}
.form-wrap{width:100%;max-width:440px}

.form-top{display:flex;align-items:center;gap:10px;margin-bottom:28px}
.form-top .back{width:36px;height:36px;border-radius:10px;border:1.5px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;font-size:1rem;transition:all .2s}
.form-top .back:hover{border-color:#19376D;color:#19376D}
.form-top h2{font-size:1.15rem;font-weight:800;color:#0B2447}

.alert{padding:12px 14px;border-radius:12px;font-size:.78rem;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.alert-success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}

/* Steps */
.steps-nav{display:flex;gap:0;margin-bottom:24px;background:#f8fafc;border-radius:12px;padding:4px}
.steps-nav button{flex:1;padding:10px 0;border:none;background:none;border-radius:10px;font-size:.72rem;font-weight:700;color:#94a3b8;cursor:pointer;transition:all .25s;font-family:inherit;display:flex;flex-direction:column;align-items:center;gap:4px}
.steps-nav button span{font-size:.62rem;color:#cbd5e1}
.steps-nav button.active{background:#fff;color:#0B2447;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.steps-nav button.active span{color:#64748b}
.steps-nav button.done{color:#16a34a}
.steps-nav button.done span{color:#16a34a}

.step-section{display:none}
.step-section.active{display:block;animation:slideUp .3s ease}
@keyframes slideUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

.fieldset-label{display:flex;align-items:center;gap:6px;font-size:.7rem;font-weight:700;color:#19376D;text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px}
.fieldset-label i{font-size:.85rem}

.type-chips{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px}
.type-chip{position:relative}
.type-chip input{position:absolute;opacity:0;pointer-events:none}
.type-chip label{display:flex;align-items:center;justify-content:center;gap:6px;padding:12px;border:1.5px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all .2s;font-size:.78rem;font-weight:600;color:#64748b;text-align:center}
.type-chip label i{font-size:1.1rem}
.type-chip input:checked+label{border-color:#19376D;background:linear-gradient(135deg,#f0f4ff,#ede9fe);color:#19376D;box-shadow:0 0 0 3px rgba(25,55,109,.06)}

.fg{margin-bottom:14px}
.fg>label{display:flex;align-items:center;gap:4px;font-size:.75rem;font-weight:600;color:#334155;margin-bottom:5px}
.fg>label .r{color:#dc2626}
.fg input,.fg select{width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.85rem;font-family:inherit;background:#f8fafc;transition:all .2s;color:#1e293b}
.fg input:focus,.fg select:focus{outline:none;border-color:#19376D;background:#fff;box-shadow:0 0 0 3px rgba(25,55,109,.07)}
.fg input.error{border-color:#dc2626;background:#fef2f2}
.fg .hint{font-size:.68rem;color:#94a3b8;margin-top:3px}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}

.uploads-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
.upload-box{border:1.5px dashed #e2e8f0;border-radius:12px;padding:20px 12px;text-align:center;cursor:pointer;transition:all .25s;background:#fafbfc;position:relative;overflow:hidden}
.upload-box::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,transparent,rgba(25,55,109,.01));opacity:0;transition:opacity .25s}
.upload-box:hover{border-color:#818cf8;background:#f5f3ff}
.upload-box:hover::before{opacity:1}
.upload-box.has-file{border-color:#16a34a;border-style:solid;background:#f0fdf4}
.upload-box input{display:none}
.upload-box .u-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;background:#f1f5f9;transition:all .25s}
.upload-box:hover .u-icon{background:#ede9fe}
.upload-box.has-file .u-icon{background:#dcfce7}
.upload-box .u-icon i{font-size:1.2rem;color:#94a3b8;transition:color .25s}
.upload-box:hover .u-icon i{color:#818cf8}
.upload-box.has-file .u-icon i{color:#16a34a}
.upload-box p{font-size:.75rem;color:#334155;font-weight:600;margin:0}
.upload-box .u-sub{font-size:.65rem;color:#94a3b8;margin-top:2px}
.upload-box .u-done{display:none;font-size:.68rem;color:#16a34a;font-weight:700;margin-top:8px;align-items:center;justify-content:center;gap:4px}
.upload-box.has-file .u-done{display:flex}
.upload-box.has-file .u-sub{display:none}

.info-banner{background:linear-gradient(135deg,#f8fafc,#f0f4ff);border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start}
.info-banner .info-icon{width:32px;height:32px;border-radius:8px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.info-banner .info-icon i{font-size:.9rem;color:#7c3aed}
.info-banner p{font-size:.72rem;color:#64748b;line-height:1.55;margin:0}

.btn-submit{width:100%;padding:13px;border:none;border-radius:12px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;font-size:.9rem;font-weight:700;cursor:pointer;transition:all .3s;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;position:relative;overflow:hidden}
.btn-submit::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.1),transparent);opacity:0;transition:opacity .3s}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(25,55,109,.3)}
.btn-submit:hover::before{opacity:1}
.btn-submit:active{transform:translateY(0)}

.btn-outline{background:#fff;color:#0B2447;border:1.5px solid #e2e8f0}
.btn-outline:hover{border-color:#19376D;background:#f8fafc;box-shadow:0 4px 12px rgba(0,0,0,.04)}

.nav-btns{display:grid;grid-template-columns:1fr 1.6fr;gap:10px;margin-top:4px}

.form-foot{text-align:center;margin-top:20px;padding-top:14px;border-top:1px solid #f1f5f9}
.form-foot span{font-size:.8rem;color:#64748b}
.form-foot a{color:#19376D;font-weight:700;text-decoration:none;transition:color .2s}
.form-foot a:hover{color:#0B2447}

@media(max-width:960px){
  .page{grid-template-columns:1fr}
  .left{display:none}
  .right{padding:24px 16px;min-height:100vh}
}
@media(max-width:480px){
  .right{padding:16px 12px}
  .form-wrap{max-width:100%}
  .row2{grid-template-columns:1fr}
  .uploads-grid{grid-template-columns:1fr}
  .type-chips{grid-template-columns:1fr 1fr}
  .nav-btns{grid-template-columns:1fr}
  .steps-nav button{font-size:.65rem;padding:8px 0}
}
    /* State Autocomplete Dropdown Styles */
    .state-autocomplete-wrapper {
      position: relative;
      width: 100%;
    }
    
    .state-suggestions-dropdown {
      position: absolute;
      top: calc(100% + 4px);
      left: 0;
      width: 100%;
      background: #fff;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      max-height: 220px;
      overflow-y: auto;
      z-index: 999;
      box-shadow: 0 10px 25px rgba(11, 36, 71, 0.08);
      scrollbar-width: thin;
      text-align: left;
    }
    
    .state-item {
      font-size: 0.85rem;
      font-weight: 600;
      color: #334155;
      padding: 10px 14px;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .state-item:hover {
      background: #f1f5f9;
      color: #19376D;
    }
    
    .no-states-found {
      font-size: 0.82rem;
      font-weight: 600;
      color: #94a3b8;
      padding: 12px;
      text-align: center;
    }
  </style>
</head>
<body>
<div class="page">

<!-- LEFT -->
<div class="left">
  <div class="brand">
    <div class="brand-icon"><i class="ph-fill ph-graduation-cap"></i></div>
    <span>AdmissionSeason</span>
  </div>

  <div class="hero">
    <h1>List your institute<br>on India's <em>#1 platform</em></h1>
    <p>Join 500+ verified colleges managing their admission presence, courses and placements — all from a single dashboard.</p>

    <div class="stats-row">
      <div class="stat"><span class="stat-num">500+</span><span class="stat-label">Verified Colleges</span></div>
      <div class="stat"><span class="stat-num">2.5L+</span><span class="stat-label">Monthly Students</span></div>
      <div class="stat"><span class="stat-num">48h</span><span class="stat-label">Avg. Approval</span></div>
    </div>

    <div class="trust-bar">
      <div class="trust-item"><i class="ph-fill ph-shield-check"></i> KYC Verified</div>
      <div class="trust-item"><i class="ph-fill ph-lock-key"></i> Encrypted Data</div>
      <div class="trust-item"><i class="ph-fill ph-seal-check"></i> UGC Recognized</div>
    </div>
  </div>

  <div class="deco-dots">
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
  </div>
</div>

<!-- RIGHT -->
<div class="right">
<div class="form-wrap">
  <div class="form-top">
    <a href="<?= BASE_URL ?>/" class="back"><i class="ph ph-arrow-left"></i></a>
    <h2>Register Institute</h2>
  </div>

  <?php if($error): ?><div class="alert alert-error"><i class="ph ph-warning-circle"></i> <?=$error?></div><?php endif;?>
  <?php if($success): ?><div class="alert alert-success"><i class="ph ph-check-circle"></i> <?=$success?></div><?php endif;?>

  <?php if(!$success): ?>
  <div class="steps-nav">
    <button class="active" id="sn1" onclick="goStep(1)"><i class="ph ph-buildings"></i>Institute<span>Details</span></button>
    <button id="sn2" onclick="goStep(2)"><i class="ph ph-shield-check"></i>KYC<span>Verification</span></button>
    <button id="sn3" onclick="goStep(3)"><i class="ph ph-upload-simple"></i>Documents<span>Upload</span></button>
    <button id="sn4" onclick="goStep(4)"><i class="ph ph-currency-circle-dollar"></i>Payment<span>₹9</span></button>
  </div>

  <form method="POST" enctype="multipart/form-data" id="regForm" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <!-- STEP 1 -->
    <div class="step-section active" id="s1">
      <div class="fieldset-label"><i class="ph ph-building"></i> Institute Information</div>

      <div class="type-chips">
        <div class="type-chip"><input type="radio" name="institute_type" id="tC" value="college" checked><label for="tC"><i class="ph ph-graduation-cap"></i>College</label></div>
        <div class="type-chip"><input type="radio" name="institute_type" id="tU" value="university"><label for="tU"><i class="ph ph-buildings"></i>University</label></div>
        <div class="type-chip"><input type="radio" name="institute_type" id="tI" value="institute"><label for="tI"><i class="ph ph-flask"></i>Institute</label></div>
      </div>

      <div class="fg"><label>Institute Name <span class="r">*</span></label><input type="text" name="institute_name" placeholder="e.g. Indian Institute of Technology Delhi" data-req="1" value="<?=htmlspecialchars($_POST['institute_name']??'')?>"></div>
      <div class="row2">
        <div class="fg">
          <label>State</label>
          <div class="state-autocomplete-wrapper">
            <?php
              $initialStateName = '';
              if (!empty($_POST['state_id'])) {
                  foreach ($states as $s) {
                      if ((int)$s['id'] === (int)$_POST['state_id']) {
                          $initialStateName = $s['name'];
                          break;
                      }
                  }
              }
            ?>
            <input type="text" id="state_input" placeholder="Select State" autocomplete="off" value="<?= htmlspecialchars($initialStateName) ?>" style="padding-right:32px;">
            <input type="hidden" name="state_id" id="state_id_input" value="<?= htmlspecialchars($_POST['state_id'] ?? '') ?>">
            <i class="ph ph-caret-down" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #64748b; font-size: 0.95rem;"></i>
            <div class="state-suggestions-dropdown" id="state_suggestions" style="display: none;"></div>
          </div>
        </div>
        <div class="fg"><label>City</label><input type="text" name="city" placeholder="New Delhi" value="<?=htmlspecialchars($_POST['city']??'')?>"></div>
      </div>
      <div class="row2">
        <div class="fg"><label>Website</label><input type="url" name="website" placeholder="https://www.example.ac.in" value="<?=htmlspecialchars($_POST['website']??'')?>"></div>
        <div class="fg"><label>Established Year</label><input type="number" name="established_year" placeholder="1961" min="1800" max="<?=date('Y')?>" value="<?=htmlspecialchars($_POST['established_year']??'')?>"></div>
      </div>
      <div class="fg"><label>UGC / AICTE / NAAC Details</label><input type="text" name="affiliation_details" placeholder="e.g. UGC Approved, NAAC A+ Grade" value="<?=htmlspecialchars($_POST['affiliation_details']??'')?>"></div>

      <button type="button" class="btn-submit" onclick="goStep(2)">Continue to KYC <i class="ph ph-arrow-right"></i></button>
    </div>

    <!-- STEP 2 -->
    <div class="step-section" id="s2">
      <div class="fieldset-label"><i class="ph ph-user"></i> Personal KYC Verification</div>

      <div class="row2">
        <div class="fg"><label>Contact Person <span class="r">*</span></label><input type="text" name="contact_person" placeholder="Full name" data-req="1" value="<?=htmlspecialchars($_POST['contact_person']??'')?>"></div>
        <div class="fg"><label>Designation <span class="r">*</span></label><input type="text" name="designation" placeholder="Director / Dean" data-req="1" value="<?=htmlspecialchars($_POST['designation']??'')?>"></div>
      </div>
      <div class="row2">
        <div class="fg"><label>Official Email <span class="r">*</span></label><input type="email" name="email" placeholder="admin@college.ac.in" data-req="1" value="<?=htmlspecialchars($_POST['email']??'')?>"></div>
        <div class="fg"><label>Mobile Number <span class="r">*</span></label><input type="tel" name="phone" placeholder="+91 98765 43210" data-req="1" value="<?=htmlspecialchars($_POST['phone']??'')?>"></div>
      </div>
      <div class="row2">
        <div class="fg"><label>PAN Number <span class="r">*</span></label><input type="text" name="pan_number" placeholder="ABCDE1234F" maxlength="10" data-req="1" style="text-transform:uppercase" value="<?=htmlspecialchars($_POST['pan_number']??'')?>"><div class="hint">Authorized signatory PAN</div></div>
        <div class="fg"><label>Aadhar Number <span class="r">*</span></label><input type="text" name="aadhar_number" placeholder="1234 5678 9012" maxlength="14" data-req="1" value="<?=htmlspecialchars($_POST['aadhar_number']??'')?>"><div class="hint">12-digit Aadhar</div></div>
      </div>
      <div class="fg"><label>GSTIN <span style="color:#94a3b8;font-weight:400">(Optional)</span></label><input type="text" name="gst_number" placeholder="22AAAAA0000A1Z5" maxlength="15" style="text-transform:uppercase" value="<?=htmlspecialchars($_POST['gst_number']??'')?>"><div class="hint">Only for private institutions</div></div>
      <div class="fg"><label>Password <span class="r">*</span></label><input type="password" name="password" placeholder="Min 6 characters" data-req="1" minlength="6"></div>

      <div class="nav-btns">
        <button type="button" class="btn-submit btn-outline" onclick="goStep(1)"><i class="ph ph-arrow-left"></i> Back</button>
        <button type="button" class="btn-submit" onclick="goStep(3)">Continue <i class="ph ph-arrow-right"></i></button>
      </div>
    </div>

    <!-- STEP 3 -->
    <div class="step-section" id="s3">
      <div class="fieldset-label"><i class="ph ph-file-text"></i> Upload KYC Documents</div>

      <div class="uploads-grid">
        <div class="upload-box" onclick="this.querySelector('input').click()">
          <input type="file" name="pan_doc" accept=".jpg,.jpeg,.png,.webp,.pdf" data-req="1" onchange="handleFile(this)">
          <div class="u-icon"><i class="ph ph-identification-card"></i></div>
          <p>PAN Card <span class="r">*</span></p>
          <div class="u-sub">JPG, PNG or PDF</div>
          <div class="u-done"><i class="ph-fill ph-check-circle"></i> File Selected</div>
        </div>
        <div class="upload-box" onclick="this.querySelector('input').click()">
          <input type="file" name="aadhar_doc" accept=".jpg,.jpeg,.png,.webp,.pdf" data-req="1" onchange="handleFile(this)">
          <div class="u-icon"><i class="ph ph-card"></i></div>
          <p>Aadhar Card <span class="r">*</span></p>
          <div class="u-sub">JPG, PNG or PDF</div>
          <div class="u-done"><i class="ph-fill ph-check-circle"></i> File Selected</div>
        </div>
        <div class="upload-box" onclick="this.querySelector('input').click()">
          <input type="file" name="gst_doc" accept=".jpg,.jpeg,.png,.webp,.pdf" onchange="handleFile(this)">
          <div class="u-icon"><i class="ph ph-receipt"></i></div>
          <p>GST Certificate</p>
          <div class="u-sub">Optional</div>
          <div class="u-done"><i class="ph-fill ph-check-circle"></i> File Selected</div>
        </div>
        <div class="upload-box" onclick="this.querySelector('input').click()">
          <input type="file" name="affiliation_doc" accept=".jpg,.jpeg,.png,.webp,.pdf" onchange="handleFile(this)">
          <div class="u-icon"><i class="ph ph-certificate"></i></div>
          <p>Affiliation Proof</p>
          <div class="u-sub">UGC/AICTE letter</div>
          <div class="u-done"><i class="ph-fill ph-check-circle"></i> File Selected</div>
        </div>
      </div>

      <div class="info-banner">
        <div class="info-icon"><i class="ph ph-info"></i></div>
        <p><strong>Verification Process:</strong> Our team reviews documents within 24-48 hours. PAN & Aadhar are mandatory. GST & affiliation proofs are optional but speed up approval.</p>
      </div>

      <div class="nav-btns">
        <button type="button" class="btn-submit btn-outline" onclick="goStep(2)"><i class="ph ph-arrow-left"></i> Back</button>
        <button type="button" class="btn-submit" onclick="goStep(4)">Continue to Payment <i class="ph ph-arrow-right"></i></button>
      </div>
    </div>

    <!-- STEP 4: Payment -->
    <div class="step-section" id="s4">
      <div class="fieldset-label"><i class="ph ph-currency-circle-dollar"></i> Registration Fee — ₹9</div>

      <div class="pay-info" style="background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:1px solid #bbf7d0;border-radius:12px;padding:16px;margin-bottom:18px">
        <p style="font-size:.82rem;color:#166534;font-weight:600;margin-bottom:4px"><i class="ph-fill ph-check-circle" style="color:#16a34a"></i> One-time registration fee of just ₹9</p>
        <p style="font-size:.72rem;color:#15803d;line-height:1.5">This covers your institute listing on AdmissionSeason. Pay via UPI using the QR code below.</p>
      </div>

      <div style="text-align:center;margin-bottom:18px">
        <div style="background:#fff;border:2px solid #e2e8f0;border-radius:16px;padding:20px;display:inline-block;position:relative">
          <img src="<?= $siteBase ?>/assets/img/QR.jpg" alt="Scan to Pay ₹9" style="width:220px;height:auto;border-radius:12px;object-fit:contain">
          <div style="position:absolute;bottom:-10px;left:50%;transform:translateX(-50%);background:#0B2447;color:#fff;font-size:.65rem;font-weight:700;padding:3px 12px;border-radius:6px;white-space:nowrap">UPI — vksandal1102-2@oksbi</div>
        </div>
      </div>

      <div class="fg">
        <label>UPI ID / Transaction Reference <span class="r">*</span></label>
        <input type="text" name="upi_transaction_id" placeholder="e.g. 123456789012 or user@upi" data-req="1" value="<?=htmlspecialchars($_POST['upi_transaction_id']??'')?>">
        <div class="hint">Enter UPI reference number or transaction ID for verification</div>
      </div>

      <div class="fg" style="margin-bottom:14px">
        <label>Payment Screenshot <span class="r">*</span></label>
        <div class="upload-box" onclick="this.querySelector('input').click()" style="margin-top:4px">
          <input type="file" name="payment_screenshot" accept=".jpg,.jpeg,.png,.webp" data-req="1" onchange="handleFile(this)">
          <div class="u-icon"><i class="ph ph-screenshot"></i></div>
          <p>Upload Payment Screenshot <span class="r">*</span></p>
          <div class="u-sub">JPG, PNG or WEBP — Max 5MB</div>
          <div class="u-done"><i class="ph-fill ph-check-circle"></i> File Selected</div>
        </div>
      </div>

      <div class="info-banner">
        <div class="info-icon"><i class="ph ph-info"></i></div>
        <p><strong>After Payment:</strong> Upload your payment screenshot and UPI reference. Our team will verify and activate your account within 24-48 hours.</p>
      </div>

      <div class="nav-btns">
        <button type="button" class="btn-submit btn-outline" onclick="goStep(3)"><i class="ph ph-arrow-left"></i> Back</button>
        <button type="submit" class="btn-submit"><i class="ph ph-check-circle"></i> Submit & Pay ₹9</button>
      </div>
    </div>
  </form>

  <div class="form-foot"><span>Already registered? </span><a href="<?= BASE_URL ?>/college/login.php">Sign in</a></div>
  <?php endif; ?>
</div>
</div>
</div>

<script>
gsap.from('.brand',{y:-20,opacity:0,duration:.6,ease:'power3.out'});
gsap.from('.hero h1',{y:30,opacity:0,duration:.7,delay:.15,ease:'power3.out'});
gsap.from('.hero p',{y:20,opacity:0,duration:.6,delay:.3,ease:'power3.out'});
gsap.from('.stat',{y:20,opacity:0,duration:.5,delay:.45,stagger:.1,ease:'power3.out'});
gsap.from('.trust-item',{y:10,opacity:0,duration:.4,delay:.7,stagger:.08,ease:'power3.out'});
gsap.from('.form-wrap',{x:30,opacity:0,duration:.6,delay:.2,ease:'power3.out'});
let cur=1;
function goStep(n){
  if(n>cur&&!validStep(cur))return;
  document.querySelectorAll('.step-section').forEach(s=>s.classList.remove('active'));
  document.getElementById('s'+n).classList.add('active');
  for(let i=1;i<=4;i++){
    const b=document.getElementById('sn'+i);
    if(!b)continue;
    b.classList.remove('active','done');
    if(i<n){b.classList.add('done');b.querySelector('i').className='ph-fill ph-check-circle';}
    else if(i===n)b.classList.add('active');
  }
  cur=n;window.scrollTo({top:0,behavior:'smooth'});
}
function validStep(n){
  let ok=true;
  document.getElementById('s'+n).querySelectorAll('[data-req="1"]').forEach(inp=>{
    inp.classList.remove('error');
    if(!inp.value||!inp.value.trim()){inp.classList.add('error');ok=false;}
  });
  if(!ok){const f=document.querySelector('#s'+n+' .error');if(f)f.focus();}
  return ok;
}
const f=document.getElementById('regForm');
if(f)f.addEventListener('submit',e=>{if(!validStep(4))e.preventDefault();});
function handleFile(inp){
  if(!(inp.files&&inp.files[0]))return;
  const f=inp.files[0];
  const allowed=['image/jpeg','image/png','image/webp','application/pdf'];
  if(!allowed.includes(f.type)){alert('Only JPG, PNG, WEBP or PDF files are allowed.');inp.value='';inp.closest('.upload-box').classList.remove('has-file');return;}
  if(f.size>5*1024*1024){alert('File size must be less than 5MB.');inp.value='';inp.closest('.upload-box').classList.remove('has-file');return;}
  inp.closest('.upload-box').classList.add('has-file');
}
document.querySelector('input[name="aadhar_number"]')?.addEventListener('input',function(){
  let v=this.value.replace(/\D/g,'').substring(0,12);
  this.value=v.replace(/(\d{4})(?=\d)/g,'$1 ');
});

// State Autocomplete Suggest Controller
const statesData = <?= json_encode($states) ?>;

const stateInput = document.getElementById('state_input');
const stateIdInput = document.getElementById('state_id_input');
const stateSuggestions = document.getElementById('state_suggestions');

function renderStateSuggestions(query = '') {
  stateSuggestions.innerHTML = '';
  
  if (query.trim() === '') {
    // Show all states
    statesData.forEach(state => {
      const stateItem = document.createElement('div');
      stateItem.className = 'state-item';
      stateItem.innerHTML = `<i class="ph ph-map-pin"></i> <span>${state.name}</span>`;
      stateItem.onclick = () => selectState(state.id, state.name);
      stateSuggestions.appendChild(stateItem);
    });
  } else {
    // Filter matching states
    const lowerQuery = query.toLowerCase();
    const matches = statesData.filter(s => s.name.toLowerCase().includes(lowerQuery));
    
    if (matches.length === 0) {
      const noFound = document.createElement('div');
      noFound.className = 'no-states-found';
      noFound.innerText = 'No matching states';
      stateSuggestions.appendChild(noFound);
    } else {
      matches.forEach(state => {
        const stateItem = document.createElement('div');
        stateItem.className = 'state-item';
        
        const idx = state.name.toLowerCase().indexOf(lowerQuery);
        if (idx >= 0) {
          const before = state.name.substring(0, idx);
          const matchText = state.name.substring(idx, idx + lowerQuery.length);
          const after = state.name.substring(idx + lowerQuery.length);
          stateItem.innerHTML = `<i class="ph ph-map-pin"></i> <span>${before}<strong>${matchText}</strong>${after}</span>`;
        } else {
          stateItem.innerHTML = `<i class="ph ph-map-pin"></i> <span>${state.name}</span>`;
        }
        
        stateItem.onclick = () => selectState(state.id, state.name);
        stateSuggestions.appendChild(stateItem);
      });
    }
  }
}

function selectState(stateId, stateName) {
  stateInput.value = stateName;
  stateIdInput.value = stateId;
  stateSuggestions.style.display = 'none';
}

if (stateInput) {
  stateInput.addEventListener('focus', () => {
    renderStateSuggestions(stateInput.value);
    stateSuggestions.style.display = 'block';
  });
  
  stateInput.addEventListener('input', (e) => {
    renderStateSuggestions(e.target.value);
  });
  
  // Close suggestions when clicking outside
  document.addEventListener('click', (e) => {
    if (!stateInput.contains(e.target) && !stateSuggestions.contains(e.target)) {
      stateSuggestions.style.display = 'none';
    }
  });
}
</script>
</body>
</html>
