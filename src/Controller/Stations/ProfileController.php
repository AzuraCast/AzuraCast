<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Acl;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\VueComponent\StationFormComponent;
use DI\FactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class ProfileController
{
    protected string $csrf_namespace = 'stations_profile';

    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\StationRepository $stationRepo,
        protected FactoryInterface $factory
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $view = $request->getView();

        if (!$station->getIsEnabled()) {
            return $view->renderToResponse($response, 'stations/profile/disabled');
        }

        // Statistics about backend playback.
        $num_songs = $this->em->createQuery(
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

        $num_playlists = $this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(sp.id)
                FROM App\Entity\StationPlaylist sp
                WHERE sp.station_id = :station_id
            DQL
        )->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        $csrf = $request->getCsrf()->generate($this->csrf_namespace);

        $backend = $request->getStationBackend();
        $frontend = $request->getStationFrontend();

        $backendConfig = $station->getBackendConfig();
        $frontendConfig = $station->getFrontendConfig();

        $acl = $request->getAcl();
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsProfile',
            id: 'profile',
            title: __('Profile'),
            props: [
                // Common
                'backendType' => $station->getBackendType(),
                'frontendType' => $station->getFrontendType(),
                'stationTimeZone' => $station->getTimezone(),
                'stationSupportsRequests' => $backend->supportsRequests(),
                'stationSupportsStreamers' => $backend->supportsStreamers(),
                'enableRequests' => $station->getEnableRequests(),
                'enableStreamers' => $station->getEnableStreamers(),
                'enablePublicPage' => $station->getEnablePublicPage(),
                'enableOnDemand' => $station->getEnableOnDemand(),
                'profileApiUri' => (string)$router->fromHere('api:stations:profile'),

                // ACL
                'userCanManageMedia' => $acl->isAllowed(Acl::STATION_MEDIA, $station->getId()),
                'userCanManageBroadcasting' => $acl->isAllowed(Acl::STATION_BROADCASTING, $station->getId()),
                'userCanManageProfile' => $acl->isAllowed(Acl::STATION_PROFILE, $station->getId()),
                'userCanManageReports' => $acl->isAllowed(Acl::STATION_REPORTS, $station->getId()),
                'userCanManageStreamers' => $acl->isAllowed(Acl::STATION_STREAMERS, $station->getId()),

                // Header
                'stationName' => $station->getName(),
                'stationDescription' => $station->getDescription(),
                'manageProfileUri' => (string)$router->fromHere('stations:profile:edit'),

                // Now Playing
                'backendSkipSongUri' => (string)$router->fromHere('api:stations:backend', ['do' => 'skip']),
                'backendDisconnectStreamerUri' => (string)$router->fromHere(
                    'api:stations:backend',
                    ['do' => 'disconnect']
                ),

                // Requests
                'requestsViewUri' => (string)$router->fromHere('stations:reports:requests'),
                'requestsToggleUri' => (string)$router->fromHere(
                    'stations:profile:toggle',
                    ['feature' => 'requests', 'csrf' => $csrf]
                ),

                // Streamers
                'streamersViewUri' => (string)$router->fromHere('stations:streamers:index'),
                'streamersToggleUri' => (string)$router->fromHere(
                    'stations:profile:toggle',
                    ['feature' => 'streamers', 'csrf' => $csrf]
                ),

                // Public Pages
                'publicPageUri' => (string)$router->named(
                    route_name: 'public:index',
                    route_params: ['station_id' => $station->getShortName()],
                    absolute: true
                ),
                'publicPageEmbedUri' => (string)$router->named(
                    route_name: 'public:index',
                    route_params: ['station_id' => $station->getShortName(), 'embed' => 'embed'],
                    absolute: true
                ),
                'publicWebDjUri' => (string)$router->named(
                    route_name: 'public:dj',
                    route_params: ['station_id' => $station->getShortName()],
                    absolute: true
                ),
                'publicOnDemandUri' => (string)$router->named(
                    route_name: 'public:ondemand',
                    route_params: ['station_id' => $station->getShortName()],
                    absolute: true
                ),
                'publicPodcastsUri' => (string)$router->named(
                    route_name: 'public:podcasts',
                    route_params: ['station_id' => $station->getShortName()],
                    absolute: true
                ),
                'publicScheduleUri' => (string)$router->named(
                    route_name: 'public:schedule',
                    route_params: ['station_id' => $station->getShortName()],
                    absolute: true
                ),
                'publicOnDemandEmbedUri' => (string)$router->named(
                    route_name: 'public:ondemand',
                    route_params: ['station_id' => $station->getShortName(), 'embed' => 'embed'],
                    absolute: true
                ),
                'publicRequestEmbedUri' => (string)$router->named(
                    route_name: 'public:embedrequests',
                    route_params: ['station_id' => $station->getShortName()],
                    absolute: true
                ),
                'publicHistoryEmbedUri' => (string)$router->named(
                    route_name: 'public:history',
                    route_params: ['station_id' => $station->getShortName()],
                    absolute: true
                ),
                'publicScheduleEmbedUri' => (string)$router->named(
                    route_name: 'public:schedule',
                    route_params: ['station_id' => $station->getShortName(), 'embed' => 'embed'],
                    absolute: true
                ),

                'togglePublicPageUri'    => (string)$router->fromHere(
                    route_name: 'stations:profile:toggle',
                    route_params: ['feature' => 'public', 'csrf' => $csrf]
                ),

                // Frontend
                'frontendAdminUri'       => (string)$frontend->getAdminUrl($station, $router->getBaseUrl()),
                'frontendAdminPassword'  => $frontendConfig->getAdminPassword(),
                'frontendSourcePassword' => $frontendConfig->getSourcePassword(),
                'frontendRelayPassword'  => $frontendConfig->getRelayPassword(),
                'frontendRestartUri'     => (string)$router->fromHere('api:stations:frontend', ['do' => 'restart']),
                'frontendStartUri'       => (string)$router->fromHere('api:stations:frontend', ['do' => 'start']),
                'frontendStopUri'        => (string)$router->fromHere('api:stations:frontend', ['do' => 'stop']),

                // Backend
                'numSongs'               => (int)$num_songs,
                'numPlaylists'           => (int)$num_playlists,
                'manageMediaUri'         => (string)$router->fromHere('stations:files:index'),
                'managePlaylistsUri'     => (string)$router->fromHere('stations:playlists:index'),
                'backendRestartUri'      => (string)$router->fromHere('api:stations:backend', ['do' => 'restart']),
                'backendStartUri'        => (string)$router->fromHere('api:stations:backend', ['do' => 'start']),
                'backendStopUri'         => (string)$router->fromHere('api:stations:backend', ['do' => 'stop']),
            ],
        );
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        StationFormComponent $stationFormComponent
    ): ResponseInterface {
        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsProfileEdit',
            id: 'edit-profile',
            title: __('Edit Profile'),
            props: array_merge(
                $stationFormComponent->getProps($request),
                [
                    'editUrl' => (string)$request->getRouter()->fromHere('api:stations:profile:edit'),
                    'continueUrl' => (string)$request->getRouter()->fromHere('stations:profile:index'),
                ]
            )
        );
    }

    public function toggleAction(
        ServerRequest $request,
        Response $response,
        string $feature,
        string $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $station = $request->getStation();

        switch ($feature) {
            case 'requests':
                $station->setEnableRequests(!$station->getEnableRequests());
                break;

            case 'streamers':
                $station->setEnableStreamers(!$station->getEnableStreamers());
                break;

            case 'public':
                $station->setEnablePublicPage(!$station->getEnablePublicPage());
                break;
        }

        $this->em->persist($station);
        $this->em->flush();

        return $response->withRedirect((string)$request->getRouter()->fromHere('stations:profile:index'));
    }
}
