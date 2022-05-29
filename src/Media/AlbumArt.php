<?php

declare(strict_types=1);

namespace App\Media;

use Intervention\Image\Constraint;
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
        $newArtwork = self::getImageManager()->make($rawArtworkString);
        $newArtwork->fit(
            $width,
            $height,
            function (Constraint $constraint) use ($upsize) {
                if (!$upsize) {
                    $constraint->upsize();
                }
            }
        );

        $newArtwork->encode('jpg');

        return $newArtwork->getEncoded();
    }

    public static function getImageManager(): ImageManager
    {
        return new ImageManager(
            [
                'driver' => 'gd',
            ]
        );
    }
}
