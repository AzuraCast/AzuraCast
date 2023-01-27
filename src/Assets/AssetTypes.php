<?php

declare(strict_types=1);

namespace App\Assets;

use App\Entity\Station;
use App\Environment;

enum AssetTypes: string
{
    case AlbumArt = 'album_art';
    case Background = 'background';
    case BrowserIcon = 'browser_icon';

    public function createObject(
        Environment $environment,
        ?Station $station = null
    ): CustomAssetInterface {
        return match ($this) {
            self::AlbumArt => new AlbumArtCustomAsset($environment, $station),
            self::Background => new BackgroundCustomAsset($environment, $station),
            self::BrowserIcon => new BrowserIconCustomAsset($environment, $station),
        };
    }
}
