<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/news_seo_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success_msg = '';
$error_msg = '';
$siteBase = getBaseUrl();

// Get fallback user ID if not logged in
$user_id = $_SESSION['user_id'] ?? 'user-1234-uuid';

// Handle Question Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ask_question') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: community?tab=ask&error=login_required");
        exit;
    }
    $question_text = trim($_POST['question_text'] ?? '');
    $question_details = trim($_POST['question_details'] ?? '');
    $category = $_POST['question_category'] ?? 'general';

    if (empty($question_text)) {
        $error_msg = 'Question text cannot be empty.';
    } elseif (strlen($question_text) < 20) {
        $error_msg = 'Question must contain at least 20 characters.';
    } elseif (strlen($question_text) > 140) {
        $error_msg = 'Question must not exceed 140 characters.';
    } else {
        // Combine question + details if provided
        $fullText = $question_text;
        if (!empty($question_details)) {
            $fullText .= "\n\n---\n" . $question_details;
        }
        try {
            $qId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));

            // Generate slug from question text
            $slug = strtolower(trim($question_text));
            $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
            $slug = preg_replace('/[\s-]+/', '-', $slug);
            $slug = trim($slug, '-');
            $slug = mb_strimwidth($slug, 0, 80, '');
            // Ensure unique slug
            $original = $slug;
            $counter = 1;
            while (true) {
                $checkStmt = $pdo->prepare("SELECT id FROM questions WHERE slug = ?");
                $checkStmt->execute([$slug]);
                if (!$checkStmt->fetch()) break;
                $slug = $original . '-' . $counter;
                $counter++;
            }

            $stmt = $pdo->prepare("
                INSERT INTO questions (id, slug, question_text, question_category, asked_by, views, answer_count, status) 
                VALUES (?, ?, ?, ?, ?, 0, 0, 'open')
            ");
            $stmt->execute([$qId, $slug, $fullText, $category, $user_id]);
            
            header("Location: community?tab=qna&msg=posted");
            exit;
        } catch (Exception $e) {
            $error_msg = 'Failed to post question: ' . $e->getMessage();
        }
    }
}

// Handle Answer Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_answer') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?redirect=community");
        exit;
    }
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
if (isset($_GET['error']) && $_GET['error'] === 'login_required') {
    $error_msg = 'Please login to continue.';
}

// Search & Tag Filter Query
$search = trim($_GET['q'] ?? '');
$searchParam = '%' . $search . '%';
$tag = trim($_GET['tag'] ?? '');
$tagParam = '%' . $tag . '%';

