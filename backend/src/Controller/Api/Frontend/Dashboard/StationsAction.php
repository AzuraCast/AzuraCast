<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Dashboard;

use App\Container\EntityManagerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Controller\SingleActionInterface;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Api\Traits\HasLinks;
use App\Entity\ApiGenerator\NowPlayingApiGenerator;
use App\Entity\Station;
use App\Enums\StationPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/frontend/dashboard/stations',
        operationId: 'getDashboardStations',
        summary: 'List stations that can be managed by the current user account on the dashboard.',
        tags: [OpenApi::TAG_MISC],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        allOf: [
                            new OA\Schema(ref: NowPlaying::class),
                            new OA\Schema(ref: HasLinks::class),
                        ]
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class StationsAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;
    use SettingsAwareTrait;
    use CanSortResults;
    use CanSearchResults;

    public function __construct(
        private readonly NowPlayingApiGenerator $npApiGenerator
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $acl = $request->getAcl();

        /** @var Station[] $stations */
        $stations = array_filter(
            $this->em->getRepository(Station::class)->findBy([
                'is_enabled' => 1,
            ]),
            static function (Station $station) use ($acl) {
                return $acl->isAllowed(StationPermissions::View, $station->id);
            }
        );

        /** @var NowPlaying[] $viewStations */
        $viewStations = array_map(
            fn(Station $station) => $this->npApiGenerator->currentOrEmpty($station),
            $stations
        );

        $viewStations = $this->searchArray(
            $request,
            $viewStations,
            [
                'station.name',
            ]
        );

        $viewStations = $this->sortArray(
            $request,
            $viewStations,
            [
                'name' => 'station.name',
                'listeners' => 'listeners.current',
                'now_playing' => 'is_online',
            ],
            'station.name'
        );

        $paginator = Paginator::fromArray($viewStations, $request);

        $router = $request->getRouter();
        $listenersEnabled = $this->readSettings()->isAnalyticsEnabled();

        $paginator->setPostprocessor(
            function (NowPlaying $np) use ($router, $listenersEnabled, $acl) {
                $links = [
                    'public' => $router->named('public:index', ['station_id' => $np->station->shortcode]),
                    'manage' => $router->named('stations:index:index', ['station_id' => $np->station->id]),
                ];

                if ($listenersEnabled && $acl->isAllowed(StationPermissions::Reports, $np->station->id)) {
                    $links['listeners'] = $router->named(
                        'stations:reports:listeners',
                        ['station_id' => $np->station->id]
                    );
                }

                return [
                    ...get_object_vars($np),
                    'links' => $links,
                ];
            }
        );

        return $paginator->write($response);
    }
}
