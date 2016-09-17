<?php
/**
 * Doctrine/App Cache Connector
 */

namespace App\Doctrine;

class Cache extends \Doctrine\Common\Cache\CacheProvider
{
    /**
     * @var \Stash\Pool
     */
    protected $_cache;

    public function __construct()
    {
        $di = $GLOBALS['di'];
        $cache_driver = $di['cache_driver'];

        $pool = new \Stash\Pool($cache_driver);
        $pool->setNamespace(\App\Cache::getSitePrefix('doctrine'));

        $this->_cache = $pool;
    }

    protected function doFetch($id, $testCacheValidity = true)
    {
        $id = $this->_filterCacheId($id);
        $item = $this->_cache->getItem($id);

        if (!$testCacheValidity || !$item->isMiss())
            return $item->get();
        else
            return FALSE;
    }

    protected function doContains($id)
    {
        $id = $this->_filterCacheId($id);
        $item = $this->_cache->getItem($id);

        return !$item->isMiss();
    }
    
    protected function doSave($id, $data, $lifeTime = NULL)
    {
        if ($lifeTime == 0 || $lifeTime == NULL)
            $lifeTime = 3600;

        $id = $this->_filterCacheId($id);

        $item = $this->_cache->getItem($id);
        return $item->set($data, $lifeTime);
    }

    protected function doDelete($id)
    {
        $id = $this->_filterCacheId($id);

        $item = $this->_cache->getItem($id);
        return $item->clear();
    }
    
    protected function doGetStats()
    {
        return null;
    }
    
    protected function doFlush()
    {
        $this->_cache->flush();
    }
    
    public function getIds()
    {
        return null;
        /*
        $all_keys = $this->_cache->queryKeys();

        if (!$this->getNamespace())
        {
            return $all_keys;
        }
        else
        {
            $relevant_keys = array();
            foreach((array)$all_keys as $key_name => $key_value)
            {
                if (strpos($key_name, $this->getNamespace()) === 0)
                {
                    $filtered_name = str_replace($this->getNamespace().'_', '', $key_name);
                    $relevant_keys[$filtered_name] = $key_value;
                }
            }
            return $relevant_keys;
        }
        */
    }
    
    protected function _filterCacheId($id)
    {
        return preg_replace("/[^a-zA-Z0-9_]/", "", $id);
    }
}