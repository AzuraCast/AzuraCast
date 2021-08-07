<?php

declare(strict_types=1);

namespace App\Assets;

use App\Environment;

class AssetFactory
{
    public const TYPE_ALBUM_ART = 'album_art';
    public const TYPE_BACKGROUND = 'background';
    public const TYPE_BROWSER_ICON = 'browser_icon';

    public static function createAlbumArt(Environment $environment): AlbumArtCustomAsset
    {
        return new AlbumArtCustomAsset($environment);
    }

    public static function createBackground(Environment $environment): BackgroundCustomAsset
    {
        return new BackgroundCustomAsset($environment);
    }

    public static function createBrowserIcon(Environment $environment): BrowserIconCustomAsset
    {
        return new BrowserIconCustomAsset($environment);
    }

    public static function createForType(Environment $environment, string $type): CustomAssetInterface
    {
        return match ($type) {
            self::TYPE_ALBUM_ART => self::createAlbumArt($environment),
            self::TYPE_BACKGROUND => self::createBackground($environment),
            self::TYPE_BROWSER_ICON => self::createBrowserIcon($environment),
            default => throw new \InvalidArgumentException('Invalid type specified.')
        };
    }
}
