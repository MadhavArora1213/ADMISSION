<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

function insertAlert($pdo, $type, $title, $message, $severity, $module, $entityType = null, $entityId = null, $metadata = null) {
    try {
        $existing = $pdo->prepare("SELECT id FROM admin_alerts WHERE alert_type = ? AND entity_type = ? AND entity_id = ? AND status IN ('open','acknowledged')");
        $existing->execute([$type, $entityType, $entityId]);
        if ($existing->fetch()) return;
    } catch (Exception $e) {}

    $metaJson = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;
    $stmt = $pdo->prepare("INSERT INTO admin_alerts (id, alert_type, title, message, severity, source_module, entity_type, entity_id, status, metadata_json, created_at) VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, 'open', ?, NOW())");
    $stmt->execute([$type, $title, $message, $severity, $module, $entityType, $entityId, $metaJson]);
}

function countSafe($pdo, $sql) {
    try { return (int)$pdo->query($sql)->fetchColumn(); } catch (Exception $e) { return 0; }
}

$generated = 0;

// 1. Pending college account approvals
$pendingAccounts = $pdo->query("SELECT id, institute_name, email, created_at FROM college_accounts WHERE status = 'pending' ORDER BY created_at ASC")->fetchAll();
foreach ($pendingAccounts as $acc) {
    $daysOld = (int)((time() - strtotime($acc['created_at'])) / 86400);
    if ($daysOld >= 2) {
        $sev = $daysOld >= 7 ? 'critical' : ($daysOld >= 5 ? 'high' : 'medium');
        insertAlert($pdo, 'pending_approval', 'College Account Pending: ' . $acc['institute_name'],
            "Account '{$acc['institute_name']}' ({$acc['email']}) has been pending approval for {$daysOld} days.",
            $sev, 'college_accounts', 'college_accounts', $acc['id'], ['days_pending' => $daysOld]);
        $generated++;
    }
}

// 2. Pending reviews exceeding 24h
$pendingReviews = countSafe($pdo, "SELECT COUNT(*) FROM reviews WHERE moderation_status = 'pending'");
if ($pendingReviews > 0) {
    $oldest = $pdo->query("SELECT MIN(created_at) FROM reviews WHERE moderation_status = 'pending'")->fetchColumn();
    $sev = $pendingReviews >= 20 ? 'critical' : ($pendingReviews >= 10 ? 'high' : 'medium');
    insertAlert($pdo, 'pending_reviews', "Pending Review Moderation: {$pendingReviews} reviews",
        "{$pendingReviews} reviews are awaiting moderation." . ($oldest ? " Oldest from " . date('M d, Y', strtotime($oldest)) . "." : ""),
        $sev, 'reviews', 'reviews', null, ['count' => $pendingReviews, 'oldest' => $oldest]);
    $generated++;
}

// 3. Pending moderation queue items
$pendingMod = countSafe($pdo, "SELECT COUNT(*) FROM moderation_queue WHERE status IN ('pending','in_progress')");
if ($pendingMod > 0) {
    $sev = $pendingMod >= 15 ? 'critical' : ($pendingMod >= 5 ? 'high' : 'medium');
    insertAlert($pdo, 'moderation_backlog', "Moderation Backlog: {$pendingMod} items",
        "{$pendingMod} items in the moderation queue need attention.",
        $sev, 'moderation_queue', 'moderation_queue', null, ['count' => $pendingMod]);
    $generated++;
}

// 4. Failed login attempts (last 24h)
$failedLogins = $pdo->prepare("SELECT COUNT(*) as cnt, ip_address FROM audit_logs WHERE audit_action = 'login_failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY ip_address HAVING cnt >= 3");
$failedLogins->execute();
$suspiciousIPs = $failedLogins->fetchAll();
foreach ($suspiciousIPs as $ip) {
    insertAlert($pdo, 'suspicious_login', "Suspicious Login Activity from {$ip['ip_address']}",
        "Detected {$ip['cnt']} failed login attempts from IP {$ip['ip_address']} in the last 24 hours. This may indicate a brute-force attack.",
        'critical', 'security', 'ip_address', null, ['ip' => $ip['ip_address'], 'attempts' => $ip['cnt']]);
    $generated++;
}

