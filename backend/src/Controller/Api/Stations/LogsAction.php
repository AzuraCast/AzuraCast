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
use App\Radio\Adapters;
use Psr\Http\Message\ResponseInterface;

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
                [
                    'logs' => array_map(
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
                ]
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
