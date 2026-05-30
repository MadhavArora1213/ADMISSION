<?php
require 'db.php';

$checks = [];

// Tables to check
$tables = ['notification_templates', 'notification_campaigns', 'audience_segments', 'notification_logs'];

foreach($tables as $t) {
    $r = $pdo->query("SHOW TABLES LIKE '$t'")->rowCount();
    $checks["TABLE: $t"] = $r > 0 ? '✅' : '❌ MISSING';
}

// Columns to check
$columns = [
    'notification_templates' => ['template_name', 'channel', 'subject', 'body_html', 'body_text', 'variables_json', 'language', 'is_active', 'category'],
    'notification_campaigns' => ['campaign_name', 'template_id', 'audience_segment_id', 'scheduled_at', 'sent_count', 'delivered_count', 'opened_count', 'clicked_count', 'unsubscribed_count', 'failed_count', 'status'],
    'audience_segments' => ['segment_name', 'filters_json', 'user_count', 'refresh_schedule'],
    'notification_logs' => ['user_id', 'channel', 'status', 'error_message', 'sent_at']
];

foreach ($columns as $table => $cols) {
    foreach ($cols as $col) {
        $r = $pdo->query("SHOW COLUMNS FROM $table LIKE '$col'")->rowCount();
        $checks["  $table.$col"] = $r > 0 ? '✅' : '❌ MISSING';
    }
}

echo "<pre style='font-family:monospace; font-size:14px; line-height:1.8;'>";
echo "=== NOTIFICATIONS SYSTEM — VERIFICATION ===\n\n";
$missing = 0;
foreach($checks as $label => $status) {
    echo str_pad($label, 45) . " $status\n";
    if(strpos($status,'❌') !== false) $missing++;
}
echo "\n";
echo $missing === 0 
    ? "✅ ALL CHECKS PASSED — Notifications Module fully implemented in DB!\n"
    : "❌ $missing item(s) MISSING — Needs fixing!\n";
echo "</pre>";
?>
