<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\SingleActionInterface;
use App\Entity\StationMount;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Psr\Http\Message\ResponseInterface;

final readonly class PlaylistAction implements SingleActionInterface
{
    public function __construct(
        private Adapters $adapters,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $format */
        $format = $params['format'] ?? 'pls';

        $station = $request->getStation();

        $streams = [];
        $streamUrls = [];

        $fa = $this->adapters->getFrontendAdapter($station);
        if (null !== $fa) {
            foreach ($station->mounts as $mount) {
                /** @var StationMount $mount */
                if (!$mount->is_visible_on_public_pages) {
                    continue;
                }

                $streamUrl = $fa->getUrlForMount($station, $mount);

                $streamUrls[] = $streamUrl;
                $streams[] = [
                    'name' => $station->name . ' - ' . $mount->display_name,
                    'url' => $streamUrl,
                ];
            }
        }

        foreach ($station->remotes as $remote) {
            if (!$remote->is_visible_on_public_pages) {
                continue;
            }

            $streamUrl = $this->adapters->getRemoteAdapter($remote)
                ->getPublicUrl($remote);

            $streamUrls[] = $streamUrl;
            $streams[] = [
                'name' => $station->name . ' - ' . $remote->display_name,
                'url' => $streamUrl,
            ];
        }

        if ($station->enable_hls && $station->backend_type->isEnabled()) {
            $backend = $this->adapters->getBackendAdapter($station);
            $backendConfig = $station->backend_config;

            if (null !== $backend && $backendConfig->hls_enable_on_public_player) {
                $streamUrl = $backend->getHlsUrl($station);
                $streamRow = [
                    'name' => $station->name . ' - HLS',
                    'url' => (string)$streamUrl,
                ];

                if ($backendConfig->hls_is_default) {
                    array_unshift($streamUrls, $streamUrl);
                    array_unshift($streams, $streamRow);
                } else {
                    $streamUrls[] = $streamUrl;
                    $streams[] = $streamRow;
                }
            }
        }

        $format = strtolower($format);
        switch ($format) {
            // M3U Playlist Format
            case 'm3u':
                $m3uFile = implode("\n", $streamUrls);

                $response->getBody()->write($m3uFile);
                return $response
                    ->withHeader('Content-Type', 'audio/x-mpegurl')
                    ->withHeader('Content-Disposition', 'attachment; filename=' . $station->short_name . '.m3u');

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
                    ->withHeader('Content-Disposition', 'attachment; filename=' . $station->short_name . '.pls');
        }
    }
}
