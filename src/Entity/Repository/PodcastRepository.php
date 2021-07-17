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
 * @extends Repository<Entity\Podcast>
 */
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
        $fs ??= $podcast->getStorageLocation()->getFilesystem();

        $podcastArtwork = $this->imageManager->make($rawArtworkString);
        $podcastArtwork->fit(
            3000,
            3000,
            function (Constraint $constraint): void {
                $constraint->upsize();
            }
        );

        $podcastArtworkPath = Entity\Podcast::getArtPath($podcast->getIdRequired());
        $podcastArtworkStream = $podcastArtwork->stream('jpg');

        $fs->writeStream($podcastArtworkPath, $podcastArtworkStream->detach());

        $podcast->setArtUpdatedAt(time());
    }

    public function removePodcastArt(
        Entity\Podcast $podcast,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= $podcast->getStorageLocation()->getFilesystem();

        $artworkPath = Entity\Podcast::getArtPath($podcast->getIdRequired());

        try {
            $fs->delete($artworkPath);
        } catch (UnableToDeleteFile) {
        }

        $podcast->setArtUpdatedAt(0);
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
