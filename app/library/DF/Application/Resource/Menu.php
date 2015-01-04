<?php
namespace DF\Application\Resource;
class Menu extends \Zend_Application_Resource_ResourceAbstract
{
    public function init()
    {
        $menu = $this->_getMenuCache();
        
        if (!$menu)
        {
            $menu = $this->_loadMenu();
            $this->_setMenuCache($menu);
        }
        
        return new \DF\Menu($menu);
    }
    
    protected function _getMenuCache()
    {
        // Never cache menus on dev environments.
        if (DF_APPLICATION_ENV == "dev")
            return NULL;
        
        // Compare to environment file timestamp, updated upon phing.
        $upload_reference_path = DF_INCLUDE_BASE.DIRECTORY_SEPARATOR . '.env';
        $last_upload_time = (int)@filemtime($upload_reference_path);
        
        $cache_contents = \DF\Cache::get('df_menu');
        $cache_timestamp = \DF\Cache::get('df_menu_timestamp');
        
        if ($cache_contents && $cache_timestamp >= $last_upload_time)
            return $cache_contents;
        else
            return NULL;
    }
    protected function _setMenuCache($menu)
    {
        \DF\Cache::save($menu, 'df_menu');
        \DF\Cache::save(time(), 'df_menu_timestamp');
    }
    
    protected function _loadMenu()
    {
        $menu = array();
        foreach(new \DirectoryIterator(DF_INCLUDE_MODULES) as $item)
        {
            if( $item->isDir() && !$item->isDot() )
            {
                $menu_file = $item->getPathname().DIRECTORY_SEPARATOR.'menu.php';
                if(file_exists($menu_file))
                {
                    $new_menu = (array)include_once($menu_file);
                    $menu = $this->_mergeFlat($menu, $new_menu);
                }
            }
        }
        return $menu;
    }
    
    protected function _mergeFlat()
    {
        $arrays = func_get_args();
        $base = array_shift($arrays);

        foreach ($arrays as $array)
        {
            reset($base); //important
            foreach($array as $key => $value)
            {
                if (is_array($value) && @is_array($base[$key]))
                    $base[$key] = $this->_mergeFlat($base[$key], $value);
                else
                    $base[$key] = $value;
            }
        }

        return $base;
    }
}