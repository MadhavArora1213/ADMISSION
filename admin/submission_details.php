<?php
session_start();
require_once __DIR__ . '/db.php';
if (empty($_SESSION['admin_id'])) { header('Location: /ADMISSION/admin/index.php'); exit; }

$id = $_GET['id'] ?? '';
$msg = '';
$error = '';

// Load submission
$stmt = $pdo->prepare("SELECT s.*, a.institute_name, a.email FROM college_submissions s LEFT JOIN college_accounts a ON s.account_id = a.id WHERE s.id = ?");
$stmt->execute([$id]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sub) {
    echo "Submission not found.";
    exit;
}

$collegeId = $sub['college_id'];
$subType = $sub['submission_type'];
$subData = json_decode($sub['data_json'], true) ?: [];

// Fetch current live college data for context
$college = []; $contact = []; $media = []; $content = []; $admissions = []; $infrastructure = []; $hostels = []; $seo = [];
$courses = []; $placements = []; $cutoffs = []; $seatMatrix = []; $faculty = []; $qna = []; $updates = []; $rankings = []; $reviews = [];

if ($collegeId) {
    try { $college = $pdo->query("SELECT * FROM colleges WHERE id=" . $pdo->quote($collegeId))->fetch(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $contact = $pdo->query("SELECT * FROM college_contacts WHERE college_id=" . $pdo->quote($collegeId))->fetch(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $media = $pdo->query("SELECT * FROM college_media WHERE college_id=" . $pdo->quote($collegeId) . " AND image_type IS NULL")->fetch(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $content = $pdo->query("SELECT * FROM college_content WHERE college_id=" . $pdo->quote($collegeId))->fetch(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $admissions = $pdo->query("SELECT * FROM college_admissions WHERE college_id=" . $pdo->quote($collegeId))->fetch(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $infrastructure = $pdo->query("SELECT * FROM college_infrastructure WHERE college_id=" . $pdo->quote($collegeId))->fetch(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $hostels = $pdo->query("SELECT * FROM college_hostels WHERE college_id=" . $pdo->quote($collegeId))->fetch(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $seo = $pdo->query("SELECT * FROM seo_meta WHERE page_type='college' AND page_id=" . $pdo->quote($collegeId))->fetch(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $courses = $pdo->query("SELECT * FROM college_courses WHERE college_id=" . $pdo->quote($collegeId))->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $placements = $pdo->query("SELECT * FROM college_placements WHERE college_id=" . $pdo->quote($collegeId))->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $cutoffs = $pdo->query("SELECT cc.*, e.exam_name FROM college_cutoffs cc LEFT JOIN exams e ON e.id=cc.exam_id WHERE cc.college_id=" . $pdo->quote($collegeId))->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $seatMatrix = $pdo->query("SELECT sm.*, cc.course_name FROM seat_matrix sm LEFT JOIN college_courses cc ON sm.course_id = cc.id WHERE sm.college_id=" . $pdo->quote($collegeId))->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $faculty = $pdo->query("SELECT * FROM college_faculty WHERE college_id=" . $pdo->quote($collegeId))->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $qna = $pdo->query("SELECT * FROM college_qna WHERE college_id=" . $pdo->quote($collegeId))->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $updates = $pdo->query("SELECT * FROM college_updates WHERE college_id=" . $pdo->quote($collegeId))->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $rankings = $pdo->query("SELECT * FROM rankings WHERE college_id=" . $pdo->quote($collegeId))->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
    try { $reviews = $pdo->query("SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.college_id=" . $pdo->quote($collegeId))->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch(Exception $e){}
}

function displayField($submittedVal, $liveVal, $label) {
    $hasChange = ($submittedVal !== null && $submittedVal !== $liveVal);
    echo '<div style="padding:12px; border-bottom:1px solid #f1f5f9; display:flex; font-size:0.85rem; background:' . ($hasChange ? '#fef9c3' : 'none') . ';">';
    echo '<div style="width:240px; font-weight:700; color:#475569;">' . htmlspecialchars($label) . '</div>';
    echo '<div style="flex:1;">';
    if ($hasChange) {
        echo '<div style="color:#ef4444; text-decoration:line-through; font-size:0.78rem;">Live: ' . htmlspecialchars((string)($liveVal ?: '[Empty]')) . '</div>';
        echo '<div style="color:#15803d; font-weight:700;">Proposed: ' . htmlspecialchars((string)($submittedVal ?: '[Empty]')) . '</div>';
    } else {
        echo '<span style="color:#0f172a;">' . htmlspecialchars((string)($liveVal ?: '[Empty]')) . '</span>';
        echo ' <span style="color:#94a3b8; font-size:0.75rem;">(Unchanged)</span>';
    }
    echo '</div>';
    echo '</div>';
}

function displayJSONListField($submittedVal, $liveVal, $label) {
    $hasChange = ($submittedVal !== null && json_encode($submittedVal) !== json_encode($liveVal));
    echo '<div style="padding:12px; border-bottom:1px solid #f1f5f9; display:flex; font-size:0.85rem; background:' . ($hasChange ? '#fef9c3' : 'none') . ';">';
    echo '<div style="width:240px; font-weight:700; color:#475569;">' . htmlspecialchars($label) . '</div>';
    echo '<div style="flex:1;">';
    if ($hasChange) {
        echo '<div style="color:#ef4444; font-size:0.78rem;">Live: ' . htmlspecialchars(is_array($liveVal) ? implode(', ', $liveVal) : (string)$liveVal) . '</div>';
        echo '<div style="color:#15803d; font-weight:700;">Proposed: ' . htmlspecialchars(is_array($submittedVal) ? implode(', ', $submittedVal) : (string)$submittedVal) . '</div>';
    } else {
        echo '<span style="color:#0f172a;">' . htmlspecialchars(is_array($liveVal) ? implode(', ', $liveVal) : ((string)$liveVal ?: '[Empty]')) . '</span>';
        echo ' <span style="color:#94a3b8; font-size:0.75rem;">(Unchanged)</span>';
    }
    echo '</div>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Submission Details Review | AdmissionSeason Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/admin-responsive.css">
<style>
    body { background-color: #F8FAFC; margin: 0; font-family: 'Inter', sans-serif; }
    .admin-layout { display: flex; min-height: 100vh; }
    
    /* Sidebar */
    .sidebar { width: 260px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 100; transition: transform 0.3s ease; }
    .sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .sidebar-header .logo { font-size: 1.2rem; color: #f8fafc; display:flex; align-items:center; gap:8px; font-weight:700; text-decoration:none; }
    .sidebar-nav { padding: 16px 0; flex: 1; }
    .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.6); transition: all 0.2s; font-size:0.95rem; text-decoration:none; }
    .sidebar-nav a:hover, .sidebar-nav a.active { color: #fff; background: rgba(255,255,255,0.05); border-left: 3px solid #19376D; }
    
    .main-content { flex: 1; margin-left: 260px; display: flex; flex-direction: column; min-width: 0; overflow-x: hidden; }
    .topbar { height: 64px; background: #fff; border-bottom: 1px solid rgba(15,23,42,0.08); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 40; }
    .content-area { padding: 24px; display: flex; flex-direction: column; gap: 20px; }
    
    /* Tabs Layout */
    .tabs-nav { display: flex; gap: 8px; border-bottom: 2px solid #e2e8f0; overflow-x: auto; padding-bottom: 12px; margin-bottom: 8px; scrollbar-width: thin; width: 100%; max-width: 100%; }
    .tabs-nav::-webkit-scrollbar { height: 6px; }
    .tabs-nav::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
    .tabs-nav::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .tabs-nav::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    .tab-btn { padding: 10px 18px; border: none; background: none; font-size: 0.85rem; font-weight: 700; color: #64748b; cursor: pointer; border-radius: 8px; transition: all 0.2s; white-space: nowrap; }
    .tab-btn:hover { background: #e2e8f0; color: #0f172a; }
    .tab-btn.active { background: #19376D; color: #fff; }
    
    .tab-content { display: none; background: #fff; border: 1px solid rgba(15,23,42,0.08); border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .tab-content.active { display: block; }
    
    .review-action-card { background: #fff; border: 1px solid rgba(15,23,42,0.08); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); display: flex; justify-content: space-between; align-items: center; }
    .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
    .btn-green { background: #16a34a; color: #fff; }
    .btn-green:hover { background: #15803d; }
    .btn-red { background: #dc2626; color: #fff; }
    .btn-red:hover { background: #b91c1c; }
    
    .change-indicator { background: #fef9c3; border: 1px solid #fef08a; color: #854d0e; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; margin-left: 8px; display: inline-block; }
</style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    
    <main class="main-content">
        <header class="topbar">
            <div style="font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <a href="college_submissions.php" style="color:#64748b; text-decoration:none;"><i class="ph ph-arrow-left"></i> Submissions</a>
                <span>/</span>
                <span>Reviewing: <?=htmlspecialchars($sub['institute_name'])?></span>
            </div>
            <div style="font-size:0.85rem; color:#64748b;">Submission Status: <strong style="color:#d97706;"><?=ucfirst($sub['status'])?></strong></div>
        </header>
        
        <div class="content-area">
            <?php if($sub['status'] === 'pending'): ?>
            <div class="review-action-card">
                <div>
                    <h3 style="font-size:0.95rem; font-weight:700; margin-bottom:4px;">Pending Action Decisions</h3>
                    <p style="font-size:0.8rem; color:#64748b; margin:0;">Please review the proposed updates highlighted in yellow tabs and fields below before deciding.</p>
                </div>
                <div style="display:flex; gap:12px; align-items:center;">
                    <form method="POST" action="college_submissions.php" style="display:inline;">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="submission_id" value="<?=$id?>">
                        <button type="submit" class="btn btn-green"><i class="ph ph-check"></i> Approve & Publish</button>
                    </form>
                    <button class="btn btn-red" onclick="document.getElementById('rejectModal').style.display='flex';"><i class="ph ph-x"></i> Reject Submission</button>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="tabs-nav">
                <button class="tab-btn active" onclick="switchTab('college-info')">College Info</button>
                <button class="tab-btn" onclick="switchTab('identity')">Identity & Contact <?php if($subType==='profile' && (isset($subData['name']) || isset($subData['email']))) echo '<span class="change-indicator">Updated</span>'; ?></button>
                <button class="tab-btn" onclick="switchTab('infrastructure')">Infrastructure <?php if($subType==='profile' && isset($subData['about_text'])) echo '<span class="change-indicator">Updated</span>'; ?></button>
                <button class="tab-btn" onclick="switchTab('seo')">SEO & Publish <?php if($subType==='profile' && isset($subData['meta_title'])) echo '<span class="change-indicator">Updated</span>'; ?></button>
                <button class="tab-btn" onclick="switchTab('courses')">Courses & Fees <?php if($subType==='courses') echo '<span class="change-indicator">Updated</span>'; ?></button>
                <button class="tab-btn" onclick="switchTab('reviews')">Reviews</button>
                <button class="tab-btn" onclick="switchTab('admissions')">Admissions <?php if($subType==='profile' && isset($subData['admission_process'])) echo '<span class="change-indicator">Updated</span>'; ?></button>
                <button class="tab-btn" onclick="switchTab('placements')">Placements <?php if($subType==='placements') echo '<span class="change-indicator">Updated</span>'; ?></button>
                <button class="tab-btn" onclick="switchTab('cutoffs')">Cut-Offs <?php if($subType==='cutoffs') echo '<span class="change-indicator">Updated</span>'; ?></button>
                <button class="tab-btn" onclick="switchTab('seat-matrix')">Seat Matrix <?php if($subType==='seat_matrix') echo '<span class="change-indicator">Updated</span>'; ?></button>
                <button class="tab-btn" onclick="switchTab('rankings')">Rankings</button>
                <button class="tab-btn" onclick="switchTab('gallery')">Gallery</button>
                <button class="tab-btn" onclick="switchTab('faculty')">Faculty</button>
                <button class="tab-btn" onclick="switchTab('compare')">Compare</button>
                <button class="tab-btn" onclick="switchTab('qna')">Q&A</button>
                <button class="tab-btn" onclick="switchTab('news')">News</button>
                <button class="tab-btn" onclick="switchTab('categories')">Course Categories</button>
            </div>
            
            <!-- College Info -->
            <div class="tab-content active" id="college-info">
                <h4 style="margin-bottom:16px;">General College Profile Summary</h4>
                <?php
                displayField($subData['name'] ?? null, $college['name'] ?? '', "College Name");
                displayField($subData['college_type'] ?? null, $college['college_type'] ?? '', "College Type");
                displayField($subData['ownership'] ?? null, $college['ownership'] ?? '', "Ownership");
                displayField($subData['founded_year'] ?? null, $college['founded_year'] ?? '', "Founded Year");
                displayField($subData['naac_grade'] ?? null, $college['naac_grade'] ?? '', "NAAC Grade");
                displayField($subData['ranking_nirf'] ?? null, $college['ranking_nirf'] ?? '', "NIRF Rank");
                ?>
            </div>
            
            <!-- Identity & Contact -->
            <div class="tab-content" id="identity">
                <h4 style="margin-bottom:16px;">Identity & Contact Info</h4>
                <?php
                displayField($subData['type_label'] ?? null, $college['type_label'] ?? '', "Display Label");
                displayField($subData['university_id'] ?? null, $college['university_id'] ?? '', "University ID");
                displayField($subData['autonomous'] ?? null, $college['autonomous'] ?? '', "Autonomous");
                displayField($subData['ugc_approved'] ?? null, $college['ugc_approved'] ?? '', "UGC Approved");
                displayField($subData['aicte_approved'] ?? null, $college['aicte_approved'] ?? '', "AICTE Approved");
                displayField($subData['total_students'] ?? null, $college['total_students'] ?? '', "Total Students");
                displayField($subData['total_faculty'] ?? null, $college['total_faculty'] ?? '', "Total Faculty");
                displayField($subData['campus_area_acres'] ?? null, $college['campus_area_acres'] ?? '', "Campus Area (Acres)");
                displayField($subData['campus_type'] ?? null, $college['campus_type'] ?? '', "Campus Type");
                displayField($subData['state_id'] ?? null, $college['state_id'] ?? '', "State ID");
                displayField($subData['city_id'] ?? null, $college['city_id'] ?? '', "City ID");
                displayField($subData['logo_url'] ?? null, $media['logo_url'] ?? '', "Logo Image Path");
                displayField($subData['cover_image_url'] ?? null, $media['cover_image_url'] ?? '', "Cover Image Path");
                displayField($subData['email'] ?? null, $contact['email'] ?? '', "Contact Email");
                displayField($subData['phone'] ?? null, $contact['phone'] ?? '', "Contact Phone");
                displayField($subData['address'] ?? null, $contact['address'] ?? '', "Address");
                displayField($subData['website_url'] ?? null, $contact['website_url'] ?? '', "Website URL");
                displayField($subData['pincode'] ?? null, $contact['pincode'] ?? '', "Pincode");
                ?>
            </div>
            
            <!-- Infrastructure -->
            <div class="tab-content" id="infrastructure">
                <h4 style="margin-bottom:16px;">Infrastructure Details & Hostels</h4>
                <?php
                displayField($subData['about_text'] ?? null, $content['about_text'] ?? '', "About Text");
                displayJSONListField($subData['highlights_json'] ?? null, json_decode($content['highlights_json'] ?? '[]', true), "Highlights");
                displayJSONListField($subData['accreditations_json'] ?? null, json_decode($content['accreditations_json'] ?? '[]', true), "Accreditations");
                displayJSONListField($subData['awards_json'] ?? null, json_decode($content['awards_json'] ?? '[]', true), "Awards");
                displayField($subData['library'] ?? null, $infrastructure['library'] ?? '', "Library");
                displayField($subData['auditorium'] ?? null, $infrastructure['auditorium'] ?? '', "Auditorium");
                displayField($subData['cafeteria'] ?? null, $infrastructure['cafeteria'] ?? '', "Cafeteria");
                displayField($subData['wifi'] ?? null, $infrastructure['wifi'] ?? '', "WiFi");
                displayField($subData['medical_facility'] ?? null, $infrastructure['medical_facility'] ?? '', "Medical Facility");
                displayField($subData['transport'] ?? null, $infrastructure['transport'] ?? '', "Transport");
                displayField($subData['hostel_available'] ?? null, $hostels['hostel_available'] ?? '', "Hostels Available");
                displayField($subData['hostel_type'] ?? null, $hostels['hostel_type'] ?? '', "Hostel Type");
                displayField($subData['hostel_capacity'] ?? null, $hostels['hostel_capacity'] ?? '', "Hostel Capacity");
                displayField($subData['hostel_fee_annual'] ?? null, $hostels['hostel_fee_annual'] ?? '', "Annual Hostel Fee");
                ?>
            </div>
            
            <!-- SEO & Publish -->
            <div class="tab-content" id="seo">
                <h4 style="margin-bottom:16px;">SEO Meta & Publish Settings</h4>
                <?php
                displayField($subData['publish_status'] ?? null, $college['publish_status'] ?? '', "Publish Status");
                displayField($subData['meta_title'] ?? null, $seo['meta_title'] ?? '', "Meta Title");
                displayField($subData['meta_description'] ?? null, $seo['meta_description'] ?? '', "Meta Description");
                displayField($subData['og_image_url'] ?? null, $seo['og_image_url'] ?? '', "OG Image URL");
                displayField($subData['canonical_url'] ?? null, $seo['canonical_url'] ?? '', "Canonical URL");
                displayField($subData['schema_markup'] ?? null, $seo['schema_markup'] ?? '', "Schema Markup");
                ?>
            </div>
            
            <!-- Courses -->
            <div class="tab-content" id="courses">
                <?php if($subType === 'courses'): ?>
                <div style="background:#fef9c3; border:1px solid #fef08a; padding:16px; border-radius:8px; margin-bottom:16px;">
                    <h5 style="font-weight:700; color:#854d0e; margin-bottom:6px;">PROPOSED NEW COURSE SUBMISSION:</h5>
                    <p style="margin:2px 0;"><strong>Course Name:</strong> <?=htmlspecialchars($subData['course_name']??'')?></p>
                    <p style="margin:2px 0;"><strong>Level:</strong> <?=htmlspecialchars($subData['course_level']??'')?></p>
                    <p style="margin:2px 0;"><strong>Duration:</strong> <?=htmlspecialchars($subData['duration_years']??'')?> Yrs</p>
                    <p style="margin:2px 0;"><strong>Fee:</strong> ₹<?=number_format($subData['total_fee']??0, 2)?></p>
                    <p style="margin:2px 0;"><strong>Seats:</strong> <?=htmlspecialchars($subData['seats_available']??'0')?></p>
                </div>
                <?php endif; ?>
                <h4 style="margin-bottom:12px;">Existing Courses List (<?=count($courses)?>)</h4>
                <table style="width:100%; border-collapse:collapse;">
                    <thead><tr style="background:#f8fafc;"><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Course Name</th><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Level</th><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Fee</th></tr></thead>
                    <tbody>
                    <?php foreach($courses as $c): ?>
                        <tr><td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?=htmlspecialchars($c['course_name'])?></td><td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?=htmlspecialchars($c['course_level'])?></td><td style="padding:10px; border-bottom:1px solid #f1f5f9;">₹<?=number_format($c['total_fee'], 2)?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Reviews -->
            <div class="tab-content" id="reviews">
                <h4 style="margin-bottom:16px;">College Reviews & Student Comments (<?=count($reviews)?>)</h4>
                <?php if($reviews): ?>
                    <?php foreach($reviews as $r): ?>
                        <div style="border:1px solid #e2e8f0; padding:12px; border-radius:8px; margin-bottom:12px;">
                            <div style="font-weight:700;"><?=htmlspecialchars($r['user_name']?:'Student')?> (Overall Rating: <?=$r['overall_rating']?>)</div>
                            <div style="font-style:italic; font-size:0.8rem; margin:4px 0;">"<?=htmlspecialchars($r['review_title'])?>"</div>
                            <p style="margin:0; font-size:0.85rem; color:#475569;"><?=nl2br(htmlspecialchars($r['review_body']))?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?><p style="color:#64748b; font-style:italic;">No reviews posted yet.</p><?php endif; ?>
            </div>
            
            <!-- Admissions -->
            <div class="tab-content" id="admissions">
                <h4 style="margin-bottom:16px;">Admissions Rules & Processes</h4>
                <?php
                displayField($subData['admission_process'] ?? null, $admissions['admission_process'] ?? '', "Process Description");
                displayJSONListField($subData['accepted_exams'] ?? null, json_decode($admissions['accepted_exams'] ?? '[]', true), "Accepted Exams");
                displayField($subData['admission_start_date'] ?? null, $admissions['admission_start_date'] ?? '', "Admissions Start Date");
                displayField($subData['admission_end_date'] ?? null, $admissions['admission_end_date'] ?? '', "Admissions End Date");
                ?>
            </div>
            
            <!-- Placements -->
            <div class="tab-content" id="placements">
                <?php if($subType === 'placements'): ?>
                <div style="background:#fef9c3; border:1px solid #fef08a; padding:16px; border-radius:8px; margin-bottom:16px;">
                    <h5 style="font-weight:700; color:#854d0e; margin-bottom:6px;">PROPOSED PLACEMENT RECORD:</h5>
                    <p style="margin:2px 0;"><strong>Year:</strong> <?=htmlspecialchars($subData['placement_year']??'')?></p>
                    <p style="margin:2px 0;"><strong>Avg Package:</strong> ₹<?=htmlspecialchars($subData['avg_package_lpa']??'')?> LPA</p>
                    <p style="margin:2px 0;"><strong>Highest Package:</strong> ₹<?=htmlspecialchars($subData['highest_package_lpa']??'')?> LPA</p>
                    <p style="margin:2px 0;"><strong>Placed %:</strong> <?=htmlspecialchars($subData['placement_percentage']??'')?>%</p>
                </div>
                <?php endif; ?>
                <h4 style="margin-bottom:12px;">Existing Placements (<?=count($placements)?>)</h4>
                <table style="width:100%; border-collapse:collapse;">
                    <thead><tr style="background:#f8fafc;"><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Year</th><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Avg LPA</th><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Highest LPA</th></tr></thead>
                    <tbody>
                    <?php foreach($placements as $pl): ?>
                        <tr><td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?=$pl['placement_year']?></td><td style="padding:10px; border-bottom:1px solid #f1f5f9;">₹<?=$pl['avg_package_lpa']?> LPA</td><td style="padding:10px; border-bottom:1px solid #f1f5f9;">₹<?=$pl['highest_package_lpa']?> LPA</td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cut-Offs -->
            <div class="tab-content" id="cutoffs">
                <?php if($subType === 'cutoffs'): ?>
                <div style="background:#fef9c3; border:1px solid #fef08a; padding:16px; border-radius:8px; margin-bottom:16px;">
                    <h5 style="font-weight:700; color:#854d0e; margin-bottom:6px;">PROPOSED CUTOFF DATA:</h5>
                    <p style="margin:2px 0;"><strong>Exam ID:</strong> <?=htmlspecialchars($subData['exam_id']??'')?></p>
                    <p style="margin:2px 0;"><strong>Year:</strong> <?=htmlspecialchars($subData['year']??'')?></p>
                    <p style="margin:2px 0;"><strong>Opening Rank:</strong> <?=htmlspecialchars($subData['opening_rank']??'')?></p>
                    <p style="margin:2px 0;"><strong>Closing Rank:</strong> <?=htmlspecialchars($subData['closing_rank']??'')?></p>
                    <p style="margin:2px 0;"><strong>Course:</strong> <?=htmlspecialchars($subData['course_name']??'')?></p>
                </div>
                <?php endif; ?>
                <h4 style="margin-bottom:12px;">Existing Cut-Offs (<?=count($cutoffs)?>)</h4>
                <table style="width:100%; border-collapse:collapse;">
                    <thead><tr style="background:#f8fafc;"><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Exam</th><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Year</th><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Closing Rank</th></tr></thead>
                    <tbody>
                    <?php foreach($cutoffs as $cf): ?>
                        <tr><td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?=htmlspecialchars($cf['exam_name']?:'Entrance')?></td><td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?=$cf['year']?></td><td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?=$cf['closing_rank']?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Seat Matrix -->
            <div class="tab-content" id="seat-matrix">
                <?php if($subType === 'seat_matrix'): ?>
                <div style="background:#fef9c3; border:1px solid #fef08a; padding:16px; border-radius:8px; margin-bottom:16px;">
                    <h5 style="font-weight:700; color:#854d0e; margin-bottom:6px;">PROPOSED SEAT MATRIX RECORD:</h5>
                    <p style="margin:2px 0;"><strong>Course ID:</strong> <?=htmlspecialchars($subData['course_id']??'')?></p>
                    <p style="margin:2px 0;"><strong>Category:</strong> <?=htmlspecialchars($subData['category']??'')?></p>
                    <p style="margin:2px 0;"><strong>Total Seats:</strong> <?=htmlspecialchars($subData['total_seats']??'')?></p>
                    <p style="margin:2px 0;"><strong>Year:</strong> <?=htmlspecialchars($subData['year']??'')?></p>
                </div>
                <?php endif; ?>
                <h4 style="margin-bottom:12px;">Existing Seat Matrix (<?=count($seatMatrix)?>)</h4>
                <table style="width:100%; border-collapse:collapse;">
                    <thead><tr style="background:#f8fafc;"><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Course</th><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Category</th><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Total Seats</th></tr></thead>
                    <tbody>
                    <?php foreach($seatMatrix as $sm): ?>
                        <tr><td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?=htmlspecialchars($sm['course_name']?:'Course')?></td><td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?=htmlspecialchars($sm['category'])?></td><td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?=$sm['total_seats']?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Rankings -->
            <div class="tab-content" id="rankings">
                <h4 style="margin-bottom:12px;">Colleges Rankings (<?=count($rankings)?>)</h4>
                <?php if($rankings): ?>
                <table style="width:100%; border-collapse:collapse;">
                    <thead><tr style="background:#f8fafc;"><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Body</th><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Year</th><th style="padding:10px; border-bottom:1px solid #e2e8f0; text-align:left;">Rank</th></tr></thead>
                    <tbody>
                    <?php foreach($rankings as $rk): ?>
                        <tr><td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?=htmlspecialchars($rk['ranking_body'])?></td><td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?=$rk['ranking_year']?></td><td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?=$rk['rank_position']?:'-'?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?><p style="color:#64748b; font-style:italic;">No rankings listed yet.</p><?php endif; ?>
            </div>

            <!-- Gallery -->
            <div class="tab-content" id="gallery">
                <h4 style="margin-bottom:12px;">Media & Documents</h4>
                <p style="color:#64748b; font-style:italic;">No changes proposed to Media files in this submission.</p>
            </div>

            <!-- Faculty -->
            <div class="tab-content" id="faculty">
                <h4 style="margin-bottom:12px;">Faculty Members (<?=count($faculty)?>)</h4>
                <?php if($faculty): ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
                        <?php foreach($faculty as $f): ?>
                            <div style="border:1px solid #e2e8f0; padding:12px; border-radius:8px; text-align:center;">
                                <div style="font-weight:700;"><?=htmlspecialchars($f['faculty_name'])?></div>
                                <div style="font-size:0.8rem; color:#64748b;"><?=htmlspecialchars($f['designation'])?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?><p style="color:#64748b; font-style:italic;">No faculty members listed yet.</p><?php endif; ?>
            </div>

            <!-- Compare -->
            <div class="tab-content" id="compare">
                <h4 style="margin-bottom:12px;">Compare Page Configuration</h4>
                <p style="color:#64748b;">Compare engine is enabled globally. Colleges can run comparison reports in their dashboard panel.</p>
            </div>

            <!-- Q&A -->
            <div class="tab-content" id="qna">
                <h4 style="margin-bottom:12px;">Student Questions & Answers (<?=count($qna)?>)</h4>
                <?php if($qna): ?>
                    <?php foreach($qna as $q): ?>
                        <div style="border:1px solid #e2e8f0; padding:12px; border-radius:8px; margin-bottom:12px;">
                            <div style="font-weight:700; color:#19376D;">Q: <?=htmlspecialchars($q['question_text'])?></div>
                            <div style="font-size:0.85rem; margin-top:6px; color:#475569;">A: <?=htmlspecialchars($q['answer_text'] ?: 'Not Answered yet')?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?><p style="color:#64748b; font-style:italic;">No Q&As recorded.</p><?php endif; ?>
            </div>

            <!-- News -->
            <div class="tab-content" id="news">
                <h4 style="margin-bottom:12px;">News & Updates Thread (<?=count($updates)?>)</h4>
                <?php if($updates): ?>
                    <?php foreach($updates as $u): ?>
                        <div style="border:1px solid #e2e8f0; padding:12px; border-radius:8px; margin-bottom:12px;">
                            <div style="font-weight:700;"><?=htmlspecialchars($u['title'])?></div>
                            <div style="font-size:0.75rem; color:#64748b;"><?=date('d M Y', strtotime($u['created_at']))?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?><p style="color:#64748b; font-style:italic;">No news articles/updates posted yet.</p><?php endif; ?>
            </div>

            <!-- Course Categories -->
            <div class="tab-content" id="categories">
                <h4 style="margin-bottom:12px;">Global Course Categories Reference</h4>
                <p style="color:#64748b;">Global course categories are managed directly by super admin in the core database configurations.</p>
            </div>
            
        </div>
    </main>
</div>

<!-- Reject Modal -->
<div class="modal-bg" id="rejectModal">
<div class="modal">
  <h3>Reject Submission Request</h3>
  <form method="POST" action="college_submissions.php">
    <input type="hidden" name="action" value="reject">
    <input type="hidden" name="submission_id" value="<?=$id?>">
    <textarea name="rejection_reason" rows="4" placeholder="Please state the reason for rejecting this profile/entity update request..." required></textarea>
    <div class="btns">
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('rejectModal').style.display='none';">Cancel</button>
      <button type="submit" class="btn btn-red">Reject</button>
    </div>
  </form>
</div>
</div>

<script>
function switchTab(tabId) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(function(content) {
        content.classList.remove('active');
    });
    // Deactivate all buttons
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.classList.remove('active');
    });
    // Show current tab and highlight button
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>
</body>
</html>
