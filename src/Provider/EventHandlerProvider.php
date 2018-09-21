<?php
namespace App\Provider;

use App\EventHandler;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class EventHandlerProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[EventHandler\DefaultRoutes::class] = function($di) {
            return new EventHandler\DefaultRoutes(APP_INCLUDE_ROOT.'/config/routes.php');
        };

        $di[EventHandler\DefaultView::class] = function($di) {
            return new EventHandler\DefaultView($di);
        };

        $di[EventHandler\DefaultNowPlaying::class] = function($di) {
            return new EventHandler\DefaultNowPlaying();
        };
    }
}
