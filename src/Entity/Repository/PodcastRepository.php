<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity\Podcast;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Environment;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class PodcastRepository extends Repository
{
    public function __construct(
        ReloadableEntityManagerInterface $entityManager,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        protected ImageManager $imageManager
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

    public function writePodcastArtwork(Podcast $podcast, string $rawArtworkString): void
    {
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

        $fsPodcasts = $podcast->getStorageLocation()->getFilesystem();
        $fsPodcasts->writeStream($podcastArtworkPath, $podcastArtworkStream->detach());
    }

    public function removePodcastArtwork(Podcast $podcast): void
    {
        $artworkPath = Podcast::getArtPath($podcast->getId());

        $fsPodcasts = $podcast->getStorageLocation()->getFilesystem();
        if ($fsPodcasts->fileExists($artworkPath)) {
            $fsPodcasts->delete($artworkPath);
        }

        $podcast->setArtUpdatedAt(0);
        $this->em->persist($podcast);
        $this->em->flush();
    }
}
