<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistMedia;
use App\Entity\StorageLocationAdapter\StorageLocationAdapterInterface;
use App\Flysystem\Adapter\ExtendedAdapterInterface;
use App\Flysystem\RemoteFilesystem;
use App\Sync\Task\CheckMediaTask;
use DI\FactoryInterface;
use FunctionalTester;
use Generator;
use Mockery;
use RuntimeException;

final class CheckMediaTaskCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function syncFileSizeOfUnchangedMedia(FunctionalTester $I): void
    {
        $I->wantTo('Sync the stored file size of unchanged media without reprocessing it.');

        $mediaId = $this->uploadTestSong()->id;

        // Simulate media that was processed before file sizes were tracked.
        $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\StationMedia sm
                SET sm.size = 0
                WHERE sm.id = :id
            DQL
        )->setParameter('id', $mediaId)
            ->execute();

        $this->di->get(CheckMediaTask::class)->importMusic(
            $this->getTestStation()->media_storage_location
        );

        $this->em->clear();
        $media = $this->em->find(StationMedia::class, $mediaId);

        $I->assertSame((int) filesize(self::TEST_SONG_PATH), $media?->size);
    }

    /**
     * @before setupComplete
     */
    public function keepMediaWhenStorageListsNoFiles(FunctionalTester $I): void
    {
        $I->wantTo('Keep media and playlist links while the media storage shows no files.');

        $mediaId = $this->uploadTestSongToPlaylist()->id;

        $checkMediaTask = $this->di->get(CheckMediaTask::class);
        $checkMediaTask->importMusic($this->getTestStation()->media_storage_location);

        $storageUsed = $this->getTestStation()->media_storage_location->storageUsedBytes;
        $I->assertTrue($storageUsed->isPositive());

        $this->withMediaStorageUnmounted(
            fn() => $checkMediaTask->importMusic($this->getTestStation()->media_storage_location)
        );

        $this->em->clear();

        $I->assertNotNull($this->em->find(StationMedia::class, $mediaId));
        $I->assertSame(
            1,
            $this->em->getRepository(StationPlaylistMedia::class)->count(['media' => $mediaId])
        );
        $I->assertSame(
            (string) $storageUsed,
            (string) $this->getTestStation()->media_storage_location->storageUsedBytes
        );
    }

    /**
     * @before setupComplete
     */
    public function removeMediaOfEmptyStorageWhenAllowed(FunctionalTester $I): void
    {
        $I->wantTo('Remove media and playlist links of an empty media storage when an empty storage is allowed.');

        $mediaId = $this->uploadTestSongToPlaylist()->id;

        $this->withMediaStorageUnmounted(
            fn() => $this->di->get(CheckMediaTask::class)->importMusic(
                $this->getTestStation()->media_storage_location,
                allowEmptyStorage: true
            )
        );

        $this->em->clear();

        $I->assertNull($this->em->find(StationMedia::class, $mediaId));
        $I->assertSame(
            0,
            $this->em->getRepository(StationPlaylistMedia::class)->count(['media' => $mediaId])
        );
    }

    /**
     * @before setupComplete
     */
    public function resetStorageUsedOfEmptyStorage(FunctionalTester $I): void
    {
        $I->wantTo('Reset the storage usage of an empty media storage without media.');

        $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\StorageLocation sl
                SET sl.storageUsedRaw = 1024
                WHERE sl.id = :id
            DQL
        )->setParameter('id', $this->getTestStation()->media_storage_location->id)
            ->execute();

        $this->em->clear();

        $I->assertSame('1024', (string) $this->getTestStation()->media_storage_location->storageUsedBytes);

        $this->withMediaStorageUnmounted(
            fn() => $this->di->get(CheckMediaTask::class)->importMusic(
                $this->getTestStation()->media_storage_location
            )
        );

        $this->em->clear();

        $I->assertSame('0', (string) $this->getTestStation()->media_storage_location->storageUsedBytes);
    }

    /**
     * @before setupComplete
     */
    public function keepMediaWhenListingFails(FunctionalTester $I): void
    {
        $I->wantTo('Skip a media storage without deleting media when listing it fails.');

        $mediaId = $this->uploadTestSong()->id;

        $failingAdapter = Mockery::mock(ExtendedAdapterInterface::class);
        $failingAdapter->allows('listContents')->andReturn(self::failingListing());

        $storageLocationAdapter = Mockery::mock(StorageLocationAdapterInterface::class);
        $storageLocationAdapter->allows('getFilesystem')->andReturn(new RemoteFilesystem($failingAdapter));

        $storageLocationRepo = Mockery::mock(StorageLocationRepository::class);
        $storageLocationRepo->allows('getAdapter')->andReturn($storageLocationAdapter);

        $checkMediaTask = $this->di->get(FactoryInterface::class)->make(
            CheckMediaTask::class,
            ['storageLocationRepo' => $storageLocationRepo]
        );
        $checkMediaTask->importMusic($this->getTestStation()->media_storage_location);

        $this->em->clear();

        $I->assertNotNull($this->em->find(StationMedia::class, $mediaId));
    }

    private function uploadTestSongToPlaylist(): StationMedia
    {
        $media = $this->uploadTestSong();

        $playlist = new StationPlaylist($this->getTestStation());
        $playlist->name = 'Rotation';
        $this->em->persist($playlist);
        $this->em->flush();

        $this->di->get(StationPlaylistMediaRepository::class)->addMediaToPlaylist($media, $playlist);
        $this->em->flush();

        return $media;
    }

    private static function failingListing(): Generator
    {
        yield from [];

        throw new RuntimeException('Transport endpoint is not connected');
    }
}
