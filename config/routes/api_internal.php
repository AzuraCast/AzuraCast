<?php

declare(strict_types=1);

use App\Controller;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

// Internal API endpoints (called by other programs hosted on the same machine).
return static function (RouteCollectorProxy $group) {
    $group->group(
        '/internal',
        function (RouteCollectorProxy $group) {
            $group->group(
                '/{station_id}',
                function (RouteCollectorProxy $group) {
                    $group->map(
                        ['GET', 'POST'],
                        '/liquidsoap/{action}',
                        Controller\Api\Internal\LiquidsoapAction::class
                    )->setName('api:internal:liquidsoap');

                    // Icecast internal auth functions
                    $group->map(
                        ['GET', 'POST'],
                        '/listener-auth',
                        Controller\Api\Internal\ListenerAuthAction::class
                    )->setName('api:internal:listener-auth');
                }
            )->add(Middleware\GetStation::class);

            $group->post('/sftp-auth', Controller\Api\Internal\SftpAuthAction::class)
                ->setName('api:internal:sftp-auth');

            $group->post('/sftp-event', Controller\Api\Internal\SftpEventAction::class)
                ->setName('api:internal:sftp-event');

            $group->get('/relays', Controller\Api\Internal\RelaysController::class)
                ->setName('api:internal:relays')
                ->add(Middleware\RequireLogin::class);

            $group->post('/relays', Controller\Api\Internal\RelaysController::class . ':updateAction')
                ->add(Middleware\RequireLogin::class);
        }
    );
};
