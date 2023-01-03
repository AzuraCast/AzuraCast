<?php

declare(strict_types=1);

namespace App\Assets;

abstract class AbstractMultiPatternCustomAsset extends AbstractCustomAsset
{
    abstract protected function getPatterns(): array;

    protected function getPattern(): string
    {
        return $this->getPatterns()['default'];
    }

    protected function getPathForPattern(string $pattern): string
    {
        $pattern = sprintf($pattern, '');
        return $this->getBasePath() . '/' . $pattern;
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

        return $this->getPathForPattern($patterns['default']);
    }

    public function delete(): void
    {
        foreach ($this->getPatterns() as $pattern) {
            @unlink($this->getPathForPattern($pattern));
        }
    }

    public function getUrl(): string
    {
        foreach ($this->getPatterns() as $pattern) {
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
