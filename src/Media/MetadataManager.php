<?php

namespace App\Media;

use App\Entity;
use App\Event\Media\ReadMetadata;
use App\Event\Media\WriteMetadata;
use App\EventDispatcher;
use App\Exception\CannotProcessMediaException;
use App\Version;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class MetadataManager
{
    public function __construct(
        protected EventDispatcher $eventDispatcher,
        protected Client $httpClient,
        protected RemoteAlbumArt $remoteAlbumArt
    ) {
    }

    public function getMetadata(Entity\StationMedia $media, string $filePath): Entity\Metadata
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

        $metadata = $event->getMetadata();
        $media->fromMetadata($metadata);

        $artwork = $metadata->getArtwork();
        if (empty($artwork) && $this->remoteAlbumArt->enableForMedia()) {
            $metadata->setArtwork($this->getExternalArtwork($media));
        }

        return $metadata;
    }

    protected function getExternalArtwork(Entity\StationMedia $media): ?string
    {
        $artUri = ($this->remoteAlbumArt)($media);
        if (empty($artUri)) {
            return null;
        }

        // Fetch external artwork.
        $response = $this->httpClient->request(
            'GET',
            $artUri,
            [
                RequestOptions::TIMEOUT => 10,
                RequestOptions::HEADERS => [
                    'User-Agent' => 'AzuraCast ' . Version::FALLBACK_VERSION,
                ],
            ]
        );

        return (string)$response->getBody();
    }

    public function writeMetadata(Entity\Metadata $metadata, string $filePath): void
    {
        $event = new WriteMetadata($metadata, $filePath);
        $this->eventDispatcher->dispatch($event);
    }
}
