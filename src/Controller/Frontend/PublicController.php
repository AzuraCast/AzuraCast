<?php
namespace App\Controller\Frontend;

use App\View;
use App\Radio\Frontend\FrontendAbstract;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class PublicController
{
    public function indexAction(Request $request, Response $response, $station_id = null, $autoplay = false): Response
    {
        $template_vars = ['autoplay' => ($autoplay === 'autoplay')];
        return $this->_getPublicPage($request, $response, 'frontend/public/index', $template_vars);
    }

    public function embedAction(Request $request, Response $response, $station_id = null, $autoplay = false): Response
    {
        $template_vars = ['autoplay' => ($autoplay === 'autoplay')];
        return $this->_getPublicPage($request, $response, 'frontend/public/embed', $template_vars);
    }

    public function embedrequestsAction(Request $request, Response $response): Response
    {
        return $this->_getPublicPage($request, $response, 'frontend/public/embedrequests');
    }

    protected function _getPublicPage(Request $request, Response $response, $template_name, $template_vars = [])
    {
        // Override system-wide iframe refusal
        $response = $response->withoutHeader('X-Frame-Options');

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new \Azura\Exception(__('Station not found!'));
        }

        $frontend_adapter = $request->getStationFrontend();

        return $request->getView()->renderToResponse($response, $template_name, $template_vars + [
            'station' => $station,
            'stream_url' => $frontend_adapter->getStreamUrl($station),
        ]);
    }

    public function playlistAction(Request $request, Response $response, $station_id, $format = 'pls'): Response
    {
        $station = $request->getStation();
        $fa = $request->getStationFrontend();

        $stream_urls = $fa->getStreamUrls($station);

        $format = strtolower($format);

        switch ($format) {
            // M3U Playlist Format
            case "m3u":
                $m3u_file = implode("\n", $stream_urls);

                return $response
                    ->withHeader('Content-Type', 'audio/x-mpegurl')
                    ->withHeader('Content-Disposition', 'attachment; filename='.$station->getShortName().'.m3u')
                    ->write($m3u_file);
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

                return $response
                    ->withHeader('Content-Type', 'audio/x-scpls')
                    ->withHeader('Content-Disposition', 'attachment; filename='.$station->getShortName().'.pls')
                    ->write(implode("\n", $output));
                break;
        }
    }
}
