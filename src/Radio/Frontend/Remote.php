<?php

declare(strict_types=1);

namespace App\Radio\Frontend;

use App\Entity;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class Remote extends AbstractFrontend
{
    public function isInstalled(): bool
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
