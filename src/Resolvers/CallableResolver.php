<?php

namespace Laasti\Lazydata\Resolvers;

/**
 * CallableResolver, Attempts to resolve data keys using callables
 *
 * @see http://php.net/manual/en/language.types.callable.php For more info on PHP callables
 *
 */
class CallableResolver implements ResolverInterface
{
    protected $prefix;

    public function __construct($prefix = '=')
    {
        $this->prefix = $prefix;
    }

    /**
     * Attempts to resolve the value using callables
     *
     * @param mixed $value
     * @param mixed $default 'value' to return original value or a provided default
     * @return mixed
     */
    public function resolve($value, $default = 'value')
    {
        $callable = $this->getCallable($value);

        if (is_array($callable) && is_callable($callable[0])) {

            if (count($callable) === 1) {
                return call_user_func($callable[0]);
            } else {
                $params = [];
                foreach ($callable[1] as $param) {
                    $params[] = $this->resolve($param);
                }
                return call_user_func_array($callable[0], $params);
            }
        }
        
        return $default === 'value' ? $value : $default;
    }

    /**
     * Attempts to find a callable and return an array [$callable (, array $arguments)]
     * 
     * @param type $value
     * @return array Returns an array [$callable (, array $arguments)], or false when not a callable
     */
    private function getCallable($value)
    {
        if (is_string($value) || is_object($value)) {
            return $this->validateCallable([$value]);
        } elseif (is_array($value) && count($value) === 1 && isset($value[0])) {
            return $this->validateCallable($value);
        } elseif (is_array($value) && count($value) === 2 && isset($value[0]) && isset($value[1]) && is_array($value[1])) {
            return $this->validateCallable($value);
        } elseif (is_array($value) && count($value) === 2 && isset($value[0]) && isset($value[1])) {
            return $this->validateCallable([$value]);
        } elseif (is_array($value) && count($value) === 3 && isset($value[0]) && isset($value[1]) && isset($value[2])) {
            return $this->validateCallable([[$value[0], $value[1]], $value[2]]);
        }

        return false;
    }

    private function validateCallable($callable)
    {
        if (is_string($callable[0]) && strpos($callable[0], $this->prefix) === 0) {
            $callable[0] = preg_replace('/^'.$this->prefix.'/', '', $callable[0]);
            return $callable[0];
        } elseif (is_array($callable[0]) && isset($callable[0][0]) && is_string($callable[0][0]) && strpos($callable[0][0], $this->prefix) === 0) {
            $callable[0][0] = preg_replace('/^'.$this->prefix.'/', '', $callable[0][0]);
            return $callable;
        } elseif (is_array($callable[0]) && isset($callable[0][0]) && is_object($callable[0][0])) {
            return $callable;
        } elseif (is_object($callable[0])) {
            return $callable;
        }

        return false;
    }

}
