<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\Traits\HasLogViewer;
use App\Environment;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class LogsAction
{
    use HasLogViewer;

    public function __construct(
        private readonly Environment $environment
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        ?string $log = null
    ): ResponseInterface {
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

        if ($this->environment->isDocker()) {
            $langServiceLog = __('%s Service Log');
            $logPaths['service_mariadb'] = [
                'name' => sprintf($langServiceLog, __('MariaDB')),
                'path' => $tempDir . '/service_mariadb.log',
                'tail' => true,
            ];
            $logPaths['service_redis'] = [
                'name' => sprintf($langServiceLog, __('Redis')),
                'path' => $tempDir . '/service_redis.log',
                'tail' => true,
            ];
            $logPaths['service_beanstalkd'] = [
                'name' => sprintf($langServiceLog, __('Beanstalkd')),
                'path' => $tempDir . '/service_beanstalkd.log',
                'tail' => true,
            ];
            $logPaths['service_cron'] = [
                'name' => sprintf($langServiceLog, __('Cron')),
                'path' => $tempDir . '/service_crond.log',
                'tail' => true,
            ];
            $logPaths['service_nginx'] = [
                'name' => sprintf($langServiceLog, __('Nginx')),
                'path' => $tempDir . '/service_nginx.log',
                'tail' => true,
            ];
            $logPaths['service_sftpgo'] = [
                'name' => sprintf($langServiceLog, __('SFTPGo')),
                'path' => $tempDir . '/service_sftpgo.log',
                'tail' => true,
            ];
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
