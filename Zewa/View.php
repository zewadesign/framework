<?php
namespace Zewa;

use Zewa\Interfaces\ContainerInterface;

/**
 * View management
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */
class View
{
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
     * \Zewa\Config reference
     *
     * @var Config
     */
    protected $configuration;

    /**
     * \Zewa\Router reference
     *
     * @var Router
     */
    protected $router;

    /**
     * \Zewa\Router reference
     *
     * @var Router
     */
    protected $request;

    /**
     * @var array
     */
    private $queuedJS = [];

    /**
     * @var array
     */
    private $queuedCSS = [];

    /**
     * Load up some basic configuration settings.
     */
    public function __construct(Config $config, Router $router, Request $request)
    {
        $this->configuration = $config;
        $this->router = $router;
        $this->request = $request;
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

    /*
     * @todo create method for returning
     * a valid json string with header..
     * view shouldn't set header logic,
     * and the framework doesn't care what returns the string
     * ..but view should handle the json_encode...
     * seems overkill to call header() with returning a $view->json;
     * thoughts?*/

    /**
     * Loads a view
     *
     * @access public
     * @param string|bool $view view to load
     * @param string|bool $layout
     * @return string
     */
    public function render($view = false, $layout = false)
    {
        if ($layout !== false) {
            $this->setLayout($layout);
        }

        if ($view !== false) {
            $view = $this->prepareView($view);

            $this->view = $return = $this->process($view);

            if (! is_null($this->layout)) {
                $return = $this->process($this->layout);
            }

            return $return;
        } else {
            if ($this->view !== false) {
                $this->view = $this->process($this->view);
            }

            if (! is_null($this->layout)) {
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
        if ($layout !== false) {
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
            $routerConfig = $this->router->getConfig()->get('Routing');
            $this->module = $routerConfig->module;
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
        $string = "";

        if (empty($this->queuedCSS)) {
            return $string;
        }

        foreach ($this->queuedCSS as $sheet) {
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
        $string = "<script>baseURL = '" . $this->baseURL() . "/'</script>\r\n";

        if (empty($this->queuedJS)) {
            return $string;
        }

        foreach ($this->queuedJS as $script) {
            $string .= '<script src="' . $script . '"></script>' . "\r\n";
        }

        return $string;
    }

    /**
     * Helper method for adding css files for aggregation/render
     *
     * @access public
     * @param $files array
     * @param $place string
     * @return string css includes
     * @throws Exception\LookupException
     */
    public function addCSS($files = [], $place = 'append')
    {
        if ($place === 'append') {
            $this->queuedCSS = array_merge($files, $this->queuedCSS);
        } else {
            $this->queuedCSS = array_merge($this->queuedCSS, $files);
        }
    }

    public function addJS($files = [], $place = 'append')
    {
        if ($place === 'append') {
            $this->queuedJS = array_merge($files, $this->queuedJS);
        } else {
            $this->queuedJS = array_merge($this->queuedJS, $files);
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
