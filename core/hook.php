<?php

namespace core;

class Hook
{
    private $enabled;
    private $loader;
    private $hooks = array();
    private $processed = array();
    public static $instance;

    public function __construct() {


        $this->loader = Registry::get('_loader');

        $this->enabled = $this->loader->config('core','hooks');

        if($this->enabled) {
            $this->registerHooks();
        }

    }

    private function registerHooks() {

        $registeredHooks = $this->loader->config('hooks','register');

        foreach($registeredHooks as $hook => $config) {
            $this->hooks[$hook]= ($config->enabled) ? $config->call : false;
            $this->processed[$hook] = false;
        }


    }

    public function dispatch($hook) {

        if($this->enabled && $this->hooks[$hook]) {

            $this->process($hook);

        }

    }

    private function process($hook) {
        //@TODO handle hook execution in try/catch with silent fail (notification to system?)
        $call = $this->hooks[$hook];
        if(is_callable($call)) {

            $call();
            $this->processed[$hook] = true;

        }

    }


    public static function &getInstance() {

        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;

    }
}
