<?php

namespace App\Media;

use App\Entity;
use App\Event\Radio\GetAlbumArt;
use App\EventDispatcher;
use App\Exception\CannotProcessMediaException;
use App\Media\MetadataService\MetadataServiceInterface;
use App\Version;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class MetadataManager
{
    protected MetadataServiceInterface $metadataService;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected EventDispatcher $eventDispatcher;

    protected Client $httpClient;

    public function __construct(
        MetadataServiceInterface $metadataService,
        Entity\Repository\SettingsRepository $settingsRepo,
        EventDispatcher $eventDispatcher,
        Client $httpClient
    ) {
        $this->metadataService = $metadataService;
        $this->settingsRepo = $settingsRepo;
        $this->eventDispatcher = $eventDispatcher;
        $this->httpClient = $httpClient;
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

        $metadata = $this->metadataService->getMetadata($filePath);
        $media->fromMetadata($metadata);

        $artwork = $metadata->getArtwork();
        if (empty($artwork)) {
            $settings = $this->settingsRepo->readSettings();
            if ($settings->getUseExternalAlbumArtWhenProcessingMedia()) {
                $metadata->setArtwork($this->getExternalArtwork($media));
            }
        }

        return $metadata;
    }

    protected function getExternalArtwork(Entity\StationMedia $media): ?string
    {
        $event = new GetAlbumArt($media);
        $this->eventDispatcher->dispatch($event);

        $artUri = (string)$event->getAlbumArt();
        if (empty($artUri)) {
            return null;
        }

        // Fetch external artwork.
        $response = $this->httpClient->request(
            'GET',
            $artUri,
            [
                RequestOptions::HEADERS => [
                    'User-Agent' => 'AzuraCast ' . Version::FALLBACK_VERSION,
                ],
            ]
        );

        return (string)$response->getBody();
    }

    public function writeMetadata(Entity\Metadata $metadata, string $filePath): bool
    {
        return $this->metadataService->writeMetadata($metadata, $filePath);
    }
}
