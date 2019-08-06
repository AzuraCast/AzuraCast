<?php
namespace App\Controller\Frontend;

use App\Entity;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Remote\AdapterProxy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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

        $station = \App\Http\RequestHelper::getStation($request);

        if (!$station->getEnablePublicPage()) {
            throw new \App\Exception\StationNotFound;
        }

        $np = [
            'station' => [
                'listen_url' => '',
                'mounts' => [],
                'remotes' => [],
            ],
            'now_playing' => [
                'song' => [
                    'title' => __('Song Title'),
                    'artist' => __('Song Artist'),
                    'art' => '',
                ],
                'playlist' => '',
                'is_request' => false,
                'duration' => 0,
            ],
            'live' => [
                'is_live' => false,
                'streamer_name' => '',
            ],
            'song_history' => [],
        ];

        $station_np = $station->getNowplaying();
        if ($station_np instanceof Entity\Api\NowPlaying) {
            $station_np->resolveUrls($request->getRouter()->getBaseUrl());
            $np = array_intersect_key($station_np->toArray(), $np) + $np;
        }

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, $template_name, $template_vars + [
            'station' => $station,
            'nowplaying' => $np,
        ]);
    }

    public function playlistAction(Request $request, Response $response, $station_id, $format = 'pls'): ResponseInterface
    {
        $station = \App\Http\RequestHelper::getStation($request);

        $streams = [];
        $stream_urls = [];

        $fa = \App\Http\RequestHelper::getStationFrontend($request);
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

        $remotes = \App\Http\RequestHelper::getStationRemotes($request);
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
        $station = \App\Http\RequestHelper::getStation($request);

        if (!$station->getEnablePublicPage()) {
            throw new \App\Exception\StationNotFound;
        }

        if (!$station->getEnableStreamers()) {
            throw new \App\Exception\StationUnsupported;
        }

        $backend = \App\Http\RequestHelper::getStationBackend($request);

        if (!($backend instanceof Liquidsoap)) {
            throw new \App\Exception\StationUnsupported;
        }

        $wss_url = (string)$backend->getWebStreamingUrl($station, $request->getRouter()->getBaseUrl());
        $wss_url = str_replace('wss://', '', $wss_url);

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'frontend/public/dj', [
            'station' => $station,
            'base_uri' => $wss_url,
        ]);
    }
}
