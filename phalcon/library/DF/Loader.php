<?php
/**
 * Autoloader with namespace absolute path caching
 */

namespace DF;
class Loader
{
    static $autoload_classes = array();
    
    public static function loadClass($class_name)
    {
        if (strpos($class_name, '\\') === 0)
            $class_name = substr($class_name, 1);
        
        // Look through pre-assigned list of include paths first.
        foreach(self::$autoload_classes as $class_prefix => $class_dir)
        {
            if (strpos($class_name, $class_prefix) === 0)
            {
                // Special handling for Doctrine 2.2 proxies.
                if ($class_prefix == "Proxy")
                {
                    $find = array('\\', 'Proxy');
                    $replace = array('', 'Proxy'.DIRECTORY_SEPARATOR);
                    $class_path = $class_dir.DIRECTORY_SEPARATOR.str_replace($find, $replace, $class_name).'.php';
                }
                else
                {
                    $find = array('\\', '_');
                    $replace = array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
                    $class_path = $class_dir.DIRECTORY_SEPARATOR.str_replace($find, $replace, $class_name).'.php';
                }
                
                if (@include($class_path))
                    return true;
                else
                    break;
            }
        }
        
        // Try loading the flat filename (some legacy classes)
        $class_path = $class_name.'.php';
        
        if (@include($class_path))
            return true;
        
        // Try loading the directory-separated filename (other legacy classes).
        $class_path = str_replace('_', DIRECTORY_SEPARATOR, $class_name).'.php';
        if (@include($class_path))
            return true;
        
        return false;
    }
    
    public static function register($class_paths)
    {
        if ($class_paths instanceof \Zend_Config)
            $class_paths = $class_paths->toArray();
        
        foreach((array)$class_paths as $class_prefix => $class_dir)
        {
            self::$autoload_classes[$class_prefix] = $class_dir;
        }
        
        // Force an update of the include path.
        $include_path = array();
        $include_path[] = DF_INCLUDE_LIB;
        $include_path[] = DF_INCLUDE_LIB.DIRECTORY_SEPARATOR.'ThirdParty';
        
        if (defined('DF_INCLUDE_LIB_LOCAL'))
        {
            $include_path[] = DF_INCLUDE_LIB_LOCAL;
            $include_path[] = DF_INCLUDE_LIB_LOCAL.DIRECTORY_SEPARATOR.'ThirdParty';
        }
        
        $include_path[] = get_include_path();
        set_include_path(implode(PATH_SEPARATOR, $include_path));
        
        spl_autoload_register(array(__CLASS__, 'loadClass'));
        spl_autoload_register();
    }
}