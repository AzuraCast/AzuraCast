<?php

declare(strict_types=1);

namespace App\Customization;

use Intervention\Image\ImageManager;

class BrowserIcons
{
    public const ICON_SIZES = [
        16, // Favicon
        32, // Favicon
        36, // Android
        48, // Android
        57, // Apple
        60, // Apple
        72, // Android/Apple
        76, // Apple
        96, // Android/Favicon
        114, // Apple
        120, // Apple
        144, // Android/Apple/MS
        152, // Apple
        180, // Apple
        192, // Android/Apple
    ];

    public function __construct(
        protected ImageManager $imageManager
    ) {
    }

    public function makeIcons(string $originalPath, ?string $outputDir = null): void
    {
        if (!is_file($originalPath)) {
            throw new \InvalidArgumentException('Original path specified not valid.');
        }

        $outputDir ??= dirname($originalPath);

        $image = $this->imageManager->make($originalPath);
        $image->resize(256, 256);
        $image->save($outputDir . '/original.png');

        foreach (self::ICON_SIZES as $iconSize) {
            $image = $this->imageManager->make($originalPath);
            $image->resize($iconSize, $iconSize);
            $image->save($outputDir . '/' . $iconSize . '.png');
        }
    }
}
