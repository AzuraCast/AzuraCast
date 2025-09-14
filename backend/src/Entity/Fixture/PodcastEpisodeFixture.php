<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Podcast;
use App\Entity\PodcastEpisode;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Environment;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;

final class PodcastEpisodeFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Environment $environment,
        private readonly PodcastEpisodeRepository $episodeRepo,
        private readonly StorageLocationRepository $storageLocationRepo
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $podcast = $this->getReference('podcast', Podcast::class);

        $fs = $this->storageLocationRepo->getAdapter($podcast->storage_location)
            ->getFilesystem();

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

        foreach ($this->getEpisodePaths() as $filePath) {
            $fileBaseName = basename($filePath);

            $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileBaseName;
            copy($filePath, $tempPath);

            // Create an episode and associate it with the podcast/media.
            $episode = new PodcastEpisode($podcast);

            /** @noinspection NonSecureArrayRandUsageInspection */
            $podcastName = $podcastNames[array_rand($podcastNames)];

            /** @noinspection NonSecureArrayRandUsageInspection */
            $podcastFiller = $podcastFillers[array_rand($podcastFillers)];

            $episode->title = 'Episode ' . $i . ': ' . sprintf($podcastName, $podcastFiller);
            $episode->description = 'Another great episode!';
            $episode->explicit = false;

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

    private function getEpisodePaths(): array
    {
        $podcastsSkeletonDir = getenv('INIT_PODCASTS_PATH');

        if (!empty($podcastsSkeletonDir) && is_dir($podcastsSkeletonDir)) {
            $finder = new Finder()
                ->files()
                ->in($podcastsSkeletonDir)
                ->name('/^.+\.(mp3|aac|ogg|flac)$/i');

            $paths = [];
            foreach ($finder as $file) {
                $paths[] = $file->getPathname();
            }

            if (!empty($paths)) {
                return $paths;
            }
        }

        return [
            $this->environment->getBaseDirectory() . '/resources/error.mp3',
        ];
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
