<?php
/**
 * Doctrine/DF Cache Connector
 */

namespace DF\Doctrine;

class Cache extends \Doctrine\Common\Cache\CacheProvider
{
    protected function doFetch($id, $testCacheValidity = true)
    {
        $id = $this->_filterCacheId($id);

        if (!$testCacheValidity || \DF\Cache::test($id))
            return \DF\Cache::get($id);
        else
            return FALSE;
    }

    protected function doContains($id)
    {
        $id = $this->_filterCacheId($id);
        return \DF\Cache::test($id);
    }
    
    protected function doSave($id, $data, $lifeTime = NULL)
    {
        if ($lifeTime == 0)
            $lifeTime = NULL;
        
        \DF\Cache::save($data, $this->_filterCacheId($id), array(), $lifeTime);
        return true;
    }

    protected function doDelete($id)
    {
        \DF\Cache::remove($this->_filterCacheId($id));
    }
    
    protected function doGetStats()
    {
        return null;
    }
    
    protected function doFlush()
    {
        \DF\Cache::clean('all');
    }
    
    public function getIds()
    {
        $all_keys = \DF\Cache::getKeys();
        
        if (!$this->_prefix)
        {
            return $all_keys;
        }
        else
        {
            $relevant_keys = array();
            foreach((array)$all_keys as $key_name => $key_value)
            {
                if (strpos($key_name, $this->_prefix) === 0)
                {
                    $filtered_name = str_replace($this->_prefix.'_', '', $key_name);
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