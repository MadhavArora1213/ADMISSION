<?php
require 'db.php';

$checks = [];

// Tables to check
$tables = ['search_indices', 'search_queries', 'search_suggestions', 'search_synonyms', 'search_trending'];

foreach($tables as $t) {
    $r = $pdo->query("SHOW TABLES LIKE '$t'")->rowCount();
    $checks["TABLE: $t"] = $r > 0 ? '✅' : '❌ MISSING';
}

// Columns to check
$columns = [
    'search_indices' => ['index_name', 'entity_type', 'indexed_at', 'document_count', 'search_weight_config', 'facets_config', 'stop_words', 'language'],
    'search_queries' => ['query_text', 'results_count', 'clicked_result_id', 'clicked_type', 'session_id', 'user_id', 'zero_results', 'device_type', 'filters_applied', 'search_timestamp'],
    'search_suggestions' => ['suggestion_text', 'suggestion_type', 'frequency', 'is_active'],
    'search_synonyms' => ['canonical', 'synonyms'],
    'search_trending' => ['query_text', 'trending_score', 'trending_period']
];

foreach ($columns as $table => $cols) {
    foreach ($cols as $col) {
        $r = $pdo->query("SHOW COLUMNS FROM $table LIKE '$col'")->rowCount();
        $checks["  $table.$col"] = $r > 0 ? '✅' : '❌ MISSING';
    }
}

echo "<pre style='font-family:monospace; font-size:14px; line-height:1.8;'>";
echo "=== SEARCH & FILTERS ENGINE — VERIFICATION ===\n\n";
$missing = 0;
foreach($checks as $label => $status) {
    echo str_pad($label, 45) . " $status\n";
    if(strpos($status,'❌') !== false) $missing++;
}
echo "\n";
echo $missing === 0 
    ? "✅ ALL CHECKS PASSED — Search Module fully implemented in DB!\n"
    : "❌ $missing item(s) MISSING — Needs fixing!\n";
echo "</pre>";
?>
