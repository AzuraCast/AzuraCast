<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Enums\PodcastSources;
use App\Entity\Podcast;
use App\Entity\Station;
use App\Exception\StorageLocationFullException;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Media\AlbumArt;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToRetrieveMetadata;

/**
 * @extends Repository<Podcast>
 */
final class PodcastRepository extends Repository
{
    protected string $entityClass = Podcast::class;

    public function __construct(
        private readonly PodcastEpisodeRepository $podcastEpisodeRepo,
        private readonly StorageLocationRepository $storageLocationRepo
    ) {
    }

    public function fetchPodcastForStation(Station $station, string $podcastId): ?Podcast
    {
        return $this->repository->findOneBy(
            [
                'id' => $podcastId,
                'storage_location' => $station->podcasts_storage_location,
            ]
        );
    }

    /**
     * @param Station $station
     * @return string[]
     */
    public function getPodcastIdsWithPublishedEpisodes(Station $station): array
    {
        return $this->em->createQuery(
            <<<'DQL'
            SELECT DISTINCT p.id
            FROM App\Entity\PodcastEpisode pe
            JOIN pe.podcast p
            LEFT JOIN pe.media pm
            LEFT JOIN pe.playlist_media sm
            WHERE 
                ((p.source = :sourceManual AND pm.id IS NOT NULL) OR (p.source = :sourcePlaylist AND sm.id IS NOT NULL))
                AND (pe.publish_at <= :time)
            DQL
        )->setParameter('time', time())
            ->setParameter('sourceManual', PodcastSources::Manual->value)
            ->setParameter('sourcePlaylist', PodcastSources::Playlist->value)
            ->enableResultCache(60, 'podcast_ids_' . $station->id)
            ->getSingleColumnResult();
    }

    public function writePodcastArt(
        Podcast $podcast,
        string $rawArtworkString,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $storageLocation = $podcast->storage_location;
        $fs ??= $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        $podcastArtworkString = AlbumArt::resize($rawArtworkString);

        $podcastArtworkSize = strlen($podcastArtworkString);
        if (!$storageLocation->canHoldFile($podcastArtworkSize)) {
            throw new StorageLocationFullException();
        }

        $podcastArtworkPath = Podcast::getArtPath($podcast->id);
        $fs->write($podcastArtworkPath, $podcastArtworkString);

        $storageLocation->addStorageUsed($podcastArtworkSize);
        $this->em->persist($storageLocation);

        $podcast->art_updated_at = time();
        $this->em->persist($podcast);
    }

    public function removePodcastArt(
        Podcast $podcast,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $storageLocation = $podcast->storage_location;
        $fs ??= $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        $artworkPath = Podcast::getArtPath($podcast->id);

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

        $podcast->art_updated_at = 0;
        $this->em->persist($podcast);
    }

    public function delete(
        Podcast $podcast,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $this->storageLocationRepo->getAdapter($podcast->storage_location)
            ->getFilesystem();

        foreach ($podcast->episodes as $episode) {
            $this->podcastEpisodeRepo->delete($episode, $fs);
        }

        $this->removePodcastArt($podcast, $fs);

        $this->em->remove($podcast);
        $this->em->flush();
    }
}
