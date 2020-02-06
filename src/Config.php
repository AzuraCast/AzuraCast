<?php
namespace App;

use const EXTR_OVERWRITE;

class Config
{
    protected $_base_folder;

    public function __construct($base_folder)
    {
        if (!is_dir($base_folder)) {
            throw new Exception("Invalid base folder for configurations.");
        }

        $this->_base_folder = $base_folder;
    }

    /**
     * @param string $name
     * @param array $inject_vars Variables to pass into the scope of the configuration.
     *
     * @return array
     */
    public function get($name, $inject_vars = []): array
    {
        $path = $this->_getPath($name);

        if (file_exists($path)) {
            unset($name);
            extract($inject_vars, EXTR_OVERWRITE);
            unset($inject_vars);

            return require $path;
        }

        return [];
    }

    /**
     * Return the configuration path resolved by the specified name.
     *
     * @param string $name
     *
     * @return string
     */
    public function _getPath($name)
    {
        return $this->_base_folder . DIRECTORY_SEPARATOR . str_replace(['.', '..'], ['', ''], $name) . '.php';
    }

    /**
     * Indicate whether a given configuration file name exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name): bool
    {
        return file_exists($this->_getPath($name));
    }
}
