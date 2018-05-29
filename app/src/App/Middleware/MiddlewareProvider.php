<?php
namespace App\Middleware;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class MiddlewareProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[DebugEcho::class] = function($di) {
            return new DebugEcho($di[\Monolog\Logger::class]);
        };
    }
}