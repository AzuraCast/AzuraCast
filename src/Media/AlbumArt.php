<?php

declare(strict_types=1);

namespace App\Media;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

final class AlbumArt
{
    public const IMAGE_WIDTH = 1500;

    public static function resize(
        string $rawArtworkString,
        int $width = self::IMAGE_WIDTH,
        int $height = self::IMAGE_WIDTH,
        bool $upsize = false,
    ): string {
        $newArtwork = self::getImageManager()->read($rawArtworkString);

        if ($upsize) {
            $newArtwork->cover($width, $height);
        } else {
            $newArtwork->coverDown($width, $height);
        }

        return $newArtwork->toJpeg()->toString();
    }

    public static function getImageManager(): ImageManager
    {
        return new ImageManager(
            new Driver()
        );
    }
}
