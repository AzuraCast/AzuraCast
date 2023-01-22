<?php

declare(strict_types=1);

namespace App\Assets;

use Intervention\Image\Constraint;
use Intervention\Image\Image;

final class BackgroundCustomAsset extends AbstractMultiPatternCustomAsset
{
    protected function getPatterns(): array
    {
        return [
            'default' => 'background%s.jpg',
            'image/png' => 'background%s.png',
            'image/webp' => 'background%s.webp',
        ];
    }

    protected function getDefaultUrl(): string
    {
        return $this->environment->getAssetUrl() . '/img/hexbg.png';
    }

    public function upload(Image $image): void
    {
        $newImage = clone $image;
        $newImage->resize(3264, 2160, function (Constraint $constraint) {
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
