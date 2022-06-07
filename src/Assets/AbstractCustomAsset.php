<?php

declare(strict_types=1);

namespace App\Assets;

use App\Environment;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

abstract class AbstractCustomAsset implements CustomAssetInterface
{
    abstract protected function getPattern(): string;

    abstract protected function getDefaultUrl(): string;

    public function getPath(): string
    {
        $pattern = sprintf($this->getPattern(), '');
        return Environment::getInstance()->getUploadsDirectory() . '/' . $pattern;
    }

    public function getUrl(): string
    {
        $path = $this->getPath();
        if (is_file($path)) {
            $pattern = $this->getPattern();
            $mtime = filemtime($path);

            return Environment::getInstance()->getAssetUrl() . self::UPLOADS_URL_PREFIX . '/' . sprintf(
                $pattern,
                '.' . $mtime
            );
        }

        return $this->getDefaultUrl();
    }

    public function getUri(): UriInterface
    {
        return new Uri($this->getUrl());
    }

    public function isUploaded(): bool
    {
        return is_file($this->getPath());
    }

    public function delete(): void
    {
        @unlink($this->getPath());
    }
}
