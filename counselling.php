<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success = false;
$error = '';

// Fetch active courses and states for dropdowns
try {
    $courses = $pdo->query("SELECT id, course_name FROM courses WHERE status='active' ORDER BY course_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $states = $pdo->query("SELECT id, name FROM states ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $courses = [];
    $states = [];
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state_id = $_POST['state_id'] ?? null;
    $course_id = $_POST['course_id'] ?? null;
    $class_12_score = $_POST['class_12_score'] !== '' ? (float)$_POST['class_12_score'] : null;
    $target_year = $_POST['target_year'] !== '' ? (int)$_POST['target_year'] : 2026;
    $preferred_budget = $_POST['preferred_budget'] !== '' ? (float)$_POST['preferred_budget'] : null;

    if (empty($name) || empty($phone) || empty($email)) {
        $error = 'Full Name, Phone Number, and Email are required.';
    } else {
        try {
            // Generate UUID
            $leadId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));

            // Fetch state name if state_id is provided
            $stateName = null;
            if ($state_id) {
                $stmt = $pdo->prepare("SELECT name FROM states WHERE id = ?");
                $stmt->execute([$state_id]);
                $stateName = $stmt->fetchColumn() ?: null;
            }

            $source_page = trim($_POST['source_page'] ?? 'counselling');

            $stmt = $pdo->prepare("
                INSERT INTO leads (
                    id, name, phone, email, city, state, course_id, class_12_score, 
                    target_year, preferred_budget, lead_type, source_page, lead_status, 
                    priority, delivery_status
                ) VALUES (
                    :id, :name, :phone, :email, :city, :state, :course_id, :class_12_score, 
                    :target_year, :preferred_budget, 'inquiry', :source_page, 'new', 
                    'medium', 'pending'
                )
            ");

            $stmt->execute([
                'id' => $leadId,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'city' => $city,
                'state' => $stateName,
                'course_id' => $course_id ?: null,
                'class_12_score' => $class_12_score,
                'target_year' => $target_year,
                'preferred_budget' => $preferred_budget,
                'source_page' => $source_page
            ]);

            $success = true;
        } catch (Exception $e) {
            $error = 'An error occurred while saving your request: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Free Career & College Counselling 2026 | AdmissionSeason</title>
  <meta name="description" content="Register for free 1-on-1 expert admission counselling and get guidance on top colleges, courses, exams, fees, and placements.">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=8">
  
  <style>
    :root {
      --oxford-navy: #0B2447;
      --yale-blue: #19376D;
      --snow-pearl: #F8FAFC;
      --ink-black: #0F172A;
      --text-muted-alt: #475569;
      --accent-glow: rgba(25, 55, 109, 0.15);
      --glass-bg: rgba(255, 255, 255, 0.85);
      --glass-border: rgba(226, 232, 240, 0.8);
    }

    body {
      background-color: var(--snow-pearl);
      color: var(--ink-black);
      font-family: 'Inter', sans-serif;
    }

    .cns-section {
      padding: 60px 0 100px 0;
      position: relative;
      overflow: hidden;
    }

    /* Background decorative blobs */
    .cns-blob {
      position: absolute;
      width: 400px;
      height: 400px;
      border-radius: 50%;
      background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
      z-index: 1;
      filter: blur(40px);
      pointer-events: none;
    }
    .cns-blob-1 { top: -100px; right: -100px; }
    .cns-blob-2 { bottom: -100px; left: -100px; }

    .cns-container {
      position: relative;
      z-index: 2;
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 50px;
      align-items: start;
    }

    /* Left Info Column */
    .cns-info {
      padding-top: 20px;
    }

    .cns-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(25, 55, 109, 0.08);
      color: var(--yale-blue);
      padding: 6px 14px;
      border-radius: 50px;
      font-size: 0.85rem;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      margin-bottom: 24px;
      border: 1px solid rgba(25, 55, 109, 0.12);
    }

    .cns-badge i {
      font-size: 1rem;
    }

    .cns-title {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 2.75rem;
      font-weight: 800;
      color: var(--oxford-navy);
      line-height: 1.15;
      margin-bottom: 20px;
    }

    .cns-title span {
      background: linear-gradient(135deg, var(--oxford-navy), var(--yale-blue));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .cns-desc {
      font-size: 1.1rem;
      line-height: 1.6;
      color: var(--text-muted-alt);
      margin-bottom: 40px;
    }

    .benefit-item {
      display: flex;
      gap: 18px;
      margin-bottom: 28px;
      align-items: flex-start;
    }

    .benefit-icon {
      width: 48px;
      height: 48px;
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--yale-blue);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
      flex-shrink: 0;
      transition: all 0.3s ease;
    }

    .benefit-item:hover .benefit-icon {
      transform: translateY(-3px);
      background: var(--oxford-navy);
      color: #fff;
      border-color: var(--oxford-navy);
      box-shadow: 0 8px 20px rgba(11, 36, 71, 0.25);
    }

    .benefit-icon i {
      font-size: 1.4rem;
    }

    .benefit-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--oxford-navy);
      margin-bottom: 4px;
    }

    .benefit-text {
      font-size: 0.92rem;
      color: var(--text-muted-alt);
      line-height: 1.5;
    }

    /* Trust Stats Footer */
    .cns-stats {
      margin-top: 50px;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      border-top: 1px solid var(--glass-border);
      padding-top: 30px;
    }

    .stat-box h4 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--yale-blue);
      margin-bottom: 2px;
    }

    .stat-box p {
      font-size: 0.82rem;
      color: var(--text-muted-alt);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Right Glass Form Card */
    .cns-form-card {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      border-radius: 24px;
      padding: 40px;
      backdrop-filter: blur(15px);
      box-shadow: 0 20px 40px rgba(11, 36, 71, 0.05);
      position: relative;
    }

    .cns-form-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--oxford-navy), var(--yale-blue));
      border-radius: 24px 24px 0 0;
    }

    .form-header {
      margin-bottom: 30px;
    }

    .form-header h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--oxford-navy);
      margin-bottom: 8px;
    }

    .form-header p {
      font-size: 0.9rem;
      color: var(--text-muted-alt);
    }

    .form-alert {
      padding: 12px 18px;
      border-radius: 10px;
      font-size: 0.88rem;
      font-weight: 500;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-danger {
      background: #fef2f2;
      color: #991b1b;
      border: 1px solid #fecaca;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      font-size: 0.85rem;
      color: var(--oxford-navy);
      margin-bottom: 8px;
    }

    .form-control {
      width: 100%;
      padding: 11px 16px;
      border: 1px solid var(--glass-border);
      border-radius: 10px;
      background: #fff;
      font-size: 0.95rem;
      font-family: inherit;
      color: var(--ink-black);
      box-sizing: border-box;
      outline: none;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      border-color: var(--yale-blue);
      box-shadow: 0 0 0 4px rgba(25, 55, 109, 0.08);
      transform: translateY(-1px);
    }

    .form-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .submit-btn {
      width: 100%;
      background: var(--oxford-navy);
      color: #fff;
      border: none;
      padding: 14px;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 4px 12px rgba(11, 36, 71, 0.15);
      transition: all 0.3s ease;
      margin-top: 10px;
    }

    .submit-btn:hover {
      background: var(--yale-blue);
      box-shadow: 0 8px 24px rgba(25, 55, 109, 0.25);
      transform: translateY(-2px);
    }

    .submit-btn i {
      font-size: 1.15rem;
    }

    /* Success Card Styles */
    .success-card {
      text-align: center;
      padding: 30px 10px;
    }

    .success-icon-wrap {
      width: 80px;
      height: 80px;
      background: rgba(22, 101, 52, 0.08);
      border: 1px solid rgba(22, 101, 52, 0.15);
      color: #166534;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      margin-bottom: 24px;
      animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }

    .success-card h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--oxford-navy);
      margin-bottom: 12px;
    }

    .success-card p {
      font-size: 1rem;
      color: var(--text-muted-alt);
      line-height: 1.6;
      margin-bottom: 30px;
    }

    .back-home-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--oxford-navy);
      color: #fff;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 700;
      text-decoration: none;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: all 0.3s;
    }

    .back-home-btn:hover {
      background: var(--yale-blue);
      transform: translateY(-2px);
    }

    @keyframes scaleIn {
      from { transform: scale(0); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

    @media (max-width: 992px) {
      .cns-container {
        grid-template-columns: 1fr;
        gap: 40px;
      }
      .cns-info {
        text-align: center;
      }
      .benefit-item {
        text-align: left;
      }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Breadcrumbs Banner (Custom styled for Oxford Royal theme) -->
<div style="background: linear-gradient(135deg, var(--oxford-navy), var(--yale-blue)); padding: 24px 0; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.1);">
  <div class="container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
    <div style="display:flex; align-items:center; gap:8px; font-size:0.88rem; opacity:0.85;">
      <a href="index.php" style="color:#fff; text-decoration:none;">Home</a>
      <i class="ph ph-caret-right"></i>
      <span>Free Counselling</span>
    </div>
    <div style="font-weight:700; font-size:0.9rem; text-transform:uppercase; letter-spacing:0.5px; opacity:0.9;">
      Academic Session 2026-27
    </div>
  </div>
</div>

<section class="cns-section">
  <div class="cns-blob cns-blob-1"></div>
  <div class="cns-blob cns-blob-2"></div>
  
  <div class="container cns-container">
    <!-- Left Info Column -->
    <div class="cns-info">
      <div class="cns-badge"><i class="ph-fill ph-shield-check"></i> 100% Free Expert Guidance</div>
      
      <h2 class="cns-title">Get Direct Admission Support From <span>Industry Experts</span></h2>
      <p class="cns-desc">Confused about which college or course to choose? Fill out the inquiry form and our experienced educational consultants will guide you through the entire admission process, absolutely free of cost.</p>
      
      <div class="benefit-item">
        <div class="benefit-icon"><i class="ph-bold ph-chats-teardrop"></i></div>
        <div>
          <h4 class="benefit-title">1-on-1 Personalized Mentorship</h4>
          <p class="benefit-text">Get connected with a dedicated expert who understands your academic background, career goals, and preferred budget.</p>
        </div>
      </div>
      
      <div class="benefit-item">
        <div class="benefit-icon"><i class="ph-bold ph-stamp"></i></div>
        <div>
          <h4 class="benefit-title">College Selection & Verification</h4>
          <p class="benefit-text">Receive curated lists of verified colleges matching your eligibility criteria, cutoff scores, and placement goals.</p>
        </div>
      </div>
      
      <div class="benefit-item">
        <div class="benefit-icon"><i class="ph-bold ph-hand-coins"></i></div>
        <div>
          <h4 class="benefit-title">Scholarships & Education Loans</h4>
          <p class="benefit-text">Find colleges offering institutional scholarships and get step-by-step guidance on processing education loans.</p>
        </div>
      </div>
      
      <div class="cns-stats">
        <div class="stat-box">
          <h4>50,000+</h4>
          <p>Guided Students</p>
        </div>
        <div class="stat-box">
          <h4>250+</h4>
          <p>Expert Advisors</p>
        </div>
        <div class="stat-box">
          <h4>1000+</h4>
          <p>Partner Colleges</p>
        </div>
      </div>
    </div>
    
    <!-- Right Form Card -->
    <div>
      <div class="cns-form-card">
        <?php if ($success): ?>
          <div class="success-card">
            <div class="success-icon-wrap">
              <i class="ph-fill ph-check-circle"></i>
            </div>
            <h3>Request Submitted!</h3>
            <p>Thank you for choosing AdmissionSeason. One of our senior admission advisors will call you shortly to assist you with your queries.</p>
            <a href="index.php" class="back-home-btn"><i class="ph ph-house"></i> Return to Homepage</a>
          </div>
        <?php else: ?>
          <div class="form-header">
            <h3>Request Counselling</h3>
            <p>Please enter your correct details to register with our counsellor.</p>
          </div>
          
          <?php if (isset($_GET['prefill_career'])): ?>
            <div style="background: rgba(25, 55, 109, 0.05); border: 1px solid rgba(25, 55, 109, 0.15); border-radius: 12px; padding: 14px; margin-bottom: 24px; font-size: 0.88rem; color: var(--yale-blue); font-weight: 700; display: flex; align-items: center; gap: 10px;">
              <i class="ph-fill ph-info" style="font-size: 1.2rem; color: var(--yale-blue);"></i>
              <span>Prefilled Interest: <?= htmlspecialchars($_GET['prefill_career']) ?> Career guidance</span>
            </div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="form-alert alert-danger">
              <i class="ph ph-warning-circle"></i>
              <span><?= htmlspecialchars($error) ?></span>
            </div>
          <?php endif; ?>
          
          <form method="POST">
            <input type="hidden" name="source_page" value="<?= htmlspecialchars($_POST['source_page'] ?? ($_GET['prefill_career'] ? 'counselling - ' . $_GET['prefill_career'] : 'counselling')) ?>">
            <div class="form-group">
              <label>Full Name *</label>
              <input type="text" name="name" class="form-control" placeholder="Enter your full name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            
            <div class="form-grid-2">
              <div class="form-group">
                <label>Mobile Number *</label>
                <input type="tel" name="phone" class="form-control" placeholder="10-digit mobile number" required pattern="[0-9]{10,15}" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" class="form-control" placeholder="name@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
              </div>
            </div>
            
            <div class="form-grid-2">
              <div class="form-group">
                <label>Current City</label>
                <input type="text" name="city" class="form-control" placeholder="e.g. Mumbai" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>State</label>
                <select name="state_id" class="form-control">
                  <option value="">-- Select State --</option>
                  <?php foreach ($states as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= (isset($_POST['state_id']) && (int)$_POST['state_id'] === $st['id']) ? 'selected' : '' ?>><?= htmlspecialchars($st['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            
            <div class="form-group">
              <label>Preferred Course Interest</label>
              <select name="course_id" class="form-control">
                <option value="">-- Select Course --</option>
                <?php foreach ($courses as $cr): ?>
                  <option value="<?= htmlspecialchars((string)$cr['id']) ?>" <?= (isset($_POST['course_id']) && $_POST['course_id'] === $cr['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cr['course_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-grid-2">
              <div class="form-group">
                <label>Class 12 Score (%)</label>
                <input type="number" step="0.01" name="class_12_score" class="form-control" placeholder="e.g. 84.5" min="0" max="100" value="<?= htmlspecialchars($_POST['class_12_score'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>Target Intake Year</label>
                <select name="target_year" class="form-control">
                  <option value="2026" <?= (isset($_POST['target_year']) && (int)$_POST['target_year'] === 2026) ? 'selected' : '' ?>>2026</option>
                  <option value="2027" <?= (isset($_POST['target_year']) && (int)$_POST['target_year'] === 2027) ? 'selected' : '' ?>>2027</option>
                </select>
              </div>
            </div>
            
            <div class="form-group">
              <label>Preferred Budget Range (Annual Fee in ₹)</label>
              <input type="number" step="0.01" name="preferred_budget" class="form-control" placeholder="e.g. 150000" min="0" value="<?= htmlspecialchars($_POST['preferred_budget'] ?? '') ?>">
            </div>
            
            <button type="submit" class="submit-btn">
              <span>Connect with an Expert</span>
              <i class="ph ph-arrow-right"></i>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
