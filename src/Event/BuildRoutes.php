<?php
namespace App\Event;

use Slim\App;
use Symfony\Component\EventDispatcher\Event;

class BuildRoutes extends Event
{
    const NAME = 'build-routes';

    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function getApp(): App
    {
        return $this->app;
    }
}
