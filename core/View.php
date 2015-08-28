<?php
namespace core;
use app\models\Example;
use app\modules as modules;

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
     * Instantiated load class pointer
     *
     * @var object
     */
    protected $load;

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
     * Load up some basic configuration settings.
     */
    public function __construct()
    {
        // This abstract is strictly to establish inheritance from a global registery.
        $this->configuration = App::getConfiguration();
        $this->load = Load::getInstance();
        $this->request = Request::getInstance();
    }

    private function baseURL($path = '') {
        return $this->router->baseURL($path);
    }

    private function currentURL() {
        return $this->router->currentURL();
    }


    private function currentURI() {
        return $this->router->uri();
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
        if($this->view !== false) {
            $this->view = $this->process($this->view);
        }
        if($this->layout === false) {
            $this->setLayout($this->configuration->layouts->default);
        }
        return $this->process($this->layout, $this->properties);
    }

    public function setView($requestedView)
    {
        $module = $this->configuration->router->module;

        try {
            $view = APP_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . strtolower($requestedView) . '.php';
            if (!file_exists($view)) {
                throw new \Exception('View: "' . $view . '" could not be found.');
            }
            $this->view = $view;
        } catch (\Exception $e) {
            echo 'Caught exception:' . $e->getMessage();
            exit;
        }

    }

    public function setProperty($property, $value = false)
    {
        if( $value !== false) {
            $this->properties[$property] = $value;
        } else if(!empty($property)) {
            $this->properties = $property;
        }
        return false;
    }

    public function setLayout($layout)
    {

        $layout = APP_PATH . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . strtolower($layout) . '.php';

        if(!file_exists($layout)) {
            throw new \Exception('Layout: "' . $layout . '" could not be found.');
        }

        $this->layout = $layout;

        return true;
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

    private function verifyResource($resource) {

        $path = PUBLIC_PATH . DIRECTORY_SEPARATOR . $resource;
        if (!file_exists($path)) {
            return false;
        }

        return true;
    }

    protected function fetchCSS()
    {
        $sheets = App::getConfiguration('view::css');
        $string = "";

        if(empty($sheets)) {
            return $string;
        }

        foreach($sheets as $sheet) {
            $string .= '<link rel="stylesheet" href="' . $this->baseURL($sheet) .'">' . "\r\n";
        }

        return $string;

    }

    protected function fetchJS()
    {
        $scripts = App::getConfiguration('view::js');
        $string = "<script>baseURL = '" . $this->baseURL() . "/'</script>";

        if(empty($scripts)) {
            return $string;
        }

        foreach($scripts as $script) {
            $string .= '<script src="' . $this->baseURL($script) . '"></script>' . "\r\n";
        }

        return $string;

    }

    public function addCSS($sheets = [], $place = 'append') {

        $existingCSS = App::getConfiguration('view::css');

        try {
            if ($existingCSS === false) {
                $existingCSS = [];
            } else {
                $existingCSS = (array)$existingCSS;
            }
            if (empty($sheets)) {
                throw new \Exception('You must provide a valid CSS Resource.');
            }

            $files = [];

            foreach ($sheets as $file) {
                if ($this->verifyResource($file)) {
                    $files[] = $file;
                } else {
                    throw new \Exception('The CSS Resource you\'ve specified does not exist.');
                }
            }

            if ($place === 'prepend') {
                $existingCSS = array_merge($files, $existingCSS);
            } else {
                $existingCSS = array_merge($existingCSS, $files);
            }

            App::setConfiguration('view::css', (object)$existingCSS);
        } catch (\Exception $e) {
            echo 'Caught exception:' . $e->getMessage();
            exit;
        }
    }

    public function addJS($scripts = [], $place = 'append') {
        $existingJS = App::getConfiguration('view::js');

        try {
            if ($existingJS === false) {
                $existingJS = [];
            } else {
                $existingJS = (array)$existingJS;
            }

            if (empty($scripts)) {
                throw new \Exception('You must provide a valid JS Resource.');
            }

            $files = [];

            foreach ($scripts as $file) {
                if ($this->verifyResource($file)) {
                    $files[] = $file;
                } else {
                    throw new \Exception('The JS Resource you\'ve specified does not exist: ' . $file);
                }
            }

            if ($place === 'prepend') {
                $existingJS = array_merge($files, $existingJS);
            } else {
                $existingJS = array_merge($existingJS, $files);
            }
            App::setConfiguration('view::js', (object)$existingJS);

        } catch (\Exception $e) {
            echo 'Caught exception:' . $e->getMessage();
            exit;
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
