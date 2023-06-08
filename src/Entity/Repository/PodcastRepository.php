<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Podcast;
use App\Entity\Station;
use App\Entity\StorageLocation;
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
        return $this->fetchPodcastForStorageLocation($station->getPodcastsStorageLocation(), $podcastId);
    }

    public function fetchPodcastForStorageLocation(
        StorageLocation $storageLocation,
        string $podcastId
    ): ?Podcast {
        return $this->repository->findOneBy(
            [
                'id' => $podcastId,
                'storage_location' => $storageLocation,
            ]
        );
    }

    /**
     * @return Podcast[]
     */
    public function fetchPublishedPodcastsForStation(Station $station): array
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
            static function (Podcast $podcast) {
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
        Podcast $podcast,
        string $rawArtworkString,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $storageLocation = $podcast->getStorageLocation();
        $fs ??= $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        $podcastArtworkString = AlbumArt::resize($rawArtworkString);

        $podcastArtworkSize = strlen($podcastArtworkString);
        if (!$storageLocation->canHoldFile($podcastArtworkSize)) {
            throw new StorageLocationFullException();
        }

        $podcastArtworkPath = Podcast::getArtPath($podcast->getIdRequired());
        $fs->write($podcastArtworkPath, $podcastArtworkString);

        $storageLocation->addStorageUsed($podcastArtworkSize);
        $this->em->persist($storageLocation);

        $podcast->setArtUpdatedAt(time());
        $this->em->persist($podcast);
    }

    public function removePodcastArt(
        Podcast $podcast,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $storageLocation = $podcast->getStorageLocation();
        $fs ??= $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        $artworkPath = Podcast::getArtPath($podcast->getIdRequired());

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
        Podcast $podcast,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $this->storageLocationRepo->getAdapter($podcast->getStorageLocation())
            ->getFilesystem();

        foreach ($podcast->getEpisodes() as $episode) {
            $this->podcastEpisodeRepo->delete($episode, $fs);
        }

        $this->removePodcastArt($podcast, $fs);

        $this->em->remove($podcast);
        $this->em->flush();
    }
}
