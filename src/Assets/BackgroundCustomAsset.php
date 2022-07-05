<?php

declare(strict_types=1);

namespace App\Assets;

use App\Environment;
use Intervention\Image\Constraint;
use Intervention\Image\Image;

final class BackgroundCustomAsset extends AbstractCustomAsset
{
    protected function getPattern(): string
    {
        return 'background%s.png';
    }

    protected function getDefaultUrl(): string
    {
        return Environment::getInstance()->getAssetUrl() . '/img/hexbg.png';
    }

    public function upload(Image $image): void
    {
        $newImage = clone $image;
        $newImage->resize(3264, 2160, function (Constraint $constraint) {
            $constraint->upsize();
        });
        $newImage->save($this->getPath());
    }
}
