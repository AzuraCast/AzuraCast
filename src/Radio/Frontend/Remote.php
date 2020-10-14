<?php

namespace App\Radio\Frontend;

use App\Entity;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class Remote extends AbstractFrontend
{
    public static function supportsMounts(): bool
    {
        return false;
    }

    public static function supportsListenerDetail(): bool
    {
        return false;
    }

    public function read(Entity\Station $station): bool
    {
        return true;
    }

    public function write(Entity\Station $station): bool
    {
        return true;
    }

    public function isRunning(Entity\Station $station): bool
    {
        return true;
    }

    public function getStreamUrl(Entity\Station $station, UriInterface $base_url = null): UriInterface
    {
        return new Uri('');
    }

    /**
     * @inheritDoc
     */
    public function getStreamUrls(Entity\Station $station, UriInterface $base_url = null): array
    {
        return [];
    }

    public function getAdminUrl(Entity\Station $station, UriInterface $base_url = null): UriInterface
    {
        return new Uri('');
    }
}
