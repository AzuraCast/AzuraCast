<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity\Podcast;
use App\Entity\PodcastEpisode;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Environment;
use Azura\Files\ExtendedFilesystemInterface;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
use League\Flysystem\UnableToDeleteFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class PodcastEpisodeRepository extends Repository
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
        $episodeArtwork = $this->imageManager->make($rawArtworkString);
        $episodeArtwork->fit(
            3000,
            3000,
            function (Constraint $constraint): void {
                $constraint->upsize();
            }
        );

        $episodeArtworkPath = PodcastEpisode::getArtPath($episode->getIdRequired());
        $episodeArtworkStream = $episodeArtwork->stream('jpg');

        $fsPodcasts = $episode->getPodcast()->getStorageLocation()->getFilesystem();
        $fsPodcasts->writeStream($episodeArtworkPath, $episodeArtworkStream->detach());

        $episode->setArtUpdatedAt(time());
    }

    public function removeEpisodeArt(
        PodcastEpisode $episode,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $artworkPath = PodcastEpisode::getArtPath($episode->getIdRequired());

        $fs ??= $episode->getPodcast()->getStorageLocation()->getFilesystem();

        try {
            $fs->delete($artworkPath);
        } catch (UnableToDeleteFile) {
        }

        $episode->setArtUpdatedAt(0);
    }

    public function delete(
        PodcastEpisode $episode,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $episode->getPodcast()->getStorageLocation()->getFilesystem();

        $media = $episode->getMedia();
        if (null !== $media) {
            try {
                $fs->delete($media->getPath());
            } catch (UnableToDeleteFile) {
            }

            $this->em->remove($media);
        }

        $this->removeEpisodeArt($episode, $fs);

        $this->em->remove($episode);
        $this->em->flush();
    }
}
