<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\HasLogViewer;
use App\Controller\SingleActionInterface;
use App\Entity\Api\LogType;
use App\Entity\Station;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Adapters;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/station/{station_id}/logs',
        operationId: 'getStationLogs',
        description: 'Return a list of available logs for the given station.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: General'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_LogType')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/log/{key}',
        operationId: 'getStationLog',
        description: 'View a specific log contents for the given station.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: General'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'key',
                description: 'Log Key from listing return.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Api_LogContents'
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class LogsAction implements SingleActionInterface
{
    use HasLogViewer;

    public function __construct(
        private readonly Adapters $adapters,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string|null $log */
        $log = $params['log'] ?? null;

        $station = $request->getStation();

        $logTypes = $this->getStationLogs($station);

        if (null === $log) {
            $router = $request->getRouter();
            return $response->withJson(
                array_map(
                    function (LogType $row) use ($router, $station): LogType {
                        $row->links = [
                            'self' => $router->named(
                                'api:stations:log',
                                [
                                    'station_id' => $station->getIdRequired(),
                                    'log' => $row->key,
                                ]
                            ),
                        ];
                        return $row;
                    },
                    $logTypes
                ),
            );
        }

        $logTypes = array_column($logTypes, null, 'key');

        if (!isset($logTypes[$log])) {
            throw new Exception('Invalid log file specified.');
        }

        $frontendConfig = $station->getFrontendConfig();
        $filteredTerms = [
            $station->getAdapterApiKey(),
            $frontendConfig->getAdminPassword(),
            $frontendConfig->getRelayPassword(),
            $frontendConfig->getSourcePassword(),
            $frontendConfig->getStreamerPassword(),
        ];

        $logType = $logTypes[$log];

        return $this->streamLogToResponse(
            $request,
            $response,
            $logType->path,
            $logType->tail,
            $filteredTerms
        );
    }

    /**
     * @return LogType[]
     */
    private function getStationLogs(Station $station): array
    {
        return [
            ...$this->adapters->getBackendAdapter($station)?->getLogTypes($station) ?? [],
            ...$this->adapters->getFrontendAdapter($station)?->getLogTypes($station) ?? [],
            new LogType(
                'station_nginx',
                __('Station Nginx Configuration'),
                $station->getRadioConfigDir() . '/nginx.conf',
                false
            ),
        ];
    }
}
