<?php

declare(strict_types=1);

use App\Controller;
use App\Enums\GlobalPermissions;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $app) {
    $app->group(
        '/vue',
        function (RouteCollectorProxy $group) {
            $group->get('/backups', Controller\Api\Admin\Vue\BackupsAction::class)
                ->setName('api:vue:admin:backups')
                ->add(new Middleware\Permissions(GlobalPermissions::Backups));

            $group->get('/custom_fields', Controller\Api\Admin\Vue\CustomFieldsAction::class)
                ->setName('api:vue:admin:custom_fields')
                ->add(new Middleware\Permissions(GlobalPermissions::CustomFields));

            $group->get('/logs', Controller\Api\Admin\Vue\LogsAction::class)
                ->setName('api:vue:admin:logs')
                ->add(new Middleware\Permissions(GlobalPermissions::Logs));

            $group->get('/permissions', Controller\Api\Admin\Vue\PermissionsAction::class)
                ->setName('api:vue:admin:permissions')
                ->add(new Middleware\Permissions(GlobalPermissions::All));

            $group->get('/settings', Controller\Api\Admin\Vue\SettingsAction::class)
                ->setName('api:vue:admin:settings')
                ->add(new Middleware\Permissions(GlobalPermissions::Settings));

            $group->get('/stations', Controller\Api\Admin\Vue\StationsAction::class)
                ->setName('api:vue:admin:stations')
                ->add(new Middleware\Permissions(GlobalPermissions::Stations));

            $group->get('/updates', Controller\Api\Admin\Vue\UpdatesAction::class)
                ->setName('api:vue:admin:updates')
                ->add(new Middleware\Permissions(GlobalPermissions::All));

            $group->get('/users', Controller\Api\Admin\Vue\UsersAction::class)
                ->setName('api:vue:admin:users')
                ->add(new Middleware\Permissions(GlobalPermissions::All));
        }
    )->add(new Middleware\Permissions(GlobalPermissions::View))
        ->add(Middleware\RequireLogin::class);
};
