<?php

namespace app\modules\user\controllers;
use \core as core;

Class Account extends core\Controller {

    private $user;
    private $role;
    public $data;

    public function __construct() {

        parent::__construct();

        $this->user = $this->load->model('user');
        $this->role = $this->load->model('role');
        $this->data = array();

    }

    public function home() {

        $layout = $this->load->view(
            'layout',
            'example/home',
            $this->data
        );

        return $layout;

    }

}
