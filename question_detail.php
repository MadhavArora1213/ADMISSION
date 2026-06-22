<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/db.php';

$questionId = $_GET['id'] ?? '';
if ($questionId === '') {
    header('Location: /ADMISSION/community');
    exit;
}

// Fetch question + asker
$stmt = $pdo->prepare("
    SELECT q.*, u.full_name AS asker_name, u.email AS asker_email
    FROM questions q
    LEFT JOIN users u ON q.asked_by = u.id
    WHERE q.id = ?
");
$stmt->execute([$questionId]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    header('Location: /ADMISSION/community');
    exit;
}

// Increment views
$pdo->prepare("UPDATE questions SET views = views + 1 WHERE id = ?")->execute([$questionId]);
$question['views'] = (int)$question['views'] + 1;

// Fetch all answers
$ansStmt = $pdo->prepare("
    SELECT a.*, u.full_name AS replier_name, u.email AS replier_email
    FROM answers a
    LEFT JOIN users u ON a.answered_by = u.id
    WHERE a.question_id = ?
    ORDER BY a.is_expert_answer DESC, a.upvotes DESC, a.created_at ASC
");
$ansStmt->execute([$questionId]);
$answers = $ansStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch follow count
$followStmt = $pdo->prepare("SELECT follow_count FROM questions WHERE id = ?");
$followStmt->execute([$questionId]);
$followCount = (int)$followStmt->fetchColumn();

// Check if current user follows
$userId = $_SESSION['user_id'] ?? 'user-1234-uuid';
$checkFollow = $pdo->prepare("SELECT id FROM follows WHERE user_id = ? AND followable_type = 'question' AND followable_id = ?");
$checkFollow->execute([$userId, $questionId]);
$isFollowing = (bool)$checkFollow->fetch();

// Sort
$sortBy = $_GET['sort'] ?? 'upvotes';
if (!in_array($sortBy, ['upvotes', 'newest', 'oldest'])) $sortBy = 'upvotes';
switch ($sortBy) {
    case 'newest': usort($answers, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at'])); break;
    case 'oldest': usort($answers, fn($a, $b) => strtotime($a['created_at']) - strtotime($b['created_at'])); break;
    default: usort($answers, fn($a, $b) => (int)$b['upvotes'] - (int)$a['upvotes']);
}

// Pre-load first 2 comments per answer
$answerComments = [];
$answerCommentCounts = [];
if (!empty($answers)) {
    $ansIds = array_column($answers, 'id');
    $ph = implode(',', array_fill(0, count($ansIds), '?'));

    // Total counts
    $cntStmt = $pdo->prepare("SELECT answer_id, COUNT(*) AS cnt FROM comments WHERE answer_id IN ($ph) AND status='active' GROUP BY answer_id");
    $cntStmt->execute($ansIds);
    while ($row = $cntStmt->fetch(PDO::FETCH_ASSOC)) {
        $answerCommentCounts[$row['answer_id']] = (int)$row['cnt'];
    }

    // First 2 comments per answer
    $comStmt = $pdo->prepare("
        SELECT c.*, u.full_name AS user_name
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.answer_id IN ($ph) AND c.status = 'active'
        ORDER BY c.created_at DESC
    ");
    $comStmt->execute($ansIds);
    foreach ($comStmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
        if (!isset($answerComments[$c['answer_id']]) || count($answerComments[$c['answer_id']]) < 2) {
            $answerComments[$c['answer_id']][] = $c;
        }
    }

    // Track user votes on comments
    if (!empty($ansIds)) {
        $voteStmt = $pdo->prepare("SELECT comment_id, vote_type FROM comment_votes WHERE user_id = ?");
        $voteStmt->execute([$userId]);
        $_SESSION['comment_votes'] = [];
        while ($v = $voteStmt->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['comment_votes'][$v['comment_id']] = $v['vote_type'];
        }
    }
}

// Split question
$qParts = explode('---', $question['question_text'], 2);
$qTitle = trim($qParts[0]);
$qDetails = isset($qParts[1]) ? trim($qParts[1]) : '';

// Helpers
function getAvatarColor($name) {
    $colors = ['#1e3a8a', '#1e40af', '#14532d', '#7c2d12', '#4c1d95', '#0f766e', '#831843', '#475569', '#111827'];
    return $colors[abs(crc32((string)$name)) % count($colors)];
}

function getCategoryIcon($cat) {
    return match($cat) {
        'admission' => 'ph ph-graduation-cap',
        'fees' => 'ph ph-currency-inr',
        'placements' => 'ph ph-briefcase',
        'hostel' => 'ph ph-house-line',
        'exams' => 'ph ph-exam',
        default => 'ph ph-hash',
    };
}

function timeAgo($datetime) {
    $diff = (new DateTime())->diff(new DateTime($datetime));
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}

$navBase = '/ADMISSION';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($qTitle) ?> | AdmissionSeason Q&A</title>
    <meta name="description" content="<?= htmlspecialchars(mb_substr($qTitle, 0, 160)) ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trumbowyg/dist/ui/trumbowyg.min.css">
    <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css?v=9">
    <style>
        :root { --oxford-navy:#0B2447; --yale-blue:#19376D; --snow-pearl:#F8FAFC; --ink-black:#0F172A; --text-muted:#64748b; --border-color:#e2e8f0; --success-green:#10b981; }
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:var(--snow-pearl);color:var(--ink-black);line-height:1.6}
        a{text-decoration:none;color:inherit}

        .qd-page{max-width:1200px;margin:0 auto;padding:20px 16px}
        .qd-back-link{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:20px;background:rgba(25,55,109,.06);color:var(--yale-blue);font-weight:600;font-size:.85rem;margin-bottom:16px;transition:.2s}
        .qd-back-link:hover{background:var(--yale-blue);color:#fff}
        .qd-breadcrumb{font-size:.85rem;color:var(--text-muted);margin-bottom:20px}
        .qd-breadcrumb a{color:var(--yale-blue);font-weight:500}
        .qd-breadcrumb a:hover{text-decoration:underline}
        .qd-breadcrumb span{margin:0 6px;color:#cbd5e1}

        .qd-layout{display:grid;grid-template-columns:1fr 340px;gap:24px}
        @media(max-width:900px){.qd-layout{grid-template-columns:1fr}}

        /* Question */
        .qd-question{background:#fff;border-radius:16px;padding:28px 32px;margin-bottom:24px;border:1px solid var(--border-color)}
        .qd-tags{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
        .qd-tag{padding:5px 14px;border-radius:20px;font-size:.8rem;font-weight:600;border:1.5px solid var(--yale-blue);color:var(--yale-blue);background:rgba(25,55,109,.04)}
        .qd-title{font-family:'Space Grotesk',sans-serif;font-size:1.55rem;font-weight:700;color:var(--ink-black);line-height:1.35;margin-bottom:16px}
        .qd-meta{display:flex;align-items:center;gap:18px;flex-wrap:wrap;font-size:.88rem;color:var(--text-muted);margin-bottom:14px}
        .qd-meta-item{display:flex;align-items:center;gap:5px}
        .qd-meta-item i{font-size:1rem}
        .qd-meta-divider{width:1px;height:16px;background:var(--border-color)}
        .qd-details{margin-top:14px;font-size:.95rem;line-height:1.7;color:#334155;white-space:pre-wrap}
        .qd-asked-by{font-size:.88rem;color:var(--text-muted);margin-top:12px}
        .qd-asked-by strong{color:var(--ink-black)}

        .qd-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;padding-top:16px;border-top:1px solid var(--border-color)}
        .qd-act-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border-color);background:#fff;color:var(--ink-black);transition:.2s}
        .qd-act-btn:hover{border-color:var(--yale-blue);color:var(--yale-blue)}
        .qd-act-btn.follow-active{background:var(--yale-blue);color:#fff;border-color:var(--yale-blue)}
        .qd-act-btn.answer-btn{background:var(--success-green);color:#fff;border-color:var(--success-green);padding:8px 22px;font-size:.9rem}
        .qd-act-btn.answer-btn:hover{background:#059669;border-color:#059669}

        /* Answers header */
        .qd-answers-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
        .qd-answers-count{font-family:'Space Grotesk',sans-serif;font-size:1.2rem;font-weight:700;color:var(--ink-black)}
        .qd-sort{display:flex;align-items:center;gap:8px;font-size:.85rem;color:var(--text-muted)}
        .qd-sort select{padding:6px 10px;border:1px solid var(--border-color);border-radius:6px;font-size:.85rem;background:#fff;color:var(--ink-black);cursor:pointer}

        /* Answer card */
        .qd-answer-card{background:#fff;border-radius:14px;padding:24px 28px;margin-bottom:16px;border:1px solid var(--border-color)}
        .qd-answer-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px}
        .qd-answerer{display:flex;align-items:center;gap:12px}
        .qd-avatar{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1rem;flex-shrink:0}
        .qd-answerer-info{font-size:.85rem;color:var(--text-muted)}
        .qd-answerer-name{font-weight:700;color:var(--ink-black);font-size:.95rem}
        .qd-expert-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:10px;font-size:.72rem;font-weight:700;background:var(--yale-blue);color:#fff;margin-left:6px}

        /* Read More */
        .qd-answer-body{font-size:.95rem;line-height:1.7;color:#334155}
        .qd-answer-body.truncated{max-height:120px;overflow:hidden;position:relative}
        .qd-answer-body.truncated::after{content:'';position:absolute;bottom:0;left:0;right:0;height:40px;background:linear-gradient(transparent,#fff)}
        .qd-read-more{color:var(--yale-blue);font-weight:600;cursor:pointer;font-size:.88rem;border:none;background:none;padding:0;margin-top:6px;display:inline-block}
        .qd-read-more:hover{text-decoration:underline}

        /* Answer actions */
        .qd-answer-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:14px;padding-top:12px;border-top:1px solid var(--border-color);align-items:center}
        .qd-ans-act{display:inline-flex;align-items:center;gap:5px;font-size:.82rem;color:var(--text-muted);cursor:pointer;border:none;background:none;padding:6px 10px;border-radius:6px;transition:.2s}
        .qd-ans-act:hover{background:rgba(25,55,109,.06);color:var(--yale-blue)}
        .qd-ans-act.active{color:var(--yale-blue);font-weight:600}
        .qd-ans-act i{font-size:1rem}
        .qd-ans-act .count{font-weight:600}
        .qd-vote-group{display:inline-flex;align-items:center;gap:2px;border:1px solid var(--border-color);border-radius:8px;overflow:hidden}
        .qd-vote-group .qd-ans-act{border-radius:0}
        .qd-vote-sep{width:1px;height:20px;background:var(--border-color)}

        /* Comments */
        .qd-comments-section{margin-top:14px;padding-top:12px;border-top:1px solid var(--border-color)}
        .qd-comment{display:flex;gap:10px;padding:10px 0;font-size:.85rem}
        .qd-comment+.qd-comment{border-top:1px solid rgba(0,0,0,.05)}
        .qd-comment-avatar{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.7rem;flex-shrink:0}
        .qd-comment-body{flex:1}
        .qd-comment-meta{display:flex;align-items:center;gap:8px;margin-bottom:4px}
        .qd-comment-name{font-weight:600;color:var(--ink-black)}
        .qd-comment-time{font-size:.78rem;color:var(--text-muted)}
        .qd-comment-text{color:#334155;line-height:1.5}
        .qd-comment-actions{display:flex;gap:10px;margin-top:4px}
        .qd-cmt-act{font-size:.76rem;color:var(--text-muted);cursor:pointer;border:none;background:none;padding:2px 4px;display:inline-flex;align-items:center;gap:3px;border-radius:4px;transition:.2s}
        .qd-cmt-act:hover{background:rgba(25,55,109,.06);color:var(--yale-blue)}
        .qd-cmt-act.active{color:var(--yale-blue);font-weight:600}
        .qd-cmt-act i{font-size:.9rem}

        .qd-load-more{display:block;width:100%;padding:8px;text-align:center;color:var(--yale-blue);font-weight:600;font-size:.85rem;cursor:pointer;border:1px dashed var(--border-color);border-radius:8px;background:rgba(25,55,109,.02);margin-top:8px;transition:.2s}
        .qd-load-more:hover{background:rgba(25,55,109,.06);border-color:var(--yale-blue)}

        .qd-comment-form{display:flex;gap:8px;margin-top:10px;align-items:center}
        .qd-comment-input{flex:1;padding:8px 12px;border:1px solid var(--border-color);border-radius:8px;font-size:.85rem;outline:none;transition:.2s}
        .qd-comment-input:focus{border-color:var(--yale-blue)}
        .qd-comment-submit{padding:8px 14px;border:none;border-radius:8px;background:var(--yale-blue);color:#fff;font-size:.82rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:.2s}
        .qd-comment-submit:hover{background:var(--oxford-navy)}

        /* Empty */
        .qd-empty{text-align:center;padding:40px 20px;color:var(--text-muted)}
        .qd-empty i{font-size:2.5rem;margin-bottom:10px;display:block}

        /* Sidebar */
        .qd-side-card{background:#fff;border-radius:14px;padding:22px;margin-bottom:18px;border:1px solid var(--border-color)}
        .qd-side-card h3{font-family:'Space Grotesk',sans-serif;font-size:1rem;font-weight:700;margin-bottom:12px;color:var(--ink-black)}
        .qd-side-card p{font-size:.88rem;color:var(--text-muted);margin-bottom:14px;line-height:1.5}
        .qd-side-btn{display:block;width:100%;padding:10px;border-radius:10px;text-align:center;font-weight:700;font-size:.9rem;cursor:pointer;transition:.2s;border:none}
        .qd-side-btn.primary{background:var(--success-green);color:#fff}
        .qd-side-btn.primary:hover{background:#059669}
        .qd-side-btn.outline{background:#fff;color:var(--yale-blue);border:1.5px solid var(--yale-blue);margin-top:10px}
        .qd-side-btn.outline:hover{background:var(--yale-blue);color:#fff}
        .qd-search-box{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-size:.88rem;outline:none;margin-bottom:10px}
        .qd-search-box:focus{border-color:var(--yale-blue)}

        /* Modals */
        .qd-modal-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center}
        .qd-modal-overlay.show{display:flex}
        .qd-modal{background:#fff;border-radius:16px;width:95%;max-width:680px;max-height:85vh;overflow-y:auto;padding:28px 32px}
        .qd-modal h3{font-family:'Space Grotesk',sans-serif;font-size:1.15rem;font-weight:700;margin-bottom:6px}
        .qd-modal .qd-modal-q{font-size:.88rem;color:var(--text-muted);margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border-color)}
        .qd-modal textarea{width:100%;min-height:160px;border:1px solid var(--border-color);border-radius:10px;padding:14px;font-size:.92rem;resize:vertical;outline:none;font-family:'Inter',sans-serif;margin-bottom:14px}
        .qd-modal textarea:focus{border-color:var(--yale-blue)}
        .qd-modal-actions{display:flex;gap:10px;justify-content:flex-end}
        .qd-modal-cancel{padding:8px 18px;border-radius:8px;border:1px solid var(--border-color);background:#fff;cursor:pointer;font-size:.88rem;font-weight:600}
        .qd-modal-submit{padding:8px 22px;border-radius:8px;border:none;background:var(--yale-blue);color:#fff;font-size:.88rem;font-weight:600;cursor:pointer}
        .qd-modal-submit:hover{background:var(--oxford-navy)}

        /* Report Modal */
        .qd-report-options{display:flex;flex-direction:column;gap:12px;margin:16px 0}
        .qd-report-opt{display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.9rem;color:var(--ink-black)}
        .qd-report-opt input[type="checkbox"]{width:18px;height:18px;accent-color:var(--yale-blue);cursor:pointer}
        .qd-report-opt label{cursor:pointer;flex:1}

        @media(max-width:900px){
            .qd-question{padding:20px}
            .qd-title{font-size:1.25rem}
            .qd-answer-card{padding:18px}
            .qd-modal{padding:20px}
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="qd-page">
        <a href="/ADMISSION/community" class="qd-back-link"><i class="ph ph-arrow-left"></i> Back to Q&A</a>

        <div class="qd-breadcrumb">
            <a href="<?= $navBase ?>/">Home</a><span>/</span>
            <a href="<?= $navBase ?>/community">Ask & Answer</a><span>/</span>
            Question
        </div>

        <div class="qd-layout">
            <div class="qd-main">
                <!-- Question -->
                <div class="qd-question">
                    <div class="qd-tags">
                        <?php if (!empty($question['question_category'])): ?>
                            <span class="qd-tag"><?= getCategoryIcon($question['question_category']) ?> <?= htmlspecialchars(ucfirst($question['question_category'])) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($question['related_college_id'])): ?>
                            <span class="qd-tag"><i class="ph ph-school"></i> College</span>
                        <?php endif; ?>
                        <?php if (!empty($question['related_exam_id'])): ?>
                            <span class="qd-tag"><i class="ph ph-exam"></i> Exam</span>
                        <?php endif; ?>
                        <?php if (!empty($question['related_course_id'])): ?>
                            <span class="qd-tag"><i class="ph ph-book-open-text"></i> Course</span>
                        <?php endif; ?>
                    </div>

                    <h1 class="qd-title"><?= htmlspecialchars($qTitle) ?></h1>

                    <?php if ($qDetails): ?>
                        <div class="qd-details"><?= nl2br(htmlspecialchars($qDetails)) ?></div>
                    <?php endif; ?>

                    <div class="qd-meta">
                        <span class="qd-meta-item"><i class="ph ph-eye"></i> <?= number_format($question['views']) ?> Views</span>
                        <span class="qd-meta-divider"></span>
                        <span class="qd-meta-item" id="qd-follow-count"><i class="ph ph-bell"></i> <?= number_format($followCount) ?> Followers</span>
                        <span class="qd-meta-divider"></span>
                        <span class="qd-meta-item"><i class="ph ph-clock"></i> Posted <?= timeAgo($question['created_at']) ?></span>
                    </div>

                    <div class="qd-asked-by">Asked by <strong><?= htmlspecialchars($question['asker_name'] ?? 'Anonymous') ?></strong></div>

                    <div class="qd-actions">
                        <button class="qd-act-btn" onclick="shareQuestion()" title="Share"><i class="ph ph-share-network"></i> Share</button>
                        <button class="qd-act-btn <?= $isFollowing ? 'follow-active' : '' ?>" id="qd-follow-btn" onclick="toggleFollow()">
                            <i class="<?= $isFollowing ? 'ph-fill' : 'ph' ?> ph-bell"></i>
                            <span id="qd-follow-label"><?= $isFollowing ? 'Following' : 'Follow' ?></span>
                        </button>
                        <button class="qd-act-btn answer-btn" onclick="openModal('answerModal')"><i class="ph ph-pencil-simple"></i> Answer</button>
                    </div>
                </div>

                <!-- Answers -->
                <div class="qd-answers-header">
                    <span class="qd-answers-count"><?= count($answers) ?> Answer<?= count($answers) !== 1 ? 's' : '' ?></span>
                    <div class="qd-sort">
                        Sort by:
                        <select onchange="window.location.href='?id=<?= urlencode($questionId) ?>&sort='+this.value">
                            <option value="upvotes" <?= $sortBy==='upvotes'?'selected':'' ?>>Upvotes</option>
                            <option value="newest" <?= $sortBy==='newest'?'selected':'' ?>>Newest</option>
                            <option value="oldest" <?= $sortBy==='oldest'?'selected':'' ?>>Oldest</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($answers)): ?>
                    <div class="qd-empty"><i class="ph ph-chat-text"></i><p>No answers yet. Be the first to answer!</p></div>
                <?php else: ?>
                    <?php foreach ($answers as $ans):
                        $ansId = $ans['id'];
                        $ansText = $ans['answer_text'] ?? '';
                        $totalComments = $answerCommentCounts[$ansId] ?? 0;
                        $showComments = $answerComments[$ansId] ?? [];
                    ?>
                        <div class="qd-answer-card" id="ans-<?= $ansId ?>">
                            <div class="qd-answer-header">
                                <div class="qd-answerer">
                                    <div class="qd-avatar" style="background:<?= getAvatarColor($ans['replier_name'] ?? 'A') ?>"><?= strtoupper(mb_substr($ans['replier_name'] ?? 'A', 0, 1)) ?></div>
                                    <div>
                                        <span class="qd-answerer-name"><?= htmlspecialchars($ans['replier_name'] ?? 'Anonymous') ?></span>
                                        <?php if ((int)($ans['is_expert_answer'] ?? 0) === 1): ?>
                                            <span class="qd-expert-badge"><i class="ph-fill ph-seal-check"></i> Expert</span>
                                        <?php endif; ?>
                                        <div class="qd-answerer-info">Answered <?= timeAgo($ans['created_at']) ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="qd-answer-body <?= mb_strlen(strip_tags($ansText)) > 300 ? 'truncated' : '' ?>" id="ans-body-<?= $ansId ?>"><?= nl2br(htmlspecialchars($ansText)) ?></div>
                            <?php if (mb_strlen(strip_tags($ansText)) > 300): ?>
                                <button class="qd-read-more" onclick="toggleReadMore('<?= $ansId ?>', this)">...Read more</button>
                            <?php endif; ?>

                            <div class="qd-answer-actions">
                                <div class="qd-vote-group">
                                    <button class="qd-ans-act <?= isset($_SESSION['upvoted_answers']) && in_array($ansId, $_SESSION['upvoted_answers']) ? 'active' : '' ?>" onclick="voteAnswer('<?= $ansId ?>', 'up', this)" title="Upvote">
                                        <i class="<?= isset($_SESSION['upvoted_answers']) && in_array($ansId, $_SESSION['upvoted_answers']) ? 'ph-fill' : 'ph' ?> ph-thumbs-up"></i>
                                        <span class="count upvote-count"><?= number_format((int)$ans['upvotes']) ?></span>
                                    </button>
                                    <span class="qd-vote-sep"></span>
                                    <button class="qd-ans-act <?= isset($_SESSION['downvoted_answers']) && in_array($ansId, $_SESSION['downvoted_answers']) ? 'active' : '' ?>" onclick="voteAnswer('<?= $ansId ?>', 'down', this)" title="Downvote">
                                        <i class="<?= isset($_SESSION['downvoted_answers']) && in_array($ansId, $_SESSION['downvoted_answers']) ? 'ph-fill' : 'ph' ?> ph-thumbs-down"></i>
                                    </button>
                                </div>

                                <button class="qd-ans-act" onclick="toggleComments('<?= $ansId ?>')">
                                    <i class="ph ph-chats"></i> <span id="comment-count-<?= $ansId ?>"><?= $totalComments ?></span> Comment<?= $totalComments !== 1 ? 's' : '' ?>
                                </button>

                                <button class="qd-ans-act" onclick="shareAnswer('<?= $ansId ?>', '<?= htmlspecialchars(addslashes($qTitle)) ?>')" title="Share">
                                    <i class="ph ph-share-network"></i> Share
                                </button>

                                <button class="qd-ans-act" onclick="openReportModal('answer', '<?= $ansId ?>')" title="Report">
                                    <i class="ph ph-dots-three"></i>
                                </button>
                            </div>

                            <!-- Comments Section (hidden by default) -->
                            <div class="qd-comments-section" id="comments-<?= $ansId ?>" style="display:none">
                                <div id="comments-list-<?= $ansId ?>">
                                    <?php foreach (array_reverse($showComments) as $c):
                                        $cVote = $_SESSION['comment_votes'][$c['id'] ?? ''] ?? '';
                                    ?>
                                        <div class="qd-comment" id="comment-<?= $c['id'] ?>">
                                            <div class="qd-comment-avatar" style="background:<?= getAvatarColor($c['user_name'] ?? 'A') ?>"><?= strtoupper(mb_substr($c['user_name'] ?? 'A', 0, 1)) ?></div>
                                            <div class="qd-comment-body">
                                                <div class="qd-comment-meta">
                                                    <span class="qd-comment-name"><?= htmlspecialchars($c['user_name'] ?? 'Anonymous') ?></span>
                                                    <span class="qd-comment-time"><?= timeAgo($c['created_at']) ?></span>
                                                </div>
                                                <div class="qd-comment-text"><?= nl2br(htmlspecialchars($c['comment_text'])) ?></div>
                                                <div class="qd-comment-actions">
                                                    <button class="qd-cmt-act <?= $cVote === 'like' ? 'active' : '' ?>" onclick="voteComment('<?= $c['id'] ?>', 'like', this)">
                                                        <i class="<?= $cVote === 'like' ? 'ph-fill' : 'ph' ?> ph-thumbs-up"></i> <span class="cmt-like-count"><?= (int)$c['like_count'] ?></span>
                                                    </button>
                                                    <button class="qd-cmt-act <?= $cVote === 'dislike' ? 'active' : '' ?>" onclick="voteComment('<?= $c['id'] ?>', 'dislike', this)">
                                                        <i class="<?= $cVote === 'dislike' ? 'ph-fill' : 'ph' ?> ph-thumbs-down"></i> <span class="cmt-dislike-count"><?= (int)$c['dislike_count'] ?></span>
                                                    </button>
                                                    <button class="qd-cmt-act" onclick="openReportModal('comment', '<?= $c['id'] ?>')"><i class="ph ph-flag"></i> Report</button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($totalComments > 2): ?>
                                    <button class="qd-load-more" id="load-more-<?= $ansId ?>" onclick="loadMoreComments('<?= $ansId ?>', 2)">Load Previous Comments</button>
                                <?php endif; ?>

                                <div class="qd-comment-form">
                                    <input type="text" class="qd-comment-input" id="comment-input-<?= $ansId ?>" placeholder="Add a comment..." onkeydown="if(event.key==='Enter')submitComment('<?= $ansId ?>')">
                                    <button class="qd-comment-submit" onclick="submitComment('<?= $ansId ?>')">Post</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="qd-sidebar">
                <div class="qd-side-card">
                    <h3>Share Your College Life Experience</h3>
                    <p>Write a review and help thousands of students make better decisions.</p>
                    <a href="<?= $navBase ?>/community?tab=ask" class="qd-side-btn primary">Write A Review</a>
                </div>
                <div class="qd-side-card">
                    <h3>Didn't find the answer?</h3>
                    <form action="<?= $navBase ?>/community" method="GET" style="display:flex;flex-direction:column;gap:10px;">
                        <input type="hidden" name="tab" value="qna">
                        <input type="text" name="q" class="qd-search-box" placeholder="Search topics...">
                        <button type="submit" class="qd-side-btn outline">Search Q&A</button>
                    </form>
                </div>
                <div class="qd-side-card">
                    <h3>Ask Our Experts</h3>
                    <p>Get instant answers to your queries from real people.</p>
                    <a href="<?= $navBase ?>/community?tab=ask" class="qd-side-btn primary" style="background:var(--yale-blue);">Ask Now</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Answer Modal -->
    <div class="qd-modal-overlay" id="answerModal">
        <div class="qd-modal">
            <h3><i class="ph ph-pencil-simple"></i> Your Answer</h3>
            <div class="qd-modal-q"><?= htmlspecialchars($qTitle) ?></div>
            <form id="answerForm" onsubmit="submitAnswer(event)">
                <div id="answerEditor" style="min-height:180px;border:1px solid var(--border-color);border-radius:10px;margin-bottom:14px;"></div>
                <input type="hidden" name="question_id" value="<?= htmlspecialchars($questionId) ?>">
                <div class="qd-modal-actions">
                    <button type="button" class="qd-modal-cancel" onclick="closeModal('answerModal')">Cancel</button>
                    <button type="submit" class="qd-modal-submit">Post Answer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Abuse Modal -->
    <div class="qd-modal-overlay" id="reportModal">
        <div class="qd-modal" style="max-width:560px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                <h3>Report Abuse</h3>
                <button onclick="closeModal('reportModal')" style="border:none;background:none;font-size:1.4rem;cursor:pointer;color:var(--text-muted)"><i class="ph ph-x"></i></button>
            </div>
            <p style="font-size:.88rem;color:var(--text-muted);margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border-color)">Kindly select reason(s) below & cast your vote for removal of this content</p>

            <div class="qd-report-options">
                <?php
                $reportReasons = [
                    'spam' => 'Marketing / Spam - Unrelated commercial information (or spam)',
                    'abusive' => 'Abusive / Inappropriate - Obscene-illegal, vulgar or objectionable language',
                    'irrelevant' => 'Irrelevant - Out of context or wrongly posted / categorized',
                    'duplicate' => 'Duplicate - Identical or nearly identical to content already posted',
                    'copyright' => 'Copyright Violation - The content violates any stipulated law or regulation',
                    'other' => 'Other'
                ];
                foreach ($reportReasons as $key => $label): ?>
                    <div class="qd-report-opt">
                        <input type="checkbox" id="rpt-<?= $key ?>" value="<?= $key ?>">
                        <label for="rpt-<?= $key ?>"><?= $label ?></label>
                    </div>
                <?php endforeach; ?>
                <div class="qd-report-opt" style="margin-top:4px">
                    <input type="checkbox" id="rpt-other-check">
                    <label for="rpt-other-check" style="font-weight:600">Other (specify below)</label>
                </div>
                <textarea id="rpt-other-text" placeholder="Describe the issue..." style="width:100%;min-height:60px;border:1px solid var(--border-color);border-radius:8px;padding:10px;font-size:.88rem;outline:none;display:none;margin-top:4px;resize:vertical" onfocus="document.getElementById('rpt-other-check').checked=true"></textarea>
            </div>

            <input type="hidden" id="rpt-type">
            <input type="hidden" id="rpt-id">

            <div class="qd-modal-actions">
                <button class="qd-modal-cancel" onclick="closeModal('reportModal')">Cancel</button>
                <button class="qd-modal-submit" onclick="submitReport()">Submit</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/trumbowyg/dist/trumbowyg.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#answerEditor').trumbowyg({
            btns: [['bold','italic','underline','strikeThrough'],['unorderedList','orderedList'],['link'],['justifyLeft','justifyCenter','justifyRight'],['fullscreen']],
            autogrow: true,
            placeholder: 'Write your answer here...'
        });
        document.querySelectorAll('.qd-report-opt input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', function() {
                if (this.id === 'rpt-other-check') {
                    document.getElementById('rpt-other-text').style.display = this.checked ? 'block' : 'none';
                }
            });
        });
    });

    // Modal
    function openModal(id) { document.getElementById(id).classList.add('show'); document.body.style.overflow = 'hidden'; }
    function closeModal(id) { document.getElementById(id).classList.remove('show'); document.body.style.overflow = ''; }
    document.querySelectorAll('.qd-modal-overlay').forEach(el => {
        el.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('show'); document.body.style.overflow = ''; });
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { document.querySelectorAll('.qd-modal-overlay.show').forEach(m => { m.classList.remove('show'); document.body.style.overflow = ''; }); }});

    // Read More
    function toggleReadMore(ansId, btn) {
        const body = document.getElementById('ans-body-' + ansId);
        const isTruncated = body.classList.contains('truncated');
        body.classList.toggle('truncated');
        btn.textContent = isTruncated ? '...Read more' : 'Show less';
    }

    // Follow
    async function toggleFollow() {
        const btn = document.getElementById('qd-follow-btn');
        try {
            const res = await fetch('/ADMISSION/api/community_actions.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle_follow', type: 'question', id: '<?= $questionId ?>' })
            });
            const data = await res.json();
            if (data.status === 'success') {
                const isActive = data.action === 'followed';
                btn.classList.toggle('follow-active', isActive);
                btn.querySelector('i').className = isActive ? 'ph-fill ph-bell' : 'ph ph-bell';
                document.getElementById('qd-follow-label').textContent = isActive ? 'Following' : 'Follow';
                document.getElementById('qd-follow-count').innerHTML = '<i class="ph ph-bell"></i> ' + data.count.toLocaleString() + ' Followers';
            }
        } catch (err) { console.error(err); }
    }

    // Share
    function shareQuestion() { shareUrl(window.location.href, '<?= addslashes($qTitle) ?>'); }
    function shareAnswer(ansId, title) { shareUrl(window.location.href + '#ans-' + ansId, title); }
    function shareUrl(url, title) {
        if (navigator.share) { navigator.share({ title, url }); }
        else { navigator.clipboard.writeText(url).then(() => alert('Link copied!')); }
    }

    // Vote Answer (up/down)
    async function voteAnswer(ansId, voteType, btn) {
        try {
            const res = await fetch('/ADMISSION/api/community_actions.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'vote_answer', id: ansId, vote_type: voteType })
            });
            const data = await res.json();
            if (data.status === 'success') {
                const card = document.getElementById('ans-' + ansId);
                const upBtn = card.querySelector('.qd-vote-group .qd-ans-act:first-child');
                const downBtn = card.querySelector('.qd-vote-group .qd-ans-act:last-child');
                const upIcon = upBtn.querySelector('i');
                const downIcon = downBtn.querySelector('i');
                const upCount = upBtn.querySelector('.upvote-count');

                upBtn.classList.toggle('active', data.action === 'upvoted');
                downBtn.classList.toggle('active', data.action === 'downvoted');
                upIcon.className = data.action === 'upvoted' ? 'ph-fill ph-thumbs-up' : 'ph ph-thumbs-up';
                downIcon.className = data.action === 'downvoted' ? 'ph-fill ph-thumbs-down' : 'ph ph-thumbs-down';
                upCount.textContent = data.upvotes.toLocaleString();
            }
        } catch (err) { console.error(err); }
    }

    // Toggle Comments
    function toggleComments(ansId) {
        const section = document.getElementById('comments-' + ansId);
        section.style.display = section.style.display === 'none' ? 'block' : 'none';
    }

    // Load More Comments
    async function loadMoreComments(ansId, offset) {
        const btn = document.getElementById('load-more-' + ansId);
        btn.textContent = 'Loading...';
        btn.disabled = true;
        try {
            const res = await fetch('/ADMISSION/api/community_actions.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'load_comments', answer_id: ansId, offset: offset })
            });
            const data = await res.json();
            if (data.status === 'success') {
                const list = document.getElementById('comments-list-' + ansId);
                data.comments.forEach(c => {
                    const vote = '<?= json_encode($_SESSION["comment_votes"] ?? []) ?>' [c.id] || '';
                    list.insertAdjacentHTML('afterbegin', `
                        <div class="qd-comment" id="comment-${c.id}">
                            <div class="qd-comment-avatar" style="background:${avatarColor(c.user_name)}">${(c.user_name||'A')[0].toUpperCase()}</div>
                            <div class="qd-comment-body">
                                <div class="qd-comment-meta">
                                    <span class="qd-comment-name">${esc(c.user_name||'Anonymous')}</span>
                                    <span class="qd-comment-time">${timeAgo(c.created_at)}</span>
                                </div>
                                <div class="qd-comment-text">${esc(c.comment_text)}</div>
                                <div class="qd-comment-actions">
                                    <button class="qd-cmt-act" onclick="voteComment('${c.id}','like',this)"><i class="ph ph-thumbs-up"></i> <span class="cmt-like-count">${c.like_count||0}</span></button>
                                    <button class="qd-cmt-act" onclick="voteComment('${c.id}','dislike',this)"><i class="ph ph-thumbs-down"></i> <span class="cmt-dislike-count">${c.dislike_count||0}</span></button>
                                    <button class="qd-cmt-act" onclick="openReportModal('comment','${c.id}')"><i class="ph ph-flag"></i> Report</button>
                                </div>
                            </div>
                        </div>
                    `);
                });
                if (!data.has_more && btn) btn.remove();
                else { btn.textContent = 'Load Previous Comments'; btn.disabled = false; btn.onclick = () => loadMoreComments(ansId, offset + 10); }
            }
        } catch (err) { console.error(err); btn.textContent = 'Load Previous Comments'; btn.disabled = false; }
    }

    // Submit Comment
    async function submitComment(ansId) {
        const input = document.getElementById('comment-input-' + ansId);
        const text = input.value.trim();
        if (text.length < 2) { alert('Comment must be at least 2 characters.'); return; }
        try {
            const res = await fetch('/ADMISSION/api/community_actions.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'submit_comment', answer_id: ansId, comment_text: text })
            });
            const data = await res.json();
            if (data.status === 'success') {
                const list = document.getElementById('comments-list-' + ansId);
                const c = data.comment;
                list.insertAdjacentHTML('beforeend', `
                    <div class="qd-comment" id="comment-${c.id}">
                        <div class="qd-comment-avatar" style="background:${avatarColor(c.user_name)}">${(c.user_name||'A')[0].toUpperCase()}</div>
                        <div class="qd-comment-body">
                            <div class="qd-comment-meta">
                                <span class="qd-comment-name">${esc(c.user_name)}</span>
                                <span class="qd-comment-time">just now</span>
                            </div>
                            <div class="qd-comment-text">${esc(c.comment_text)}</div>
                            <div class="qd-comment-actions">
                                <button class="qd-cmt-act" onclick="voteComment('${c.id}','like',this)"><i class="ph ph-thumbs-up"></i> <span class="cmt-like-count">0</span></button>
                                <button class="qd-cmt-act" onclick="voteComment('${c.id}','dislike',this)"><i class="ph ph-thumbs-down"></i> <span class="cmt-dislike-count">0</span></button>
                                <button class="qd-cmt-act" onclick="openReportModal('comment','${c.id}')"><i class="ph ph-flag"></i> Report</button>
                            </div>
                        </div>
                    </div>
                `);
                input.value = '';
                const cnt = document.getElementById('comment-count-' + ansId);
                cnt.textContent = parseInt(cnt.textContent) + 1;
                document.getElementById('comments-' + ansId).style.display = 'block';
            } else { alert(data.message || 'Error posting comment.'); }
        } catch (err) { console.error(err); alert('Network error.'); }
    }

    // Vote Comment
    async function voteComment(commentId, voteType, btn) {
        try {
            const res = await fetch('/ADMISSION/api/community_actions.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'vote_comment', comment_id: commentId, vote_type: voteType })
            });
            const data = await res.json();
            if (data.status === 'success') {
                const row = document.getElementById('comment-' + commentId);
                const likes = row.querySelector('.cmt-like-count');
                const dislikes = row.querySelector('.cmt-dislike-count');
                const likeBtn = likes.closest('.qd-cmt-act');
                const dislikeBtn = dislikes.closest('.qd-cmt-act');
                likes.textContent = data.like_count;
                dislikes.textContent = data.dislike_count;
                likeBtn.classList.toggle('active', data.action !== 'removed' && voteType === 'like');
                dislikeBtn.classList.toggle('active', data.action !== 'removed' && voteType === 'dislike');
                likeBtn.querySelector('i').className = likeBtn.classList.contains('active') ? 'ph-fill ph-thumbs-up' : 'ph ph-thumbs-up';
                dislikeBtn.querySelector('i').className = dislikeBtn.classList.contains('active') ? 'ph-fill ph-thumbs-down' : 'ph ph-thumbs-down';
            }
        } catch (err) { console.error(err); }
    }

    // Report Abuse
    function openReportModal(type, id) {
        document.getElementById('rpt-type').value = type;
        document.getElementById('rpt-id').value = id;
        document.querySelectorAll('.qd-report-options input[type="checkbox"]').forEach(c => c.checked = false);
        document.getElementById('rpt-other-text').style.display = 'none';
        document.getElementById('rpt-other-text').value = '';
        openModal('reportModal');
    }

    async function submitReport() {
        const type = document.getElementById('rpt-type').value;
        const id = document.getElementById('rpt-id').value;
        const reasons = [];
        document.querySelectorAll('.qd-report-options input[type="checkbox"]:checked').forEach(cb => {
            if (cb.id !== 'rpt-other-check') reasons.push(cb.value);
        });
        const otherText = document.getElementById('rpt-other-text').value.trim();
        if (reasons.length === 0 && !otherText) { alert('Please select at least one reason.'); return; }
        if (otherText && !reasons.includes('other')) reasons.push('other');
        try {
            const res = await fetch('/ADMISSION/api/community_actions.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'report_abuse', report_type: type, report_id: id, reasons: reasons, other_text: otherText })
            });
            const data = await res.json();
            if (data.status === 'success') {
                alert(data.message);
                closeModal('reportModal');
            } else { alert(data.message || 'Error submitting report.'); }
        } catch (err) { console.error(err); alert('Network error.'); }
    }

    // Submit Answer
    async function submitAnswer(e) {
        e.preventDefault();
        const editor = $('#answerEditor');
        const text = editor.trumbowyg('html').replace(/<[^>]*>/g, '').trim();
        if (text.length < 10) { alert('Answer must be at least 10 characters.'); return; }
        const fd = new FormData(document.getElementById('answerForm'));
        fd.append('answer_text', editor.trumbowyg('html'));
        fd.append('action', 'submit_answer');
        try {
            const res = await fetch('/ADMISSION/api/community_actions.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') window.location.reload();
            else alert(data.message || 'Error posting answer.');
        } catch (err) { console.error(err); alert('Network error.'); }
    }

    // Helpers
    function avatarColor(name) {
        const colors = ['#1e3a8a','#1e40af','#14532d','#7c2d12','#4c1d95','#0f766e','#831843','#475569','#111827'];
        let h = 0; for (let i = 0; i < (name||'').length; i++) h = ((h << 5) - h + name.charCodeAt(i)) | 0;
        return colors[Math.abs(h) % colors.length];
    }
    function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function timeAgo(dt) {
        const s = Math.floor((Date.now() - new Date(dt)) / 1000);
        if (s < 60) return 'just now';
        if (s < 3600) return Math.floor(s/60) + ' min ago';
        if (s < 86400) return Math.floor(s/3600) + ' hour(s) ago';
        if (s < 2592000) return Math.floor(s/86400) + ' day(s) ago';
        return Math.floor(s/2592000) + ' month(s) ago';
    }
    </script>
</body>
</html>
