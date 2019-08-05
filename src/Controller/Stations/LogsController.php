<?php
namespace App\Controller\Stations;

use App\Controller\Traits\LogViewerTrait;
use Azura\Exception;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseInterface;

class LogsController
{
    use LogViewerTrait;

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        return $request->getView()->renderToResponse($response, 'stations/logs/index', [
            'logs' => $this->_getStationLogs($station),
        ]);
    }

    public function viewAction(Request $request, Response $response, $station_id, $log_key): ResponseInterface
    {
        $station = $request->getStation();
        $log_areas = $this->_getStationLogs($station);

        if (!isset($log_areas[$log_key])) {
            throw new Exception('Invalid log file specified.');
        }

        $log = $log_areas[$log_key];
        return $this->_view($request, $response, $log['path'], $log['tail'] ?? true);
    }
}
