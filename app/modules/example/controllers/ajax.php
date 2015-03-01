<?php

namespace app\modules\example\controllers;
use \core as core;

Class Ajax extends core\Controller {

    public $data;

    public function __construct() {

        parent::__construct();

        $this->data = [];

    }

    private function privateMethod() {

        return json_encode(['access' => 'private', 'name' => 'Zechariah Walden', 'email' => 'zech@zewadesign.com']);

    }

    public function publicMethod() {

        return json_encode(['access' => 'public', 'name' => 'Zechariah Walden', 'email' => 'zech@zewadesign.com']);

    }
}
