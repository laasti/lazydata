<?php

namespace Laasti\Lazydata;

use InvalidArgumentException;

/**
 * Resolves lazy loaded callables using league/container
 *
 */
class FilterResolver implements ResolverInterface
{

    /**
     * Valid filter key name regex
     */
    const KEY_REGEX = '[a-zA-Z_0-9]+';

    /**
     * Fallback Resolver
     * @var ResolverInterface
     */
    protected $fallback;

    /**
     * Separator between filter key and data
     * @var string
     */
    protected $separator;

    /**
     * Filters
     * @var array
     */
    protected $filters = [];

    /**
     * Constructor
     * @param string $separator
     * @param ResolverInterface $fallback
     */
    public function __construct($separator = ':',
            ResolverInterface $fallback = null)
    {
        $this->separator = $separator;
        $this->fallback = $fallback ? : new CallableResolver('=');
    }

    /**
     * Attempts to resolve through defined filters
     *
     * @param mixed $value
     */
    public function resolve($value, $default = 'value')
    {
        $matches = [];
        if (is_string($value) && preg_match('/^('.self::KEY_REGEX.')'.  preg_quote($this->separator).'(.*)/', $value, $matches)) {
            if (isset($this->filters[$matches[1]])) {
                return call_user_func_array($this->filters[$matches[1]], [$matches[2]]);
            } else if (function_exists($matches[1])) {
                return call_user_func_array($matches[1], [$matches[2]]);
            }
        }

        return $this->fallback->resolve($value, $default);
    }

    /**
     * Map a filter to a callable
     * @param string $key
     * @param callable $callable
     * @return FilterResolver
     * @throws InvalidArgumentException
     */
    public function setFilter($key, $callable)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Invalid callable for filter: "'.$key.'"');
        }
        if (!preg_match('/^'.self::KEY_REGEX.'$/', $key)) {
            throw new InvalidArgumentException('Invalid name for filter: "'.$key.'". The filter name can only contain alphanumeric characters and underscore.');
        }

        $this->filters[$key] = $callable;

        return $this;

    }

    /**
     * Remove filter
     * @param string $name
     * @return \Laasti\Lazydata\FilterResolver
     */
    public function removeFilter($name)
    {
        if (isset($this->filters[$name])) {
            unset($this->filters[$name]);
        }

        return $this;
    }

}
