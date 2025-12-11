<?php

declare(strict_types=1);

namespace App\Assets;

use App\Container\EnvironmentAwareTrait;
use App\Entity\Station;
use App\Service\Vite;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractCustomAsset implements CustomAssetInterface
{
    use EnvironmentAwareTrait;

    public function __construct(
        protected readonly Vite $vite
    ) {
    }

    abstract protected function getPattern(): string;

    abstract protected function getDefaultUrl(): string;

    public function getPath(
        ?Station $station = null
    ): string {
        $pattern = sprintf($this->getPattern(), '');
        return $this->getBasePath($station) . '/' . $pattern;
    }

    protected function getBasePath(
        ?Station $station = null
    ): string {
        $basePath = $this->environment->getUploadsDirectory();

        if (null !== $station) {
            $basePath .= '/' . $station->short_name;
        }

        return $basePath;
    }

    public function getUrl(?Station $station = null): string
    {
        $path = $this->getPath($station);
        if (is_file($path)) {
            $pattern = $this->getPattern();
            $mtime = filemtime($path);

            return $this->getBaseUrl($station) . '/' . sprintf(
                $pattern,
                '.' . $mtime
            );
        }

        return $this->getDefaultUrl();
    }

    protected function getBaseUrl(
        ?Station $station = null
    ): string {
        $baseUrl = $this->environment->getAssetUrl() . self::UPLOADS_URL_PREFIX;

        if (null !== $station) {
            $baseUrl .= '/' . $station->short_name;
        }

        return $baseUrl;
    }

    public function getUri(?Station $station = null): UriInterface
    {
        return new Uri($this->getUrl($station));
    }

    public function isUploaded(?Station $station = null): bool
    {
        return is_file($this->getPath($station));
    }

    public function delete(?Station $station = null): void
    {
        @unlink($this->getPath($station));
    }

    protected function ensureDirectoryExists(string $path): void
    {
        new Filesystem()->mkdir($path);
    }
}
