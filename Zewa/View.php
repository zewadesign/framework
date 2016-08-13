<?php
namespace Zewa;

/**
 * Abstract class for model extension
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class View
{
    /**
     * System configuration
     *
     * @var object
     */
    protected $configuration;

    /**
     * Instantiated request class pointer
     *
     * @var object
     */
    protected $request;

    /**
     * Active layout for view
     *
     * @var string|bool
     */
    protected $layout = false;

    /**
     * Active module for view
     *
     * @var string|bool
     */
    protected $module = false;

    /**
     * Rendered view content
     *
     * @var string
     */
    protected $view = false;

    /**
     * Data object for view
     *
     * @var object
     */
    protected $properties;

    /**
     * Router object for view injection
     *
     * @var object
     */
    protected $router;

    /**
     * Load up some basic configuration settings.
     */
    public function __construct()
    {
        // This abstract is strictly to establish inheritance from a global registery.
        $app = App::getInstance();
        $layouts = $app->getConfiguration('layouts');
        $this->configuration = $app->getConfiguration();
        $this->request = $app->getService('request');
        $this->router = $app->getService('router');
    }

    private function baseURL($path = '')
    {
        return $this->router->baseURL($path);
    }

    private function currentURL($params = false)
    {
        return $this->router->currentURL($params);
    }


    private function currentURI()
    {
        return $this->router->uri;
    }
    /**
     * Loads a view
     *
     * @access public
     *
     * @param string $requestedView relative path for the view
     * @param string $renderName array of data to expose to view
     *
     * @throws \Exception when a view can not be found
     */
    public function render()
    {
        if ($this->view !== false) {
            $this->view = $this->process($this->view);
        }
        if ($this->layout === false) {
            $this->setLayout($this->configuration->layouts->default);
        }

        if (is_null($this->layout)) {
            return $this->view;
        } else {
            return $this->process($this->layout);
        }
    }

    public function setView($requestedView)
    {

        if ($this->module === false) {
            $this->module = $this->configuration->router->module;
        }

        $view = APP_PATH
                . DIRECTORY_SEPARATOR
                . 'Modules'
                . DIRECTORY_SEPARATOR
                . $this->module
                . DIRECTORY_SEPARATOR
                . 'Views'
                . DIRECTORY_SEPARATOR
                . strtolower($requestedView)
                . '.php';

        if (!file_exists($view)) {
            throw new Exception\LookupException('View: "' . $view . '" could not be found.');
        }
        $this->view = $view;
    }

    public function setProperty($property, $value = false)
    {
        if ($value !== false) {
            $this->properties[$property] = $value;
        } elseif (!empty($property)) {
            $this->properties = $property;
        }
        return false;
    }

    public function setLayout($layout)
    {

        if ($layout === false) {
            $this->layout = null;
        } else {
            $layout = APP_PATH . DIRECTORY_SEPARATOR . 'Layouts' . DIRECTORY_SEPARATOR . strtolower($layout) . '.php';

            if (!file_exists($layout)) {
                throw new Exception\LookupException('Layout: "' . $layout . '" could not be found.');
            }

            $this->layout = $layout;

            return true;
        }
    }

    public function setModule($module = false)
    {
        if ($module === false) {
            $this->module = $this->configuration->router->module;
        } else {
            $this->module = ucfirst($module);
        }
    }
    /**
     * Processes view/layouts and exposes variables to the view/layout
     *
     * @access private
     *
     * @param string $file file being rendered
     *
     * @return string processed content
     */
    //@TODO: come back and clean up this and the way the view receives stuff
    private function process($file)
    {
        ob_start();

        if (is_array($this->properties)) {
            extract($this->properties); // yuck. could produce undeclared errors. hmm..
        }
        //should i set $this->data in abstract controller, and provide all access vars ? seems bad practice..

        include($file);

        $return = ob_get_contents();

        ob_end_clean();

        return $return;
    }

//    private function verifyResource($resource) {
//
//        $path = PUBLIC_PATH . DIRECTORY_SEPARATOR . $resource;
//
//        if (!file_exists($path)) {
//            return false;
//        }
//
//        return true;
//    }

    protected function fetchCSS()
    {
        $app = App::getInstance();
        $sheets = $app->getConfiguration('view::css');

        $string = "";

        if (empty($sheets)) {
            return $string;
        }

        foreach ($sheets as $sheet) {
            $string .= '<link rel="stylesheet" href="' . $sheet .'">' . "\r\n";
        }

        return $string;
    }

    protected function fetchJS()
    {

        $app = App::getInstance();
        $scripts = $app->getConfiguration('view::js');
        $string = "<script>baseURL = '" . $this->baseURL() . "/'</script>";

        if (empty($scripts)) {
            return $string;
        }

        foreach ($scripts as $script) {
            $string .= '<script src="' . $script . '"></script>' . "\r\n";
        }

        return $string;
    }

    public function addCSS($sheets = [], $place = 'append')
    {
        $app = App::getInstance();
        $existingCSS = $app->getConfiguration('view::css');

        if ($existingCSS === false) {
            $existingCSS = [];
        } else {
            $existingCSS = (array)$existingCSS;
        }
        if (empty($sheets)) {
            throw new Exception\LookupException('You must provide a valid CSS Resource.');
        }

        $files = [];

        foreach ($sheets as $file) {
            $files[] = $file;
//            if ($this->verifyResource($file)) {
//            } else {
//                throw new Exception\LookupException('The CSS Resource you\'ve specified does not exist.');
//            }
        }

        if ($place === 'prepend') {
            $existingCSS = array_merge($files, $existingCSS);
        } else {
            $existingCSS = array_merge($existingCSS, $files);
        }

        $app = App::getInstance();
        $app->setConfiguration('view::css', (object)$existingCSS);
    }

    public function addJS($scripts = [], $place = 'append')
    {

        $app = App::getInstance();
        $existingJS = $app->getConfiguration('view::js');

        if ($existingJS === false) {
            $existingJS = [];
        } else {
            $existingJS = (array)$existingJS;
        }

        if (!empty($scripts)) {
            $files = [];

            foreach ($scripts as $file) {
                $files[] = $file;
//                if ($this->verifyResource($file)) {
//                } else {
//                    throw new Exception\LookupException('The JS Resource you\'ve specified does not exist: ' . $file);
//                }
            }

            if ($place === 'prepend') {
                $existingJS = array_merge($files, $existingJS);
            } else {
                $existingJS = array_merge($existingJS, $files);
            }

            $app = App::getInstance();
            $app->setConfiguration('view::js', (object)$existingJS);
        }
    }


    /**
     * Set 401 header, and return noaccess view contents
     *
     * @access public
     * @return string
     */
    public function renderNoAccess($data)
    {
        header('HTTP/1.1 401 Access Denied');
        $this->setProperty($data);
        $this->setLayout('no-access');
        return $this->render();
    }

    /**
     * Set 404 header, and return 404 view contents
     *
     * @access public
     * @param $module string
     * @param $data array
     * @return string
     */
    public function render404($data = [])
    {
        header('HTTP/1.1 404 Not Found');
        $this->setProperty($data);
        $this->setLayout('404');
        return $this->render();
    }
}
