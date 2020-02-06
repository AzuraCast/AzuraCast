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

/**
 * Collection
 * This class provides a common interface used by many other
 * classes in a Slim application that manage "collections"
 * of data that must be inspected and/or manipulated
 */
class Collection implements ArrayAccess, IteratorAggregate
{
    /**
     * The source data
     * @var array
     */
    protected $data = [];

    /**
     * @param array $items Pre-populate collection with this key-value array
     */
    public function __construct(array $items = [])
    {
        $this->replace($items);
    }

    /**
     * @param array $items
     */
    public function replace(array $items): void
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get collection keys
     * @return array The collection's source data keys
     */
    public function keys(): array
    {
        return array_keys($this->data);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * Does this collection have a given key?
     *
     * @param string $key The data key
     *
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get collection item for key
     *
     * @param string $key The data key
     *
     * @return mixed The key's value, or the default value
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    /**
     * Set collection item
     *
     * @param string $key The data key
     * @param mixed $value The data value
     */
    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function offsetUnset($key): void
    {
        $this->remove($key);
    }

    /**
     * @param string $key
     */
    public function remove($key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Get number of items in collection
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Get collection iterator
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }
}