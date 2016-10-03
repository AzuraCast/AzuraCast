<?php
namespace Modules\Stations\Controllers;

use Entity\Station;

class UtilController extends BaseController
{
    /**
     * Allow download of playlist files for the station in PLS/M3U format.
     */
    public function playlistAction()
    {
        $stations = array($this->station);
        $this->doNotRender();

        $format = strtolower($this->getParam('format', 'pls'));
        switch($format)
        {
            // M3U Playlist Format
            case "m3u":
                $m3u_lines = array();
                $m3u_lines[] = '#EXTM3U';

                $i = 0;
                foreach($stations as $station)
                {
                    $fa = $station->getFrontendAdapter($this->di);
                    $stream_url = $fa->getStreamUrl();

                    $m3u_lines[] = '#EXTINF:' . $i . ',' . $station['name'];
                    $m3u_lines[] = $stream_url;
                    $i++;
                }

                $m3u_file = implode("\r\n", $m3u_lines);

                header('Content-Type: audio/x-mpegurl');
                header('Content-Disposition: attachment; filename="'.$this->station->getShortName().'.m3u"');
                echo $m3u_file;
            break;

            // Euro Truck Simulator 2
            case "ets":
                $ets_lines = array();
                $ets_i = 0;

                foreach($stations as $station)
                {
                    foreach($station['streams'] as $stream)
                    {
                        if (!$stream['is_active'] || !$stream['is_default'])
                            continue;

                        $ets_line = array(
                            str_replace('|', '', $stream['stream_url']),
                            str_replace('|', '', $station['name']),
                            str_replace('|', '', $station['genre']),
                            'EN',
                            128,
                            1,
                        );

                        $ets_lines[] = ' stream_data['.$ets_i.']: "'.implode('|', $ets_line).'"';
                        $ets_i++;
                    }
                }

                $ets_file =  "SiiNunit\n{\nlive_stream_def : _nameless.0662.83F8 {\n";
                $ets_file .= " stream_data: ".count($ets_lines)."\n";
                $ets_file .= implode("\n", $ets_lines);
                $ets_file .= "\n}\n\n}";

                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="live_streams.sii"');
                echo $ets_file;
            break;

            // PLS Playlist Format
            case "pls":
            default:
                $output = array();
                $output[] = '[playlist]';
                $output[] = 'NumberOfEntries='.count($stations);

                $i = 1;
                foreach($stations as $station)
                {
                    $fa = $station->getFrontendAdapter($this->di);
                    $stream_url = $fa->getStreamUrl();

                    $output[] = 'File' . $i . '=' . $stream_url;
                    $output[] = 'Title' . $i . '=' . $station['name'];
                    $output[] = 'Length' . $i . '=-1';
                    $output[] = 'Version=2';

                    $i++;
                }

                header('Content-Type: audio/x-scpls');
                header('Content-Disposition: attachment; filename="'.$this->station->getShortName().'.pls"');
                echo implode("\r\n", $output);
            break;
        }
    }

    /**
     * Write configuration changes to the station backend and restart it.
     */
    public function writeAction()
    {
        $this->acl->checkPermission('manage station broadcasting', $this->station->id);

        $backend = $this->station->getBackendAdapter($this->di);
        $backend->write();

        $this->view->backend_result = $backend->restart();
    }

    /**
     * Restart all services associated with the radio.
     */
    public function restartAction()
    {
        $this->acl->checkPermission('manage station broadcasting', $this->station->id);

        $frontend = $this->station->getFrontendAdapter($this->di);
        $backend = $this->station->getBackendAdapter($this->di);

        $backend->stop();
        $frontend->stop();

        $frontend->write();
        $backend->write();

        $this->view->frontend_result = $frontend->start();
        $this->view->backend_result = $backend->start();
    }
}