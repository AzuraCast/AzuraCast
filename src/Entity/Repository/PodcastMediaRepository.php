<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\BatchIteratorAggregate;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity\PodcastMedia;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Environment;
use App\Exception\InvalidPodcastMediaFileException;
use App\Exception\PodcastMediaProcessingException;
use App\Media\MetadataService\GetId3MetadataService;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class PodcastMediaRepository extends Repository
{
    public function __construct(
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        protected GetId3MetadataService $metadataService,
        protected ImageManager $imageManager
    ) {
        parent::__construct($em, $serializer, $environment, $logger);
    }

    public function fetchPodcastMediaForStation(Station $station, string $podcastMediaId): ?PodcastMedia
    {
        return $this->fetchPodcastMediaForStorageLocation($station->getPodcastsStorageLocation(), $podcastMediaId);
    }

    public function fetchPodcastMediaForStorageLocation(
        StorageLocation $storageLocation,
        string $podcastMediaId
    ): ?PodcastMedia {
        return $this->repository->findOneBy(
            [
                'id' => $podcastMediaId,
                'storage_location' => $storageLocation,
            ]
        );
    }

    public function buildSimpleBatchIteratorForAllStationPodcastMedia(
        Station $station,
        int $batchSize = 10
    ): BatchIteratorAggregate {
        $podcastMediaQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT pm, e
                FROM App\Entity\PodcastMedia pm
                LEFT JOIN pm.episode e
                WHERE pm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $station->getPodcastsStorageLocation());

        return BatchIteratorAggregate::fromQuery($podcastMediaQuery, $batchSize);
    }

    public function getOrCreate(
        StorageLocation $storageLocation,
        string $path,
        ?string $uploadPath = null
    ): PodcastMedia {
        $podcastMedia = $this->repository->findOneBy(
            [
                'storage_location' => $storageLocation,
                'path' => $path,
            ]
        );

        $created = false;
        if (!($podcastMedia instanceof PodcastMedia)) {
            $podcastMedia = new PodcastMedia($storageLocation);
            $podcastMedia->setPath($path);
            $podcastMedia->setModifiedTime(0);
            $podcastMedia->setOriginalName(pathinfo($path, PATHINFO_FILENAME));

            $created = true;

            // Trigger ID creation.
            $this->em->persist($podcastMedia);
            $this->em->flush();
        }

        $this->processPodcastMedia($podcastMedia, $created, $uploadPath);

        $this->em->persist($podcastMedia);
        $this->em->flush();

        return $podcastMedia;
    }

    public function processPodcastMedia(
        PodcastMedia $podcastMedia,
        bool $force = false,
        ?string $uploadPath = null
    ): bool {
        $podcastMediaPath = $podcastMedia->getPath();

        $fsPodcasts = $podcastMedia->getStorageLocation()->getFilesystem();

        if ($uploadPath !== null) {
            try {
                $this->loadFromFile($podcastMedia, $uploadPath);
            } finally {
                $fsPodcasts->uploadAndDeleteOriginal($uploadPath, $podcastMediaPath);
            }
        }

        if (!$fsPodcasts->fileExists($podcastMediaPath)) {
            throw new PodcastMediaProcessingException(
                sprintf(
                    'Podcast media path "%s" not found.',
                    $podcastMediaPath
                )
            );
        }

        $podcastMediaMetadata = $fsPodcasts->getMetadata($podcastMediaPath);

        $mediaModifiedTime = $podcastMediaMetadata->lastModified() ?? 0;

        if (!$force && $podcastMedia->getModifiedTime() >= $mediaModifiedTime) {
            return false;
        }

        $fsPodcasts->withLocalFile(
            $podcastMediaPath,
            function ($localPodcastMediaPath) use ($podcastMedia): void {
                $this->loadFromFile($podcastMedia, $localPodcastMediaPath);
            }
        );

        $podcastMedia->setModifiedTime($mediaModifiedTime);

        $this->em->persist($podcastMedia);

        return true;
    }

    public function loadFromFile(PodcastMedia $podcastMedia, string $filePath): void
    {
        $metadata = $this->metadataService->readMetadata($filePath);

        $podcastMedia->setLength($metadata->getDuration());

        $podcastMedia->setMimeType($metadata->getMimeType());

        if (!in_array($podcastMedia->getMimeType(), ['audio/x-m4a', 'audio/mpeg'])) {
            throw new InvalidPodcastMediaFileException(
                'Invalid Podcast Media mime type: ' . $podcastMedia->getMimeType()
            );
        }

        $artwork = $metadata->getArtwork();
        if (!empty($artwork)) {
            $this->writePodcastArtwork($podcastMedia, $artwork);
        }
    }

    public function writePodcastArtwork(PodcastMedia $podcastMedia, string $rawArtworkString): void
    {
        $albumArtwork = $this->imageManager->make($rawArtworkString);
        $albumArtwork->fit(
            3000,
            3000,
            function (Constraint $constraint): void {
                $constraint->upsize();
            }
        );

        $albumArtworkPath = PodcastMedia::getArtPath($podcastMedia->getId());
        $albumArtworkStream = $albumArtwork->stream('jpg');

        $fsPodcasts = $podcastMedia->getStorageLocation()->getFilesystem();
        $fsPodcasts->writeStream($albumArtworkPath, $albumArtworkStream->detach());
    }

    public function removePodcastArtwork(PodcastMedia $podcastMedia): void
    {
        $artworkPath = PodcastMedia::getArtPath($podcastMedia->getId());

        $fsPodcasts = $podcastMedia->getStorageLocation()->getFilesystem();
        if ($fsPodcasts->fileExists($artworkPath)) {
            $fsPodcasts->delete($artworkPath);
        }
    }
}
