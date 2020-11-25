<?php

namespace App\Flysystem;

use App\Http\Response;
use InvalidArgumentException;
use Iterator;
use Jhofm\FlysystemIterator\FilesystemFilterIterator;
use Jhofm\FlysystemIterator\FilesystemIterator;
use Jhofm\FlysystemIterator\Options\Options;
use Jhofm\FlysystemIterator\RecursiveFilesystemIteratorIterator;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\AbstractCache;
use League\Flysystem\Filesystem as LeagueFilesystem;
use Psr\Http\Message\ResponseInterface;

class Filesystem extends LeagueFilesystem implements FilesystemInterface
{
    /**
     * Call a callable function with a path that is guaranteed to be a local path, even if
     * this filesystem is a remote one, by copying to a temporary directory first in the
     * case of remote filesystems.
     *
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

            try {
                $returnVal = $function($tempPath);
            } finally {
                unlink($tempPath);
            }

            return $returnVal;
        }
    }

    public function putFromLocal(string $localPath, string $to): bool
    {
        $uploaded = $this->copyFromLocal($localPath, $to);

        if ($uploaded) {
            @unlink($localPath);
        }

        return $uploaded;
    }

    public function copyFromLocal(string $localPath, string $to): bool
    {
        if (!file_exists($localPath)) {
            throw new \RuntimeException(sprintf('Source upload file not found at path: %s', $localPath));
        }

        $stream = fopen($localPath, 'rb');

        $uploaded = $this->putStream($to, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $uploaded;
    }

    public function copyToLocal(string $from, ?string $localPath = null): string
    {
        if (null === $localPath) {
            $folderPrefix = substr(md5($from), 0, 10);
            $localPath = sys_get_temp_dir() . '/' . $folderPrefix . '_' . basename($from);
        }

        if (file_exists($localPath)) {
            if (filemtime($localPath) >= $this->getTimestamp($from)) {
                touch($localPath);
                return $localPath;
            }

            unlink($localPath);
        }

        $stream = $this->readStream($from);

        file_put_contents($localPath, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $localPath;
    }

    public function clearCache(bool $inMemoryOnly = false): void
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

    /**
     * Create an iterator that loops through the entire contents of a given prefix.
     *
     * @param string $path
     * @param array $iteratorOptions
     *
     */
    public function createIterator(string $path, array $iteratorOptions = []): Iterator
    {
        $iterator = new FilesystemIterator($this, $path, $iteratorOptions);

        $options = Options::fromArray($iteratorOptions);
        if ($options->{Options::OPTION_IS_RECURSIVE}) {
            $iterator = new RecursiveFilesystemIteratorIterator($iterator);
        }
        if ($options->{Options::OPTION_FILTER} !== null) {
            $iterator = new FilesystemFilterIterator($iterator, $options->{Options::OPTION_FILTER});
        }

        return $iterator;
    }

    /** @inheritDoc */
    public function streamToResponse(
        Response $response,
        string $path,
        string $fileName = null,
        string $disposition = 'attachment'
    ): ResponseInterface {
        $meta = $this->getMetadata($path);

        try {
            $mime = $this->getMimetype($path);
        } catch (\Exception $e) {
            $mime = 'application/octet-stream';
        }

        $fileName ??= basename($path);

        if ('attachment' === $disposition) {
            /*
             * The regex used below is to ensure that the $fileName contains only
             * characters ranging from ASCII 128-255 and ASCII 0-31 and 127 are replaced with an empty string
             */
            $disposition .= '; filename="' . preg_replace('/[\x00-\x1F\x7F\"]/', ' ', $fileName) . '"';
            $disposition .= "; filename*=UTF-8''" . rawurlencode($fileName);
        }

        $response = $response->withHeader('Content-Disposition', $disposition)
            ->withHeader('Content-Length', $meta['size'])
            ->withHeader('X-Accel-Buffering', 'no');

        try {
            $localPath = $this->getFullPath($path);
        } catch (InvalidArgumentException $e) {
            $localPath = $this->copyToLocal($path);
        }

        // Special internal nginx routes to use X-Accel-Redirect for far more performant file serving.
        $specialPaths = [
            '/var/azuracast/backups' => '/internal/backups',
            '/var/azuracast/stations' => '/internal/stations',
        ];

        foreach ($specialPaths as $diskPath => $nginxPath) {
            if (0 === strpos($localPath, $diskPath)) {
                $accelPath = str_replace($diskPath, $nginxPath, $localPath);

                // Temporary work around, see SlimPHP/Slim#2924
                $response->getBody()->write(' ');

                return $response->withHeader('Content-Type', $mime)
                    ->withHeader('X-Accel-Redirect', $accelPath);
            }
        }


        return $response->withFile($localPath, $mime);
    }
}
