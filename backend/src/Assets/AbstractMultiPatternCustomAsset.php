<?php

declare(strict_types=1);

namespace App\Assets;

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

    protected function getPathForPattern(string $pattern): string
    {
        $pattern = sprintf($pattern, '');
        return $this->getBasePath() . '/' . $pattern;
    }

    public function getPath(): string
    {
        $patterns = $this->getPatterns();
        foreach ($patterns as [$pattern, $encoder]) {
            $path = $this->getPathForPattern($pattern);
            if (is_file($path)) {
                return $path;
            }
        }

        return $this->getPathForPattern($patterns['default'][0]);
    }

    public function delete(): void
    {
        foreach ($this->getPatterns() as [$pattern, $encoder]) {
            @unlink($this->getPathForPattern($pattern));
        }
    }

    public function getUrl(): string
    {
        foreach ($this->getPatterns() as [$pattern, $encoder]) {
            $path = $this->getPathForPattern($pattern);

            if (is_file($path)) {
                $mtime = filemtime($path);

                return $this->getBaseUrl() . '/' . sprintf(
                    $pattern,
                    '.' . $mtime
                );
            }
        }

        return $this->getDefaultUrl();
    }
}
