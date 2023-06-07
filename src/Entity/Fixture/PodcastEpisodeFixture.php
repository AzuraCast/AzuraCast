<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Podcast;
use App\Entity\PodcastEpisode;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Entity\Repository\StorageLocationRepository;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;

final class PodcastEpisodeFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function __construct(
        protected PodcastEpisodeRepository $episodeRepo,
        protected StorageLocationRepository $storageLocationRepo
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $podcastsSkeletonDir = getenv('INIT_PODCASTS_PATH');

        if (empty($podcastsSkeletonDir) || !is_dir($podcastsSkeletonDir)) {
            return;
        }

        /** @var Podcast $podcast */
        $podcast = $this->getReference('podcast');

        $fs = $this->storageLocationRepo->getAdapter($podcast->getStorageLocation())
            ->getFilesystem();

        $finder = (new Finder())
            ->files()
            ->in($podcastsSkeletonDir)
            ->name('/^.+\.(mp3|aac|ogg|flac)$/i');

        $i = 1;

        $podcastNames = [
            'Attack of the %s',
            'Introducing: %s!',
            'Rants About %s',
            'The %s Where Everyone Yells',
            '%s? It\'s AzuraCastastic!',
        ];

        $podcastFillers = [
            'Content',
            'Unicorn Login Screen',
            'Default Error Message',
        ];

        foreach ($finder as $file) {
            $filePath = $file->getPathname();
            $fileBaseName = basename($filePath);

            $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileBaseName;
            copy($filePath, $tempPath);

            // Create an episode and associate it with the podcast/media.
            $episode = new PodcastEpisode($podcast);

            /** @noinspection NonSecureArrayRandUsageInspection */
            $podcastName = $podcastNames[array_rand($podcastNames)];

            /** @noinspection NonSecureArrayRandUsageInspection */
            $podcastFiller = $podcastFillers[array_rand($podcastFillers)];

            $episode->setTitle('Episode ' . $i . ': ' . sprintf($podcastName, $podcastFiller));
            $episode->setDescription('Another great episode!');
            $episode->setExplicit(false);

            $manager->persist($episode);
            $manager->flush();

            $this->episodeRepo->uploadMedia(
                $episode,
                $fileBaseName,
                $tempPath,
                $fs
            );

            $i++;
        }
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            PodcastFixture::class,
        ];
    }
}
