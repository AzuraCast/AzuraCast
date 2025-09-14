<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Enums\PodcastSources;
use App\Entity\Podcast;
use App\Entity\PodcastEpisode;
use App\Entity\PodcastMedia;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Exception\StorageLocationFullException;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Media\AlbumArt;
use App\Media\MetadataManager;
use InvalidArgumentException;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToRetrieveMetadata;
use LogicException;

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

    public function fetchEpisodeForPodcast(Podcast $podcast, string $episodeId): ?PodcastEpisode
    {
        return $this->repository->findOneBy([
            'id' => $episodeId,
            'podcast' => $podcast,
        ]);
    }

    public function fetchEpisodeForStation(Station $station, string $episodeId): ?PodcastEpisode
    {
        return $this->fetchEpisodeForStorageLocation(
            $station->podcasts_storage_location,
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

    public function writeEpisodeArt(
        PodcastEpisode $episode,
        string $rawArtworkString
    ): void {
        $episodeArtworkString = AlbumArt::resize($rawArtworkString);

        $storageLocation = $episode->podcast->storage_location;
        $fs = $this->storageLocationRepo->getAdapter($storageLocation)
            ->getFilesystem();

        $episodeArtworkSize = strlen($episodeArtworkString);
        if (!$storageLocation->canHoldFile($episodeArtworkSize)) {
            throw new StorageLocationFullException();
        }

        $episodeArtworkPath = PodcastEpisode::getArtPath($episode->id);
        $fs->write($episodeArtworkPath, $episodeArtworkString);

        $storageLocation->addStorageUsed($episodeArtworkSize);
        $this->em->persist($storageLocation);

        $episode->art_updated_at = time();
        $this->em->persist($episode);
    }

    public function removeEpisodeArt(
        PodcastEpisode $episode,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $artworkPath = PodcastEpisode::getArtPath($episode->id);

        $storageLocation = $episode->podcast->storage_location;
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

        $episode->art_updated_at = 0;
        $this->em->persist($episode);
    }

    public function uploadMedia(
        PodcastEpisode $episode,
        string $originalPath,
        string $uploadPath,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $podcast = $episode->podcast;

        if ($podcast->source !== PodcastSources::Manual) {
            throw new LogicException('Cannot upload media to this podcast type.');
        }

        $storageLocation = $podcast->storage_location;

        $fs ??= $this->storageLocationRepo->getAdapter($storageLocation)
            ->getFilesystem();

        $size = filesize($uploadPath) ?: 0;
        if (!$storageLocation->canHoldFile($size)) {
            throw new StorageLocationFullException();
        }

        // Do an early metadata check of the new media to avoid replacing a valid file with an invalid one.
        $metadata = $this->metadataManager->read($uploadPath);

        if (!in_array($metadata->getMimeType(), ['audio/x-m4a', 'audio/mpeg'])) {
            throw new InvalidArgumentException(
                sprintf('Invalid Podcast Media mime type: %s', $metadata->getMimeType())
            );
        }

        $existingMedia = $episode->media;
        if ($existingMedia instanceof PodcastMedia) {
            $this->deleteMedia($existingMedia, $fs);
            $episode->media = null;
        }

        $ext = pathinfo($originalPath, PATHINFO_EXTENSION);
        $path = $podcast->id . '/' . $episode->id . '.' . $ext;

        $podcastMedia = new PodcastMedia($storageLocation);
        $podcastMedia->path = $path;
        $podcastMedia->original_name = basename($originalPath);

        // Load metadata from local file while it's available.
        $podcastMedia->length = $metadata->getDuration();
        $podcastMedia->mime_type = $metadata->getMimeType();

        // Upload local file remotely.
        $fs->uploadAndDeleteOriginal($uploadPath, $path);

        $podcastMedia->episode = $episode;
        $this->em->persist($podcastMedia);

        $storageLocation->addStorageUsed($size);
        $this->em->persist($storageLocation);

        $episode->media = $podcastMedia;

        $artwork = $metadata->getArtwork();
        if (!empty($artwork) && 0 === $episode->art_updated_at) {
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
        $storageLocation = $media->storage_location;
        $fs ??= $this->storageLocationRepo->getAdapter($storageLocation)
            ->getFilesystem();

        $mediaPath = $media->path;

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
        $fs ??= $this->storageLocationRepo->getAdapter($episode->podcast->storage_location)
            ->getFilesystem();

        $media = $episode->media;
        if (null !== $media) {
            $this->deleteMedia($media, $fs);
        }

        $this->removeEpisodeArt($episode, $fs);

        $this->em->remove($episode);
        $this->em->flush();
    }
}
