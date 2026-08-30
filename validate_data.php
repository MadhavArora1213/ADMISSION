<?php
$json = file_get_contents(__DIR__ . '/data/punjab_haryana_hp_chandigarh.json');
$data = json_decode($json);
if ($data === null) {
    echo "INVALID JSON: " . json_last_error_msg() . "\n";
    exit(1);
}
echo "VALID JSON\n";
echo "Universities: " . count($data->universities) . "\n";
echo "Colleges: " . count($data->colleges) . "\n";

$states = [];
foreach ($data->universities as $u) {
    $s = $u->core->state_name;
    $states[$s] = ($states[$s] ?? 0) + 1;
}
echo "\nUniversities by state:\n";
foreach ($states as $s => $c) echo "  $s: $c\n";

$colStates = [];
foreach ($data->colleges as $c) {
    $s = $c->core->state_name;
    $colStates[$s] = ($colStates[$s] ?? 0) + 1;
}
echo "\nColleges by state:\n";
foreach ($colStates as $s => $c) echo "  $s: $c\n";
