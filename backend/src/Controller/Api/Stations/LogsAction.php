<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\HasLogViewer;
use App\Controller\SingleActionInterface;
use App\Entity\Api\LogContents;
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
        summary: 'Return a list of available logs for the given station.',
        tags: [OpenApi::TAG_STATIONS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: LogType::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/log/{key}',
        operationId: 'getStationLog',
        summary: 'View a specific log contents for the given station.',
        tags: [OpenApi::TAG_STATIONS],
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
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: LogContents::class
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
class LogsAction implements SingleActionInterface
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
                                    'station_id' => $station->id,
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

        $logType = $logTypes[$log];

        return $this->streamLogToResponse(
            $request,
            $response,
            $logType->path,
            $logType->tail,
            $station->getFilteredPasswords()
        );
    }

    /**
     * @return LogType[]
     */
    protected function getStationLogs(Station $station): array
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
