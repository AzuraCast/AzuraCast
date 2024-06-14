<?php

declare(strict_types=1);

namespace App\Event;

use App\AppFactory;
use Psr\Container\ContainerInterface;
use Slim\App;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @phpstan-import-type AppWithContainer from AppFactory
 */
final class BuildRoutes extends Event
{
    /**
     * @param AppWithContainer $app
     * @param ContainerInterface $container
     */
    public function __construct(
        private readonly App $app,
        private readonly ContainerInterface $container
    ) {
    }

    /**
     * @return AppWithContainer
     */
    public function getApp(): App
    {
        return $this->app;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
