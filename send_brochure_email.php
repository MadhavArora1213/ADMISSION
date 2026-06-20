<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/phone_email_config.php';
require_once __DIR__ . '/includes/college_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Please login first.', 'redirect' => 'login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '/')]);
    exit;
}

$college_id = trim($_POST['college_id'] ?? '');
if ($college_id === '') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid college.']);
    exit;
}

$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || empty($user['email'])) {
    echo json_encode(['ok' => false, 'msg' => 'No email found for your account.']);
    exit;
}

try {
    $s = $pdo->prepare("SELECT * FROM colleges WHERE id = ? LIMIT 1");
    $s->execute([$college_id]);
    $college = $s->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $college = null;
}
if (!$college) {
    echo json_encode(['ok' => false, 'msg' => 'College not found.']);
    exit;
}

$collegeName = $college['name'];
$collegeSlug = $college['slug'] ?? '';
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/ADMISSION';
$collegeUrl = $baseUrl . '/college/' . htmlspecialchars($collegeSlug);

$brochureUrl = '';
$stmt = $pdo->prepare("SELECT document_url FROM college_media WHERE college_id = ? AND document_type = 'brochure' AND document_url IS NOT NULL ORDER BY sort_order ASC LIMIT 1");
$stmt->execute([$college_id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if ($doc) $brochureUrl = $doc['document_url'];

if (!$brochureUrl) {
    echo json_encode(['ok' => false, 'msg' => 'No brochure available for this college.']);
    exit;
}

if (!str_starts_with($brochureUrl, 'http')) {
    $brochureUrl = $baseUrl . '/' . ltrim($brochureUrl, '/');
}

$userName = htmlspecialchars($user['full_name'] ?? 'Student');
$emailTo = $user['email'];

$htmlEmail = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:40px 16px">
<tr><td align="center">
<table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04),0 8px 32px rgba(0,0,0,0.06)">

  <tr>
    <td style="background:#0B2447;padding:40px 48px 36px;text-align:center">
      <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.45);margin-bottom:12px">AdmissionSeason</div>
      <h1 style="margin:0;color:#fff;font-size:24px;font-weight:800;line-height:1.3">College Brochure</h1>
      <div style="width:40px;height:2px;background:rgba(255,255,255,0.25);margin:16px auto;border-radius:1px"></div>
      <p style="margin:0;color:rgba(255,255,255,0.65);font-size:14px;font-weight:500">{$collegeName}</p>
    </td>
  </tr>

  <tr>
    <td style="padding:32px 48px 8px">
      <p style="margin:0;font-size:15px;color:#0f172a">Hello <strong>{$userName}</strong>,</p>
      <p style="margin:10px 0 0;font-size:14px;color:#64748b;line-height:1.6">Here is the official brochure for <strong style="color:#0f172a">{$collegeName}</strong> as requested.</p>
    </td>
  </tr>

  <tr>
    <td style="padding:20px 48px 32px;text-align:center">
      <a href="{$brochureUrl}" style="display:inline-block;background:#0B2447;color:#fff;padding:14px 40px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;letter-spacing:0.3px">Download Brochure</a>
      <p style="margin:14px 0 0;font-size:12px;color:#94a3b8">Click the button above to download the PDF</p>
    </td>
  </tr>

  <tr>
    <td style="padding:0 48px 32px;text-align:center">
      <a href="{$collegeUrl}" style="display:inline-block;background:#fff;color:#0B2447;padding:14px 40px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;border:2px solid #0B2447;letter-spacing:0.3px">View Complete Details</a>
      <p style="margin:14px 0 0;font-size:12px;color:#94a3b8">Explore fees, placements, cutoffs and more</p>
    </td>
  </tr>

  <tr>
    <td style="background:#f8fafc;padding:24px 48px;text-align:center;border-top:1px solid #f1f5f9">
      <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.6">
        Sent by <strong style="color:#64748b">AdmissionSeason</strong>
        <span style="margin:0 6px;color:#cbd5e1">|</span>
        <a href="{$baseUrl}" style="color:#19376D;text-decoration:none;font-weight:600">admissionseason.com</a>
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;

$apiKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? '');
$senderEmail = getenv('BREVO_SENDER_EMAIL') ?: ($_ENV['BREVO_SENDER_EMAIL'] ?? '');
$senderName = getenv('BREVO_SENDER_NAME') ?: ($_ENV['BREVO_SENDER_NAME'] ?? 'AdmissionSeason');

if (empty($apiKey)) {
    echo json_encode(['ok' => false, 'msg' => 'Email service not configured.']);
    exit;
}

$payload = [
    'sender' => ['email' => $senderEmail, 'name' => $senderName],
    'to' => [['email' => $emailTo, 'name' => $userName]],
    'subject' => "{$collegeName} - Official Brochure",
    'htmlContent' => $htmlEmail,
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
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['ok' => false, 'msg' => 'Email service connection failed' . ($curlError ? ': ' . $curlError : '') . '.']);
    exit;
}

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['ok' => true, 'msg' => "Brochure for {$collegeName} has been emailed to {$emailTo}.", 'email' => $emailTo, 'college_id' => $college_id, 'college_name' => $collegeName, 'college_slug' => $collegeSlug]);
} else {
    $err = json_decode($response, true);
    $errMsg = $err['message'] ?? ('Failed to send email (HTTP ' . $httpCode . ').');
    echo json_encode(['ok' => false, 'msg' => $errMsg]);
}
