<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractLogViewerController
{
    public static int $maximum_log_size = 1048576;

    protected function view(
        ServerRequest $request,
        Response $response,
        string $log_path,
        bool $tail_file = true
    ): ResponseInterface {
        clearstatcache();

        if (!is_file($log_path)) {
            throw new NotFoundException('Log file not found!');
        }

        if (!$tail_file) {
            $log = file_get_contents($log_path) ?: '';
            $log_contents = $this->processLog($request, $log);

            return $response->withJson([
                'contents' => $log_contents,
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
            if (false === $fp) {
                throw new \RuntimeException(sprintf('Could not open file at path "%s".', $log_path));
            }

            fseek($fp, -$log_visible_size, SEEK_END);
            $log_contents_raw = fread($fp, $log_visible_size) ?: '';
            fclose($fp);

            $log_contents = $this->processLog($request, $log_contents_raw, $cut_first_line, true);
        }

        return $response->withJson([
            'contents' => $log_contents,
            'position' => $log_size,
            'eof' => false,
        ]);
    }

    protected function processLog(
        ServerRequest $request,
        string $rawLog,
        bool $cutFirstLine = false,
        bool $cutEmptyLastLine = false
    ): string {
        $logParts = explode("\n", $rawLog);

        if ($cutFirstLine) {
            array_shift($logParts);
        }
        if ($cutEmptyLastLine && end($logParts) === '') {
            array_pop($logParts);
        }

        $logParts = str_replace(['>', '<'], ['&gt;', '&lt;'], $logParts);

        $log = implode("\n", $logParts);
        return mb_convert_encoding($log, 'UTF-8', 'UTF-8');
    }

    /**
     * @return array<string, array>
     */
    protected function getStationLogs(Entity\Station $station): array
    {
        $log_paths = [];

        $stationConfigDir = $station->getRadioConfigDir();

        switch ($station->getBackendType()) {
            case Adapters::BACKEND_LIQUIDSOAP:
                $log_paths['liquidsoap_log'] = [
                    'name' => __('Liquidsoap Log'),
                    'path' => $stationConfigDir . '/liquidsoap.log',
                    'tail' => true,
                ];
                $log_paths['liquidsoap_liq'] = [
                    'name' => __('Liquidsoap Configuration'),
                    'path' => $stationConfigDir . '/liquidsoap.liq',
                    'tail' => false,
                ];
                break;
        }

        switch ($station->getFrontendType()) {
            case Adapters::FRONTEND_ICECAST:
                $log_paths['icecast_access_log'] = [
                    'name' => __('Icecast Access Log'),
                    'path' => $stationConfigDir . '/icecast_access.log',
                    'tail' => true,
                ];
                $log_paths['icecast_error_log'] = [
                    'name' => __('Icecast Error Log'),
                    'path' => $stationConfigDir . '/icecast.log',
                    'tail' => true,
                ];
                $log_paths['icecast_xml'] = [
                    'name' => __('Icecast Configuration'),
                    'path' => $stationConfigDir . '/icecast.xml',
                    'tail' => false,
                ];
                break;

            case Adapters::FRONTEND_SHOUTCAST:
                $log_paths['shoutcast_log'] = [
                    'name' => __('SHOUTcast Log'),
                    'path' => $stationConfigDir . '/shoutcast.log',
                    'tail' => true,
                ];
                $log_paths['shoutcast_conf'] = [
                    'name' => __('SHOUTcast Configuration'),
                    'path' => $stationConfigDir . '/sc_serv.conf',
                    'tail' => false,
                ];
                break;
        }

        return $log_paths;
    }
}
