<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Container\EnvironmentAwareTrait;
use App\Controller\Api\Traits\HasLogViewer;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\ServiceControl;
use Psr\Http\Message\ResponseInterface;

final class LogsAction
{
    use HasLogViewer;
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly ServiceControl $serviceControl,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string|null $log */
        $log = $params['log'] ?? null;

        $logPaths = $this->getGlobalLogs();

        if (null === $log) {
            $router = $request->getRouter();
            return $response->withJson(
                [
                    'logs' => array_map(
                        function (string $key, array $row) use ($router) {
                            $row['key'] = $key;
                            $row['links'] = [
                                'self' => $router->named(
                                    'api:admin:log',
                                    [
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

        return $this->streamLogToResponse(
            $request,
            $response,
            $logPaths[$log]['path'],
            $logPaths[$log]['tail'] ?? true
        );
    }

    /**
     * @return array<string, array>
     */
    private function getGlobalLogs(): array
    {
        $tempDir = $this->environment->getTempDirectory();
        $logPaths = [];

        $logPaths['azuracast_log'] = [
            'name' => __('AzuraCast Application Log'),
            'path' => $tempDir . '/app-' . gmdate('Y-m-d') . '.log',
            'tail' => true,
        ];

        $logPaths['azuracast_nowplaying_log'] = [
            'name' => __('AzuraCast Now Playing Log'),
            'path' => $tempDir . '/app_nowplaying-' . gmdate('Y-m-d') . '.log',
            'tail' => true,
        ];

        $logPaths['azuracast_sync_log'] = [
            'name' => __('AzuraCast Synchronized Task Log'),
            'path' => $tempDir . '/app_sync-' . gmdate('Y-m-d') . '.log',
            'tail' => true,
        ];

        $logPaths['azuracast_worker_log'] = [
            'name' => __('AzuraCast Queue Worker Log'),
            'path' => $tempDir . '/app_worker-' . gmdate('Y-m-d') . '.log',
            'tail' => true,
        ];

        if ($this->environment->isDocker()) {
            $langServiceLog = __('Service Log: %s (%s)');

            foreach ($this->serviceControl->getServiceNames() as $serviceKey => $serviceName) {
                $logPath = $tempDir . '/service_' . $serviceKey . '.log';

                if (is_file($logPath)) {
                    $logPaths['service_' . $serviceKey] = [
                        'name' => sprintf($langServiceLog, $serviceKey, $serviceName),
                        'path' => $logPath,
                        'tail' => true,
                    ];
                }
            }
        } else {
            $logPaths['nginx_access'] = [
                'name' => __('Nginx Access Log'),
                'path' => $tempDir . '/access.log',
                'tail' => true,
            ];
            $logPaths['nginx_error'] = [
                'name' => __('Nginx Error Log'),
                'path' => $tempDir . '/error.log',
                'tail' => true,
            ];
            $logPaths['php'] = [
                'name' => __('PHP Application Log'),
                'path' => $tempDir . '/php_errors.log',
                'tail' => true,
            ];
            $logPaths['supervisord'] = [
                'name' => __('Supervisord Log'),
                'path' => $tempDir . '/supervisord.log',
                'tail' => true,
            ];
        }

        return $logPaths;
    }
}
