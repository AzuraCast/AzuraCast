<?php
namespace App\Controller\Stations;

use App\Controller\Traits\LogViewerTrait;
use App\Http\RequestHelper;
use Azura\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LogsController
{
    use LogViewerTrait;

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $station = RequestHelper::getStation($request);

        return RequestHelper::getView($request)->renderToResponse($response, 'stations/logs/index', [
            'logs' => $this->_getStationLogs($station),
        ]);
    }

    public function viewAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $log_key): ResponseInterface
    {
        $station = RequestHelper::getStation($request);
        $log_areas = $this->_getStationLogs($station);

        if (!isset($log_areas[$log_key])) {
            throw new Exception('Invalid log file specified.');
        }

        $log = $log_areas[$log_key];
        return $this->_view($request, $response, $log['path'], $log['tail'] ?? true);
    }
}
