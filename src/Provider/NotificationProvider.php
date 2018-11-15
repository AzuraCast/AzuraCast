<?php
namespace App\Provider;

use App\Notification;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class NotificationProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Notification\Manager::class] = function ($di) {
            return new Notification\Manager(
                $di[\App\Acl::class],
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\Monolog\Logger::class]
            );
        };
    }
}
