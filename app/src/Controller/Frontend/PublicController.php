<?php
namespace Controller\Frontend;

use Entity;
use Slim\Http\Request;
use Slim\Http\Response;

class PublicController extends BaseController
{
    public function permissions()
    {
        return true;
    }

    /** @var Entity\Station */
    protected $station;

    protected function preDispatch()
    {
        $this->station = $this->_getStation();

        $this->view->station = $this->station;

        $frontend = $this->station->getFrontendAdapter($this->di);
        $this->view->stream_url = $frontend->getStreamUrl();
    }

    public function indexAction(Request $request, Response $response): Response
    {}

    public function embedAction(Request $request, Response $response): Response
    {}

    public function embedrequestsAction(Request $request, Response $response): Response
    {}

    public function playlistAction(Request $request, Response $response): Response
    {
        $this->doNotRender();

        $fa = $this->station->getFrontendAdapter($this->di);
        $stream_urls = $fa->getStreamUrls();

        $format = strtolower($this->getParam('format', 'pls'));
        switch ($format) {
            // M3U Playlist Format
            case "m3u":
                $m3u_file = implode("\n", $stream_urls);

                $this->response->getBody()->write($m3u_file);

                return $this->response
                    ->withHeader('Content-Type', 'audio/x-mpegurl');

                // Disable for mobile devices
                // ->withHeader('Content-Disposition', 'attachment; filename="' . $this->station->getShortName() . '.m3u"');
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

                $this->response->getBody()->write(implode("\n", $output));

                return $this->response
                    ->withHeader('Content-Type', 'audio/x-scpls');

                // Disable for mobile devices
                // ->withHeader('Content-Disposition', 'attachment; filename="' . $this->station->getShortName() . '.pls"');
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

        if (!($station instanceof Entity\Station) || !$station->isEnablePublicPage()) {
            throw new \App\Exception(_('Station not found!'));
        }

        return $station;
    }
}