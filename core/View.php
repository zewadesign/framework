<?php
namespace core;
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
    protected $view;

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
        if($this->layout === false) {
            $this->setLayout($this->configuration->layouts->default);
        }

        return $this->process($this->layout, $this->properties);
    }

    public function setView($requestedView = false, $renderName = false)
    {
        $module = $this->configuration->router->module;

        if ($requestedView !== false) {
            $view = APP_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . strtolower($requestedView) . '.php';
            if (!file_exists($view)) {
                throw new \Exception('View: "' . $view . '" could not be found.');
            }
            if($renderName !== false) {
                $this->$renderName = $this->process($view);
            } else {
                $this->view = $this->process($view);
            }
        } else {
            throw new \Exception('Please provide a view for setView.');
        }
    }

    public function setProperty($property, $value = false)
    {
        if( $value !== false) {
            $this->properties[$property] = $value;
        } else if(is_array($property)) {
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

        //should i set $this->data in abstract controller, and provide all access vars ? seems bad practice..

        include($file);

        $return = ob_get_contents();

        ob_end_clean();

        return $return;
    }

    private function verifyResource($resource) {

        $path = PUBLIC_PATH . DIRECTORY_SEPARATOR . strtolower($resource);
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
            $string .= '<link rel="stylesheet" href="' . baseURL($sheet) .'">';
        }

        return $string;

    }

    protected function fetchJS()
    {
        $scripts = App::getConfiguration('view::js');
        $string = "<script>baseURL = '".baseURL()."'</script>";

        if(empty($scripts)) {
            return $string;
        }

        foreach($scripts as $script) {
            $string .= '<script src="' . baseURL($script) .'"></script>';
        }

        return $string;

    }

    protected function addCSS($sheets = []) {

        $existingCSS = App::getConfiguration('view::css');

        if(empty($existingCSS)) {
            $existingCSS = [];
        }

        if(empty($sheets)) {
            throw new \Exception('You must provide a valid CSS Resource.');
        }

        foreach($sheets as $file) {
            if($this->verifyResource($file)) {
                array_push($existingCSS, $file);
            } else {
                throw new \Exception('The CSS Resource you\'ve specified does not exist.');
            }
        }

        App::setConfiguration('view::css', (object)$existingCSS);
    }

    protected function addJS($scripts = []) {

        $existingJS = App::getConfiguration('view::js');

        if(empty($existingJS)) {
            $existingJS = [];
        }

        if(empty($scripts)) {
            throw new \Exception('You must provide a valid JS Resource.');
        }

        foreach($scripts as $file) {
            if($this->verifyResource($file)) {
                array_push($existingJS, $file);
            } else {
                throw new \Exception('The JS Resource you\'ve specified does not exist.');
            }
        }

        App::setConfiguration('view::js', (object)$existingJS);
    }
}
