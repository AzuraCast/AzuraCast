<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\SingleActionInterface;
use App\Entity\StationMount;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Psr\Http\Message\ResponseInterface;

final class PlaylistAction implements SingleActionInterface
{
    public function __construct(
        private readonly Adapters $adapters,
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
            foreach ($station->getMounts() as $mount) {
                /** @var StationMount $mount */
                if (!$mount->getIsVisibleOnPublicPages()) {
                    continue;
                }

                $streamUrl = $fa->getUrlForMount($station, $mount);

                $streamUrls[] = $streamUrl;
                $streams[] = [
                    'name' => $station->getName() . ' - ' . $mount->getDisplayName(),
                    'url' => $streamUrl,
                ];
            }
        }

        foreach ($station->getRemotes() as $remote) {
            if (!$remote->getIsVisibleOnPublicPages()) {
                continue;
            }

            $streamUrl = $this->adapters->getRemoteAdapter($remote)
                ->getPublicUrl($remote);

            $streamUrls[] = $streamUrl;
            $streams[] = [
                'name' => $station->getName() . ' - ' . $remote->getDisplayName(),
                'url' => $streamUrl,
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
