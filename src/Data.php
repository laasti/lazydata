<?php

namespace Laasti\Lazydata;

/**
 * Lazyloads data from config using a Resolver
 */
class Data extends \Dflydev\DotAccessData\Data
{

    /**
     * Constructor
     *
     * @param array|null $data
     */
    public function __construct(array $data = null, ResolverInterface $resolver = null)
    {
        $this->data = $data ?: array();
        $this->resolver = $resolver ?: new CallableResolver;
        parent::__construct($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $resolvableValue = $this->data;
        $keyPath = explode('.', $key);
        
        for ( $i = 0; $i < count($keyPath); $i++ ) {
            $currentKey = $keyPath[$i];
            if ( !is_array($resolvableValue) || !isset($resolvableValue[$currentKey])) {
                $resolvableValue = $this->resolver->resolve($resolvableValue, null);
                if (is_null($resolvableValue)) {
                    return $default;
                }
            }
            if ( is_array($resolvableValue) && isset($resolvableValue[$currentKey])) {
                $resolvableValue = $resolvableValue[$currentKey];
            }

        }

        $resolvableValue = $this->resolver->resolve($resolvableValue);

        return $resolvableValue === null ? $default : $resolvableValue;
    }
    
}
