<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Enums\StationPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use App\VueComponent\StationFormComponent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

final class ProfileController
{
    private const CSRF_NAMESPACE = 'stations_profile';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StationFormComponent $stationFormComponent,
        private readonly Adapters $adapters,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();
        $view = $request->getView();

        if (!$station->getIsEnabled()) {
            return $view->renderToResponse($response, 'stations/profile_disabled');
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

        $csrf = $request->getCsrf()->generate(self::CSRF_NAMESPACE);

        $backendEnum = $station->getBackendTypeEnum();

        $frontend = $this->adapters->getFrontendAdapter($station);
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
                'stationSupportsRequests' => $backendEnum->isEnabled(),
                'stationSupportsStreamers' => $backendEnum->isEnabled(),
                'enableRequests' => $station->getEnableRequests(),
                'enableStreamers' => $station->getEnableStreamers(),
                'enablePublicPage' => $station->getEnablePublicPage(),
                'enableOnDemand' => $station->getEnableOnDemand(),
                'profileApiUri' => $router->fromHere('api:stations:profile'),
                'hasStarted' => $station->getHasStarted(),

                // ACL
                'userCanManageMedia' => $acl->isAllowed(StationPermissions::Media, $station->getId()),
                'userCanManageBroadcasting' => $acl->isAllowed(StationPermissions::Broadcasting, $station->getId()),
                'userCanManageProfile' => $acl->isAllowed(StationPermissions::Profile, $station->getId()),
                'userCanManageReports' => $acl->isAllowed(StationPermissions::Reports, $station->getId()),
                'userCanManageStreamers' => $acl->isAllowed(StationPermissions::Streamers, $station->getId()),

                // Header
                'stationName' => $station->getName(),
                'stationDescription' => $station->getDescription(),
                'manageProfileUri' => $router->fromHere('stations:profile:edit'),

                // Now Playing
                'backendSkipSongUri' => $router->fromHere('api:stations:backend', ['do' => 'skip']),
                'backendDisconnectStreamerUri' => $router->fromHere(
                    'api:stations:backend',
                    ['do' => 'disconnect']
                ),

                // Requests
                'requestsViewUri' => $router->fromHere('stations:reports:requests'),
                'requestsToggleUri' => $router->fromHere(
                    'stations:profile:toggle',
                    ['feature' => 'requests', 'csrf' => $csrf]
                ),

                // Streamers
                'streamersViewUri' => $router->fromHere('stations:streamers:index'),
                'streamersToggleUri' => $router->fromHere(
                    'stations:profile:toggle',
                    ['feature' => 'streamers', 'csrf' => $csrf]
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

                'togglePublicPageUri' => $router->fromHere(
                    routeName: 'stations:profile:toggle',
                    routeParams: ['feature' => 'public', 'csrf' => $csrf]
                ),

                // Frontend
                'frontendAdminUri' => (string)$frontend?->getAdminUrl($station, $router->getBaseUrl()),
                'frontendAdminPassword' => $frontendConfig->getAdminPassword(),
                'frontendSourcePassword' => $frontendConfig->getSourcePassword(),
                'frontendRelayPassword' => $frontendConfig->getRelayPassword(),
                'frontendRestartUri' => $router->fromHere('api:stations:frontend', ['do' => 'restart']),
                'frontendStartUri' => $router->fromHere('api:stations:frontend', ['do' => 'start']),
                'frontendStopUri' => $router->fromHere('api:stations:frontend', ['do' => 'stop']),

                // Backend
                'numSongs' => (int)$num_songs,
                'numPlaylists' => (int)$num_playlists,
                'manageMediaUri' => $router->fromHere('stations:files:index'),
                'managePlaylistsUri' => $router->fromHere('stations:playlists:index'),
                'backendRestartUri' => $router->fromHere('api:stations:backend', ['do' => 'restart']),
                'backendStartUri' => $router->fromHere('api:stations:backend', ['do' => 'start']),
                'backendStopUri' => $router->fromHere('api:stations:backend', ['do' => 'stop']),
            ],
        );
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsProfileEdit',
            id: 'edit-profile',
            title: __('Edit Profile'),
            props: array_merge(
                $this->stationFormComponent->getProps($request),
                [
                    'editUrl' => $router->fromHere('api:stations:profile:edit'),
                    'continueUrl' => $router->fromHere('stations:profile:index'),
                ]
            )
        );
    }

    public function toggleAction(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $feature,
        string $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, self::CSRF_NAMESPACE);

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

        return $response->withRedirect($request->getRouter()->fromHere('stations:profile:index'));
    }
}
