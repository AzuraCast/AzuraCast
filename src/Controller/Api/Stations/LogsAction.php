<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\HasLogViewer;
use App\Controller\SingleActionInterface;
use App\Entity\Station;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
use Psr\Http\Message\ResponseInterface;

final class LogsAction implements SingleActionInterface
{
    use HasLogViewer;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string|null $log */
        $log = $params['log'] ?? null;

        $station = $request->getStation();

        $logPaths = $this->getStationLogs($station);

        if (null === $log) {
            $router = $request->getRouter();
            return $response->withJson(
                [
                    'logs' => array_map(
                        function (string $key, array $row) use ($router, $station) {
                            $row['key'] = $key;
                            $row['links'] = [
                                'self' => $router->named(
                                    'api:stations:log',
                                    [
                                        'station_id' => $station->getIdRequired(),
                                        'log' => $key,
                                    ]
                                ),
                            ];
                            return $row;
                        },
                        array_keys($logPaths),
                        array_values($logPaths)
                    ),
                ]
            );
        }

        if (!isset($logPaths[$log])) {
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

        return $this->streamLogToResponse(
            $request,
            $response,
            $logPaths[$log]['path'],
            $logPaths[$log]['tail'] ?? true,
            $filteredTerms
        );
    }

    private function getStationLogs(Station $station): array
    {
        $logPaths = [];
        $stationConfigDir = $station->getRadioConfigDir();

        $logPaths['station_nginx'] = [
            'name' => __('Station Nginx Configuration'),
            'path' => $stationConfigDir . '/nginx.conf',
            'tail' => false,
        ];

        if (BackendAdapters::Liquidsoap === $station->getBackendType()) {
            $logPaths['liquidsoap_log'] = [
                'name' => __('Liquidsoap Log'),
                'path' => $stationConfigDir . '/liquidsoap.log',
                'tail' => true,
            ];
            $logPaths['liquidsoap_liq'] = [
                'name' => __('Liquidsoap Configuration'),
                'path' => $stationConfigDir . '/liquidsoap.liq',
                'tail' => false,
            ];
        }

        switch ($station->getFrontendType()) {
            case FrontendAdapters::Icecast:
                $logPaths['icecast_access_log'] = [
                    'name' => __('Icecast Access Log'),
                    'path' => $stationConfigDir . '/icecast_access.log',
                    'tail' => true,
                ];
                $logPaths['icecast_error_log'] = [
                    'name' => __('Icecast Error Log'),
                    'path' => $stationConfigDir . '/icecast.log',
                    'tail' => true,
                ];
                $logPaths['icecast_xml'] = [
                    'name' => __('Icecast Configuration'),
                    'path' => $stationConfigDir . '/icecast.xml',
                    'tail' => false,
                ];
                break;

            case FrontendAdapters::Shoutcast:
                $logPaths['shoutcast_log'] = [
                    'name' => __('Shoutcast Log'),
                    'path' => $stationConfigDir . '/shoutcast.log',
                    'tail' => true,
                ];
                $logPaths['shoutcast_conf'] = [
                    'name' => __('Shoutcast Configuration'),
                    'path' => $stationConfigDir . '/sc_serv.conf',
                    'tail' => false,
                ];
                break;

            case FrontendAdapters::Remote:
                // Noop
                break;
        }

        return $logPaths;
    }
}
