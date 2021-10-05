<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Api\Traits\HasLogViewer;
use App\Entity;
use App\Radio\Adapters;

abstract class AbstractLogViewerController
{
    use HasLogViewer;

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
