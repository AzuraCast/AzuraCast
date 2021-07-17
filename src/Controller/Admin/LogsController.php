<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractLogViewerController;
use App\Entity;
use App\Environment;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class LogsController extends AbstractLogViewerController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Environment $environment
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $stations = $this->em->getRepository(Entity\Station::class)->findAll();
        $station_logs = [];

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            $station_logs[$station->getId()] = [
                'name' => $station->getName(),
                'logs' => $this->getStationLogs($station),
            ];
        }

        return $request->getView()->renderToResponse(
            $response,
            'admin/logs/index',
            [
                'global_logs' => $this->getGlobalLogs(),
                'station_logs' => $station_logs,
            ]
        );
    }

    /**
     * @return array<string, array>
     */
    protected function getGlobalLogs(): array
    {
        $tempDir = $this->environment->getTempDirectory();
        $logPaths = [];

        $logPaths['azuracast_log'] = [
            'name' => __('AzuraCast Application Log'),
            'path' => $tempDir . '/app-' . gmdate('Y-m-d') . '.log',
            'tail' => true,
        ];

        if (!$this->environment->isDocker()) {
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

    public function viewAction(
        ServerRequest $request,
        Response $response,
        string|int $station_id,
        string $log
    ): ResponseInterface {
        if ('global' === $station_id) {
            $log_areas = $this->getGlobalLogs();
        } else {
            $log_areas = $this->getStationLogs($request->getStation());
        }

        if (!isset($log_areas[$log])) {
            throw new Exception('Invalid log file specified.');
        }

        $logArea = $log_areas[$log];
        return $this->view($request, $response, $logArea['path'], $logArea['tail'] ?? true);
    }
}
