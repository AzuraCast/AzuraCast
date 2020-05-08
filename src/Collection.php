<?php
/**
 * Lightweight "Collection" class.
 * Originates from the Slim PHP framework, version 3.
 * Slim Framework (https://slimframework.com)
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace App;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;

/**
 * Collection
 * This class provides a common interface used by many other
 * classes in a Slim application that manage "collections"
 * of data that must be inspected and/or manipulated
 */
class Collection implements ArrayAccess, IteratorAggregate, JsonSerializable
{
    protected array $data = [];

    public function __construct(array $items = [])
    {
        $this->replace($items);
    }

    public function replace(array $items): void
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function set($key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function keys(): array
    {
        return array_keys($this->data);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function offsetGet($key)
    {
        return $this->get($key);
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    public function offsetUnset($key): void
    {
        $this->remove($key);
    }

    public function remove($key): void
    {
        unset($this->data[$key]);
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }

    public function jsonSerialize()
    {
        return $this->data;
    }
}