// Community stats (real data)
$statQueriesResolved = (int)$pdo->query("SELECT COUNT(*) FROM questions WHERE status IN ('answered','resolved')")->fetchColumn();
$statExpertCounselors = (int)$pdo->query("SELECT COUNT(*) FROM experts")->fetchColumn();
$statTotalAnswers = (int)$pdo->query("SELECT COUNT(*) FROM answers")->fetchColumn();
$statTotalQuestions = (int)$pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$statResponseRate = $statTotalQuestions > 0 ? round(($statTotalAnswers / $statTotalQuestions) * 100, 1) : 0;

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

    // Track which questions current user follows
    $userId = $_SESSION['user_id'] ?? 'user-1234-uuid';
    if (!empty($questions)) {
        $qIds = array_column($questions, 'id');
        $placeholders = implode(',', array_fill(0, count($qIds), '?'));
        $followStmt = $pdo->prepare("SELECT followable_id FROM follows WHERE user_id = ? AND followable_type = 'question' AND followable_id IN ($placeholders)");
        $followStmt->execute(array_merge([$userId], $qIds));
        $_SESSION['followed_questions'] = $followStmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $_SESSION['followed_questions'] = [];
    }
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trumbowyg/dist/ui/trumbowyg.min.css">
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
      border-radius: 16px;
      padding: 0;
      margin-bottom: 20px;
      transition: all 0.3s ease;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(11, 36, 71, 0.02);
      overflow: hidden;
    }

    .q-card:hover {
      box-shadow: 0 8px 24px rgba(11, 36, 71, 0.06);
      border-color: rgba(25, 55, 109, 0.15);
    }

    .q-top-label {
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--text-muted-alt);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 12px 22px 0;
    }

    .q-tags-row {
      display: flex;
      gap: 8px;
      padding: 10px 22px 0;
      flex-wrap: wrap;
    }

    .q-tag {
      font-size: 0.72rem;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 6px;
      background: rgba(25, 55, 109, 0.06);
      color: var(--yale-blue);
      border: 1px solid rgba(25, 55, 109, 0.1);
    }

    .q-tag.tag-cat {
      background: rgba(16, 185, 129, 0.06);
      color: #059669;
      border-color: rgba(16, 185, 129, 0.12);
    }

    .q-body {
      padding: 14px 22px 0;
    }

    .q-text {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.08rem;
      font-weight: 700;
      color: var(--oxford-navy);
      line-height: 1.4;
      margin: 0 0 12px 0;
      text-align: left;
    }

    .q-stats-row {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 0 22px;
      margin-bottom: 12px;
    }

    .q-stat {
      font-size: 0.78rem;
      color: var(--text-muted-alt);
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .q-stat.views-stat { color: var(--yale-blue); }

    .q-actions-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 22px;
      border-top: 1px solid var(--border-color-alt);
    }

    .q-action-btn {
      background: none;
      border: 1px solid var(--border-color-alt);
      color: var(--text-muted-alt);
      padding: 7px 14px;
      border-radius: 8px;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.2s;
    }

    .q-action-btn:hover {
      background: rgba(25, 55, 109, 0.04);
      color: var(--yale-blue);
      border-color: var(--yale-blue);
    }

    .q-action-btn.answer-btn {
      background: var(--yale-blue);
      color: #fff;
      border-color: var(--yale-blue);
      padding: 7px 18px;
    }

    .q-action-btn.answer-btn:hover {
      background: var(--oxford-navy);
      border-color: var(--oxford-navy);
    }

    .q-action-btn.follow-btn {
      color: var(--yale-blue);
      border-color: var(--yale-blue);
    }

    .q-action-btn.follow-btn.active {
      background: var(--yale-blue);
      color: #fff;
      border-color: var(--yale-blue);
    }

    .q-action-btn.follow-btn:hover {
      background: var(--yale-blue);
      color: #fff;
    }

    .follow-count-badge {
      font-size: 0.75rem;
      opacity: 0.8;
    }

    .q-action-spacer { flex: 1; }

    /* Share Dropdown */
    .share-dropdown-wrap { position: relative; display: inline-block; }
    .share-dropdown {
      display: none;
      position: absolute;
      bottom: calc(100% + 8px);
      left: 50%;
      transform: translateX(-50%);
      background: #fff;
      border: 1px solid var(--border-color-alt);
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(11,36,71,0.1);
      min-width: 160px;
      z-index: 100;
      overflow: hidden;
    }
    .share-dropdown::after {
      content: '';
      position: absolute;
      top: 100%;
      left: 50%;
      transform: translateX(-50%);
      border: 7px solid transparent;
      border-top-color: #fff;
    }
    .share-dropdown-wrap.open .share-dropdown { display: block; }
    .share-opt {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 18px;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--ink-black);
      text-decoration: none;
      transition: background 0.15s;
    }
    .share-opt:hover { background: rgba(25,55,109,0.04); }
    .share-opt i { font-size: 1.15rem; }
    .share-opt:nth-child(1) i { color: #1877f2; }
    .share-opt:nth-child(2) i { color: #1da1f2; }
    .share-opt:nth-child(3) i { color: #0a66c2; }
    .share-opt:nth-child(4) i { color: #db4437; }

    /* Answer Preview inside card */
    .q-answer-preview {
      padding: 14px 22px;
      border-top: 1px solid var(--border-color-alt);
      background: #fafbfc;
    }

    .ans-preview-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
    }

    .ans-preview-avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 700;
      font-size: 0.82rem;
      flex-shrink: 0;
    }

    .ans-preview-name {
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--ink-black);
    }

    .ans-preview-level {
      font-size: 0.68rem;
      color: var(--text-muted-alt);
      font-weight: 500;
    }

    .ans-preview-text {
      font-size: 0.85rem;
      color: #475569;
      line-height: 1.55;
      margin: 0;
    }

    .ans-preview-footer {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-top: 10px;
    }

    .ans-vote-btn {
      background: none;
      border: none;
      color: var(--text-muted-alt);
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 8px;
      border-radius: 6px;
      transition: all 0.2s;
    }

    .ans-vote-btn:hover { background: rgba(0,0,0,0.04); }
    .ans-vote-btn.upvoted { color: var(--yale-blue); }

    .q-view-all-link {
      display: block;
      text-align: center;
      padding: 12px;
      color: var(--yale-blue);
      font-size: 0.85rem;
      font-weight: 700;
      text-decoration: none;
      border-top: 1px solid var(--border-color-alt);
      transition: background 0.2s;
    }

    .q-view-all-link:hover {
      background: rgba(25, 55, 109, 0.03);
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
      border-radius: 20px;
      padding: 32px;
      box-shadow: 0 8px 24px rgba(11, 36, 71, 0.03);
      text-align: left;
    }

    .ask-form-panel h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.4rem;
      font-weight: 800;
      color: var(--oxford-navy);
      margin-bottom: 20px;
      border-bottom: 1.5px solid var(--border-color-alt);
      padding-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-weight: 700;
      margin-bottom: 6px;
      font-size: 0.88rem;
      color: var(--oxford-navy);
    }

    .form-control {
      width: 100%;
      padding: 12px 14px;
      border: 1.5px solid var(--border-color-alt);
      border-radius: 10px;
      background: #fff;
      font-size: 0.88rem;
      font-family: inherit;
      color: var(--ink-black);
      box-sizing: border-box;
      outline: none;
      transition: all 0.3s ease;
      appearance: none;
    }

    textarea.form-control {
      min-height: 100px;
      resize: vertical;
      line-height: 1.5;
    }

    select.form-control {
      background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      background-size: 14px;
      padding-right: 40px;
      cursor: pointer;
    }

    .form-control:focus {
      border-color: var(--yale-blue);
      box-shadow: 0 0 0 3px rgba(25, 55, 109, 0.08);
    }

    .char-counter {
      font-size: 0.78rem;
      color: var(--text-muted-alt);
      text-align: right;
      margin-top: 6px;
      font-weight: 500;
    }
    .char-counter.over { color: #dc2626; font-weight: 700; }
    .char-counter.near { color: #f59e0b; }

    .char-hint {
      font-size: 0.78rem;
      color: var(--text-muted-alt);
      margin-top: 6px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .validation-msg {
      font-size: 0.78rem;
      margin-top: 6px;
      display: none;
      font-weight: 600;
    }
    .validation-msg.error { color: #dc2626; }
    .validation-msg.success { color: #16a34a; }

    .add-details-toggle {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: none;
      border: 1.5px solid var(--border-color-alt);
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--oxford-navy);
      cursor: pointer;
      transition: all 0.2s;
      margin-bottom: 12px;
    }
    .add-details-toggle:hover { border-color: var(--yale-blue); color: var(--yale-blue); }
    .add-details-toggle i { font-size: 1rem; }

    .details-section {
      display: none;
      margin-top: 0;
    }
    .details-section.show { display: block; }

    .details-section textarea {
      min-height: 80px;
    }

    .submit-q-btn {
      background: var(--oxford-navy);
      color: #fff;
      border: none;
      padding: 12px 28px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.88rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 4px 12px rgba(11, 36, 71, 0.15);
      transition: all 0.3s;
      margin-top: 8px;
    }

    .submit-q-btn:hover {
      background: var(--yale-blue);
      box-shadow: 0 8px 24px rgba(25, 55, 109, 0.25);
      transform: translateY(-1px);
    }

    .submit-q-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
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

    /* Answer Modal */
    .ans-modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 9999;
      justify-content: center;
      align-items: center;
      padding: 20px;
      backdrop-filter: blur(2px);
    }
    .ans-modal-overlay.open { display: flex; }

    .ans-modal {
      background: #fff;
      border-radius: 16px;
      width: 100%;
      max-width: 640px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
      animation: modalSlideUp 0.25s ease;
    }
    @keyframes modalSlideUp {
      from { transform: translateY(20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .ans-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 18px 24px;
      border-bottom: 1px solid var(--border-color-alt);
    }
    .ans-modal-header h3 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--oxford-navy);
      margin: 0;
    }
    .ans-modal-close {
      background: none;
      border: none;
      color: var(--text-muted-alt);
      font-size: 1.3rem;
      cursor: pointer;
      padding: 4px;
      border-radius: 6px;
      transition: all 0.2s;
    }
    .ans-modal-close:hover { background: rgba(0,0,0,0.05); color: var(--ink-black); }

    .ans-modal-question {
      padding: 16px 24px;
      font-size: 0.95rem;
      color: var(--ink-black);
      line-height: 1.5;
      border-bottom: 1px solid var(--border-color-alt);
      background: rgba(25,55,109,0.02);
    }

    .ans-modal-body { padding: 16px 24px; }
    .ans-modal-body #ansModalEditor {
      border: 1.5px solid var(--border-color-alt);
      border-radius: 12px;
      overflow: hidden;
    }
    .ans-modal-body .trumbowyg-box {
      border: none;
      border-radius: 12px;
      min-height: 180px;
    }
    .ans-modal-body .trumbowyg-editor {
      min-height: 180px;
      font-family: 'Inter', sans-serif;
      font-size: 0.92rem;
      color: var(--ink-black);
      line-height: 1.6;
      padding: 14px 16px;
    }
    .ans-modal-body .trumbowyg-editor:empty::before {
      content: attr(data-placeholder);
      color: var(--text-muted-alt);
    }
    .ans-modal-body .trumbowyg-button-pane {
      border-radius: 0 0 12px 12px;
      background: #f8fafc;
    }
    .ans-modal-hint {
      font-size: 0.78rem;
      color: var(--text-muted-alt);
      margin-top: 8px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .ans-modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      padding: 14px 24px;
      border-top: 1px solid var(--border-color-alt);
    }
    .ans-modal-btn {
      padding: 10px 24px;
      border-radius: 10px;
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      border: none;
      transition: all 0.2s;
    }
    .ans-modal-btn.cancel {
      background: transparent;
      color: var(--yale-blue);
      border: 1px solid var(--border-color-alt);
    }
    .ans-modal-btn.cancel:hover { background: rgba(0,0,0,0.03); }
    .ans-modal-btn.post {
      background: var(--yale-blue);
      color: #fff;
    }
    .ans-modal-btn.post:hover { background: var(--oxford-navy); }

    @media (max-width: 600px) {
      .ans-modal { max-width: 100%; margin: 0; border-radius: 16px 16px 0 0; }
      .ans-modal-overlay { align-items: flex-end; }
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
        <span class="stat-val"><?= number_format($statQueriesResolved) ?>+</span>
        <span class="stat-lbl">Queries Resolved</span>
      </div>
      <div class="stat-item">
        <span class="stat-val"><?= number_format($statExpertCounselors) ?>+</span>
        <span class="stat-lbl">Expert Counselors</span>
      </div>
      <div class="stat-item">
        <span class="stat-val"><?= $statResponseRate ?>%</span>
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
        <div style="text-align:center; padding:60px 0; color:var(--text-muted-alt); background:#fff; border-radius:16px; border:1px solid var(--border-color-alt);">
          <i class="ph ph-chat-slash" style="font-size:3rem; margin-bottom:14px; color: #cbd5e1;"></i>
          <p style="font-weight: 600; font-size: 1rem;">No questions found in this tab.</p>
          <p style="font-size: 0.85rem; color: #94a3b8; margin-top: 4px;">Be the first one to raise a query or start a discussion!</p>
        </div>
      <?php else: ?>
        <div>
          <?php foreach ($questions as $q): ?>
            <?php
              $qCats = [
                'admission' => 'Admissions', 'fees' => 'Fees', 'placements' => 'Placements',
                'hostel' => 'Campus Life', 'exams' => 'Entrance Exams', 'general' => 'General'
              ];
              $qCatLabel = $qCats[$q['question_category']] ?? ucfirst($q['question_category']);
              $qAnswers = $answers[$q['id']] ?? [];
              $firstAns = $qAnswers[0] ?? null;
            ?>
            <div class="q-card" data-qid="<?= $q['id'] ?>" onclick="toggleCard(this, event)">
              <div class="q-top-label">Top Content</div>
              <div class="q-tags-row">
                <span class="q-tag tag-cat"><?= htmlspecialchars($qCatLabel) ?></span>
                <?php if (!empty($q['related_college_id'])): ?>
                  <span class="q-tag">College</span>
                <?php endif; ?>
                <?php if (!empty($q['related_course_id'])): ?>
                  <span class="q-tag">Course</span>
                <?php endif; ?>
              </div>
              <div class="q-body">
                <h3 class="q-text"><?= htmlspecialchars($q['question_text']) ?></h3>
              </div>
              <div class="q-stats-row">
                <span class="q-stat views-stat"><i class="ph ph-eye"></i> <?= number_format((int)$q['views']) ?> Views</span>
                <span class="q-stat"><i class="ph ph-chat-text"></i> <?= number_format((int)$q['answer_count']) ?> Answers</span>
                <?php if ((int)($q['follow_count'] ?? 0) > 0): ?>
                  <span class="q-stat"><i class="ph ph-bell"></i> <?= number_format((int)$q['follow_count']) ?> Followers</span>
                <?php endif; ?>
                <span class="q-stat">Asked <?= date('d M', strtotime($q['created_at'])) ?></span>
              </div>
              <div class="q-actions-row">
                <button class="q-action-btn follow-btn <?= isset($_SESSION['followed_questions']) && in_array($q['id'], $_SESSION['followed_questions']) ? 'active' : '' ?>" onclick="toggleFollow('question', '<?= $q['id'] ?>', this, event)" title="Follow">
                  <i class="<?= isset($_SESSION['followed_questions']) && in_array($q['id'], $_SESSION['followed_questions']) ? 'ph-fill' : 'ph' ?> ph-bell"></i> <span class="follow-label"><?= isset($_SESSION['followed_questions']) && in_array($q['id'], $_SESSION['followed_questions']) ? 'Following' : 'Follow' ?></span> <span class="follow-count-badge"><?= (int)($q['follow_count'] ?? 0) > 0 ? '(' . number_format((int)$q['follow_count']) . ')' : '' ?></span>
                </button>
                <div class="share-dropdown-wrap">
                  <button class="q-action-btn" onclick="toggleShareDropdown(this, event)" title="Share">
                    <i class="ph ph-share-network"></i> Share
                  </button>
                  <div class="share-dropdown">
                    <a class="share-opt" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('$siteBase/question/' . ($q['slug'] ?? $q['id'])) ?>" target="_blank" rel="noopener" onclick="event.stopPropagation()">
                      <i class="ph-fill ph-facebook-logo"></i> Facebook
                    </a>
                    <a class="share-opt" href="https://twitter.com/intent/tweet?url=<?= urlencode('$siteBase/question/' . ($q['slug'] ?? $q['id'])) ?>&text=<?= urlencode($q['question_text']) ?>" target="_blank" rel="noopener" onclick="event.stopPropagation()">
                      <i class="ph-fill ph-twitter-logo"></i> Twitter
                    </a>
                    <a class="share-opt" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode('$siteBase/question/' . ($q['slug'] ?? $q['id'])) ?>" target="_blank" rel="noopener" onclick="event.stopPropagation()">
                      <i class="ph-fill ph-linkedin-logo"></i> LinkedIn
                    </a>
                    <a class="share-opt" href="https://plus.google.com/share?url=<?= urlencode('$siteBase/question/' . ($q['slug'] ?? $q['id'])) ?>" target="_blank" rel="noopener" onclick="event.stopPropagation()">
                      <i class="ph-fill ph-google-logo"></i> Google
                    </a>
                  </div>
                </div>
                <span class="q-action-spacer"></span>
                <?php if ($firstAns): ?>
                  <button class="q-action-btn answer-btn" onclick="if(checkLogin())openAnsModal('<?= $q['id'] ?>', '<?= htmlspecialchars(addslashes($q['question_text'])) ?>', event)">
                    <i class="ph ph-pencil-simple"></i> Answer
                  </button>
                <?php else: ?>
                  <button class="q-action-btn answer-btn" onclick="if(checkLogin())openAnsModal('<?= $q['id'] ?>', '<?= htmlspecialchars(addslashes($q['question_text'])) ?>', event)">
                    <i class="ph ph-plus"></i> Answer
                  </button>
                <?php endif; ?>
              </div>

              <?php if ($firstAns): ?>
              <div class="q-answer-preview" onclick="event.stopPropagation()">
                <div class="ans-preview-header">
                  <div class="ans-preview-avatar" style="background-color: <?= getAvatarColor($firstAns['replier_name'] ?: 'User') ?>;">
                    <?= strtoupper(substr($firstAns['replier_name'] ?: 'U', 0, 1)) ?>
                  </div>
                  <div>
                    <div class="ans-preview-name"><?= htmlspecialchars($firstAns['replier_name'] ?: 'Community User') ?></div>
                    <div class="ans-preview-level">
                      <?php if ($firstAns['is_expert_answer']): ?>
                        <i class="ph-fill ph-seal-check" style="color:var(--expert-badge-color);"></i> Verified Expert
                      <?php elseif ($firstAns['is_verified_alumnus']): ?>
                        <i class="ph-fill ph-graduation-cap"></i> Verified Alumnus
                      <?php else: ?>
                        Beginner - Level 1
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <p class="ans-preview-text"><?= htmlspecialchars(mb_strimwidth($firstAns['answer_text'], 0, 200, '...')) ?></p>
                <div class="ans-preview-footer">
                  <button class="ans-vote-btn <?= isset($_SESSION['upvoted_answers']) && in_array($firstAns['id'], $_SESSION['upvoted_answers']) ? 'upvoted' : '' ?>" onclick="upvoteAnswer('<?= $firstAns['id'] ?>', this, event)">
                    <i class="<?= isset($_SESSION['upvoted_answers']) && in_array($firstAns['id'], $_SESSION['upvoted_answers']) ? 'ph-fill' : 'ph' ?> ph-thumbs-up"></i> <?= number_format((int)($firstAns['upvotes'] ?? 0)) ?>
                  </button>
                </div>
              </div>
              <?php endif; ?>

              <?php if ((int)$q['answer_count'] > 1): ?>
                <a class="q-view-all-link" href="/ADMISSION/question/<?= urlencode($q['slug'] ?? $q['id']) ?>" onclick="event.stopPropagation()">
                  View All <?= number_format((int)$q['answer_count']) ?> Answers
                </a>
              <?php endif; ?>

              <div class="q-expanded" onclick="event.stopPropagation()">
                <h4 class="ans-title"><i class="ph ph-chat-centered-text"></i> All Answers</h4>
                <div class="answers-list">
                  <?php if (empty($qAnswers)): ?>
                    <p style="font-size:0.85rem; color:var(--text-muted-alt); padding: 10px 0;">No answers posted yet. Be the first to help this student!</p>
                  <?php else: ?>
                    <?php foreach ($qAnswers as $ans): ?>
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
                          <button class="action-btn upvote-ans-btn <?= isset($_SESSION['upvoted_answers']) && in_array($ans['id'], $_SESSION['upvoted_answers']) ? 'upvoted' : '' ?>" onclick="upvoteAnswer('<?= $ans['id'] ?>', this, event)">
                            <i class="<?= isset($_SESSION['upvoted_answers']) && in_array($ans['id'], $_SESSION['upvoted_answers']) ? 'ph-fill' : 'ph' ?> ph-thumbs-up"></i>
                            <span class="upvote-count"><?= number_format((int)($ans['upvotes'] ?? 0)) ?></span>
                          </button>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
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
      <?php if (!isset($_SESSION['user_id'])): ?>
      <div class="ask-form-panel" style="text-align:center; padding:50px 32px;">
        <i class="ph ph-lock" style="font-size:3rem; color:var(--yale-blue); margin-bottom:16px; display:block;"></i>
        <h3 style="justify-content:center; border:none; padding-bottom:0;"><i class="ph ph-question"></i> Login to Ask a Question</h3>
        <p style="color:var(--text-muted-alt); margin-bottom:24px; font-size:0.95rem;">You need to be logged in to ask questions and participate in discussions.</p>
        <a href="login.php?redirect=<?= urlencode('community.php?tab=ask') ?>" style="display:inline-block; background:var(--oxford-navy); color:#fff; padding:12px 32px; border-radius:10px; font-weight:700; font-size:0.95rem; text-decoration:none; transition:all 0.2s;">Login / Sign Up</a>
      </div>
      <?php else: ?>
      <div class="ask-form-panel">
        <h3><i class="ph ph-question"></i> Ask Your Question to Experts</h3>
        <form method="POST" action="community" id="askForm" onsubmit="return validateAskForm()">
          <input type="hidden" name="action" value="ask_question">
          
          <div class="form-group">
            <label>What is your question? *</label>
            <textarea name="question_text" id="questionText" class="form-control" placeholder="e.g. What is the average package for MBA at FMS Delhi?" maxlength="140" oninput="updateCounter('questionText', 'qCounter', 140, 20)" required></textarea>
            <div class="char-counter" id="qCounter">Characters 0/140</div>
            <div class="validation-msg error" id="qValidation"></div>
          </div>

          <button type="button" class="add-details-toggle" id="detailsToggle" onclick="toggleDetails()">
            <i class="ph ph-plus-circle"></i> Add more details
          </button>

          <div class="details-section" id="detailsSection">
            <div class="form-group" style="margin-bottom:0;">
              <textarea name="question_details" id="questionDetails" class="form-control" placeholder="Give information like score, education background etc." maxlength="300" oninput="updateCounter('questionDetails', 'dCounter', 300, 0)"></textarea>
              <div class="char-counter" id="dCounter">Characters 0/300</div>
            </div>
          </div>

          <div class="form-group" style="max-width:280px;">
            <label>Question Category *</label>
            <select name="question_category" class="form-control" required>
              <option value="general">General Guidance</option>
              <option value="admission">Admissions</option>
              <option value="fees">Fees Structure</option>
              <option value="placements">Placements & Salary</option>
              <option value="hostel">Hostel & Campus Life</option>
              <option value="exams">Entrance Exams</option>
            </select>
          </div>

          <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
            <button type="submit" class="submit-q-btn" id="submitBtn">
              <i class="ph ph-paper-plane-right"></i> Submit Question
            </button>
            <span class="char-hint"><i class="ph ph-info"></i> Keep it short & simple. Type complete words. Avoid abusive language.</span>
          </div>
        </form>
      </div>
      <?php endif; ?>
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
      <a href="/ADMISSION/experts" style="display:block; text-align:center; padding:12px; background:rgba(25,55,109,0.04); border-radius:10px; color:var(--yale-blue); font-size:0.85rem; font-weight:700; margin-top:12px; text-decoration:none; border:1px solid rgba(25,55,109,0.1); transition:all 0.2s;">
        <i class="ph ph-arrow-right"></i> View All Experts
      </a>
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

<!-- Answer Modal -->
<div id="ansModal" class="ans-modal-overlay" onclick="if(event.target===this)closeAnsModal()">
  <div class="ans-modal">
    <div class="ans-modal-header">
      <h3>Write your answer</h3>
      <button class="ans-modal-close" onclick="closeAnsModal()"><i class="ph ph-x"></i></button>
    </div>
    <div class="ans-modal-question" id="ansModalQuestion"></div>
    <form method="POST" action="community" id="ansModalForm">
      <input type="hidden" name="action" value="submit_answer">
      <input type="hidden" name="question_id" id="ansModalQid">
      <div class="ans-modal-body">
        <div id="ansModalEditor"></div>
        <input type="hidden" name="answer_text" id="ansModalHiddenText">
        <div class="ans-modal-hint"><i class="ph ph-info"></i> Minimum 10 characters. Be helpful and respectful.</div>
      </div>
      <div class="ans-modal-footer">
        <button type="button" class="ans-modal-btn cancel" onclick="closeAnsModal()">Cancel</button>
        <button type="submit" class="ans-modal-btn post">Post</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/trumbowyg/dist/trumbowyg.min.js"></script>

<script>
  const LOGGED_IN = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

  function checkLogin() {
    if (!LOGGED_IN) {
      if (confirm('Please login to continue. Click OK to go to login page.')) {
        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
      }
      return false;
    }
    return true;
  }

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
  function toggleShareDropdown(btn, event) {
    event.stopPropagation();
    const wrap = btn.closest('.share-dropdown-wrap');
    const wasOpen = wrap.classList.contains('open');
    document.querySelectorAll('.share-dropdown-wrap.open').forEach(w => w.classList.remove('open'));
    if (!wasOpen) wrap.classList.add('open');
  }

  document.addEventListener('click', function(e) {
    if (!e.target.closest('.share-dropdown-wrap')) {
      document.querySelectorAll('.share-dropdown-wrap.open').forEach(w => w.classList.remove('open'));
    }
  });

  // ═══ FOLLOW / UNFOLLOW ═══
  async function toggleFollow(type, id, btn, event) {
    event.stopPropagation();
    try {
      const res = await fetch('api/community_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle_follow', type: type, id: id })
      });
      const data = await res.json();
      if (data.status === 'success') {
        const isActive = data.action === 'followed';
        btn.classList.toggle('active', isActive);
        const icon = btn.querySelector('i');
        icon.className = isActive ? 'ph-fill ph-bell' : 'ph ph-bell';
        const label = btn.querySelector('.follow-label');
        if (label) label.textContent = isActive ? 'Following' : 'Follow';
        const badge = btn.querySelector('.follow-count-badge');
        if (badge && data.count > 0) {
          badge.textContent = '(' + data.count + ')';
        } else if (badge) {
          badge.textContent = '';
        }
      }
    } catch (err) {
      console.error('Follow error:', err);
    }
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

  // ═══ ASK QUESTION FORM: Character Counters & Validation ═══════════════════

  function updateCounter(textareaId, counterId, max, min) {
    const textarea = document.getElementById(textareaId);
    const counter = document.getElementById(counterId);
    if (!textarea || !counter) return;
    const len = textarea.value.length;
    counter.textContent = 'Characters ' + len + '/' + max;
    counter.classList.remove('over', 'near');
    if (len > max) {
      counter.classList.add('over');
    } else if (len >= max * 0.85) {
      counter.classList.add('near');
    }
  }

  function toggleDetails() {
    const section = document.getElementById('detailsSection');
    const toggle = document.getElementById('detailsToggle');
    const icon = toggle.querySelector('i');
    if (section.classList.contains('show')) {
      section.classList.remove('show');
      icon.className = 'ph ph-plus-circle';
      toggle.innerHTML = '<i class="ph ph-plus-circle"></i> Add more details';
      document.getElementById('questionDetails').value = '';
      updateCounter('questionDetails', 'dCounter', 300, 0);
    } else {
      section.classList.add('show');
      icon.className = 'ph ph-minus-circle';
      toggle.innerHTML = '<i class="ph ph-minus-circle"></i> Remove details';
    }
  }

  function validateAskForm() {
    const question = document.getElementById('questionText').value.trim();
    const qValidation = document.getElementById('qValidation');
    qValidation.style.display = 'block';

    if (question.length < 20) {
      qValidation.textContent = 'The Question must contain at least 20 characters.';
      qValidation.className = 'validation-msg error';
      return false;
    }
    if (question.length > 140) {
      qValidation.textContent = 'The Question must not exceed 140 characters.';
      qValidation.className = 'validation-msg error';
      return false;
    }

    qValidation.textContent = '';
    qValidation.style.display = 'none';
    return true;
  }

  // ═══ ANSWER MODAL ═══
  let trumbowygInited = false;

  function openAnsModal(qId, qText, event) {
    event.stopPropagation();
    document.getElementById('ansModalQid').value = qId;
    document.getElementById('ansModalQuestion').textContent = qText;
    document.getElementById('ansModal').classList.add('open');
    document.body.style.overflow = 'hidden';

    if (!trumbowygInited) {
      $('#ansModalEditor').trumbowyg({
        btns: [
          ['bold', 'italic', 'underline'],
          ['unorderedList', 'orderedList'],
          ['link'],
          ['justifyLeft', 'justifyCenter', 'justifyRight'],
          ['horizontalRule'],
          ['removeformat'],
          ['fullscreen']
        ],
        placeholder: 'Share your knowledge and insights...',
        autogrow: true,
        autogrowOnEnter: true
      });
      trumbowygInited = true;
    }

    $('#ansModalEditor').trumbowyg('empty');
    $('#ansModalEditor').trumbowyg('focus');
  }

  function closeAnsModal() {
    document.getElementById('ansModal').classList.remove('open');
    document.body.style.overflow = '';
  }

  // Sync Trumbowyg content to hidden input before submit
  document.getElementById('ansModalForm').addEventListener('submit', function(e) {
    var content = $('#ansModalEditor').trumbowyg('html');
    document.getElementById('ansModalHiddenText').value = content;
    if (!content || content.replace(/<[^>]*>/g, '').trim().length < 10) {
      e.preventDefault();
      alert('Answer must be at least 10 characters.');
      return false;
    }
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAnsModal();
  });
</script>

</body>
</html>
