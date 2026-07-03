<?php
/**
 * Site Configuration
 * Auto-detects base URL for localhost vs hosted environment.
 */

$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$appDir  = realpath(__DIR__);
define('BASE_URL', ($docRoot && $appDir && $docRoot === $appDir) ? '' : '/ADMISSION');
