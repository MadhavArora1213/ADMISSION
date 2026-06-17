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

require_once __DIR__ . '/includes/sms_config.php';
require_once __DIR__ . '/includes/phone_email_config.php';

// Supported Country Codes with Phone Lengths
$country_data = [
    '91'  => ['min' => 10, 'max' => 10, 'name' => 'India (+91)'],
    '1'   => ['min' => 10, 'max' => 10, 'name' => 'USA/Canada (+1)'],
    '44'  => ['min' => 10, 'max' => 10, 'name' => 'UK (+44)'],
    '61'  => ['min' => 9,  'max' => 9,  'name' => 'Australia (+61)'],
    '971' => ['min' => 9,  'max' => 9,  'name' => 'UAE (+971)'],
    '977' => ['min' => 10, 'max' => 10, 'name' => 'Nepal (+977)'],
];

// Handle AJAX Actions
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_GET['ajax'];
    
    // Validate CSRF token for AJAX requests
    $post_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $post_token)) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh the page.']);
        exit;
    }
    
    if ($action === 'check_exist') {
        $form_mode = $_POST['form_mode'] ?? 'signup';
        if ($form_mode === 'signup') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $country_code = trim($_POST['country_code'] ?? '91');
            $phone = trim($_POST['phone'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $course_id = trim($_POST['course'] ?? '');
            if ($course_id === 'Other') {
                $course_id = trim($_POST['other_course_name'] ?? '');
            }

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
                echo json_encode(['success' => false, 'error' => 'Name must be letters and spaces only (2-100 chars).']);
                exit;
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
                exit;
            } elseif (!$isValidPhone) {
                $limits = $country_data[$country_code] ?? ['min' => 10, 'max' => 10];
                echo json_encode(['success' => false, 'error' => 'Please enter a valid phone number containing exactly ' . $limits['min'] . ' digits.']);
                exit;
            } elseif (!preg_match($city_regex, $city)) {
                echo json_encode(['success' => false, 'error' => 'Please enter a valid city name.']);
                exit;
            } elseif (empty($course_id)) {
                echo json_encode(['success' => false, 'error' => 'Please select your preferred course.']);
                exit;
            } else {
                $full_phone = '+' . $country_code . $phone;
                $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
                $stmtCheck->execute([$email, $full_phone]);
                if ($stmtCheck->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'An account with this email or phone number already exists.']);
                    exit;
                } else {
                    // Send ShoutOUT OTP
                    $otp_id = sendShoutoutOtp($full_phone);
                    if (!$otp_id) {
                        echo json_encode(['success' => false, 'error' => 'Failed to send verification SMS via ShoutOUT. Please check your number or try again.']);
                        exit;
                    }
                    
                    $_SESSION['temp_auth'] = [
                        'mode' => 'signup',
                        'name' => $name,
                        'email' => $email,
                        'phone' => $full_phone,
                        'city' => $city,
                        'course_id' => $course_id,
                        'otp_id' => $otp_id
                    ];
                    echo json_encode(['success' => true, 'phone' => $full_phone]);
                    exit;
                }
            }
        } else {
            // Login Mode
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
                echo json_encode(['success' => false, 'error' => 'Please enter a valid phone number containing exactly ' . $limits['min'] . ' digits.']);
                exit;
            } else {
                $full_phone = '+' . $country_code . $phone;
                $stmtUser = $pdo->prepare("SELECT id, full_name, phone FROM users WHERE phone = ? AND status = 'active' LIMIT 1");
                $stmtUser->execute([$full_phone]);
                $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    echo json_encode(['success' => false, 'error' => 'No registered user found with this mobile number. Please sign up first.']);
                    exit;
                } else {
                    // Send ShoutOUT OTP
                    $otp_id = sendShoutoutOtp($full_phone);
                    if (!$otp_id) {
                        echo json_encode(['success' => false, 'error' => 'Failed to send verification SMS via ShoutOUT. Please try again.']);
                        exit;
                    }
                    
                    $_SESSION['temp_auth'] = [
                        'mode' => 'login',
                        'id' => $user['id'],
                        'name' => $user['full_name'],
                        'phone' => $user['phone'],
                        'otp_id' => $otp_id
                    ];
                    echo json_encode(['success' => true, 'phone' => $full_phone]);
                    exit;
                }
            }
        }
    } elseif ($action === 'verify_shoutout_otp') {
        $otpCode = trim($_POST['otp'] ?? '');
        if (empty($otpCode)) {
            echo json_encode(['success' => false, 'error' => 'Please enter the verification code.']);
            exit;
        }

        if (empty($_SESSION['temp_auth'])) {
            echo json_encode(['success' => false, 'error' => 'Session expired. Please request a new OTP.']);
            exit;
        }

        $temp = $_SESSION['temp_auth'];
        $otpId = $temp['otp_id'] ?? '';

        // Verify with ShoutOUT
        $isVerified = verifyShoutoutOtp($otpId, $otpCode);
        if (!$isVerified) {
            echo json_encode(['success' => false, 'error' => 'Invalid OTP code. Please try again.']);
            exit;
        }

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
                
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $temp['name'];
                
                echo json_encode(['success' => true, 'redirect' => 'index.php']);
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo json_encode(['success' => false, 'error' => 'An error occurred during registration. Details: ' . $e->getMessage()]);
                exit;
            }
        } else {
            // Login Mode
            unset($_SESSION['temp_auth']);
            
            session_regenerate_id(true);
            $_SESSION['user_id'] = $temp['id'];
            $_SESSION['user_name'] = $temp['name'];
            
            $stmtUpdate = $pdo->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ?, login_count = login_count + 1 WHERE id = ?");
            $stmtUpdate->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $temp['id']]);
            
            echo json_encode(['success' => true, 'redirect' => 'index.php']);
            exit;
        }
    } elseif ($action === 'verify_phone_email') {
        $user_json_url = trim($_POST['user_json_url'] ?? '');
        if (empty($user_json_url)) {
            echo json_encode(['success' => false, 'error' => 'Invalid verification data.']);
            exit;
        }

        $verified = verifyPhoneEmail($user_json_url);
        if (!$verified) {
            echo json_encode(['success' => false, 'error' => 'Phone verification failed. Please try again.']);
            exit;
        }

        $verified_phone = $verified['phone'];
        $form_mode = trim($_POST['form_mode'] ?? 'signup');

        if ($form_mode === 'signup') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $course_id = trim($_POST['course'] ?? '');
            if ($course_id === 'Other') {
                $course_id = trim($_POST['other_course_name'] ?? '');
            }

            $name_regex = "/^[a-zA-Z\s]{2,100}$/";
            $city_regex = "/^[a-zA-Z\s\.\-']{2,100}$/";

            if (!preg_match($name_regex, $name)) {
                echo json_encode(['success' => false, 'error' => 'Name must be letters and spaces only (2-100 chars).']);
                exit;
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
                exit;
            } elseif (!preg_match($city_regex, $city)) {
                echo json_encode(['success' => false, 'error' => 'Please enter a valid city name.']);
                exit;
            } elseif (empty($course_id)) {
                echo json_encode(['success' => false, 'error' => 'Please select your preferred course.']);
                exit;
            }

            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
            $stmtCheck->execute([$email, $verified_phone]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['success' => false, 'error' => 'An account with this email or phone number already exists.']);
                exit;
            }

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
                    $user_id, $name, $email, $verified_phone, $dummy_pass
                ]);

                $stmtInsertProfile = $pdo->prepare("
                    INSERT INTO student_profiles (id, user_id, city, preferred_courses)
                    VALUES (?, ?, ?, ?)
                ");
                $preferred_courses_json = json_encode([$course_id]);
                $stmtInsertProfile->execute([
                    $profile_id, $user_id, $city, $preferred_courses_json
                ]);

                $pdo->commit();

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;

                echo json_encode(['success' => true, 'redirect' => 'index.php']);
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo json_encode(['success' => false, 'error' => 'An error occurred during registration.']);
                exit;
            }
        } else {
            $stmtUser = $pdo->prepare("SELECT id, full_name, phone FROM users WHERE phone = ? AND status = 'active' LIMIT 1");
            $stmtUser->execute([$verified_phone]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['success' => false, 'error' => 'No registered user found with this mobile number. Please sign up first.']);
                exit;
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];

            $stmtUpdate = $pdo->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ?, login_count = login_count + 1 WHERE id = ?");
            $stmtUpdate->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $user['id']]);

            echo json_encode(['success' => true, 'redirect' => 'index.php']);
            exit;
        }
    }
}

