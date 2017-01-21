<?php

namespace Laasti\Lazydata;

use ArrayAccess;
use JsonSerializable;
use Laasti\Lazydata\Resolvers\CallableResolver;
use Laasti\Lazydata\Resolvers\ResolverInterface;
use RuntimeException;

/**
 * Data Class
 *
 */
class Data implements ArrayAccess, JsonSerializable
{

    protected $data = [];
    protected $resolver;

    public function __construct($initialData = [], ResolverInterface $resolver = null)
    {
        $this->resolver = $resolver ?: new CallableResolver;
        $this->add($initialData);
    }

    /**
     * Add data in batch from an array
     * @param array $data
     * @param bool $overwrite
     * @return Data
     */
    public function add($data, $overwrite = true)
    {
        foreach ($data as $property => $value) {
            if ($overwrite || !$this->offsetExists($property)) {
                $this->set($property, $value);
            }
        }
        return $this;
    }

    public function offsetExists($property)
    {
        $random = uniqid('DATA', true);
        return $this->get($property, $random) !== $random;
    }

    /**
     * {@inheritdoc}
     */
    public function get($property, $default = null, $wrapInResolver = true)
    {
        $keyPath = explode('.', $property);
        $resolvableValue =& $this->data;

        for ($i = 0; $i < count($keyPath); $i++) {
            $currentKey = $keyPath[$i];
            if ((is_array($resolvableValue) || $resolvableValue instanceof ArrayAccess) && isset($resolvableValue[$currentKey])) {
                $resolvableValue =& $resolvableValue[$currentKey];
            } else {
                $random = uniqid('DATA_GET', true);
                $result = $this->resolver->resolve($resolvableValue, $random);
                if ((is_array($result) || $result instanceof ArrayAccess) && isset($result[$currentKey])) {
                    $resolvableValue =& $result[$currentKey];
                } else {
                    return $default;
                }
            }
        }

        $resolvableValue = $this->resolver->resolve($resolvableValue);

        //Create a new resolver in case of nested lazydata
        if (!$resolvableValue instanceof Data && (is_array($resolvableValue) || $resolvableValue instanceof ArrayAccess)) {
            $class = __CLASS__;
            $i = 0;
            $isAssoc = false;
            foreach ($resolvableValue as $key => $value) {
                if ($key !== $i) {
                    $isAssoc = true;
                    break;
                }
                $i++;
            }
            if ($isAssoc) {
                $resolvableValue = new $class($resolvableValue, $this->resolver);
            } else {
                $resolvableValue = new IterableData($resolvableValue, $this->resolver);
            }
        }

        return is_null($resolvableValue) ? $default : $resolvableValue;
    }

    /**
     * {@inheritdoc}
     */
    public function set($property, $value)
    {
        $keyPath = explode('.', $property);
        $resolvableValue = &$this->data;
        $end = array_pop($keyPath);
        for ($i = 0; $i < count($keyPath); $i++) {
            $currentKey = $keyPath[$i];
            //$resolvableValue is always an array
            if (!isset($resolvableValue[$currentKey])) {
                $resolvableValue[$currentKey] = [];
            } else {
                $uid = uniqid('', true);
                $result = $this->resolver->resolve($resolvableValue[$currentKey], $uid);
                if ($result === $uid && (!is_array($resolvableValue[$currentKey]) && !$resolvableValue[$currentKey] instanceof ArrayAccess)) {
                    throw new RuntimeException('Trying to set data into a non-array property: "' . $property . '".');
                } elseif (is_array($result) || $result instanceof ArrayAccess) {
                    $resolvableValue[$currentKey] = $result;
                }
            }
            $resolvableValue = &$resolvableValue[$currentKey];
        }
        //Must exit the loop with an array
        $resolvableValue[$end] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function push($property, $value)
    {
        $data = &$this->get($property);
        if (is_array($data) || $data instanceof ArrayAccess) {
            $data[] = $value;
            return $this;
        }

        throw new RuntimeException('Trying to push data into an undefined or a non-array property: "' . $property . '".');
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        return $this;
    }

    public function toArray()
    {
        $data = [];
        $keys = array_keys($this->data);
        foreach ($keys as $key) {
            $data[$key] = $this->get($key);
        }
        return $data;
    }

    public function offsetSet($property, $value)
    {
        $this->set($property, $value);
    }

    public function offsetUnset($property)
    {
        $this->remove($property);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($property)
    {

        $data = &$this->data;
        $keyPath = explode('.', $property);

        if (1 === count($keyPath)) {
            unset($data[$property]);
            return $this;
        }

        $end = array_pop($keyPath);
        for ($i = 0; $i < count($keyPath); $i++) {
            $currentKey = &$keyPath[$i];
            if (!isset($data[$currentKey])) {
                return $this;
            }
            $currentValue = &$currentValue[$currentKey];
        }
        unset($currentValue[$end]);

        return $this;
    }

    public function offsetGet($property)
    {
        return $this->get($property);
    }

    public function getResolver()
    {
        return $this->resolver;
    }

    public function setResolver($resolver)
    {
        $this->resolver = $resolver;
        return $this;
    }

    public function jsonSerialize($data = null)
    {
        $data = $data ?: $this->data;

        if (is_null($data)) {
            return null;
        }

        $exportData = [];
        foreach ($data as $key => $value) {
            $exportData[$key] = $this->get($key, null, false);

            if (is_array($exportData[$key])) {
                $exportData[$key] = $this->jsonSerialize($exportData[$key]);
            }
        }

        return $exportData;
    }
}
