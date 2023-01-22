<?php

declare(strict_types=1);

namespace App\Assets;

use Intervention\Image\Constraint;
use Intervention\Image\Image;

final class AlbumArtCustomAsset extends AbstractMultiPatternCustomAsset
{
    protected function getPatterns(): array
    {
        return [
            'default' => 'album_art%s.jpg',
            'image/png' => 'album_art%s.png',
            'image/webp' => 'album_art%s.webp',
        ];
    }

    protected function getDefaultUrl(): string
    {
        return $this->environment->getAssetUrl() . '/img/generic_song.jpg';
    }

    public function upload(Image $image): void
    {
        $newImage = clone $image;
        $newImage->resize(1500, 1500, function (Constraint $constraint) {
            $constraint->upsize();
        });

        $this->delete();

        $patterns = $this->getPatterns();
        $mimeType = $newImage->mime();

        $pattern = $patterns[$mimeType] ?? $patterns['default'];

        $destPath = $this->getPathForPattern($pattern);
        $this->ensureDirectoryExists(dirname($destPath));

        $newImage->save($destPath, 90);
    }
}
