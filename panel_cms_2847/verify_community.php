<?php
require 'db.php';

$checks = [];
$tables = ['questions', 'answers', 'experts', 'qa_reports'];

foreach($tables as $t) {
    $r = $pdo->query("SHOW TABLES LIKE '$t'")->rowCount();
    $checks["TABLE: $t"] = $r > 0 ? '✅' : '❌ MISSING';
}

$columns = [
    'questions' => ['question_text', 'question_category', 'related_college_id', 'related_exam_id', 'related_course_id', 'asked_by', 'views', 'answer_count', 'is_featured', 'status', 'trending_score'],
    'answers' => ['question_id', 'answer_text', 'answered_by', 'is_expert_answer', 'is_verified_alumnus', 'upvotes', 'is_accepted'],
    'experts' => ['expert_name', 'expert_designation', 'expert_college', 'verified_badge', 'answer_count', 'profile_url', 'specialization', 'linkedin_url', 'response_rate_pct', 'avg_response_hours'],
    'qa_reports' => ['question_id', 'answer_id', 'report_reason', 'reported_by', 'moderation_action']
];

foreach ($columns as $table => $cols) {
    foreach ($cols as $col) {
        $r = $pdo->query("SHOW COLUMNS FROM $table LIKE '$col'")->rowCount();
        $checks["  $table.$col"] = $r > 0 ? '✅' : '❌ MISSING';
    }
}

echo "<pre style='font-family:monospace; font-size:14px; line-height:1.8;'>";
echo "=== COMMUNITY & Q&A SYSTEM — VERIFICATION ===\n\n";
$missing = 0;
foreach($checks as $label => $status) {
    echo str_pad($label, 45) . " $status\n";
    if(strpos($status,'❌') !== false) $missing++;
}
echo "\n";
echo $missing === 0 
    ? "✅ ALL CHECKS PASSED — Community Module fully implemented in DB!\n"
    : "❌ $missing item(s) MISSING — Needs fixing!\n";
echo "</pre>";
?>
