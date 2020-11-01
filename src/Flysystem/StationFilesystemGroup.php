<?php

namespace App\Flysystem;

use App\Exception;
use App\Http\Response;
use Iterator;
use League\Flysystem\MountManager;
use Psr\Http\Message\ResponseInterface;

class StationFilesystemGroup extends MountManager implements FilesystemInterface
{
    public function upload(string $localPath, string $to): bool
    {
        [$prefix, $path] = $this->getPrefixAndPath($to);

        /** @var Filesystem $fs */
        $fs = $this->getFilesystem($prefix);

        return $fs->putFromLocal($localPath, $to);
    }

    public function clearCache(bool $inMemoryOnly = false): void
    {
        foreach ($this->filesystems as $prefix => $filesystem) {
            /** @var Filesystem $filesystem */
            $filesystem->clearCache($inMemoryOnly);
        }
    }

    public function getFullPath(string $uri): string
    {
        [$prefix, $path] = $this->getPrefixAndPath($uri);

        /** @var Filesystem $fs */
        $fs = $this->getFilesystem($prefix);

        return $fs->getFullPath($path);
    }

    public function getLocalPath(string $uri): string
    {
        [$prefix, $path] = $this->getPrefixAndPath($uri);

        /** @var Filesystem $fs */
        $fs = $this->getFilesystem($prefix);

        try {
            return $fs->getFullPath($path);
        } catch (\InvalidArgumentException $e) {
            return $this->copyToTemp($uri);
        }
    }

    public function copyToTemp(string $from, ?string $to = null): string
    {
        [, $fromPath] = $this->getPrefixAndPath($from);

        if (null === $to) {
            $randomPrefix = gmdate('Ymdhis') . substr(md5(random_bytes(8)), 0, 5);
            $to = FilesystemManager::PREFIX_TEMP . '://' . $randomPrefix . '_' . basename($fromPath);
        }

        if ($this->has($to)) {
            $this->delete($to);
        }

        $this->copy($from, $to);

        return $to;
    }

    public function putFromTemp(string $from, string $to, array $config = []): string
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

    /** @inheritDoc */
    public function createIterator(string $path, array $iteratorOptions = []): Iterator
    {
        [$prefix, $path] = $this->getPrefixAndPath($path);

        /** @var FilesystemInterface $fs */
        $fs = $this->getFilesystem($prefix);
        return $fs->createIterator($path, $iteratorOptions);
    }

    /** @inheritDoc */
    public function streamToResponse(
        Response $response,
        string $path,
        string $fileName = null,
        string $disposition = 'attachment'
    ): ResponseInterface {
        [$prefix, $path] = $this->getPrefixAndPath($path);

        /** @var FilesystemInterface $fs */
        $fs = $this->getFilesystem($prefix);
        return $fs->streamToResponse($response, $path, $fileName, $disposition);
    }
}
