<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success_msg = '';
$error_msg = '';

// Get fallback user ID if not logged in
$user_id = $_SESSION['user_id'] ?? 'user-1234-uuid';

// Handle Question Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ask_question') {
    $question_text = trim($_POST['question_text'] ?? '');
    $category = $_POST['question_category'] ?? 'general';

    if (empty($question_text)) {
        $error_msg = 'Question text cannot be empty.';
    } else {
        try {
            $qId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));

            $stmt = $pdo->prepare("
                INSERT INTO questions (id, question_text, question_category, asked_by, views, upvotes, answer_count, status) 
                VALUES (?, ?, ?, ?, 0, 0, 0, 'open')
            ");
            $stmt->execute([$qId, $question_text, $category, $user_id]);
            
            // Redirect to home tab to see the question
            header("Location: community?tab=qna&msg=posted");
            exit;
        } catch (Exception $e) {
            $error_msg = 'Failed to post question: ' . $e->getMessage();
        }
    }
}

// Handle Answer Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_answer') {
    $question_id = $_POST['question_id'] ?? '';
    $answer_text = trim($_POST['answer_text'] ?? '');

    if (empty($question_id) || empty($answer_text)) {
        $error_msg = 'Answer text cannot be empty.';
    } else {
        try {
            $ansId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));

            // Check if admin is answering (to set expert badge)
            $isAdmin = false;
            $userRoleStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $userRoleStmt->execute([$user_id]);
            $userEmail = $userRoleStmt->fetchColumn() ?: '';
            if (strpos($userEmail, 'admin') !== false) {
                $isAdmin = true;
            }

            $stmt = $pdo->prepare("
                INSERT INTO answers (id, question_id, answer_text, answered_by, is_expert_answer, upvotes, is_accepted) 
                VALUES (?, ?, ?, ?, ?, 0, 0)
            ");
            $stmt->execute([$ansId, $question_id, $answer_text, $user_id, $isAdmin ? 1 : 0]);

            // Update answer count in questions
            $pdo->prepare("UPDATE questions SET answer_count = answer_count + 1, status = 'answered' WHERE id = ?")->execute([$question_id]);

            header("Location: community?tab=qna&msg=answered");
            exit;
        } catch (Exception $e) {
            $error_msg = 'Failed to submit answer: ' . $e->getMessage();
        }
    }
}

// Fetch tab state
$tab = $_GET['tab'] ?? 'qna';
if (!in_array($tab, ['qna', 'discussions', 'unanswered', 'ask'])) {
    $tab = 'qna';
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'posted') $success_msg = 'Your question has been posted successfully!';
    if ($_GET['msg'] === 'answered') $success_msg = 'Your answer has been posted!';
}

// Search & Tag Filter Query
$search = trim($_GET['q'] ?? '');
$searchParam = '%' . $search . '%';
$tag = trim($_GET['tag'] ?? '');
$tagParam = '%' . $tag . '%';

