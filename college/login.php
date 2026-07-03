<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../admin/db.php';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (!empty($_SESSION['college_account_id'])) { header('Location: ' . BASE_URL . '/college/dashboard.php'); exit; }

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$error = '';
$flash = $_GET['msg'] ?? '';

// CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// IP rate limiting
$rlKey = sys_get_temp_dir() . '/college_rl_' . md5($ip);
$attempts = file_exists($rlKey) ? json_decode(file_get_contents($rlKey), true) ?? [] : [];
$attempts = array_filter($attempts, fn($t) => (time() - $t) < 900);
$attemptCount = count($attempts);
$blocked = $attemptCount >= 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh.';
    } elseif ($blocked) {
        $error = 'Too many failed attempts. Try again in 15 minutes.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $error = 'Email and password are required.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM college_accounts WHERE email=?");
            $stmt->execute([$email]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                $attempts[] = time();
                file_put_contents($rlKey, json_encode(array_values($attempts)), LOCK_EX);
                $error = 'No account found with this email.';
            } elseif ($account['status'] === 'pending') {
                $error = 'Your account is pending admin approval.';
            } elseif ($account['status'] === 'rejected') {
                $error = 'Your account has been rejected.';
            } elseif ($account['status'] === 'suspended') {
                $error = 'Your account has been suspended.';
            } elseif (!password_verify($password, $account['password_hash'])) {
                $attempts[] = time();
                file_put_contents($rlKey, json_encode(array_values($attempts)), LOCK_EX);
                $remaining = 5 - count($attempts);
                $error = "Incorrect password. {$remaining} attempts remaining.";
            } else {
                session_regenerate_id(true);
                $_SESSION['college_account_id'] = $account['id'];
                $_SESSION['college_name'] = $account['institute_name'];
                $_SESSION['login_ip'] = $ip;
                $upd = $pdo->prepare("UPDATE college_accounts SET last_login=NOW() WHERE id=?");
                $upd->execute([$account['id']]);
                @unlink($rlKey);
                header('Location: ' . BASE_URL . '/college/dashboard.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Institute Login – AdmissionSeason</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;min-height:100vh;background:#0B2447;overflow-x:hidden}

.page{display:grid;grid-template-columns:1fr 1fr;min-height:100vh}

.left{background:linear-gradient(160deg,#0B2447 0%,#19376D 40%,#0d3b66 100%);display:flex;flex-direction:column;padding:40px 52px;position:relative;overflow:hidden;gap:0}
.left::before{content:'';position:absolute;top:-150px;right:-100px;width:400px;height:400px;background:radial-gradient(circle,rgba(96,165,250,.08) 0%,transparent 70%);pointer-events:none}

.brand{display:flex;align-items:center;gap:10px;margin-bottom:20px}
.brand-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#60a5fa,#818cf8);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff}
.brand span{font-size:1.15rem;font-weight:800;color:#fff;letter-spacing:-.3px}

.hero{margin-bottom:0}
.hero h1{font-size:2.2rem;font-weight:800;color:#fff;line-height:1.25;letter-spacing:-.5px;margin-bottom:16px}
.hero h1 em{font-style:normal;background:linear-gradient(135deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:.92rem;color:rgba(255,255,255,.5);line-height:1.65;max-width:380px}

.features{display:flex;flex-direction:column;gap:16px;margin-top:36px}
.feat{display:flex;align-items:flex-start;gap:12px}
.feat-icon{width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.06);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.feat-icon i{font-size:1rem;color:#60a5fa}
.feat-text strong{font-size:.85rem;color:#fff;display:block;margin-bottom:2px}
.feat-text span{font-size:.75rem;color:rgba(255,255,255,.4);line-height:1.4}

.deco-dots{position:absolute;top:200px;right:40px;display:grid;grid-template-columns:repeat(5,8px);gap:12px;opacity:.12}
.deco-dots span{width:4px;height:4px;border-radius:50%;background:#fff}

.right{background:#fff;display:flex;align-items:center;justify-content:center;padding:32px;position:relative}
.form-wrap{width:100%;max-width:380px}

.form-top{display:flex;align-items:center;gap:10px;margin-bottom:32px}
.form-top .back{width:36px;height:36px;border-radius:10px;border:1.5px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;font-size:1rem;transition:all .2s}
.form-top .back:hover{border-color:#19376D;color:#19376D}
.form-top h2{font-size:1.15rem;font-weight:800;color:#0B2447}

.alert{padding:12px 14px;border-radius:12px;font-size:.78rem;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.alert-success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}

.fg{margin-bottom:16px}
.fg label{display:block;font-size:.78rem;font-weight:600;color:#334155;margin-bottom:5px}
.fg input{width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:.9rem;font-family:inherit;background:#f8fafc;transition:all .2s;color:#1e293b}
.fg input:focus{outline:none;border-color:#19376D;background:#fff;box-shadow:0 0 0 3px rgba(25,55,109,.07)}

.fg-icon{position:relative}
.fg-icon i{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:1rem;color:#94a3b8;cursor:pointer;transition:color .2s}
.fg-icon i:hover{color:#19376D}
.fg-icon input{padding-right:42px}

.btn-primary{width:100%;padding:13px;border:none;border-radius:12px;background:linear-gradient(135deg,#0B2447,#19376D);color:#fff;font-size:.9rem;font-weight:700;cursor:pointer;transition:all .3s;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;position:relative;overflow:hidden}
.btn-primary::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.1),transparent);opacity:0;transition:opacity .3s}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(25,55,109,.3)}
.btn-primary:hover::before{opacity:1}

.divider{display:flex;align-items:center;gap:12px;margin:20px 0}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e2e8f0}
.divider span{font-size:.72rem;color:#94a3b8;font-weight:500}

.form-foot{text-align:center;margin-top:0}
.form-foot span{font-size:.82rem;color:#64748b}
.form-foot a{color:#19376D;font-weight:700;text-decoration:none;transition:color .2s}
.form-foot a:hover{color:#0B2447}

.forgot{display:block;text-align:right;font-size:.75rem;color:#64748b;text-decoration:none;font-weight:500;margin-top:-8px;margin-bottom:16px;transition:color .2s}
.forgot:hover{color:#19376D}

@media(max-width:960px){
  .page{grid-template-columns:1fr}
  .left{display:none}
  .right{padding:24px 16px;min-height:100vh}
}
@media(max-width:480px){
  .right{padding:20px 14px}
}
</style>
</head>
<body>
<div class="page">

<div class="left">
  <div class="brand">
    <div class="brand-icon"><i class="ph-fill ph-graduation-cap"></i></div>
    <span>AdmissionSeason</span>
  </div>

  <div class="hero">
    <h1>Welcome back to your <em>dashboard</em></h1>
    <p>Manage your college profile, update courses, track placements and student enquiries.</p>

    <div class="features">
      <div class="feat"><div class="feat-icon"><i class="ph ph-pencil-simple"></i></div><div class="feat-text"><strong>Update Anytime</strong><span>Edit courses, fees, cutoffs and placements in real-time</span></div></div>
      <div class="feat"><div class="feat-icon"><i class="ph ph-users-three"></i></div><div class="feat-text"><strong>Track Enquiries</strong><span>See which students are interested in your programs</span></div></div>
      <div class="feat"><div class="feat-icon"><i class="ph ph-seal-check"></i></div><div class="feat-text"><strong>Verified Badge</strong><span>Boost credibility with a verified institute tag</span></div></div>
    </div>
  </div>

  <div class="deco-dots">
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
  </div>
</div>

<div class="right">
<div class="form-wrap">
  <div class="form-top">
    <a href="<?= BASE_URL ?>/" class="back"><i class="ph ph-arrow-left"></i></a>
    <h2>Institute Login</h2>
  </div>

  <?php if($flash==='approved'):?><div class="alert alert-success"><i class="ph ph-check-circle"></i> Account approved! You can now login.</div><?php endif;?>
  <?php if($flash==='logout'):?><div class="alert alert-success"><i class="ph ph-check-circle"></i> Logged out successfully.</div><?php endif;?>
  <?php if($error):?><div class="alert alert-error"><i class="ph ph-warning-circle"></i> <?=$error?></div><?php endif;?>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <div class="fg"><label>Email Address</label><input type="email" name="email" placeholder="your@institute.ac.in" required value="<?=htmlspecialchars($_POST['email'] ?? '')?>"></div>
    <div class="fg"><label>Password</label><div class="fg-icon"><input type="password" name="password" id="pwd" placeholder="Enter your password" required autocomplete="current-password"><i class="ph ph-eye" onclick="togglePwd()"></i></div></div>
    <a href="#" class="forgot">Forgot password?</a>
    <button type="submit" class="btn-primary"><i class="ph ph-sign-in"></i> Login</button>
  </form>

  <div class="divider"><span>New to AdmissionSeason?</span></div>
  <div class="form-foot"><a href="<?= BASE_URL ?>/college/signup.php">Create institute account &rarr;</a></div>
</div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>
gsap.from('.brand',{y:-20,opacity:0,duration:.6,ease:'power3.out'});
gsap.from('.hero h1',{y:30,opacity:0,duration:.7,delay:.15,ease:'power3.out'});
gsap.from('.hero p',{y:20,opacity:0,duration:.6,delay:.3,ease:'power3.out'});
gsap.from('.feat',{y:20,opacity:0,duration:.5,delay:.45,stagger:.1,ease:'power3.out'});
gsap.from('.form-wrap',{x:30,opacity:0,duration:.6,delay:.2,ease:'power3.out'});
function togglePwd(){const i=document.getElementById('pwd');i.type=i.type==='password'?'text':'password';}
</script>
</body>
</html>
