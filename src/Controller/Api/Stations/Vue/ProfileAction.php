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

        if (!$station->getIsEnabled()) {
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
                AND sp.station_id = :station_id
            DQL
        )->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        $numPlaylists = $this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(sp.id)
                FROM App\Entity\StationPlaylist sp
                WHERE sp.station_id = :station_id
            DQL
        )->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        $backendEnum = $station->getBackendType();

        $frontend = $this->adapters->getFrontendAdapter($station);
        $frontendConfig = $station->getFrontendConfig();

        $router = $request->getRouter();

        return $response->withJson([
            ...$this->nowPlayingComponent->getDataProps($request),

            // Common
            'backendType' => $station->getBackendType()->value,
            'frontendType' => $station->getFrontendType()->value,
            'stationSupportsRequests' => $backendEnum->isEnabled(),
            'stationSupportsStreamers' => $backendEnum->isEnabled(),
            'enableRequests' => $station->getEnableRequests(),
            'enableStreamers' => $station->getEnableStreamers(),
            'enablePublicPage' => $station->getEnablePublicPage(),
            'enableOnDemand' => $station->getEnableOnDemand(),
            'profileApiUri' => $router->fromHere('api:stations:profile'),
            'hasStarted' => $station->getHasStarted(),

            // Header
            'stationName' => $station->getName(),
            'stationDescription' => $station->getDescription(),

            // Now Playing
            'backendSkipSongUri' => $router->fromHere('api:stations:backend', ['do' => 'skip']),
            'backendDisconnectStreamerUri' => $router->fromHere(
                'api:stations:backend',
                ['do' => 'disconnect']
            ),

            // Public Pages
            'publicPageUri' => $router->named(
                routeName: 'public:index',
                routeParams: ['station_id' => $station->getShortName()],
                absolute: true
            ),
            'publicPageEmbedUri' => $router->named(
                routeName: 'public:index',
                routeParams: ['station_id' => $station->getShortName(), 'embed' => 'embed'],
                absolute: true
            ),
            'publicWebDjUri' => $router->named(
                routeName: 'public:dj',
                routeParams: ['station_id' => $station->getShortName()],
                absolute: true
            ),
            'publicOnDemandUri' => $router->named(
                routeName: 'public:ondemand',
                routeParams: ['station_id' => $station->getShortName()],
                absolute: true
            ),
            'publicPodcastsUri' => $router->named(
                routeName: 'public:podcasts',
                routeParams: ['station_id' => $station->getShortName()],
                absolute: true
            ),
            'publicScheduleUri' => $router->named(
                routeName: 'public:schedule',
                routeParams: ['station_id' => $station->getShortName()],
                absolute: true
            ),
            'publicOnDemandEmbedUri' => $router->named(
                routeName: 'public:ondemand',
                routeParams: ['station_id' => $station->getShortName(), 'embed' => 'embed'],
                absolute: true
            ),
            'publicRequestEmbedUri' => $router->named(
                routeName: 'public:embedrequests',
                routeParams: ['station_id' => $station->getShortName()],
                absolute: true
            ),
            'publicHistoryEmbedUri' => $router->named(
                routeName: 'public:history',
                routeParams: ['station_id' => $station->getShortName()],
                absolute: true
            ),
            'publicScheduleEmbedUri' => $router->named(
                routeName: 'public:schedule',
                routeParams: ['station_id' => $station->getShortName(), 'embed' => 'embed'],
                absolute: true
            ),

            // Frontend
            'frontendAdminUri' => (string)$frontend?->getAdminUrl($station, $router->getBaseUrl()),
            'frontendAdminPassword' => $frontendConfig->getAdminPassword(),
            'frontendSourcePassword' => $frontendConfig->getSourcePassword(),
            'frontendRelayPassword' => $frontendConfig->getRelayPassword(),
            'frontendPort' => $frontendConfig->getPort(),
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
