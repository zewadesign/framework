<?php

namespace app\modules\example\controllers;
use \core as core;

Class Home extends core\Controller {

    public $data;

    public function __construct() {

        parent::__construct();

        $this->data = [];

    }

    public function index() {


        $layout = $this->load->view(
            'example/home',
            $this->data
        );
        //view takes an optional third parameter,
        //which is the relative path to the preferred layout
        //default is "layout" within the active module layout directory. (layouts/activemodule/layout) ,

        return $layout;

    }

    private function hello($name) {
        return "Hello " .$name;
    }

    public function usages($usage = false) {

        $usage = strtolower($usage);

        switch($usage) {
            case 'invokeowncontroller':

                $homeController = $this->load->controller('example','home');
                /*
                 * optional parameters for constructor can be passed
                 * e.g: $this->load->controllers('example','home',['1','2','3']);
                 * if class is created, but parameters are provided, class will be instantiated with arguments
                 * if class is created, and no parameters are provided, class will be an instance
                 * if class is not created, class will be created.
                 *
                 **/
                return $homeController->hello('Zech');

            break;
            case 'invokefriendlycontroller':

                $ajaxController = $this->load->controller('example','ajax');

                /*
                 * $ajaxController->publicMethod() is available....
                 * $ajaxController->privateMethod() is not available.
                 */

                return $ajaxController->publicMethod();

            break;
            default:
                return $this->index();
            break;
        }
    }

}
