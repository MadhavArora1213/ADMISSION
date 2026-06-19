<?php
$html = file_get_contents('http://localhost/ADMISSION/ADMISSION/colleges');
preg_match_all('/href="([^"]+)"/', $html, $matches);
foreach ($matches[1] as $href) {
    if (strpos($href, 'iit-bombay') !== false) {
        echo $href . "\n";
    }
}
