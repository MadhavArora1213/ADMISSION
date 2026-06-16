<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/admin/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Rate Limiting
if (!isset($_SESSION['rate_limit'])) {
    $_SESSION['rate_limit'] = [
        'attempts' => 0,
        'first_attempt' => time()
    ];
}
if (time() - $_SESSION['rate_limit']['first_attempt'] > 60) {
    $_SESSION['rate_limit']['attempts'] = 0;
    $_SESSION['rate_limit']['first_attempt'] = time();
}

// 2. Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Supported Country Codes with Phone Lengths
$country_data = [
    '91'  => ['min' => 10, 'max' => 10, 'name' => 'India (+91)'],
    '1'   => ['min' => 10, 'max' => 10, 'name' => 'USA/Canada (+1)'],
    '44'  => ['min' => 10, 'max' => 10, 'name' => 'UK (+44)'],
    '61'  => ['min' => 9,  'max' => 9,  'name' => 'Australia (+61)'],
    '971' => ['min' => 9,  'max' => 9,  'name' => 'UAE (+971)'],
    '977' => ['min' => 10, 'max' => 10, 'name' => 'Nepal (+977)'],
];

$error = '';
$success_message = '';
$show_otp_verify = false;
$otp_sent_to = '';
$action_type = 'login'; // default active tab for login.php

