<?php

declare(strict_types=1);

namespace App\Event;

use Slim\App;
use Symfony\Contracts\EventDispatcher\Event;

class BuildRoutes extends Event
{
    public function __construct(
        protected App $app
    ) {
    }

    public function getApp(): App
    {
        return $this->app;
    }
}
