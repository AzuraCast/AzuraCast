<?php
namespace App\Controller\Admin;

use App\Controller\Traits\LogViewerTrait;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Settings;
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
        $tempDir = Settings::getInstance()->getTempDirectory();
        $logPaths = [];

        $logPaths['azuracast_log'] = [
            'name' => __('AzuraCast Application Log'),
            'path' => $tempDir . '/app.log',
            'tail' => true,
        ];

        if (!Settings::getInstance()->isDocker()) {
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
