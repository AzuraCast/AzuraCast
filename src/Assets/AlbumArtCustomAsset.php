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
            'default' => 'album_art%s.webp',
            'image/jpeg' => 'album_art%s.jpg',
        ];
    }

    protected function getDefaultUrl(): string
    {
        return $this->environment->getAssetUrl() . '/img/generic_song.webp';
    }

    public function upload(Image $image): void
    {
        $newImage = clone $image;
        $newImage->resize(1500, 1500, function (Constraint $constraint) {
            $constraint->upsize();
        });

        $this->delete();

        $pattern = $this->getPattern();

        $mimeType = $newImage->mime();
        $quality = ('image/png' === $mimeType) ? 100 : 90;

        $newImage->save($this->getPathForPattern($pattern), $quality);
    }
}
