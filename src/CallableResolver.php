<?php

namespace Laasti\Lazydata;

/**
 * CallableResolver, Attempts to resolve data keys using callables
 *
 * @see http://php.net/manual/en/language.types.callable.php For more info on PHP callables
 *
 */
class CallableResolver implements ResolverInterface
{

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

        if (is_array($callable) && count($callable) === 1) {
            return call_user_func($callable[0]);
        } else if (is_array($callable)) {
            return call_user_func_array($callable[0], $callable[1]);
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
        if (is_callable($value)) {
            return [$value];
        } elseif (is_array($value) && count($value) === 1 && isset($value[0]) && is_callable($value[0])) {
            return $value;
        } elseif (is_array($value) && count($value) === 2 && isset($value[1]) && is_array($value[1])) {
            if (is_array($value[1]) && is_callable($value[0])) {
                return $value;
            }
        } elseif (is_array($value) && count($value) === 3 && isset($value[0]) && isset($value[1]) && is_callable([$value[0], $value[1]])) {
            return [[$value[0], $value[1]], $value[2]];
        }

        return false;
    }

}
