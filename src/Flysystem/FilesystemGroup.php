<?php
namespace App\Flysystem;

use InvalidArgumentException;
use Iterator;
use Jhofm\FlysystemIterator\FilesystemFilterIterator;
use Jhofm\FlysystemIterator\FilesystemIterator;
use Jhofm\FlysystemIterator\Options\Options;
use Jhofm\FlysystemIterator\RecursiveFilesystemIteratorIterator;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\AbstractCache;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use RuntimeException;

class FilesystemGroup extends MountManager
{
    /**
     * "Upload" a local path into the Flysystem abstract filesystem.
     *
     * @param string $local_path
     * @param string $to
     * @param array $config
     *
     * @return bool
     */
    public function upload($local_path, $to, array $config = []): bool
    {
        if (!file_exists($local_path)) {
            throw new RuntimeException(sprintf('Source upload file not found at path: %s', $local_path));
        }

        $stream = fopen($local_path, 'rb+');

        $uploaded = $this->putStream($to, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($uploaded) {
            @unlink($local_path);
            return true;
        }

        return false;
    }

    /**
     * If the adapter associated with the specified URI is a local one, get the full filesystem path.
     *
     * NOTE: This can only be assured for the temp:// and config:// prefixes. Other prefixes can (and will)
     *       use non-local adapters that will trigger an exception here.
     *
     * @param string $uri
     *
     * @return string
     */
    public function getFullPath($uri): string
    {
        [$prefix, $path] = $this->getPrefixAndPath($uri);

        $fs = $this->getFilesystem($prefix);

        if (!($fs instanceof Filesystem)) {
            throw new InvalidArgumentException(sprintf('Filesystem for "%s" is not an instance of Filesystem.',
                $prefix));
        }

        $adapter = $fs->getAdapter();

        if ($adapter instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        if (!($adapter instanceof Local)) {
            throw new InvalidArgumentException(sprintf('Adapter for "%s" is not a Local or cached Local adapter.',
                $prefix));
        }

        $prefix = $adapter->getPathPrefix();
        return $prefix . $path;
    }

    /**
     * Flush the caches of all associated filesystems.
     *
     * @param bool $in_memory_only Set to TRUE to only flush the current PHP process's memory, not the Redis cache.
     */
    public function flushAllCaches($in_memory_only = false): void
    {
        foreach ($this->filesystems as $prefix => $filesystem) {
            if ($filesystem instanceof Filesystem) {
                $adapter = $filesystem->getAdapter();
                if ($adapter instanceof CachedAdapter) {
                    $cache = $adapter->getCache();

                    if ($in_memory_only && $cache instanceof AbstractCache) {
                        $prev_autosave = $cache->getAutosave();
                        $cache->setAutosave(false);
                        $cache->flush();
                        $cache->setAutosave($prev_autosave);
                    } else {
                        $cache->flush();
                    }
                }
            }
        }
    }

    /**
     * Create an iterator that loops through the entire contents of a given prefix.
     *
     * @param string $uri
     * @param array $iteratorOptions
     *
     * @return Iterator
     */
    public function createIterator(string $uri, array $iteratorOptions = []): Iterator
    {
        [$prefix, $path] = $this->getPrefixAndPath($uri);

        $fs = $this->getFilesystem($prefix);
        if (!($fs instanceof Filesystem)) {
            throw new RuntimeException('Filesystem cannot be iterated.');
        }

        $iterator = new FilesystemIterator($fs, $path, $iteratorOptions);

        $options = Options::fromArray($iteratorOptions);
        if ($options->{Options::OPTION_IS_RECURSIVE}) {
            $iterator = new RecursiveFilesystemIteratorIterator($iterator);
        }
        if ($options->{Options::OPTION_FILTER} !== null) {
            $iterator = new FilesystemFilterIterator($iterator, $options->{Options::OPTION_FILTER});
        }
        return $iterator;
    }
}