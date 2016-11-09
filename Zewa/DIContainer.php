<?php

namespace Zewa;

class DIContainer
{
    //So.. I'm storing dependencies so they can be retrived for later collection
    //without re-instantiating.. we should make this optional some way?

    /**
     * @var array $dependencies
     * @var Config $dependencies['Config']
     */
    private $dependencies = [];
    private $callbacks = [];

    public function __construct(Config $config)
    {
        $this->dependencies['\Zewa\DIContainer'] = $this;
        $this->dependencies['\Zewa\Config'] = $config;
        $controllers = $config->get('controllers');
        $services = $config->get('services');
        if (is_array($controllers)) {
            $this->callbacks = array_merge($controllers);
        }

        if (is_array($services)) {
            $this->callbacks = array_merge($services);
        }
    }

    private function isDependencyLoaded(string $class) : bool
    {
        if (isset($this->dependencies[$class])) {
            return true;
        }

        return false;
    }

    public function resolve($class, $share = false)
    {
        if ($this->isDependencyLoaded($class)) {
            return $this->dependencies[$class];
        } elseif (!empty($this->callbacks[$class])) {
            $share = $this->callbacks[$class]['share'] === true || $share === true ? true : false;
            //pass an instance of $this to the factory for dependency injection on cached classes
            $dependency = $this->callbacks[$class]['factory']($this);
            if ($share === true) {
                $this->dependencies[$class] = $dependency;
            }

            return $dependency;
        }

        // Reflect on the $class
        $reflectionClass = new \ReflectionClass($class);

        // Fetch the constructor (instance of ReflectionMethod)
        $constructor = $reflectionClass->getConstructor();

        // If there is no constructor, there is no
        // dependencies, which means that our job is done.
        if (! $constructor) {
            $dependency = new $class;
            if ($share === true) {
                $this->dependencies[$class] = $dependency;
            }

            return $dependency;
        }

        // Fetch the arguments from the constructor
        // (collection of ReflectionParameter instances)
        $params = $constructor->getParameters();

        // If there is a constructor, but no dependencies,
        // our job is done.
        if (count($params) === 0) {
            $dependency = new $class;
            if ($share === true) {
                $this->dependencies[$class] = $dependency;
            }
            return $dependency;
        }

        // This is were we store the dependencies
        $newInstanceParams = [];

        // Loop over the constructor arguments
        foreach ($params as $param) {
            // Here we should perform a bunch of checks, such as:
            // isArray(), isCallable(), isDefaultValueAvailable()
            // isOptional() etc.

            // For now, we just check to see if the argument is
            // a class, so we can instantiate it,
            // otherwise we just pass null.
            if (is_null($param->getClass())) {
                $newInstanceParams[] = null;
                continue;
            }


            // This is where 'the magic happens'. We resolve each
            // of the dependencies, by recursively calling the
            // resolve() method.
            // At one point, we will reach the bottom of the
            // nested dependencies we need in order to instantiate
            // the class.
            $newInstanceParams[] = $this->resolve(
                $param->getClass()->getName()
            );
        }

        // Return the reflected class, instantiated with all its
        // dependencies (this happens once for all the
        // nested dependencies).
        $dependency = $reflectionClass->newInstanceArgs(
            $newInstanceParams
        );

        if ($share === true) {
            $this->dependencies[$class] = $dependency;
        }

        return $dependency;
    }
}
