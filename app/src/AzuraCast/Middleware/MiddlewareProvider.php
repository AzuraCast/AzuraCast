<?php
namespace AzuraCast\Middleware;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Entity;

class MiddlewareProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        /*
         * Generic middleware in App namespace
         */

        $di->register(new \App\Middleware\MiddlewareProvider);

        /*
         * App-wide middleware
         */

        $di[\App\Middleware\DebugEcho::class] = function($di) {
            return new \App\Middleware\DebugEcho($di[\Monolog\Logger::class]);
        };

        $di[EnableView::class] = function($di) {
            return new EnableView($di[\App\Mvc\View::class]);
        };

        $di[EnforceSecurity::class] = function($di) {
            return new EnforceSecurity(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[\AzuraCast\Assets::class]
            );
        };

        $di[GetCurrentUser::class] = function($di) {
            return new GetCurrentUser(
                $di[\App\Auth::class],
                $di[\AzuraCast\Customization::class]
            );
        };

        $di[GetStation::class] = function($di) {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $di[\Doctrine\ORM\EntityManager::class];

            /** @var \Entity\Repository\StationRepository $station_repo */
            $station_repo = $em->getRepository(Entity\Station::class);

            return new GetStation(
                $station_repo,
                $di[\AzuraCast\Radio\Adapters::class]
            );
        };

        $di[Permissions::class] = function($di) {
            return new Permissions(
                $di[\AzuraCast\Acl\StationAcl::class]
            );
        };

        $di[RateLimit::class] = function($di) {
            return new RateLimit(
                $di[\AzuraCast\RateLimit::class]
            );
        };

        $di[RemoveSlashes::class] = function() {
            return new RemoveSlashes;
        };

        /*
         * Module-specific middleware
         */

        $di[Module\Admin::class] = function($di) {
            /** @var \App\Config $config */
            $config = $di[\App\Config::class];

            return new Module\Admin(
                $di[\AzuraCast\Acl\StationAcl::class],
                $config->get('admin/dashboard')
            );
        };

        $di[Module\Api::class] = function($di) {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $di[\Doctrine\ORM\EntityManager::class];

            /** @var Entity\Repository\ApiKeyRepository $api_repo */
            $api_repo = $em->getRepository(Entity\ApiKey::class);

            return new Module\Api(
                $di[\App\Session::class],
                $api_repo
            );
        };

        $di[Module\Stations::class] = function() {
            return new Module\Stations;
        };

        $di[Module\StationFiles::class] = function($di) {
            return new Module\StationFiles;
        };
    }
}