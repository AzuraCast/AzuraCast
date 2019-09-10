<?php
namespace App\Controller\Traits;

use App\Entity;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Psr\Http\Message\ResponseInterface;

trait LogViewerTrait
{
    static $maximum_log_size = 1048576;

    protected function _view(
        ServerRequest $request,
        Response $response,
        $log_path,
        $tail_file = true
    ): ResponseInterface {
        clearstatcache();

        if (!file_exists($log_path)) {
            throw new NotFoundException('Log file not found!');
        }

        if (!$tail_file) {
            $log_contents_parts = explode("\n", file_get_contents($log_path));
            $log_contents_parts = str_replace([">", "<"], ["&gt;", "&lt;"], $log_contents_parts);

            return $response->withJson([
                'contents' => implode("\n", $log_contents_parts),
                'eof' => true,
            ]);
        }

        $params = $request->getQueryParams();
        $last_viewed_size = (int)($params['position'] ?? 0);

        $log_size = filesize($log_path);
        if ($last_viewed_size > $log_size) {
            $last_viewed_size = $log_size;
        }

        $log_visible_size = ($log_size - $last_viewed_size);
        $cut_first_line = false;

        if ($log_visible_size > self::$maximum_log_size) {
            $log_visible_size = self::$maximum_log_size;
            $cut_first_line = true;
        }

        $log_contents = '';

        if ($log_visible_size > 0) {
            $fp = fopen($log_path, 'rb');
            fseek($fp, -$log_visible_size, SEEK_END);
            $log_contents_raw = fread($fp, $log_visible_size);

            $log_contents_parts = explode("\n", $log_contents_raw);
            if ($cut_first_line) {
                array_shift($log_contents_parts);
            }
            if (end($log_contents_parts) == "") {
                array_pop($log_contents_parts);
            }

            $log_contents_parts = str_replace([">", "<"], ["&gt;", "&lt;"], $log_contents_parts);
            $log_contents = implode("\n", $log_contents_parts);

            fclose($fp);
        }

        return $response->withJson([
            'contents' => $log_contents,
            'position' => $log_size,
            'eof' => false,
        ]);
    }

    protected function _getStationLogs(Entity\Station $station): array
    {
        $log_paths = [];

        $station_config_dir = $station->getRadioConfigDir();

        switch ($station->getBackendType()) {
            case Adapters::BACKEND_LIQUIDSOAP:
                $log_paths['liquidsoap_log'] = [
                    'name' => __('Liquidsoap Log'),
                    'path' => $station_config_dir . '/liquidsoap.log',
                    'tail' => true,
                ];
                $log_paths['liquidsoap_liq'] = [
                    'name' => __('Liquidsoap Configuration'),
                    'path' => $station_config_dir . '/liquidsoap.liq',
                    'tail' => false,
                ];
                break;
        }

        switch ($station->getFrontendType()) {
            case Adapters::FRONTEND_ICECAST:
                $log_paths['icecast_access_log'] = [
                    'name' => __('Icecast Access Log'),
                    'path' => $station_config_dir . '/icecast_access.log',
                    'tail' => true,
                ];
                $log_paths['icecast_error_log'] = [
                    'name' => __('Icecast Error Log'),
                    'path' => $station_config_dir . '/icecast.log',
                    'tail' => true,
                ];
                $log_paths['icecast_xml'] = [
                    'name' => __('Icecast Configuration'),
                    'path' => $station_config_dir . '/icecast.xml',
                    'tail' => false,
                ];
                break;

            case Adapters::FRONTEND_SHOUTCAST:
                $log_paths['shoutcast_log'] = [
                    'name' => __('SHOUTcast Log'),
                    'path' => $station_config_dir . '/shoutcast.log',
                    'tail' => true,
                ];
                $log_paths['shoutcast_conf'] = [
                    'name' => __('SHOUTcast Configuration'),
                    'path' => $station_config_dir . '/sc_serv.conf',
                    'tail' => false,
                ];
                break;
        }

        return $log_paths;
    }


}
