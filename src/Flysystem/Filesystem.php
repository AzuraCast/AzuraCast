<?php

namespace App\Flysystem;

use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\AbstractCache;
use League\Flysystem\Filesystem as LeagueFilesystem;

class Filesystem extends LeagueFilesystem
{
    /**
     * @param string $path
     * @param callable $function
     *
     * @return mixed
     */
    public function withLocalFile(string $path, callable $function)
    {
        try {
            $localPath = $this->getFullPath($path);
            return $function($localPath);
        } catch (InvalidArgumentException $e) {
            $tempPath = $this->copyToLocal($path);
            $returnVal = $function($tempPath);
            unlink($tempPath);

            return $returnVal;
        }
    }

    public function putFromLocal(string $localPath, string $to): bool
    {
        if (!file_exists($localPath)) {
            throw new \RuntimeException(sprintf('Source upload file not found at path: %s', $localPath));
        }

        $stream = fopen($localPath, 'rb+');

        $uploaded = $this->putStream($to, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($uploaded) {
            @unlink($localPath);
            return true;
        }

        return false;
    }

    public function copyToLocal(string $from, ?string $to = null): string
    {
        if (null === $to) {
            $to = tempnam(sys_get_temp_dir(), $from);
        }

        if (file_exists($to)) {
            unlink($to);
        }

        $stream = $this->readStream($from);

        file_put_contents($to, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $to;
    }

    public function flushCache(bool $inMemoryOnly = false): void
    {
        $adapter = $this->getAdapter();
        if ($adapter instanceof CachedAdapter) {
            $cache = $adapter->getCache();

            if ($inMemoryOnly && $cache instanceof AbstractCache) {
                $prev_autosave = $cache->getAutosave();
                $cache->setAutosave(false);
                $cache->flush();
                $cache->setAutosave($prev_autosave);
            } else {
                $cache->flush();
            }
        }
    }

    public function getFullPath(string $path): string
    {
        $adapter = $this->getAdapter();
        if ($adapter instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        if (!($adapter instanceof Local)) {
            throw new InvalidArgumentException('Filesystem adapter is not a Local or cached Local adapter.');
        }

        return $adapter->applyPathPrefix($path);
    }
}
