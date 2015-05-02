<?php
namespace DF;

class Config
{
    protected $_baseFolder;
    protected $_loaded_configs;
    
    public function __construct($baseFolder)
    {
        $this->_loaded_configs = array();
        
        if(is_dir($baseFolder))
            $this->_baseFolder = $baseFolder;
        else
            throw new \Exception("Invalid base folder for configurations.");
    }
    
    public function preload($configs)
    {
        $config_array = (is_array($configs)) ? $configs : array($configs);
        foreach($config_array as $config_item)
        {
            $this->__get($config_item);
        }
    }
    
    public function __set($name, $value)
    {
        throw new \Exception("Configuration is read-only.");
    }
    
    public function __get($name)
    {
        if (!isset($this->_loaded_configs[$name]))
        {
            $config_name = str_replace(array('.','..'), array('', ''), $name);
            $config_base = $this->_baseFolder.DIRECTORY_SEPARATOR.$config_name;
            
            if (is_dir($config_base))
                return new self($config_base); // Return entire directories.
            else
                $this_config = $this->getFile($config_base); // Return single files.
            
            $this->_loaded_configs[$name] = $this_config;
        }
        
        return $this->_loaded_configs[$name];
    }

    public function getFile($config_base)
    {
        if (file_exists($config_base))
            return new Config\Item(require $config_base);
        elseif (file_exists($config_base.'.conf.php'))
            return new Config\Item(require $config_base.'.conf.php');
        else
            return new Config\Item(array());
    }
    
    /**
     * Static Functions
     */
    
    public static function loadConfig($directory)
    {
        return new self($directory);
    }
    public static function loadModuleConfig($directory)
    {
        $module_config = array();
        foreach(new \DirectoryIterator($directory) as $item)
        {
            if($item->isDir() && !$item->isDot())
            {
                $config = $item->getPathname().DIRECTORY_SEPARATOR.'config';
                if(file_exists($config))
                    $module_config[$item->getFilename()] = new self($config);
            }
        }
        return $module_config;
    }
}