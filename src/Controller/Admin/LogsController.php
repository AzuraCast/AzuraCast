<?php
namespace App\Controller\Admin;

use App\Controller\Traits\LogViewerTrait;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Exception;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class LogsController
{
    use LogViewerTrait;

    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $stations = $this->em->getRepository(Entity\Station::class)->findAll();
        $station_logs = [];

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            $station_logs[$station->getId()] = [
                'name' => $station->getName(),
                'logs' => $this->_getStationLogs($station),
            ];
        }

        return $request->getView()->renderToResponse($response, 'admin/logs/index', [
            'global_logs' => $this->_getGlobalLogs(),
            'station_logs' => $station_logs,
        ]);
    }

    protected function _getGlobalLogs(): array
    {
        $log_paths = [];

        $log_paths['azuracast_log'] = [
            'name' => __('AzuraCast Application Log'),
            'path' => APP_INCLUDE_TEMP . '/app.log',
            'tail' => true,
        ];

        if (!APP_INSIDE_DOCKER) {
            $log_paths['nginx_access'] = [
                'name' => __('Nginx Access Log'),
                'path' => APP_INCLUDE_TEMP . '/access.log',
                'tail' => true,
            ];
            $log_paths['nginx_error'] = [
                'name' => __('Nginx Error Log'),
                'path' => APP_INCLUDE_TEMP . '/error.log',
                'tail' => true,
            ];
            $log_paths['php'] = [
                'name' => __('PHP Application Log'),
                'path' => APP_INCLUDE_TEMP . '/php_errors.log',
                'tail' => true,
            ];
            $log_paths['supervisord'] = [
                'name' => __('Supervisord Log'),
                'path' => APP_INCLUDE_TEMP . '/supervisord.log',
                'tail' => true,
            ];
        }

        return $log_paths;
    }

    public function viewAction(ServerRequest $request, Response $response, $station_id, $log): ResponseInterface
    {
        if ('global' === $station_id) {
            $log_areas = $this->_getGlobalLogs();
        } else {
            $log_areas = $this->_getStationLogs($request->getStation());
        }

        if (!isset($log_areas[$log])) {
            throw new Exception('Invalid log file specified.');
        }

        $log = $log_areas[$log];
        return $this->_view($request, $response, $log['path'], $log['tail'] ?? true);
    }
}
