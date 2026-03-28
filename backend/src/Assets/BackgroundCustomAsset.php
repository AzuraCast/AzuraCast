<?php

declare(strict_types=1);

namespace App\Assets;

use App\Entity\Station;
use DI\Attribute\Injectable;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\ImageInterface;

#[Injectable(lazy: true)]
final class BackgroundCustomAsset extends AbstractMultiPatternCustomAsset
{
    protected function getPatterns(): array
    {
        return [
            'default' => [
                'background%s.jpg',
                new JpegEncoder(90),
            ],
            'image/png' => [
                'background%s.png',
                new PngEncoder(),
            ],
            'image/webp' => [
                'background%s.webp',
                new WebpEncoder(90),
            ],
        ];
    }

    protected function getDefaultUrl(): string
    {
        return $this->vite->getImagePath('img/hexbg.webp');
    }

    public function upload(
        ImageInterface $image,
        string $mimeType,
        ?Station $station = null
    ): void {
        $newImage = clone $image;
        $newImage->resizeDown(3264, 2160);

        $this->delete($station);

        $patterns = $this->getPatterns();
        [$pattern, $encoder] = $patterns[$mimeType] ?? $patterns['default'];

        $destPath = $this->getPathForPattern($pattern, $station);
        $this->ensureDirectoryExists(dirname($destPath));

        $newImage->encode($encoder)->save($destPath);
    }
}
