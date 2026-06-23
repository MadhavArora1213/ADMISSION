<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../admin/db.php';

$response = ['status' => 'error', 'message' => 'Invalid request'];

try {
    // Read JSON payload or fall back to $_POST
    $input = json_decode(file_get_contents('php://input') ?: '{}', true);
    if (empty($input)) {
        $input = $_POST;
    }

    $action = $input['action'] ?? '';
    $id = $input['id'] ?? '';

    if (empty($action)) {
        throw new Exception('Missing action.');
    }

    if ($action === 'upvote_question') {
        if (!isset($_SESSION['upvoted_questions'])) {
            $_SESSION['upvoted_questions'] = [];
        }

        if (in_array($id, $_SESSION['upvoted_questions'])) {
            // Already upvoted, so undo the upvote (downvote/toggle off)
            $stmt = $pdo->prepare("UPDATE questions SET upvotes = GREATEST(0, upvotes - 1), trending_score = GREATEST(0, trending_score - 1.0) WHERE id = ?");
            $stmt->execute([$id]);

            // Remove from session
            $_SESSION['upvoted_questions'] = array_diff($_SESSION['upvoted_questions'], [$id]);

            // Fetch new count
            $stmtCount = $pdo->prepare("SELECT upvotes FROM questions WHERE id = ?");
            $stmtCount->execute([$id]);
            $newCount = $stmtCount->fetchColumn();

            $response = [
                'status' => 'success',
                'action' => 'undone',
                'count' => (int)$newCount
            ];
        } else {
            // Add upvote
            $stmt = $pdo->prepare("UPDATE questions SET upvotes = upvotes + 1, trending_score = trending_score + 1.0 WHERE id = ?");
            $stmt->execute([$id]);

            // Save to session
            $_SESSION['upvoted_questions'][] = $id;

            // Fetch new count
            $stmtCount = $pdo->prepare("SELECT upvotes FROM questions WHERE id = ?");
            $stmtCount->execute([$id]);
            $newCount = $stmtCount->fetchColumn();

            $response = [
                'status' => 'success',
                'action' => 'upvoted',
                'count' => (int)$newCount
            ];
        }
    } elseif ($action === 'upvote_answer') {
        if (!isset($_SESSION['upvoted_answers'])) {
            $_SESSION['upvoted_answers'] = [];
        }

        if (in_array($id, $_SESSION['upvoted_answers'])) {
            // Undo upvote
            $stmt = $pdo->prepare("UPDATE answers SET upvotes = GREATEST(0, upvotes - 1) WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['upvoted_answers'] = array_diff($_SESSION['upvoted_answers'], [$id]);

            $stmtCount = $pdo->prepare("SELECT upvotes FROM answers WHERE id = ?");
            $stmtCount->execute([$id]);
            $newCount = $stmtCount->fetchColumn();

            $response = [
                'status' => 'success',
                'action' => 'undone',
                'count' => (int)$newCount
            ];
        } else {
            // Add upvote
            $stmt = $pdo->prepare("UPDATE answers SET upvotes = upvotes + 1 WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['upvoted_answers'][] = $id;

            $stmtCount = $pdo->prepare("SELECT upvotes FROM answers WHERE id = ?");
            $stmtCount->execute([$id]);
            $newCount = $stmtCount->fetchColumn();

            $response = [
                'status' => 'success',
                'action' => 'upvoted',
                'count' => (int)$newCount
            ];
        }
    } elseif ($action === 'increment_views') {
        $viewerUserId  = $_SESSION['user_id'] ?? null;
        $viewerSession = session_id();
        $viewerIp      = $_SERVER['REMOTE_ADDR'] ?? '';

        // Check if already viewed in last 24h
        $alreadyViewed = false;
        if ($viewerUserId) {
            $chk = $pdo->prepare("SELECT id FROM question_views WHERE question_id=? AND user_id=? AND viewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1");
            $chk->execute([$id, $viewerUserId]);
            $alreadyViewed = (bool)$chk->fetch();
        } else {
            $chk = $pdo->prepare("SELECT id FROM question_views WHERE question_id=? AND ip_address=? AND viewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1");
            $chk->execute([$id, $viewerIp]);
            $alreadyViewed = (bool)$chk->fetch();
        }

        if (!$alreadyViewed) {
            $ins = $pdo->prepare("INSERT INTO question_views (question_id, user_id, session_id, ip_address) VALUES (?, ?, ?, ?)");
            $ins->execute([$id, $viewerUserId, $viewerSession, $viewerIp]);
            // Update cached count + trending
            $cnt = $pdo->prepare("SELECT COUNT(DISTINCT COALESCE(user_id, ip_address)) FROM question_views WHERE question_id=?");
            $cnt->execute([$id]);
            $newCount = (int)$cnt->fetchColumn();
            $pdo->prepare("UPDATE questions SET views=?, trending_score = trending_score + 0.1 WHERE id=?")->execute([$newCount, $id]);
            $response = ['status' => 'success', 'action' => 'incremented', 'count' => $newCount];
        } else {
            $stmtCount = $pdo->prepare("SELECT views FROM questions WHERE id = ?");
            $stmtCount->execute([$id]);
            $response = ['status' => 'success', 'action' => 'ignored', 'count' => (int)$stmtCount->fetchColumn()];
        }
    } else if ($action === 'toggle_follow') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Please login to follow.');
        }
        $type = $input['type'] ?? '';  // 'question' or 'expert'
        $targetId = $input['id'] ?? '';
        $userId = $_SESSION['user_id'];

        if (!in_array($type, ['question', 'expert'])) {
            throw new Exception('Invalid follow type.');
        }

        $table = ($type === 'question') ? 'questions' : 'experts';

        // Check if already following
        $check = $pdo->prepare("SELECT id FROM follows WHERE user_id = ? AND followable_type = ? AND followable_id = ?");
        $check->execute([$userId, $type, $targetId]);
        $existing = $check->fetch();

        if ($existing) {
            // Unfollow
            $pdo->prepare("DELETE FROM follows WHERE id = ?")->execute([$existing['id']]);
            $pdo->prepare("UPDATE $table SET follow_count = GREATEST(0, follow_count - 1) WHERE id = ?")->execute([$targetId]);
            
            $countStmt = $pdo->prepare("SELECT follow_count FROM $table WHERE id = ?");
            $countStmt->execute([$targetId]);
            $response = ['status' => 'success', 'action' => 'unfollowed', 'count' => (int)$countStmt->fetchColumn()];
        } else {
            // Follow
            $followId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
            $pdo->prepare("INSERT INTO follows (id, user_id, followable_type, followable_id) VALUES (?, ?, ?, ?)")
                 ->execute([$followId, $userId, $type, $targetId]);
            $pdo->prepare("UPDATE $table SET follow_count = follow_count + 1 WHERE id = ?")->execute([$targetId]);
            
            $countStmt = $pdo->prepare("SELECT follow_count FROM $table WHERE id = ?");
            $countStmt->execute([$targetId]);
            $response = ['status' => 'success', 'action' => 'followed', 'count' => (int)$countStmt->fetchColumn()];
        }
    } else if ($action === 'submit_answer') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Please login to answer.');
        }
        $questionId = $input['question_id'] ?? '';
        $answerText = $input['answer_text'] ?? '';
        $userId = $_SESSION['user_id'];

        if (empty($questionId) || empty($answerText)) {
            throw new Exception('Missing question ID or answer text.');
        }

        $cleanText = strip_tags($answerText);
        if (strlen(trim($cleanText)) < 10) {
            throw new Exception('Answer must be at least 10 characters.');
        }

        // Verify question exists
        $qCheck = $pdo->prepare("SELECT id FROM questions WHERE id = ?");
        $qCheck->execute([$questionId]);
        if (!$qCheck->fetch()) {
            throw new Exception('Question not found.');
        }

        $ansId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));

        $pdo->prepare("INSERT INTO answers (id, question_id, answer_text, answered_by, upvotes, is_expert_answer) VALUES (?, ?, ?, ?, 0, 0)")
             ->execute([$ansId, $questionId, $answerText, $userId]);

        $pdo->prepare("UPDATE questions SET answer_count = answer_count + 1 WHERE id = ?")->execute([$questionId]);

        $response = ['status' => 'success', 'message' => 'Answer posted successfully.', 'answer_id' => $ansId];
    } else if ($action === 'submit_comment') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Please login to comment.');
        }
        $answerId = $input['answer_id'] ?? '';
        $commentText = trim($input['comment_text'] ?? '');
        $userId = $_SESSION['user_id'];

        if (empty($answerId) || empty($commentText)) {
            throw new Exception('Missing answer ID or comment text.');
        }
        if (strlen($commentText) < 2) {
            throw new Exception('Comment must be at least 2 characters.');
        }

        $commentId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));

        $pdo->prepare("INSERT INTO comments (id, answer_id, user_id, comment_text) VALUES (?, ?, ?, ?)")
             ->execute([$commentId, $answerId, $userId, $commentText]);

        $stmtU = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmtU->execute([$userId]);
        $userName = $stmtU->fetchColumn() ?: 'Anonymous';

        $response = [
            'status' => 'success',
            'comment' => [
                'id' => $commentId,
                'user_name' => $userName,
                'comment_text' => $commentText,
                'like_count' => 0,
                'dislike_count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
    } else if ($action === 'load_comments') {
        $answerId = $input['answer_id'] ?? '';
        $offset = (int)($input['offset'] ?? 0);
        $limit = 10;

        if (empty($answerId)) {
            throw new Exception('Missing answer ID.');
        }

        $stmt = $pdo->prepare("
            SELECT c.*, u.full_name AS user_name
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.answer_id = ? AND c.status = 'active'
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$answerId, $limit, $offset]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE answer_id = ? AND status = 'active'");
        $countStmt->execute([$answerId]);
        $total = (int)$countStmt->fetchColumn();

        $response = [
            'status' => 'success',
            'comments' => $comments,
            'total' => $total,
            'has_more' => ($offset + $limit) < $total
        ];
    } else if ($action === 'vote_comment') {
        $commentId = $input['comment_id'] ?? '';
        $voteType = $input['vote_type'] ?? '';
        $userId = $_SESSION['user_id'] ?? 'user-1234-uuid';

        if (empty($commentId) || !in_array($voteType, ['like', 'dislike'])) {
            throw new Exception('Invalid vote parameters.');
        }

        $check = $pdo->prepare("SELECT id, vote_type FROM comment_votes WHERE comment_id = ? AND user_id = ?");
        $check->execute([$commentId, $userId]);
        $existing = $check->fetch();

        if ($existing) {
            if ($existing['vote_type'] === $voteType) {
                // Remove vote
                $pdo->prepare("DELETE FROM comment_votes WHERE id = ?")->execute([$existing['id']]);
                $col = $voteType === 'like' ? 'like_count' : 'dislike_count';
                $pdo->prepare("UPDATE comments SET $col = GREATEST(0, $col - 1) WHERE id = ?")->execute([$commentId]);
                $response = ['status' => 'success', 'action' => 'removed'];
            } else {
                // Switch vote
                $pdo->prepare("UPDATE comment_votes SET vote_type = ? WHERE id = ?")->execute([$voteType, $existing['id']]);
                $oldCol = $existing['vote_type'] === 'like' ? 'like_count' : 'dislike_count';
                $newCol = $voteType === 'like' ? 'like_count' : 'dislike_count';
                $pdo->prepare("UPDATE comments SET $oldCol = GREATEST(0, $oldCol - 1), $newCol = $newCol + 1 WHERE id = ?")->execute([$commentId]);
                $response = ['status' => 'success', 'action' => 'switched'];
            }
        } else {
            // New vote
            $voteId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
            $pdo->prepare("INSERT INTO comment_votes (id, comment_id, user_id, vote_type) VALUES (?, ?, ?, ?)")
                 ->execute([$voteId, $commentId, $userId, $voteType]);
            $col = $voteType === 'like' ? 'like_count' : 'dislike_count';
            $pdo->prepare("UPDATE comments SET $col = $col + 1 WHERE id = ?")->execute([$commentId]);
            $response = ['status' => 'success', 'action' => 'voted'];
        }

        $counts = $pdo->prepare("SELECT like_count, dislike_count FROM comments WHERE id = ?");
        $counts->execute([$commentId]);
        $c = $counts->fetch(PDO::FETCH_ASSOC);
        $response['like_count'] = (int)($c['like_count'] ?? 0);
        $response['dislike_count'] = (int)($c['dislike_count'] ?? 0);

    } else if ($action === 'vote_answer') {
        $answerId = $input['id'] ?? '';
        $voteType = $input['vote_type'] ?? 'up';
        $userId = $_SESSION['user_id'] ?? 'user-1234-uuid';

        if (empty($answerId) || !in_array($voteType, ['up', 'down'])) {
            throw new Exception('Invalid vote parameters.');
        }

        // Check existing vote in session
        $sessionKey = $voteType === 'up' ? 'upvoted_answers' : 'downvoted_answers';
        if (!isset($_SESSION[$sessionKey])) $_SESSION[$sessionKey] = [];

        $oppositeKey = $voteType === 'up' ? 'downvoted_answers' : 'upvoted_answers';
        if (!isset($_SESSION[$oppositeKey])) $_SESSION[$oppositeKey] = [];

        if (in_array($answerId, $_SESSION[$sessionKey])) {
            // Undo
            $col = $voteType === 'up' ? 'upvotes' : 'dislike_count';
            $pdo->prepare("UPDATE answers SET $col = GREATEST(0, $col - 1) WHERE id = ?")->execute([$answerId]);
            $_SESSION[$sessionKey] = array_diff($_SESSION[$sessionKey], [$answerId]);
            $response = ['status' => 'success', 'action' => 'undone'];
        } else {
            // Remove opposite if exists
            if (in_array($answerId, $_SESSION[$oppositeKey])) {
                $opCol = $voteType === 'up' ? 'dislike_count' : 'upvotes';
                $pdo->prepare("UPDATE answers SET $opCol = GREATEST(0, $opCol - 1) WHERE id = ?")->execute([$answerId]);
                $_SESSION[$oppositeKey] = array_diff($_SESSION[$oppositeKey], [$answerId]);
            }
            $col = $voteType === 'up' ? 'upvotes' : 'dislike_count';
            $pdo->prepare("UPDATE answers SET $col = $col + 1 WHERE id = ?")->execute([$answerId]);
            $_SESSION[$sessionKey][] = $answerId;
            $response = ['status' => 'success', 'action' => $voteType === 'up' ? 'upvoted' : 'downvoted'];
        }

        $counts = $pdo->prepare("SELECT upvotes, dislike_count FROM answers WHERE id = ?");
        $counts->execute([$answerId]);
        $c = $counts->fetch(PDO::FETCH_ASSOC);
        $response['upvotes'] = (int)($c['upvotes'] ?? 0);
        $response['dislike_count'] = (int)($c['dislike_count'] ?? 0);

    } else if ($action === 'report_abuse') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Please login to report content.');
        }
        $reportType = $input['report_type'] ?? '';
        $reportId = $input['report_id'] ?? '';
        $reasons = $input['reasons'] ?? [];
        $otherText = $input['other_text'] ?? '';
        $userId = $_SESSION['user_id'];

        if (empty($reportType) || empty($reportId) || empty($reasons)) {
            throw new Exception('Missing report details.');
        }

        // Build reason text
        $reasonText = implode(', ', $reasons);
        if ($otherText) $reasonText .= ' - ' . $otherText;

        // Set question_id or answer_id based on report type
        $questionIdCol = null;
        $answerIdCol = null;
        if ($reportType === 'answer') {
            // Look up question_id from the answer
            $ansStmt = $pdo->prepare("SELECT question_id FROM answers WHERE id = ?");
            $ansStmt->execute([$reportId]);
            $row = $ansStmt->fetch(PDO::FETCH_ASSOC);
            $questionIdCol = $row ? $row['question_id'] : null;
            $answerIdCol = $reportId;
        } elseif ($reportType === 'question') {
            $questionIdCol = $reportId;
        } elseif ($reportType === 'comment') {
            // Look up answer then question from comment
            $cmtStmt = $pdo->prepare("SELECT answer_id FROM comments WHERE id = ?");
            $cmtStmt->execute([$reportId]);
            $cmtRow = $cmtStmt->fetch(PDO::FETCH_ASSOC);
            if ($cmtRow) {
                $ansStmt2 = $pdo->prepare("SELECT question_id FROM answers WHERE id = ?");
                $ansStmt2->execute([$cmtRow['answer_id']]);
                $ansRow = $ansStmt2->fetch(PDO::FETCH_ASSOC);
                $questionIdCol = $ansRow ? $ansRow['question_id'] : null;
                $answerIdCol = $cmtRow['answer_id'];
            }
        }

        $pdo->prepare("INSERT INTO qa_reports (question_id, answer_id, report_reason, reported_by) VALUES (?, ?, ?, ?)")
             ->execute([$questionIdCol, $answerIdCol, $reasonText, $userId]);

        $response = ['status' => 'success', 'message' => 'Report submitted. Thank you!'];
    } else {
        throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
exit;
?>
