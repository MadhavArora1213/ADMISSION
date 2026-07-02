<?php
session_start();
require_once __DIR__ . '/../admin/db.php';

function collegeAuth() {
    global $pdo;
    if (empty($_SESSION['college_account_id'])) {
        header('Location: ' . BASE_URL . '/college/login.php');
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM college_accounts WHERE id=? AND status IN ('approved','active')");
    $stmt->execute([$_SESSION['college_account_id']]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$account) {
        session_destroy();
        header('Location: ' . BASE_URL . '/college/login.php');
        exit;
    }
    return $account;
}

function collegeId() {
    return $_SESSION['college_account_id'] ?? null;
}
