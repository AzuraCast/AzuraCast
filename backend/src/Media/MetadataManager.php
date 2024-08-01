<?php

declare(strict_types=1);

namespace App\Media;

use App\Container\LoggerAwareTrait;
use App\Event\Media\ReadMetadata;
use App\Event\Media\WriteMetadata;
use App\Exception\CannotProcessMediaException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class MetadataManager
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function read(string $filePath): MetadataInterface
    {
        if (!MimeType::isFileProcessable($filePath)) {
            $mimeType = MimeType::getMimeTypeFromFile($filePath);
            throw CannotProcessMediaException::forPath(
                $filePath,
                sprintf('MIME type "%s" is not processable.', $mimeType)
            );
        }

        try {
            $event = new ReadMetadata($filePath);
            $this->eventDispatcher->dispatch($event);

            return $event->getMetadata();
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Cannot read metadata for file "%s": %s',
                    $filePath,
                    $e->getMessage()
                ),
                [
                    'path' => $filePath,
                    'exception' => $e,
                ]
            );

            return new Metadata();
        }
    }

    public function write(MetadataInterface $metadata, string $filePath): void
    {
        try {
            $event = new WriteMetadata($metadata, $filePath);
            $this->eventDispatcher->dispatch($event);
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Cannot write metadata for file "%s": %s',
                    $filePath,
                    $e->getMessage()
                ),
                [
                    'path' => $filePath,
                    'exception' => $e,
                ]
            );
        }
    }
}
