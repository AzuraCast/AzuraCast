<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Types;
use App\Utilities\Urls;
use Psr\Http\Message\UriInterface;

class StationBrandingConfiguration extends AbstractStationConfiguration
{
    public const DEFAULT_ALBUM_ART_URL = 'default_album_art_url';

    public function getDefaultAlbumArtUrl(): ?string
    {
        return Types::stringOrNull($this->get(self::DEFAULT_ALBUM_ART_URL), true);
    }

    public function getDefaultAlbumArtUrlAsUri(): ?UriInterface
    {
        return Urls::tryParseUserUrl(
            $this->getDefaultAlbumArtUrl(),
            'Station Default Album Art URL',
            false
        );
    }

    public function setDefaultAlbumArtUrl(?string $defaultAlbumArtUrl): void
    {
        $this->set(self::DEFAULT_ALBUM_ART_URL, $defaultAlbumArtUrl);
    }

    public const PUBLIC_CUSTOM_CSS = 'public_custom_css';

    public function getPublicCustomCss(): ?string
    {
        return Types::stringOrNull($this->get(self::PUBLIC_CUSTOM_CSS), true);
    }

    public function setPublicCustomCss(?string $css): void
    {
        $this->set(self::PUBLIC_CUSTOM_CSS, $css);
    }

    public const PUBLIC_CUSTOM_JS = 'public_custom_js';

    public function getPublicCustomJs(): ?string
    {
        return Types::stringOrNull($this->get(self::PUBLIC_CUSTOM_JS), true);
    }

    public function setPublicCustomJs(?string $js): void
    {
        $this->set(self::PUBLIC_CUSTOM_JS, $js);
    }

    public const OFFLINE_TEXT = 'offline_text';

    public function getOfflineText(): ?string
    {
        return Types::stringOrNull($this->get(self::OFFLINE_TEXT), true);
    }

    public function setOfflineText(?string $message): void
    {
        $this->set(self::OFFLINE_TEXT, $message);
    }
}
