<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Urls;
use Psr\Http\Message\UriInterface;

class StationBrandingConfiguration extends AbstractStationConfiguration
{
    public const DEFAULT_ALBUM_ART_URL = 'default_album_art_url';

    public function getDefaultAlbumArtUrl(): ?string
    {
        return $this->get(self::DEFAULT_ALBUM_ART_URL);
    }

    public function getDefaultAlbumArtUrlAsUri(): ?UriInterface
    {
        return Urls::tryParseUserUrl(
            $this->getDefaultAlbumArtUrl(),
            'Station Default Album Art URL',
            false
        );
    }

    public function setDefaultAlbumArtUrl(?string $default_album_art_url): void
    {
        $this->set(self::DEFAULT_ALBUM_ART_URL, $default_album_art_url);
    }
}
