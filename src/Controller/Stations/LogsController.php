<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Controller\AbstractLogViewerController;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class LogsController extends AbstractLogViewerController
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        return $request->getView()->renderToResponse($response, 'stations/logs/index', [
            'logs' => $this->getStationLogs($station),
        ]);
    }

    public function viewAction(ServerRequest $request, Response $response, string $log): ResponseInterface
    {
        $station = $request->getStation();
        $log_areas = $this->getStationLogs($station);

        if (!isset($log_areas[$log])) {
            throw new Exception('Invalid log file specified.');
        }

        $logArea = $log_areas[$log];
        return $this->view($request, $response, $logArea['path'], $logArea['tail'] ?? true);
    }

    protected function processLog(
        ServerRequest $request,
        string $rawLog,
        bool $cutFirstLine = false,
        bool $cutEmptyLastLine = false
    ): string {
        $log = parent::processLog($request, $rawLog, $cutFirstLine, $cutEmptyLastLine);

        // Filter out passwords, API keys, etc.
        $station = $request->getStation();

        $frontendConfig = $station->getFrontendConfig();

        $passwords = [
            $station->getAdapterApiKey(),
            $frontendConfig->getAdminPassword(),
            $frontendConfig->getRelayPassword(),
            $frontendConfig->getSourcePassword(),
            $frontendConfig->getStreamerPassword(),
        ];

        return str_replace($passwords, '(PASSWORD)', $log);
    }
}
