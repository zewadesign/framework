<?php
namespace core;

use app\modules as modules;
//can name spaces be removed in the classes extending..?
class Model {

//    public static $instance;
    protected $database;
    protected $load;
    protected $request;
    protected $cache;

    public function __construct() {

        $this->database = Registry::get('_database');
        $this->load = Registry::get('_loader');
        $this->request = Registry::get('_request');

    }

//    public static function &getInstance() {
//
//        if (static::$instance === null) {
//            static::$instance = new static();
//        }
//
//        return static::$instance;
//
//    }

}