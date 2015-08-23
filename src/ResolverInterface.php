<?php

namespace Laasti\Lazydata;

interface ResolverInterface
{
    /**
     * Loads data from lazy loading value
     * @param mixed $value
     */
    public function resolve($value);
}
