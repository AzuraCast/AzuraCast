<?php

declare(strict_types=1);

namespace App\Assets;

use App\Environment;

class AssetFactory
{
    public const TYPE_BACKGROUND = 'background';
    public const TYPE_FAVICON = 'favicon';
    public const TYPE_ALBUM_ART = 'album_art';

    public static function createForType(Environment $environment, string $type): CustomAssetInterface
    {
        return match ($type) {
            self::TYPE_BACKGROUND => new BackgroundCustomAsset($environment),
            default => throw new \InvalidArgumentException('Invalid type specified.')
        };
    }
}
