<?php

declare(strict_types=1);

namespace App\Entity\Fixture;

use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistMedia;
use App\Media\MediaProcessor;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;

final class StationMediaFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly MediaProcessor $mediaProcessor,
        private readonly StorageLocationRepository $storageLocationRepo,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $musicSkeletonDir = getenv('INIT_MUSIC_PATH');

        if (empty($musicSkeletonDir) || !is_dir($musicSkeletonDir)) {
            return;
        }

        $station = $this->getReference('station', Station::class);

        $mediaStorage = $station->media_storage_location;
        $fs = $this->storageLocationRepo->getAdapter($mediaStorage)->getFilesystem();

        $playlist = $this->getReference('station_playlist', StationPlaylist::class);

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
            $spmRow = new StationPlaylistMedia($playlist, $mediaRow);
            $spmRow->weight = 1;
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
            StationFixture::class,
            StationPlaylistFixture::class,
        ];
    }
}
