<?php
/**
 * Doctrine/DF Cache Connector
 */

namespace DF\Doctrine;

class Cache extends \Doctrine\Common\Cache\CacheProvider
{
    /**
     * @var \Phalcon\Cache\BackendInterface
     */
    protected $_cache;

    public function __construct()
    {
        $this->_cache = \DF\Cache::getCache('doctrine');
    }

    protected function doFetch($id, $testCacheValidity = true)
    {
        $id = $this->_filterCacheId($id);

        if (!$testCacheValidity || $this->_cache->exists($id))
            return $this->_cache->get($id);
        else
            return FALSE;
    }

    protected function doContains($id)
    {
        $id = $this->_filterCacheId($id);
        return $this->_cache->exists($id);
    }
    
    protected function doSave($id, $data, $lifeTime = NULL)
    {
        if ($lifeTime == 0 || $lifeTime == NULL)
            $lifeTime = 3600;

        $id = $this->_filterCacheId($id);
        $this->_cache->save($id, $data, $lifeTime);
        return true;
    }

    protected function doDelete($id)
    {
        $id = $this->_filterCacheId($id);
        $this->_cache->delete($id);
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
    }
    
    protected function _filterCacheId($id)
    {
        return preg_replace("/[^a-zA-Z0-9_]/", "", $id);
    }
}