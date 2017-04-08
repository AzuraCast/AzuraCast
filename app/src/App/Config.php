<?php
namespace App;

use Interop\Container\ContainerInterface;

class Config
{
    protected $_baseFolder;

    protected $_loaded_configs;

    /** @var ContainerInterface */
    protected $di;

    public function __construct($baseFolder, ContainerInterface $di)
    {
        $this->di = $di;
        $this->_loaded_configs = [];

        if (is_dir($baseFolder)) {
            $this->_baseFolder = $baseFolder;
        } else {
            throw new \Exception("Invalid base folder for configurations.");
        }
    }

    public function preload($configs)
    {
        $config_array = (is_array($configs)) ? $configs : [$configs];
        foreach ($config_array as $config_item) {
            $this->__get($config_item);
        }
    }

    public function __set($name, $value)
    {
        throw new \Exception("Configuration is read-only.");
    }

    public function __get($name)
    {
        if (!isset($this->_loaded_configs[$name])) {
            $config_name = str_replace(['.', '..'], ['', ''], $name);
            $config_base = $this->_baseFolder . DIRECTORY_SEPARATOR . $config_name;

            if (is_dir($config_base)) {
                return new self($config_base, $this->di);
            } // Return entire directories.
            else {
                $this_config = $this->getFile($config_base);
            } // Return single files.

            $this->_loaded_configs[$name] = $this_config;
        }

        return $this->_loaded_configs[$name];
    }

    public function getFile($config_base)
    {
        $di = $this->di;

        if (file_exists($config_base)) {
            $config = require $config_base;
        } elseif (file_exists($config_base . '.conf.php')) {
            $config = require $config_base . '.conf.php';
        } else {
            $config = [];
        }

        return new \Zend\Config\Config($config);
    }
}