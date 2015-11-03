<?php

namespace Laasti\Lazydata\Resolvers;

interface ResolverInterface
{
    /**
     * Loads data from lazy loading value
     * @param mixed $value
     */
    public function resolve($value);
}