// Database queries based on selected Tab
try {
    $where = [];
    $params = [];

    if ($tab === 'unanswered') {
        $where[] = "q.answer_count = 0";
    } elseif ($tab === 'discussions') {
        $where[] = "q.question_category = 'general'";
    }

    if ($search !== '') {
        $where[] = "q.question_text LIKE ?";
        $params[] = $searchParam;
    }

    if ($tag !== '') {
        $where[] = "(q.question_text LIKE ? OR q.question_category = ?)";
        $params[] = $tagParam;
        $params[] = $tag;
    }

    $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    $qQuery = "SELECT q.*, u.full_name AS asker_name 
               FROM questions q 
               LEFT JOIN users u ON q.asked_by = u.id 
               $whereSql 
               ORDER BY " . ($tab === 'discussions' ? "q.views DESC" : "q.is_featured DESC, q.trending_score DESC, q.created_at DESC") . " LIMIT 50";

    $qStmt = $pdo->prepare($qQuery);
    $qStmt->execute($params);
    $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Answers for loaded questions (grouped by question_id)
    $answers = [];
    if (!empty($questions)) {
        $questionIds = array_column($questions, 'id');
        $inClause = implode(',', array_fill(0, count($questionIds), '?'));
        
        $ansStmt = $pdo->prepare("
            SELECT a.*, u.full_name AS replier_name, u.email AS replier_email
            FROM answers a
            LEFT JOIN users u ON a.answered_by = u.id
            WHERE a.question_id IN ($inClause)
            ORDER BY a.is_expert_answer DESC, a.upvotes DESC, a.created_at ASC
        ");
        $ansStmt->execute($questionIds);
        
        foreach ($ansStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $answers[$row['question_id']][] = $row;
        }
    }

    // Fetch Expert Panelists
    $experts = $pdo->query("SELECT * FROM experts ORDER BY answer_count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $questions = [];
    $answers = [];
    $experts = [];
}

// Helpers
function getAvatarColor($name) {
    $colors = ['#1e3a8a', '#1e40af', '#14532d', '#7c2d12', '#4c1d95', '#0f766e', '#831843', '#475569', '#111827'];
    $hash = crc32((string)$name);
    return $colors[abs($hash) % count($colors)];
}

function getCategoryIcon($cat) {
    switch ($cat) {
        case 'admission': return 'ph ph-graduation-cap';
        case 'fees': return 'ph ph-currency-inr';
        case 'placements': return 'ph ph-briefcase';
        case 'hostel': return 'ph ph-house-line';
        case 'exams': return 'ph ph-exam';
        default: return 'ph ph-hash';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Q&A Community Forum | AdmissionSeason</title>
  <meta name="description" content="Join India's largest educational community. Ask career questions, get verified expert responses, and participate in admissions discussions.">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=9">

  <style>
    :root {
      --oxford-navy: #0B2447;
      --yale-blue: #19376D;
      --snow-pearl: #F8FAFC;
      --ink-black: #0F172A;
      --text-muted-alt: #64748b;
      --border-color-alt: #e2e8f0;
      --success-green: #10b981;
      --expert-badge-bg: rgba(217, 119, 6, 0.08);
      --expert-badge-color: #d97706;
    }

    body {
      background-color: var(--snow-pearl);
      color: var(--ink-black);
      font-family: 'Inter', sans-serif;
    }

    /* Hero Header */
    .qna-hero {
      background: linear-gradient(135deg, var(--oxford-navy) 0%, var(--yale-blue) 100%);
      color: #fff;
      padding: 70px 0 60px 0;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .qna-hero::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: radial-gradient(circle at 80% 20%, rgba(25, 55, 109, 0.3) 0%, transparent 60%);
      pointer-events: none;
    }

    .qna-hero h1 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 2.85rem;
      font-weight: 800;
      margin-bottom: 12px;
      letter-spacing: -0.02em;
    }

    .qna-hero p {
      font-size: 1.12rem;
      opacity: 0.9;
      max-width: 620px;
      margin: 0 auto 32px auto;
      line-height: 1.6;
    }

    .qna-search-bar {
      max-width: 580px;
      margin: 0 auto 40px auto;
      background: #fff;
      border-radius: 100px;
      padding: 8px 12px;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 15px 35px rgba(11, 36, 71, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .qna-search-bar i {
      color: var(--text-muted-alt);
      font-size: 1.4rem;
      margin-left: 12px;
    }

    .qna-search-bar input {
      flex: 1;
      border: none;
      outline: none;
      font-size: 1.05rem;
      font-family: inherit;
      padding: 8px 0;
      color: var(--ink-black);
    }

    .qna-search-bar button {
      background: var(--oxford-navy);
      color: #fff;
      border: none;
      padding: 10px 28px;
      border-radius: 100px;
      font-weight: 700;
      cursor: pointer;
      font-size: 0.95rem;
      transition: all 0.2s ease;
    }

    .qna-search-bar button:hover {
      background: var(--yale-blue);
      transform: scale(1.02);
    }

    /* Community Stats Ticker */
    .community-stats {
      display: flex;
      justify-content: center;
      gap: 50px;
      max-width: 800px;
      margin: 0 auto;
      border-top: 1px solid rgba(255, 255, 255, 0.15);
      padding-top: 30px;
    }

    .stat-item {
      text-align: center;
    }

    .stat-val {
      display: block;
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.75rem;
      font-weight: 800;
      color: #fff;
    }

    .stat-lbl {
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      opacity: 0.75;
      margin-top: 4px;
      display: block;
    }

    /* Content Layout */
    .qna-container {
      padding: 50px 0 100px 0;
      display: grid;
      grid-template-columns: 1.35fr 0.65fr;
      gap: 40px;
      align-items: start;
    }

    /* Segmented Tab Switcher */
    .qna-tabs-container {
      border-bottom: 2px solid #e2e8f0;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .qna-tabs {
      display: flex;
      gap: 6px;
      overflow-x: auto;
      padding-bottom: 0px;
    }

    .qna-tab-btn {
      background: none;
      border: none;
      color: var(--text-muted-alt);
      font-size: 1rem;
      font-weight: 700;
      padding: 14px 22px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      white-space: nowrap;
      border-bottom: 3px solid transparent;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .qna-tab-btn:hover {
      color: var(--yale-blue);
    }

    .qna-tab-btn.active {
      color: var(--oxford-navy);
      border-bottom-color: var(--yale-blue);
    }

    /* Hashtags Filter Bar */
    .discussions-tags-bar {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 24px;
      background: #fff;
      padding: 12px 20px;
      border-radius: 12px;
      border: 1px solid var(--border-color-alt);
    }

    .tag-title {
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--oxford-navy);
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .tag-pill {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-muted-alt);
      background: var(--snow-pearl);
      padding: 6px 14px;
      border-radius: 100px;
      text-decoration: none;
      border: 1px solid var(--border-color-alt);
      transition: all 0.2s ease;
    }

    .tag-pill:hover, .tag-pill.active {
      background: var(--yale-blue);
      color: #fff;
      border-color: var(--yale-blue);
    }

    /* Alerts */
    .alert {
      padding: 14px 20px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-size: 0.92rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .alert-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    /* Interactive Expandable Question Cards */
    .q-card {
      background: #fff;
      border: 1px solid var(--border-color-alt);
      border-radius: 20px;
      padding: 26px;
      margin-bottom: 24px;
      transition: all 0.3s ease;
      cursor: pointer;
      box-shadow: 0 4px 15px rgba(11, 36, 71, 0.02);
    }

    .q-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(11, 36, 71, 0.06);
      border-color: rgba(25, 55, 109, 0.2);
    }

    .q-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 18px;
      gap: 15px;
    }

    .q-asker-profile {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .asker-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 700;
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.05rem;
    }

    .asker-meta {
      display: flex;
      flex-direction: column;
      text-align: left;
    }

    .asker-name {
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--oxford-navy);
    }

    .asker-date {
      font-size: 0.76rem;
      color: var(--text-muted-alt);
      margin-top: 1px;
    }

    .q-category {
      font-size: 0.72rem;
      font-weight: 800;
      padding: 6px 14px;
      border-radius: 100px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .q-category.cat-general { background: rgba(25, 55, 109, 0.08); color: var(--yale-blue); }
    .q-category.cat-placements { background: rgba(16, 185, 129, 0.08); color: #10b981; }
    .q-category.cat-admission { background: rgba(217, 119, 6, 0.08); color: #d97706; }
    .q-category.cat-exams { background: rgba(219, 39, 119, 0.08); color: #db2777; }
    .q-category.cat-fees { background: rgba(79, 70, 229, 0.08); color: #4f46e5; }
    .q-category.cat-hostel { background: rgba(13, 148, 136, 0.08); color: #0d9488; }

    .q-text {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--oxford-navy);
      line-height: 1.45;
      margin-bottom: 20px;
      text-align: left;
    }

    .q-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px solid var(--border-color-alt);
      padding-top: 18px;
      margin-top: 18px;
    }

    .q-footer-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .q-footer-right {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .action-btn {
      background: var(--snow-pearl);
      border: 1px solid var(--border-color-alt);
      color: var(--text-muted-alt);
      padding: 8px 16px;
      border-radius: 100px;
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s ease;
    }

    .action-btn:hover {
      background: rgba(25, 55, 109, 0.05);
      color: var(--yale-blue);
      border-color: rgba(25, 55, 109, 0.2);
    }

    .upvote-q-btn.upvoted {
      background: rgba(25, 55, 109, 0.08);
      color: var(--yale-blue);
      border-color: var(--yale-blue);
    }

    .q-view-indicator {
      font-size: 0.82rem;
      color: var(--text-muted-alt);
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-weight: 650;
    }

    .read-ans-trigger {
      color: var(--yale-blue);
      font-size: 0.85rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 4px;
      transition: all 0.2s;
    }

    /* Expanded Details Thread */
    .q-expanded {
      display: none;
      margin-top: 24px;
      padding-top: 24px;
      border-top: 1.5px dashed var(--border-color-alt);
      text-align: left;
    }

    .q-card.open .q-expanded {
      display: block;
    }

    .ans-title {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.1rem;
      font-weight: 750;
      color: var(--oxford-navy);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .answers-list {
      margin-bottom: 20px;
    }

    .ans-item {
      background: var(--snow-pearl);
      border: 1px solid var(--border-color-alt);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 16px;
      transition: all 0.2s ease;
      position: relative;
    }

    .ans-item.expert-ans-item {
      background: rgba(217, 119, 6, 0.02);
      border-color: rgba(217, 119, 6, 0.2);
    }

    .ans-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }

    .ans-replier-profile {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .replier-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.85rem;
      color: #fff;
    }

    .replier-meta {
      display: flex;
      flex-direction: column;
    }

    .replier-name {
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--oxford-navy);
    }

    .replier-role {
      font-size: 0.72rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 3px;
      margin-top: 1px;
    }

    .replier-role.role-expert { color: var(--expert-badge-color); }
    .replier-role.role-alumni { color: var(--yale-blue); }
    .replier-role.role-student { color: var(--text-muted-alt); }

    .ans-time {
      font-size: 0.76rem;
      color: var(--text-muted-alt);
    }

    .ans-body {
      font-size: 0.95rem;
      line-height: 1.6;
      color: #334155;
      margin-bottom: 14px;
    }

    .ans-footer {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .upvote-ans-btn {
      padding: 6px 14px;
      font-size: 0.8rem;
    }

    .upvote-ans-btn.upvoted {
      background: rgba(25, 55, 109, 0.08);
      color: var(--yale-blue);
      border-color: var(--yale-blue);
    }

    /* Reply Form Box */
    .inline-ans-form {
      margin-top: 24px;
      display: flex;
      gap: 16px;
      align-items: flex-start;
    }

    .inline-ans-form textarea {
      flex: 1;
      min-height: 90px;
      padding: 16px;
      border: 1.5px solid var(--border-color-alt);
      border-radius: 14px;
      font-family: inherit;
      font-size: 0.95rem;
      outline: none;
      box-sizing: border-box;
      resize: vertical;
      transition: all 0.3s;
    }

    .inline-ans-form textarea:focus {
      border-color: var(--yale-blue);
      box-shadow: 0 0 0 3px rgba(25, 55, 109, 0.08);
    }

    .inline-ans-submit {
      background: var(--yale-blue);
      color: #fff;
      border: none;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.88rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
      align-self: flex-end;
    }

    .inline-ans-submit:hover {
      background: var(--oxford-navy);
    }

    /* Ask Question Form Panel */
    .ask-form-panel {
      background: #fff;
      border: 1px solid var(--border-color-alt);
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 15px 30px rgba(11, 36, 71, 0.03);
      text-align: left;
    }

    .ask-form-panel h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.65rem;
      font-weight: 800;
      color: var(--oxford-navy);
      margin-bottom: 24px;
      border-bottom: 1.5px solid var(--border-color-alt);
      padding-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .form-group {
      margin-bottom: 24px;
    }

    .form-group label {
      display: block;
      font-weight: 700;
      margin-bottom: 8px;
      font-size: 0.92rem;
      color: var(--oxford-navy);
    }

    .form-control {
      width: 100%;
      padding: 14px 18px;
      border: 1.5px solid var(--border-color-alt);
      border-radius: 12px;
      background: #fff;
      font-size: 0.96rem;
      font-family: inherit;
      color: var(--ink-black);
      box-sizing: border-box;
      outline: none;
      transition: all 0.3s ease;
      appearance: none;
    }

    textarea.form-control {
      min-height: 130px;
      resize: vertical;
      line-height: 1.5;
    }

    select.form-control {
      background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 18px center;
      background-size: 16px;
      padding-right: 45px;
      cursor: pointer;
    }

    .form-control:focus {
      border-color: var(--yale-blue);
      box-shadow: 0 0 0 4px rgba(25, 55, 109, 0.08);
    }

    .submit-q-btn {
      background: var(--oxford-navy);
      color: #fff;
      border: none;
      padding: 14px 30px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 0.96rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 4px 12px rgba(11, 36, 71, 0.15);
      transition: all 0.3s;
    }

    .submit-q-btn:hover {
      background: var(--yale-blue);
      box-shadow: 0 8px 24px rgba(25, 55, 109, 0.25);
      transform: translateY(-2px);
    }

    /* Sidebar Panels */
    .sidebar-panel {
      background: #fff;
      border: 1px solid var(--border-color-alt);
      border-radius: 20px;
      padding: 26px;
      margin-bottom: 24px;
      text-align: left;
    }

    .sidebar-panel h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.2rem;
      font-weight: 800;
      color: var(--oxford-navy);
      margin-bottom: 20px;
      border-bottom: 1px solid var(--border-color-alt);
      padding-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .expert-card {
      display: flex;
      gap: 14px;
      align-items: center;
      margin-bottom: 18px;
      padding-bottom: 18px;
      border-bottom: 1px solid var(--border-color-alt);
    }

    .expert-card:last-child {
      margin-bottom: 0;
      padding-bottom: 0;
      border-bottom: none;
    }

    .expert-avatar {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--border-color-alt);
      flex-shrink: 0;
    }

    .expert-info h4 {
      font-size: 0.95rem;
      font-weight: 750;
      color: var(--oxford-navy);
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .expert-info p {
      font-size: 0.8rem;
      color: var(--text-muted-alt);
      margin-top: 3px;
      line-height: 1.3;
    }

    .expert-spec {
      background: rgba(25, 55, 109, 0.08);
      color: var(--yale-blue);
      font-size: 0.72rem;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 6px;
      margin-top: 6px;
      display: inline-block;
    }

    .review-cta-box {
      background: linear-gradient(135deg, var(--yale-blue) 0%, var(--oxford-navy) 100%);
      color: #fff;
      border-radius: 20px;
      padding: 35px 30px;
      text-align: center;
    }

    .review-cta-box h4 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.35rem;
      font-weight: 700;
      margin-bottom: 10px;
      letter-spacing: -0.01em;
    }

    .review-cta-box p {
      font-size: 0.88rem;
      opacity: 0.88;
      margin-bottom: 22px;
      line-height: 1.5;
    }

    .review-btn-link {
      display: inline-block;
      background: #fff;
      color: var(--oxford-navy);
      text-decoration: none;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 750;
      font-size: 0.9rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transition: all 0.2s;
    }

    .review-btn-link:hover {
      background: var(--snow-pearl);
      transform: translateY(-2px);
    }

    /* Toast Notification */
    .toast-notif {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: var(--oxford-navy);
      color: #fff;
      padding: 12px 24px;
      border-radius: 8px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      font-weight: 600;
      font-size: 0.9rem;
      z-index: 9999;
      transform: translateY(100px);
      opacity: 0;
      transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
    }

    .toast-notif.show {
      transform: translateY(0);
      opacity: 1;
    }

    /* Responsive Media Queries */
    @media (max-width: 992px) {
      .qna-container {
        grid-template-columns: 1fr;
        padding: 40px 20px;
        gap: 30px;
      }
      
      .qna-hero {
        padding: 50px 20px;
      }
      
      .qna-hero h1 {
        font-size: 2.2rem;
      }
    }

    @media (max-width: 768px) {
      .community-stats {
        flex-direction: column;
        gap: 20px;
        padding-top: 20px;
      }
      
      .qna-tabs-container {
        overflow-x: auto;
      }
      
      .q-card {
        padding: 18px;
      }
      
      .q-text {
        font-size: 1.1rem;
      }
      
      .q-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 14px;
      }
      
      .q-footer-right {
        width: 100%;
        justify-content: space-between;
        border-top: 1px solid var(--border-color-alt);
        padding-top: 10px;
      }
      
      .inline-ans-form {
        flex-direction: column;
      }
      
      .inline-ans-submit {
        align-self: stretch;
        justify-content: center;
        margin-top: 10px;
      }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Hero banner -->
<section class="qna-hero">
  <div class="container">
    <h1>Education & Career Q&A Forum</h1>
    <p>Ask questions regarding admissions, placements, fees, and exams, and get verified answers from expert counsellors and college alumni.</p>
    
    <!-- Search Bar -->
    <form method="GET" action="community" class="qna-search-bar">
      <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
      <?php if($tag): ?><input type="hidden" name="tag" value="<?= htmlspecialchars($tag) ?>"><?php endif; ?>
      <i class="ph ph-magnifying-glass"></i>
      <input type="text" name="q" placeholder="Type your query (e.g. placements, admission)..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit">Search</button>
    </form>

    <!-- Stats Ticker -->
    <div class="community-stats">
      <div class="stat-item">
        <span class="stat-val">12,400+</span>
        <span class="stat-lbl">Queries Resolved</span>
      </div>
      <div class="stat-item">
        <span class="stat-val">50+</span>
        <span class="stat-lbl">Expert Counselors</span>
      </div>
      <div class="stat-item">
        <span class="stat-val">98.5%</span>
        <span class="stat-lbl">Response Rate</span>
      </div>
    </div>
  </div>
</section>

<!-- Content Container -->
<section class="container qna-container">
  
  <!-- Left Side: Tabs and Question Lists -->
  <div>
    <!-- Tabs Menu -->
    <div class="qna-tabs-container">
      <div class="qna-tabs">
        <a href="community?tab=qna" class="qna-tab-btn <?= $tab === 'qna' ? 'active' : '' ?>">
          <i class="ph ph-chats-teardrop"></i> Q&A Home
        </a>
        <a href="community?tab=discussions" class="qna-tab-btn <?= $tab === 'discussions' ? 'active' : '' ?>">
          <i class="ph ph-users"></i> Discussions
        </a>
        <a href="community?tab=unanswered" class="qna-tab-btn <?= $tab === 'unanswered' ? 'active' : '' ?>">
          <i class="ph ph-question"></i> Unanswered
        </a>
        <a href="community?tab=ask" class="qna-tab-btn <?= $tab === 'ask' ? 'active' : '' ?>">
          <i class="ph ph-plus-circle"></i> Ask a Question
        </a>
      </div>
    </div>

    <!-- Tags Row for Discussions / Q&A -->
    <?php if ($tab === 'qna' || $tab === 'discussions'): ?>
      <div class="discussions-tags-bar">
        <span class="tag-title"><i class="ph ph-trend-up"></i> Topics:</span>
        <a href="community?tab=<?= $tab ?>" class="tag-pill <?= $tag === '' ? 'active' : '' ?>">All</a>
        <a href="community?tab=<?= $tab ?>&tag=MBA" class="tag-pill <?= $tag === 'MBA' ? 'active' : '' ?>">#MBA</a>
        <a href="community?tab=<?= $tab ?>&tag=BTech" class="tag-pill <?= $tag === 'BTech' ? 'active' : '' ?>">#BTech</a>
        <a href="community?tab=<?= $tab ?>&tag=NEET" class="tag-pill <?= $tag === 'NEET' ? 'active' : '' ?>">#NEET</a>
        <a href="community?tab=<?= $tab ?>&tag=placements" class="tag-pill <?= $tag === 'placements' ? 'active' : '' ?>">#Placements</a>
        <a href="community?tab=<?= $tab ?>&tag=fees" class="tag-pill <?= $tag === 'fees' ? 'active' : '' ?>">#Fees</a>
        <a href="community?tab=<?= $tab ?>&tag=hostel" class="tag-pill <?= $tag === 'hostel' ? 'active' : '' ?>">#HostelLife</a>
      </div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
      <div class="alert alert-success"><i class="ph ph-check-circle"></i> <?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
      <div class="alert alert-danger"><i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <!-- Render List of Questions -->
    <?php if ($tab !== 'ask'): ?>
      <?php if (empty($questions)): ?>
        <div style="text-align:center; padding:60px 0; color:var(--text-muted-alt); background:#fff; border-radius:20px; border:1px solid var(--border-color-alt);">
          <i class="ph ph-chat-slash" style="font-size:3.5rem; margin-bottom:14px; color: #cbd5e1;"></i>
          <p style="font-weight: 600; font-size: 1.05rem;">No questions found in this tab.</p>
          <p style="font-size: 0.88rem; color: #94a3b8; margin-top: 4px;">Be the first one to raise a query or start a discussion!</p>
        </div>
      <?php else: ?>
        <div>
          <?php foreach ($questions as $q): ?>
            <div class="q-card" data-qid="<?= $q['id'] ?>" onclick="toggleCard(this, event)">
              <div class="q-header">
                <div class="q-asker-profile">
                  <div class="asker-avatar" style="background-color: <?= getAvatarColor($q['asker_name'] ?: 'Guest') ?>;">
                    <?= strtoupper(substr($q['asker_name'] ?: 'G', 0, 1)) ?>
                  </div>
                  <div class="asker-meta">
                    <span class="asker-name"><?= htmlspecialchars($q['asker_name'] ?: 'Guest Student') ?></span>
                    <span class="asker-date">Asked on <?= date('d M Y', strtotime($q['created_at'])) ?></span>
                  </div>
                </div>
                <span class="q-category cat-<?= htmlspecialchars($q['question_category']) ?>">
                  <i class="<?= getCategoryIcon($q['question_category']) ?>"></i> <?= htmlspecialchars($q['question_category']) ?>
                </span>
              </div>
              
              <h3 class="q-text"><?= htmlspecialchars($q['question_text']) ?></h3>
              
              <div class="q-footer">
                <div class="q-footer-left">
                  <!-- AJAX Upvote Button -->
                  <button class="action-btn upvote-q-btn <?= isset($_SESSION['upvoted_questions']) && in_array($q['id'], $_SESSION['upvoted_questions']) ? 'upvoted' : '' ?>" onclick="upvoteQuestion('<?= $q['id'] ?>', this, event)">
                    <i class="<?= isset($_SESSION['upvoted_questions']) && in_array($q['id'], $_SESSION['upvoted_questions']) ? 'ph-fill' : 'ph' ?> ph-thumbs-up"></i>
                    <span class="upvote-count"><?= number_format((int)($q['upvotes'] ?? 0)) ?></span>
                  </button>

                  <div class="q-view-indicator"><i class="ph ph-chat-text" style="font-size: 1.1rem;"></i> <?= number_format((int)$q['answer_count']) ?> Answers</div>
                  <div class="q-view-indicator"><i class="ph ph-eye" style="font-size: 1.1rem;"></i> <span class="views-count"><?= number_format((int)$q['views']) ?></span> Views</div>
                </div>

                <div class="q-footer-right">
                  <!-- Share Link Button -->
                  <button class="action-btn share-q-btn" onclick="shareQuestion('<?= $q['id'] ?>', event)" title="Copy Question Link">
                    <i class="ph ph-share-network"></i>
                  </button>
                  <span class="read-ans-trigger"><i class="ph ph-caret-down-bold"></i> View Thread</span>
                </div>
              </div>

              <!-- Expanded Thread -->
              <div class="q-expanded" onclick="event.stopPropagation()">
                <h4 class="ans-title"><i class="ph ph-chat-centered-text"></i> Discussions Thread</h4>
                
                <div class="answers-list">
                  <?php if (empty($answers[$q['id']])): ?>
                    <p style="font-size:0.9rem; color:var(--text-muted-alt); padding: 10px 0;">No answers posted yet. Be the first to help this student!</p>
                  <?php else: ?>
                    <?php foreach ($answers[$q['id']] as $ans): ?>
                      <div class="ans-item <?= $ans['is_expert_answer'] ? 'expert-ans-item' : '' ?>">
                        <div class="ans-header">
                          <div class="ans-replier-profile">
                            <div class="replier-avatar" style="background-color: <?= getAvatarColor($ans['replier_name'] ?: 'User') ?>;">
                              <?= strtoupper(substr($ans['replier_name'] ?: 'U', 0, 1)) ?>
                            </div>
                            <div class="replier-meta">
                              <span class="replier-name"><?= htmlspecialchars($ans['replier_name'] ?: 'Community User') ?></span>
                              <?php if ($ans['is_expert_answer']): ?>
                                <span class="replier-role role-expert"><i class="ph-fill ph-seal-check" style="color:var(--expert-badge-color);"></i> Verified Expert</span>
                              <?php elseif ($ans['is_verified_alumnus']): ?>
                                <span class="replier-role role-alumni"><i class="ph-fill ph-graduation-cap"></i> Verified Alumnus</span>
                              <?php else: ?>
                                <span class="replier-role role-student"><i class="ph ph-user"></i> Student</span>
                              <?php endif; ?>
                            </div>
                          </div>
                          <span class="ans-time"><?= date('d M Y, H:i', strtotime($ans['created_at'])) ?></span>
                        </div>
                        <p class="ans-body"><?= htmlspecialchars($ans['answer_text']) ?></p>

                        <div class="ans-footer">
                          <!-- AJAX Upvote Answer Button -->
                          <button class="action-btn upvote-ans-btn <?= isset($_SESSION['upvoted_answers']) && in_array($ans['id'], $_SESSION['upvoted_answers']) ? 'upvoted' : '' ?>" onclick="upvoteAnswer('<?= $ans['id'] ?>', this, event)">
                            <i class="<?= isset($_SESSION['upvoted_answers']) && in_array($ans['id'], $_SESSION['upvoted_answers']) ? 'ph-fill' : 'ph' ?> ph-thumbs-up"></i>
                            <span class="upvote-count"><?= number_format((int)($ans['upvotes'] ?? 0)) ?></span>
                          </button>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>

                <!-- Answer Form inside the card -->
                <form method="POST" action="community" class="inline-ans-form">
                  <input type="hidden" name="action" value="submit_answer">
                  <input type="hidden" name="question_id" value="<?= htmlspecialchars($q['id']) ?>">
                  <textarea name="answer_text" placeholder="Share your experience or advice with this student..." required></textarea>
                  <button type="submit" class="inline-ans-submit">
                    <i class="ph ph-paper-plane-right"></i> Post
                  </button>
                </form>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <!-- Ask a Question Form Tab -->
    <?php else: ?>
      <div class="ask-form-panel">
        <h3><i class="ph ph-question"></i> Ask Your Question to Experts</h3>
        <form method="POST" action="community">
          <input type="hidden" name="action" value="ask_question">
          
          <div class="form-group">
            <label>What is your question? *</label>
            <textarea name="question_text" class="form-control" placeholder="Type your question here (e.g. What is the average package for MBA at FMS Delhi?)..." required></textarea>
            <span style="font-size:0.8rem; color:var(--text-muted-alt); margin-top:6px; display:block;">Keep it concise, clear, and specific for high-quality answers.</span>
          </div>

          <div class="form-group" style="max-width:320px;">
            <label>Question Category *</label>
            <select name="question_category" class="form-control" required>
              <option value="general">General Guidance</option>
              <option value="admission">Admissions</option>
              <option value="fees">Fees Structure</option>
              <option value="placements">Placements & Salary</option>
              <option value="hostel">Hostel & Campus life</option>
              <option value="exams">Entrance Exams</option>
            </select>
          </div>

          <button type="submit" class="submit-q-btn">
            <i class="ph ph-paper-plane-right"></i> Submit Question
          </button>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <!-- Right Side: Sidebar Panels -->
  <div>
    <!-- Panel of Experts -->
    <div class="sidebar-panel">
      <h3><i class="ph ph-shield-check" style="color:var(--yale-blue);"></i> Panel of Experts</h3>
      <?php if(empty($experts)): ?>
        <p style="font-size:0.85rem; color:var(--text-muted-alt);">No experts loaded.</p>
      <?php else: ?>
        <?php foreach ($experts as $exp): ?>
          <div class="expert-card">
            <img src="<?= htmlspecialchars($exp['profile_url'] ?: 'https://images.unsplash.com/photo-1537368910025-700350fe46c7?w=120&h=120&fit=crop') ?>" alt="expert" class="expert-avatar">
            <div class="expert-info">
              <h4>
                <?= htmlspecialchars($exp['expert_name']) ?>
                <i class="ph-fill ph-seal-check" style="color:var(--yale-blue); font-size:1.05rem;" title="Verified Expert"></i>
              </h4>
              <p><?= htmlspecialchars($exp['expert_designation']) ?> at <strong><?= htmlspecialchars($exp['expert_college'] ?: 'AdmissionSeason') ?></strong></p>
              <span class="expert-spec"><?= htmlspecialchars($exp['specialization']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Review CTA box -->
    <div class="review-cta-box">
      <h4>Share Your College Life Experience</h4>
      <p>Help thousands of students choose their future path by reviewing your college placements, faculty, and hostel facilities.</p>
      <a href="colleges.php" class="review-btn-link"><i class="ph ph-pencil-line"></i> Write A Review</a>
    </div>
  </div>

</section>

<!-- Toast notification element -->
<div id="toast" class="toast-notif">Link copied to clipboard!</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
  // Expand/Collapse Question Cards & Increment Views via AJAX
  async function toggleCard(card, event) {
    const target = event.target;
    // Do not toggle if user clicked on action buttons, links, or reply fields
    if (target.tagName === 'TEXTAREA' || target.tagName === 'BUTTON' || target.tagName === 'A' || target.closest('.action-btn') || target.closest('.inline-ans-form') || target.closest('.q-expanded')) {
      return;
    }
    
    const isOpen = card.classList.contains('open');
    card.classList.toggle('open');

    // If opening, increment views via AJAX
    if (!isOpen) {
      const qId = card.getAttribute('data-qid');
      try {
        const res = await fetch('api/community_actions.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'increment_views', id: qId })
        });
        const data = await res.json();
        if (data.status === 'success') {
          const viewsEl = card.querySelector('.views-count');
          if (viewsEl && data.count) {
            viewsEl.textContent = data.count.toLocaleString();
          }
        }
      } catch (err) {
        console.error("Error logging view:", err);
      }
    }
  }

  // Question AJAX Upvoting
  async function upvoteQuestion(qId, btnEl, event) {
    event.stopPropagation();
    try {
      const res = await fetch('api/community_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'upvote_question', id: qId })
      });
      const data = await res.json();
      if (data.status === 'success') {
        const countEl = btnEl.querySelector('.upvote-count');
        countEl.textContent = data.count.toLocaleString();
        
        const iconEl = btnEl.querySelector('i');
        if (data.action === 'upvoted') {
          btnEl.classList.add('upvoted');
          iconEl.className = 'ph-fill ph-thumbs-up';
        } else {
          btnEl.classList.remove('upvoted');
          iconEl.className = 'ph ph-thumbs-up';
        }
      }
    } catch (err) {
      console.error("Error upvoting question:", err);
    }
  }

  // Answer AJAX Upvoting
  async function upvoteAnswer(ansId, btnEl, event) {
    event.stopPropagation();
    try {
      const res = await fetch('api/community_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'upvote_answer', id: ansId })
      });
      const data = await res.json();
      if (data.status === 'success') {
        const countEl = btnEl.querySelector('.upvote-count');
        countEl.textContent = data.count.toLocaleString();
        
        const iconEl = btnEl.querySelector('i');
        if (data.action === 'upvoted') {
          btnEl.classList.add('upvoted');
          iconEl.className = 'ph-fill ph-thumbs-up';
        } else {
          btnEl.classList.remove('upvoted');
          iconEl.className = 'ph ph-thumbs-up';
        }
      }
    } catch (err) {
      console.error("Error upvoting answer:", err);
    }
  }

  // Copy Link Helper
  function shareQuestion(qId, event) {
    event.stopPropagation();
    const shareUrl = window.location.origin + window.location.pathname + '?tab=qna&q=' + qId;
    navigator.clipboard.writeText(shareUrl).then(() => {
      showToast("Question thread link copied!");
    }).catch(err => {
      console.error("Failed to copy link:", err);
    });
  }

  // Toast notification alert helper
  function showToast(msg) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => {
      toast.classList.remove('show');
    }, 2500);
  }
</script>

</body>
</html>
