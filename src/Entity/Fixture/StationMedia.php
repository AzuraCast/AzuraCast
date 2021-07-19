<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;

class StationMedia extends AbstractFixture implements DependentFixtureInterface
{
    protected Entity\Repository\StationMediaRepository $mediaRepo;

    public function __construct(Entity\Repository\StationMediaRepository $mediaRepo)
    {
        $this->mediaRepo = $mediaRepo;
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

            $mediaRow = $this->mediaRepo->getOrCreate($mediaStorage, $fileBaseName);
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
