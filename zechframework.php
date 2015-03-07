<?php
// Composer Autoloader
require __DIR__ . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');

if (!ob_start("ob_gzhandler")) {
    ob_start();
}

$out = new \core\App();

print $out;

while (ob_get_level() > 0) {
    ob_end_flush();
}
