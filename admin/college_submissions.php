<?php
session_start();
require_once __DIR__ . '/db.php';
if (empty($_SESSION['admin_id'])) { header('Location: /ADMISSION/admin/index.php'); exit; }

$msg = '';

// Load .env for Brevo Email configurations
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

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

function applySubmissionApproval($pdo, $sub, $adminId) {
    $data = json_decode($sub['data_json'], true);
    $collegeId = $sub['college_id'];
    $type = $sub['submission_type'];

    switch ($type) {
        case 'profile':
            if ($data && $collegeId) {
                // 1. Update Colleges
                $colFields = []; $colVals = [];
                foreach (['name', 'college_type', 'ownership', 'type_label', 'founded_year', 'university_id', 'naac_grade', 'ranking_nirf', 'autonomous', 'ugc_approved', 'aicte_approved', 'total_students', 'total_faculty', 'campus_area_acres', 'campus_type', 'state_id', 'city_id', 'publish_status'] as $f) {
                    if (isset($data[$f])) { $colFields[] = "$f=?"; $colVals[] = $data[$f]; }
                }
                if ($colFields) { $colVals[] = $collegeId; $pdo->prepare("UPDATE colleges SET ".implode(',',$colFields)." WHERE id=?")->execute($colVals); }

                // 2. Update Media
                if (isset($data['logo_url']) || isset($data['cover_image_url'])) {
                    $mediaCheck = $pdo->prepare("SELECT id FROM college_media WHERE college_id = ? AND image_type IS NULL");
                    $mediaCheck->execute([$collegeId]);
                    if ($mediaCheck->rowCount() > 0) {
                        $pdo->prepare("UPDATE college_media SET logo_url = ?, cover_image_url = ? WHERE college_id = ? AND image_type IS NULL")
                            ->execute([$data['logo_url'] ?? null, $data['cover_image_url'] ?? null, $collegeId]);
                    } else {
                        $pdo->prepare("INSERT INTO college_media (id, college_id, logo_url, cover_image_url) VALUES (?, ?, ?, ?)")
                            ->execute([generateUUID(), $collegeId, $data['logo_url'] ?? null, $data['cover_image_url'] ?? null]);
                    }
                }

                // 3. Update Contacts
                $conFields = []; $conVals = [];
                foreach (['email', 'phone', 'address', 'latitude', 'longitude', 'website_url', 'pincode', 'google_maps_embed_url', 'nearest_railway_km', 'nearest_airport_km'] as $f) {
                    if (isset($data[$f])) { $conFields[] = "$f=?"; $conVals[] = $data[$f]; }
                }
                if ($conFields) {
                    $contactCheck = $pdo->prepare("SELECT id FROM college_contacts WHERE college_id = ?");
                    $contactCheck->execute([$collegeId]);
                    if ($contactCheck->rowCount() > 0) {
                        $conVals[] = $collegeId;
                        $pdo->prepare("UPDATE college_contacts SET ".implode(',',$conFields)." WHERE college_id=?")->execute($conVals);
                    } else {
                        $newId = generateUUID();
                        array_unshift($conVals, $newId, $collegeId);
                        $placeholders = array_fill(0, count($conFields), '?');
                        $pdo->prepare("INSERT INTO college_contacts (id, college_id, ".implode(',', array_map(fn($x) => str_replace('=?','',$x), $conFields)).") VALUES (?, ?, ".implode(',',$placeholders).")")->execute($conVals);
                    }
                }

                // 4. Update Content
                $contentFields = []; $contentVals = [];
                foreach (['about_text', 'highlights_json', 'accreditations_json', 'rankings_json', 'awards_json'] as $f) {
                    if (isset($data[$f])) { $contentFields[] = "$f=?"; $contentVals[] = $data[$f]; }
                }
                if ($contentFields) {
                    $chk = $pdo->prepare("SELECT id FROM college_content WHERE college_id = ?"); $chk->execute([$collegeId]);
                    if ($chk->rowCount() > 0) {
                        $contentVals[] = $collegeId;
                        $pdo->prepare("UPDATE college_content SET ".implode(',',$contentFields)." WHERE college_id=?")->execute($contentVals);
                    } else {
                        $newId = generateUUID();
                        array_unshift($contentVals, $newId, $collegeId);
                        $placeholders = array_fill(0, count($contentFields), '?');
                        $pdo->prepare("INSERT INTO college_content (id, college_id, ".implode(',', array_map(fn($x) => str_replace('=?','',$x), $contentFields)).") VALUES (?, ?, ".implode(',',$placeholders).")")->execute($contentVals);
                    }
                }

                // 5. Update Infrastructure
                $infFields = []; $infVals = [];
                foreach (['library', 'auditorium', 'cafeteria', 'wifi', 'medical_facility', 'transport', 'ev_charging', 'solar_power', 'sports_facilities', 'labs'] as $f) {
                    if (isset($data[$f])) { $infFields[] = "$f=?"; $infVals[] = $data[$f]; }
                }
                if ($infFields) {
                    $chk = $pdo->prepare("SELECT id FROM college_infrastructure WHERE college_id = ?"); $chk->execute([$collegeId]);
                    if ($chk->rowCount() > 0) {
                        $infVals[] = $collegeId;
                        $pdo->prepare("UPDATE college_infrastructure SET ".implode(',',$infFields)." WHERE college_id=?")->execute($infVals);
                    } else {
                        $newId = generateUUID();
                        array_unshift($infVals, $newId, $collegeId);
                        $placeholders = array_fill(0, count($infFields), '?');
                        $pdo->prepare("INSERT INTO college_infrastructure (id, college_id, ".implode(',', array_map(fn($x) => str_replace('=?','',$x), $infFields)).") VALUES (?, ?, ".implode(',',$placeholders).")")->execute($infVals);
                    }
                }

                // 6. Update Hostels
                $hstFields = []; $hstVals = [];
                foreach (['hostel_available', 'hostel_type', 'hostel_capacity', 'hostel_fee_annual', 'mess_available', 'mess_type', 'ac_available', 'laundry_available'] as $f) {
                    if (isset($data[$f])) { $hstFields[] = "$f=?"; $hstVals[] = $data[$f]; }
                }
                if ($hstFields) {
                    $chk = $pdo->prepare("SELECT id FROM college_hostels WHERE college_id = ?"); $chk->execute([$collegeId]);
                    if ($chk->rowCount() > 0) {
                        $hstVals[] = $collegeId;
                        $pdo->prepare("UPDATE college_hostels SET ".implode(',',$hstFields)." WHERE college_id=?")->execute($hstVals);
                    } else {
                        $newId = generateUUID();
                        array_unshift($hstVals, $newId, $collegeId);
                        $placeholders = array_fill(0, count($hstFields), '?');
                        $pdo->prepare("INSERT INTO college_hostels (id, college_id, ".implode(',', array_map(fn($x) => str_replace('=?','',$x), $hstFields)).") VALUES (?, ?, ".implode(',',$placeholders).")")->execute($hstVals);
                    }
                }

                // 7. Update Admissions
                $admFields = []; $admVals = [];
                foreach (['admission_process', 'accepted_exams', 'admission_start_date', 'admission_end_date', 'merit_based', 'direct_admission', 'management_quota_seats', 'nri_quota_seats', 'lateral_entry_available', 'application_mode'] as $f) {
                    if (isset($data[$f])) { $admFields[] = "$f=?"; $admVals[] = $data[$f]; }
                }
                if ($admFields) {
                    $chk = $pdo->prepare("SELECT id FROM college_admissions WHERE college_id = ?"); $chk->execute([$collegeId]);
                    if ($chk->rowCount() > 0) {
                        $admVals[] = $collegeId;
                        $pdo->prepare("UPDATE college_admissions SET ".implode(',',$admFields)." WHERE college_id=?")->execute($admVals);
                    } else {
                        $newId = generateUUID();
                        array_unshift($admVals, $newId, $collegeId);
                        $placeholders = array_fill(0, count($admFields), '?');
                        $pdo->prepare("INSERT INTO college_admissions (id, college_id, ".implode(',', array_map(fn($x) => str_replace('=?','',$x), $admFields)).") VALUES (?, ?, ".implode(',',$placeholders).")")->execute($admVals);
                    }
                }

                // 8. Update SEO
                $seoFields = []; $seoVals = [];
                foreach (['meta_title', 'meta_description', 'og_image_url', 'canonical_url', 'schema_markup', 'noindex'] as $f) {
                    if (isset($data[$f])) { $seoFields[] = "$f=?"; $seoVals[] = $data[$f]; }
                }
                if ($seoFields) {
                    $chk = $pdo->prepare("SELECT id FROM seo_meta WHERE page_type='college' AND page_id=?"); $chk->execute([$collegeId]);
                    if ($chk->rowCount() > 0) {
                        $seoVals[] = $collegeId;
                        $pdo->prepare("UPDATE seo_meta SET ".implode(',',$seoFields)." WHERE page_type='college' AND page_id=?")->execute($seoVals);
                    } else {
                        $newId = generateUUID();
                        array_unshift($seoVals, $newId, 'college', $collegeId);
                        $placeholders = array_fill(0, count($seoFields), '?');
                        $pdo->prepare("INSERT INTO seo_meta (id, page_type, page_id, ".implode(',', array_map(fn($x) => str_replace('=?','',$x), $seoFields)).") VALUES (?, ?, ?, ".implode(',',$placeholders).")")->execute($seoVals);
                    }
                }
            }
            break;
        case 'courses':
            if (isset($data['course_name']) && $collegeId) {
                $pdo->prepare("
                    INSERT INTO college_courses 
                    (id, college_id, course_name, course_level, duration_years, total_fee, semester_fee, annual_fee, seats_available, specializations, eligibility_criteria, application_fee, emi_available) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([
                    generateUUID(), $collegeId, $data['course_name'], 
                    $data['course_level'] ?? 'UG', $data['duration_years'] ?? null,
                    $data['total_fee'] ?? null, $data['semester_fee'] ?? null,
                    $data['annual_fee'] ?? null, $data['seats_available'] ?? null,
                    $data['specializations'] ?? null, $data['eligibility_criteria'] ?? null,
                    $data['application_fee'] ?? null, $data['emi_available'] ?? 0
                ]);
            }
            break;
        case 'placements':
            if (isset($data['placement_year']) && $collegeId) {
                $topRecruiters = '[]';
                if (!empty($data['top_recruiters'])) {
                    if (is_array($data['top_recruiters'])) {
                        $topRecruiters = json_encode($data['top_recruiters']);
                    } else {
                        $arr = array_filter(array_map('trim', explode(',', (string)$data['top_recruiters'])));
                        $jsonArr = [];
                        foreach ($arr as $r) {
                            $jsonArr[] = ['name' => $r];
                        }
                        $topRecruiters = json_encode($jsonArr);
                    }
                }
                $pdo->prepare("
                    INSERT INTO college_placements 
                    (id, college_id, placement_year, avg_package_lpa, highest_package_lpa, median_package_lpa, placement_percentage, students_placed, top_recruiters) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([
                    generateUUID(), $collegeId, (int)$data['placement_year'],
                    $data['avg_package_lpa'] ?? null, $data['highest_package_lpa'] ?? null,
                    $data['median_package_lpa'] ?? null, $data['placement_percentage'] ?? null,
                    $data['total_placed'] ?? $data['students_placed'] ?? null,
                    $topRecruiters
                ]);
            }
            break;
        case 'cutoffs':
            if (isset($data['exam_id']) && $collegeId) {
                $pdo->prepare("
                    INSERT INTO college_cutoffs 
                    (id, college_id, exam_id, course_id, category, year, opening_rank, closing_rank, quota) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([
                    generateUUID(), $collegeId, $data['exam_id'],
                    $data['course_id'] ?? null, $data['category'] ?? 'General',
                    (int)$data['year'], $data['opening_rank'] ?? null,
                    $data['closing_rank'] ?? null, $data['quota'] ?? null
                ]);
            }
            break;
        case 'seat_matrix':
            if (isset($data['course_id']) && $collegeId) {
                $pdo->prepare("
                    INSERT INTO seat_matrix 
                    (college_id, course_id, category, total_seats, year, source) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ")->execute([
                    $collegeId, $data['course_id'], $data['category'],
                    (int)$data['total_seats'] ?? 0, (int)($data['year'] ?? date('Y')),
                    $data['source'] ?? 'Portal'
                ]);
            }
            break;
    }

    $pdo->prepare("UPDATE college_submissions SET status='approved',reviewed_by=?,reviewed_at=NOW() WHERE id=?")
        ->execute([$adminId, $sub['id']]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $id = $_POST['submission_id'] ?? '';
    $accountId = $_POST['account_id'] ?? '';

    if ($act === 'approve' && $id) {
        $stmt = $pdo->prepare("SELECT s.*, a.institute_name, a.email FROM college_submissions s LEFT JOIN college_accounts a ON s.account_id = a.id WHERE s.id = ?");
        $stmt->execute([$id]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sub) {
            applySubmissionApproval($pdo, $sub, $_SESSION['admin_id']);

            // Send Confirmation Email
            if (!empty($sub['email'])) {
                $toEmail = $sub['email'];
                $toName = $sub['institute_name'] ?: 'Institute';
                $subject = "Submission Approved - AdmissionSeason";
                $typeLabel = ucfirst(str_replace('_', ' ', $sub['submission_type']));
                $html = "
                <div style='font-family:sans-serif; max-width:600px; padding:20px; border:1px solid #e2e8f0; border-radius:10px;'>
                    <h2 style='color:#15803d;'>Updates Approved & Published</h2>
                    <p>Dear <strong>{$toName}</strong>,</p>
                    <p>We are pleased to inform you that your request to update the <strong>{$typeLabel}</strong> details has been verified and successfully approved by our team.</p>
                    <p>The changes are now live on your college profile page.</p>
                    <br>
                    <p style='color:#64748b; font-size:11px;'>Regards,<br>Team AdmissionSeason</p>
                </div>";
                sendCollegeAccountEmail($toEmail, $toName, $subject, $html);
            }
            $msg = 'Submission approved, applied to database, and email sent.';
        }
    }

    if ($act === 'approve_all' && $accountId) {
        $stmt = $pdo->prepare("SELECT s.*, a.institute_name, a.email FROM college_submissions s LEFT JOIN college_accounts a ON s.account_id = a.id WHERE s.account_id = ? AND s.status = 'pending'");
        $stmt->execute([$accountId]);
        $pendingList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($pendingList)) {
            $approvedTypes = [];
            $accountEmail = '';
            $instituteName = '';

            foreach ($pendingList as $sub) {
                applySubmissionApproval($pdo, $sub, $_SESSION['admin_id']);
                $approvedTypes[] = $sub['submission_type'];
                $accountEmail = $sub['email'];
                $instituteName = $sub['institute_name'];
            }

            // Send Single Combined Confirmation Email
            if (!empty($accountEmail)) {
                $toEmail = $accountEmail;
                $toName = $instituteName ?: 'Institute';
                $subject = "All Submissions Approved - AdmissionSeason";
                
                $itemsHtml = '';
                foreach (array_unique($approvedTypes) as $t) {
                    $itemsHtml .= "<li><strong>" . ucfirst(str_replace('_', ' ', $t)) . "</strong> details</li>";
                }
                
                $html = "
                <div style='font-family:sans-serif; max-width:600px; padding:20px; border:1px solid #e2e8f0; border-radius:10px;'>
                    <h2 style='color:#15803d;'>Updates Approved & Published</h2>
                    <p>Dear <strong>{$toName}</strong>,</p>
                    <p>We are pleased to inform you that your requests to update the following details have been verified and successfully approved by our team:</p>
                    <ul style='line-height: 1.6; margin: 16px 0; padding-left: 20px;'>
                        {$itemsHtml}
                    </ul>
                    <p>All updates are now live on your college profile page.</p>
                    <br>
                    <p style='color:#64748b; font-size:11px;'>Regards,<br>Team AdmissionSeason</p>
                </div>";
                sendCollegeAccountEmail($toEmail, $toName, $subject, $html);
            }
            $msg = 'All pending submissions approved, applied to database, and a single confirmation email sent.';
        }
    }

    if ($act === 'reject' && $id) {
        $reason = trim($_POST['rejection_reason'] ?? '');
        
        $stmt = $pdo->prepare("SELECT s.*, a.institute_name, a.email FROM college_submissions s LEFT JOIN college_accounts a ON s.account_id = a.id WHERE s.id = ?");
        $stmt->execute([$id]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo->prepare("UPDATE college_submissions SET status='rejected',admin_note=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?")
            ->execute([$reason ?: 'Rejected by admin', $_SESSION['admin_id'], $id]);

        // Send Rejection Email
        if ($sub && !empty($sub['email'])) {
            $toEmail = $sub['email'];
            $toName = $sub['institute_name'] ?: 'Institute';
            $subject = "Submission Rejected - AdmissionSeason";
            $typeLabel = ucfirst(str_replace('_', ' ', $sub['submission_type']));
            $safeReason = htmlspecialchars($reason ?: 'No specific reason provided.');
            $html = "
            <div style='font-family:sans-serif; max-width:600px; padding:20px; border:1px solid #e2e8f0; border-radius:10px;'>
                <h2 style='color:#b91c1c;'>Updates Rejected</h2>
                <p>Dear <strong>{$toName}</strong>,</p>
                <p>We regret to inform you that your request to update <strong>{$typeLabel}</strong> details has been rejected due to the following reason:</p>
                <div style='background:#fef2f2; border:1px solid #fecaca; padding:12px; border-radius:6px; color:#991b1b; font-weight:bold; margin:16px 0;'>
                    {$safeReason}
                </div>
                <p>Please review and submit a fresh request with correct details.</p>
                <br>
                <p style='color:#64748b; font-size:11px;'>Regards,<br>Team AdmissionSeason</p>
            </div>";
            sendCollegeAccountEmail($toEmail, $toName, $subject, $html);
        }
        $msg = 'Submission rejected and email notification sent.';
    }

    if ($act === 'reject_all' && $accountId) {
        $reason = trim($_POST['rejection_reason'] ?? '');
        
        $stmt = $pdo->prepare("SELECT s.*, a.institute_name, a.email FROM college_submissions s LEFT JOIN college_accounts a ON s.account_id = a.id WHERE s.account_id = ? AND s.status = 'pending'");
        $stmt->execute([$accountId]);
        $pendingList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($pendingList)) {
            $rejectedTypes = [];
            $accountEmail = '';
            $instituteName = '';

            foreach ($pendingList as $sub) {
                $pdo->prepare("UPDATE college_submissions SET status='rejected',admin_note=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?")
                    ->execute([$reason ?: 'Rejected by admin', $_SESSION['admin_id'], $sub['id']]);
                
                $rejectedTypes[] = $sub['submission_type'];
                $accountEmail = $sub['email'];
                $instituteName = $sub['institute_name'];
            }

            // Send Single Combined Rejection Email
            if (!empty($accountEmail)) {
                $toEmail = $accountEmail;
                $toName = $instituteName ?: 'Institute';
                $subject = "Submissions Rejected - AdmissionSeason";
                
                $itemsHtml = '';
                foreach (array_unique($rejectedTypes) as $t) {
                    $itemsHtml .= "<li><strong>" . ucfirst(str_replace('_', ' ', $t)) . "</strong> details</li>";
                }
                
                $safeReason = htmlspecialchars($reason ?: 'No specific reason provided.');
                
                $html = "
                <div style='font-family:sans-serif; max-width:600px; padding:20px; border:1px solid #e2e8f0; border-radius:10px;'>
                    <h2 style='color:#b91c1c;'>Updates Rejected</h2>
                    <p>Dear <strong>{$toName}</strong>,</p>
                    <p>We regret to inform you that your requests to update the following details have been rejected:</p>
                    <ul style='line-height: 1.6; margin: 16px 0; padding-left: 20px;'>
                        {$itemsHtml}
                    </ul>
                    <p><strong>Reason for rejection:</strong></p>
                    <div style='background:#fef2f2; border:1px solid #fecaca; padding:12px; border-radius:6px; color:#991b1b; font-weight:bold; margin:16px 0;'>
                        {$safeReason}
                    </div>
                    <p>Please review and submit a fresh request with correct details.</p>
                    <br>
                    <p style='color:#64748b; font-size:11px;'>Regards,<br>Team AdmissionSeason</p>
                </div>";
                sendCollegeAccountEmail($toEmail, $toName, $subject, $html);
            }
            $msg = 'All pending submissions rejected and a single email notification sent.';
        }
    }

    if ($act === 'approve_selected') {
        $selectedIds = $_POST['selected_ids'] ?? [];
        if (!empty($selectedIds)) {
            $inClause = implode(',', array_fill(0, count($selectedIds), '?'));
            $stmt = $pdo->prepare("SELECT s.*, a.institute_name, a.email FROM college_submissions s LEFT JOIN college_accounts a ON s.account_id = a.id WHERE s.id IN ($inClause) AND s.status = 'pending'");
            $stmt->execute($selectedIds);
            $pendingList = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($pendingList)) {
                $approvedTypes = [];
                $accountEmail = '';
                $instituteName = '';

                foreach ($pendingList as $sub) {
                    applySubmissionApproval($pdo, $sub, $_SESSION['admin_id']);
                    $approvedTypes[] = $sub['submission_type'];
                    $accountEmail = $sub['email'];
                    $instituteName = $sub['institute_name'];
                }

                // Send Single Combined Confirmation Email
                if (!empty($accountEmail)) {
                    $toEmail = $accountEmail;
                    $toName = $instituteName ?: 'Institute';
                    $subject = "Submissions Approved - AdmissionSeason";
                    
                    $itemsHtml = '';
                    foreach (array_unique($approvedTypes) as $t) {
                        $itemsHtml .= "<li><strong>" . ucfirst(str_replace('_', ' ', $t)) . "</strong> details</li>";
                    }
                    
                    $html = "
                    <div style='font-family:sans-serif; max-width:600px; padding:20px; border:1px solid #e2e8f0; border-radius:10px;'>
                        <h2 style='color:#15803d;'>Updates Approved & Published</h2>
                        <p>Dear <strong>{$toName}</strong>,</p>
                        <p>We are pleased to inform you that your request to update the following details has been verified and successfully approved by our team:</p>
                        <ul style='line-height: 1.6; margin: 16px 0; padding-left: 20px;'>
                            {$itemsHtml}
                        </ul>
                        <p>All approved updates are now live on your college profile page.</p>
                        <br>
                        <p style='color:#64748b; font-size:11px;'>Regards,<br>Team AdmissionSeason</p>
                    </div>";
                    sendCollegeAccountEmail($toEmail, $toName, $subject, $html);
                }
                $msg = 'Selected submissions approved, applied to database, and a single confirmation email sent.';
            }
        } else {
            $error = 'No items selected for approval.';
        }
    }

    if ($act === 'reject_selected') {
        $selectedIdsCsv = $_POST['selected_ids_csv'] ?? '';
        $reason = trim($_POST['rejection_reason'] ?? '');
        $selectedIds = array_filter(array_map('trim', explode(',', $selectedIdsCsv)));

        if (!empty($selectedIds)) {
            $inClause = implode(',', array_fill(0, count($selectedIds), '?'));
            $stmt = $pdo->prepare("SELECT s.*, a.institute_name, a.email FROM college_submissions s LEFT JOIN college_accounts a ON s.account_id = a.id WHERE s.id IN ($inClause) AND s.status = 'pending'");
            $stmt->execute($selectedIds);
            $pendingList = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($pendingList)) {
                $rejectedTypes = [];
                $accountEmail = '';
                $instituteName = '';

                foreach ($pendingList as $sub) {
                    $pdo->prepare("UPDATE college_submissions SET status='rejected',admin_note=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?")
                        ->execute([$reason ?: 'Rejected by admin', $_SESSION['admin_id'], $sub['id']]);
                    
                    $rejectedTypes[] = $sub['submission_type'];
                    $accountEmail = $sub['email'];
                    $instituteName = $sub['institute_name'];
                }

                // Send Single Combined Rejection Email
                if (!empty($accountEmail)) {
                    $toEmail = $accountEmail;
                    $toName = $instituteName ?: 'Institute';
                    $subject = "Submissions Rejected - AdmissionSeason";
                    
                    $itemsHtml = '';
                    foreach (array_unique($rejectedTypes) as $t) {
                        $itemsHtml .= "<li><strong>" . ucfirst(str_replace('_', ' ', $t)) . "</strong> details</li>";
                    }
                    
                    $safeReason = htmlspecialchars($reason ?: 'No specific reason provided.');
                    
                    $html = "
                    <div style='font-family:sans-serif; max-width:600px; padding:20px; border:1px solid #e2e8f0; border-radius:10px;'>
                        <h2 style='color:#b91c1c;'>Updates Rejected</h2>
                        <p>Dear <strong>{$toName}</strong>,</p>
                        <p>We regret to inform you that your request to update the following details has been rejected:</p>
                        <ul style='line-height: 1.6; margin: 16px 0; padding-left: 20px;'>
                            {$itemsHtml}
                        </ul>
                        <p><strong>Reason for rejection:</strong></p>
                        <div style='background:#fef2f2; border:1px solid #fecaca; padding:12px; border-radius:6px; color:#991b1b; font-weight:bold; margin:16px 0;'>
                            {$safeReason}
                        </div>
                        <p>Please review and submit a fresh request with correct details.</p>
                        <br>
                        <p style='color:#64748b; font-size:11px;'>Regards,<br>Team AdmissionSeason</p>
                    </div>";
                    sendCollegeAccountEmail($toEmail, $toName, $subject, $html);
                }
                $msg = 'Selected submissions rejected and a single email notification sent.';
            }
        } else {
            $error = 'No items selected for rejection.';
        }
    }
}

