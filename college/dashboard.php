<?php
require_once __DIR__ . '/auth.php';
$account = collegeAuth();
$collegeId = $account['college_id'];
$tab = $_GET['tab'] ?? 'overview';

$msg = '';
$error = '';

function getValue($arr, $key, $default = '') {
    return isset($arr[$key]) ? htmlspecialchars((string)$arr[$key]) : $default;
}
function jsonToLines($jsonStr, $isObject = false) {
    if (empty($jsonStr)) return '';
    $data = json_decode($jsonStr, true);
    if (!is_array($data)) return htmlspecialchars($jsonStr);
    if ($isObject) {
        $lines = [];
        foreach($data as $k => $v) $lines[] = "$k: $v";
        return htmlspecialchars(implode("\n", $lines));
    }
    return htmlspecialchars(implode("\n", $data));
}
function linesToJsonList($text) {
    if (empty(trim($text))) return null;
    $lines = array_filter(array_map('trim', explode("\n", $text)), 'strlen');
    return empty($lines) ? null : json_encode(array_values($lines));
}
function linesToJsonObject($text) {
    if (empty(trim($text))) return null;
    $lines = array_filter(array_map('trim', explode("\n", $text)), 'strlen');
    $obj = [];
    foreach($lines as $line) {
        $parts = explode(':', $line, 2);
        if (count($parts) == 2) {
            $obj[trim($parts[0])] = trim($parts[1]);
        }
    }
    return empty($obj) ? null : json_encode($obj);
}
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}
function createSubmission($pdo, $accountId, $collegeId, $type, $dataArray) {
    $uuid = generateUUID();
    $stmt = $pdo->prepare("INSERT INTO college_submissions (id, account_id, college_id, submission_type, data_json, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    return $stmt->execute([$uuid, $accountId, $collegeId, $type, json_encode($dataArray)]);
}

// Fetch Reference Data
$states = [];
try { $states = $pdo->query("SELECT * FROM states ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
$universities = [];
try { $universities = $pdo->query("SELECT * FROM universities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
$exams = [];
try { $exams = $pdo->query("SELECT id, exam_name FROM exams WHERE status='active' ORDER BY exam_name ASC")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
$global_categories = [];
try { $global_categories = $pdo->query("SELECT c1.*, c2.category_name as parent_name FROM course_categories c1 LEFT JOIN course_categories c2 ON c1.parent_category_id = c2.id ORDER BY c1.sort_order ASC, c1.category_name ASC")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
$all_colleges = [];
try { $all_colleges = $pdo->query("SELECT id, name FROM colleges WHERE id != " . $pdo->quote($collegeId) . " ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}

// Handle Form POST Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $collegeId) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_identity') {
        try {
            $upload_dir = '../uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $logo_url = $_POST['existing_logo_url'] ?? '';
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == 0) {
                $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
                $filename = 'college_logo_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_dir . $filename)) {
                    $logo_url = 'uploads/' . $filename;
                }
            }

            $cover_image_url = $_POST['existing_cover_image_url'] ?? '';
            if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] == 0) {
                $ext = pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION);
                $filename = 'college_cover_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['cover_file']['tmp_name'], $upload_dir . $filename)) {
                    $cover_image_url = 'uploads/' . $filename;
                }
            }

            $subData = [
                'name' => $_POST['name'],
                'college_type' => $_POST['college_type'] ?: null,
                'ownership' => $_POST['ownership'] ?: null,
                'type_label' => $_POST['type_label'] ?: null,
                'founded_year' => $_POST['founded_year'] ?: null,
                'university_id' => $_POST['university_id'] ?: null,
                'naac_grade' => $_POST['naac_grade'] ?: null,
                'ranking_nirf' => $_POST['ranking_nirf'] ?: null,
                'autonomous' => isset($_POST['autonomous']) ? 1 : 0,
                'ugc_approved' => isset($_POST['ugc_approved']) ? 1 : 0,
                'aicte_approved' => isset($_POST['aicte_approved']) ? 1 : 0,
                'total_students' => $_POST['total_students'] ?: null,
                'total_faculty' => $_POST['total_faculty'] ?: null,
                'campus_area_acres' => $_POST['campus_area_acres'] ?: null,
                'campus_type' => $_POST['campus_type'] ?: null,
                'state_id' => $_POST['state_id'] ?: null,
                'city_id' => $_POST['city_id'] ?: null,
                'logo_url' => $logo_url,
                'cover_image_url' => $cover_image_url,
                'email' => $_POST['contact_email'] ?: null,
                'phone' => $_POST['contact_phone'] ?: null,
                'address' => $_POST['address'] ?: null,
                'latitude' => $_POST['latitude'] ?: null,
                'longitude' => $_POST['longitude'] ?: null,
                'website_url' => $_POST['official_website'] ?: null,
                'pincode' => $_POST['pincode'] ?: null,
                'google_maps_embed_url' => $_POST['google_maps_embed_url'] ?: null,
                'nearest_railway_km' => $_POST['nearest_railway_km'] ?: null,
                'nearest_airport_km' => $_POST['nearest_airport_km'] ?: null
            ];

            createSubmission($pdo, $account['id'], $collegeId, 'profile', $subData);
            $msg = 'Identity & Contact changes submitted for verification. Admin approval is pending.';
        } catch (Exception $e) {
            $error = 'Error saving details: ' . $e->getMessage();
        }
    }

    if ($action === 'update_about') {
        try {
            $subData = [
                'about_text' => $_POST['about_text'],
                'highlights_json' => linesToJsonList($_POST['highlights_json']),
                'accreditations_json' => linesToJsonList($_POST['accreditations_json']),
                'rankings_json' => linesToJsonObject($_POST['rankings_json']),
                'awards_json' => linesToJsonList($_POST['awards_json']),
                'library' => isset($_POST['library']) ? 1 : 0,
                'auditorium' => isset($_POST['auditorium']) ? 1 : 0,
                'cafeteria' => isset($_POST['cafeteria']) ? 1 : 0,
                'wifi' => isset($_POST['wifi']) ? 1 : 0,
                'medical_facility' => isset($_POST['medical_facility']) ? 1 : 0,
                'transport' => isset($_POST['transport']) ? 1 : 0,
                'ev_charging' => isset($_POST['ev_charging']) ? 1 : 0,
                'solar_power' => isset($_POST['solar_power']) ? 1 : 0,
                'sports_facilities' => linesToJsonList($_POST['sports_facilities']),
                'labs' => linesToJsonList($_POST['labs']),
                'hostel_available' => isset($_POST['hostel_available']) ? 1 : 0,
                'hostel_type' => $_POST['hostel_type'] ?: null,
                'hostel_capacity' => $_POST['hostel_capacity'] ?: null,
                'hostel_fee_annual' => $_POST['hostel_fee_annual'] ?: null,
                'mess_available' => isset($_POST['mess_available']) ? 1 : 0,
                'mess_type' => $_POST['mess_type'] ?: null,
                'ac_available' => isset($_POST['ac_available']) ? 1 : 0,
                'laundry_available' => isset($_POST['laundry_available']) ? 1 : 0
            ];

            createSubmission($pdo, $account['id'], $collegeId, 'profile', $subData);
            $msg = 'About & Infrastructure updates submitted for verification. Admin approval is pending.';
        } catch (Exception $e) {
            $error = 'Error saving details: ' . $e->getMessage();
        }
    }

    if ($action === 'update_admissions') {
        try {
            $subData = [
                'admission_process' => $_POST['admission_process'],
                'accepted_exams' => linesToJsonList($_POST['accepted_exams']),
                'admission_start_date' => $_POST['admission_start_date'] ?: null,
                'admission_end_date' => $_POST['admission_end_date'] ?: null,
                'merit_based' => isset($_POST['merit_based']) ? 1 : 0,
                'direct_admission' => isset($_POST['direct_admission']) ? 1 : 0,
                'management_quota_seats' => $_POST['management_quota_seats'] ?: 0,
                'nri_quota_seats' => $_POST['nri_quota_seats'] ?: 0,
                'lateral_entry_available' => isset($_POST['lateral_entry_available']) ? 1 : 0,
                'application_mode' => $_POST['application_mode'] ?: null
            ];

            createSubmission($pdo, $account['id'], $collegeId, 'profile', $subData);
            $msg = 'Admissions updates submitted for verification. Admin approval is pending.';
        } catch (Exception $e) {
            $error = 'Error saving details: ' . $e->getMessage();
        }
    }

    if ($action === 'update_seo') {
        try {
            $upload_dir = '../uploads/';
            $og_image_url = $_POST['existing_og_image_url'] ?? '';
            if (isset($_FILES['og_image_file']) && $_FILES['og_image_file']['error'] == 0) {
                $ext = pathinfo($_FILES['og_image_file']['name'], PATHINFO_EXTENSION);
                $filename = 'college_og_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['og_image_file']['tmp_name'], $upload_dir . $filename)) {
                    $og_image_url = 'uploads/' . $filename;
                }
            }

            $subData = [
                'publish_status' => $_POST['publish_status'],
                'meta_title' => $_POST['meta_title'],
                'meta_description' => $_POST['meta_description'],
                'og_image_url' => $og_image_url,
                'canonical_url' => $_POST['canonical_url'],
                'schema_markup' => $_POST['schema_markup'] ?: null,
                'noindex' => isset($_POST['noindex']) ? 1 : 0
            ];

            createSubmission($pdo, $account['id'], $collegeId, 'profile', $subData);
            $msg = 'SEO & publish status updates submitted for verification. Admin approval is pending.';
        } catch (Exception $e) {
            $error = 'Error saving details: ' . $e->getMessage();
        }
    }

    if ($action === 'add_course') {
        try {
            $specializations_json = null;
            if (!empty(trim($_POST['specializations'] ?? ''))) {
                $specs = array_map('trim', explode(',', $_POST['specializations']));
                $specs = array_filter($specs, 'strlen');
                if (!empty($specs)) {
                    $specializations_json = json_encode(array_values($specs));
                }
            }

            $subData = [
                'course_name' => $_POST['course_name'],
                'course_level' => $_POST['course_level'],
                'duration_years' => $_POST['duration_years'] ?: null,
                'total_fee' => $_POST['total_fee'] ?: null,
                'semester_fee' => $_POST['semester_fee'] ?: null,
                'annual_fee' => $_POST['annual_fee'] ?: null,
                'seats_available' => $_POST['seats_available'] ?: null,
                'specializations' => $specializations_json,
                'eligibility_criteria' => $_POST['eligibility_criteria'] ?: null,
                'application_fee' => $_POST['application_fee'] ?: null,
                'emi_available' => isset($_POST['emi_available']) ? 1 : 0
            ];

            createSubmission($pdo, $account['id'], $collegeId, 'courses', $subData);
            $msg = 'Course creation request submitted for verification. Admin approval is pending.';
        } catch (Exception $e) {
            $error = 'Error adding course: ' . $e->getMessage();
        }
    }

    if ($action === 'delete_course') {
        try {
            $stmt = $pdo->prepare("DELETE FROM college_courses WHERE id = ? AND college_id = ?");
            $stmt->execute([$_POST['cc_id'], $collegeId]);
            $msg = 'Course deleted successfully!';
        } catch (Exception $e) {
            $error = 'Error deleting course: ' . $e->getMessage();
        }
    }

    if ($action === 'add_placement') {
        try {
            $subData = [
                'placement_year' => (int)$_POST['placement_year'],
                'avg_package_lpa' => (float)$_POST['avg_package_lpa'] ?: null,
                'highest_package_lpa' => (float)$_POST['highest_package_lpa'] ?: null,
                'median_package_lpa' => (float)$_POST['median_package_lpa'] ?: null,
                'placement_percentage' => (float)$_POST['placement_percentage'] ?: null,
                'total_students' => (int)$_POST['total_students'] ?: null,
                'total_placed' => (int)$_POST['total_placed'] ?: null,
                'top_recruiters' => trim($_POST['top_recruiters'] ?? '')
            ];

            createSubmission($pdo, $account['id'], $collegeId, 'placements', $subData);
            $msg = 'Placement data submission sent for verification. Admin approval is pending.';
        } catch (Exception $e) {
            $error = 'Error adding placement: ' . $e->getMessage();
        }
    }

    if ($action === 'delete_placement') {
        try {
            $stmt = $pdo->prepare("DELETE FROM college_placements WHERE id = ? AND college_id = ?");
            $stmt->execute([$_POST['placement_id'], $collegeId]);
            $msg = 'Placement data deleted!';
        } catch (Exception $e) {
            $error = 'Error deleting placement: ' . $e->getMessage();
        }
    }

    if ($action === 'add_cutoff') {
        try {
            $subData = [
                'exam_id' => $_POST['exam_id'],
                'year' => (int)$_POST['year'],
                'category' => $_POST['category'],
                'quota' => $_POST['quota'],
                'opening_rank' => (int)$_POST['opening_rank'] ?: null,
                'closing_rank' => (int)$_POST['closing_rank'] ?: null,
                'course_name' => trim($_POST['course_name'] ?? '')
            ];

            createSubmission($pdo, $account['id'], $collegeId, 'cutoffs', $subData);
            $msg = 'Cutoff submission sent for verification. Admin approval is pending.';
        } catch (Exception $e) {
            $error = 'Error adding cutoff: ' . $e->getMessage();
        }
    }

    if ($action === 'delete_cutoff') {
        try {
            $stmt = $pdo->prepare("DELETE FROM college_cutoffs WHERE id = ? AND college_id = ?");
            $stmt->execute([$_POST['cutoff_id'], $collegeId]);
            $msg = 'Cutoff deleted successfully!';
        } catch (Exception $e) {
            $error = 'Error deleting cutoff: ' . $e->getMessage();
        }
    }

    if ($action === 'add_seat_matrix') {
        try {
            $subData = [
                'course_id' => $_POST['course_id'],
                'category' => $_POST['category'],
                'total_seats' => (int)$_POST['total_seats'] ?: 0,
                'year' => (int)($_POST['year'] ?? date('Y')),
                'source' => trim($_POST['source'] ?? 'Portal')
            ];

            createSubmission($pdo, $account['id'], $collegeId, 'seat_matrix', $subData);
            $msg = 'Seat matrix submission sent for verification. Admin approval is pending.';
        } catch (Exception $e) {
            $error = 'Error adding seat matrix: ' . $e->getMessage();
        }
    }

    if ($action === 'delete_seat_matrix') {
        try {
            $stmt = $pdo->prepare("DELETE FROM seat_matrix WHERE id = ? AND college_id = ?");
            $stmt->execute([$_POST['seat_matrix_id'], $collegeId]);
            $msg = 'Seat matrix deleted successfully!';
        } catch (Exception $e) {
            $error = 'Error deleting seat matrix: ' . $e->getMessage();
        }
    }

    // Rankings Actions
    if ($action === 'add_ranking') {
        try {
            $ins = $pdo->prepare("
                INSERT INTO rankings 
                (ranking_body, ranking_year, category, college_id, rank_position, rank_band, score, source_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([
                $_POST['ranking_body'],
                (int)$_POST['ranking_year'],
                $_POST['category'],
                $collegeId,
                $_POST['rank_position'] ?: null,
                $_POST['rank_band'] ?: null,
                $_POST['score'] ?: null,
                $_POST['source_url'] ?: null
            ]);
            $msg = 'Ranking added successfully!';
        } catch (Exception $e) {
            $error = 'Error adding ranking: ' . $e->getMessage();
        }
    }

    if ($action === 'delete_ranking') {
        try {
            $stmt = $pdo->prepare("DELETE FROM rankings WHERE id = ? AND college_id = ?");
            $stmt->execute([$_POST['ranking_id'], $collegeId]);
            $msg = 'Ranking deleted!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }

    // Media & Gallery Action handlers
    if ($action === 'add_gallery_media') {
        try {
            $media_type = $_POST['media_type'];
            $image_url = null; $image_type = null;
            $video_url = null; $video_type = null;
            $document_url = null; $document_type = null;
            $tour_360_url = null;

            $final_url = !empty($_POST['url']) ? $_POST['url'] : null;

            if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
                $upload_dir = '../uploads/media/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['media_file']['name']));
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['media_file']['tmp_name'], $target_file)) {
                    $final_url = 'uploads/media/' . $file_name;
                }
            }

            if($media_type == 'image') { $image_url = $final_url; $image_type = $_POST['sub_type'] ?: null; } 
            elseif ($media_type == 'video') { $video_url = $final_url; $video_type = $_POST['sub_type'] ?: null; } 
            elseif ($media_type == 'document') { $document_url = $final_url; $document_type = $_POST['sub_type'] ?: null; } 
            elseif ($media_type == '360') { $tour_360_url = $final_url; }
            
            $stmt = $pdo->prepare("
                INSERT INTO college_media (id, college_id, image_url, image_type, video_url, video_type, document_url, document_type, `360_tour_url`, caption, sort_order) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                generateUUID(), $collegeId, $image_url, $image_type, $video_url, $video_type, $document_url, $document_type, $tour_360_url, $_POST['caption'] ?: null, $_POST['sort_order'] ?: 0
            ]);
            $msg = 'Media added successfully!';
        } catch (Exception $e) {
            $error = 'Error adding media: ' . $e->getMessage();
        }
    }

    if ($action === 'delete_gallery_media') {
        try {
            $stmt = $pdo->prepare("DELETE FROM college_media WHERE id = ? AND college_id = ?");
            $stmt->execute([$_POST['m_id'], $collegeId]);
            $msg = 'Media deleted successfully!';
        } catch (Exception $e) {
            $error = 'Error deleting media: ' . $e->getMessage();
        }
    }

    if ($action === 'update_virtual_tour') {
        try {
            $enabled = isset($_POST['virtual_tour_enabled']) ? 1 : 0;
            $url = !empty($_POST['tour_url']) ? $_POST['tour_url'] : null;
            $chk = $pdo->prepare("SELECT id FROM college_media WHERE college_id = ? AND image_url IS NULL AND video_url IS NULL AND document_url IS NULL LIMIT 1");
            $chk->execute([$collegeId]);
            $row = $chk->fetch();
            if ($row) {
                $pdo->prepare("UPDATE college_media SET virtual_tour_enabled = ?, `360_tour_url` = ? WHERE id = ?")->execute([$enabled, $url, $row['id']]);
            } else {
                $pdo->prepare("INSERT INTO college_media (id, college_id, virtual_tour_enabled, `360_tour_url`) VALUES (?, ?, ?, ?)")->execute([generateUUID(), $collegeId, $enabled, $url]);
            }
            $msg = 'Virtual tour settings updated!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }

    // FAQs Actions
    if ($action === 'add_faq') {
        try {
            $ins = $pdo->prepare("INSERT INTO college_faqs (id, college_id, question_text, answer_text, category, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([generateUUID(), $collegeId, $_POST['question_text'], $_POST['answer_text'], $_POST['category'] ?: 'General', (int)($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0]);
            $msg = 'FAQ added successfully!';
        } catch (Exception $e) {
            $error = 'Error adding FAQ: ' . $e->getMessage();
        }
    }

    if ($action === 'delete_faq') {
        try {
            $pdo->prepare("DELETE FROM college_faqs WHERE id = ? AND college_id = ?")->execute([$_POST['faq_id'], $collegeId]);
            $msg = 'FAQ deleted!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }

    // Faculty Actions
    if ($action === 'add_faculty') {
        try {
            $photo_url = null;
            if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] == 0) {
                $upload_dir = '../uploads/faculty/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $file_name = time() . '_' . basename($_FILES['photo_file']['name']);
                if (move_uploaded_file($_FILES['photo_file']['tmp_name'], $upload_dir . $file_name)) {
                    $photo_url = 'uploads/faculty/' . $file_name;
                }
            }
            $ins = $pdo->prepare("INSERT INTO college_faculty (id, college_id, faculty_name, designation, department, qualification, experience_years, photo_url, research_papers, specialization, phd_from, linkedin_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([generateUUID(), $collegeId, $_POST['faculty_name'], $_POST['designation'], $_POST['department'], $_POST['qualification'], (int)($_POST['experience_years'] ?? 0), $photo_url, (int)($_POST['research_papers'] ?? 0), $_POST['specialization'] ?: null, $_POST['phd_from'] ?: null, $_POST['linkedin_url'] ?: null]);
            $msg = 'Faculty member added successfully!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }

    if ($action === 'delete_faculty') {
        try {
            $pdo->prepare("DELETE FROM college_faculty WHERE id = ? AND college_id = ?")->execute([$_POST['faculty_id'], $collegeId]);
            $msg = 'Faculty member deleted!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }

    // Scholarship Actions
    if ($action === 'add_scholarship') {
        try {
            $ins = $pdo->prepare("INSERT INTO college_scholarships (id, college_id, scholarship_name, scholarship_type, amount, amount_type, eligibility_criteria, renewable, apply_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([generateUUID(), $collegeId, $_POST['scholarship_name'], $_POST['scholarship_type'], $_POST['amount'] ?: null, $_POST['amount_type'] ?: 'fixed', $_POST['eligibility_criteria'] ?: null, isset($_POST['renewable']) ? 1 : 0, $_POST['apply_link'] ?: null]);
            $msg = 'Scholarship added successfully!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }

    if ($action === 'delete_scholarship') {
        try {
            $pdo->prepare("DELETE FROM college_scholarships WHERE id = ? AND college_id = ?")->execute([$_POST['scholarship_id'], $collegeId]);
            $msg = 'Scholarship deleted!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }

    // Updates/News Actions
    if ($action === 'add_update') {
        try {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['title']))) . '-' . time();
            $ins = $pdo->prepare("INSERT INTO college_updates (id, college_id, title, slug, description, update_type, event_date, action_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([generateUUID(), $collegeId, $_POST['title'], $slug, $_POST['description'], $_POST['update_type'], $_POST['event_date'] ?: null, $_POST['action_url'] ?: null, $_POST['status'] ?: 'draft']);
            $msg = 'Update / News published successfully!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }

    if ($action === 'delete_update') {
        try {
            $pdo->prepare("DELETE FROM college_updates WHERE id = ? AND college_id = ?")->execute([$_POST['update_id'], $collegeId]);
            $msg = 'News / Update deleted!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }

    // Student Q&A Answer Actions
    if ($action === 'answer_qna') {
        try {
            $upd = $pdo->prepare("UPDATE college_qna SET answer_text = ?, answered_by_user_id = ?, status = 'approved', updated_at = NOW() WHERE id = ? AND college_id = ?");
            $upd->execute([$_POST['answer_text'], $account['id'], $_POST['qna_id'], $collegeId]);
            $msg = 'Question answered and published!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Fetch all college details
$college = []; $contact = []; $media = []; $content = []; $admissions = []; $infrastructure = []; $hostels = []; $seo = [];
$courses = []; $placements = []; $cutoffs = []; $seatMatrix = []; $leads = []; $pendingSubs = [];
$galleryMedia = []; $faqs = []; $faculty = []; $scholarships = []; $updates = []; $qna = [];
$rankings = []; $reviews = [];

if ($collegeId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM colleges WHERE id=?");
        $stmt->execute([$collegeId]);
        $college = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM college_contacts WHERE college_id=?");
        $stmt->execute([$collegeId]);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM college_media WHERE college_id=? AND image_type IS NULL");
        $stmt->execute([$collegeId]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM college_content WHERE college_id=?");
        $stmt->execute([$collegeId]);
        $content = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM college_admissions WHERE college_id=?");
        $stmt->execute([$collegeId]);
        $admissions = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM college_infrastructure WHERE college_id=?");
        $stmt->execute([$collegeId]);
        $infrastructure = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM college_hostels WHERE college_id=?");
        $stmt->execute([$collegeId]);
        $hostels = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM seo_meta WHERE page_type='college' AND page_id=?");
        $stmt->execute([$collegeId]);
        $seo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch(Exception $e){}
    try {
        $s=$pdo->prepare("SELECT * FROM college_courses WHERE college_id=? ORDER BY course_name");
        $s->execute([$collegeId]);
        $courses=$s->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $s=$pdo->prepare("SELECT * FROM college_placements WHERE college_id=? ORDER BY placement_year DESC");
        $s->execute([$collegeId]);
        $placements=$s->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $s=$pdo->prepare("SELECT cc.*, e.exam_name FROM college_cutoffs cc LEFT JOIN exams e ON e.id=cc.exam_id WHERE cc.college_id=? ORDER BY cc.year DESC");
        $s->execute([$collegeId]);
        $cutoffs=$s->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $s=$pdo->prepare("SELECT sm.*, cc.course_name FROM seat_matrix sm LEFT JOIN college_courses cc ON sm.course_id = cc.id WHERE sm.college_id=? ORDER BY sm.year DESC");
        $s->execute([$collegeId]);
        $seatMatrix=$s->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $s=$pdo->prepare("SELECT * FROM leads WHERE college_id=? ORDER BY created_at DESC LIMIT 50");
        $s->execute([$collegeId]);
        $leads=$s->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $s=$pdo->prepare("SELECT * FROM college_submissions WHERE account_id=? ORDER BY created_at DESC LIMIT 20");
        $s->execute([$account['id']]);
        $pendingSubs=$s->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM college_media WHERE college_id = ? AND (image_url IS NOT NULL OR video_url IS NOT NULL OR document_url IS NOT NULL OR `360_tour_url` IS NOT NULL) ORDER BY sort_order ASC");
        $stmt->execute([$collegeId]);
        $galleryMedia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM college_faqs WHERE college_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$collegeId]);
        $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM college_faculty WHERE college_id = ?");
        $stmt->execute([$collegeId]);
        $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM college_scholarships WHERE college_id = ?");
        $stmt->execute([$collegeId]);
        $scholarships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM college_updates WHERE college_id = ? ORDER BY event_date DESC, created_at DESC");
        $stmt->execute([$collegeId]);
        $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM college_qna WHERE college_id = ? ORDER BY created_at DESC");
        $stmt->execute([$collegeId]);
        $qna = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT * FROM rankings WHERE college_id = ? ORDER BY ranking_year DESC, ranking_body ASC");
        $stmt->execute([$collegeId]);
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
    try {
        $stmt = $pdo->prepare("SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.college_id = ? ORDER BY r.created_at DESC");
        $stmt->execute([$collegeId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
}

// Fetch virtual tour setting
$tourSetting = [];
if ($collegeId) {
    try {
        $stmtTour = $pdo->prepare("SELECT virtual_tour_enabled, `360_tour_url` FROM college_media WHERE college_id = ? AND image_url IS NULL AND video_url IS NULL AND document_url IS NULL LIMIT 1");
        $stmtTour->execute([$collegeId]);
        $tourSetting = $stmtTour->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch(Exception $e){}
}

// Fetch cities dynamically if state is selected
$cities = [];
$currentStateId = getValue($college, 'state_id');
if ($currentStateId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM cities WHERE state_id = ? ORDER BY name ASC");
        $stmt->execute([$currentStateId]);
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
}

// Handle Compare logic
$compare_result = null;
$compare_college = null;
if ($tab === 'compare' && !empty($_GET['compare_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM colleges WHERE id = ?");
        $stmt->execute([$_GET['compare_id']]);
        $compare_college = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($compare_college) {
            // Get comparison details
            $stmtCon = $pdo->prepare("SELECT * FROM college_contacts WHERE college_id = ?");
            $stmtCon->execute([$compare_college['id']]);
            $compare_contact = $stmtCon->fetch(PDO::FETCH_ASSOC) ?: [];

            $stmtPl = $pdo->prepare("SELECT * FROM college_placements WHERE college_id = ? ORDER BY placement_year DESC LIMIT 1");
            $stmtPl->execute([$compare_college['id']]);
            $compare_placement = $stmtPl->fetch(PDO::FETCH_ASSOC) ?: [];

            $myPlacement = [];
            if ($placements) $myPlacement = $placements[0];

            $compare_result = [
                'my_name' => $college['name'] ?? '',
                'other_name' => $compare_college['name'] ?? '',
                'my_type' => $college['college_type'] ?? '-',
                'other_type' => $compare_college['college_type'] ?? '-',
                'my_est' => $college['founded_year'] ?? '-',
                'other_est' => $compare_college['founded_year'] ?? '-',
                'my_naac' => $college['naac_grade'] ?? '-',
                'other_naac' => $compare_college['naac_grade'] ?? '-',
                'my_nirf' => $college['ranking_nirf'] ?? '-',
                'other_nirf' => $compare_college['ranking_nirf'] ?? '-',
                'my_avg_lpa' => $myPlacement['avg_package_lpa'] ?? '-',
                'other_avg_lpa' => $compare_placement['avg_package_lpa'] ?? '-',
                'my_highest_lpa' => $myPlacement['highest_package_lpa'] ?? '-',
                'other_highest_lpa' => $compare_placement['highest_package_lpa'] ?? '-',
            ];
        }
    } catch(Exception $e){}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>College Dashboard – AdmissionSeason</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link rel="stylesheet" href="/ADMISSION/assets/css/style.css?v=15">
<!-- jQuery and Trumbowyg -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
<style>
:root {
  --primary: #0B2447;
  --secondary: #19376D;
  --accent: #19376D;
  --accent-light: rgba(11,36,71,0.06);
  --bg-light: #F8FAFC;
  --text-dark: #0F172A;
  --text-muted: rgba(15,23,42,0.65);
  --border-color: rgba(15,23,42,0.1);
  --shadow-sm: 0 1px 3px rgba(11,36,71,0.06), 0 8px 24px rgba(11,36,71,0.04);
  --shadow-md: 0 4px 6px -1px rgba(11,36,71,0.08), 0 10px 15px -3px rgba(11,36,71,0.04);
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F1F5F9; min-height: 100vh; color: var(--text-dark); -webkit-font-smoothing: antialiased; }

/* Header Styling */
.dash-top {
  background: linear-gradient(135deg, #0B2447, #19376D);
  color: #fff;
  padding: 0 32px;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  box-shadow: 0 4px 24px rgba(11,36,71,0.15);
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.dash-top h1 {
  font-size: 1.2rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 12px;
  letter-spacing: -0.5px;
}
.dash-top h1 i {
  font-size: 1.6rem;
  background: linear-gradient(135deg, #60A5FA, #3B82F6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 2px 8px rgba(96,165,250,0.3));
}
.dash-user {
  display: flex;
  align-items: center;
  gap: 16px;
  font-size: 0.85rem;
}
.dash-user span {
  opacity: 0.95;
  font-weight: 700;
  background: rgba(255,255,255,0.08);
  padding: 6px 14px;
  border-radius: 20px;
  border: 1px solid rgba(255,255,255,0.1);
  letter-spacing: 0.2px;
}
.dash-user a {
  color: #fff;
  text-decoration: none;
  padding: 8px 16px;
  background: rgba(239,68,68,0.1);
  border: 1px solid rgba(239,68,68,0.2);
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 700;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.dash-user a:hover {
  background: #EF4444;
  border-color: #EF4444;
  box-shadow: 0 4px 16px rgba(239,68,68,0.35);
  transform: translateY(-1px);
}

/* Layout Wrapper */
.dash-layout {
  display: flex;
  padding-top: 70px;
  min-height: 100vh;
}

/* Sidebar */
.dash-side {
  width: 280px;
  background: #fff;
  border-right: 1px solid var(--border-color);
  padding: 20px 0;
  flex-shrink: 0;
  position: fixed;
  top: 70px;
  left: 0;
  bottom: 0;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 2px;
  z-index: 999;
}
.dash-side a {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 24px;
  font-size: 0.85rem;
  color: var(--text-muted);
  text-decoration: none;
  font-weight: 600;
  transition: all 0.2s ease;
  border-left: 4px solid transparent;
}
.dash-side a:hover {
  background: var(--bg-light);
  color: var(--accent);
  padding-left: 28px;
}
.dash-side a.active {
  background: var(--accent-light);
  color: var(--accent);
  border-left-color: var(--accent);
  font-weight: 700;
}
.dash-side a i {
  font-size: 1.25rem;
  width: 22px;
}

/* Main Content */
.dash-main {
  flex: 1;
  padding: 32px;
  min-width: 0;
  background: var(--bg-light);
  margin-left: 280px;
}
.dash-msg {
  padding: 16px 20px;
  border-radius: 12px;
  background: #ECFDF5;
  color: #065F46;
  border: 1px solid #A7F3D0;
  font-size: 0.85rem;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(6,95,70,0.03);
}
.dash-error {
  padding: 16px 20px;
  border-radius: 12px;
  background: #FEF2F2;
  color: #991B1B;
  border: 1px solid #FEE2E2;
  font-size: 0.85rem;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
}
.dash-card {
  background: #fff;
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 28px;
  margin-bottom: 24px;
  box-shadow: var(--shadow-sm);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.dash-card:hover {
  box-shadow: var(--shadow-md);
}
.dash-card h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--primary);
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding-bottom: 14px;
  border-bottom: 2px solid var(--bg-light);
}
.dash-card h3 i {
  color: var(--accent);
}

/* Stat Grid */
.dash-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 24px;
}
.stat-box {
  background: #fff;
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 24px;
  text-align: center;
  box-shadow: var(--shadow-sm);
  transition: all 0.2s ease;
}
.stat-box:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
  border-color: var(--accent);
}
.stat-box .num {
  font-size: 2rem;
  font-weight: 800;
  color: var(--primary);
  line-height: 1.2;
}
.stat-box .lbl {
  font-size: 0.72rem;
  color: var(--text-muted);
  margin-top: 6px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}
.stat-box i {
  font-size: 1.8rem;
  color: var(--accent);
  margin-bottom: 12px;
  display: block;
}

/* Tables */
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  margin-top: 10px;
}
th {
  text-align: left;
  padding: 14px 18px;
  background: var(--bg-light);
  color: var(--text-muted);
  font-weight: 700;
  border-bottom: 2px solid var(--border-color);
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
td {
  padding: 16px 18px;
  border-bottom: 1px solid var(--border-color);
  color: var(--text-dark);
  font-weight: 500;
}
tr:hover td {
  background: var(--bg-light);
}
.badge {
  display: inline-flex;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.2px;
}
.badge-green { background: #D1FAE5; color: #065F46; }
.badge-yellow { background: #FEF3C7; color: #92400E; }
.badge-red { background: #FEE2E2; color: #991B1B; }
.badge-blue { background: #DBEAFE; color: #1E40AF; }

/* Forms */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
.form-group {
  margin-bottom: 18px;
}
.form-group.full {
  grid-column: 1 / -1;
}
.form-group label {
  display: block;
  font-size: 0.72rem;
  font-weight: 800;
  color: var(--text-muted);
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.form-group input, .form-group select, .form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 1.5px solid var(--border-color);
  border-radius: 12px;
  font-size: 0.88rem;
  font-family: inherit;
  background: var(--bg-light);
  color: var(--text-dark);
  transition: all 0.2s ease;
  font-weight: 500;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
  outline: none;
  border-color: var(--accent);
  background: #fff;
  box-shadow: 0 0 0 4px rgba(37,99,235,0.12);
}
.checkbox-group {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 10px;
}
.checkbox-group input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}
.checkbox-group label {
  margin-bottom: 0;
  cursor: pointer;
}

/* Buttons */
.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  font-family: inherit;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.btn-primary {
  background: var(--primary);
  color: #fff;
}
.btn-primary:hover {
  background: var(--secondary);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(11,36,71,0.25);
}
.btn-danger {
  background: #dc2626;
  color: #fff;
}
.btn-danger:hover {
  background: #b91c1c;
}
.btn-sm {
  padding: 6px 12px;
  font-size: 0.75rem;
  border-radius: 6px;
}
.empty {
  text-align: center;
  padding: 48px;
  color: var(--text-muted);
  font-size: 0.9rem;
}
.empty i {
  font-size: 2.5rem;
  color: var(--border-color);
  margin-bottom: 12px;
  display: block;
}

@media(max-width:768px) {
  .dash-top { padding: 0 16px; }
  .dash-layout { padding-top: 70px; }
  .dash-side { display: none; }
  .dash-main { padding: 16px; margin-left: 0; }
  .dash-stats { grid-template-columns: repeat(2, 1fr); }
  .form-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="dash-top">
  <h1><i class="ph-fill ph-graduation-cap"></i> AdmissionSeason Dashboard</h1>
  <div class="dash-user">
    <span><?=htmlspecialchars($account['institute_name'])?></span>
    <a href="/ADMISSION/college/logout.php"><i class="ph ph-sign-out"></i> Logout</a>
  </div>
</div>
<div class="dash-layout">
  <nav class="dash-side">
    <a href="?tab=overview" class="<?=$tab==='overview'?'active':''?>"><i class="ph ph-squares-four"></i> College Info</a>
    <a href="?tab=identity" class="<?=$tab==='identity'?'active':''?>"><i class="ph ph-notebook"></i> Identity & Contact</a>
    <a href="?tab=infrastructure" class="<?=$tab==='infrastructure'?'active':''?>"><i class="ph ph-buildings"></i> Infrastructure</a>
    <a href="?tab=seo" class="<?=$tab==='seo'?'active':''?>"><i class="ph ph-globe"></i> SEO & Publish</a>
    <a href="?tab=courses" class="<?=$tab==='courses'?'active':''?>"><i class="ph ph-book-open"></i> Courses & Fees</a>
    <a href="?tab=reviews" class="<?=$tab==='reviews'?'active':''?>"><i class="ph ph-star"></i> Reviews</a>
    <a href="?tab=admissions" class="<?=$tab==='admissions'?'active':''?>"><i class="ph ph-check-square"></i> Admissions</a>
    <a href="?tab=placements" class="<?=$tab==='placements'?'active':''?>"><i class="ph ph-chart-line-up"></i> Placements</a>
    <a href="?tab=cutoffs" class="<?=$tab==='cutoffs'?'active':''?>"><i class="ph ph-scissors"></i> Cut-Offs</a>
    <a href="?tab=seats" class="<?=$tab==='seats'?'active':''?>"><i class="ph ph-table"></i> Seat Matrix</a>
    <a href="?tab=rankings" class="<?=$tab==='rankings'?'active':''?>"><i class="ph ph-trophy"></i> Rankings</a>
    <a href="?tab=media" class="<?=$tab==='media'?'active':''?>"><i class="ph ph-images"></i> Gallery</a>
    <a href="?tab=faculty" class="<?=$tab==='faculty'?'active':''?>"><i class="ph ph-users"></i> Faculty</a>
    <a href="?tab=compare" class="<?=$tab==='compare'?'active':''?>"><i class="ph ph-scales"></i> Compare</a>
    <a href="?tab=qna" class="<?=$tab==='qna'?'active':''?>"><i class="ph ph-chats-teardrop"></i> Q&A</a>
    <a href="?tab=updates" class="<?=$tab==='updates'?'active':''?>"><i class="ph ph-megaphone"></i> News</a>
    <a href="?tab=categories" class="<?=$tab==='categories'?'active':''?>"><i class="ph ph-folders"></i> Course Categories</a>
    <a href="?tab=leads" class="<?=$tab==='leads'?'active':''?>"><i class="ph ph-funnel"></i> Leads</a>
    <a href="?tab=submissions" class="<?=$tab==='submissions'?'active':''?>"><i class="ph ph-clock-countdown"></i> My Submissions</a>
  </nav>
  
  <main class="dash-main">
    <?php if($msg): ?><div class="dash-msg"><i class="ph ph-check-circle"></i> <?=$msg?></div><?php endif;?>
    <?php if($error): ?><div class="dash-error"><i class="ph ph-warning-circle"></i> <?=$error?></div><?php endif;?>
    
    <?php if(!$collegeId): ?>
    <div class="dash-card"><div class="empty"><i class="ph ph-link" style="font-size:2rem;display:block;margin-bottom:8px"></i>Your account is not yet linked to a college profile. Please contact admin to link your institute.</div></div>
    <?php else: ?>

    <?php if($tab==='overview'): ?>
    <div class="dash-stats">
      <div class="stat-box"><i class="ph ph-book-open"></i><div class="num"><?=count($courses)?></div><div class="lbl">Courses</div></div>
      <div class="stat-box"><i class="ph ph-chart-line-up"></i><div class="num"><?=count($placements)?></div><div class="lbl">Placements</div></div>
      <div class="stat-box"><i class="ph ph-scissors"></i><div class="num"><?=count($cutoffs)?></div><div class="lbl">Cutoffs</div></div>
      <div class="stat-box"><i class="ph ph-users-three"></i><div class="num"><?=count($leads)?></div><div class="lbl">Leads</div></div>
    </div>
    <div class="dash-card">
      <h3><i class="ph ph-info"></i> College Overview</h3>
      <table>
        <tr><td style="font-weight:600;width:180px">Name</td><td><?=htmlspecialchars($college['name']??'')?></td></tr>
        <tr><td style="font-weight:600">Type</td><td><?=htmlspecialchars($college['college_type']??'')?></td></tr>
        <tr><td style="font-weight:600">NAAC Grade</td><td><?=htmlspecialchars($college['naac_grade']??'-')?></td></tr>
        <tr><td style="font-weight:600">NIRF Rank</td><td><?=$college['ranking_nirf'] ?: '-'?></td></tr>
        <tr><td style="font-weight:600">Established</td><td><?=$college['founded_year'] ?: '-'?></td></tr>
        <tr><td style="font-weight:600">Location</td><td><?=htmlspecialchars(($contact['address']??''))?></td></tr>
      </table>
    </div>

    <?php elseif($tab==='identity'): ?>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update_identity">
      
      <div class="dash-card">
        <h3><i class="ph ph-identification-card"></i> College Identity</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>College Name *</label>
                <input type="text" name="name" required value="<?=getValue($college, 'name')?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>College Type</label>
                    <select name="college_type">
                        <option value="">Select Type</option>
                        <option value="govt" <?=getValue($college, 'college_type') == 'govt' ? 'selected' : ''?>>Government</option>
                        <option value="private" <?=getValue($college, 'college_type') == 'private' ? 'selected' : ''?>>Private</option>
                        <option value="deemed" <?=getValue($college, 'college_type') == 'deemed' ? 'selected' : ''?>>Deemed</option>
                        <option value="autonomous" <?=getValue($college, 'college_type') == 'autonomous' ? 'selected' : ''?>>Autonomous</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ownership</label>
                    <select name="ownership">
                        <option value="">Select Ownership</option>
                        <option value="central" <?=getValue($college, 'ownership') == 'central' ? 'selected' : ''?>>Central</option>
                        <option value="state" <?=getValue($college, 'ownership') == 'state' ? 'selected' : ''?>>State</option>
                        <option value="private_trust" <?=getValue($college, 'ownership') == 'private_trust' ? 'selected' : ''?>>Private Trust</option>
                        <option value="minority" <?=getValue($college, 'ownership') == 'minority' ? 'selected' : ''?>>Minority</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Type Label (Display Text)</label>
                    <input type="text" name="type_label" value="<?=getValue($college, 'type_label')?>">
                </div>
                <div class="form-group">
                    <label>Founded Year</label>
                    <input type="number" name="founded_year" min="1800" max="2099" value="<?=getValue($college, 'founded_year')?>">
                </div>
            </div>
        </div>
      </div>

      <div class="dash-card">
        <h3><i class="ph ph-image"></i> Media & Accreditations</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Logo Image</label>
                <?php if(getValue($media, 'logo_url')): ?>
                    <div style="margin-bottom: 8px;">
                        <img src="../<?=getValue($media, 'logo_url')?>" style="height: 50px; border-radius: 4px; border: 1px solid #ccc;">
                    </div>
                <?php endif; ?>
                <input type="hidden" name="existing_logo_url" value="<?=getValue($media, 'logo_url')?>">
                <input type="file" name="logo_file" accept="image/*">
            </div>
            <div class="form-group">
                <label>Cover Image</label>
                <?php if(getValue($media, 'cover_image_url')): ?>
                    <div style="margin-bottom: 8px;">
                        <img src="../<?=getValue($media, 'cover_image_url')?>" style="height: 50px; border-radius: 4px; border: 1px solid #ccc;">
                    </div>
                <?php endif; ?>
                <input type="hidden" name="existing_cover_image_url" value="<?=getValue($media, 'cover_image_url')?>">
                <input type="file" name="cover_file" accept="image/*">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>University Affiliation</label>
                <select name="university_id">
                    <option value="">Select University</option>
                    <?php foreach($universities as $u): ?>
                        <option value="<?=$u['id']?>" <?=getValue($college, 'university_id') == $u['id'] ? 'selected' : ''?>><?=htmlspecialchars($u['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>NAAC Grade</label>
                <select name="naac_grade">
                    <option value="">None</option>
                    <?php foreach(['A++', 'A+', 'A', 'B++', 'B+', 'B', 'C'] as $g): ?>
                        <option value="<?=$g?>" <?=getValue($college, 'naac_grade') == $g ? 'selected' : ''?>><?=$g?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>NIRF Rank</label>
                <input type="number" name="ranking_nirf" value="<?=getValue($college, 'ranking_nirf')?>">
            </div>
            <div class="form-group" style="padding-top:20px;">
                <div class="checkbox-group"><input type="checkbox" name="autonomous" <?=!empty($college['autonomous']) ? 'checked' : ''?> id="aut"><label for="aut">Autonomous</label></div>
                <div class="checkbox-group"><input type="checkbox" name="ugc_approved" <?=!empty($college['ugc_approved']) ? 'checked' : ''?> id="ugc"><label for="ugc">UGC Approved</label></div>
                <div class="checkbox-group"><input type="checkbox" name="aicte_approved" <?=!empty($college['aicte_approved']) ? 'checked' : ''?> id="aicte"><label for="aicte">AICTE Approved</label></div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Total Students</label>
                <input type="number" name="total_students" value="<?=getValue($college, 'total_students')?>">
            </div>
            <div class="form-group">
                <label>Total Faculty</label>
                <input type="number" name="total_faculty" value="<?=getValue($college, 'total_faculty')?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Campus Area (Acres)</label>
                <input type="number" step="0.1" name="campus_area_acres" value="<?=getValue($college, 'campus_area_acres')?>">
            </div>
            <div class="form-group">
                <label>Campus Type</label>
                <select name="campus_type">
                    <option value="">Select Type</option>
                    <option value="urban" <?=getValue($college, 'campus_type') == 'urban' ? 'selected' : ''?>>Urban</option>
                    <option value="semi-urban" <?=getValue($college, 'campus_type') == 'semi-urban' ? 'selected' : ''?>>Semi-Urban</option>
                    <option value="rural" <?=getValue($college, 'campus_type') == 'rural' ? 'selected' : ''?>>Rural</option>
                </select>
            </div>
        </div>
      </div>

      <div class="dash-card">
        <h3><i class="ph ph-map-pin"></i> Contact & Location</h3>
        <div class="form-row">
            <div class="form-group">
                <label>State</label>
                <select name="state_id" id="state_id" onchange="loadCities(this.value)">
                    <option value="">Select State</option>
                    <?php foreach($states as $s): ?>
                        <option value="<?=$s['id']?>" <?=getValue($college, 'state_id') == $s['id'] ? 'selected' : ''?>><?=htmlspecialchars($s['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>City</label>
                <select name="city_id" id="city_id">
                    <option value="">Select City</option>
                    <?php foreach($cities as $c): ?>
                        <option value="<?=$c['id']?>" <?=getValue($college, 'city_id') == $c['id'] ? 'selected' : ''?>><?=htmlspecialchars($c['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" rows="3"><?=getValue($contact, 'address')?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="contact_email" value="<?=getValue($contact, 'email')?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="contact_phone" value="<?=getValue($contact, 'phone')?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Website URL</label>
                <input type="url" name="official_website" value="<?=getValue($contact, 'website_url')?>">
            </div>
            <div class="form-group">
                <label>Pincode</label>
                <input type="text" name="pincode" value="<?=getValue($contact, 'pincode')?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Latitude</label>
                <input type="number" step="0.000001" name="latitude" value="<?=getValue($contact, 'latitude')?>">
            </div>
            <div class="form-group">
                <label>Longitude</label>
                <input type="number" step="0.000001" name="longitude" value="<?=getValue($contact, 'longitude')?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Nearest Railway (km)</label>
                <input type="number" step="0.1" name="nearest_railway_km" value="<?=getValue($contact, 'nearest_railway_km')?>">
            </div>
            <div class="form-group">
                <label>Nearest Airport (km)</label>
                <input type="number" step="0.1" name="nearest_airport_km" value="<?=getValue($contact, 'nearest_airport_km')?>">
            </div>
        </div>
        <div class="form-group">
            <label>Google Maps Embed URL</label>
            <input type="text" name="google_maps_embed_url" value="<?=getValue($contact, 'google_maps_embed_url')?>">
        </div>
        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Identity Details</button>
      </div>
    </form>

    <script>
    function loadCities(stateId) {
        if(!stateId) {
            document.getElementById('city_id').innerHTML = '<option value="">Select City</option>';
            return;
        }
        fetch('/ADMISSION/api/get_cities.php?state_id=' + stateId)
            .then(res => res.json())
            .then(data => {
                let html = '<option value="">Select City</option>';
                data.forEach(c => {
                    html += `<option value="${c.id}">${c.name}</option>`;
                });
                document.getElementById('city_id').innerHTML = html;
            });
    }
    </script>

    <?php elseif($tab==='infrastructure'): ?>
    <form method="POST">
      <input type="hidden" name="action" value="update_about">
      
      <div class="dash-card">
        <h3><i class="ph ph-article"></i> About & Description</h3>
        <div class="form-group">
            <label>About Text</label>
            <textarea name="about_text" rows="5"><?=getValue($content, 'about_text')?></textarea>
        </div>
        <div class="form-group">
            <label>Highlights (One per line)</label>
            <textarea name="highlights_json" rows="3"><?=jsonToLines(getValue($content, 'highlights_json'))?></textarea>
        </div>
        <div class="form-group">
            <label>Accreditations (One per line)</label>
            <textarea name="accreditations_json" rows="3"><?=jsonToLines(getValue($content, 'accreditations_json'))?></textarea>
        </div>
        <div class="form-group">
            <label>Rankings (Format - Key: Value, One per line, e.g. NIRF: 12)</label>
            <textarea name="rankings_json" rows="3"><?=jsonToLines(getValue($content, 'rankings_json'), true)?></textarea>
        </div>
        <div class="form-group">
            <label>Awards (One per line)</label>
            <textarea name="awards_json" rows="3"><?=jsonToLines(getValue($content, 'awards_json'))?></textarea>
        </div>
      </div>

      <div class="dash-card">
        <h3><i class="ph ph-buildings"></i> Infrastructure</h3>
        <div class="form-group">
            <div class="checkbox-group"><input type="checkbox" name="library" <?=!empty($infrastructure['library']) ? 'checked' : ''?> id="inf1"><label for="inf1">Library</label></div>
            <div class="checkbox-group"><input type="checkbox" name="auditorium" <?=!empty($infrastructure['auditorium']) ? 'checked' : ''?> id="inf2"><label for="inf2">Auditorium</label></div>
            <div class="checkbox-group"><input type="checkbox" name="cafeteria" <?=!empty($infrastructure['cafeteria']) ? 'checked' : ''?> id="inf3"><label for="inf3">Cafeteria</label></div>
            <div class="checkbox-group"><input type="checkbox" name="wifi" <?=!empty($infrastructure['wifi']) ? 'checked' : ''?> id="inf4"><label for="inf4">WiFi</label></div>
            <div class="checkbox-group"><input type="checkbox" name="medical_facility" <?=!empty($infrastructure['medical_facility']) ? 'checked' : ''?> id="inf5"><label for="inf5">Medical Facility</label></div>
            <div class="checkbox-group"><input type="checkbox" name="transport" <?=!empty($infrastructure['transport']) ? 'checked' : ''?> id="inf6"><label for="inf6">Transport</label></div>
            <div class="checkbox-group"><input type="checkbox" name="ev_charging" <?=!empty($infrastructure['ev_charging']) ? 'checked' : ''?> id="inf7"><label for="inf7">EV Charging</label></div>
            <div class="checkbox-group"><input type="checkbox" name="solar_power" <?=!empty($infrastructure['solar_power']) ? 'checked' : ''?> id="inf8"><label for="inf8">Solar Power</label></div>
        </div>
        <div class="form-group">
            <label>Sports Facilities (One per line)</label>
            <textarea name="sports_facilities" rows="3"><?=jsonToLines(getValue($infrastructure, 'sports_facilities'))?></textarea>
        </div>
        <div class="form-group">
            <label>Labs (One per line)</label>
            <textarea name="labs" rows="3"><?=jsonToLines(getValue($infrastructure, 'labs'))?></textarea>
        </div>
      </div>

      <div class="dash-card">
        <h3><i class="ph ph-bed"></i> Hostels</h3>
        <div class="checkbox-group" style="margin-bottom:14px;"><input type="checkbox" name="hostel_available" <?=!empty($hostels['hostel_available']) ? 'checked' : ''?> id="hst1"><label for="hst1">Hostels Available</label></div>
        <div class="form-row">
            <div class="form-group">
                <label>Hostel Type</label>
                <input type="text" name="hostel_type" placeholder="e.g. Boys / Girls / Co-ed" value="<?=getValue($hostels, 'hostel_type')?>">
            </div>
            <div class="form-group">
                <label>Hostel Capacity</label>
                <input type="number" name="hostel_capacity" value="<?=getValue($hostels, 'hostel_capacity')?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Annual Hostel Fee (₹)</label>
                <input type="number" name="hostel_fee_annual" value="<?=getValue($hostels, 'hostel_fee_annual')?>">
            </div>
            <div class="form-group">
                <label>Mess Type</label>
                <input type="text" name="mess_type" placeholder="e.g. Veg / Non-Veg" value="<?=getValue($hostels, 'mess_type')?>">
            </div>
        </div>
        <div class="form-group">
            <div class="checkbox-group"><input type="checkbox" name="mess_available" <?=!empty($hostels['mess_available']) ? 'checked' : ''?> id="hst2"><label for="hst2">Mess Available</label></div>
            <div class="checkbox-group"><input type="checkbox" name="ac_available" <?=!empty($hostels['ac_available']) ? 'checked' : ''?> id="hst3"><label for="hst3">A/C Available</label></div>
            <div class="checkbox-group"><input type="checkbox" name="laundry_available" <?=!empty($hostels['laundry_available']) ? 'checked' : ''?> id="hst4"><label for="hst4">Laundry Available</label></div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Info & Hostels</button>
      </div>
    </form>

    <?php elseif($tab==='admissions'): ?>
    <form method="POST">
        <input type="hidden" name="action" value="update_admissions">
        <div class="dash-card">
            <h3><i class="ph ph-check-square"></i> Admissions Info</h3>
            <div class="form-group">
                <label>Admission Process Description</label>
                <textarea name="admission_process" rows="4"><?=getValue($admissions, 'admission_process')?></textarea>
            </div>
            <div class="form-group">
                <label>Accepted Exams (One per line)</label>
                <textarea name="accepted_exams" rows="3"><?=jsonToLines(getValue($admissions, 'accepted_exams'))?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Admission Start Date</label>
                    <input type="date" name="admission_start_date" value="<?=getValue($admissions, 'admission_start_date')?>">
                </div>
                <div class="form-group">
                    <label>Admission End Date</label>
                    <input type="date" name="admission_end_date" value="<?=getValue($admissions, 'admission_end_date')?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Management Quota Seats</label>
                    <input type="number" name="management_quota_seats" value="<?=getValue($admissions, 'management_quota_seats')?>">
                </div>
                <div class="form-group">
                    <label>NRI Quota Seats</label>
                    <input type="number" name="nri_quota_seats" value="<?=getValue($admissions, 'nri_quota_seats')?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Application Mode</label>
                    <input type="text" name="application_mode" placeholder="e.g. Online / Offline" value="<?=getValue($admissions, 'application_mode')?>">
                </div>
                <div class="form-group" style="padding-top:20px;">
                    <div class="checkbox-group"><input type="checkbox" name="merit_based" <?=!empty($admissions['merit_based']) ? 'checked' : ''?> id="mb"><label for="mb">Merit Based</label></div>
                    <div class="checkbox-group"><input type="checkbox" name="direct_admission" <?=!empty($admissions['direct_admission']) ? 'checked' : ''?> id="da"><label for="da">Direct Admission</label></div>
                    <div class="checkbox-group"><input type="checkbox" name="lateral_entry_available" <?=!empty($admissions['lateral_entry_available']) ? 'checked' : ''?> id="le"><label for="le">Lateral Entry Available</label></div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Admissions Info</button>
        </div>
    </form>

    <?php elseif($tab==='reviews'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-star"></i> Student Reviews & Feedback (<?=count($reviews)?>)</h3>
      <?php if($reviews): ?>
      <?php foreach($reviews as $r): ?>
      <div style="border: 1px solid var(--border-color); border-radius:12px; padding:18px; margin-bottom:16px; background:#fafbfc;">
          <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
              <span style="font-weight:700; color:var(--primary);"><?=htmlspecialchars($r['user_name'] ?: 'Student')?></span>
              <div>
                  <?php for($i=1; $i<=5; $i++): ?>
                      <i class="ph-fill ph-star" style="color: <?=$i<=$r['overall_rating']?'#f59e0b':'#e2e8f0'?>"></i>
                  <?php endfor; ?>
              </div>
          </div>
          <h4 style="font-size:0.9rem; font-weight:700; margin-bottom:8px;"><?=htmlspecialchars($r['review_title'])?></h4>
          <p style="font-size:0.82rem; color:var(--text-dark); line-height:1.5;"><?=nl2br(htmlspecialchars($r['review_body']))?></p>
          <div style="margin-top:10px; display:flex; gap:16px; font-size:0.75rem; color:var(--text-muted);">
              <span><strong>Pros:</strong> <?=htmlspecialchars($r['pros'] ?: 'None')?></span>
              <span><strong>Cons:</strong> <?=htmlspecialchars($r['cons'] ?: 'None')?></span>
          </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?><div class="empty">No student reviews received yet.</div><?php endif; ?>
    </div>

    <?php elseif($tab==='rankings'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-trophy"></i> Rankings History (<?=count($rankings)?>)</h3>
      <?php if($rankings): ?>
      <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Ranking Body</th><th>Year</th><th>Category</th><th>Rank Position</th><th>Rank Band</th><th>Score</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach($rankings as $rk): ?>
            <tr>
                <td style="font-weight:700; color:var(--primary);"><?=htmlspecialchars($rk['ranking_body'])?></td>
                <td><?=$rk['ranking_year']?></td>
                <td><?=htmlspecialchars($rk['category'])?></td>
                <td><?=$rk['rank_position'] ?: '-'?></td>
                <td><?=htmlspecialchars($rk['rank_band'] ?: '-')?></td>
                <td><?=$rk['score'] ?: '-'?></td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete ranking?');">
                        <input type="hidden" name="action" value="delete_ranking">
                        <input type="hidden" name="ranking_id" value="<?=$rk['id']?>">
                        <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer;" title="Delete"><i class="ph ph-trash" style="font-size:1.2rem;"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach;?>
        </tbody>
      </table>
      </div>
      <?php else: ?><div class="empty">No rankings posted yet.</div><?php endif; ?>
    </div>

    <div class="dash-card">
      <h3><i class="ph ph-plus-circle"></i> Add Ranking</h3>
      <form method="POST">
        <input type="hidden" name="action" value="add_ranking">
        <div class="form-grid">
            <div class="form-group">
                <label>Ranking Body *</label>
                <select name="ranking_body" required>
                    <option>NIRF</option><option>QS</option><option>Times</option>
                    <option>Outlook</option><option>IndiaToday</option>
                    <option>NAAC</option><option>Careers360</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ranking Year *</label>
                <input type="number" name="ranking_year" value="<?=date('Y')?>" required>
            </div>
            <div class="form-group">
                <label>Category *</label>
                <select name="category" required>
                    <option>Overall</option><option>Engineering</option>
                    <option>Management</option><option>Medical</option>
                    <option>Law</option><option>Arts</option>
                </select>
            </div>
            <div class="form-group">
                <label>Rank Position</label>
                <input type="number" name="rank_position" placeholder="e.g. 23">
            </div>
            <div class="form-group">
                <label>Rank Band (If exact rank not given)</label>
                <input type="text" name="rank_band" placeholder="e.g. 51-100">
            </div>
            <div class="form-group">
                <label>Score</label>
                <input type="number" step="0.01" name="score" placeholder="e.g. 78.45">
            </div>
            <div class="form-group full">
                <label>Source URL (Link details)</label>
                <input type="url" name="source_url">
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="ph ph-plus"></i> Add Ranking</button>
      </form>
    </div>

    <?php elseif($tab==='compare'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-scales"></i> Compare Colleges</h3>
      <p style="color:var(--text-muted); font-size:0.8rem; margin-bottom:16px;">Compare your college against other colleges listed on AdmissionSeason in real-time.</p>
      
      <form method="GET" style="margin-bottom: 24px;">
          <input type="hidden" name="tab" value="compare">
          <div class="form-row" style="align-items: flex-end;">
              <div class="form-group" style="margin-bottom:0;">
                  <label>Select College to Compare With</label>
                  <select name="compare_id" required>
                      <option value="">Choose college...</option>
                      <?php foreach($all_colleges as $c): ?>
                          <option value="<?=$c['id']?>" <?=(isset($_GET['compare_id']) && $_GET['compare_id']==$c['id']?'selected':'')?>><?=htmlspecialchars($c['name'])?></option>
                      <?php endforeach; ?>
                  </select>
              </div>
              <button type="submit" class="btn btn-primary">Run Comparison</button>
          </div>
      </form>

      <?php if ($compare_result): ?>
      <div style="overflow-x:auto; margin-top:20px;">
          <table style="border: 1px solid var(--border-color); border-radius:10px;">
              <thead>
                  <tr>
                      <th style="width:250px;">Metric</th>
                      <th><?=htmlspecialchars($compare_result['my_name'])?> (You)</th>
                      <th><?=htmlspecialchars($compare_result['other_name'])?></th>
                  </tr>
              </thead>
              <tbody>
                  <tr><td style="font-weight:600;">College Type</td><td><?=ucfirst($compare_result['my_type'])?></td><td><?=ucfirst($compare_result['other_type'])?></td></tr>
                  <tr><td style="font-weight:600;">Founded Year</td><td><?=$compare_result['my_est']?></td><td><?=$compare_result['other_est']?></td></tr>
                  <tr><td style="font-weight:600;">NAAC Grade</td><td><span class="badge badge-blue"><?=$compare_result['my_naac']?></span></td><td><span class="badge badge-blue"><?=$compare_result['other_naac']?></span></td></tr>
                  <tr><td style="font-weight:600;">NIRF Ranking</td><td><strong><?=$compare_result['my_nirf']?></strong></td><td><strong><?=$compare_result['other_nirf']?></strong></td></tr>
                  <tr><td style="font-weight:600;">Avg Salary Package (LPA)</td><td>₹<?=$compare_result['my_avg_lpa']?> LPA</td><td>₹<?=$compare_result['other_avg_lpa']?> LPA</td></tr>
                  <tr><td style="font-weight:600;">Highest Salary Package (LPA)</td><td>₹<?=$compare_result['my_highest_lpa']?> LPA</td><td>₹<?=$compare_result['other_highest_lpa']?> LPA</td></tr>
              </tbody>
          </table>
      </div>
      <?php endif; ?>
    </div>

    <?php elseif($tab==='seo'): ?>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update_seo">
      
      <div class="dash-card">
        <h3><i class="ph ph-globe"></i> SEO Settings & Publish Status</h3>
        <div class="form-group">
            <label>Publish Status</label>
            <select name="publish_status" required>
                <option value="draft" <?=getValue($college, 'publish_status') === 'draft' ? 'selected' : ''?>>Draft</option>
                <option value="published" <?=getValue($college, 'publish_status') === 'published' ? 'selected' : ''?>>Published</option>
                <option value="archived" <?=getValue($college, 'publish_status') === 'archived' ? 'selected' : ''?>>Archived</option>
            </select>
        </div>
        <div class="form-group">
            <label>Meta Title</label>
            <input type="text" name="meta_title" value="<?=getValue($seo, 'meta_title')?>">
        </div>
        <div class="form-group">
            <label>Meta Description</label>
            <textarea name="meta_description" rows="3"><?=getValue($seo, 'meta_description')?></textarea>
        </div>
        <div class="form-group">
            <label>Canonical URL</label>
            <input type="url" name="canonical_url" value="<?=getValue($seo, 'canonical_url')?>">
        </div>
        <div class="form-group">
            <label>OG Image File</label>
            <?php if(getValue($seo, 'og_image_url')): ?>
                <div style="margin-bottom:8px;">
                    <img src="../<?=getValue($seo, 'og_image_url')?>" style="height: 50px; border-radius: 4px; border: 1px solid #ccc;">
                </div>
            <?php endif; ?>
            <input type="hidden" name="existing_og_image_url" value="<?=getValue($seo, 'og_image_url')?>">
            <input type="file" name="og_image_file" accept="image/*">
        </div>
        <div class="form-group">
            <label>Schema Markup</label>
            <textarea name="schema_markup" rows="5"><?=getValue($seo, 'schema_markup')?></textarea>
        </div>
        <div class="checkbox-group" style="margin-bottom:14px;"><input type="checkbox" name="noindex" <?=!empty($seo['noindex']) ? 'checked' : ''?> id="noind"><label for="noind">Mark Page as No-Index (Do not show on Google)</label></div>
        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save SEO Settings</button>
      </div>
    </form>

    <?php elseif($tab==='courses'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-book-open"></i> Associated Courses (<?=count($courses)?>)</h3>
      <?php if($courses): ?>
      <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Course Name</th><th>Level</th><th>Duration</th><th>Total Fee</th><th>Seats</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($courses as $c): ?>
        <tr>
          <td style="font-weight:600; color:var(--primary);"><?=htmlspecialchars($c['course_name'])?></td>
          <td><span class="badge badge-blue"><?=htmlspecialchars($c['course_level'])?></span></td>
          <td><?=$c['duration_years'] ? $c['duration_years'].' Yrs' : '-'?></td>
          <td><?=$c['total_fee'] ? '₹'.number_format($c['total_fee'], 2) : '-'?></td>
          <td><?=$c['seats_available'] ?: '-'?></td>
          <td>
             <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this course?');">
                 <input type="hidden" name="action" value="delete_course">
                 <input type="hidden" name="cc_id" value="<?=$c['id']?>">
                 <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer;" title="Delete Course"><i class="ph ph-trash" style="font-size:1.2rem;"></i></button>
             </form>
          </td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
      </div>
      <?php else: ?><div class="empty">No courses added yet.</div><?php endif;?>
    </div>
    
    <div class="dash-card">
      <h3><i class="ph ph-plus-circle"></i> Add Course to College</h3>
      <form method="POST">
        <input type="hidden" name="action" value="add_course">
        <div class="form-grid">
            <div class="form-group">
                <label>Course Name *</label>
                <input type="text" name="course_name" required placeholder="e.g. B.Tech Computer Science">
            </div>
            <div class="form-group">
                <label>Course Level *</label>
                <select name="course_level" required>
                    <option value="">Select Level</option>
                    <option value="UG">Undergraduate (UG)</option>
                    <option value="PG">Postgraduate (PG)</option>
                    <option value="Diploma">Diploma</option>
                    <option value="PhD">PhD</option>
                    <option value="Certificate">Certificate</option>
                </select>
            </div>
            <div class="form-group">
                <label>Duration (Years)</label>
                <input type="number" step="0.5" name="duration_years">
            </div>
            <div class="form-group">
                <label>Total Intake (Seats)</label>
                <input type="number" name="seats_available">
            </div>
            <div class="form-group">
                <label>Total Fee (₹)</label>
                <input type="number" step="0.01" name="total_fee">
            </div>
            <div class="form-group">
                <label>Semester Fee (₹)</label>
                <input type="number" step="0.01" name="semester_fee">
            </div>
            <div class="form-group">
                <label>Annual Fee (₹)</label>
                <input type="number" step="0.01" name="annual_fee">
            </div>
            <div class="form-group">
                <label>Application Fee (₹)</label>
                <input type="number" step="0.01" name="application_fee">
            </div>
            <div class="form-group full">
                <label>Eligibility Criteria</label>
                <textarea name="eligibility_criteria" rows="2"></textarea>
            </div>
            <div class="form-group full">
                <label>Specializations (Comma-separated)</label>
                <input type="text" name="specializations" placeholder="e.g. CSE, IT, Data Science">
            </div>
            <div class="form-group full checkbox-group">
                <input type="checkbox" name="emi_available" id="emi">
                <label for="emi" style="font-weight:normal;">EMI Options Available for Fees</label>
            </div>
        </div>
        <div style="text-align: right; margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="ph ph-plus"></i> Add Course</button>
        </div>
      </form>
    </div>

    <?php elseif($tab==='placements'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-chart-line-up"></i> Placement Records (<?=count($placements)?>)</h3>
      <?php if($placements): ?>
      <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Year</th><th>Avg Package</th><th>Highest</th><th>Median</th><th>Placed %</th><th>Students</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($placements as $p): ?>
        <tr>
          <td style="font-weight:600"><?=$p['placement_year']?></td>
          <td><?=$p['avg_package_lpa'] ? '₹'.$p['avg_package_lpa'].' LPA' : '-'?></td>
          <td><?=$p['highest_package_lpa'] ? '₹'.$p['highest_package_lpa'].' LPA' : '-'?></td>
          <td><?=$p['median_package_lpa'] ? '₹'.$p['median_package_lpa'].' LPA' : '-'?></td>
          <td><?=$p['placement_percentage'] ? $p['placement_percentage'].'%' : '-'?></td>
          <td><?=$p['total_placed']?>/<?=$p['total_students']?></td>
          <td>
             <form method="POST" style="display:inline;" onsubmit="return confirm('Delete placement record?');">
                 <input type="hidden" name="action" value="delete_placement">
                 <input type="hidden" name="placement_id" value="<?=$p['id']?>">
                 <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer;" title="Delete Record"><i class="ph ph-trash" style="font-size:1.2rem;"></i></button>
             </form>
          </td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
      </div>
      <?php else: ?><div class="empty">No placement data added yet.</div><?php endif;?>
    </div>
    
    <div class="dash-card">
      <h3><i class="ph ph-plus-circle"></i> Add Placement Data</h3>
      <form method="POST">
        <input type="hidden" name="action" value="add_placement">
        <div class="form-grid">
          <div class="form-group">
              <label>Year *</label>
              <input type="number" name="placement_year" value="<?=date('Y')?>" min="2015" max="2030" required>
          </div>
          <div class="form-group">
              <label>Avg Package (LPA)</label>
              <input type="number" name="avg_package_lpa" step="0.01">
          </div>
          <div class="form-group">
              <label>Highest Package (LPA)</label>
              <input type="number" name="highest_package_lpa" step="0.01">
          </div>
          <div class="form-group">
              <label>Median Package (LPA)</label>
              <input type="number" name="median_package_lpa" step="0.01">
          </div>
          <div class="form-group">
              <label>Placement Percentage (%)</label>
              <input type="number" name="placement_percentage" step="0.01" max="100">
          </div>
          <div class="form-group">
              <label>Total Intake Students</label>
              <input type="number" name="total_students">
          </div>
          <div class="form-group">
              <label>Total Students Placed</label>
              <input type="number" name="total_placed">
          </div>
          <div class="form-group full">
              <label>Top Recruiters (Comma-separated)</label>
              <input type="text" name="top_recruiters" placeholder="Google, Microsoft, Amazon, etc.">
          </div>
        </div>
        <div style="text-align: right; margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="ph ph-plus"></i> Add Placement</button>
        </div>
      </form>
    </div>

    <?php elseif($tab==='cutoffs'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-scissors"></i> Cutoffs (<?=count($cutoffs)?>)</h3>
      <?php if($cutoffs): ?>
      <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Exam</th><th>Year</th><th>Course</th><th>Category</th><th>Opening Rank</th><th>Closing Rank</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($cutoffs as $c): ?>
        <tr>
          <td style="font-weight:600"><?=htmlspecialchars($c['exam_name']??'-')?></td>
          <td><?=$c['year']?></td>
          <td><?=htmlspecialchars($c['course_name']??'-')?></td>
          <td><span class="badge badge-blue"><?=htmlspecialchars($c['category'])?></span></td>
          <td><?=$c['opening_rank'] ?: '-'?></td>
          <td><?=$c['closing_rank'] ?: '-'?></td>
          <td>
             <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this cutoff?');">
                 <input type="hidden" name="action" value="delete_cutoff">
                 <input type="hidden" name="cutoff_id" value="<?=$c['id']?>">
                 <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer;" title="Delete Cutoff"><i class="ph ph-trash" style="font-size:1.2rem;"></i></button>
             </form>
          </td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
      </div>
      <?php else: ?><div class="empty">No cutoffs added yet.</div><?php endif;?>
    </div>
    
    <div class="dash-card">
      <h3><i class="ph ph-plus-circle"></i> Add Cutoff</h3>
      <form method="POST">
        <input type="hidden" name="action" value="add_cutoff">
        <div class="form-grid">
          <div class="form-group">
              <label>Entrance Exam *</label>
              <select name="exam_id" required>
                  <option value="">Select Exam</option>
                  <?php foreach($exams as $e):?>
                      <option value="<?=$e['id']?>"><?=htmlspecialchars($e['exam_name'])?></option>
                  <?php endforeach;?>
              </select>
          </div>
          <div class="form-group">
              <label>Year *</label>
              <input type="number" name="year" value="<?=date('Y')?>" required>
          </div>
          <div class="form-group">
              <label>Course Name / Specialization</label>
              <input type="text" name="course_name" placeholder="e.g. B.Tech Computer Science">
          </div>
          <div class="form-group">
              <label>Category</label>
              <select name="category">
                  <option>General</option>
                  <option>OBC</option>
                  <option>SC</option>
                  <option>ST</option>
                  <option>EWS</option>
                  <option>PwD</option>
              </select>
          </div>
          <div class="form-group">
              <label>Quota</label>
              <select name="quota">
                  <option>All India</option>
                  <option>Home State</option>
              </select>
          </div>
          <div class="form-group">
              <label>Opening Rank</label>
              <input type="number" name="opening_rank">
          </div>
          <div class="form-group">
              <label>Closing Rank</label>
              <input type="number" name="closing_rank">
          </div>
        </div>
        <div style="text-align: right; margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="ph ph-plus"></i> Add Cutoff</button>
        </div>
      </form>
    </div>

    <?php elseif($tab==='seats'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-table"></i> Seat Matrix (<?=count($seatMatrix)?>)</h3>
      <?php if($seatMatrix): ?>
      <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Course</th><th>Category</th><th>Total Seats</th><th>Year</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($seatMatrix as $sm): ?>
        <tr>
          <td style="font-weight:600"><?=htmlspecialchars($sm['course_name'] ?: '-')?></td>
          <td><span class="badge badge-blue"><?=htmlspecialchars($sm['category'])?></span></td>
          <td><?=$sm['total_seats'] ?: '-'?></td>
          <td><?=$sm['year']?></td>
          <td>
             <form method="POST" style="display:inline;" onsubmit="return confirm('Delete seat matrix?');">
                 <input type="hidden" name="action" value="delete_seat_matrix">
                 <input type="hidden" name="seat_matrix_id" value="<?=$sm['id']?>">
                 <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer;" title="Delete Seat Matrix"><i class="ph ph-trash" style="font-size:1.2rem;"></i></button>
             </form>
          </td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
      </div>
      <?php else: ?><div class="empty">No seat matrix data yet.</div><?php endif;?>
    </div>
    
    <div class="dash-card">
      <h3><i class="ph ph-plus-circle"></i> Add Seat Matrix</h3>
      <form method="POST">
        <input type="hidden" name="action" value="add_seat_matrix">
        <div class="form-grid">
          <div class="form-group">
              <label>Course *</label>
              <select name="course_id" required>
                  <option value="">Select Course</option>
                  <?php foreach($courses as $c):?>
                      <option value="<?=$c['id']?>"><?=htmlspecialchars($c['course_name'])?></option>
                  <?php endforeach;?>
              </select>
          </div>
          <div class="form-group">
              <label>Category *</label>
              <select name="category" required>
                  <option>General</option>
                  <option>OBC</option>
                  <option>SC</option>
                  <option>ST</option>
                  <option>EWS</option>
                  <option>PwD</option>
                  <option>NRI</option>
                  <option>Mgmt</option>
              </select>
          </div>
          <div class="form-group">
              <label>Total Seats</label>
              <input type="number" name="total_seats" required>
          </div>
          <div class="form-group">
              <label>Year</label>
              <input type="number" name="year" value="<?=date('Y')?>" required>
          </div>
          <div class="form-group">
              <label>Source</label>
              <input type="text" name="source" placeholder="e.g. JoSAA / State Counselling">
          </div>
        </div>
        <div style="text-align: right; margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="ph ph-plus"></i> Add Seat Matrix</button>
        </div>
      </form>
    </div>

    <?php elseif($tab==='media'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-cube"></i> Virtual Tour Settings</h3>
      <form method="POST">
          <input type="hidden" name="action" value="update_virtual_tour">
          <div class="form-group checkbox-group" style="margin-bottom:14px;">
              <input type="checkbox" name="virtual_tour_enabled" id="vt" <?=!empty($tourSetting['virtual_tour_enabled']) ? 'checked' : ''?> onchange="document.getElementById('tourUrlInput').style.display = this.checked ? 'block' : 'none';">
              <label for="vt" style="font-weight:700; cursor:pointer;">Virtual Tour Enabled</label>
          </div>
          <div id="tourUrlInput" class="form-group" style="display: <?=!empty($tourSetting['virtual_tour_enabled']) ? 'block' : 'none'?>;">
              <label>360 Virtual Tour URL</label>
              <input type="url" name="tour_url" placeholder="Virtual Tour URL (https://...)" value="<?=htmlspecialchars($tourSetting['360_tour_url'] ?? '')?>">
          </div>
          <button type="submit" class="btn btn-primary">Save Tour Settings</button>
      </form>
    </div>

    <div class="dash-card">
      <h3><i class="ph ph-images"></i> Media Gallery (<?=count($galleryMedia)?> items)</h3>
      <?php if($galleryMedia): ?>
      <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Preview</th><th>Category</th><th>Sub-Type</th><th>Caption</th><th>Order</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach($galleryMedia as $m): 
                $typeStr = ''; $subStr = ''; $link = '';
                if ($m['image_url']) { $typeStr = 'Image'; $subStr = $m['image_type']; $link = $m['image_url']; }
                elseif ($m['video_url']) { $typeStr = 'Video'; $subStr = $m['video_type']; $link = $m['video_url']; }
                elseif ($m['document_url']) { $typeStr = 'Document'; $subStr = $m['document_type']; $link = $m['document_url']; }
                elseif ($m['360_tour_url']) { $typeStr = '360 Tour'; $link = $m['360_tour_url']; }
                $display_link = preg_match('/^https?:\/\//', $link) ? $link : '../' . $link;
            ?>
            <tr>
                <td>
                    <?php if($typeStr == 'Image'): ?>
                        <img src="<?=htmlspecialchars($display_link)?>" alt="" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">
                    <?php elseif($typeStr == 'Document'): ?>
                        <i class="ph ph-file-pdf" style="font-size:2rem; color:var(--text-muted);"></i>
                    <?php else: ?>
                        <i class="ph ph-video-camera" style="font-size:2rem; color:var(--text-muted);"></i>
                    <?php endif; ?>
                </td>
                <td style="text-transform:capitalize;"><?=$typeStr?></td>
                <td style="text-transform:capitalize;"><?=htmlspecialchars($subStr ?: '-')?></td>
                <td><?=htmlspecialchars($m['caption'])?></td>
                <td><?=htmlspecialchars($m['sort_order'])?></td>
                <td>
                    <a href="<?=htmlspecialchars($display_link)?>" target="_blank" style="margin-right:8px; color:var(--primary);"><i class="ph ph-eye" style="font-size:1.2rem;"></i></a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete media file?');">
                        <input type="hidden" name="action" value="delete_gallery_media">
                        <input type="hidden" name="m_id" value="<?=$m['id']?>">
                        <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer;"><i class="ph ph-trash" style="font-size:1.2rem;"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php else: ?><div class="empty">No media uploaded yet.</div><?php endif; ?>
    </div>

    <div class="dash-card">
      <h3><i class="ph ph-plus-circle"></i> Add Media or Document</h3>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_gallery_media">
        <div class="form-grid">
            <div class="form-group">
                <label>Media Type</label>
                <select name="media_type" required id="media_type_select">
                    <option value="image">Image</option>
                    <option value="video">Video</option>
                    <option value="document">Document</option>
                    <option value="360">360 Tour Embed</option>
                </select>
            </div>
            <div class="form-group">
                <label>Category Sub-type</label>
                <select name="sub_type" id="sub_type_select">
                    <option value="">None</option>
                    <optgroup label="Images" id="opt_img">
                        <option value="campus">Campus</option><option value="lab">Lab</option>
                        <option value="hostel">Hostel</option><option value="event">Event</option>
                        <option value="classroom">Classroom</option>
                    </optgroup>
                    <optgroup label="Videos" id="opt_vid">
                        <option value="tour">Tour</option><option value="placement">Placement</option>
                        <option value="event">Event</option><option value="alumni_talk">Alumni Talk</option>
                    </optgroup>
                    <optgroup label="Documents" id="opt_doc">
                        <option value="brochure">Brochure</option><option value="prospectus">Prospectus</option>
                        <option value="annual_report">Annual Report</option><option value="ranking_cert">Ranking Cert</option>
                    </optgroup>
                </select>
            </div>
            <div class="form-group full" id="file_group">
                <label>Upload File</label>
                <input type="file" name="media_file" id="media_file_input" accept="image/*">
            </div>
            <div class="form-group full" id="url_group">
                <label>Or Embed Link / Direct URL (Matterport, YouTube, Google Drive)</label>
                <input type="url" name="url">
            </div>
            <div class="form-group full">
                <label>Caption / Title Text</label>
                <input type="text" name="caption" placeholder="Short description of the media">
            </div>
            <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="0">
            </div>
        </div>
        <div style="text-align: right; margin-top:16px;">
            <button type="submit" class="btn btn-primary">Add Media</button>
        </div>
      </form>
    </div>

    <script>
    document.getElementById('media_type_select')?.addEventListener('change', function() {
        var type = this.value;
        var fileGroup = document.getElementById('file_group');
        var urlGroup = document.getElementById('url_group');
        var fileInput = document.getElementById('media_file_input');
        
        if (type === '360') {
            fileGroup.style.display = 'none';
            if(fileInput) fileInput.removeAttribute('accept');
            urlGroup.style.display = 'block';
        } else {
            fileGroup.style.display = 'block';
            urlGroup.style.display = 'block';
            if(fileInput) {
                if (type === 'image') fileInput.setAttribute('accept', 'image/*');
                else if (type === 'video') fileInput.setAttribute('accept', 'video/*');
                else if (type === 'document') fileInput.setAttribute('accept', '.pdf,.doc,.docx');
            }
        }
    });
    document.getElementById('media_type_select')?.dispatchEvent(new Event('change'));
    </script>

    <?php elseif($tab==='faqs'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-question"></i> Frequently Asked Questions (<?=count($faqs)?>)</h3>
      <?php if($faqs): ?>
      <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Category</th><th>Question</th><th>Answer</th><th>Order</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach($faqs as $f): ?>
            <tr>
                <td style="font-weight:700; color:var(--primary);"><?=htmlspecialchars($f['category'])?></td>
                <td style="font-weight:600;"><?=htmlspecialchars($f['question_text'])?></td>
                <td><?=nl2br(htmlspecialchars($f['answer_text']))?></td>
                <td><?=$f['sort_order']?></td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete FAQ?');">
                        <input type="hidden" name="action" value="delete_faq">
                        <input type="hidden" name="faq_id" value="<?=$f['id']?>">
                        <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer;" title="Delete"><i class="ph ph-trash" style="font-size:1.2rem;"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach;?>
        </tbody>
      </table>
      </div>
      <?php else: ?><div class="empty">No FAQs added yet.</div><?php endif; ?>
    </div>

    <div class="dash-card">
      <h3><i class="ph ph-plus-circle"></i> Add New FAQ</h3>
      <form method="POST">
        <input type="hidden" name="action" value="add_faq">
        <div class="form-group">
            <label>FAQ Category</label>
            <input type="text" name="category" placeholder="e.g. Admission, Fees, Hostels" required>
        </div>
        <div class="form-group">
            <label>Question Text *</label>
            <input type="text" name="question_text" required>
        </div>
        <div class="form-group">
            <label>Answer Text *</label>
            <textarea name="answer_text" rows="3" required></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="0">
            </div>
            <div class="form-group checkbox-group" style="padding-top:24px;">
                <input type="checkbox" name="is_active" checked id="actfaq">
                <label for="actfaq">FAQ Active (Show to students)</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="ph ph-plus"></i> Add FAQ</button>
      </form>
    </div>

    <?php elseif($tab==='faculty'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-users"></i> Faculty Members (<?=count($faculty)?>)</h3>
      <?php if($faculty): ?>
      <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Photo</th><th>Name</th><th>Designation</th><th>Department</th><th>Qualification</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach($faculty as $f): 
                $photo = $f['photo_url'] ? '../' . $f['photo_url'] : '/ADMISSION/assets/images/faculty_placeholder.png';
            ?>
            <tr>
                <td><img src="<?=htmlspecialchars($photo)?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;"></td>
                <td style="font-weight:600; color:var(--primary);"><?=htmlspecialchars($f['faculty_name'])?></td>
                <td><?=htmlspecialchars($f['designation'])?></td>
                <td><?=htmlspecialchars($f['department'])?></td>
                <td><?=htmlspecialchars($f['qualification'])?> (<?=$f['experience_years']?> yrs exp)</td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete faculty member?');">
                        <input type="hidden" name="action" value="delete_faculty">
                        <input type="hidden" name="faculty_id" value="<?=$f['id']?>">
                        <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer;" title="Delete"><i class="ph ph-trash" style="font-size:1.2rem;"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach;?>
        </tbody>
      </table>
      </div>
      <?php else: ?><div class="empty">No faculty members added yet.</div><?php endif; ?>
    </div>

    <div class="dash-card">
      <h3><i class="ph ph-plus-circle"></i> Add Faculty Member</h3>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_faculty">
        <div class="form-grid">
            <div class="form-group">
                <label>Faculty Name *</label>
                <input type="text" name="faculty_name" required>
            </div>
            <div class="form-group">
                <label>Designation *</label>
                <input type="text" name="designation" placeholder="e.g. Associate Professor" required>
            </div>
            <div class="form-group">
                <label>Department *</label>
                <input type="text" name="department" placeholder="e.g. Computer Science" required>
            </div>
            <div class="form-group">
                <label>Qualification *</label>
                <input type="text" name="qualification" placeholder="e.g. M.Tech, PhD" required>
            </div>
            <div class="form-group">
                <label>Specialization</label>
                <input type="text" name="specialization" placeholder="e.g. Machine Learning, Cryptography">
            </div>
            <div class="form-group">
                <label>Ph.D From</label>
                <input type="text" name="phd_from" placeholder="University name">
            </div>
            <div class="form-group">
                <label>Experience (Years)</label>
                <input type="number" name="experience_years" value="0">
            </div>
            <div class="form-group">
                <label>Research Papers Published</label>
                <input type="number" name="research_papers" value="0">
            </div>
            <div class="form-group">
                <label>LinkedIn Profile URL</label>
                <input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/username">
            </div>
            <div class="form-group">
                <label>Faculty Photo</label>
                <input type="file" name="photo_file" accept="image/*">
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="ph ph-plus"></i> Add Faculty</button>
      </form>
    </div>

    <?php elseif($tab==='updates'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-megaphone"></i> News & Announcements (<?=count($updates)?>)</h3>
      <?php if($updates): ?>
      <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Title</th><th>Type</th><th>Event Date</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach($updates as $u): ?>
            <tr>
                <td style="font-weight:600; color:var(--primary);"><?=htmlspecialchars($u['title'])?></td>
                <td style="text-transform:capitalize;"><span class="badge badge-blue"><?=$u['update_type']?></span></td>
                <td><?=$u['event_date'] ? date('d M Y', strtotime($u['event_date'])) : '-'?></td>
                <td><span class="badge <?=($u['status']==='published'?'badge-green':'badge-yellow')?>"><?=ucfirst($u['status'])?></span></td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete announcement?');">
                        <input type="hidden" name="action" value="delete_update">
                        <input type="hidden" name="update_id" value="<?=$u['id']?>">
                        <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer;" title="Delete"><i class="ph ph-trash" style="font-size:1.2rem;"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach;?>
        </tbody>
      </table>
      </div>
      <?php else: ?><div class="empty">No news or updates posted yet.</div><?php endif; ?>
    </div>

    <div class="dash-card">
      <h3><i class="ph ph-plus-circle"></i> Post News / Announcement</h3>
      <form method="POST">
        <input type="hidden" name="action" value="add_update">
        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" required placeholder="e.g. Admissions 2026-27 Open for B.Tech Courses">
        </div>
        <div class="form-group">
            <label>Announcement Type</label>
            <select name="update_type">
                <option value="admission">Admission Notice</option>
                <option value="placement">Placement news</option>
                <option value="exam">Exam Schedule</option>
                <option value="event">Campus Event</option>
                <option value="ranking">Achievements & Ranking</option>
                <option value="scholarship">Scholarships Update</option>
                <option value="general">General Notice</option>
            </select>
        </div>
        <div class="form-group">
            <label>Content Description *</label>
            <textarea name="description" rows="5" required></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Event / Date</label>
                <input type="date" name="event_date">
            </div>
            <div class="form-group">
                <label>Action URL (Link details)</label>
                <input type="url" name="action_url">
            </div>
        </div>
        <div class="form-group">
            <label>Publish Status</label>
            <select name="status">
                <option value="published">Publish Immediately</option>
                <option value="draft">Save as Draft</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="ph ph-plus"></i> Post News / Blog / Update</button>
      </form>
    </div>

    <?php elseif($tab==='qna'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-chats-teardrop"></i> Student Q&A Thread (<?=count($qna)?> questions)</h3>
      <?php if($qna): ?>
      <?php foreach($qna as $q): ?>
      <div style="border: 1px solid var(--border-color); border-radius:12px; padding:18px; margin-bottom:16px; background:#fafbfc;">
          <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
              <span style="font-size:0.72rem; color:var(--text-muted); font-weight:700;"><i class="ph ph-user"></i> Asked by Student</span>
              <span class="badge <?=($q['status']==='approved'?'badge-green':'badge-yellow')?>"><?=ucfirst($q['status'])?></span>
          </div>
          <p style="font-weight:600; color:var(--primary); margin-bottom:10px;">Q: <?=htmlspecialchars($q['question_text'])?></p>
          
          <?php if(!empty($q['answer_text'])): ?>
              <div style="margin-top:10px; padding:12px; background:#f0f4ff; border-radius:8px; border-left:4px solid var(--secondary);">
                  <p style="font-size:0.8rem; font-weight:500; color:var(--text-dark);"><strong>A:</strong> <?=nl2br(htmlspecialchars($q['answer_text']))?></p>
              </div>
          <?php else: ?>
              <form method="POST" style="margin-top:12px;">
                  <input type="hidden" name="action" value="answer_qna">
                  <input type="hidden" name="qna_id" value="<?=$q['id']?>">
                  <div class="form-group" style="margin-bottom:8px;">
                      <label style="font-size:0.7rem;">Submit Answer</label>
                      <textarea name="answer_text" rows="2" placeholder="Write your reply here..." required></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary btn-sm"><i class="ph ph-paper-plane"></i> Submit Reply</button>
              </form>
          <?php endif; ?>
      </div>
      <?php endforeach;?>
      <?php else: ?><div class="empty">No student questions posted yet.</div><?php endif; ?>
    </div>

    <?php elseif($tab==='categories'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-folders"></i> Global Course Categories</h3>
      <p style="color:var(--text-muted); font-size:0.8rem; margin-bottom:16px;">These are the global categories defined by the main admin. Your courses can belong to these categories.</p>
      <?php if($global_categories): ?>
      <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Category Name</th><th>Slug</th><th>Parent Category</th></tr></thead>
        <tbody>
            <?php foreach($global_categories as $cat): ?>
            <tr>
                <td style="font-weight:600; color:var(--primary); display:flex; align-items:center; gap:8px;">
                    <?php if($cat['icon_url']): ?>
                        <img src="<?=preg_match('/^https?:\/\//', $cat['icon_url']) ? htmlspecialchars($cat['icon_url']) : '../' . htmlspecialchars($cat['icon_url']);?>" style="width:24px; height:24px; object-fit:cover; border-radius:4px;">
                    <?php endif; ?>
                    <?=htmlspecialchars($cat['category_name'])?>
                </td>
                <td><code><?=htmlspecialchars($cat['category_slug'])?></code></td>
                <td><?=$cat['parent_name'] ? htmlspecialchars($cat['parent_name']) : '-'?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php endif;?>
    </div>

    <?php elseif($tab==='leads'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-users-three"></i> Enquiries & Leads (<?=count($leads)?>)</h3>
      <?php if($leads): ?>
      <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Course Interest</th><th>Received Date</th></tr></thead>
        <tbody>
        <?php foreach($leads as $l): ?>
        <tr>
          <td style="font-weight:600"><?=htmlspecialchars($l['full_name']??$l['name']??'-')?></td>
          <td><?=htmlspecialchars($l['email']??'-')?></td>
          <td><?=htmlspecialchars($l['phone']??'-')?></td>
          <td><?=htmlspecialchars($l['course_interest']??$l['course']??'-')?></td>
          <td><?=date('d M Y h:i A', strtotime($l['created_at']))?></td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
      </div>
      <?php else: ?><div class="empty">No enquiries received yet.</div><?php endif;?>
    </div>

    <?php elseif($tab==='submissions'): ?>
    <div class="dash-card">
      <h3><i class="ph ph-clock-countdown"></i> Account Submissions History</h3>
      <?php if($pendingSubs): ?>
      <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Type</th><th>Status</th><th>Submitted Date</th><th>Admin Note</th></tr></thead>
        <tbody>
        <?php foreach($pendingSubs as $ps): ?>
        <tr>
          <td style="font-weight:600"><?=ucfirst(str_replace('_',' ',$ps['submission_type']))?></td>
          <td><span class="badge <?=($ps['status']==='approved'?'badge-green':($ps['status']==='rejected'?'badge-red':'badge-yellow'))?>"><?=ucfirst($ps['status'])?></span></td>
          <td><?=date('d M Y', strtotime($ps['created_at']))?></td>
          <td><?=htmlspecialchars($ps['admin_note']??'-')?></td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
      </div>
      <?php else: ?><div class="empty">No submissions history yet.</div><?php endif;?>
    </div>

    <?php endif; ?>
    <?php endif; ?>
  </main>
</div>
<script>
$(document).ready(function() {
    if (typeof $.fn.trumbowyg === 'function') {
        $('textarea[name="about_text"], textarea[name="admission_process"], textarea[name="description"]').trumbowyg({
            btns: [
                ['viewHTML'],
                ['undo', 'redo'],
                ['formatting'],
                ['strong', 'em', 'del'],
                ['superscript', 'subscript'],
                ['link'],
                ['insertImage'],
                ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
                ['unorderedList', 'orderedList'],
                ['horizontalRule'],
                ['removeformat'],
                ['fullscreen']
            ]
        });
    }
});
</script>
<style>
.trumbowyg-box, .trumbowyg-editor {
    border-radius: 10px;
    background: #f8fafc !important;
    font-family: inherit;
    border-color: var(--border-color) !important;
}
.trumbowyg-button-pane {
    background: #f1f5f9 !important;
    border-bottom: 1px solid var(--border-color) !important;
}
</style>
</body>
</html>
