<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Environment;
use Azura\Files\ExtendedFilesystemInterface;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
use League\Flysystem\UnableToDeleteFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @extends Repository<Entity\PodcastEpisode>
 */
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

    public function fetchEpisodeForStation(Entity\Station $station, string $episodeId): ?Entity\PodcastEpisode
    {
        return $this->fetchEpisodeForStorageLocation(
            $station->getPodcastsStorageLocation(),
            $episodeId
        );
    }

    public function fetchEpisodeForStorageLocation(
        Entity\StorageLocation $storageLocation,
        string $episodeId
    ): ?Entity\PodcastEpisode {
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
     * @return Entity\PodcastEpisode[]
     */
    public function fetchPublishedEpisodesForPodcast(Entity\Podcast $podcast): array
    {
        $episodes = $this->em->createQueryBuilder()
            ->select('pe')
            ->from(Entity\PodcastEpisode::class, 'pe')
            ->where('pe.podcast = :podcast')
            ->setParameter('podcast', $podcast)
            ->getQuery()
            ->getResult();

        return array_filter(
            $episodes,
            static function (Entity\PodcastEpisode $episode) {
                return $episode->isPublished();
            }
        );
    }

    public function writeEpisodeArt(
        Entity\PodcastEpisode $episode,
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

        $episodeArtworkPath = Entity\PodcastEpisode::getArtPath($episode->getIdRequired());
        $episodeArtworkStream = $episodeArtwork->stream('jpg');

        $fsPodcasts = $episode->getPodcast()->getStorageLocation()->getFilesystem();
        $fsPodcasts->writeStream($episodeArtworkPath, $episodeArtworkStream->detach());

        $episode->setArtUpdatedAt(time());
    }

    public function removeEpisodeArt(
        Entity\PodcastEpisode $episode,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $artworkPath = Entity\PodcastEpisode::getArtPath($episode->getIdRequired());

        $fs ??= $episode->getPodcast()->getStorageLocation()->getFilesystem();

        try {
            $fs->delete($artworkPath);
        } catch (UnableToDeleteFile) {
        }

        $episode->setArtUpdatedAt(0);
    }

    public function delete(
        Entity\PodcastEpisode $episode,
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
