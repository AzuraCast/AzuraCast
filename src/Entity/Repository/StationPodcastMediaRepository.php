<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\BatchIteratorAggregate;
use App\Doctrine\Repository;
use App\Entity\Station;
use App\Entity\StationPodcastMedia;
use App\Environment;
use App\Exception\InvalidPodcastMediaFileException;
use App\Exception\PodcastMediaProcessingException;
use App\Flysystem\StationFilesystems;
use App\Media\MetadataService\GetId3MetadataService;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class StationPodcastMediaRepository extends Repository
{
    protected GetId3MetadataService $metadataService;

    protected ImageManager $imageManager;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        GetId3MetadataService $metadataService,
        ImageManager $imageManager
    ) {
        parent::__construct($em, $serializer, $environment, $logger);

        $this->metadataService = $metadataService;
        $this->imageManager = $imageManager;
    }

    public function fetchPodcastMediaForStation(Station $station, int $podcastMediaId): ?StationPodcastMedia
    {
        return $this->repository->findOneBy(['id' => $podcastMediaId, 'station' => $station]);
    }

    public function buildSimpleBatchIteratorForAllStationPodcastMedia(
        Station $station,
        int $batchSize = 10
    ): BatchIteratorAggregate {
        $podcastMediaQuery = $this->em->createQuery(/** @lang DQL */
            'SELECT pm, e
                FROM App\Entity\StationPodcastMedia pm
                LEFT JOIN pm.episode e
                WHERE pm.stationId = :stationId'
            )
            ->setParameter('stationId', $station->getId());

        return BatchIteratorAggregate::fromQuery($podcastMediaQuery, $batchSize);
    }

    public function getOrCreate(
        Station $station,
        string $path,
        ?string $uploadPath = null
    ): StationPodcastMedia {
        $podcastMedia = $this->repository->findOneBy([
            'stationId' => $station->getId(),
            'path' => $path,
        ]);

        $created = false;
        if (!($podcastMedia instanceof StationPodcastMedia)) {
            $podcastMedia = new StationPodcastMedia($station);
            $podcastMedia->setPath($path);
            $podcastMedia->setModifiedTime(0);
            $podcastMedia->setOriginalName(pathinfo($path, PATHINFO_FILENAME));

            $created = true;
        }

        $this->processPodcastMedia($podcastMedia, $created, $uploadPath);

        if ($created) {
            $this->em->persist($podcastMedia);
            $this->em->flush();
        }

        return $podcastMedia;
    }

    public function processPodcastMedia(
        StationPodcastMedia $podcastMedia,
        bool $force = false,
        ?string $uploadPath = null
    ): bool {
        $podcastMediaPath = $podcastMedia->getPath();

        $stationFilesystems = new StationFilesystems($podcastMedia->getStation());
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        if ($uploadPath !== null) {
            try {
                $this->loadFromFile($podcastMedia, $uploadPath);
            } finally {
                $podcastsFilesystem->uploadAndDeleteOriginal($uploadPath, $podcastMediaPath);
            }
        }

        if (!$podcastsFilesystem->fileExists($podcastMediaPath)) {
            throw new PodcastMediaProcessingException(
                sprintf(
                    'Podcast media path "%s" not found.',
                    $podcastMediaPath
                )
            );
        }

        $podcastMediaMetadata = $podcastsFilesystem->getMetadata($podcastMediaPath);

        $mediaModifiedTime = $podcastMediaMetadata->lastModified() ?? 0;

        if (!$force && $podcastMedia->getModifiedTime() >= $mediaModifiedTime) {
            return false;
        }

        $podcastsFilesystem->withLocalFile(
            $podcastMediaPath,
            function ($localPodcastMediaPath) use ($podcastMedia): void {
                $this->loadFromFile($podcastMedia, $localPodcastMediaPath);
            }
        );

        $podcastMedia->setModifiedTime($mediaModifiedTime);

        $this->em->persist($podcastMedia);

        return true;
    }

    public function loadFromFile(StationPodcastMedia $podcastMedia, string $filePath): void
    {
        $metadata = $this->metadataService->readMetadata($filePath);

        $podcastMedia->setLength($metadata->getDuration());

        $podcastMedia->setMimeType($metadata->getMimeType());

        if (!in_array($podcastMedia->getMimeType(), ['audio/x-m4a', 'audio/mpeg'])) {
            throw new InvalidPodcastMediaFileException('Invalid Podcast Media mime type: ' . $podcastMedia->getMimeType());
        }

        $artwork = $metadata->getArtwork();
        if (!empty($artwork)) {
            $this->writePodcastArtwork($podcastMedia, $artwork);
        }
    }

    public function writePodcastArtwork(StationPodcastMedia $podcastMedia, string $rawArtworkString): void
    {
        $albumArtwork = $this->imageManager->make($rawArtworkString);
        $albumArtwork->fit(
            3000,
            3000,
            function (Constraint $constraint): void {
                $constraint->upsize();
            }
        );

        $stationFilesystems = new StationFilesystems($podcastMedia->getStation());
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        $albumArtworkPath = StationPodcastMedia::getArtworkPath($podcastMedia->getUniqueId());
        $albumArtworkStream = $albumArtwork->stream('jpg');

        $podcastsFilesystem->writeStream($albumArtworkPath, $albumArtworkStream->detach());
    }

    public function removePodcastArtwork(StationPodcastMedia $podcastMedia): void
    {
        $stationFilesystems = new StationFilesystems($podcastMedia->getStation());
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        $artworkPath = StationPodcastMedia::getArtworkPath($podcastMedia->getUniqueId());

        if ($podcastsFilesystem->fileExists($artworkPath)) {
            $podcastsFilesystem->delete($artworkPath);
        }
    }
}
