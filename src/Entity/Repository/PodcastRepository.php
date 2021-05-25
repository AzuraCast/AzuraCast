<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity\Podcast;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Environment;
use Azura\Files\ExtendedFilesystemInterface;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
use League\Flysystem\UnableToDeleteFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class PodcastRepository extends Repository
{
    public function __construct(
        ReloadableEntityManagerInterface $entityManager,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        protected ImageManager $imageManager,
        protected PodcastEpisodeRepository $podcastEpisodeRepo,
    ) {
        parent::__construct($entityManager, $serializer, $environment, $logger);
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
        $fs ??= $podcast->getStorageLocation()->getFilesystem();

        $podcastArtwork = $this->imageManager->make($rawArtworkString);
        $podcastArtwork->fit(
            3000,
            3000,
            function (Constraint $constraint): void {
                $constraint->upsize();
            }
        );

        $podcastArtworkPath = Podcast::getArtPath($podcast->getId());
        $podcastArtworkStream = $podcastArtwork->stream('jpg');

        $fs->writeStream($podcastArtworkPath, $podcastArtworkStream->detach());

        $podcast->setArtUpdatedAt(time());
    }

    public function removePodcastArt(
        Podcast $podcast,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $podcast->getStorageLocation()->getFilesystem();

        $artworkPath = Podcast::getArtPath($podcast->getId());

        try {
            $fs->delete($artworkPath);
        } catch (UnableToDeleteFile) {
        }

        $podcast->setArtUpdatedAt(0);
    }

    public function delete(
        Podcast $podcast,
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
