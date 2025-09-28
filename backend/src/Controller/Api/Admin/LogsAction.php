<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Container\EnvironmentAwareTrait;
use App\Controller\Api\Stations\LogsAction as StationLogsAction;
use App\Entity\Api\Admin\LogList;
use App\Entity\Api\Admin\StationLogList;
use App\Entity\Api\LogContents;
use App\Entity\Api\LogType;
use App\Entity\Repository\StationRepository;
use App\Enums\StationPermissions;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Adapters;
use App\Service\ServiceControl;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/logs',
        operationId: 'adminListLogs',
        summary: 'List all available log types for viewing.',
        tags: [OpenApi::TAG_ADMIN],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: LogList::class
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/admin/log/{key}',
        operationId: 'adminViewLog',
        summary: 'View a specific log contents.',
        tags: [OpenApi::TAG_ADMIN],
        parameters: [
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
    ),
]
final class LogsAction extends StationLogsAction
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly ServiceControl $serviceControl,
        private readonly StationRepository $stationRepo,
        Adapters $adapters,
    ) {
        parent::__construct($adapters);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string|null $log */
        $log = $params['log'] ?? null;

        $logTypes = $this->getGlobalLogs();

        if (null === $log) {
            $router = $request->getRouter();

            $globalLogs = array_map(
                function (LogType $row) use ($router): LogType {
                    $row->links = [
                        'self' => $router->named(
                            'api:admin:log',
                            [
                                'log' => $row->key,
                            ]
                        ),
                    ];
                    return $row;
                },
                $logTypes
            );

            $acl = $request->getAcl();
            $stationLogs = [];
            foreach ($this->stationRepo->iterateEnabledStations() as $station) {
                if (!$acl->isAllowed(StationPermissions::Logs, $station)) {
                    continue;
                }

                $stationLogList = array_map(
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
                    $this->getStationLogs($station)
                );

                $stationLogs[] = new StationLogList(
                    id: $station->id,
                    name: $station->name,
                    logs: $stationLogList
                );
            }

            return $response->withJson(
                new LogList(
                    globalLogs: $globalLogs,
                    stationLogs: $stationLogs
                )
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
            $logType->tail
        );
    }

    /**
     * @return LogType[]
     */
    private function getGlobalLogs(): array
    {
        $tempDir = $this->environment->getTempDirectory();
        $logPaths = [
            new LogType(
                'azuracast_log',
                __('AzuraCast Application Log'),
                $tempDir . '/app-' . gmdate('Y-m-d') . '.log',
                true,
            ),
            new LogType(
                'azuracast_nowplaying_log',
                __('AzuraCast Now Playing Log'),
                $tempDir . '/app_nowplaying-' . gmdate('Y-m-d') . '.log',
                true,
            ),
            new LogType(
                'azuracast_sync_log',
                __('AzuraCast Synchronized Task Log'),
                $tempDir . '/app_sync-' . gmdate('Y-m-d') . '.log',
                true
            ),
            new LogType(
                'azuracast_worker_log',
                __('AzuraCast Queue Worker Log'),
                $tempDir . '/app_worker-' . gmdate('Y-m-d') . '.log',
                true
            ),
        ];

        if ($this->environment->isDocker()) {
            $langServiceLog = __('Service Log: %s (%s)');

            foreach ($this->serviceControl->getServiceNames() as $serviceKey => $serviceName) {
                $logPath = $tempDir . '/service_' . $serviceKey . '.log';

                if (is_file($logPath)) {
                    $logPaths[] = new LogType(
                        'service_' . $serviceKey,
                        sprintf($langServiceLog, $serviceKey, $serviceName),
                        $logPath,
                        true,
                    );
                }
            }
        } else {
            $logPaths[] = new LogType(
                'nginx_access',
                __('Nginx Access Log'),
                $tempDir . '/access.log',
                true
            );
            $logPaths[] = new LogType(
                'nginx_error',
                __('Nginx Error Log'),
                $tempDir . '/error.log',
                true,
            );
            $logPaths[] = new LogType(
                'php',
                __('PHP Application Log'),
                $tempDir . '/php_errors.log',
                true,
            );
            $logPaths[] = new LogType(
                'supervisord',
                __('Supervisord Log'),
                $tempDir . '/supervisord.log',
                true,
            );
        }

        $liquidsoapDir = $this->environment->getParentDirectory() . '/liquidsoap';

        $logPaths[] = new LogType(
            'azuracast_liq_functions',
            __('AzuraCast Common Liquidsoap Functions'),
            $liquidsoapDir . '/azuracast.liq',
            false,
        );

        $logPaths[] = new LogType(
            'azuracast_liq_autocue',
            __('AutoCue Liquidsoap Functions'),
            $liquidsoapDir . '/autocue.liq',
            false,
        );

        return $logPaths;
    }
}
