<?php
require_once __DIR__ . '/admin/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, full_name, email, phone FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$stmtProfile = $pdo->prepare("SELECT city, preferred_courses FROM student_profiles WHERE user_id = ?");
$stmtProfile->execute([$userId]);
$profile = $stmtProfile->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/favicon.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile | AdmissionSeason</title>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #F8FAFC;
      color: #0f172a;
      min-height: 100vh;
    }
    .profile-container {
      max-width: 720px;
      margin: 60px auto;
      padding: 0 24px;
    }
    .profile-card {
      background: #fff;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.04);
    }
    .profile-header {
      display: flex;
      align-items: center;
      gap: 20px;
      padding-bottom: 28px;
      border-bottom: 1px solid rgba(15,23,42,0.08);
      margin-bottom: 28px;
    }
    .profile-avatar {
      width: 64px;
      height: 64px;
      border-radius: 16px;
      background: linear-gradient(135deg, #0b2447, #19376D);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      font-weight: 800;
      font-family: 'Space Grotesk', sans-serif;
    }
    .profile-header h1 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.6rem;
      color: #0b2447;
    }
    .profile-header p {
      color: rgba(15,23,42,0.45);
      font-size: 0.92rem;
    }
    .profile-field {
      display: flex;
      justify-content: space-between;
      padding: 14px 0;
      border-bottom: 1px solid #F8FAFC;
      font-size: 0.95rem;
    }
    .profile-field:last-child { border-bottom: none; }
    .profile-field .label { color: rgba(15,23,42,0.45); font-weight: 600; }
    .profile-field .value { color: #0f172a; font-weight: 700; }
    .logout-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 24px;
      padding: 12px 28px;
      background: #0F172A;
      color: #fff;
      border: none;
      border-radius: 12px;
      font-weight: 700;
      font-size: 0.92rem;
      cursor: pointer;
      font-family: inherit;
      transition: all 0.3s ease;
    }
    .logout-btn:hover {
      background: #0F172A;
      transform: translateY(-2px);
    }
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #19376D;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.9rem;
      margin-bottom: 24px;
    }
    .back-link:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="profile-container">
    <a href="index.php" class="back-link"><i class="ph ph-arrow-left"></i> Back to Home</a>
    <div class="profile-card">
      <div class="profile-header">
        <div class="profile-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <div>
          <h1><?= htmlspecialchars($user['full_name']) ?></h1>
          <p>Member since 2026</p>
        </div>
      </div>
      <div class="profile-field">
        <span class="label">Email</span>
        <span class="value"><?= htmlspecialchars($user['email'] ?? '—') ?></span>
      </div>
      <div class="profile-field">
        <span class="label">Phone</span>
        <span class="value"><?= htmlspecialchars($user['phone'] ?? '—') ?></span>
      </div>
      <div class="profile-field">
        <span class="label">City</span>
        <span class="value"><?= htmlspecialchars($profile['city'] ?? '—') ?></span>
      </div>
      <div class="profile-field">
        <span class="label">Preferred Courses</span>
        <span class="value"><?= htmlspecialchars(implode(', ', json_decode($profile['preferred_courses'] ?? '[]', true) ?: [])) ?: '—' ?></span>
      </div>
      <form method="POST" action="logout.php">
        <button type="submit" class="logout-btn"><i class="ph ph-sign-out"></i> Logout</button>
      </form>
    </div>
  </div>
</body>
</html>
