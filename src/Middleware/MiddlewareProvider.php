<?php
namespace App\Middleware;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use App;
use App\Entity;

class MiddlewareProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[DebugEcho::class] = function($di) {
            return new DebugEcho($di[\Monolog\Logger::class]);
        };

        $di[EnableSession::class] = function($di) {
            return new EnableSession($di[App\Session::class]);
        };

        $di[EnableView::class] = function($di) {
            return new EnableView($di[App\View::class]);
        };

        $di[EnforceSecurity::class] = function($di) {
            return new EnforceSecurity(
                $di[\Doctrine\ORM\EntityManager::class],
                $di[App\Assets::class]
            );
        };

        $di[GetCurrentUser::class] = function($di) {
            return new GetCurrentUser(
                $di[App\Auth::class],
                $di[App\Customization::class]
            );
        };

        $di[GetStation::class] = function($di) {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $di[\Doctrine\ORM\EntityManager::class];

            /** @var Entity\Repository\StationRepository $station_repo */
            $station_repo = $em->getRepository(Entity\Station::class);

            return new GetStation(
                $station_repo,
                $di[App\Radio\Adapters::class]
            );
        };

        $di[Permissions::class] = function($di) {
            return new Permissions(
                $di[App\Acl::class]
            );
        };

        $di[RateLimit::class] = function($di) {
            return new RateLimit(
                $di[App\RateLimit::class]
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
            $config = $di[App\Config::class];

            return new Module\Admin(
                $di[App\Acl::class],
                $config->get('admin/dashboard')
            );
        };

        $di[Module\Api::class] = function($di) {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $di[\Doctrine\ORM\EntityManager::class];

            /** @var Entity\Repository\ApiKeyRepository $api_repo */
            $api_repo = $em->getRepository(Entity\ApiKey::class);

            return new Module\Api(
                $di[App\Session::class],
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