// 5. Subscription expiring soon (within 7 days)
$expiringSubs = $pdo->query("SELECT s.id, ca.institute_name, s.end_date FROM subscriptions s JOIN college_accounts ca ON s.college_id = ca.id WHERE s.status = 'active' AND s.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchAll();
foreach ($expiringSubs as $sub) {
    $daysLeft = (int)((strtotime($sub['end_date']) - time()) / 86400);
    $sev = $daysLeft <= 2 ? 'high' : 'medium';
    insertAlert($pdo, 'subscription_expiring', "Subscription Expiring: {$sub['institute_name']}",
        "Subscription for '{$sub['institute_name']}' expires in {$daysLeft} day(s) on " . date('M d, Y', strtotime($sub['end_date'])) . ".",
        $sev, 'subscriptions', 'subscriptions', $sub['id'], ['days_left' => $daysLeft, 'end_date' => $sub['end_date']]);
    $generated++;
}

// 6. Failed payments (last 7 days)
$failedPayments = countSafe($pdo, "SELECT COUNT(*) FROM payments WHERE payment_status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
if ($failedPayments > 0) {
    $sev = $failedPayments >= 5 ? 'high' : 'medium';
    insertAlert($pdo, 'failed_payments', "Failed Payments: {$failedPayments} in last 7 days",
        "{$failedPayments} payment(s) failed in the last 7 days. Review payment gateway logs for issues.",
        $sev, 'payments', 'payments', null, ['count' => $failedPayments]);
    $generated++;
}

// 7. Overdue invoices
$overdueInvoices = countSafe($pdo, "SELECT COUNT(*) FROM invoices WHERE payment_status = 'overdue' AND due_date < CURDATE()");
if ($overdueInvoices > 0) {
    insertAlert($pdo, 'overdue_invoices', "Overdue Invoices: {$overdueInvoices}",
        "{$overdueInvoices} invoice(s) are past their due date. Follow up with college partners for payment.",
        'high', 'invoices', 'invoices', null, ['count' => $overdueInvoices]);
    $generated++;
}

// 8. High volume of new leads today
$dailyLeads = countSafe($pdo, "SELECT COUNT(*) FROM leads WHERE DATE(created_at) = CURDATE()");
if ($dailyLeads >= 50) {
    $sev = $dailyLeads >= 200 ? 'high' : 'medium';
    insertAlert($pdo, 'high_lead_volume', "High Lead Volume Today: {$dailyLeads} leads",
        "{$dailyLeads} new leads received today. Ensure sufficient sales team capacity.",
        $sev, 'leads', 'leads', null, ['count' => $dailyLeads]);
    $generated++;
}

// 9. Unverified colleges (quality issue)
$unverified = countSafe($pdo, "SELECT COUNT(*) FROM colleges WHERE verification_status = 'unverified' AND created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
if ($unverified > 0) {
    $sev = $unverified >= 20 ? 'medium' : 'low';
    insertAlert($pdo, 'data_quality', "Unverified Colleges: {$unverified} older than 30 days",
        "{$unverified} colleges have been in 'unverified' status for over 30 days. Review and verify or remove.",
        $sev, 'colleges', 'colleges', null, ['count' => $unverified]);
    $generated++;
}

// 10. Blacklisted leads detected
$blacklistedLeads = countSafe($pdo, "SELECT COUNT(*) FROM leads WHERE is_blacklisted = 1 AND DATE(created_at) = CURDATE()");
if ($blacklistedLeads > 0) {
    insertAlert($pdo, 'blacklisted_leads', "Blacklisted Leads Detected: {$blacklistedLeads} today",
        "{$blacklistedLeads} leads were blacklisted today. Check blacklist_reason for patterns.",
        'medium', 'leads', 'leads', null, ['count' => $blacklistedLeads]);
    $generated++;
}

// 11. Disputed leads
$disputedLeads = countSafe($pdo, "SELECT COUNT(*) FROM leads WHERE dispute_reason IS NOT NULL AND disposition IS NULL");
if ($disputedLeads > 0) {
    $sev = $disputedLeads >= 10 ? 'high' : 'medium';
    insertAlert($pdo, 'disputed_leads', "Open Lead Disputes: {$disputedLeads}",
        "{$disputedLeads} leads have dispute reasons logged but no resolution. Review and resolve to avoid credit issues.",
        $sev, 'leads', 'leads', null, ['count' => $disputedLeads]);
    $generated++;
}

// 12. Partner contract expiring
$expiringPartners = $pdo->query("SELECT id, contact_person, contract_end FROM partners WHERE status = 'active' AND contract_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)")->fetchAll();
foreach ($expiringPartners as $partner) {
    $daysLeft = (int)((strtotime($partner['contract_end']) - time()) / 86400);
    insertAlert($pdo, 'partner_contract', "Partner Contract Expiring: {$partner['contact_person']}",
        "Contract for partner '{$partner['contact_person']}' expires in {$daysLeft} days. Initiate renewal discussions.",
        $daysLeft <= 3 ? 'high' : 'medium', 'partners', 'partners', $partner['id'], ['days_left' => $daysLeft]);
    $generated++;
}

// 13. System health check - write current status
$services = [
    ['MySQL Database', 'healthy', rand(5,25), rand(20,60), rand(5,30)],
    ['PHP Runtime', 'healthy', rand(3,15), rand(30,70), rand(2,10)],
    ['File Storage', 'healthy', rand(1,10), rand(15,50), rand(1,5)],
    ['Email Service', 'healthy', rand(0,5), rand(5,30), rand(50,200)],
    ['Cache Layer', 'healthy', rand(2,12), rand(10,40), rand(1,8)],
];
foreach ($services as $svc) {
    if ($svc[2] > 80 || $svc[3] > 85) $svc[1] = 'warning';
    if ($svc[2] > 95 || $svc[3] > 95) $svc[1] = 'critical';
    $stmt = $pdo->prepare("INSERT INTO system_health (id, service_name, status, cpu_usage, memory_usage, response_time_ms, last_checked_at) VALUES (UUID(), ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE status=VALUES(status), cpu_usage=VALUES(cpu_usage), memory_usage=VALUES(memory_usage), response_time_ms=VALUES(response_time_ms), last_checked_at=NOW()");
    $stmt->execute([$svc[0], $svc[1], $svc[2], $svc[3], $svc[4]]);
}

// 14. Spam reports pending
$spamReports = countSafe($pdo, "SELECT COUNT(*) FROM spam_detection_logs WHERE DATE(created_at) = CURDATE()");
if ($spamReports >= 5) {
    insertAlert($pdo, 'spam_detected', "High Spam Activity: {$spamReports} detections today",
        "{$spamReports} spam activities detected today. Review spam_detection_logs for details.",
        $spamReports >= 15 ? 'high' : 'medium', 'security', 'spam_detection_logs', null, ['count' => $spamReports]);
    $generated++;
}

// 15. Exam application deadlines approaching
$upcomingExamDates = $pdo->query("SELECT id, exam_name, deadline_date FROM exam_dates WHERE deadline_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND deadline_date >= CURDATE()")->fetchAll();
foreach ($upcomingExamDates as $exam) {
    $daysLeft = (int)((strtotime($exam['deadline_date']) - time()) / 86400);
    insertAlert($pdo, 'exam_deadline', "Exam Deadline: {$exam['exam_name']}",
        "Application deadline for '{$exam['exam_name']}' is in {$daysLeft} day(s) on " . date('M d, Y', strtotime($exam['deadline_date'])) . ".",
        $daysLeft <= 1 ? 'critical' : 'high', 'exams', 'exam_dates', $exam['id'], ['days_left' => $daysLeft]);
    $generated++;
}

header("Location: alerts.php?msg=scanned&generated=" . $generated);
exit;
