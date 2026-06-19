<?php
$c = file_get_contents('http://localhost/ADMISSION/ADMISSION/course/mba');
preg_match_all('/<div class="info-card-content">\s*(.*?)\s*<\/div>/s', $c, $matches);
print_r($matches[1]);
