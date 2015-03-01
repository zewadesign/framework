<?php

namespace core;
use app\modules as modules;

//can name spaces be removed in the classes extending..?
class Controller {

    protected $load;
    protected $router;
    protected $request;
    protected $output;
    protected $validate;
    protected $data;
    public static $instance;

    function __construct() {

        $this->router = Registry::get('_router');
        $this->load = Registry::get('_loader');
        $this->request = Registry::get('_request');
        $this->output = Registry::get('_output');
        $this->validate = Registry::get('_validate');

        //@TODO: make $this->license

        $this->data = array();


        if(Registry::get('_acl')) {


        }

    }

    public static function &getInstance() {

        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;

    }

}