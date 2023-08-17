<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Podcast;
use App\Entity\PodcastEpisode;
use App\Entity\PodcastMedia;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Exception\InvalidPodcastMediaFileException;
use App\Exception\StorageLocationFullException;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Media\AlbumArt;
use App\Media\MetadataManager;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToRetrieveMetadata;

/**
 * @extends Repository<PodcastEpisode>
 */
final class PodcastEpisodeRepository extends Repository
{
    protected string $entityClass = PodcastEpisode::class;

    public function __construct(
        private readonly MetadataManager $metadataManager,
        private readonly StorageLocationRepository $storageLocationRepo,
    ) {
    }

    public function fetchEpisodeForStation(Station $station, string $episodeId): ?PodcastEpisode
    {
        return $this->fetchEpisodeForStorageLocation(
            $station->getPodcastsStorageLocation(),
            $episodeId
        );
    }

    public function fetchEpisodeForStorageLocation(
        StorageLocation $storageLocation,
        string $episodeId
    ): ?PodcastEpisode {
        return $this->em->createQuery(
            <<<'DQL'
                SELECT pe
                FROM App\Entity\PodcastEpisode pe
                JOIN pe.podcast p
                WHERE pe.id = :id
                AND p.storage_location = :storageLocation
            DQL
        )->setParameter('id', $episodeId)
            ->setParameter('storageLocation', $storageLocation)
            ->getOneOrNullResult();
    }

    /**
     * @return PodcastEpisode[]
     */
    public function fetchPublishedEpisodesForPodcast(Podcast $podcast): array
    {
        $episodes = $this->em->createQueryBuilder()
            ->select('pe')
            ->from(PodcastEpisode::class, 'pe')
            ->where('pe.podcast = :podcast')
            ->setParameter('podcast', $podcast)
            ->orderBy('pe.created_at', 'DESC')
            ->getQuery()
            ->getResult();

        return array_filter(
            $episodes,
            static function (PodcastEpisode $episode) {
                return $episode->isPublished();
            }
        );
    }

    public function writeEpisodeArt(
        PodcastEpisode $episode,
        string $rawArtworkString
    ): void {
        $episodeArtworkString = AlbumArt::resize($rawArtworkString);

        $storageLocation = $episode->getPodcast()->getStorageLocation();
        $fs = $this->storageLocationRepo->getAdapter($storageLocation)
            ->getFilesystem();

        $episodeArtworkSize = strlen($episodeArtworkString);
        if (!$storageLocation->canHoldFile($episodeArtworkSize)) {
            throw new StorageLocationFullException();
        }

        $episodeArtworkPath = PodcastEpisode::getArtPath($episode->getIdRequired());
        $fs->write($episodeArtworkPath, $episodeArtworkString);

        $storageLocation->addStorageUsed($episodeArtworkSize);
        $this->em->persist($storageLocation);

        $episode->setArtUpdatedAt(time());
        $this->em->persist($episode);
    }

    public function removeEpisodeArt(
        PodcastEpisode $episode,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $artworkPath = PodcastEpisode::getArtPath($episode->getIdRequired());

        $storageLocation = $episode->getPodcast()->getStorageLocation();
        $fs ??= $this->storageLocationRepo->getAdapter($storageLocation)
            ->getFilesystem();

        try {
            $size = $fs->fileSize($artworkPath);
        } catch (UnableToRetrieveMetadata) {
            $size = 0;
        }

        try {
            $fs->delete($artworkPath);
        } catch (UnableToDeleteFile) {
        }

        $storageLocation->removeStorageUsed($size);
        $this->em->persist($storageLocation);

        $episode->setArtUpdatedAt(0);
        $this->em->persist($episode);
    }

    public function uploadMedia(
        PodcastEpisode $episode,
        string $originalPath,
        string $uploadPath,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $podcast = $episode->getPodcast();
        $storageLocation = $podcast->getStorageLocation();

        $fs ??= $this->storageLocationRepo->getAdapter($storageLocation)
            ->getFilesystem();

        $size = filesize($uploadPath) ?: 0;
        if (!$storageLocation->canHoldFile($size)) {
            throw new StorageLocationFullException();
        }

        // Do an early metadata check of the new media to avoid replacing a valid file with an invalid one.
        $metadata = $this->metadataManager->read($uploadPath);

        if (!in_array($metadata->getMimeType(), ['audio/x-m4a', 'audio/mpeg'])) {
            throw new InvalidPodcastMediaFileException(
                'Invalid Podcast Media mime type: ' . $metadata->getMimeType()
            );
        }

        $existingMedia = $episode->getMedia();
        if ($existingMedia instanceof PodcastMedia) {
            $this->deleteMedia($existingMedia, $fs);
            $episode->setMedia(null);
        }

        $ext = pathinfo($originalPath, PATHINFO_EXTENSION);
        $path = $podcast->getId() . '/' . $episode->getId() . '.' . $ext;

        $podcastMedia = new PodcastMedia($storageLocation);
        $podcastMedia->setPath($path);
        $podcastMedia->setOriginalName(basename($originalPath));

        // Load metadata from local file while it's available.
        $podcastMedia->setLength($metadata->getDuration());
        $podcastMedia->setMimeType($metadata->getMimeType());

        // Upload local file remotely.
        $fs->uploadAndDeleteOriginal($uploadPath, $path);

        $podcastMedia->setEpisode($episode);
        $this->em->persist($podcastMedia);

        $storageLocation->addStorageUsed($size);
        $this->em->persist($storageLocation);

        $episode->setMedia($podcastMedia);

        $artwork = $metadata->getArtwork();
        if (!empty($artwork) && 0 === $episode->getArtUpdatedAt()) {
            $this->writeEpisodeArt(
                $episode,
                $artwork
            );
        }

        $this->em->persist($episode);
        $this->em->flush();
    }

    public function deleteMedia(
        PodcastMedia $media,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $storageLocation = $media->getStorageLocation();
        $fs ??= $this->storageLocationRepo->getAdapter($storageLocation)
            ->getFilesystem();

        $mediaPath = $media->getPath();

        try {
            $size = $fs->fileSize($mediaPath);
        } catch (UnableToRetrieveMetadata) {
            $size = 0;
        }

        try {
            $fs->delete($mediaPath);
        } catch (UnableToDeleteFile) {
        }

        $storageLocation->removeStorageUsed($size);
        $this->em->persist($storageLocation);

        $this->em->remove($media);
        $this->em->flush();
    }

    public function delete(
        PodcastEpisode $episode,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $this->storageLocationRepo->getAdapter($episode->getPodcast()->getStorageLocation())
            ->getFilesystem();

        $media = $episode->getMedia();
        if (null !== $media) {
            $this->deleteMedia($media, $fs);
        }

        $this->removeEpisodeArt($episode, $fs);

        $this->em->remove($episode);
        $this->em->flush();
    }
}
