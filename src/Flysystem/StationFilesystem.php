<?php
namespace App\Flysystem;

use Azura\Exception;
use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\AbstractCache;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;

class StationFilesystem extends MountManager
{
    /**
     * Copy a file from the specified path to the temp directory
     *
     * @param string $from The permanent path to copy from
     * @param string|null $to The temporary path to copy to (temp://original if not specified)
     * @return string The temporary path
     */
    public function copyToTemp($from, $to = null): string
    {
        [$prefix_from, $path_from] = $this->getPrefixAndPath($from);

        if (null === $to) {
            $random_prefix = substr(md5(random_bytes(8)), 0, 5);
            $to = 'temp://' . $random_prefix . '_' . $path_from;
        }

        if ($this->has($to)) {
            $this->delete($to);
        }

        $this->copy($from, $to);

        return $to;
    }

    /**
     * Update the value of a permanent file from a temporary directory.
     *
     * @param string $from The temporary path to update from
     * @param string $to The permanent path to update to
     * @param array $config
     * @return string
     */
    public function updateFromTemp($from, $to, array $config = []): string
    {
        $buffer = $this->readStream($from);
        if ($buffer === false) {
            throw new Exception('Source file could not be read.');
        }

        $written = $this->putStream($to, $buffer, $config);

        if (is_resource($buffer)) {
            fclose($buffer);
        }

        if ($written) {
            $this->delete($from);
        }

        return $to;
    }

    /**
     * "Upload" a local path into the Flysystem abstract filesystem.
     *
     * @param string $local_path
     * @param string $to
     * @param array $config
     * @return bool
     */
    public function upload($local_path, $to, array $config = []): bool
    {
        $stream = fopen($local_path, 'r+');

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
     * @return string
     */
    public function getFullPath($uri): string
    {
        list($prefix, $path) = $this->getPrefixAndPath($uri);

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
}
