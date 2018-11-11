<?php
namespace App\Controller\Stations;

use App\Entity;
use Azura\Exception;
use App\Http\Request;
use App\Http\Response;
use App\Radio\Adapters;

class LogsController
{
    const MAXIMUM_LOG_SIZE = 1048576;

    public function __invoke(Request $request, Response $response): Response
    {
        $station = $request->getStation();

        return $request->getView()->renderToResponse($response, 'stations/logs/index', [
            'logs' => $this->_getLogAreas($station),
        ]);
    }

    public function viewAction(Request $request, Response $response, $station_id, $log_key): Response
    {
        $station = $request->getStation();
        $log_areas = $this->_getLogAreas($station);

        if (!isset($log_areas[$log_key])) {
            throw new Exception('Invalid log file specified.');
        }

        clearstatcache();

        $log = $log_areas[$log_key];

        if (!file_exists($log['path'])) {
            throw new Exception('Log file not found!');
        }

        if (!$log['tail']) {
            $log_contents_parts = explode("\n", file_get_contents($log['path']));
            $log_contents_parts = str_replace(array(">", "<"), array("&gt;", "&lt;"), $log_contents_parts);

            return $response->withJson([
                'contents' => implode("\n", $log_contents_parts),
                'eof' => true,
            ]);
        }

        $last_viewed_size = (int)$request->getParam('position', 0);

        $log_size = filesize($log['path']);
        if ($last_viewed_size > $log_size) {
            $last_viewed_size = $log_size;
        }

        $log_visible_size = ($log_size - $last_viewed_size);
        $cut_first_line = false;

        if ($log_visible_size > self::MAXIMUM_LOG_SIZE) {
            $log_visible_size = self::MAXIMUM_LOG_SIZE;
            $cut_first_line = true;
        }

        $log_contents = '';

        if ($log_visible_size > 0) {
            $fp = fopen($log['path'], 'rb');
            fseek($fp, -$log_visible_size, SEEK_END);
            $log_contents_raw = fread($fp, $log_visible_size);

            $log_contents_parts = explode("\n", $log_contents_raw);
            if ($cut_first_line) {
                array_shift($log_contents_parts);
            }
            if(end($log_contents_parts) == "") {
                array_pop($log_contents_parts);
            }

            $log_contents_parts = str_replace(array(">", "<"), array("&gt;", "&lt;"), $log_contents_parts);
            $log_contents = implode("\n", $log_contents_parts);

            fclose($fp);
        }

        return $response->withJson([
            'contents' => $log_contents,
            'position' => $log_size,
            'eof' => false,
        ]);
    }

    protected function _getLogAreas(Entity\Station $station)
    {
        $log_paths = [];

        $log_paths['azuracast_log'] = [
            'name' => __('AzuraCast Application Log'),
            'path' => APP_INCLUDE_TEMP.'/azuracast.log',
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
                'path' => APP_INCLUDE_TEMP.'/azuracast.log',
                'tail' => true,
            ];
            $log_paths['supervisord'] = [
                'name' => __('Supervisord Log'),
                'path' => APP_INCLUDE_TEMP.'/supervisord.log',
                'tail' => true,
            ];
        }

        $station_config_dir = $station->getRadioConfigDir();

        switch($station->getBackendType())
        {
            case Adapters::BACKEND_LIQUIDSOAP:
                $log_paths['liquidsoap_log'] = [
                    'name' => __('Liquidsoap Log'),
                    'path' => $station_config_dir.'/liquidsoap.log',
                    'tail' => true,
                ];
                $log_paths['liquidsoap_liq'] = [
                    'name' => __('Liquidsoap Configuration'),
                    'path' => $station_config_dir.'/liquidsoap.liq',
                    'tail' => false,
                ];
                break;
        }

        switch($station->getFrontendType())
        {
            case Adapters::FRONTEND_ICECAST:
                $log_paths['icecast_access_log'] = [
                    'name' => __('Icecast Access Log'),
                    'path' => $station_config_dir.'/icecast_access.log',
                    'tail' => true,
                ];
                $log_paths['icecast_error_log'] = [
                    'name' => __('Icecast Error Log'),
                    'path' => $station_config_dir.'/icecast_error.log',
                    'tail' => true,
                ];
                $log_paths['icecast_xml'] = [
                    'name' => __('Icecast Configuration'),
                    'path' => $station_config_dir.'/icecast.xml',
                    'tail' => false,
                ];
                break;

            case Adapters::FRONTEND_SHOUTCAST:
                $log_paths['shoutcast_log'] = [
                    'name' => __('SHOUTcast Log'),
                    'path' => $station_config_dir.'/sc_serv.log',
                    'tail' => true,
                ];
                $log_paths['shoutcast_conf'] = [
                    'name' => __('SHOUTcast Configuration'),
                    'path' => $station_config_dir.'/sc_serv.conf',
                    'tail' => false,
                ];
                break;
        }

        return $log_paths;
    }
}
