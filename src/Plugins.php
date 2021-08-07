<?php

declare(strict_types=1);

namespace App;

use Azura\SlimCallableEventDispatcher\CallableEventDispatcherInterface;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Plugins
{
    /** @var array An array of all plugins and their capabilities. */
    protected array $plugins = [];

    protected Inflector $inflector;

    public function __construct(string $baseDir)
    {
        $this->inflector = InflectorFactory::create()
            ->build();

        $this->loadDirectory($baseDir);
    }

    public function loadDirectory(string $dir): void
    {
        $plugins = (new Finder())
            ->ignoreUnreadableDirs()
            ->directories()
            ->depth('== 0')
            ->in($dir);

        foreach ($plugins as $plugin_dir) {
            /** @var SplFileInfo $plugin_dir */
            $plugin_prefix = $plugin_dir->getRelativePathname();
            $plugin_namespace = 'Plugin\\' . $this->inflector->classify($plugin_prefix) . '\\';

            $this->plugins[$plugin_prefix] = [
                'namespace' => $plugin_namespace,
                'path' => $plugin_dir->getPathname(),
            ];
        }
    }

    /**
     * Register or override any services contained in the global Dependency Injection container.
     *
     * @param array $diDefinitions
     *
     * @return mixed[]
     */
    public function registerServices(array $diDefinitions = []): array
    {
        foreach ($this->plugins as $plugin) {
            $plugin_path = $plugin['path'];

            if (is_file($plugin_path . '/services.php')) {
                $services = include $plugin_path . '/services.php';
                $diDefinitions = array_merge($diDefinitions, $services);
            }
        }

        return $diDefinitions;
    }

    /**
     * Register custom events that the plugin overrides with the Event Dispatcher.
     *
     * @param CallableEventDispatcherInterface $dispatcher
     */
    public function registerEvents(CallableEventDispatcherInterface $dispatcher): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin_path = $plugin['path'];

            if (file_exists($plugin_path . '/events.php')) {
                call_user_func(include($plugin_path . '/events.php'), $dispatcher);
            }
        }
    }

    /**
     * @param mixed[] $receivers
     *
     * @return mixed[]
     */
    public function registerMessageQueueReceivers(array $receivers): array
    {
        foreach ($this->plugins as $plugin) {
            $pluginPath = $plugin['path'];

            if (is_file($pluginPath . '/messagequeue.php')) {
                $pluginReceivers = include $pluginPath . '/messagequeue.php';
                $receivers = array_merge($receivers, $pluginReceivers);
            }
        }

        return $receivers;
    }
}
