<?php
namespace Controller\Stations;

class UtilController extends BaseController
{
    /**
     * Allow download of playlist files for the station in PLS/M3U format.
     */
    public function playlistAction()
    {
        $this->doNotRender();

        $fa = $this->station->getFrontendAdapter($this->di);
        $stream_urls = $fa->getStreamUrls();

        $format = strtolower($this->getParam('format', 'pls'));
        switch ($format) {
            // M3U Playlist Format
            case "m3u":
                $m3u_file = implode("\n", $stream_urls);

                header('Content-Type: audio/x-mpegurl');
                header('Content-Disposition: attachment; filename="' . $this->station->getShortName() . '.m3u"');
                echo $m3u_file;
                break;

            // PLS Playlist Format
            case "pls":
            default:
                $output = [
                    '[playlist]'
                ];

                $i = 1;
                foreach ($stream_urls as $stream_url) {
                    $output[] = 'File' . $i . '=' . $stream_url;
                    $output[] = 'Length' . $i . '=-1';
                    $i++;
                }

                $output[] = '';
                $output[] = 'NumberOfEntries=' . count($stream_urls);
                $output[] = 'Version=2';

                header('Content-Type: audio/x-scpls');
                header('Content-Disposition: attachment; filename="' . $this->station->getShortName() . '.pls"');
                echo implode("\n", $output);
                break;
        }
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

        $frontend->start();
        $backend->start();

        $this->station->needs_restart = false;
        $this->em->persist($this->station);
        $this->em->flush();
    }
}