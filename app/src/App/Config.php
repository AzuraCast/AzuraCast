<?php
namespace App;

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
     * @return array
     */
    public function get($name, $inject_vars = []): array
    {
        $path = $this->_base_folder.DIRECTORY_SEPARATOR.str_replace(['.', '..'], ['', ''], $name).'.conf.php';

        if (file_exists($path)) {
            unset($name);
            extract($inject_vars, \EXTR_OVERWRITE);
            unset($inject_vars);

            return require $path;
        }

        return [];
    }
}