<?php
require __DIR__ . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants for file paths, url, etc.
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', __DIR__);
//define('CORE_PATH', ROOT_PATH.DS.'core');
//define('WWW_PATH', ROOT_PATH);
define('APP_PATH', ROOT_PATH.DS.'app');
//define('LIBRARY_PATH', ROOT_PATH.DS.'libraries');

if (!ob_start("ob_gzhandler")) ob_start();

$out = new \core\App();

print $out;

while (ob_get_level() > 0) {
    ob_end_flush();
}
