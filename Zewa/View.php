<?php
namespace Zewa;

use Zewa\HTTP\Request;

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

    private $pathToView;

    private $pathToLayout;

    /** @var  array */
    protected $viewQueue;

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

    /** @var Container  */
    protected $container;

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
    public function __construct(Config $config, Router $router, Request $request, Container $container)
    {
        $this->configuration = $config->get('view');
        $this->router = $router;
        $this->request = $request;
        $this->container = $container;

        $this->pathToView = $this->configuration['viewPath'];
        $this->pathToLayout = $this->configuration['layoutPath'];
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
            $this->setView($view);
        }

        return $this->bufferResponse();
    }

    /**
     * formats and prepares view for inclusion
     * @param $viewName
     * @return string
     * @throws Exception\LookupException
     */
    public function setView($viewName)
    {
        $view = $this->pathToView . DIRECTORY_SEPARATOR . strtolower($viewName) . '.php';

        if (!file_exists($view)) {
            throw new Exception\LookupException('View: "' . $view . '" could not be found.');
        }

        $this->viewQueue[$viewName] = $view;
    }

    public function getView($view = null)
    {
        if ($view !== null) {
            return $this->viewQueue[$view];
        } else {
            return $this->viewQueue;
        }
    }

    public function setProperty(string $key, $value)
    {
        $container = $this->container->has('view_properties') ? $this->container->get('view_properties') : [];
        $container[$key] = $value;
        $this->container->set('view_properties', $container);
    }

    public function getProperty(string $key = null, $default = null)
    {
        $container = $this->container->has('view_properties') ? $this->container->get('view_properties') : [];

        if ($key === null) {
            return $container;
        }

        return $this->container->get('view_properties')[$key] ?? $default;
    }

    public function unsetProperty(string $key)
    {
        $container = $this->container->has('view_properties') ? $this->container->get('view_properties') : [];
        if (!empty($container[$key])) {
            unset($container[$key]);
            $this->container->set('view_properties', $container);
        }
    }

    public function setLayout($layout = null)
    {
        if ($layout === null) {
            $this->layout = null;
            return;
        }

        $layout = $this->pathToLayout . DIRECTORY_SEPARATOR . strtolower($layout) . '.php';

        if (!file_exists($layout)) {
            throw new Exception\LookupException('Layout: "' . $layout . '" could not be found.');
        }

        $this->layout = $layout;

        return;
    }

    public function getLayout()
    {
        return $this->layout;
    }

    private function renderViews() : string
    {
        $views = "";

        foreach ($this->viewQueue as $view) {
            //if not end.. otherwise include \r\n
            $views .= $this->buffer($view);
        }

        return $views;
    }

    private function bufferResponse() : string
    {
        $this->view = $response = $this->renderViews();

        if ($this->layout !== null) {
            $response = $this->buffer($this->layout);
        }

        return $response;
    }

    private function buffer(string $path) : string
    {
        $container = $this->container->has('view_properties') ? $this->container->get('view_properties') : [];

        ob_start();
        if (!empty($container)) {
            extract($container); // yuck. could produce undeclared errors. hmm..
        }
        //should i set $this->data in abstract controller, and provide all access vars ? seems bad practice..

        include $path;

        $result = ob_get_contents();
        ob_end_clean();

        return $result;
    }

    /**
     * Helper method for grabbing aggregated css files
     *
     * @access protected
     * @return string css includes
     */
    public function fetchCSS()
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
    public function fetchJS()
    {
        $string = "<script>baseURL = '" . $this->router->baseURL() . "/'</script>\r\n";

        if (empty($this->queuedJS)) {
            return $string;
        }

        foreach ($this->queuedJS as $script) {
            $string .= '<script type="javascript/text" src="' . $script . '"></script>' . "\r\n";
        }

        return $string;
    }

    /**
     * Helper method for adding css files for aggregation/render
     *
     * @access public
     * @param $files array
     * @param $place string
     */
    public function addCSS($files = [], $place = 'append')
    {
        if ($place === 'prepend') {
            $this->queuedCSS = array_merge($files, $this->queuedCSS);
        } else {
            $this->queuedCSS = array_merge($this->queuedCSS, $files);
        }
    }

    public function addJS($files = [], $place = 'append')
    {
        if ($place === 'prepend') {
            $this->queuedJS = array_merge($files, $this->queuedJS);
        } else {
            $this->queuedJS = array_merge($this->queuedJS, $files);
        }
    }

    /**
     * Set 404 header, and return 404 view contents
     *
     * @access public
     * @return string
     */
    public function render404()
    {
        header('HTTP/1.1 404 Not Found');
        $this->setLayout('404');
        return $this->render();
    }
}
