<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Api\Error;
use App\Entity\Api\StationServiceStatus;
use App\Entity\Api\Status;
use App\Exception\Supervisor\NotRunningException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Nginx\Nginx;
use App\OpenApi;
use App\Radio\Adapters;
use App\Radio\Configuration;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Throwable;

#[
    OA\Get(
        path: '/station/{station_id}/status',
        operationId: 'getServiceStatus',
        description: 'Retrieve the current status of all serivces associated with the radio broadcast.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Service Control'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Api_StationServiceStatus'
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/restart',
        operationId: 'restartServices',
        description: 'Restart all services associated with the radio broadcast.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Service Control'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/frontend/{action}',
        operationId: 'doFrontendServiceAction',
        description: 'Perform service control actions on the radio frontend (Icecast, Shoutcast, etc.)',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Service Control'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'action',
                description: 'The action to perform (start, stop, restart)',
                in: 'path',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'restart')
            ),
        ],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/backend/{action}',
        operationId: 'doBackendServiceAction',
        description: 'Perform service control actions on the radio backend (Liquidsoap)',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Service Control'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'action',
                description: 'The action to perform (for all: start, stop, restart, skip, disconnect)',
                in: 'path',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'restart')
            ),
        ],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
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
                null !== $backend && $backend->isRunning($station),
                null !== $frontend && $frontend->isRunning($station),
                $station->getHasStarted(),
                $station->getNeedsRestart()
            )
        );
    }

    public function reloadAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        // Reloading attempts to update configuration without restarting broadcasting, if possible and supported.
        $station = $request->getStation();

        try {
            $station->setHasStarted(true);
            $this->em->persist($station);
            $this->em->flush();

            $this->configuration->writeConfiguration(
                station: $station,
                forceRestart: true
            );

            $this->nginx->writeConfiguration($station);
        } catch (Throwable $e) {
            return $response->withJson(Error::fromException($e));
        }

        return $response->withJson(new Status(true, __('Station reloaded.')));
    }

    public function restartAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        // Restarting will always shut down and restart any services.
        $station = $request->getStation();

        try {
            $station->setHasStarted(true);
            $this->em->persist($station);
            $this->em->flush();

            $this->configuration->writeConfiguration(
                station: $station,
                forceRestart: true,
                attemptReload: false
            );

            $this->nginx->writeConfiguration($station);
        } catch (Throwable $e) {
            return $response->withJson(Error::fromException($e));
        }

        return $response->withJson(new Status(true, __('Station restarted.')));
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
