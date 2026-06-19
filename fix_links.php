<?php
require_once 'admin/db.php';
$mba = $pdo->query('SELECT id FROM courses WHERE course_slug="mba"')->fetchColumn();
$mbbs = $pdo->query('SELECT id FROM courses WHERE course_slug="mbbs"')->fetchColumn();
$regtbeg = $pdo->query('SELECT id FROM courses WHERE course_slug="regtbeg"')->fetchColumn();

if($mba) {
    $pdo->exec("UPDATE course_specializations SET parent_course_id='$mba' WHERE parent_course_id='crs-mba-02'");
    $pdo->exec("UPDATE course_career_paths SET course_id='$mba' WHERE course_id='crs-mba-02'");
}
if($mbbs) {
    $pdo->exec("UPDATE course_specializations SET parent_course_id='$mbbs' WHERE parent_course_id='crs-mbbs-03'");
    $pdo->exec("UPDATE course_career_paths SET course_id='$mbbs' WHERE course_id='crs-mbbs-03'");
}
if($regtbeg) {
    $pdo->exec("UPDATE course_specializations SET parent_course_id='$regtbeg' WHERE parent_course_id='crs-bca-04'");
    $pdo->exec("UPDATE course_career_paths SET course_id='$regtbeg' WHERE course_id='crs-bca-04'");
}
echo 'Done linking';
