<?php

namespace core;

class Hook
{
    private $loader;
    private $processed = array();

    public function __construct() {

        $this->loader = Registry::get('_loader');

        $registeredHooks = $this->loader->config('hooks','register');

        foreach($registeredHooks as $hook) {
            $processed[$hook] = false;
        }

    }

    private function process($hook) {
        //@TODO handle hook execution in try/catch with silent fail (notification to system?)
        if(is_callable($hook)) {

            $hook();
            $processed[$hook] = true;

        }

    }

}
