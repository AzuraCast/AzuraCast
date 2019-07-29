<?php
namespace App\Controller\Frontend;

use App\Radio\Backend\Liquidsoap;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use App\Radio\Remote\AdapterProxy;
use Psr\Http\Message\ResponseInterface;

class PublicController
{
    public function indexAction(Request $request, Response $response, $station_id = null): ResponseInterface
    {
        return $this->_getPublicPage($request, $response, 'frontend/public/index');
    }

    public function embedAction(Request $request, Response $response, $station_id = null): ResponseInterface
    {
        return $this->_getPublicPage($request, $response, 'frontend/public/embed');
    }

    public function embedrequestsAction(Request $request, Response $response): ResponseInterface
    {
        return $this->_getPublicPage($request, $response, 'frontend/public/embedrequests');
    }

    protected function _getPublicPage(Request $request, Response $response, $template_name, $template_vars = [])
    {
        // Override system-wide iframe refusal
        $response = $response->withoutHeader('X-Frame-Options');

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new \App\Exception\StationNotFound;
        }

        return $request->getView()->renderToResponse($response, $template_name, $template_vars + [
            'station' => $station,
        ]);
    }

    public function playlistAction(Request $request, Response $response, $station_id, $format = 'pls'): ResponseInterface
    {
        $station = $request->getStation();

        $streams = [];
        $stream_urls = [];

        $fa = $request->getStationFrontend();
        foreach ($station->getMounts() as $mount) {
            /** @var Entity\StationMount $mount */
            if (!$mount->isVisibleOnPublicPages()) {
                continue;
            }

            $stream_url = $fa->getUrlForMount($station, $mount, null, false);

            $stream_urls[] = $stream_url;
            $streams[] = [
                'name'  => $station->getName().' - '.$mount->getDisplayName(),
                'url'   => $stream_url,
            ];
        }

        $remotes = $request->getStationRemotes();
        foreach($remotes as $remote_proxy) {
            /** @var AdapterProxy $remote_proxy */
            $adapter = $remote_proxy->getAdapter();
            $remote = $remote_proxy->getRemote();

            $stream_url = $adapter->getPublicUrl($remote);

            $stream_urls[] = $stream_url;
            $streams[] = [
                'name'  => $station->getName().' - '.$remote->getDisplayName(),
                'url'   => $stream_url,
            ];
        }

        $format = strtolower($format);
        switch ($format) {
            // M3U Playlist Format
            case 'm3u':
                $m3u_file = implode("\n", $stream_urls);

                return $response
                    ->withHeader('Content-Type', 'audio/x-mpegurl')
                    ->withHeader('Content-Disposition', 'attachment; filename=' . $station->getShortName() . '.m3u')
                    ->write($m3u_file);
                break;

            // PLS Playlist Format
            case 'pls':
            default:
                $output = [
                    '[playlist]'
                ];

                $i = 1;
                foreach ($streams as $stream) {
                    $output[] = 'File' . $i . '=' . $stream['url'];
                    $output[] = 'Title' . $i . '=' . $stream['name'];
                    $output[] = 'Length' . $i . '=-1';
                    $output[] = '';
                    $i++;
                }

                $output[] = 'NumberOfEntries=' . count($streams);
                $output[] = 'Version=2';

                return $response
                    ->withHeader('Content-Type', 'audio/x-scpls')
                    ->withHeader('Content-Disposition', 'attachment; filename=' . $station->getShortName() . '.pls')
                    ->write(implode("\n", $output));
                break;
        }
    }

    public function djAction(Request $request, Response $response, $station_id, $format = 'pls'): ResponseInterface
    {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new \App\Exception\StationNotFound;
        }

        if (!$station->getEnableStreamers()) {
            throw new \App\Exception\StationUnsupported;
        }

        $backend = $request->getStationBackend();

        if (!($backend instanceof Liquidsoap)) {
            throw new \App\Exception\StationUnsupported;
        }

        $wss_url = (string)$backend->getWebStreamingUrl($station, $request->getRouter()->getBaseUrl());
        $wss_url = str_replace('wss://', '', $wss_url);

        return $request->getView()->renderToResponse($response, 'frontend/public/dj', [
            'station' => $station,
            'base_uri' => $wss_url,
        ]);
    }
}
