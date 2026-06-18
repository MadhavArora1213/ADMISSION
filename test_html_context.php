<?php
$html = file_get_contents('http://localhost/ADMISSION/ADMISSION/colleges');
$pos = strpos($html, 'college.php?');
if ($pos !== false) {
    echo substr($html, max(0, $pos - 100), 200);
}
