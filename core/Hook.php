<?php

namespace core;

/**
 * This class registers, dispatches and invokes configured hooks.
 *
 * <code>
 *
 * $this->hook = new Hook();
 * $this->hook->dispatch('hook1');
 * $this->hook->dispatch('hook2');
 * $this->hook->dispatch('hook3');
 *
 * </code>
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */

class Hook
{
    /**
     * Boolean for whether or not hooks are enabled
     *
     * @access private
     * @var boolean
     */

    private $enabled;

    /**
     * Instantiated load class pointer
     *
     * @access private
     * @var object
     */

    private $load;

    /**
     * Hooks registered at application runtime
     *
     * @access private
     * @var array
     */

    private $hooks = [];

    /**
     * Hooks that have been invoked
     *
     * @access private
     * @var array
     */

    private $processed = [];

    /**
     * Reference to instantiated controller object.
     *
     * @var object
     */

    public static $instance;

    /**
     * Set some basic hook settings
     *
     * Set whether or not hooks are enabled,
     * if they are enabled, register them.
     */

    public function __construct($load)
    {
        $this->load = $load;
        $this->enabled = $this->load->config('core', 'hooks');
        if ($this->enabled) {
            // Why would this class care if it's enabled?
            // Don't even new it up in the first place if it's not enabled?
            $this->registerHooks();
        }
        return;
    }

    /**
     * Set some basic hook settings
     *
     * Set whether or not hooks are enabled,
     * if they are enabled, register them.
     *
     * @access private
     */

    private function registerHooks()
    {

        $registeredHooks = $this->load->config('hooks', 'register');

        foreach ($registeredHooks as $hook => $config) {
            $this->hooks[$hook] = ($config->enabled) ? $config->call : false;
            $this->processed[$hook] = false;
        }

        return;

    }

    /**
     * Dispatch a hook to $this->process to be invoked
     *
     * @access public
     *
     * @param string $hook pointer to closure
     */

    public function dispatch($hook)
    {

        if ($this->enabled && $this->hooks[$hook]) {
            $this->process($hook);

        }

        return;

    }

    /**
     * Processes queued hooks
     *
     * @access private
     *
     * @param string $hook pointer to closure
     */

    private function process($hook)
    {
        //@TODO handle hook execution in try/catch with silent fail (notification to system?)
        $call = $this->hooks[$hook];
        if (is_callable($call)) {
            $call();
            $this->processed[$hook] = true;

        }

        return;

    }

    /**
     * Returns a reference of object once instantiated
     *
     * @access public
     * @return object
     */

    public static function &getInstance()
    {

        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;

    }
}
