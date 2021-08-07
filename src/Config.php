<?php

declare(strict_types=1);

namespace App;

use const EXTR_OVERWRITE;

class Config
{
    protected string $baseFolder;

    public function __construct(Environment $environment)
    {
        $this->baseFolder = $environment->getConfigDirectory();
    }

    /**
     * @param string $name
     * @param array $inject_vars Variables to pass into the scope of the configuration.
     *
     * @return array<mixed>
     * @noinspection PhpIncludeInspection
     * @noinspection UselessUnsetInspection
     */
    public function get(string $name, array $inject_vars = []): array
    {
        $path = $this->getPath($name);

        if (is_file($path)) {
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
     */
    public function getPath(string $name): string
    {
        return $this->baseFolder . DIRECTORY_SEPARATOR . str_replace(['.', '..'], ['', ''], $name) . '.php';
    }

    /**
     * Indicate whether a given configuration file name exists.
     *
     * @param string $name
     */
    public function has(string $name): bool
    {
        return file_exists($this->getPath($name));
    }
}
