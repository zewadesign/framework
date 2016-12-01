<?php

namespace Zewa;

use Zewa\Exception\Exception;

class Dependency
{
    /**
     * @var Container
     */
    private $dependencies;

    public function __construct(Config $config, Container $container)
    {
        $local = [
            '\Zewa\Config' => $config,
            '\Zewa\Container' => $container,
            '\Zewa\Dependency' => $this
        ];

        $this->dependencies = $container;
        $this->dependencies->set('__dependencies', $local);
    }

    private function isDependencyLoaded(string $class) : bool
    {
        $dependencies = $this->dependencies->get('__dependencies');

        if (isset($dependencies[$class]) === false) {
            return false;
        }

        return true;
    }

    private function getDependency(string $class)
    {
        $dependencies = $this->dependencies->get('__dependencies');
        return $dependencies[$class] ?? null;
    }

    private function load(string $class, $dependency, bool $persist)
    {
        if ($persist === true) {
            $dependencies = $this->dependencies->get('__dependencies');
            $dependencies[$class] = $dependency;
            $this->dependencies->set('__dependencies', $dependencies);
        }

//        $this->injectAppInstance($dependency);
        return $dependency;
    }

    public function resolve($class, $persist = false)
    {
        if ($this->isDependencyLoaded($class)) {
            return $this->getDependency($class);
        }

        try {
            $reflectionClass = new \ReflectionClass($class);

            $constructor = $reflectionClass->getConstructor();

            if (!$constructor || $constructor->getParameters() === 0) {
                $dependency = new $class;
                return $this->load($class, $dependency, $persist);
            }

            $params = $this->constructConstructorParameters($reflectionClass);
            $dependency = $reflectionClass->newInstanceArgs($params);

            return $this->load($class, $dependency, $persist);
        } catch (\Exception $e) {
            echo "<PRE>";
            print_r($e->getMessage()); //->getMessage();
            var_dump($e->getTrace()[0]);
            echo "</PRE>";
            return false;
        }
    }

    /**
     * @param \ReflectionClass $reflection
     * @return array
     */
    private function constructConstructorParameters(\ReflectionClass $reflection) : array
    {
        $constructor = $reflection->getConstructor();
        $params = $constructor->getParameters();

        $injection = [];

        foreach ($params as $param) {
            // Here we should perform a bunch of checks, such as: isArray(), isCallable(), isDefaultValueAvailable()
            // isOptional() etc.
            if (is_null($param->getClass())) {
                $injection[] = null;
                continue;
            }
            $injection[] = $this->resolve("\\" . $param->getClass()->getName());
        }

        return $injection;
    }


    private function injectAppInstance($dependency)
    {
        if ($dependency instanceof Controller) {
            $dependency->setEvent($this->resolve('\Sabre\Event\Emitter'));
            $dependency->setRequest($this->resolve('\Zewa\HTTP\Request'));
            $dependency->setRouter($this->resolve('\Zewa\Router'));
            $dependency->setConfig($this->resolve('\Zewa\Config'));
            $dependency->setDependency($this->resolve('\Zewa\Dependency'));
            $dependency->setView($this->resolve('\Zewa\View'));
        }
    }
}
