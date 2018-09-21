<?php
namespace App\Provider;

use App\Middleware;
use Pimple\ServiceProviderInterface;
use Pimple\Container;
use App;
use App\Entity;

class MiddlewareProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Middleware\EnableSession::class] = function($di) {
            return new Middleware\EnableSession($di[App\Session::class]);
        };

        $di[Middleware\EnableRouter::class] = function($di) {
            return new Middleware\EnableRouter($di['router']);
        };

        $di[Middleware\EnableView::class] = function($di) {
            return new Middleware\EnableView($di[App\View::class]);
        };

        $di[Middleware\EnforceSecurity::class] = function($di) {
            return new Middleware\EnforceSecurity(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[App\Assets::class]
            );
        };

        $di[Middleware\GetCurrentUser::class] = function($di) {
            return new Middleware\GetCurrentUser(
                $di[App\Auth::class],
                $di[App\Customization::class]
            );
        };

        $di[Middleware\GetStation::class] = function($di) {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $di[\Doctrine\ORM\EntityManager::class];

            /** @var Entity\Repository\StationRepository $station_repo */
            $station_repo = $em->getRepository(Entity\Station::class);

            return new Middleware\GetStation(
                $station_repo,
                $di[App\Radio\Adapters::class]
            );
        };

        $di[Middleware\Permissions::class] = function($di) {
            return new Middleware\Permissions(
                $di[App\Acl::class]
            );
        };

        $di[Middleware\RateLimit::class] = function($di) {
            return new Middleware\RateLimit(
                $di[App\RateLimit::class]
            );
        };

        $di[Middleware\RemoveSlashes::class] = function() {
            return new Middleware\RemoveSlashes;
        };

        /*
         * Module-specific middleware
         */

        $di[Middleware\Module\Admin::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[App\Config::class];

            return new Middleware\Module\Admin(
                $di[App\Acl::class],
                $config->get('admin/dashboard')
            );
        };

        $di[Middleware\Module\Api::class] = function($di) {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $di[\Doctrine\ORM\EntityManager::class];

            /** @var Entity\Repository\ApiKeyRepository $api_repo */
            $api_repo = $em->getRepository(Entity\ApiKey::class);

            return new Middleware\Module\Api(
                $di[App\Session::class],
                $api_repo
            );
        };

        $di[Middleware\Module\Stations::class] = function() {
            return new Middleware\Module\Stations;
        };

        $di[Middleware\Module\StationFiles::class] = function($di) {
            return new Middleware\Module\StationFiles;
        };
    }
}
