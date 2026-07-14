<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../panel_cms_2847/db.php';
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (!empty($_SESSION['college_account_id'])) { header('Location: ' . BASE_URL . '/college/dashboard.php'); exit; }

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$error = '';
$flash = $_GET['msg'] ?? '';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$rlKey = sys_get_temp_dir() . '/college_rl_' . md5($ip);
$attempts = file_exists($rlKey) ? json_decode(file_get_contents($rlKey), true) ?? [] : [];
$attempts = array_filter($attempts, fn($t) => (time() - $t) < 900);
$blocked = count($attempts) >= 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } elseif ($blocked) {
        $error = 'Too many attempts. Wait 15 min.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$email || !$password) { $error = 'Email and password required.'; }
        else {
            $stmt = $pdo->prepare("SELECT * FROM college_accounts WHERE email=?");
            $stmt->execute([$email]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$account) { $attempts[] = time(); file_put_contents($rlKey, json_encode(array_values($attempts)), LOCK_EX); $error = 'No account found.'; }
            elseif ($account['status'] !== 'active') { $error = 'Account is ' . $account['status'] . '.'; }
            elseif (!password_verify($password, $account['password_hash'])) { $attempts[] = time(); file_put_contents($rlKey, json_encode(array_values($attempts)), LOCK_EX); $error = 'Incorrect password. ' . (5 - count($attempts)) . ' left.'; }
            else {
                session_regenerate_id(true);
                $_SESSION['college_account_id'] = $account['id'];
                $_SESSION['college_name'] = $account['institute_name'];
                @unlink($rlKey);
                header('Location: ' . BASE_URL . '/college/dashboard.php');
                exit;
            }
        }
    }
}
$siteBase = defined('BASE_URL') ? BASE_URL : '/ADMISSION';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>College Login | AdmissionSeason</title>
<meta name="robots" content="noindex, nofollow">
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
<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{height:100%;overflow:hidden}
body{font-family:'Plus Jakarta Sans',sans-serif}

.page{display:grid;grid-template-columns:480px 1fr;height:100vh}

