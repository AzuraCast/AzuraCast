<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use App\VueComponent\NowPlayingComponent;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class ProfileAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly NowPlayingComponent $nowPlayingComponent,
        private readonly Adapters $adapters,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->is_enabled) {
            throw new RuntimeException('The station profile is disabled.');
        }

        // Statistics about backend playback.
        $numSongs = $this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(sm.id)
                FROM App\Entity\StationMedia sm
                LEFT JOIN sm.playlists spm
                LEFT JOIN spm.playlist sp
                WHERE sp.id IS NOT NULL
                AND sp.station = :station
            DQL
        )->setParameter('station', $station)
            ->getSingleScalarResult();

        $numPlaylists = $this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(sp.id)
                FROM App\Entity\StationPlaylist sp
                WHERE sp.station = :station
            DQL
        )->setParameter('station', $station)
            ->getSingleScalarResult();

        $backendEnum = $station->backend_type;

        $frontend = $this->adapters->getFrontendAdapter($station);
        $frontendConfig = $station->frontend_config;

        $router = $request->getRouter();

        return $response->withJson([
            ...$this->nowPlayingComponent->getDataProps($request),

            // Common
            'backendType' => $station->backend_type->value,
            'frontendType' => $station->frontend_type->value,
            'stationSupportsRequests' => $backendEnum->isEnabled(),
            'stationSupportsStreamers' => $backendEnum->isEnabled(),
            'enableRequests' => $station->enable_requests,
            'enableStreamers' => $station->enable_streamers,
            'enablePublicPage' => $station->enable_public_page,
            'enableOnDemand' => $station->enable_on_demand,
            'profileApiUri' => $router->fromHere('api:stations:profile'),
            'hasStarted' => $station->has_started,

            // Header
            'stationName' => $station->name,
            'stationDescription' => $station->description,

            // Now Playing
            'backendSkipSongUri' => $router->fromHere('api:stations:backend', ['do' => 'skip']),
            'backendDisconnectStreamerUri' => $router->fromHere(
                'api:stations:backend',
                ['do' => 'disconnect']
            ),

            // Public Pages
            'publicPageUri' => $router->named(
                routeName: 'public:index',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            ),
            'publicPageEmbedUri' => $router->named(
                routeName: 'public:index',
                routeParams: ['station_id' => $station->short_name, 'embed' => 'embed'],
                absolute: true
            ),
            'publicWebDjUri' => $router->named(
                routeName: 'public:dj',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            ),
            'publicOnDemandUri' => $router->named(
                routeName: 'public:ondemand',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            ),
            'publicPodcastsUri' => $router->named(
                routeName: 'public:podcasts',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            ),
            'publicScheduleUri' => $router->named(
                routeName: 'public:schedule',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            ),
            'publicOnDemandEmbedUri' => $router->named(
                routeName: 'public:ondemand',
                routeParams: ['station_id' => $station->short_name, 'embed' => 'embed'],
                absolute: true
            ),
            'publicRequestEmbedUri' => $router->named(
                routeName: 'public:embedrequests',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            ),
            'publicHistoryEmbedUri' => $router->named(
                routeName: 'public:history',
                routeParams: ['station_id' => $station->short_name],
                absolute: true
            ),
            'publicScheduleEmbedUri' => $router->named(
                routeName: 'public:schedule',
                routeParams: ['station_id' => $station->short_name, 'embed' => 'embed'],
                absolute: true
            ),
            'publicPodcastsEmbedUri' => $router->named(
                routeName: 'public:podcasts',
                routeParams: ['station_id' => $station->short_name],
                queryParams: ['embed' => 'true'],
                absolute: true
            ),

            // Frontend
            'frontendAdminUri' => (string)$frontend?->getAdminUrl($station, $router->getBaseUrl()),
            'frontendAdminPassword' => $frontendConfig->admin_pw,
            'frontendSourcePassword' => $frontendConfig->source_pw,
            'frontendRelayPassword' => $frontendConfig->relay_pw,
            'frontendPort' => $frontendConfig->port,
            'frontendRestartUri' => $router->fromHere('api:stations:frontend', ['do' => 'restart']),
            'frontendStartUri' => $router->fromHere('api:stations:frontend', ['do' => 'start']),
            'frontendStopUri' => $router->fromHere('api:stations:frontend', ['do' => 'stop']),

            // Backend
            'numSongs' => (int)$numSongs,
            'numPlaylists' => (int)$numPlaylists,
            'backendRestartUri' => $router->fromHere('api:stations:backend', ['do' => 'restart']),
            'backendStartUri' => $router->fromHere('api:stations:backend', ['do' => 'start']),
            'backendStopUri' => $router->fromHere('api:stations:backend', ['do' => 'stop']),
        ]);
    }
}
