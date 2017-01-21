<?php

namespace Laasti\Lazydata;

class IterableData extends Data implements \IteratorAggregate
{
    public function getIterator()
    {
        $data = [];
        foreach (array_keys($this->data) as $key) {
            $data[$key] = $this->get($key);
        }
        return new \ArrayIterator($data);
    }
}
