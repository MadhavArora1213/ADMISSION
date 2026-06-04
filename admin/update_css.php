<?php
$files = [
    'c:\xampp\htdocs\ADMISSION\admin\universities.php',
    'c:\xampp\htdocs\ADMISSION\admin\university_courses.php',
    'c:\xampp\htdocs\ADMISSION\admin\university_placements.php',
    'c:\xampp\htdocs\ADMISSION\admin\university_form.php',
    'c:\xampp\htdocs\ADMISSION\admin\colleges.php',
    'c:\xampp\htdocs\ADMISSION\admin\college_courses.php',
    'c:\xampp\htdocs\ADMISSION\admin\college_cutoffs.php',
    'c:\xampp\htdocs\ADMISSION\admin\college_faculty.php',
    'c:\xampp\htdocs\ADMISSION\admin\college_faqs.php',
    'c:\xampp\htdocs\ADMISSION\admin\college_form.php',
    'c:\xampp\htdocs\ADMISSION\admin\college_media.php',
    'c:\xampp\htdocs\ADMISSION\admin\college_placements.php',
    'c:\xampp\htdocs\ADMISSION\admin\college_scholarships.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Replace .main-content
        $content = preg_replace(
            '/\.main-content\s*\{\s*flex:\s*1;\s*margin-left:\s*280px;\s*display:\s*flex;\s*flex-direction:\s*column;\s*\}/', 
            '.main-content { flex: 1; margin-left: 280px; max-width: calc(100% - 280px); display: flex; flex-direction: column; }', 
            $content
        );
        
        // Replace .form-grid with media query
        $content = preg_replace(
            '/\.form-grid\s*\{\s*display:\s*grid;\s*grid-template-columns:\s*1fr\s+1fr;\s*gap:\s*16px;\s*\}\s*@media\s*\(\s*max-width:\s*768px\s*\)\s*\{\s*\.form-grid\s*\{\s*grid-template-columns:\s*1fr;\s*\}\s*\}/', 
            '.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }', 
            $content
        );

        // Replace .form-grid without media query
        $content = preg_replace(
            '/\.form-grid\s*\{\s*display:\s*grid;\s*grid-template-columns:\s*1fr\s+1fr;\s*gap:\s*16px;\s*\}/', 
            '.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }', 
            $content
        );

        file_put_contents($file, $content);
        echo "Updated $file\n";
    } else {
        echo "Not found $file\n";
    }
}
?>
