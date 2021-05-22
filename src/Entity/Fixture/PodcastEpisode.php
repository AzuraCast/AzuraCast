<?php

namespace App\Entity\Fixture;

use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;

class PodcastEpisode extends AbstractFixture implements DependentFixtureInterface
{
    protected Entity\Repository\PodcastMediaRepository $mediaRepo;

    public function __construct(Entity\Repository\PodcastMediaRepository $mediaRepo)
    {
        $this->mediaRepo = $mediaRepo;
    }

    public function load(ObjectManager $em): void
    {
        $podcastsSkeletonDir = getenv('INIT_PODCASTS_PATH');

        if (empty($podcastsSkeletonDir) || !is_dir($podcastsSkeletonDir)) {
            return;
        }

        /** @var Entity\Podcast $podcast */
        $podcast = $this->getReference('podcast');

        $storageLocation = $podcast->getStorageLocation();
        $fs = $storageLocation->getFilesystem();

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

            // Create an episode and associate it with the podcast/media.
            $episode = new Entity\PodcastEpisode($podcast);

            $podcastName = $podcastNames[array_rand($podcastNames)];
            $podcastFiller = $podcastFillers[array_rand($podcastFillers)];

            $episode->setTitle('Episode ' . $i . ': ' . sprintf($podcastName, $podcastFiller));
            $episode->setDescription('Another great episode!');
            $episode->setExplicit(false);

            $em->persist($episode);
            $em->flush();

            $this->mediaRepo->upload(
                $episode,
                $fileBaseName,
                $filePath,
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
            Podcast::class,
        ];
    }
}
