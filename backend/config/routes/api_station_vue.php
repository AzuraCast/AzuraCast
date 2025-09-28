<?php

declare(strict_types=1);

use App\Controller;
use App\Enums\StationFeatures;
use App\Enums\StationPermissions;
use App\Middleware;
use Slim\Routing\RouteCollectorProxy;

return static function (RouteCollectorProxy $app) {
    $app->group('/vue', function (RouteCollectorProxy $group) {
        $group->get('/files', Controller\Api\Stations\Vue\FilesAction::class)
            ->setName('api:vue:stations:files:index')
            ->add(new Middleware\StationSupportsFeature(StationFeatures::Media))
            ->add(new Middleware\Permissions(StationPermissions::Media, true));

        $group->get('/podcasts', Controller\Api\Stations\Vue\PodcastsAction::class)
            ->setName('api:vue:stations:podcasts:index')
            ->add(new Middleware\StationSupportsFeature(StationFeatures::Podcasts))
            ->add(new Middleware\Permissions(StationPermissions::Podcasts, true));

        $group->get('/profile', Controller\Api\Stations\Vue\ProfileAction::class)
            ->setName('api:vue:stations:profile:index');

        $group->get('/profile/edit', Controller\Api\Stations\Vue\ProfileEditAction::class)
            ->setName('api:vue:stations:profile:edit')
            ->add(new Middleware\Permissions(StationPermissions::Profile, true));

        $group->get('/sftp_users', Controller\Api\Stations\Vue\SftpUsersAction::class)
            ->setName('api:vue:stations:sftp_users:index')
            ->add(new Middleware\StationSupportsFeature(StationFeatures::Sftp))
            ->add(new Middleware\Permissions(StationPermissions::Media, true));

        $group->get('/streamers', Controller\Api\Stations\Vue\StreamersAction::class)
            ->setName('api:vue:stations:streamers:index')
            ->add(new Middleware\StationSupportsFeature(StationFeatures::Streamers))
            ->add(new Middleware\Permissions(StationPermissions::Streamers, true));
    })->add(new Middleware\Permissions(StationPermissions::View, true));
};
