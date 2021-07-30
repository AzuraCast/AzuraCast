<?php

declare(strict_types=1);

namespace App\Assets;

use Intervention\Image\Constraint;
use Intervention\Image\Image;

class AlbumArtCustomAsset extends AbstractCustomAsset
{
    protected function getPattern(): string
    {
        return 'album_art%s.jpg';
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
        $newImage->save($this->getPath());
    }
}
