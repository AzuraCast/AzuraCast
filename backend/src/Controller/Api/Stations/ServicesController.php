<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Api\StationServiceStatus;
use App\Entity\Api\Status;
use App\Entity\Station;
use App\Exception\Supervisor\NotRunningException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Nginx\Nginx;
use App\OpenApi;
use App\Radio\Adapters;
use App\Radio\Configuration;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/station/{station_id}/status',
        operationId: 'getServiceStatus',
        summary: 'Retrieve the current status of all serivces associated with the radio broadcast.',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: StationServiceStatus::class
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/restart',
        operationId: 'restartServices',
        summary: 'Restart all services associated with the radio broadcast.',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/frontend/{action}',
        operationId: 'doFrontendServiceAction',
        summary: 'Perform service control actions on the radio frontend (Icecast, Shoutcast, etc.)',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'action',
                description: 'The action to perform.',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                    default: 'restart',
                    enum: [
                        'start',
                        'stop',
                        'reload',
                        'restart',
                    ]
                )
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/backend/{action}',
        operationId: 'doBackendServiceAction',
        summary: 'Perform service control actions on the radio backend (Liquidsoap)',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'action',
                description: 'The action to perform.',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                    default: 'restart',
                    enum: [
                        'skip',
                        'disconnect',
                        'start',
                        'stop',
                        'reload',
                        'restart',
                    ]
                )
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class ServicesController
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly Nginx $nginx,
        private readonly Adapters $adapters,
    ) {
    }

    public function statusAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();

        $backend = $this->adapters->getBackendAdapter($station);
        $frontend = $this->adapters->getFrontendAdapter($station);

        return $response->withJson(
            new StationServiceStatus(
                backendRunning: null !== $backend && $backend->isRunning($station),
                frontendRunning: null !== $frontend && $frontend->isRunning($station),
            )
        );
    }

    public function reloadAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $this->reloadOrRestartStation($request->getStation(), true);

        return $response->withJson(new Status(true, __('Station reloaded.')));
    }

    public function restartAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $this->reloadOrRestartStation($request->getStation(), false);

        return $response->withJson(new Status(true, __('Station restarted.')));
    }

    protected function reloadOrRestartStation(
        Station $station,
        bool $attemptReload
    ): void {
        $station->has_started = true;
        $this->em->persist($station);
        $this->em->flush();

        $this->configuration->writeConfiguration(
            station: $station,
            forceRestart: true,
            attemptReload: $attemptReload
        );

        $this->nginx->writeConfiguration($station);
    }

    public function frontendAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $do */
        $do = $params['do'] ?? 'restart';

        $station = $request->getStation();
        $frontend = $this->adapters->requireFrontendAdapter($station);

        switch ($do) {
            case 'stop':
                $frontend->stop($station);

                return $response->withJson(new Status(true, __('Service stopped.')));

            case 'start':
                $frontend->start($station);

                return $response->withJson(new Status(true, __('Service started.')));

            case 'reload':
                $frontend->write($station);
                $frontend->reload($station);

                return $response->withJson(new Status(true, __('Service reloaded.')));

            case 'restart':
            default:
                try {
                    $frontend->stop($station);
                } catch (NotRunningException) {
                }

                $frontend->write($station);
                $frontend->start($station);

                return $response->withJson(new Status(true, __('Service restarted.')));
        }
    }

    public function backendAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $do */
        $do = $params['do'] ?? 'restart';

        $station = $request->getStation();
        $backend = $this->adapters->requireBackendAdapter($station);

        switch ($do) {
            case 'skip':
                $backend->skip($station);

                return $response->withJson(new Status(true, __('Song skipped.')));

            case 'disconnect':
                $backend->disconnectStreamer($station);

                return $response->withJson(new Status(true, __('Streamer disconnected.')));

            case 'stop':
                $backend->stop($station);

                return $response->withJson(new Status(true, __('Service stopped.')));

            case 'start':
                $backend->start($station);

                return $response->withJson(new Status(true, __('Service started.')));

            case 'reload':
                $backend->write($station);
                $backend->reload($station);

                return $response->withJson(new Status(true, __('Service reloaded.')));

            case 'restart':
            default:
                try {
                    $backend->stop($station);
                } catch (NotRunningException) {
                }

                $backend->write($station);
                $backend->start($station);

                return $response->withJson(new Status(true, __('Service restarted.')));
        }
    }
}
