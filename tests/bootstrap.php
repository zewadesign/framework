<?php

// Register Composer Auto Loader
require __DIR__ . "/../vendor/autoload.php";

// Register Globals
define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public');

$_SERVER['HTTP_HOST'] = 'test.zewa.com';
$_SERVER['PHP_SELF'] = "index.php";
$_SERVER['REQUEST_METHOD'] = "GET";

// Create Instance of App
//$app = new \Zewa\App();
