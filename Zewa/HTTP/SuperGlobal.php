<?php
declare(strict_types=1);
namespace Zewa\HTTP;

use Zewa\Interfaces\HTTP\GlobalInterface;
use Zewa\Container;
use Zewa\Security;

abstract class SuperGlobal implements GlobalInterface
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var Security
     */
    protected $security;

    public function __construct(Container $container, Security $security)
    {
        $this->container = $container;
        $this->security = $security;
    }

    public function set(string $key, $value)
    {
        $global = $this->getGlobalName();
        $container = $this->container->get($global);
        $container[$key] = $value;
        $this->container->set($global, $container);
    }

    public function fetch(string $key = null, $default = null)
    {
        $global = $this->getGlobalName();
        if ($key === null) {
            return $this->container->get($global);
        }

        return $this->container->has($global) ? $this->container->get($global)[$key] ?? $default : null;
    }

    public function remove(string $key)
    {
        $global = $this->getGlobalName();
        if ($this->container->has($global)) {
            $this->processRemoval($key);
        }
    }

    private function getGlobalName() : string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    private function processRemoval(string $key)
    {
        $global = $this->getGlobalName();
        $container = $this->container->get($global);
        if (isset($container[$key])) {
            unset($container[$key]);
            $this->container->set($global, $container);
        }
    }

    protected function registerGlobal($value)
    {
        $value = $this->security->normalize($value);
        $this->container->set($this->getGlobalName(), $value);
    }
}
