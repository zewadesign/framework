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
    protected $layout;

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
        $app = App::getInstance();
        $this->configuration = $app->getConfiguration();
        $this->request = $app->getService('request');
        $this->router = $app->getService('router');
    }

    /**
     * Returns base URL for app
     * @return string
     */
    private function baseURL($path = '')
    {
        return $this->router->baseURL($path);
    }

    /**
     * Returns the current request URL
     * @return string
     */
    private function currentURL($params = false)
    {
        return $this->router->currentURL($params);
    }

    /**
     * Returns uri string
     * @return string
     */
    private function currentURI()
    {
        return $this->router->uri;
    }

    /**
     * Loads a view
     *
     * @access public
     *
     * @param string|bool $view view to load
     *
     * @return string
     */
    public function render($view = false)
    {
        if($view !== false) {
            $view = $this->prepareView($view);
            return $this->process($view);
        } else {
            if ($this->view !== false) {
                $this->view = $this->process($this->view);
            }

            if (! is_null($this->layout) ) {
                return $this->process($this->layout);
            } else {
                return $this->view;
            }
        }
    }

    /**
     * formats and prepares view for inclusion
     * @param $viewName
     * @return string
     * @throws Exception\LookupException
     */
    private function prepareView($viewName)
    {
        if ($this->module === false) {
            $this->setModule();
        }

        $view = APP_PATH
            . DIRECTORY_SEPARATOR
            . 'Modules'
            . DIRECTORY_SEPARATOR
            . $this->module
            . DIRECTORY_SEPARATOR
            . 'Views'
            . DIRECTORY_SEPARATOR
            . strtolower($viewName)
            . '.php';

        if (!file_exists($view)) {
            throw new Exception\LookupException('View: "' . $view . '" could not be found.');
        }

        return $view;
    }

    public function setView($viewName, $layout = false)
    {
        if( $layout !== false ) {
            $this->setLayout($layout);
        }
        $this->view = $this->prepareView($viewName);
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

    /**
     * Set the module for view look
     *
     * @access public
     * @param string|bool $module module to override
     */
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
     * @param string $file file being rendered
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

        include $file;

        $return = ob_get_contents();

        ob_end_clean();

        return $return;
    }

    /**
     * Helper method for grabbing aggregated css files
     *
     * @access protected
     * @return string css includes
     */
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

    /**
     * Helper method for grabbing aggregated JS files
     *
     * @access protected
     * @return string JS includes
     */
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

    /**
     * Helper method for adding css files for aggregation/render
     *
     * @access public
     * @param $sheets array
     * @param $place string
     * @return string css includes
     * @throws Exception\LookupException
     */
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
     * Set 404 header, and return 404 view contents
     *
     * @access public
     * @param  $data array
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
