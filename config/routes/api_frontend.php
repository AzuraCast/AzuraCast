<?php

declare(strict_types=1);

use App\Controller;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $group) {
    $group->group(
        '/frontend',
        function (RouteCollectorProxy $group) {
            $group->group(
                '/account',
                function (RouteCollectorProxy $group) {
                    $group->get('/me', Controller\Api\Frontend\Account\GetMeAction::class)
                        ->setName('api:frontend:account:me');

                    $group->put('/me', Controller\Api\Frontend\Account\PutMeAction::class);

                    $group->put('/password', Controller\Api\Frontend\Account\PutPasswordAction::class)
                        ->setName('api:frontend:account:password');

                    $group->get('/two-factor', Controller\Api\Frontend\Account\GetTwoFactorAction::class)
                        ->setName('api:frontend:account:two-factor');

                    $group->put('/two-factor', Controller\Api\Frontend\Account\PutTwoFactorAction::class);

                    $group->delete('/two-factor', Controller\Api\Frontend\Account\DeleteTwoFactorAction::class);

                    $group->get(
                        '/api-keys',
                        Controller\Api\Frontend\Account\ApiKeysController::class . ':listAction'
                    )->setName('api:frontend:api-keys');

                    $group->post(
                        '/api-keys',
                        Controller\Api\Frontend\Account\ApiKeysController::class . ':createAction'
                    );

                    $group->get(
                        '/api-key/{id}',
                        Controller\Api\Frontend\Account\ApiKeysController::class . ':getAction'
                    )->setName('api:frontend:api-key');

                    $group->delete(
                        '/api-key/{id}',
                        Controller\Api\Frontend\Account\ApiKeysController::class . ':deleteAction'
                    );
                }
            );

            $group->group(
                '/dashboard',
                function (RouteCollectorProxy $group) {
                    $group->get('/charts', Controller\Api\Frontend\Dashboard\ChartsAction::class)
                        ->setName('api:frontend:dashboard:charts');

                    $group->get('/notifications', Controller\Api\Frontend\Dashboard\NotificationsAction::class)
                        ->setName('api:frontend:dashboard:notifications');

                    $group->get('/stations', Controller\Api\Frontend\Dashboard\StationsAction::class)
                        ->setName('api:frontend:dashboard:stations');
                }
            );
        }
    )->add(Middleware\RequireLogin::class);
};