// Fetch active courses
try {
    $courses = $pdo->query("SELECT id, course_name FROM courses ORDER BY course_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $courses = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['rate_limit']['attempts']++;
    if ($_SESSION['rate_limit']['attempts'] > 15) {
        $error = 'Too many requests. Please wait a minute.';
    } else {
        $post_token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'], $post_token)) {
            $error = 'Invalid security token. Please refresh.';
        } else {
            $action = $_POST['action'] ?? '';

            if ($action === 'request_otp') {
                $form_mode = $_POST['form_mode'] ?? 'login';
                
                if ($form_mode === 'signup') {
                    $action_type = 'signup';
                    $name = trim($_POST['name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $country_code = trim($_POST['country_code'] ?? '91');
                    $phone = trim($_POST['phone'] ?? '');
                    $city = trim($_POST['city'] ?? '');
                    $course_id = trim($_POST['course'] ?? '');

                    $name_regex = "/^[a-zA-Z\s]{2,100}$/";
                    $city_regex = "/^[a-zA-Z\s\.\-']{2,100}$/";

                    $isValidPhone = false;
                    if (array_key_exists($country_code, $country_data)) {
                        $limits = $country_data[$country_code];
                        $phone_len = strlen($phone);
                        if ($phone_len >= $limits['min'] && $phone_len <= $limits['max'] && ctype_digit($phone)) {
                            $isValidPhone = true;
                        }
                    }

                    if (!preg_match($name_regex, $name)) {
                        $error = 'Name must be letters and spaces only (2-100 chars).';
                    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $error = 'Please enter a valid email address.';
                    } elseif (!$isValidPhone) {
                        $limits = $country_data[$country_code] ?? ['min' => 10, 'max' => 10];
                        $error = 'Please enter a valid phone number containing exactly ' . $limits['min'] . ' digits.';
                    } elseif (!preg_match($city_regex, $city)) {
                        $error = 'Please enter a valid city name.';
                    } elseif (empty($course_id)) {
                        $error = 'Please select your preferred course.';
                    } else {
                        $full_phone = '+' . $country_code . $phone;
                        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
                        $stmtCheck->execute([$email, $full_phone]);
                        if ($stmtCheck->fetch()) {
                            $error = 'An account with this email or phone number already exists.';
                        } else {
                            $_SESSION['temp_auth'] = [
                                'mode' => 'signup',
                                'name' => $name,
                                'email' => $email,
                                'phone' => $full_phone,
                                'city' => $city,
                                'course_id' => $course_id
                            ];
                            
                            $_SESSION['temp_otp'] = '123456';
                            $_SESSION['otp_expiry'] = time() + 300;
                            
                            $show_otp_verify = true;
                            $otp_sent_to = htmlspecialchars($full_phone);
                            $success_message = 'Mock OTP Sent: 123456';
                        }
                    }
                } else {
                    $action_type = 'login';
                    $country_code = trim($_POST['country_code_login'] ?? '91');
                    $phone = trim($_POST['phone_login'] ?? '');

                    $isValidPhone = false;
                    if (array_key_exists($country_code, $country_data)) {
                        $limits = $country_data[$country_code];
                        $phone_len = strlen($phone);
                        if ($phone_len >= $limits['min'] && $phone_len <= $limits['max'] && ctype_digit($phone)) {
                            $isValidPhone = true;
                        }
                    }

                    if (!$isValidPhone) {
                        $limits = $country_data[$country_code] ?? ['min' => 10, 'max' => 10];
                        $error = 'Please enter a valid phone number containing exactly ' . $limits['min'] . ' digits.';
                    } else {
                        $full_phone = '+' . $country_code . $phone;
                        $stmtUser = $pdo->prepare("SELECT id, full_name, phone FROM users WHERE phone = ? AND status = 'active' LIMIT 1");
                        $stmtUser->execute([$full_phone]);
                        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

                        if (!$user) {
                            $error = 'No registered user found with this mobile number. Please sign up first.';
                        } else {
                            $_SESSION['temp_auth'] = [
                                'mode' => 'login',
                                'id' => $user['id'],
                                'name' => $user['full_name'],
                                'phone' => $user['phone']
                            ];
                            
                            $_SESSION['temp_otp'] = '123456';
                            $_SESSION['otp_expiry'] = time() + 300;
                            
                            $show_otp_verify = true;
                            $otp_sent_to = htmlspecialchars($full_phone);
                            $success_message = 'Mock OTP Sent: 123456';
                        }
                    }
                }
            } elseif ($action === 'verify_otp') {
                $entered_otp = trim($_POST['otp'] ?? '');
                
                if (empty($_SESSION['temp_auth'])) {
                    $error = 'Session expired. Please request a new OTP.';
                } elseif (time() > ($_SESSION['otp_expiry'] ?? 0)) {
                    $error = 'OTP has expired. Please request a new one.';
                    $show_otp_verify = false;
                } elseif ($entered_otp !== ($_SESSION['temp_otp'] ?? '')) {
                    $error = 'Incorrect OTP entered.';
                    $show_otp_verify = true;
                    $otp_sent_to = htmlspecialchars($_SESSION['temp_auth']['phone'] ?? '');
                } else {
                    $temp = $_SESSION['temp_auth'];
                    
                    if ($temp['mode'] === 'signup') {
                        try {
                            $pdo->beginTransaction();
                            
                            $user_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                                mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
                                mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
                                mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
                                
                            $profile_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                                mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
                                mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
                                mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
                                
                            $stmtInsertUser = $pdo->prepare("
                                INSERT INTO users (id, full_name, email, phone, password_hash, auth_provider, status, phone_verified, email_verified)
                                VALUES (?, ?, ?, ?, ?, 'phone_otp', 'active', TRUE, TRUE)
                            ");
                            $dummy_pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
                            $stmtInsertUser->execute([
                                $user_id,
                                $temp['name'],
                                $temp['email'],
                                $temp['phone'],
                                $dummy_pass
                            ]);
                            
                            $stmtInsertProfile = $pdo->prepare("
                                INSERT INTO student_profiles (id, user_id, city, preferred_courses)
                                VALUES (?, ?, ?, ?)
                            ");
                            $preferred_courses_json = json_encode([$temp['course_id']]);
                            $stmtInsertProfile->execute([
                                $profile_id,
                                $user_id,
                                $temp['city'],
                                $preferred_courses_json
                            ]);
                            
                            $pdo->commit();
                            
                            unset($_SESSION['temp_auth']);
                            unset($_SESSION['temp_otp']);
                            unset($_SESSION['otp_expiry']);
                            
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['user_name'] = $temp['name'];
                            
                            header('Location: index.php');
                            exit;
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $error = 'An error occurred. Code: ' . $e->getMessage();
                        }
                    } else {
                        // Login Success
                        unset($_SESSION['temp_auth']);
                        unset($_SESSION['temp_otp']);
                        unset($_SESSION['otp_expiry']);
                        
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $temp['id'];
                        $_SESSION['user_name'] = $temp['name'];
                        
                        $stmtUpdate = $pdo->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ?, login_count = login_count + 1 WHERE id = ?");
                        $stmtUpdate->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $temp['id']]);
                        
                        header('Location: index.php');
                        exit;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login & Register | AdmissionSeason</title>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --oxford-navy: #0B2447;
      --yale-blue: #19376D;
      --snow-pearl: #F8FAFC;
      --ink-black: #0F172A;
      --border-glass: rgba(255, 255, 255, 0.55);
      --border-glass-dark: rgba(11, 36, 71, 0.05);
      --white: #FFFFFF;
      --glass-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.35) 0%, rgba(255, 255, 255, 0.15) 100%);
      --card-shadow: 0 30px 70px rgba(11, 36, 71, 0.08), 0 8px 32px rgba(11, 36, 71, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.5);
      --glow-blue: rgba(25, 55, 109, 0.12);
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      background-color: #EBF1FA;
      background-image: 
        radial-gradient(at 0% 0%, rgba(219, 234, 254, 0.6) 0px, transparent 45%),
        radial-gradient(at 100% 100%, rgba(248, 250, 252, 0.8) 0px, transparent 50%),
        radial-gradient(at 100% 0%, rgba(195, 218, 254, 0.4) 0px, transparent 40%),
        radial-gradient(at 0% 100%, rgba(225, 237, 255, 0.5) 0px, transparent 50%);
      color: var(--ink-black);
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      position: relative;
      overflow-x: hidden;
    }
    
    /* Glowing ambient background blur circles */
    .glowing-bg-blobs {
      position: absolute;
      inset: 0;
      z-index: 0;
      pointer-events: none;
      overflow: hidden;
    }
    
    .blob {
      position: absolute;
      border-radius: 50%;
      filter: blur(150px);
      opacity: 0.18;
      animation: floatBlob 24s ease-in-out infinite;
    }
    
    .blob-1 {
      top: -12%;
      left: 8%;
      width: 580px;
      height: 580px;
      background: var(--oxford-navy);
    }
    
    .blob-2 {
      bottom: -12%;
      right: 8%;
      width: 680px;
      height: 680px;
      background: var(--yale-blue);
      animation-delay: -6s;
    }
    
    @keyframes floatBlob {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(60px, -60px) scale(1.12); }
    }
    
    /* Main Glass Card Wrapper */
    .main-wrapper {
      width: 100%;
      max-width: 1080px;
      background: var(--glass-bg);
      backdrop-filter: blur(40px) saturate(180%);
      -webkit-backdrop-filter: blur(40px) saturate(180%);
      border: 1px solid var(--border-glass);
      border-radius: 28px;
      display: grid;
      grid-template-columns: 1fr 1.25fr;
      overflow: hidden;
      box-shadow: var(--card-shadow);
      position: relative;
      z-index: 1;
      transition: all 0.3s ease;
    }
    
    @media(max-width: 991px) {
      .main-wrapper {
        grid-template-columns: 1fr;
        max-width: 520px;
      }
      .visual-section { display: none; }
    }
    
    /* Left Visual Info Section - Minimalist Glass */
    .visual-section {
      background: rgba(255, 255, 255, 0.18);
      padding: 60px 48px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      border-right: 1px solid var(--border-glass);
      position: relative;
    }
    
    .visual-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--oxford-navy);
      text-decoration: none;
      transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .visual-logo:hover {
      transform: translateY(-2px) scale(1.02);
    }
    .visual-logo i {
      color: var(--oxford-navy);
      font-size: 2rem;
    }
    
    .visual-content {
      margin: 30px 0;
    }
    
    .visual-content h2 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.95rem;
      font-weight: 800;
      color: var(--oxford-navy);
      line-height: 1.25;
      margin-bottom: 14px;
      letter-spacing: -0.02em;
    }
    
    .visual-content p {
      color: var(--ink-black);
      opacity: 0.75;
      font-size: 0.95rem;
      margin-bottom: 28px;
      font-weight: 600;
    }
    
    .benefit-list {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    
    .benefit-item {
      display: flex;
      align-items: center;
      gap: 14px;
      font-size: 0.92rem;
      font-weight: 600;
      color: var(--yale-blue);
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      padding: 6px;
      border-radius: 8px;
    }
    .benefit-item:hover {
      transform: translateX(8px);
      background: rgba(255, 255, 255, 0.25);
    }
    
    .benefit-item i {
      font-size: 1rem;
      color: #FFFFFF;
      background: var(--oxford-navy);
      padding: 6px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 10px rgba(11, 36, 71, 0.12);
    }
    
    /* Interactive vector graph illustration */
    .vector-illustration {
      position: relative;
      height: 130px;
      width: 100%;
      margin-top: 30px;
      overflow: visible;
    }
    
    .vector-illustration .node-blob {
      position: absolute;
      border-radius: 50%;
      filter: blur(16px);
      opacity: 0.35;
      animation: pulseBlob 4s ease-in-out infinite alternate;
    }
    
    .nb-orange { width: 50px; height: 50px; background: #FF9F43; bottom: 10px; left: 15px; }
    .nb-yellow { width: 60px; height: 60px; background: #FFD200; top: 15px; right: 35px; animation-delay: -1.5s; }
    .nb-blue { width: 45px; height: 45px; background: #54A0FF; top: 10px; left: 100px; animation-delay: -3s; }
    
    @keyframes pulseBlob {
      0% { transform: scale(0.9); opacity: 0.25; }
      100% { transform: scale(1.1); opacity: 0.45; }
    }
    
    .vector-illustration .connections {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
    }
    
    /* Animated flow lines */
    .vector-illustration .connections line {
      stroke-dasharray: 6;
      stroke-dashoffset: 60;
      animation: dashFlow 8s linear infinite;
    }
    
    @keyframes dashFlow {
      to {
        stroke-dashoffset: 0;
      }
    }
    
    .vector-illustration .node {
      position: absolute;
      width: 44px;
      height: 44px;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(10px);
      border: 1px solid var(--border-glass);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 20px rgba(11, 36, 71, 0.08);
      color: var(--oxford-navy);
      font-size: 1.15rem;
      z-index: 2;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      cursor: pointer;
    }
    
    .vector-illustration .node:hover {
      transform: scale(1.18) translateY(-5px);
      box-shadow: 0 16px 32px rgba(11, 36, 71, 0.16);
      border-color: var(--oxford-navy);
      background: var(--white);
      color: var(--oxford-navy);
    }
    
    .node-1 { bottom: 10px; left: 35px; }
    .node-2 { top: 10px; left: 125px; }
    .node-3 { bottom: 10px; right: 55px; }
    
    /* Right Forms Panel - Glass minimalism */
    .form-section {
      padding: 55px 45px;
      background: rgba(255, 255, 255, 0.25);
      display: flex;
      flex-direction: column;
      position: relative;
      justify-content: center;
    }
    
    @media(max-width: 480px) {
      .form-section { padding: 35px 20px; }
    }
    
    .tabs-header-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 30px;
      border-bottom: 1px solid var(--border-glass-dark);
      padding-bottom: 2px;
    }
    
    .tabs-flex {
      display: flex;
      gap: 24px;
    }
    
    .tab-btn {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--ink-black);
      opacity: 0.45;
      background: none;
      border: none;
      padding-bottom: 12px;
      cursor: pointer;
      position: relative;
      font-family: 'Space Grotesk', sans-serif;
      transition: all 0.25s ease;
    }
    
    .tab-btn:hover {
      opacity: 0.8;
    }
    
    .tab-btn.active {
      opacity: 1;
      color: var(--oxford-navy);
    }
    
    .tab-btn.active::after {
      content: '';
      position: absolute;
      bottom: -1px;
      left: 0;
      width: 100%;
      height: 3px;
      background-color: var(--oxford-navy);
      border-radius: 3px;
      animation: slideIn 0.25s ease;
    }
    
    @keyframes slideIn {
      from { transform: scaleX(0); }
      to { transform: scaleX(1); }
    }
    
    .google-btn {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 18px;
      border: 1px solid var(--border-glass);
      border-radius: 100px;
      background: rgba(255, 255, 255, 0.65);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      color: var(--ink-black);
      font-size: 0.85rem;
      font-weight: 700;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 2px 8px rgba(11, 36, 71, 0.03);
    }
    
    .google-btn:hover {
      background: rgba(255, 255, 255, 0.95);
      border-color: rgba(11, 36, 71, 0.15);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(11, 36, 71, 0.08);
    }
    
    .google-btn img {
      width: 18px;
      height: 18px;
    }
    
    .form-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    
    @media(max-width: 580px) {
      .form-grid-2 { grid-template-columns: 1fr; gap: 0; }
    }
    
    .form-group-custom {
      margin-bottom: 20px;
      position: relative;
    }
    
    .form-group-custom.full-width {
      grid-column: span 2;
    }
    
    @media(max-width: 580px) {
      .form-group-custom.full-width { grid-column: span 1; }
    }
    
    /* Frosted minimalist input style */
    .input-card {
      width: 100%;
      height: 50px;
      background: rgba(255, 255, 255, 0.55);
      backdrop-filter: blur(5px);
      -webkit-backdrop-filter: blur(5px);
      border: 1px solid rgba(11, 36, 71, 0.08);
      border-radius: 12px;
      padding: 0 16px;
      font-family: inherit;
      font-size: 0.92rem;
      color: var(--ink-black);
      font-weight: 600;
      outline: none;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .input-card::placeholder {
      color: var(--ink-black);
      opacity: 0.35;
      font-weight: 500;
    }
    
    .input-card:focus {
      border-color: var(--oxford-navy);
      background: rgba(255, 255, 255, 0.9);
      box-shadow: 0 0 0 4px var(--glow-blue);
      transform: translateY(-1px);
    }
    
    .phone-row-custom {
      display: flex;
      gap: 10px;
      width: 100%;
    }
    
    .cc-selector {
      width: 82px;
      flex-shrink: 0;
      cursor: pointer;
      padding-right: 24px;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%230B2447' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 8px center;
      background-size: 14px;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
    }
    
    select.input-card {
      padding-right: 28px;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%230B2447' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      background-size: 15px;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
    }
    
    .terms-row {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin-bottom: 24px;
      padding-left: 2px;
    }
    
    .terms-row input[type="checkbox"] {
      width: 17px;
      height: 17px;
      margin-top: 2px;
      accent-color: var(--oxford-navy);
      cursor: pointer;
    }
    
    .terms-row label {
      font-size: 0.8rem;
      color: var(--ink-black);
      opacity: 0.75;
      line-height: 1.4;
      font-weight: 600;
    }
    
    .terms-row label a {
      color: var(--yale-blue);
      text-decoration: none;
      font-weight: 700;
    }
    
    .terms-row label a:hover {
      text-decoration: underline;
    }
    
    .btn-get-otp {
      width: 100%;
      max-width: 280px;
      margin: 8px auto 0 auto;
      background: var(--oxford-navy);
      color: #FFFFFF;
      height: 50px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 0.95rem;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 15px rgba(11, 36, 71, 0.15);
    }
    
    .btn-get-otp:hover {
      background: var(--yale-blue);
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(11, 36, 71, 0.25);
    }
    
    .btn-get-otp:active {
      transform: translateY(0) scale(0.98);
    }
    
    .btn-submit {
      width: 100%;
      background: var(--oxford-navy);
      color: #FFFFFF;
      height: 50px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 0.95rem;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 15px rgba(11, 36, 71, 0.15);
    }
    
    .btn-submit:hover {
      background: var(--yale-blue);
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(11, 36, 71, 0.25);
    }
    
    .btn-submit:active {
      transform: translateY(0) scale(0.98);
    }
    
    .switch-footer-text {
      text-align: center;
      margin-top: 28px;
      font-size: 0.88rem;
      color: var(--ink-black);
      opacity: 0.75;
      font-weight: 600;
    }
    
    .switch-footer-text span {
      cursor: pointer;
      color: var(--oxford-navy);
      font-weight: 700;
      transition: color 0.2s;
    }
    
    .switch-footer-text span:hover {
      color: var(--yale-blue);
      text-decoration: underline;
    }
    
    .alert-box {
      padding: 12px 16px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-size: 0.88rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .alert-danger {
      background: rgba(244, 63, 94, 0.08);
      color: #f43f5e;
      border: 1px solid rgba(244, 63, 94, 0.15);
    }
    
    .alert-success {
      background: rgba(16, 185, 129, 0.08);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.15);
    }
    
    .subtitle {
      font-size: 0.92rem;
      color: var(--ink-black);
      opacity: 0.7;
      margin-bottom: 24px;
      line-height: 1.5;
    }
  </style>
</head>
<body>

<!-- Glowing blur blobs in the background for glassmorphism depth -->
<div class="glowing-bg-blobs">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
</div>

<div class="main-wrapper">
  
  <!-- Left Column - Feature list identical to layout style -->
  <div class="visual-section">
    <a href="index.php" class="visual-logo">
      <i class="ph-fill ph-student"></i>
      <span>AdmissionSeason</span>
    </a>
    
    <div class="visual-content">
      <h2>Unlock Your Future with AdmissionSeason</h2>
      <p>Register now and get exclusive access to:</p>
      
      <div class="benefit-list">
        <div class="benefit-item"><i class="ph ph-check"></i> Exam And Admission Alerts</div>
        <div class="benefit-item"><i class="ph ph-check"></i> Predictor & Cutoff Updates</div>
        <div class="benefit-item"><i class="ph ph-check"></i> 100+ College & Course Guides</div>
        <div class="benefit-item"><i class="ph ph-check"></i> Real Reviews & Rankings</div>
        <div class="benefit-item"><i class="ph ph-check"></i> 1 On 1 Counselling From Experts</div>
      </div>
    </div>
    
    <!-- Modern Interactive HTML/CSS Vector Illustration -->
    <div class="vector-illustration">
      <div class="node-blob nb-orange"></div>
      <div class="node-blob nb-yellow"></div>
      <div class="node-blob nb-blue"></div>
      
      <svg class="connections" viewBox="0 0 300 140">
        <line x1="50" y1="100" x2="140" y2="35" stroke="rgba(11, 36, 71, 0.08)" stroke-width="1.5" stroke-dasharray="4"/>
        <line x1="140" y1="35" x2="230" y2="100" stroke="rgba(11, 36, 71, 0.08)" stroke-width="1.5" stroke-dasharray="4"/>
        <line x1="50" y1="100" x2="230" y2="100" stroke="rgba(11, 36, 71, 0.08)" stroke-width="1.5" stroke-dasharray="4"/>
      </svg>
      
      <div class="node node-1" title="Colleges Discovery"><i class="ph ph-buildings"></i></div>
      <div class="node node-2" title="Expert Counselling"><i class="ph ph-chats-teardrop"></i></div>
      <div class="node node-3" title="Exams Updates"><i class="ph ph-file-text"></i></div>
    </div>
    
    <div style="opacity: 0.4; font-size: 0.8rem; font-weight: 600;">© 2026 AdmissionSeason</div>
  </div>
  
  <!-- Right Column - Interacting Tab Forms -->
  <div class="form-section">
    
    <?php if (!$show_otp_verify): ?>
      <div class="tabs-header-row">
        <div class="tabs-flex">
          <button class="tab-btn <?= ($action_type === 'signup') ? 'active' : '' ?>" onclick="switchFormMode('signup')">Sign Up</button>
          <button class="tab-btn <?= ($action_type === 'login') ? 'active' : '' ?>" onclick="switchFormMode('login')">Login</button>
        </div>
        
        <!-- Continue with Google button -->
        <a href="#" class="google-btn">
          <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="G">
          <span>Continue with Google</span>
        </a>
      </div>
      
      <?php if ($error): ?>
        <div class="alert-box alert-danger"><i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <!-- Unified POST Form -->
      <form method="POST" action="" id="auth_form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="request_otp">
        <!-- Hidden input to track form active mode -->
        <input type="hidden" name="form_mode" id="form_mode" value="<?= htmlspecialchars($action_type) ?>">
        
        <!-- SIGN UP CARD FIELDS -->
        <div id="signup_fields_group" style="display: <?= ($action_type === 'signup') ? 'block' : 'none' ?>;">
          <div class="form-grid-2">
            <div class="form-group-custom">
              <input type="text" name="name" class="input-card" placeholder="Full Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" pattern="^[a-zA-Z\s]{2,100}$">
            </div>
            
            <div class="form-group-custom">
              <input type="email" name="email" class="input-card" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            
            <div class="form-group-custom">
              <div class="phone-row-custom">
                <select name="country_code" class="input-card cc-selector" id="country_code" onchange="updateDynamicPhoneConstraint('phone', 'country_code')">
                  <?php foreach ($country_data as $code => $data): ?>
                    <option value="<?= htmlspecialchars((string)$code) ?>" data-min="<?= $data['min'] ?>" data-max="<?= $data['max'] ?>">+<?= htmlspecialchars((string)$code) ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="tel" name="phone" id="phone" class="input-card" placeholder="Mobile Number" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
              </div>
            </div>
            
            <div class="form-group-custom">
              <select name="course" class="input-card">
                <option value="" disabled selected>Studying In</option>
                <?php foreach ($courses as $c): ?>
                  <option value="<?= htmlspecialchars($c['id']) ?>" <?= (($_POST['course'] ?? '') === $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['course_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group-custom full-width">
              <input type="text" name="city" class="input-card" placeholder="City You Live In" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" pattern="^[a-zA-Z\s\.\-']{2,100}$">
            </div>
          </div>
          
          <div class="terms-row">
            <input type="checkbox" id="agree_terms" required checked>
            <label for="agree_terms">I agree to AdmissionSeason's <a href="#">Privacy Policy</a> and <a href="#">Terms & Conditions</a> and provide consent to be contacted for updates.</label>
          </div>
        </div>
        
        <!-- LOGIN CARD FIELDS -->
        <div id="login_fields_group" style="display: <?= ($action_type === 'login') ? 'block' : 'none' ?>;">
          <div class="form-group-custom">
            <div class="phone-row-custom">
              <select name="country_code_login" class="input-card cc-selector" id="country_code_login" onchange="updateDynamicPhoneConstraint('phone_login', 'country_code_login')">
                <?php foreach ($country_data as $code => $data): ?>
                  <option value="<?= htmlspecialchars((string)$code) ?>" data-min="<?= $data['min'] ?>" data-max="<?= $data['max'] ?>">+<?= htmlspecialchars((string)$code) ?></option>
                <?php endforeach; ?>
              </select>
              <input type="tel" name="phone_login" id="phone_login" class="input-card" placeholder="Mobile Number" value="<?= htmlspecialchars($_POST['phone_login'] ?? '') ?>">
            </div>
          </div>
        </div>
        
        <button type="submit" class="btn-get-otp">Get OTP <i class="ph ph-arrow-right"></i></button>
        
        <!-- Bottom switch links -->
        <div class="switch-footer-text" id="signup_footer_switch" style="display: <?= ($action_type === 'signup') ? 'block' : 'none' ?>;">
          Already have an AdmissionSeason account? <span onclick="switchFormMode('login')">Login to continue</span>
        </div>
        <div class="switch-footer-text" id="login_footer_switch" style="display: <?= ($action_type === 'login') ? 'block' : 'none' ?>;">
          Don't have an AdmissionSeason account? <span onclick="switchFormMode('signup')">Sign Up to continue</span>
        </div>
      </form>
      
    <?php else: ?>
      <!-- OTP VERIFICATION VIEW -->
      <h1 style="font-family: 'Space Grotesk', sans-serif; margin-bottom: 8px;">Verify Mobile Number</h1>
      <p class="subtitle">A 6-digit OTP code has been sent to your phone (<strong><?= $otp_sent_to ?></strong>).</p>
      
      <?php if ($success_message): ?>
        <div class="alert-box alert-success"><i class="ph ph-check-circle"></i> <?= htmlspecialchars($success_message) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert-box alert-danger"><i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="verify_otp">
        
        <div class="form-group-custom" style="max-width: 320px; margin: 0 auto 24px auto;">
          <input type="text" name="otp" class="input-card" placeholder="Enter OTP (e.g. 123456)" required pattern="^[0-9]{6}$" maxlength="6" autofocus style="letter-spacing: 6px; font-weight: 800; text-align: center; font-size: 1.1rem; background: var(--snow-pearl); border: 1.5px solid var(--border-light);">
        </div>
        
        <button type="submit" class="btn-submit" style="max-width: 260px; margin: 0 auto;">Verify OTP <i class="ph ph-arrow-right"></i></button>
      </form>
    <?php endif; ?>
    
  </div>
  
</div>

<script>
function switchFormMode(mode) {
  // Toggle hidden mode input
  document.getElementById('form_mode').value = mode;
  
  // Toggle display of field groups
  const signupGroup = document.getElementById('signup_fields_group');
  const loginGroup = document.getElementById('login_fields_group');
  const signupFooter = document.getElementById('signup_footer_switch');
  const loginFooter = document.getElementById('login_footer_switch');
  
  // Toggle tab button active state
  const buttons = document.querySelectorAll('.tab-btn');
  
  if (mode === 'signup') {
    signupGroup.style.display = 'block';
    loginGroup.style.display = 'none';
    signupFooter.style.display = 'block';
    loginFooter.style.display = 'none';
    
    // Set required attributes dynamically
    document.getElementsByName('name')[0].setAttribute('required', 'required');
    document.getElementsByName('email')[0].setAttribute('required', 'required');
    document.getElementsByName('phone')[0].setAttribute('required', 'required');
    document.getElementsByName('course')[0].setAttribute('required', 'required');
    document.getElementsByName('city')[0].setAttribute('required', 'required');
    
    document.getElementsByName('phone_login')[0].removeAttribute('required');
    
    buttons[0].classList.add('active');
    buttons[1].classList.remove('active');
  } else {
    signupGroup.style.display = 'none';
    loginGroup.style.display = 'block';
    signupFooter.style.display = 'none';
    loginFooter.style.display = 'block';
    
    // Remove required attributes from hidden fields
    document.getElementsByName('name')[0].removeAttribute('required');
    document.getElementsByName('email')[0].removeAttribute('required');
    document.getElementsByName('phone')[0].removeAttribute('required');
    document.getElementsByName('course')[0].removeAttribute('required');
    document.getElementsByName('city')[0].removeAttribute('required');
    
    document.getElementsByName('phone_login')[0].setAttribute('required', 'required');
    
    buttons[0].classList.remove('active');
    buttons[1].classList.add('active');
  }
}

function updateDynamicPhoneConstraint(input_id, select_id) {
  const select = document.getElementById(select_id);
  const option = select.options[select.selectedIndex];
  const min = option.getAttribute('data-min');
  const max = option.getAttribute('data-max');
  
  const phoneInput = document.getElementById(input_id);
  if (phoneInput) {
    phoneInput.setAttribute('pattern', `^[0-9]{${min},${max}}$`);
    phoneInput.setAttribute('title', `Enter exactly ${min} to ${max} digits number`);
    phoneInput.placeholder = `Mobile Number (${min}-${max} digits)`;
  }
}

// Initialize validation formats
document.addEventListener("DOMContentLoaded", function() {
  updateDynamicPhoneConstraint('phone', 'country_code');
  updateDynamicPhoneConstraint('phone_login', 'country_code_login');
  
  // Enforce initial mode defaults
  switchFormMode('<?= $action_type ?>');
});
</script>
</body>
</html>
