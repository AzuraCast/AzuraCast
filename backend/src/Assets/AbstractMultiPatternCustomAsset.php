<?php

declare(strict_types=1);

namespace App\Assets;

use App\Entity\Station;
use Intervention\Image\Interfaces\EncoderInterface;

abstract class AbstractMultiPatternCustomAsset extends AbstractCustomAsset
{
    /**
     * @return array<string, array{string, EncoderInterface}>
     */
    abstract protected function getPatterns(): array;

    protected function getPattern(): string
    {
        return $this->getPatterns()['default'][0];
    }

    protected function getPathForPattern(
        string $pattern,
        ?Station $station = null
    ): string {
        $pattern = sprintf($pattern, '');
        return $this->getBasePath($station) . '/' . $pattern;
    }

    public function getPath(?Station $station = null): string
    {
        $patterns = $this->getPatterns();
        foreach ($patterns as [$pattern, $encoder]) {
            $path = $this->getPathForPattern($pattern, $station);
            if (is_file($path)) {
                return $path;
            }
        }

        return $this->getPathForPattern($patterns['default'][0], $station);
    }

    public function delete(?Station $station = null): void
    {
        foreach ($this->getPatterns() as [$pattern, $encoder]) {
            @unlink($this->getPathForPattern($pattern, $station));
        }
    }

    public function getUrl(?Station $station = null): string
    {
        foreach ($this->getPatterns() as [$pattern, $encoder]) {
            $path = $this->getPathForPattern($pattern, $station);

            if (is_file($path)) {
                $mtime = filemtime($path);

                return $this->getBaseUrl($station) . '/' . sprintf(
                    $pattern,
                    '.' . $mtime
                );
            }
        }

        return $this->getDefaultUrl();
    }
}
