<?php

namespace Laasti\Lazydata;

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

    /**
     * Constructor
     * @param ContainerInterface $container
     * @param ResolverInterface $fallback
     */
    public function __construct(ContainerInterface $container,
            ResolverInterface $fallback = null)
    {
        $this->container = $container;
        $this->fallback = $fallback ? : new CallableResolver;
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
            $name = is_array($callable[0]) ? $callable[0][0] : $callable[0];
            $method = is_array($callable[0]) ? $callable[0][1] : null;
            $args = isset($callable[1]) ? $callable[1] : [];

            //The $name must
            if ($this->container->isRegistered($name) || $this->container->isInServiceProvider($name) || isset($this->container[$name])) {
                $object = $this->container->get($name);
                //Use reflection to find object
            } else if (class_exists($name)) {
                $object = $this->container->get($name);
            } else {
                return $this->fallback->resolve($value, $default);
            }

            if (!is_null($method) && method_exists($object, $method)) {
                return call_user_func_array([$object, $method], $args);
            } elseif (is_callable($object)) {
                return call_user_func_array($object, $args);
            } else {
                return $object;
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
    private function getCallable($value)
    {
        if (is_string($value)) {
            return [strpos($value, '::') ? explode('::', $value) : $value];
        } else if (is_array($value) && count($value) === 1) {
            return [strpos($value[0], '::') ? explode('::', $value[0]) : $value[0]];
        } else if (is_array($value) && count($value) === 2 && is_array($value[1])) {
            return [strpos($value[0], '::') ? explode('::', $value[0]) : $value[0], $value[1]];
        } else if (is_array($value) && count($value) === 2) {
            return [$value];
        } else if (is_array($value) && count($value) === 3) {
            return [[$value[0], $value[1]], $value[2]];
        }
        return false;
    }

}
