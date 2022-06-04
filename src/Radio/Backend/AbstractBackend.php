<?php

declare(strict_types=1);

namespace App\Radio\Backend;

use App\Entity;
use App\Nginx\CustomUrls;
use App\Radio\AbstractAdapter;
use App\Radio\Enums\StreamFormats;
use Psr\Http\Message\UriInterface;

abstract class AbstractBackend extends AbstractAdapter
{
    public function supportsMedia(): bool
    {
        return false;
    }

    public function supportsRequests(): bool
    {
        return false;
    }

    public function supportsStreamers(): bool
    {
        return false;
    }

    public function supportsWebStreaming(): bool
    {
        return false;
    }

    public function supportsImmediateQueue(): bool
    {
        return false;
    }

    public function supportsHls(): bool
    {
        return false;
    }

    public function getDefaultHlsStreams(Entity\Station $station): array
    {
        return array_map(
            function (string $name, int $bitrate) use ($station) {
                $record = new Entity\StationHlsStream($station);
                $record->setName($name);
                $record->setFormat(StreamFormats::Aac->value);
                $record->setBitrate($bitrate);
            },
            ['aac_lofi', 'aac_midfi', 'aac_hifi'],
            [64, 128, 256]
        );
    }

    public function getHlsUrl(Entity\Station $station, UriInterface $baseUrl = null): UriInterface
    {
        if (!$this->supportsHls()) {
            throw new \RuntimeException('Cannot generate HLS URL.');
        }

        $baseUrl ??= $this->router->getBaseUrl();

        return $baseUrl->withPath(
            $baseUrl->getPath() . CustomUrls::getHlsUrl($station) . '/live.m3u8'
        );
    }

    public function getStreamPort(Entity\Station $station): ?int
    {
        return null;
    }

    /**
     * @param Entity\StationMedia $media
     *
     * @return mixed[]
     */
    public function annotateMedia(Entity\StationMedia $media): array
    {
        return [];
    }

    public function getProgramName(Entity\Station $station): string
    {
        return 'station_' . $station->getId() . ':station_' . $station->getId() . '_backend';
    }
}
