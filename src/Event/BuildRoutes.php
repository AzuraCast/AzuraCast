<?php

namespace App\Event;

use Slim\App;
use Symfony\Contracts\EventDispatcher\Event;

class BuildRoutes extends Event
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function getApp(): App
    {
        return $this->app;
    }
}
