<?php

declare(strict_types=1);

namespace App\Assets;

use App\Environment;
use Intervention\Image\Image;
use Symfony\Component\Filesystem\Filesystem;

final class BrowserIconCustomAsset extends AbstractCustomAsset
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

    protected function getPattern(): string
    {
        return 'browser_icon/original%s.png';
    }

    protected function getDefaultUrl(): string
    {
        $env = Environment::getInstance();

        $assetUrl = $env->getAssetUrl();
        return $assetUrl . '/icons/' . $env->getAppEnvironmentEnum()->value . '/original.png';
    }

    public function upload(Image $image): void
    {
        $uploadsDir = Environment::getInstance()->getUploadsDirectory() . '/browser_icon';
        (new Filesystem())->mkdir($uploadsDir);

        $newImage = clone $image;
        $newImage->resize(256, 256);
        $newImage->save($uploadsDir . '/original.png');

        foreach (self::ICON_SIZES as $iconSize) {
            $newImage = clone $image;
            $newImage->resize($iconSize, $iconSize);
            $newImage->save($uploadsDir . '/' . $iconSize . '.png');
        }
    }

    public function delete(): void
    {
        $uploadsDir = Environment::getInstance()->getUploadsDirectory() . '/browser_icon';
        (new Filesystem())->remove($uploadsDir);
    }

    public function getUrlForSize(int $size): string
    {
        $env = Environment::getInstance();
        $assetUrl = $env->getAssetUrl();

        $uploadsDir = $env->getUploadsDirectory();
        $iconPath = $uploadsDir . '/browser_icon/' . $size . '.png';

        if (is_file($iconPath)) {
            $mtime = filemtime($iconPath);
            return $assetUrl . '/uploads/browser_icon/' . $size . '.' . $mtime . '.png';
        }

        return $assetUrl . '/icons/' . $env->getAppEnvironmentEnum()->value . '/' . $size . '.png';
    }
}
