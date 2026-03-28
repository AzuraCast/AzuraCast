<?php

declare(strict_types=1);

namespace App\Assets;

final readonly class CustomAssetFactory
{
    public function __construct(
        private AlbumArtCustomAsset $albumArtCustomAsset,
        private BackgroundCustomAsset $backgroundCustomAsset,
        private BrowserIconCustomAsset $browserIconCustomAsset
    ) {
    }

    public function getForType(
        string|AssetTypes $type
    ): CustomAssetInterface {
        if (is_string($type)) {
            $type = AssetTypes::from($type);
        }

        return match ($type) {
            AssetTypes::AlbumArt => $this->albumArtCustomAsset,
            AssetTypes::Background => $this->backgroundCustomAsset,
            AssetTypes::BrowserIcon => $this->browserIconCustomAsset
        };
    }
}
