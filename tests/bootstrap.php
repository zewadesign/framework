<?php

// Register Composer Auto Loader
require __DIR__ . "/../vendor/autoload.php";

// Register Globals
define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR . "fixtures");
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public');

$_SERVER['HTTP_HOST'] = 'zewa.test';

// Create Instance of App
$app = new \Zewa\App();