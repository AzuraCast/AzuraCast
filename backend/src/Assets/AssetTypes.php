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
        $instance = match ($this) {
            self::AlbumArt => new AlbumArtCustomAsset($station),
            self::Background => new BackgroundCustomAsset($station),
            self::BrowserIcon => new BrowserIconCustomAsset($station),
        };
        $instance->setEnvironment($environment);
        return $instance;
    }
}
