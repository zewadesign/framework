<?php
/**
 * @file autoloader.php
 * @project zechframework
 * @author Josh Houghtelin <josh@findsomehelp.com>
 * @created 3/7/15 1:44 PM
 */

spl_autoload_register(
    function ($class) {
        $class = strtolower(ltrim($class, '\\'));
        $subpath = '';
        $pos = strrpos($class, '\\');
        if ($pos !== false) {
            $ns = substr($class, 0, $pos);
            $subpath = str_replace('\\', DIRECTORY_SEPARATOR, $ns) . DS;
            $class = substr($class, $pos + 1);
        }
        $subpath .= str_replace('_', DIRECTORY_SEPARATOR, $class);

        $file = __DIR__ . DIRECTORY_SEPARATOR . $subpath . '.php';

        if (file_exists($file)) {
            require $file;
        }

    }
);