$subs = $pdo->query("
    SELECT s.*, a.institute_name, a.email
    FROM college_submissions s
    LEFT JOIN college_accounts a ON s.account_id = a.id
    WHERE s.status != 'draft'
    ORDER BY FIELD(s.status,'pending','approved','rejected'), s.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$typeLabels = ['profile'=>'College Profile','courses'=>'Course','placements'=>'Placement','cutoffs'=>'Cutoff','seat_matrix'=>'Seat Matrix','facilities'=>'Facilities','faqs'=>'FAQs'];

function renderField($data, $fieldName, $label) {
    echo '<tr>';
    echo '<td style="font-weight:700; width:220px; color:#475569; text-transform:capitalize; padding:8px; border-bottom:1px solid #e2e8f0; background:#f8fafc; font-size:0.75rem;">' . htmlspecialchars($label) . '</td>';
    echo '<td style="color:#0f172a; padding:8px; border-bottom:1px solid #e2e8f0; font-size:0.78rem; font-weight:600;">';
    if (!isset($data[$fieldName]) || $data[$fieldName] === '' || $data[$fieldName] === null) {
        echo '<span style="color:#ef4444; font-style:italic; font-weight:700;">[Empty / Not Updated]</span>';
    } else {
        $val = $data[$fieldName];
        if (is_array($val)) {
            echo '<pre style="margin:0; font-family:monospace; white-space:pre-wrap; background:#f1f5f9; padding:6px; border-radius:4px; font-size:0.75rem;">' . htmlspecialchars(json_encode($val, JSON_PRETTY_PRINT)) . '</pre>';
        } else {
            echo htmlspecialchars((string)$val);
        }
    }
    echo '</td>';
    echo '</tr>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>College Submissions | AdmissionSeason Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/admin-responsive.css">
<style>
    body { background-color: #F8FAFC; margin: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .admin-layout { display: flex; min-height: 100vh; }
    
    /* Sidebar styles */
    .sidebar { width: 260px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; transition: transform 0.3s ease; }
    .sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .sidebar-header .logo { font-size: 1.2rem; color: #f8fafc; display:flex; align-items:center; gap:8px; font-weight:700; text-decoration:none; }
    .sidebar-nav { padding: 16px 0; flex: 1; }
    .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.6); transition: all 0.2s; font-size:0.95rem; text-decoration:none; }
    .sidebar-nav a:hover, .sidebar-nav a.active { color: #fff; background: rgba(255,255,255,0.05); border-left: 3px solid #19376D; }
    .sidebar-nav a i { font-size: 1.2rem; }
    .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #0f172a; padding: 4px; }
    
    .main-content { flex: 1; margin-left: 260px; display: flex; flex-direction: column; }
    
    /* Top Header */
    .topbar { height: 64px; background: #fff; border-bottom: 1px solid rgba(15,23,42,0.08); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 40; }
    .header-left { display: flex; align-items: center; gap: 16px; }
    .env-badge { background: rgba(11,36,71,0.04); color: #0B2447; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; border: 1px solid rgba(11,36,71,0.04); }
    .header-right { display: flex; align-items: center; gap: 16px; }
    .avatar { width: 32px; height: 32px; border-radius: 50%; background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size:0.85rem; cursor:pointer; }
    
    .content-area { padding: 24px; display: flex; flex-direction: column; gap: 24px; box-sizing: border-box; }
    .msg{padding: 12px 16px; border-radius: 10px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; font-size: .82rem; margin-bottom: 20px;}
    
    .card{background:#fff; border:1px solid rgba(15,23,42,0.08); border-radius:14px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); overflow-x:auto;}
    table.subs-table { width: 100%; border-collapse: collapse; font-size: .85rem; min-width: 500px; }
    table.subs-table th { text-align: left; padding: 12px; background: #f8fafc; color: #64748b; font-weight: 700; border-bottom: 2px solid #e2e8f0; font-size: .72rem; text-transform: uppercase; }
    table.subs-table td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    
    .badge{display:inline-flex; padding:4px 8px; border-radius:6px; font-size:.7rem; font-weight:700;}
    .badge-green{background:#dcfce7; color:#166534;}
    .badge-yellow{background:#fef3c7; color:#92400e;}
    .badge-red{background:#fef2f2; color:#991b1b;}
    .badge-blue{background:#eff6ff; color:#1d4ed8;}
    
    .btn{padding:8px 14px; border:none; border-radius:8px; font-size:.78rem; font-weight:700; cursor:pointer; font-family:inherit; transition:all 0.2s;}
    .btn-green{background:#16a34a; color:#fff;}.btn-green:hover{background:#15803d;}
    .btn-red{background:#dc2626; color:#fff;}.btn-red:hover{background:#b91c1c;}
    .btn-sm{padding:6px 10px; font-size:.72rem;}
    .btn-ghost{background:#f1f5f9; color:#334155;}
    .btn-ghost:hover{background:#e2e8f0;}

    .modal-bg{display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:200; align-items:center; justify-content:center;}
    .modal-bg.show{display:flex;}
    .modal{background:#fff; border-radius:14px; padding:24px; width:100%; max-width:500px;}
    .modal h3{font-size:1.1rem; font-weight:700; margin-bottom:12px; color:#0f172a;}
    .modal textarea{width:100%; padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:.82rem; font-family:inherit; margin-bottom:12px;}
    .modal .btns{display:flex; gap:8px; justify-content:flex-end;}
    
    .data-preview{background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:20px; font-size:.78rem; color:#475569; max-height:480px; overflow-y:auto; margin-top:8px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);}
    .section-header { font-size: 0.85rem; font-weight: 800; color: #1e293b; background: #e2e8f0; padding: 6px 12px; margin-top: 14px; margin-bottom: 6px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em; }

    .filters{display:flex; gap:10px; margin-bottom:20px;}
    .filters select{padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:.85rem; font-family:inherit; background:#fff;}

    @media(max-width:768px){
        .sidebar{transform:translateX(-100%);transition:transform .3s}.sidebar.open{transform:translateX(0)}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:90}.sidebar-overlay.show{display:block}
        .main-content{margin-left:0}.topbar{height:56px;padding:0 12px;justify-content:space-between}
        .mobile-menu-btn{display:block!important}.content-area{padding:12px}
        .page-header{flex-direction:column;align-items:flex-start}.page-header h2{font-size:1.2rem}
        .filters{flex-direction:column;gap:6px}.filters select{width:100%}
        table{font-size:.75rem}th,td{padding:8px 10px}
        .card{overflow-x:auto}
    }
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <button class="mobile-menu-btn" id="mobile-menu-btn"><i class="ph ph-list"></i></button>
            <div class="header-left">
                <div style="font-weight:700; color:#0f172a;">College Submissions Review Dashboard</div>
            </div>
            <div class="header-right">
                <div class="avatar">A</div>
            </div>
        </header>

        <div class="content-area">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <h2 style="font-size:1.3rem; font-weight:800; color:#0f172a;">College Submissions Queue</h2>
            </div>

            <?php if($msg): ?><div class="msg"><?=$msg?></div><?php endif;?>

            <div class="filters">
              <select id="filterStatus" onchange="filterTable()">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
              <select id="filterType" onchange="filterTable()">
                <option value="">All Types</option>
                <option value="profile">Profile</option>
                <option value="courses">Courses</option>
                <option value="placements">Placements</option>
                <option value="cutoffs">Cutoffs</option>
                <option value="seat_matrix">Seat Matrix</option>
              </select>
            </div>

            <?php
            $grouped = [];
            foreach ($subs as $s) {
                $grouped[$s['account_id']]['institute_name'] = $s['institute_name'];
                $grouped[$s['account_id']]['email'] = $s['email'];
                $grouped[$s['account_id']]['items'][] = $s;
            }
            ?>

            <?php if (empty($grouped)): ?>
            <div class="card"><div class="empty" style="text-align:center;color:#94a3b8;padding:32px">No submissions yet.</div></div>
            <?php endif; ?>
            
            <?php foreach($grouped as $accountId => $group): 
                $pendingCount = count(array_filter($group['items'], function($x) { return $x['status'] === 'pending'; }));
            ?>
            <form method="POST" id="form-<?=$accountId?>" style="margin: 0; padding: 0;">
            <input type="hidden" name="action" id="action-<?=$accountId?>" value="approve_selected">
            <div class="card" style="margin-bottom: 24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h3 style="margin:0; font-size:1.15rem; font-weight:800; color:#0f172a;"><?=htmlspecialchars($group['institute_name'] ?: 'Unknown Institute')?></h3>
                        <span style="font-size:0.8rem; color:#64748b;"><?=htmlspecialchars($group['email'] ?: '')?></span>
                    </div>
                    <?php if ($pendingCount > 0): ?>
                    <div style="display:flex; gap:8px;">
                        <button type="submit" onclick="return submitApproveSelected('<?=$accountId?>', event)" class="btn btn-green btn-sm"><i class="ph ph-check-square"></i> Approve Selected</button>
                        <button type="button" onclick="submitRejectSelected('<?=$accountId?>', event)" class="btn btn-red btn-sm"><i class="ph ph-x-square"></i> Reject Selected</button>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="overflow-x:auto;">
                <table class="subs-table">
                <thead>
                <tr>
                  <th style="width: 40px;">
                    <?php if ($pendingCount > 0): ?>
                    <input type="checkbox" onclick="toggleSelectAll('<?=$accountId?>', this)">
                    <?php endif; ?>
                  </th>
                  <th>Submission Type</th><th>Status</th><th>Submitted Date</th><th style="text-align:right;">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach($group['items'] as $s): ?>
                <tr class="sub-row" data-status="<?=$s['status']?>" data-type="<?=$s['submission_type']?>">
                  <td>
                    <?php if($s['status']==='pending'): ?>
                    <input type="checkbox" name="selected_ids[]" value="<?=$s['id']?>" class="sub-cb-<?=$accountId?>">
                    <?php endif; ?>
                  </td>
                  <td><span class="badge badge-blue"><?=($typeLabels[$s['submission_type']] ?? $s['submission_type'])?></span></td>
                  <td><span class="badge <?=($s['status']==='approved'?'badge-green':($s['status']==='rejected'?'badge-red':'badge-yellow'))?>"><?=ucfirst($s['status'])?></span></td>
                  <td><?=date('d M Y', strtotime($s['created_at']))?></td>
                  <td style="text-align:right;">
                    <a href="submission_details.php?id=<?=$s['id']?>" class="btn btn-ghost btn-sm" style="text-decoration:none;"><i class="ph ph-eye"></i> View Details</a>
                    <?php if($s['status']==='pending'): ?>
                    <button type="button" class="btn btn-green btn-ghost btn-sm" style="color:#16a34a; border: 1px solid #16a34a; margin-left:4px;" onclick="approveSingleDirect('<?=$s['id']?>')"><i class="ph ph-check"></i> Approve Single</button>
                    <button type="button" class="btn btn-red btn-ghost btn-sm" style="color:#dc2626; border: 1px solid #dc2626; margin-left:4px;" onclick="showReject('<?=$s['id']?>')"><i class="ph ph-x"></i> Reject Single</button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                </table>
                </div>
            </div>
            </form>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<div class="modal-bg" id="rejectModal">
<div class="modal">
  <h3 id="rejectModalTitle">Reject Submission</h3>
  <form method="POST">
    <input type="hidden" name="action" id="rejectAction" value="reject">
    <input type="hidden" name="submission_id" id="rejectId">
    <input type="hidden" name="account_id" id="rejectAccountId">
    <input type="hidden" name="selected_ids_csv" id="rejectSelectedIds">
    <textarea name="rejection_reason" rows="3" placeholder="Reason for rejection (optional)"></textarea>
    <div class="btns">
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('rejectModal').classList.remove('show')">Cancel</button>
      <button type="submit" class="btn btn-red">Reject</button>
    </div>
  </form>
</div>
</div>

<form id="singleActionForm" method="POST" style="display:none;">
    <input type="hidden" name="action" id="singleAction" value="">
    <input type="hidden" name="submission_id" id="singleSubmissionId" value="">
</form>

<script>
function toggleSelectAll(accountId, masterCb) {
    const checkboxes = document.querySelectorAll('.sub-cb-' + accountId);
    checkboxes.forEach(cb => cb.checked = masterCb.checked);
}

function submitApproveSelected(accountId, event) {
    const checked = document.querySelectorAll('.sub-cb-' + accountId + ':checked');
    if (checked.length === 0) {
        alert('Please select at least one submission to approve.');
        event.preventDefault();
        return false;
    }
    if (!confirm('Approve the selected updates?')) {
        event.preventDefault();
        return false;
    }
    document.getElementById('action-' + accountId).value = 'approve_selected';
    return true;
}

function submitRejectSelected(accountId, event) {
    event.preventDefault();
    const checked = document.querySelectorAll('.sub-cb-' + accountId + ':checked');
    if (checked.length === 0) {
        alert('Please select at least one submission to reject.');
        return false;
    }
    const ids = Array.from(checked).map(cb => cb.value).join(',');
    
    document.getElementById('rejectAction').value = 'reject_selected';
    document.getElementById('rejectId').value = '';
    document.getElementById('rejectAccountId').value = '';
    document.getElementById('rejectSelectedIds').value = ids;
    document.getElementById('rejectModalTitle').textContent = 'Reject Selected Submissions';
    document.getElementById('rejectModal').classList.add('show');
}

function approveSingleDirect(id) {
    if(confirm('Approve this submission?')) {
        document.getElementById('singleAction').value = 'approve';
        document.getElementById('singleSubmissionId').value = id;
        document.getElementById('singleActionForm').submit();
    }
}

function showReject(id){
  document.getElementById('rejectAction').value = 'reject';
  document.getElementById('rejectId').value = id;
  document.getElementById('rejectAccountId').value = '';
  document.getElementById('rejectSelectedIds').value = '';
  document.getElementById('rejectModalTitle').textContent = 'Reject Submission';
  document.getElementById('rejectModal').classList.add('show');
}

function showRejectAll(accountId){
  document.getElementById('rejectAction').value = 'reject_all';
  document.getElementById('rejectId').value = '';
  document.getElementById('rejectAccountId').value = accountId;
  document.getElementById('rejectSelectedIds').value = '';
  document.getElementById('rejectModalTitle').textContent = 'Reject All Submissions';
  document.getElementById('rejectModal').classList.add('show');
}

function filterTable(){
  var st=document.getElementById('filterStatus').value;
  var ty=document.getElementById('filterType').value;
  document.querySelectorAll('.sub-row').forEach(function(r){
    var show=true;
    if(st && r.dataset.status!==st) show=false;
    if(ty && r.dataset.type!==ty) show=false;
    r.style.display=show?'':'none';
  });
}
</script>
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
