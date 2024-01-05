<?php

declare(strict_types=1);

namespace App\Assets;

use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\ImageInterface;

final class AlbumArtCustomAsset extends AbstractMultiPatternCustomAsset
{
    protected function getPatterns(): array
    {
        return [
            'default' => [
                'album_art%s.jpg',
                new JpegEncoder(90),
            ],
            'image/png' => [
                'album_art%s.png',
                new PngEncoder(),
            ],
            'image/webp' => [
                'album_art%s.webp',
                new WebpEncoder(90),
            ],
        ];
    }

    protected function getDefaultUrl(): string
    {
        return $this->environment->getAssetUrl() . '/img/generic_song.jpg';
    }

    public function upload(ImageInterface $image, string $mimeType): void
    {
        $newImage = clone $image;
        $newImage->resizeDown(1500, 1500);

        $this->delete();

        $patterns = $this->getPatterns();
        [$pattern, $encoder] = $patterns[$mimeType] ?? $patterns['default'];

        $destPath = $this->getPathForPattern($pattern);
        $this->ensureDirectoryExists(dirname($destPath));

        $newImage->encode($encoder)->save($destPath);
    }
}
