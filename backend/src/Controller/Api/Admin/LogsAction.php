<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Container\EnvironmentAwareTrait;
use App\Controller\Api\Traits\HasLogViewer;
use App\Entity\Api\LogType;
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

        $logTypes = $this->getGlobalLogs();

        if (null === $log) {
            $router = $request->getRouter();
            return $response->withJson(
                [
                    'logs' => array_map(
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
                    ),
                ]
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
