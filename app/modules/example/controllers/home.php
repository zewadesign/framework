<?php

namespace app\modules\example\controllers;
use \core as core;

Class Home extends core\Controller {

    public $data;

    public function __construct() {

        parent::__construct();

        $this->data = array();

    }

    public function index() {


        $layout = $this->load->view(
            'example/layout',
            'example/home',
            $this->data
        );

        return $layout;

    }

}
