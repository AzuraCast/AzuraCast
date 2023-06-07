<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Psr\Http\Message\ResponseInterface;

final class PlaylistAction
{
    public function __construct(
        private readonly Adapters $adapters,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $format = 'pls'
    ): ResponseInterface {
        $station = $request->getStation();

        $streams = [];
        $stream_urls = [];

        $fa = $this->adapters->getFrontendAdapter($station);
        if (null !== $fa) {
            foreach ($station->getMounts() as $mount) {
                /** @var \App\Entity\StationMount $mount */
                if (!$mount->getIsVisibleOnPublicPages()) {
                    continue;
                }

                $stream_url = $fa->getUrlForMount($station, $mount);

                $stream_urls[] = $stream_url;
                $streams[] = [
                    'name' => $station->getName() . ' - ' . $mount->getDisplayName(),
                    'url' => $stream_url,
                ];
            }
        }

        foreach ($station->getRemotes() as $remote) {
            if (!$remote->getIsVisibleOnPublicPages()) {
                continue;
            }

            $stream_url = $this->adapters->getRemoteAdapter($remote)
                ->getPublicUrl($remote);

            $stream_urls[] = $stream_url;
            $streams[] = [
                'name' => $station->getName() . ' - ' . $remote->getDisplayName(),
                'url' => $stream_url,
            ];
        }

        if ($station->getEnableHls() && $station->getBackendType()->isEnabled()) {
            $backend = $this->adapters->getBackendAdapter($station);
            $backendConfig = $station->getBackendConfig();

            if (null !== $backend && $backendConfig->getHlsEnableOnPublicPlayer()) {
                $streamUrl = $backend->getHlsUrl($station);
                $streamRow = [
                    'name' => $station->getName() . ' - HLS',
                    'url' => (string)$streamUrl,
                ];

                if ($backendConfig->getHlsIsDefault()) {
                    array_unshift($stream_urls, $streamUrl);
                    array_unshift($streams, $streamRow);
                } else {
                    $stream_urls[] = $streamUrl;
                    $streams[] = $streamRow;
                }
            }
        }

        $format = strtolower($format);
        switch ($format) {
            // M3U Playlist Format
            case 'm3u':
                $m3u_file = implode("\n", $stream_urls);

                $response->getBody()->write($m3u_file);
                return $response
                    ->withHeader('Content-Type', 'audio/x-mpegurl')
                    ->withHeader('Content-Disposition', 'attachment; filename=' . $station->getShortName() . '.m3u');

            // PLS Playlist Format
            case 'pls':
            default:
                $output = [
                    '[playlist]',
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

                $response->getBody()->write(implode("\n", $output));
                return $response
                    ->withHeader('Content-Type', 'audio/x-scpls')
                    ->withHeader('Content-Disposition', 'attachment; filename=' . $station->getShortName() . '.pls');
        }
    }
}
