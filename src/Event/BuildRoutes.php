<?php
namespace App\Event;

use Slim\App;
use Symfony\Contracts\EventDispatcher\Event;

class BuildRoutes extends Event
{
    /**
     * @var App
     */
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
