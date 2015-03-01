<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Define constants for file paths, url, etc.

define('DS', DIRECTORY_SEPARATOR);
$path_info = pathinfo(__FILE__);
$root = $path_info['dirname']; // php 5.3.0+

define('ROOT_PATH', $root);
define('CORE_PATH', ROOT_PATH.DS.'core');
define('WWW_PATH', ROOT_PATH);
define('APP_PATH', ROOT_PATH.DS.'app');
define('LIBRARY_PATH', ROOT_PATH.DS.'libraries');

spl_autoload_register(
    function ($class) {
        $class = strtolower(ltrim($class, '\\'));
        $subpath = '';
        $pos = strrpos($class, '\\');
        if ($pos !== false) {
            $ns = substr($class, 0, $pos);
            $subpath = str_replace('\\', DS, $ns) . DS;
            $class = substr($class, $pos + 1);
        }
        $subpath .= str_replace('_', DS, $class);
        $dir = ROOT_PATH;

        $file = $dir . DS . $subpath . '.php';

        if (file_exists($file)) {
            require $file;
        }

    }
);


$start = microtime(true);

if (!ob_start("ob_gzhandler")) ob_start();

$out = '';

try {

    $out = new core\App();

} catch(Exception $e){

    trigger_error($e->getMessage(), E_USER_ERROR);

}

// internal nonsense.. could be built into a debug class and sent with output.
$finish = microtime(true);
$debug = 'Total time spent: '.sprintf('%.6f',($finish-$start)).' seconds<br/>';
$debug .= 'Memory usage: '.number_format(((memory_get_usage()/1024)/1024),4,'.',',').'MB<br/>';
if($_SERVER['REMOTE_ADDR'] == MYIP) {
//$out .= $debug;
}
echo $out;

while (ob_get_level() > 0) {
    ob_end_flush();
}
