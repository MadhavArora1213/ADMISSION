<?php
$files = [
    'colleges.php' => 'universities.php'
];

$replacements = [
    '$allColleges' => '$allUniversities',
    'allColleges' => 'allUniversities',
    'colleges' => 'universities',
    'Colleges' => 'Universities',
    'college' => 'university',
    'College' => 'University'
];

foreach ($files as $src => $dest) {
    if (file_exists($src)) {
        $content = file_get_contents($src);
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        file_put_contents($dest, $content);
        echo "Created $dest\n";
    }
}
?>
