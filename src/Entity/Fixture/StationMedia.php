<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity;
use App\Media\MediaProcessor;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;

final class StationMedia extends AbstractFixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly MediaProcessor $mediaProcessor
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $musicSkeletonDir = getenv('INIT_MUSIC_PATH');

        if (empty($musicSkeletonDir) || !is_dir($musicSkeletonDir)) {
            return;
        }

        /** @var Entity\Station $station */
        $station = $this->getReference('station');

        $mediaStorage = $station->getMediaStorageLocation();
        $fs = $mediaStorage->getFilesystem();

        /** @var Entity\StationPlaylist $playlist */
        $playlist = $this->getReference('station_playlist');

        $finder = (new Finder())
            ->files()
            ->in($musicSkeletonDir)
            ->name('/^.+\.(mp3|aac|ogg|flac)$/i');

        foreach ($finder as $file) {
            $filePath = $file->getPathname();
            $fileBaseName = basename($filePath);

            // Copy the file to the station media directory.
            $fs->upload($filePath, '/' . $fileBaseName);

            $mediaRow = $this->mediaProcessor->process($mediaStorage, $fileBaseName);
            if (null === $mediaRow) {
                continue;
            }

            $manager->persist($mediaRow);

            // Add the file to the playlist.
            $spmRow = new Entity\StationPlaylistMedia($playlist, $mediaRow);
            $spmRow->setWeight(1);
            $manager->persist($spmRow);
        }

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            Station::class,
            StationPlaylist::class,
        ];
    }
}
