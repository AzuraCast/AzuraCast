<?php

declare(strict_types=1);

namespace App\Assets;

use App\Environment;

enum AssetTypes: string
{
    case AlbumArt = 'album_art';
    case Background = 'background';
    case BrowserIcon = 'browser_icon';

    public function createObject(Environment $environment): CustomAssetInterface
    {
        return match ($this) {
            self::AlbumArt => new AlbumArtCustomAsset($environment),
            self::Background => new BackgroundCustomAsset($environment),
            self::BrowserIcon => new BrowserIconCustomAsset($environment),
        };
    }
}