$error = '';
$success_message = '';
$show_otp_verify = false;
$otp_sent_to = '';
$action_type = 'signup';

// Fetch active states and cities
try {
    $states_list = $pdo->query("SELECT id, name FROM states ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $cities_list = $pdo->query("SELECT id, state_id, name FROM cities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $states_list = [];
    $cities_list = [];
}

$popular_courses = [
    ['id' => 'B.Tech', 'course_name' => 'B.Tech (Bachelor of Technology)'],
    ['id' => 'MBA', 'course_name' => 'MBA (Master of Business Administration)'],
    ['id' => 'MBBS', 'course_name' => 'MBBS (Bachelor of Medicine, Bachelor of Surgery)'],
    ['id' => 'BBA', 'course_name' => 'BBA (Bachelor of Business Administration)'],
    ['id' => 'BCA', 'course_name' => 'BCA (Bachelor of Computer Applications)'],
    ['id' => 'MCA', 'course_name' => 'MCA (Master of Computer Applications)'],
];

$ug_courses = [
    ['id' => 'B.Tech', 'course_name' => 'B.Tech (Bachelor of Technology)'],
    ['id' => 'B.Sc', 'course_name' => 'B.Sc (Bachelor of Science)'],
    ['id' => 'B.Com', 'course_name' => 'B.Com (Bachelor of Commerce)'],
    ['id' => 'B.A.', 'course_name' => 'B.A. (Bachelor of Arts)'],
    ['id' => 'BBA', 'course_name' => 'BBA (Bachelor of Business Administration)'],
    ['id' => 'BCA', 'course_name' => 'BCA (Bachelor of Computer Applications)'],
    ['id' => 'B.Arch', 'course_name' => 'B.Arch (Bachelor of Architecture)'],
    ['id' => 'B.Ed', 'course_name' => 'B.Ed (Bachelor of Education)'],
    ['id' => 'B.Pharma', 'course_name' => 'B.Pharma (Bachelor of Pharmacy)'],
    ['id' => 'BDS', 'course_name' => 'BDS (Bachelor of Dental Surgery)'],
    ['id' => 'BHM', 'course_name' => 'BHM (Bachelor of Hotel Management)'],
    ['id' => 'BJMC', 'course_name' => 'BJMC (Bachelor of Journalism & Mass Comm)'],
];

$pg_courses = [
    ['id' => 'MBA', 'course_name' => 'MBA (Master of Business Administration)'],
    ['id' => 'M.Tech', 'course_name' => 'M.Tech (Master of Technology)'],
    ['id' => 'M.Sc', 'course_name' => 'M.Sc (Master of Science)'],
    ['id' => 'M.Com', 'course_name' => 'M.Com (Master of Commerce)'],
    ['id' => 'M.A.', 'course_name' => 'M.A. (Master of Arts)'],
    ['id' => 'MCA', 'course_name' => 'MCA (Master of Computer Applications)'],
    ['id' => 'M.Pharma', 'course_name' => 'M.Pharma (Master of Pharmacy)'],
    ['id' => 'LL.M.', 'course_name' => 'LL.M. (Master of Laws)'],
    ['id' => 'M.Ed', 'course_name' => 'M.Ed (Master of Education)'],
    ['id' => 'MD-MS', 'course_name' => 'MD / MS (Doctor of Medicine / Master of Surgery)'],
];

$phd_courses = [
    ['id' => 'PhD-Engg', 'course_name' => 'Ph.D. in Engineering'],
    ['id' => 'PhD-Mgmt', 'course_name' => 'Ph.D. in Management'],
    ['id' => 'PhD-Science', 'course_name' => 'Ph.D. in Science'],
    ['id' => 'PhD-Hums', 'course_name' => 'Ph.D. in Humanities & Social Sciences'],
    ['id' => 'PhD-CS', 'course_name' => 'Ph.D. in Computer Science'],
    ['id' => 'PhD-Pharma', 'course_name' => 'Ph.D. in Pharmacy'],
];

$diploma_courses = [
    ['id' => 'Dip-Poly', 'course_name' => 'Diploma in Engineering (Polytechnic)'],
    ['id' => 'DCA', 'course_name' => 'Diploma in Computer Applications (DCA)'],
    ['id' => 'Dip-Mgmt', 'course_name' => 'Diploma in Business Management'],
    ['id' => 'D.Pharma', 'course_name' => 'Diploma in Pharmacy (D.Pharma)'],
    ['id' => 'Dip-Hotel', 'course_name' => 'Diploma in Hotel Management'],
    ['id' => 'Dip-Nursing', 'course_name' => 'Diploma in Nursing (GNM / ANM)'],
    ['id' => 'Dip-Mktg', 'course_name' => 'Diploma in Digital Marketing'],
];

$other_courses = [
    ['id' => 'Other', 'course_name' => 'Other']
];

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
                $form_mode = $_POST['form_mode'] ?? 'signup';
                
                if ($form_mode === 'signup') {
                    $action_type = 'signup';
                    $name = trim($_POST['name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $country_code = trim($_POST['country_code'] ?? '91');
                    $phone = trim($_POST['phone'] ?? '');
                    $city = trim($_POST['city'] ?? '');
                    $course_id = trim($_POST['course'] ?? '');
                    if ($course_id === 'Other') {
                        $course_id = trim($_POST['other_course_name'] ?? '');
                    }

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
  <title>Register & Login | AdmissionSeason</title>
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
      background: rgba(255, 255, 255, 0.22);
      padding: 55px 45px;
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
      font-size: 1.75rem;
      font-weight: 800;
      text-decoration: none;
      transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      background: linear-gradient(135deg, var(--oxford-navy) 0%, var(--yale-blue) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .visual-logo:hover {
      transform: translateY(-2px) scale(1.02);
      opacity: 0.95;
    }
    .visual-logo i {
      font-size: 2.1rem;
      background: linear-gradient(135deg, var(--oxford-navy) 0%, var(--yale-blue) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .visual-content {
      margin: 24px 0;
    }
    
    .visual-content h2 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 2.1rem;
      font-weight: 800;
      line-height: 1.25;
      margin-bottom: 10px;
      letter-spacing: -0.02em;
      background: linear-gradient(135deg, var(--oxford-navy) 0%, var(--yale-blue) 80%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .visual-content p {
      color: var(--ink-black);
      opacity: 0.65;
      font-size: 0.92rem;
      margin-bottom: 24px;
      font-weight: 600;
      letter-spacing: 0.01em;
    }
    
    .benefit-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    /* Premium Glass Capsules for list items */
    .benefit-item {
      display: flex;
      align-items: center;
      gap: 14px;
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--yale-blue);
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      padding: 12px 16px;
      border-radius: 14px;
      background: rgba(255, 255, 255, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.4);
      box-shadow: 0 4px 12px rgba(11, 36, 71, 0.02);
    }
    
    .benefit-item:hover {
      transform: translateX(6px) translateY(-1px);
      background: rgba(255, 255, 255, 0.65);
      border-color: rgba(11, 36, 71, 0.1);
      box-shadow: 0 6px 16px rgba(11, 36, 71, 0.05);
    }
    
    .benefit-item i {
      font-size: 0.85rem;
      color: #FFFFFF;
      background: linear-gradient(135deg, var(--oxford-navy) 0%, var(--yale-blue) 100%);
      padding: 6px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 3px 8px rgba(11, 36, 71, 0.15);
    }
    
    /* Interactive vector graph illustration inside a widget box */
    .vector-illustration {
      position: relative;
      height: 145px;
      width: 100%;
      margin-top: 24px;
      overflow: visible;
      background: rgba(255, 255, 255, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.65);
      border-radius: 20px;
      padding: 16px;
      box-shadow: 0 10px 30px rgba(11, 36, 71, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.5);
    }
    
    .vector-badge {
      position: absolute;
      top: 12px;
      left: 16px;
      background: rgba(255, 255, 255, 0.75);
      border: 1px solid rgba(11, 36, 71, 0.08);
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.68rem;
      font-weight: 800;
      color: var(--oxford-navy);
      letter-spacing: 0.06em;
      display: flex;
      align-items: center;
      gap: 6px;
      z-index: 3;
      box-shadow: 0 2px 6px rgba(11, 36, 71, 0.02);
    }
    
    .pulse-dot {
      width: 6px;
      height: 6px;
      background: #10b981;
      border-radius: 50%;
      display: inline-block;
      box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
      animation: dotPulse 1.6s infinite;
    }
    
    @keyframes dotPulse {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5); }
      70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    
    .vector-illustration .node-blob {
      position: absolute;
      border-radius: 50%;
      filter: blur(12px);
      opacity: 0.35;
      animation: pulseBlob 4s ease-in-out infinite alternate;
    }
    
    .nb-orange { width: 45px; height: 45px; background: #FF9F43; bottom: 12px; left: 20px; }
    .nb-yellow { width: 55px; height: 55px; background: #FFD200; top: 12px; right: 40px; animation-delay: -1.5s; }
    .nb-blue { width: 38px; height: 38px; background: #54A0FF; top: 10px; left: 110px; animation-delay: -3s; }
    
    @keyframes pulseBlob {
      0% { transform: scale(0.95); opacity: 0.25; }
      100% { transform: scale(1.05); opacity: 0.45; }
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
      animation: dashFlow 6s linear infinite;
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
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.8);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 14px rgba(11, 36, 71, 0.1);
      color: var(--oxford-navy);
      font-size: 1.15rem;
      z-index: 2;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      cursor: pointer;
      animation: nodeFloat 4s ease-in-out infinite alternate;
    }
    
    .node-1 { bottom: 15px; left: 45px; animation-delay: 0s; }
    .node-2 { top: 25px; left: 130px; animation-delay: -1.3s; }
    .node-3 { bottom: 15px; right: 65px; animation-delay: -2.6s; }
    
    @keyframes nodeFloat {
      0% { transform: translateY(0); }
      100% { transform: translateY(-5px); }
    }
    
    .vector-illustration .node:hover {
      transform: scale(1.15) translateY(-6px) !important;
      box-shadow: 0 12px 24px rgba(11, 36, 71, 0.18);
      border-color: var(--oxford-navy);
      background: var(--white);
      animation-play-state: paused;
    }
    
    .visual-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 24px;
      padding-top: 16px;
      border-top: 1px solid rgba(255, 255, 255, 0.25);
      font-size: 0.74rem;
      font-weight: 700;
      color: var(--oxford-navy);
      opacity: 0.55;
      letter-spacing: 0.03em;
      font-family: 'Space Grotesk', sans-serif;
    }
    
    .visual-footer .footer-secured {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .visual-footer .footer-secured i {
      font-size: 0.95rem;
      color: #10b981;
    }
    
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
    
    /* City Autocomplete Dropdown Styles */
    .city-autocomplete-wrapper {
      position: relative;
      width: 100%;
    }
    
    .city-suggestions-dropdown {
      position: absolute;
      top: calc(100% + 4px);
      left: 0;
      width: 100%;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(15px);
      -webkit-backdrop-filter: blur(15px);
      border: 1px solid rgba(11, 36, 71, 0.1);
      border-radius: 12px;
      max-height: 280px;
      overflow-y: auto;
      z-index: 999;
      box-shadow: 0 10px 30px rgba(11, 36, 71, 0.1);
      scrollbar-width: thin;
      scrollbar-color: rgba(11, 36, 71, 0.2) transparent;
      text-align: left;
    }
    
    .city-suggestions-dropdown::-webkit-scrollbar {
      width: 6px;
    }
    
    .city-suggestions-dropdown::-webkit-scrollbar-thumb {
      background: rgba(11, 36, 71, 0.15);
      border-radius: 4px;
    }
    
    .city-group-header {
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
      color: var(--oxford-navy);
      opacity: 0.85;
      background: rgba(11, 36, 71, 0.05);
      padding: 8px 16px;
      letter-spacing: 0.05em;
    }
    
    .city-item {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--ink-black);
      padding: 10px 18px 10px 36px;
      cursor: pointer;
      position: relative;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .city-item::before {
      content: '\f21a'; /* phosphor map-pin icon */
      font-family: "Phosphor";
      font-weight: 400;
      font-size: 0.95rem;
      color: var(--oxford-navy);
      opacity: 0.5;
      position: absolute;
      left: 16px;
      transition: all 0.2s ease;
    }
    
    .city-item:hover {
      background: rgba(11, 36, 71, 0.04);
      color: var(--oxford-navy);
      padding-left: 40px;
    }
    
    .city-item:hover::before {
      color: #FF9F43; /* orange map-pin icon on hover */
      opacity: 1;
    }
    
    .no-cities-found {
      font-size: 0.88rem;
      font-weight: 600;
      color: var(--ink-black);
      opacity: 0.5;
      padding: 16px;
      text-align: center;
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
    

    
    <div class="visual-footer">
      <div class="footer-copy">© 2026 AdmissionSeason</div>
      <div class="footer-secured"><i class="ph-fill ph-shield-check"></i> SSL Secured</div>
    </div>
  </div>
  
  <!-- Right Column - Interacting Tab Forms -->
  <div class="form-section">
    <!-- Alert Box Container for Dynamic Alerts -->
    <div id="alert_container" style="display: none;"></div>

    <div id="auth_forms_wrapper">
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
              <select name="course" class="input-card" id="course_select" onchange="toggleOtherCourseInput()">
                <option value="" disabled selected>Course</option>
                <?php if (!empty($popular_courses)): ?>
                  <optgroup label="Popular Courses">
                    <?php foreach ($popular_courses as $c): ?>
                      <option value="<?= htmlspecialchars($c['id']) ?>" <?= (($_POST['course'] ?? '') === $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['course_name']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
                <?php if (!empty($ug_courses)): ?>
                  <optgroup label="Bachelor Courses">
                    <?php foreach ($ug_courses as $c): ?>
                      <option value="<?= htmlspecialchars($c['id']) ?>" <?= (($_POST['course'] ?? '') === $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['course_name']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
                <?php if (!empty($pg_courses)): ?>
                  <optgroup label="Master Courses">
                    <?php foreach ($pg_courses as $c): ?>
                      <option value="<?= htmlspecialchars($c['id']) ?>" <?= (($_POST['course'] ?? '') === $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['course_name']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
                <?php if (!empty($phd_courses)): ?>
                  <optgroup label="Doctorate Courses">
                    <?php foreach ($phd_courses as $c): ?>
                      <option value="<?= htmlspecialchars($c['id']) ?>" <?= (($_POST['course'] ?? '') === $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['course_name']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
                <?php if (!empty($diploma_courses)): ?>
                  <optgroup label="Diploma Courses">
                    <?php foreach ($diploma_courses as $c): ?>
                      <option value="<?= htmlspecialchars($c['id']) ?>" <?= (($_POST['course'] ?? '') === $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['course_name']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
                <?php if (!empty($other_courses)): ?>
                  <optgroup label="Other Courses">
                    <?php foreach ($other_courses as $c): ?>
                      <option value="<?= htmlspecialchars($c['id']) ?>" <?= (($_POST['course'] ?? '') === $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['course_name']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
              </select>
            </div>
            
            <div class="form-group-custom" id="other_course_wrapper" style="display: <?= (($_POST['course'] ?? '') === 'Other') ? 'block' : 'none' ?>;">
              <input type="text" name="other_course_name" id="other_course_name" class="input-card" placeholder="Specify Course Name" value="<?= htmlspecialchars($_POST['other_course_name'] ?? '') ?>" pattern="^[a-zA-Z0-9\s\.\-\(\)]{2,100}$">
            </div>
            
            <div class="form-group-custom full-width">
              <div class="city-autocomplete-wrapper">
                <input type="text" name="city" id="city_input" class="input-card" placeholder="City You Live In" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" pattern="^[a-zA-Z\s\.\-']{2,100}$" autocomplete="off" required>
                <div class="city-suggestions-dropdown" id="city_suggestions" style="display: none;"></div>
              </div>
            </div>
          </div>
          
          <div class="terms-row">
            <input type="checkbox" id="agree_terms" required checked>
            <label for="agree_terms">I agree to AdmissionSeason's <a href="#">Privacy Policy</a> and <a href="#">Terms & Conditions</a> and provide consent to be contacted for updates.</label>
          </div>
        </div>
        
        <!-- LOGIN CARD FIELDS -->
        <div id="login_fields_group" style="display: <?= ($action_type === 'login') ? 'block' : 'none' ?>;">
          <p style="text-align:center; font-size:0.92rem; color:#64748b; margin-bottom:8px;">Click the button below to sign in with your phone number via Phone.Email.</p>
        </div>
        
        <!-- Recaptcha Container required for Firebase -->
        <div id="recaptcha-container" style="margin-bottom: 20px; display: flex; justify-content: center;"></div>
        
        <div style="display: flex; justify-content: center; margin: 8px auto 0 auto;">
          <div class="pe_signin_button" data-client-id="<?= PE_APP_ID ?>"></div>
        </div>
        <script src="https://www.phone.email/sign_in_button_v1.js"></script>
        
        <!-- Bottom switch links -->
        <div class="switch-footer-text" id="signup_footer_switch" style="display: <?= ($action_type === 'signup') ? 'block' : 'none' ?>;">
          Already have an AdmissionSeason account? <span onclick="switchFormMode('login')">Login to continue</span>
        </div>
        <div class="switch-footer-text" id="login_footer_switch" style="display: <?= ($action_type === 'login') ? 'block' : 'none' ?>;">
          Don't have an AdmissionSeason account? <span onclick="switchFormMode('signup')">Sign Up to continue</span>
        </div>
      </form>
    </div>

    <!-- OTP VERIFICATION VIEW (Handled dynamically) -->
    <div id="otp_verification_wrapper" style="display: none;">
      <h1 style="font-family: 'Space Grotesk', sans-serif; margin-bottom: 8px;">Verify Mobile Number</h1>
      <p class="subtitle">A 5-digit OTP code has been sent to your phone (<strong id="otp_sent_to_label"></strong>).</p>
      
      <form method="POST" action="" id="otp_form">
        <div class="form-group-custom" style="max-width: 320px; margin: 0 auto 24px auto;">
          <input type="text" name="otp" id="otp_input" class="input-card" placeholder="Enter 5-digit OTP" required pattern="^[0-9]{5}$" maxlength="5" autofocus style="letter-spacing: 6px; font-weight: 800; text-align: center; font-size: 1.1rem; background: rgba(255,255,255,0.5); border: 1px solid var(--border-glass);">
        </div>
        
        <button type="submit" class="btn-submit" style="max-width: 260px; margin: 0 auto;">Verify OTP <i class="ph ph-arrow-right"></i></button>
      </form>
      
      <p class="switch-footer-text" style="margin-top: 24px;">
        Entered wrong number? <span onclick="cancelOtpVerification()" style="cursor: pointer; color: var(--oxford-navy); font-weight: 700; text-decoration: underline;">Change Number</span>
      </p>
    </div>
  </div>
</div>

<script>
// Dynamic Alert Message Handler
function showAlert(message, type = 'danger') {
  const container = document.getElementById('alert_container');
  if (!container) return;
  
  if (message) {
    const icon = type === 'success' ? 'ph-check-circle' : 'ph-warning-circle';
    container.className = `alert-box alert-${type}`;
    container.innerHTML = `<i class="ph ${icon}"></i> ${message}`;
    container.style.display = 'flex';
  } else {
    container.style.display = 'none';
  }
}

// Reset view back to signup/login fields
function cancelOtpVerification() {
  document.getElementById('auth_forms_wrapper').style.display = 'block';
  document.getElementById('otp_verification_wrapper').style.display = 'none';
  showAlert('');
}

// Client logic for form submit interceptions
document.addEventListener('DOMContentLoaded', () => {
  const authForm = document.getElementById('auth_form');
  if (authForm) {
    authForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      showAlert('');
      
      const formMode = document.getElementById('form_mode').value;
      const formData = new FormData(authForm);
      
      const submitBtn = authForm.querySelector('.btn-get-otp');
      const originalBtnText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Sending SMS OTP... <i class="ph ph-spinner-gap animate-spin"></i>';
      
      try {
        // 1. Trigger pre-validation check and OTP generation via server ShoutOUT SMS API
        const response = await fetch('?ajax=check_exist', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        
        if (!result.success) {
          showAlert(result.error);
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
          return;
        }
        
        // 2. Pre-validation and OTP generation succeeded. Transition UI views
        const phoneNumber = result.phone;
        document.getElementById('auth_forms_wrapper').style.display = 'none';
        document.getElementById('otp_verification_wrapper').style.display = 'block';
        document.getElementById('otp_sent_to_label').innerText = phoneNumber;
        
        // Focus on OTP input
        setTimeout(() => {
          const otpInput = document.getElementById('otp_input');
          if (otpInput) otpInput.focus();
        }, 100);
        
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        
      } catch (err) {
        console.error("OTP send failed:", err);
        showAlert("Failed to send OTP verification code. Please check your network.");
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      }
    });
  }

  // OTP Verification Submission
  const otpForm = document.getElementById('otp_form');
  if (otpForm) {
    otpForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      showAlert('');
      
      const otp = document.getElementById('otp_input').value;
      const submitBtn = otpForm.querySelector('.btn-submit');
      const originalBtnText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Verifying OTP code... <i class="ph ph-spinner-gap animate-spin"></i>';
      
      try {
        // Send verified OTP code back to server for verification
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('otp', otp);
        
        const response = await fetch('?ajax=verify_shoutout_otp', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        
        if (result.success) {
          showAlert('Verified successfully! Redirecting you...', 'success');
          setTimeout(() => {
            window.location.href = result.redirect;
          }, 1000);
        } else {
          showAlert(result.error);
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        }
      } catch (err) {
        console.error("OTP verification error:", err);
        showAlert("An error occurred during verification. Please try again.");
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      }
    });
  }
});

// Phone.Email verification callback
function phoneEmailListener(userObj) {
  var user_json_url = userObj.user_json_url;
  const form = document.getElementById('auth_form');
  const formMode = document.getElementById('form_mode').value;
  const submitBtn = document.querySelector('.pe_signin_button');
  if (submitBtn) submitBtn.style.pointerEvents = 'none';

  showAlert('Phone verified! Completing...', 'success');

  const formData = new FormData(form);
  formData.append('user_json_url', user_json_url);
  formData.append('form_mode', formMode);

  fetch('?ajax=verify_phone_email', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(result => {
    if (result.success) {
      showAlert('Verified successfully! Redirecting...', 'success');
      setTimeout(() => { window.location.href = result.redirect; }, 1000);
    } else {
      showAlert(result.error);
      if (submitBtn) submitBtn.style.pointerEvents = 'auto';
    }
  })
  .catch(err => {
    console.error('Phone.Email verification error:', err);
    showAlert('An error occurred during verification. Please try again.');
    if (submitBtn) submitBtn.style.pointerEvents = 'auto';
  });
}

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
    document.getElementsByName('course')[0].setAttribute('required', 'required');
    document.getElementsByName('city')[0].setAttribute('required', 'required');
    
    buttons[0].classList.add('active');
    buttons[1].classList.remove('active');
    toggleOtherCourseInput();
  } else {
    signupGroup.style.display = 'none';
    loginGroup.style.display = 'block';
    signupFooter.style.display = 'none';
    loginFooter.style.display = 'block';
    
    // Remove required attributes from hidden fields
    document.getElementsByName('name')[0].removeAttribute('required');
    document.getElementsByName('email')[0].removeAttribute('required');
    document.getElementsByName('course')[0].removeAttribute('required');
    document.getElementsByName('city')[0].removeAttribute('required');
    
    buttons[0].classList.remove('active');
    buttons[1].classList.add('active');
    
    const otherWrapper = document.getElementById('other_course_wrapper');
    const otherInput = document.getElementById('other_course_name');
    if (otherWrapper && otherInput) {
      otherWrapper.style.display = 'none';
      otherInput.removeAttribute('required');
    }
  }
}

function toggleOtherCourseInput() {
  const courseSelect = document.getElementById('course_select');
  const otherWrapper = document.getElementById('other_course_wrapper');
  const otherInput = document.getElementById('other_course_name');
  if (courseSelect && otherWrapper && otherInput) {
    if (courseSelect.value === 'Other') {
      otherWrapper.style.display = 'block';
      otherInput.setAttribute('required', 'required');
    } else {
      otherWrapper.style.display = 'none';
      otherInput.removeAttribute('required');
    }
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
  // Enforce initial mode defaults
  switchFormMode('<?= $action_type ?>');
});

// City Autocomplete Suggest Controller
const statesData = <?= json_encode($states_list) ?>;
const citiesData = <?= json_encode($cities_list) ?>;
const majorCities = ["New Delhi", "Ahmedabad", "Bangalore", "Mumbai", "Pune", "Chennai", "Kolkata"];

const cityInput = document.getElementById('city_input');
const citySuggestions = document.getElementById('city_suggestions');

function renderCitySuggestions(query = '') {
  citySuggestions.innerHTML = '';
  
  if (query.trim() === '') {
    // Show Major Cities first
    const majorHeader = document.createElement('div');
    majorHeader.className = 'city-group-header';
    majorHeader.innerText = 'Major Cities';
    citySuggestions.appendChild(majorHeader);
    
    majorCities.forEach(cityName => {
      const cityItem = document.createElement('div');
      cityItem.className = 'city-item';
      cityItem.innerText = cityName;
      cityItem.onclick = () => selectCity(cityName);
      citySuggestions.appendChild(cityItem);
    });
    
    // Group cities by State
    const grouped = {};
    citiesData.forEach(c => {
      if (!grouped[c.state_id]) grouped[c.state_id] = [];
      grouped[c.state_id].push(c.name);
    });
    
    statesData.forEach(state => {
      if (grouped[state.id] && grouped[state.id].length > 0) {
        const stateHeader = document.createElement('div');
        stateHeader.className = 'city-group-header';
        stateHeader.innerText = state.name;
        citySuggestions.appendChild(stateHeader);
        
        grouped[state.id].forEach(cityName => {
          const cityItem = document.createElement('div');
          cityItem.className = 'city-item';
          cityItem.innerText = cityName;
          cityItem.onclick = () => selectCity(cityName);
          citySuggestions.appendChild(cityItem);
        });
      }
    });
  } else {
    // Filter matching cities
    const lowerQuery = query.toLowerCase();
    const matches = citiesData.filter(c => c.name.toLowerCase().includes(lowerQuery));
    
    if (matches.length === 0) {
      const noFound = document.createElement('div');
      noFound.className = 'no-cities-found';
      noFound.innerText = 'No cities found';
      citySuggestions.appendChild(noFound);
    } else {
      // Group matches by state_id
      const groupedMatches = {};
      matches.forEach(c => {
        if (!groupedMatches[c.state_id]) groupedMatches[c.state_id] = [];
        groupedMatches[c.state_id].push(c.name);
      });
      
      statesData.forEach(state => {
        if (groupedMatches[state.id] && groupedMatches[state.id].length > 0) {
          const stateHeader = document.createElement('div');
          stateHeader.className = 'city-group-header';
          stateHeader.innerText = state.name;
          citySuggestions.appendChild(stateHeader);
          
          groupedMatches[state.id].forEach(cityName => {
            const cityItem = document.createElement('div');
            cityItem.className = 'city-item';
            
            const idx = cityName.toLowerCase().indexOf(lowerQuery);
            if (idx >= 0) {
              const before = cityName.substring(0, idx);
              const matchText = cityName.substring(idx, idx + lowerQuery.length);
              const after = cityName.substring(idx + lowerQuery.length);
              cityItem.innerHTML = `${before}<strong>${matchText}</strong>${after}`;
            } else {
              cityItem.innerText = cityName;
            }
            
            cityItem.onclick = () => selectCity(cityName);
            citySuggestions.appendChild(cityItem);
          });
        }
      });
    }
  }
}

function selectCity(cityName) {
  cityInput.value = cityName;
  citySuggestions.style.display = 'none';
}

if (cityInput) {
  cityInput.addEventListener('focus', () => {
    renderCitySuggestions(cityInput.value);
    citySuggestions.style.display = 'block';
  });
  
  cityInput.addEventListener('input', (e) => {
    renderCitySuggestions(e.target.value);
  });
  
  // Close suggestions when clicking outside
  document.addEventListener('click', (e) => {
    if (!cityInput.contains(e.target) && !citySuggestions.contains(e.target)) {
      citySuggestions.style.display = 'none';
    }
  });
}
</script>
</body>
</html>
