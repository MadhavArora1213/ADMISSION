<?php
$files = [
    'college_form.php' => 'university_form.php',
    'college_courses.php' => 'university_courses.php',
    'college_placements.php' => 'university_placements.php',
    'college_cutoffs.php' => 'university_cutoffs.php',
    'college_media.php' => 'university_media.php',
    'college_faqs.php' => 'university_faqs.php',
    'college_faculty.php' => 'university_faculty.php',
    'college_scholarships.php' => 'university_scholarships.php'
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
    } else {
        echo "Source file $src not found.\n";
    }
}
?>
