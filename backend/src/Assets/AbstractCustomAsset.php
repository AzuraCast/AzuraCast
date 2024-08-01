<?php

declare(strict_types=1);

namespace App\Assets;

use App\Container\EnvironmentAwareTrait;
use App\Entity\Station;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractCustomAsset implements CustomAssetInterface
{
    use EnvironmentAwareTrait;

    public function __construct(
        protected readonly ?Station $station = null
    ) {
    }

    abstract protected function getPattern(): string;

    abstract protected function getDefaultUrl(): string;

    public function getPath(): string
    {
        $pattern = sprintf($this->getPattern(), '');
        return $this->getBasePath() . '/' . $pattern;
    }

    protected function getBasePath(): string
    {
        $basePath = $this->environment->getUploadsDirectory();

        if (null !== $this->station) {
            $basePath .= '/' . $this->station->getShortName();
        }

        return $basePath;
    }

    public function getUrl(): string
    {
        $path = $this->getPath();
        if (is_file($path)) {
            $pattern = $this->getPattern();
            $mtime = filemtime($path);

            return $this->getBaseUrl() . '/' . sprintf(
                $pattern,
                '.' . $mtime
            );
        }

        return $this->getDefaultUrl();
    }

    protected function getBaseUrl(): string
    {
        $baseUrl = $this->environment->getAssetUrl() . self::UPLOADS_URL_PREFIX;

        if (null !== $this->station) {
            $baseUrl .= '/' . $this->station->getShortName();
        }

        return $baseUrl;
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

    protected function ensureDirectoryExists(string $path): void
    {
        (new Filesystem())->mkdir($path);
    }
}
