<?php

declare(strict_types=1);

namespace App\Media;

use App\Entity;
use App\Environment;
use App\Event\Media\ReadMetadata;
use App\Event\Media\WriteMetadata;
use App\Exception\CannotProcessMediaException;
use App\Utilities\File;
use App\Utilities\Json;
use GuzzleHttp\Client;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class MetadataManager implements EventSubscriberInterface
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected Client $httpClient,
        protected Environment $environment
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            ReadMetadata::class => [
                ['readFromId3', 0],
            ],
            WriteMetadata::class => [
                ['writeToId3', 0],
            ],
        ];
    }

    public function read(string $filePath): Entity\Metadata
    {
        if (!MimeType::isFileProcessable($filePath)) {
            $mimeType = MimeType::getMimeTypeFromFile($filePath);
            throw CannotProcessMediaException::forPath(
                $filePath,
                sprintf('MIME type "%s" is not processable.', $mimeType)
            );
        }

        $event = new ReadMetadata($filePath);
        $this->eventDispatcher->dispatch($event);

        return $event->getMetadata();
    }

    public function readFromId3(ReadMetadata $event): void
    {
        $sourceFilePath = $event->getPath();

        $jsonOutput = File::generateTempPath('metadata.json');
        $artOutput = File::generateTempPath('metadata.jpg');

        try {
            $phpBinaryPath = (new PhpExecutableFinder())->find();
            if (false === $phpBinaryPath) {
                throw new \RuntimeException('Could not find PHP executable path.');
            }

            $scriptPath = $this->environment->getBaseDirectory() . '/bin/metadata';

            $process = new Process(
                [
                    $phpBinaryPath,
                    $scriptPath,
                    'read',
                    $sourceFilePath,
                    $jsonOutput,
                    '--art-output=' . $artOutput,
                ]
            );

            $process->mustRun();

            $metadataJson = Json::loadFromFile($jsonOutput);
            $metadata = Entity\Metadata::fromJson($metadataJson);

            if (is_file($artOutput)) {
                $artwork = file_get_contents($artOutput) ?: null;
                $metadata->setArtwork($artwork);
            }

            $event->setMetadata($metadata);
        } finally {
            @unlink($jsonOutput);
            @unlink($artOutput);
        }
    }

    public function write(Entity\Metadata $metadata, string $filePath): void
    {
        $event = new WriteMetadata($metadata, $filePath);
        $this->eventDispatcher->dispatch($event);
    }

    public function writeToId3(WriteMetadata $event): void
    {
        $destFilePath = $event->getPath();

        $metadata = $event->getMetadata();
        if (null === $metadata) {
            return;
        }

        $jsonInput = File::generateTempPath('metadata.json');
        $artInput = File::generateTempPath('metadata.jpg');

        try {
            // Write input files for the metadata process.
            file_put_contents(
                $jsonInput,
                json_encode($metadata, JSON_THROW_ON_ERROR)
            );

            $artwork = $metadata->getArtwork();
            if (null !== $artwork) {
                file_put_contents(
                    $artInput,
                    $artwork
                );
            }

            // Run remote process.
            $phpBinaryPath = (new PhpExecutableFinder())->find();
            if (false === $phpBinaryPath) {
                throw new \RuntimeException('Could not find PHP executable path.');
            }

            $scriptPath = $this->environment->getBaseDirectory() . '/bin/metadata';

            $processCommand = [
                $phpBinaryPath,
                $scriptPath,
                'write',
                $destFilePath,
                $jsonInput,
            ];

            if (null !== $artwork) {
                $processCommand[] = '--art-input=' . $artInput;
            }

            $process = new Process($processCommand);
            $process->run();
        } finally {
            @unlink($jsonInput);
            @unlink($artInput);
        }
    }
}
