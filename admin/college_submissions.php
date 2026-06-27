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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $id = $_POST['submission_id'] ?? '';

    if ($act === 'approve' && $id) {
        $stmt = $pdo->prepare("SELECT s.*, a.institute_name, a.email FROM college_submissions s LEFT JOIN college_accounts a ON s.account_id = a.id WHERE s.id = ?");
        $stmt->execute([$id]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sub) {
            $data = json_decode($sub['data_json'], true);
            $collegeId = $sub['college_id'];
            $type = $sub['submission_type'];

            // Apply data to main tables based on type
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
                            (id, college_id, course_name, course_slug, course_level, duration_years, total_fee, semester_fee, annual_fee, seats_available, specializations, eligibility_criteria, application_fee, emi_available, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                        ")->execute([
                            generateUUID(), $collegeId, $data['course_name'], 
                            strtolower(preg_replace('/[^a-z0-9]+/','-', $data['course_name'])),
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
                        $pdo->prepare("
                            INSERT INTO college_placements 
                            (id, college_id, placement_year, avg_package_lpa, highest_package_lpa, median_package_lpa, placement_percentage, total_students, total_placed, top_recruiters) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ")->execute([
                            generateUUID(), $collegeId, (int)$data['placement_year'],
                            $data['avg_package_lpa'] ?? null, $data['highest_package_lpa'] ?? null,
                            $data['median_package_lpa'] ?? null, $data['placement_percentage'] ?? null,
                            $data['total_students'] ?? null, $data['total_placed'] ?? null,
                            $data['top_recruiters'] ?? ''
                        ]);
                    }
                    break;
                case 'cutoffs':
                    if (isset($data['exam_id']) && $collegeId) {
                        $pdo->prepare("
                            INSERT INTO college_cutoffs 
                            (id, college_id, exam_id, year, category, quota, opening_rank, closing_rank, course_name) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ")->execute([
                            generateUUID(), $collegeId, $data['exam_id'], (int)$data['year'],
                            $data['category'] ?? 'General', $data['quota'] ?? 'All India',
                            $data['opening_rank'] ?? null, $data['closing_rank'] ?? null,
                            $data['course_name'] ?? ''
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
                ->execute([$_SESSION['admin_id'], $id]);

            // Send Confirmation Email
            if (!empty($sub['email'])) {
                $toEmail = $sub['email'];
                $toName = $sub['institute_name'] ?: 'Institute';
                $subject = "Submission Approved - AdmissionSeason";
                $typeLabel = ucfirst(str_replace('_', ' ', $type));
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

    if ($act === 'reject' && $id) {
        $reason = trim($_POST['rejection_reason'] ?? '');
        
        $stmt = $pdo->prepare("SELECT s.*, a.institute_name, a.email FROM college_submissions s LEFT JOIN college_accounts a ON s.account_id = a.id WHERE s.id = ?");
        $stmt->execute([$id]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo->prepare("UPDATE college_submissions SET status='rejected',rejection_reason=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?")
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
}

$subs = $pdo->query("
    SELECT s.*, a.institute_name, a.email
    FROM college_submissions s
    LEFT JOIN college_accounts a ON s.account_id = a.id
    ORDER BY FIELD(s.status,'pending','approved','rejected'), s.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$typeLabels = ['profile'=>'College Profile','courses'=>'Course','placements'=>'Placement','cutoffs'=>'Cutoff','seat_matrix'=>'Seat Matrix','facilities'=>'Facilities','faqs'=>'FAQs'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>College Submissions – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f1f5f9;padding:24px}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.page-header h1{font-size:1.4rem;font-weight:800;color:#0B2447}
.msg{padding:12px 16px;border-radius:10px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;font-size:.82rem;margin-bottom:20px}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;margin-bottom:16px}
table{width:100%;border-collapse:collapse;font-size:.8rem}
th{text-align:left;padding:10px 12px;background:#f8fafc;color:#64748b;font-weight:600;border-bottom:1px solid #e2e8f0;font-size:.72rem;text-transform:uppercase}
td{padding:10px 12px;border-bottom:1px solid #f1f5f9;color:#334155}
.badge{display:inline-flex;padding:3px 8px;border-radius:5px;font-size:.65rem;font-weight:600}
.badge-green{background:#dcfce7;color:#166534}
.badge-yellow{background:#fef3c7;color:#92400e}
.badge-red{background:#fef2f2;color:#991b1b}
.badge-blue{background:#eff6ff;color:#1d4ed8}
.btn{padding:6px 12px;border:none;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;font-family:inherit}
.btn-green{background:#16a34a;color:#fff}.btn-green:hover{background:#15803d}
.btn-red{background:#dc2626;color:#fff}.btn-red:hover{background:#b91c1c}
.btn-sm{padding:4px 8px;font-size:.7rem}
.btn-ghost{background:#f1f5f9;color:#334155}
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center}
.modal-bg.show{display:flex}
.modal{background:#fff;border-radius:14px;padding:24px;width:100%;max-width:500px}
.modal h3{font-size:1rem;font-weight:700;margin-bottom:12px;color:#0B2447}
.modal textarea{width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.82rem;font-family:inherit;margin-bottom:12px}
.modal .btns{display:flex;gap:8px;justify-content:flex-end}
.data-preview{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;font-size:.78rem;color:#475569;max-height:120px;overflow:auto;margin-top:4px;white-space:pre-wrap;word-break:break-all}
.filters{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.filters select,.filters input{padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:.8rem;font-family:inherit}
</style>
</head>
<body>
<div class="page-header">
  <h1><i class="ph ph-inbox"></i> College Submissions</h1>
  <a href="/ADMISSION/admin/dashboard.php" style="font-size:.82rem;color:#19376D;text-decoration:none"><i class="ph ph-arrow-left"></i> Back</a>
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

<div class="card">
<table>
<thead>
<tr><th>Institute</th><th>Type</th><th>Data</th><th>Status</th><th>Date</th><th>Actions</th></tr>
</thead>
<tbody>
<?php if (empty($subs)): ?>
<tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:32px">No submissions yet.</td></tr>
<?php endif; ?>
<?php foreach($subs as $s): ?>
<tr class="sub-row" data-status="<?=$s['status']?>" data-type="<?=$s['submission_type']?>">
  <td style="font-weight:600"><?=htmlspecialchars($s['institute_name'] ?? 'Unknown')?></td>
  <td><span class="badge badge-blue"><?=($typeLabels[$s['submission_type']] ?? $s['submission_type'])?></span></td>
  <td>
    <button class="btn btn-ghost btn-sm" onclick="this.nextElementSibling.classList.toggle('show');this.nextElementSibling.style.display=this.nextElementSibling.style.display==='block'?'none':'block'">View Details</button>
    <div class="data-preview" style="display:none; background:#fff; border:1px solid #cbd5e1; padding:16px; border-radius:10px; max-height:400px; overflow-y:auto; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1)">
      <?php
      $subData = json_decode($s['data_json'], true);
      if (is_array($subData)) {
          echo '<table style="width:100%; font-size:0.75rem; border-collapse:collapse; margin-top:0;">';
          foreach ($subData as $key => $val) {
              echo '<tr>';
              echo '<td style="font-weight:700; width:150px; color:#1e293b; text-transform:capitalize; padding:6px 8px; border-bottom:1px solid #f1f5f9; background:none;">' . htmlspecialchars(str_replace('_', ' ', $key)) . '</td>';
              echo '<td style="color:#475569; padding:6px 8px; border-bottom:1px solid #f1f5f9;">';
              if (is_array($val)) {
                  echo '<pre style="margin:0; font-family:inherit; white-space:pre-wrap; background:#f8fafc; padding:6px; border-radius:4px;">' . htmlspecialchars(json_encode($val, JSON_PRETTY_PRINT)) . '</pre>';
              } else {
                  echo htmlspecialchars((string)$val);
              }
              echo '</td>';
              echo '</tr>';
          }
          echo '</table>';
      } else {
          echo htmlspecialchars($s['data_json']);
      }
      ?>
    </div>
  </td>
  <td><span class="badge <?=($s['status']==='approved'?'badge-green':($s['status']==='rejected'?'badge-red':'badge-yellow'))?>"><?=ucfirst($s['status'])?></span></td>
  <td><?=date('d M Y', strtotime($s['created_at']))?></td>
  <td>
    <?php if($s['status']==='pending'): ?>
    <form method="POST" style="display:inline"><input type="hidden" name="action" value="approve"><input type="hidden" name="submission_id" value="<?=$s['id']?>"><button class="btn btn-green btn-sm"><i class="ph ph-check"></i> Approve</button></form>
    <button class="btn btn-red btn-sm" onclick="showReject('<?=$s['id']?>')"><i class="ph ph-x"></i> Reject</button>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach;?>
</tbody>
</table>
</div>

<div class="modal-bg" id="rejectModal">
<div class="modal">
  <h3>Reject Submission</h3>
  <form method="POST">
    <input type="hidden" name="action" value="reject">
    <input type="hidden" name="submission_id" id="rejectId">
    <textarea name="rejection_reason" rows="3" placeholder="Reason for rejection (optional)"></textarea>
    <div class="btns">
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('rejectModal').classList.remove('show')">Cancel</button>
      <button type="submit" class="btn btn-red">Reject</button>
    </div>
  </form>
</div>
</div>

<script>
function showReject(id){
  document.getElementById('rejectId').value=id;
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
</body>
</html>
