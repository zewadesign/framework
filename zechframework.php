<?php
require __DIR__ . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants for file paths, url, etc.
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH.DS.'app');

if (!ob_start("ob_gzhandler")) ob_start();

$out = new \core\App();

print $out;

while (ob_get_level() > 0) {
    ob_end_flush();
}
