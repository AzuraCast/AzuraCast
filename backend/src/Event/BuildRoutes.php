<?php

declare(strict_types=1);

namespace App\Event;

use Psr\Container\ContainerInterface;
use Slim\App;
use Symfony\Contracts\EventDispatcher\Event;

final class BuildRoutes extends Event
{
    public function __construct(
        private readonly App $app,
        private readonly ContainerInterface $container
    ) {
    }

    public function getApp(): App
    {
        return $this->app;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
