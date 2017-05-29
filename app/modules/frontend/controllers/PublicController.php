<?php
namespace Controller\Frontend;

use Entity;

class PublicController extends BaseController
{
    public function permissions()
    {
        return true;
    }

    /** @var Entity\Station */
    protected $station;

    public function preDispatch()
    {
        $this->station = $this->_getStation();
        $this->view->station = $this->station;
    }

    public function indexAction()
    {}

    public function embedAction()
    {}

    public function embedrequestsAction()
    {}

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

    protected function _getStation()
    {
        $station_id = $this->getParam('station');

        /** @var Entity\Repository\StationRepository $station_repo */
        $station_repo = $this->em->getRepository(Entity\Station::class);

        if (is_numeric($station_id)) {
            $station = $station_repo->find($station_id);
        } else {
            $station = $station_repo->findByShortCode($station_id);
        }

        if (!($station instanceof Entity\Station)) {
            throw new \App\Exception(_('Station not found!'));
        }

        return $station;
    }
}