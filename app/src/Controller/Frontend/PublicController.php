<?php
namespace Controller\Frontend;

use App\Mvc\View;
use AzuraCast\Radio\Frontend\FrontendAbstract;
use Entity;
use App\Http\Request;
use App\Http\Response;

class PublicController
{
    public function indexAction(Request $request, Response $response): Response
    {
        return $this->_getPublicPage($request, $response, 'frontend/public/index');
    }

    public function embedAction(Request $request, Response $response): Response
    {
        return $this->_getPublicPage($request, $response, 'frontend/public/embed');
    }

    public function embedrequestsAction(Request $request, Response $response): Response
    {
        return $this->_getPublicPage($request, $response, 'frontend/public/embedrequests');
    }

    protected function _getPublicPage(Request $request, Response $response, $template_name)
    {
        // Override system-wide iframe refusal
        $response = $response->withoutHeader('X-Frame-Options');

        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        if (!$station->getEnablePublicPage()) {
            throw new \App\Exception(_('Station not found!'));
        }

        /** @var FrontendAbstract $frontend_adapter */
        $frontend_adapter = $request->getAttribute('station_frontend');

        /** @var View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, $template_name, [
            'station' => $station,
            'stream_url' => $frontend_adapter->getStreamUrl(),
        ]);
    }

    public function playlistAction(Request $request, Response $response): Response
    {
        /** @var FrontendAbstract $frontend_adapter */
        $fa = $request->getAttribute('station_frontend');

        $stream_urls = $fa->getStreamUrls();

        $format = strtolower($request->getParam('format', 'pls'));
        switch ($format) {
            // M3U Playlist Format
            case "m3u":
                $m3u_file = implode("\n", $stream_urls);

                return $response
                    ->withHeader('Content-Type', 'audio/x-mpegurl')
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
                    ->write(implode("\n", $output));
                break;
        }
    }
}