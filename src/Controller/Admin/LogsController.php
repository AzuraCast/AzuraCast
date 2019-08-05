<?php
namespace App\Controller\Admin;

use App\Controller\Traits\LogViewerTrait;
use App\Entity;
use Azura\Exception;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class LogsController
{
    use LogViewerTrait;

    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $em
     *
     * @see \App\Provider\AdminProvider
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $stations = $this->em->getRepository(Entity\Station::class)->findAll();
        $station_logs = [];

        foreach($stations as $station) {
            /** @var Entity\Station $station */
            $station_logs[$station->getId()] = [
                'name' => $station->getName(),
                'logs' => $this->_getStationLogs($station)
            ];
        }

        return $request->getView()->renderToResponse($response, 'admin/logs/index', [
            'global_logs' => $this->_getGlobalLogs(),
            'station_logs' => $station_logs,
        ]);
    }

    public function viewAction(Request $request, Response $response, $station_id, $log_key): ResponseInterface
    {
        if ('global' === $station_id) {
            $log_areas = $this->_getGlobalLogs();
        } else {
            $station = $request->getStation();
            $log_areas = $this->_getStationLogs($station);
        }

        if (!isset($log_areas[$log_key])) {
            throw new Exception('Invalid log file specified.');
        }

        $log = $log_areas[$log_key];
        return $this->_view($request, $response, $log['path'], $log['tail'] ?? true);
    }

    protected function _getGlobalLogs(): array
    {
        $log_paths = [];

        $log_paths['azuracast_log'] = [
            'name' => __('AzuraCast Application Log'),
            'path' => APP_INCLUDE_TEMP.'/app.log',
            'tail' => true,
        ];

        if (!APP_INSIDE_DOCKER) {
            $log_paths['nginx_access'] = [
                'name' => __('Nginx Access Log'),
                'path' => APP_INCLUDE_TEMP.'/access.log',
                'tail' => true,
            ];
            $log_paths['nginx_error'] = [
                'name' => __('Nginx Error Log'),
                'path' => APP_INCLUDE_TEMP.'/error.log',
                'tail' => true,
            ];
            $log_paths['php'] = [
                'name' => __('PHP Application Log'),
                'path' => APP_INCLUDE_TEMP.'/php_errors.log',
                'tail' => true,
            ];
            $log_paths['supervisord'] = [
                'name' => __('Supervisord Log'),
                'path' => APP_INCLUDE_TEMP.'/supervisord.log',
                'tail' => true,
            ];
        }

        return $log_paths;
    }
}
