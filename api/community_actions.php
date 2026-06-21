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

    if (empty($action) || empty($id)) {
        throw new Exception('Missing action or target ID.');
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
        if (!isset($_SESSION['viewed_questions'])) {
            $_SESSION['viewed_questions'] = [];
        }

        if (!in_array($id, $_SESSION['viewed_questions'])) {
            $stmt = $pdo->prepare("UPDATE questions SET views = views + 1, trending_score = trending_score + 0.1 WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['viewed_questions'][] = $id;

            $stmtCount = $pdo->prepare("SELECT views FROM questions WHERE id = ?");
            $stmtCount->execute([$id]);
            $newCount = $stmtCount->fetchColumn();

            $response = [
                'status' => 'success',
                'action' => 'incremented',
                'count' => (int)$newCount
            ];
        } else {
            $stmtCount = $pdo->prepare("SELECT views FROM questions WHERE id = ?");
            $stmtCount->execute([$id]);
            $newCount = $stmtCount->fetchColumn();

            $response = [
                'status' => 'success',
                'action' => 'ignored',
                'count' => (int)$newCount
            ];
        }
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
