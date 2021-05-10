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

class StationPodcastEpisodeRepository extends Repository
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

    public function fetchEpisodeForStation(Station $station, int $episodeId): ?StationPodcastEpisode
    {
        return $this->repository->findOneBy(['id' => $episodeId, 'station' => $station]);
    }

    /**
     * @return StationPodcastEpisode[]
     */
    public function fetchPublishedEpisodesForPodcast(StationPodcast $podcast): array {
        /** @var StationPodcastEpisode[] $episode */
        $episodes = $this->em->createQueryBuilder()
        ->select('pe')
        ->from(StationPodcastEpisode::class, 'pe')
        ->where('pe.podcast = :podcast')
        ->setParameter('podcast', $podcast)
        ->getQuery()
        ->getResult();

        $publishedEpisodes = [];

        /** @var StationPodcastEpisode $episode */
        foreach ($episodes as $episode) {
            if ($episode->isPublished()) {
                $publishedEpisodes[] = $episode;
            }
        }

        return $publishedEpisodes;
    }

    public function writeEpisodeArtwork(StationPodcastEpisode $episode, string $rawArtworkString): void
    {
        $episodeArtwork = $this->imageManager->make($rawArtworkString);
        $episodeArtwork->fit(
            3000,
            3000,
            function (Constraint $constraint): void {
                $constraint->upsize();
            }
        );

        $stationFilesystems = new StationFilesystems($episode->getStation());
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        $episodeArtworkPath = StationPodcastEpisode::getArtworkPath($episode->getUniqueId());
        $episodeArtworkStream = $episodeArtwork->stream('jpg');

        $podcastsFilesystem->writeStream($episodeArtworkPath, $episodeArtworkStream->detach());
    }

    public function removeEpisodeArt(StationPodcastEpisode $episode): void
    {
        $stationFilesystems = new StationFilesystems($episode->getStation());
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        $artworkPath = StationPodcastEpisode::getArtworkPath($episode->getUniqueId());

        if ($podcastsFilesystem->fileExists($artworkPath)) {
            $podcastsFilesystem->delete($artworkPath);
        }
    }
}
