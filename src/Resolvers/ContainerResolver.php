<?php

namespace Laasti\Lazydata\Resolvers;

use League\Container\ContainerInterface;

/**
 * Resolves lazy loaded callables using league/container
 *
 */
class ContainerResolver implements ResolverInterface
{

    /**
     * Fallback Resolver
     * @var ResolverInterface
     */
    protected $fallback;

    /**
     * The container to use
     * @var ContainerInterface
     */
    protected $container;

    protected $prefix;

    /**
     * Constructor
     * @param ContainerInterface $container
     * @param ResolverInterface $fallback
     */
    public function __construct(ContainerInterface $container,
            ResolverInterface $fallback = null, $prefix = '=')
    {
        $this->container = $container;
        $this->prefix = $prefix;
        $this->fallback = $fallback ? : new CallableResolver($prefix);
    }

    /**
     * Attempts to resolve through the container or fallback on CallableResolver
     *
     * @param mixed $value
     */
    public function resolve($value, $default = 'value')
    {
        $callable = $this->getCallable($value);

        if (is_array($callable)) {
            if (is_callable($callable[0])) {
                return call_user_func_array($callable[0], $callable[1]);
            } else {
                return $callable[0];
            }
        }

        return $this->fallback->resolve($value, $default);
    }

    /**
     * Get a properly formatted callable
     *
     * @param mixed $value
     * @return array|bool The callable with its arguments, or false on fail
     */
    public function getCallable($value)
    {
        if (is_string($value)) {
            $callable = $this->validateCallable([strpos($value, '::') ? explode('::', $value) : $value]);
        } else if (is_array($value) && count($value) === 1 && isset($value[0]) && is_string($value[0])) {
            $callable = $this->validateCallable([strpos($value[0], '::') ? explode('::', $value[0]) : $value[0]]);
        } else if (is_array($value) && count($value) === 2 && isset($value[0]) && is_string($value[0]) && isset($value[1]) && is_array($value[1])) {
            $callable = $this->validateCallable([strpos($value[0], '::') ? explode('::', $value[0]) : $value[0], $value[1]]);
        } else if (is_array($value) && count($value) === 2 && isset($value[0]) && isset($value[1])) {
            $callable = $this->validateCallable([$value]);
        } else if (is_array($value) && count($value) === 3 && isset($value[0]) && isset($value[1]) && isset($value[2])) {
            $callable = $this->validateCallable([[$value[0], $value[1]], $value[2]]);
        } else {
            $callable = null;
        }

        if (is_array($callable)) {
            $name = is_array($callable[0]) ? $callable[0][0] : $callable[0];
            $method = is_array($callable[0]) ? $callable[0][1] : null;
            $args = isset($callable[1]) ? $callable[1] : [];

            if (is_object($name)) {
                $object = $name;
            } else if ($this->container->isRegistered($name) || $this->container->isInServiceProvider($name) || isset($this->container[$name]) || class_exists($name)) {
                $object = $this->container->get($name);
            } else {
                return false;
            }
            if (is_null($method)) {
                return [$object, $args];
            } else {
                return [[$object, $method], $args];
            }
        }
        return false;
    }

    private function validateCallable($callable)
    {
        if (is_string($callable[0]) && strpos($callable[0], $this->prefix) === 0) {
            $callable[0] = preg_replace('/^'.$this->prefix.'/', '', $callable[0]);
            return $callable;
        } elseif (is_array($callable[0]) && is_string($callable[0][0]) && strpos($callable[0][0], $this->prefix) === 0) {
            $callable[0][0] = preg_replace('/^'.$this->prefix.'/', '', $callable[0][0]);
            return $callable;
        } elseif (is_array($callable[0]) && is_object($callable[0][0])) {
            return $callable;
        } elseif (is_object($callable[0])) {
            return $callable;
        }

        return false;
    }
}
