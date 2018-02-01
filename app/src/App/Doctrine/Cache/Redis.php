<?php
namespace App\Doctrine\Cache;

/**
 * Overrider of the Doctrine Redis Cache functionality
 *
 * Dev Note: This entire class basically only exists because \Doctrine\Common\Cache\CacheProvider::getNamespacedId
 * is a private function that can't be overridden. Thus, instead, to remove Doctrine's flavor of namespacing
 * from Redis caching, one must reimplement *everything* that calls that.
 *
 * @package App\Doctrine\Cache
 */
class Redis extends \Doctrine\Common\Cache\RedisCache
{
    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        return $this->doFetch($id);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMultiple(array $keys)
    {
        if (empty($keys)) {
            return array();
        }

        // note: the array_combine() is in place to keep an association between our $keys and the $namespacedKeys
        $namespacedKeys = array_combine($keys, $keys);
        $items          = $this->doFetchMultiple($namespacedKeys);
        $foundItems     = array();

        // no internal array function supports this sort of mapping: needs to be iterative
        // this filters and combines keys in one pass
        foreach ($namespacedKeys as $requestedKey => $namespacedKey) {
            if (isset($items[$namespacedKey]) || array_key_exists($namespacedKey, $items)) {
                $foundItems[$requestedKey] = $items[$namespacedKey];
            }
        }

        return $foundItems;
    }

    /**
     * {@inheritdoc}
     */
    public function saveMultiple(array $keysAndValues, $lifetime = 0)
    {
        return $this->doSaveMultiple($keysAndValues, $lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return $this->doContains($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        return $this->doSave($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->doDelete($id);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAll()
    {
        $this->getRedis()->flushDB();
    }

    protected function getSerializerValue()
    {
        return \Redis::SERIALIZER_PHP;
    }
}