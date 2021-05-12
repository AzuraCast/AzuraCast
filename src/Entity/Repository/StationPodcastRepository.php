<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Station;
use App\Entity\StationPodcast;
use App\Entity\StationPodcastEpisode;
use App\Environment;
use App\Flysystem\StationFilesystems;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class StationPodcastRepository extends Repository
{
    protected ImageManager $imageManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        ImageManager $imageManager
    ) {
        parent::__construct($entityManager, $serializer, $environment, $logger);

        $this->imageManager = $imageManager;
    }

    public function fetchPodcastForStation(Station $station, int $podcastId): ?StationPodcast
    {
        return $this->repository->findOneBy(['id' => $podcastId, 'station' => $station]);
    }

    /**
     * @return StationPodcast[]
     */
    public function fetchPublishedPodcastsForStation(Station $station): array
    {
        /** @var StationPodcast[] $podcasts */
        $podcasts = $this->em->createQueryBuilder()
            ->select('p')
            ->from(StationPodcast::class, 'p')
            ->where('p.station = :station')
            ->setParameter('station', $station)
            ->getQuery()
            ->getResult();

        $publishedPodcasts = [];

        foreach ($podcasts as $podcast) {
            /** @var StationPodcastEpisode $episode */
            foreach ($podcast->getEpisodes() as $episode) {
                if ($episode->isPublished()) {
                    $publishedPodcasts[] = $podcast;
                    break;
                }
            }
        }

        return $publishedPodcasts;
    }

    public function writePodcastArtwork(StationPodcast $podcast, string $rawArtworkString): void
    {
        $podcastArtwork = $this->imageManager->make($rawArtworkString);
        $podcastArtwork->fit(
            3000,
            3000,
            function (Constraint $constraint): void {
                $constraint->upsize();
            }
        );

        $fsStation = new StationFilesystems($podcast->getStation());
        $fsPodcasts = $fsStation->getPodcastsFilesystem();

        $podcastArtworkPath = StationPodcast::getArtworkPath($podcast->getUniqueId());
        $podcastArtworkStream = $podcastArtwork->stream('jpg');

        $fsPodcasts->writeStream($podcastArtworkPath, $podcastArtworkStream->detach());
    }

    public function removePodcastArtwork(StationPodcast $podcast): void
    {
        $fsStation = new StationFilesystems($podcast->getStation());
        $fsPodcasts = $fsStation->getPodcastsFilesystem();

        $artworkPath = StationPodcast::getArtworkPath($podcast->getUniqueId());

        if ($fsPodcasts->fileExists($artworkPath)) {
            $fsPodcasts->delete($artworkPath);
        }
    }
}
