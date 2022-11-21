<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Exception\StorageLocationFullException;
use App\Media\AlbumArt;
use App\Flysystem\ExtendedFilesystemInterface;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToRetrieveMetadata;

/**
 * @extends Repository<Entity\Podcast>
 */
final class PodcastRepository extends Repository
{
    public function __construct(
        ReloadableEntityManagerInterface $entityManager,
        private readonly PodcastEpisodeRepository $podcastEpisodeRepo,
    ) {
        parent::__construct($entityManager);
    }

    public function fetchPodcastForStation(Entity\Station $station, string $podcastId): ?Entity\Podcast
    {
        return $this->fetchPodcastForStorageLocation($station->getPodcastsStorageLocation(), $podcastId);
    }

    public function fetchPodcastForStorageLocation(
        Entity\StorageLocation $storageLocation,
        string $podcastId
    ): ?Entity\Podcast {
        return $this->repository->findOneBy(
            [
                'id' => $podcastId,
                'storage_location' => $storageLocation,
            ]
        );
    }

    /**
     * @return Entity\Podcast[]
     */
    public function fetchPublishedPodcastsForStation(Entity\Station $station): array
    {
        $podcasts = $this->em->createQuery(
            <<<'DQL'
                SELECT p, pe
                FROM App\Entity\Podcast p
                LEFT JOIN p.episodes pe
                WHERE p.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $station->getPodcastsStorageLocation())
            ->getResult();

        return array_filter(
            $podcasts,
            static function (Entity\Podcast $podcast) {
                foreach ($podcast->getEpisodes() as $episode) {
                    if ($episode->isPublished()) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    public function writePodcastArt(
        Entity\Podcast $podcast,
        string $rawArtworkString,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $storageLocation = $podcast->getStorageLocation();
        $fs ??= $storageLocation->getFilesystem();

        $podcastArtworkString = AlbumArt::resize($rawArtworkString);

        $podcastArtworkSize = strlen($podcastArtworkString);
        if (!$storageLocation->canHoldFile($podcastArtworkSize)) {
            throw new StorageLocationFullException();
        }

        $podcastArtworkPath = Entity\Podcast::getArtPath($podcast->getIdRequired());
        $fs->write($podcastArtworkPath, $podcastArtworkString);

        $storageLocation->addStorageUsed($podcastArtworkSize);
        $this->em->persist($storageLocation);

        $podcast->setArtUpdatedAt(time());
        $this->em->persist($podcast);
    }

    public function removePodcastArt(
        Entity\Podcast $podcast,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $storageLocation = $podcast->getStorageLocation();
        $fs ??= $storageLocation->getFilesystem();

        $artworkPath = Entity\Podcast::getArtPath($podcast->getIdRequired());

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

        $podcast->setArtUpdatedAt(0);
        $this->em->persist($podcast);
    }

    public function delete(
        Entity\Podcast $podcast,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $podcast->getStorageLocation()->getFilesystem();

        foreach ($podcast->getEpisodes() as $episode) {
            $this->podcastEpisodeRepo->delete($episode, $fs);
        }

        $this->removePodcastArt($podcast, $fs);

        $this->em->remove($podcast);
        $this->em->flush();
    }
}
