<?php
namespace App;

use Composer\Autoload\ClassLoader;
use Slim\Container;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Plugins
{
    /** @var array An array of all plugins and their capabilities. */
    protected $plugins = [];

    public function __construct($base_dir)
    {
        $this->loadDirectory($base_dir);
    }

    public function loadDirectory($dir): void
    {
        $plugins = (new Finder())
            ->ignoreUnreadableDirs()
            ->directories()
            ->in($dir);

        foreach($plugins as $plugin_dir) {
            /** @var SplFileInfo $plugin_dir */
            $plugin_prefix = $plugin_dir->getRelativePathname();
            $plugin_namespace = 'Plugin\\'.\Doctrine\Common\Inflector\Inflector::classify($plugin_prefix).'\\';

            $this->plugins[$plugin_prefix] = [
                'namespace' => $plugin_namespace,
                'path' => $plugin_dir->getPathname(),
            ];
        }
    }

    /**
     * Add plugin namespace classes (and any Composer dependencies) to the global include list.
     *
     * @param ClassLoader $autoload
     */
    public function registerAutoloaders(ClassLoader $autoload): void
    {
        foreach($this->plugins as $plugin) {
            $plugin_path = $plugin['path'];

            if (file_exists($plugin_path.'/vendor/autoload.php')) {
                require($plugin_path.'/vendor/autoload.php');
            }

            $autoload->addPsr4($plugin['namespace'], $plugin_path.'/src');
        }
    }

    /**
     * Register or override any services contained in the global Dependency Injection container.
     *
     * @param Container $di
     * @param array $settings
     */
    public function registerServices(Container $di, array $settings): void
    {
        foreach($this->plugins as $plugin) {
            $plugin_path = $plugin['path'];

            if (file_exists($plugin_path . '/services.php')) {
                call_user_func(include($plugin_path . '/services.php'), $di, $settings);
            }
        }
    }

    /**
     * Register custom events that the plugin overrides with the Event Dispatcher.
     *
     * @param EventDispatcher $dispatcher
     */
    public function registerEvents(EventDispatcher $dispatcher): void
    {
        foreach($this->plugins as $plugin) {
            $plugin_path = $plugin['path'];

            if (file_exists($plugin_path . '/events.php')) {
                call_user_func(include($plugin_path . '/events.php'), $dispatcher);
            }
        }
    }
}
