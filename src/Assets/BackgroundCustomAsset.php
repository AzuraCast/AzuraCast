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
        return $this->getPatterns()['default'];
    }

    private function getPatterns(): array
    {
        return [
            'image/png' => 'background%s.png',
            'default' => 'background%s.jpg',
        ];
    }

    private function getPathForPattern(string $pattern): string
    {
        $pattern = sprintf($pattern, '');
        return Environment::getInstance()->getUploadsDirectory() . '/' . $pattern;
    }

    public function getPath(): string
    {
        $patterns = $this->getPatterns();
        foreach ($patterns as $pattern) {
            $path = $this->getPathForPattern($pattern);
            if (is_file($path)) {
                return $path;
            }
        }

        return $patterns['default'];
    }

    protected function getDefaultUrl(): string
    {
        return Environment::getInstance()->getAssetUrl() . '/img/hexbg.png';
    }

    public function delete(): void
    {
        foreach ($this->getPatterns() as $pattern) {
            @unlink($this->getPathForPattern($pattern));
        }
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

        $newImage->save($this->getPathForPattern($pattern), 90);
    }

    public function getUrl(): string
    {
        foreach ($this->getPatterns() as $pattern) {
            $path = $this->getPathForPattern($pattern);

            if (is_file($path)) {
                $mtime = filemtime($path);

                return Environment::getInstance()->getAssetUrl() . self::UPLOADS_URL_PREFIX . '/' . sprintf(
                    $pattern,
                    '.' . $mtime
                );
            }
        }

        return $this->getDefaultUrl();
    }
}