/* ═══ LEFT PANEL ═══ */
.left{background:#0f1d3d;position:relative;overflow:hidden;display:flex;flex-direction:column}

/* Background art */
.left-bg{position:absolute;inset:0;z-index:0}
.left-bg .orb{position:absolute;border-radius:50%;filter:blur(80px)}
.orb-1{width:300px;height:300px;top:-60px;right:-40px;background:rgba(96,165,250,.12)}
.orb-2{width:200px;height:200px;bottom:20%;left:-60px;background:rgba(139,92,246,.08)}
.orb-3{width:150px;height:150px;bottom:-30px;right:30%;background:rgba(34,197,94,.06)}
.grid-lines{position:absolute;inset:0;background-image:
  linear-gradient(rgba(255,255,255,.02) 1px, transparent 1px),
  linear-gradient(90deg, rgba(255,255,255,.02) 1px, transparent 1px);
  background-size:40px 40px}

.left-content{position:relative;z-index:1;display:flex;flex-direction:column;height:100%;padding:32px 36px 0}

/* Brand */
.brand{display:flex;align-items:center;gap:10px;margin-bottom:32px}
.brand-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#60a5fa,#818cf8);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff}
.brand span{font-size:1rem;font-weight:800;color:#fff}

/* Hero */
.hero{margin-bottom:auto}
.hero-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(96,165,250,.1);border:1px solid rgba(96,165,250,.15);border-radius:100px;padding:5px 14px;font-size:.7rem;font-weight:600;color:#60a5fa;margin-bottom:16px}
.hero-badge i{font-size:.85rem}
.hero h1{font-size:1.65rem;font-weight:800;color:#fff;line-height:1.3;letter-spacing:-.3px;margin-bottom:10px}
.hero h1 em{font-style:normal;background:linear-gradient(135deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:.82rem;color:rgba(255,255,255,.38);line-height:1.55}

/* Stats strip */
.stats-strip{display:flex;gap:0;padding:16px 0;border-top:1px solid rgba(255,255,255,.06)}
.ss-item{flex:1;text-align:center;position:relative}
.ss-item:not(:last-child)::after{content:'';position:absolute;right:0;top:10%;height:80%;width:1px;background:rgba(255,255,255,.06)}
.ss-num{font-size:1.15rem;font-weight:800;color:#fff}
.ss-lbl{font-size:.6rem;color:rgba(255,255,255,.28);text-transform:uppercase;letter-spacing:.4px;margin-top:1px}

/* Marketing card */
.mkt{margin:0;background:linear-gradient(135deg,rgba(245,158,11,.1),rgba(249,115,22,.06));border-top:1px solid rgba(245,158,11,.15);padding:20px 36px 24px;position:relative;z-index:1;flex-shrink:0}
.mkt-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.mkt-price{display:flex;align-items:baseline;gap:6px}
.mkt-price b{font-size:1.5rem;font-weight:800;color:#fbbf24}
.mkt-price s{font-size:.75rem;color:rgba(255,255,255,.25);font-weight:500}
.mkt-off{background:#ef4444;color:#fff;font-size:.56rem;font-weight:800;padding:3px 8px;border-radius:4px;text-transform:uppercase}
.mkt-title{font-size:.78rem;color:rgba(255,255,255,.5);margin-bottom:14px;line-height:1.5}
.mkt-title strong{color:rgba(255,255,255,.85)}
.mkt-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:16px}
.mkt-grid span{display:flex;align-items:center;gap:5px;font-size:.7rem;color:rgba(255,255,255,.5)}
.mkt-grid i{color:#22c55e;font-size:.75rem}
.mkt-btn{display:flex;align-items:center;justify-content:center;gap:7px;background:linear-gradient(135deg,#f59e0b,#f97316);color:#fff;font-size:.82rem;font-weight:700;padding:12px;border-radius:10px;text-decoration:none;transition:all .3s;width:100%}
.mkt-btn:hover{box-shadow:0 6px 24px rgba(245,158,11,.35);transform:translateY(-1px)}
.mkt-btn i{font-size:.95rem}

/* ═══ RIGHT PANEL ═══ */
.right{background:#fff;display:flex;align-items:center;justify-content:center;padding:40px}
.form-wrap{width:100%;max-width:380px}

.form-top{display:flex;align-items:center;gap:12px;margin-bottom:32px}
.form-top .back{width:40px;height:40px;border-radius:11px;border:1.5px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;font-size:1.1rem;transition:all .2s}
.form-top .back:hover{border-color:#19376D;color:#19376D}
.form-top h2{font-size:1.2rem;font-weight:800;color:#0B2447}

.alert{padding:12px 14px;border-radius:12px;font-size:.78rem;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.alert-success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}

.fg{margin-bottom:16px}
.fg label{display:block;font-size:.8rem;font-weight:600;color:#334155;margin-bottom:5px}
.fg input{width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:.9rem;font-family:inherit;background:#f8fafc;transition:all .2s;color:#1e293b}
.fg input:focus{outline:none;border-color:#19376D;background:#fff;box-shadow:0 0 0 3px rgba(25,55,109,.07)}
.fg-icon{position:relative}
.fg-icon i{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:1rem;color:#94a3b8;cursor:pointer}
.fg-icon i:hover{color:#19376D}
.fg-icon input{padding-right:42px}

.btn-primary{width:100%;padding:13px;border:none;border-radius:12px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;font-size:.9rem;font-weight:700;cursor:pointer;transition:all .3s;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(25,55,109,.3)}

.divider{display:flex;align-items:center;gap:12px;margin:20px 0}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e2e8f0}
.divider span{font-size:.72rem;color:#94a3b8;font-weight:500}

.form-foot{text-align:center}
.form-foot span{font-size:.82rem;color:#64748b}
.form-foot a{color:#19376D;font-weight:700;text-decoration:none}

@media(max-width:900px){
  .page{grid-template-columns:1fr}
  .left{display:none}
  .right{padding:28px 16px}
}
</style>
</head>
<body>
<div class="page">

<!-- LEFT PANEL -->
<div class="left">
  <div class="left-bg">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-lines"></div>
  </div>

  <div class="left-content">
    <div class="brand">
      <div class="brand-icon"><i class="ph-fill ph-graduation-cap"></i></div>
      <span>AdmissionSeason</span>
    </div>

    <div class="hero">
      <div class="hero-badge"><i class="ph ph-rocket-launch"></i> For Institutes & Colleges</div>
      <h1>Where India's <em>best colleges</em> get discovered</h1>
      <p>Manage your profile, courses, placements and track student enquiries from one powerful dashboard.</p>
    </div>

    <div class="stats-strip">
      <div class="ss-item"><div class="ss-num">26+</div><div class="ss-lbl">Colleges</div></div>
      <div class="ss-item"><div class="ss-num">1.5L+</div><div class="ss-lbl">Students</div></div>
      <div class="ss-item"><div class="ss-num">22+</div><div class="ss-lbl">Exams</div></div>
      <div class="ss-item"><div class="ss-num">50+</div><div class="ss-lbl">Courses</div></div>
    </div>
  </div>

  <div class="mkt">
    <div class="mkt-top">
      <div class="mkt-price"><b>₹9</b> <s>₹999</s></div>
      <div class="mkt-off">99% OFF</div>
    </div>
    <div class="mkt-title"><strong>Register your college today</strong> — lifetime listing on India's top discovery platform.</div>
    <div class="mkt-grid">
      <span><i class="ph-fill ph-check-circle"></i> Verified badge</span>
      <span><i class="ph-fill ph-check-circle"></i> Search rankings</span>
      <span><i class="ph-fill ph-check-circle"></i> Course management</span>
      <span><i class="ph-fill ph-check-circle"></i> Student leads</span>
    </div>
    <a href="<?= BASE_URL ?>/college/signup.php" class="mkt-btn"><i class="ph ph-rocket-launch"></i> Register Now — ₹9 Only</a>
  </div>
</div>

<!-- RIGHT PANEL -->
<div class="right">
<div class="form-wrap">
  <div class="form-top">
    <a href="<?= BASE_URL ?>/" class="back"><i class="ph ph-arrow-left"></i></a>
    <h2>Institute Login</h2>
  </div>

  <?php if($flash==='approved'):?><div class="alert alert-success"><i class="ph ph-check-circle"></i> Account approved! Login now.</div><?php endif;?>
  <?php if($flash==='logout'):?><div class="alert alert-success"><i class="ph ph-check-circle"></i> Logged out.</div><?php endif;?>
  <?php if($error):?><div class="alert alert-error"><i class="ph ph-warning-circle"></i> <?=$error?></div><?php endif;?>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <div class="fg"><label>Email Address</label><input type="email" name="email" placeholder="your@institute.ac.in" required value="<?=htmlspecialchars($_POST['email'] ?? '')?>"></div>
    <div class="fg"><label>Password</label><div class="fg-icon"><input type="password" name="password" id="pwd" placeholder="Enter your password" required><i class="ph ph-eye" onclick="const i=document.getElementById('pwd');i.type=i.type==='password'?'text':'password'"></i></div></div>
    <button type="submit" class="btn-primary"><i class="ph ph-sign-in"></i> Login</button>
  </form>

  <div class="divider"><span>New to AdmissionSeason?</span></div>
  <div class="form-foot"><span>Don't have an account? </span><a href="<?= BASE_URL ?>/college/signup.php">Create institute account &rarr;</a></div>
</div>
</div>

</div>
</body>
</html>
