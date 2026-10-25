<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Repository\StationPlaylistFolderRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistFolder;
use App\Entity\StationPlaylistMedia;
use App\Entity\StorageLocationAdapter\StorageLocationAdapterInterface;
use App\Flysystem\Adapter\ExtendedAdapterInterface;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\StationFilesystems;
use App\Sync\Task\CheckFolderPlaylistsTask;
use DI\FactoryInterface;
use FunctionalTester;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCreateDirectory;
use Mockery;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

final class CheckFolderPlaylistsTaskCest extends CestAbstract
{
    /**
     * @before setupComplete
     */
    public function keepFolderWhileMediaIsOnRecord(FunctionalTester $I): void
    {
        $I->wantTo('Keep a folder playlist link while media under the folder is still on record.');

        $fsMedia = $this->getMediaFilesystem();
        $fsMedia->createDirectory('Other');

        $this->uploadTestSong('Folder/test.mp3');
        $playlistId = $this->linkFolderToPlaylist('Folder');

        $I->assertSame(1, $this->countFolderLinks($playlistId));
        $I->assertSame(1, $this->countPlaylistSongs($playlistId));

        $fsMedia->deleteDirectory('Folder');
        $this->syncPlaylistFolders();

        $I->assertSame(1, $this->countFolderLinks($playlistId));
        $I->assertSame(1, $this->countPlaylistSongs($playlistId));
    }

    /**
     * @before setupComplete
     */
    public function removeDeletedEmptyFolder(FunctionalTester $I): void
    {
        $I->wantTo('Remove the playlist link of a deleted empty folder while the media storage is visible.');

        $fsMedia = $this->getMediaFilesystem();
        $fsMedia->createDirectory('Other');
        $fsMedia->createDirectory('Gone');
        $playlistId = $this->linkFolderToPlaylist('Gone');

        $I->assertSame(1, $this->countFolderLinks($playlistId));

        $fsMedia->deleteDirectory('Gone');
        $this->syncPlaylistFolders();

        $I->assertSame(0, $this->countFolderLinks($playlistId));
    }

    /**
     * @before setupComplete
     */
    public function keepExistingEmptyFolder(FunctionalTester $I): void
    {
        $I->wantTo('Keep the playlist link of an empty folder that still exists.');

        $this->getMediaFilesystem()->createDirectory('Empty');
        $playlistId = $this->linkFolderToPlaylist('Empty');

        $I->assertSame(1, $this->countFolderLinks($playlistId));

        $this->syncPlaylistFolders();

        $I->assertSame(1, $this->countFolderLinks($playlistId));
    }

    /**
     * @before setupComplete
     */
    public function keepEmptyFolderWhileStorageIsUnmounted(FunctionalTester $I): void
    {
        $I->wantTo('Keep the playlist link of an empty folder while the media storage is unmounted.');

        $this->getMediaFilesystem()->createDirectory('Incoming');
        $playlistId = $this->linkFolderToPlaylist('Incoming');

        $I->assertSame(1, $this->countFolderLinks($playlistId));

        $this->withMediaStorageUnmounted(function (): void {
            $this->getMediaFilesystem()->createDirectory('.waveforms');
            $this->syncPlaylistFolders();
        });

        $I->assertSame(1, $this->countFolderLinks($playlistId));
    }

    /**
     * @before setupComplete
     */
    public function keepFolderWhenItsCheckFails(FunctionalTester $I): void
    {
        $I->wantTo('Keep the playlist link of an empty folder when checking the folder fails.');

        $playlistId = $this->linkFolderToPlaylist('Unreachable');

        $I->assertSame(1, $this->countFolderLinks($playlistId));

        $failingAdapter = Mockery::mock(ExtendedAdapterInterface::class);
        $failingAdapter->allows('directoryExists')
            ->andThrow(UnableToCheckDirectoryExistence::forLocation('Unreachable'));

        $storageLocationAdapter = Mockery::mock(StorageLocationAdapterInterface::class);
        $storageLocationAdapter->allows('getStorageAdapter')->andReturn($failingAdapter);

        $storageLocationRepo = Mockery::mock(StorageLocationRepository::class);
        $storageLocationRepo->allows('getAdapter')->andReturn($storageLocationAdapter);

        $this->em->clear();
        $this->makeTask($storageLocationRepo)->syncPlaylistFolders($this->getTestStation());
        $this->em->clear();

        $I->assertSame(1, $this->countFolderLinks($playlistId));
    }

    /**
     * @before setupComplete
     */
    public function skipStationWhenItsMediaStorageFails(FunctionalTester $I): void
    {
        $I->wantTo('Skip a station without stopping the folder sync when its media storage fails.');

        $storageLocationRepo = Mockery::mock(StorageLocationRepository::class);
        $storageLocationRepo->allows('getAdapter')
            ->andThrow(UnableToCreateDirectory::atLocation('/', 'Transport endpoint is not connected'));

        $logHandler = new TestHandler();
        $logger = $this->di->get(Logger::class);
        $logger->pushHandler($logHandler);

        try {
            $this->makeTask($storageLocationRepo)->run();
        } finally {
            $logger->popHandler();
        }

        $I->assertTrue($logHandler->hasErrorThatContains('Flysystem Error for Station'));
    }

    private function makeTask(StorageLocationRepository $storageLocationRepo): CheckFolderPlaylistsTask
    {
        $factory = $this->di->get(FactoryInterface::class);

        return $factory->make(
            CheckFolderPlaylistsTask::class,
            [
                'stationFilesystems' => $factory->make(
                    StationFilesystems::class,
                    ['storageLocationRepo' => $storageLocationRepo]
                ),
            ]
        );
    }

    private function linkFolderToPlaylist(string $path): int
    {
        $playlist = new StationPlaylist($this->getTestStation());
        $playlist->name = 'Folder Playlist';
        $this->em->persist($playlist);
        $this->em->flush();

        $this->di->get(StationPlaylistFolderRepository::class)->setPlaylistsForFolder(
            $this->getTestStation(),
            $path,
            [$playlist->id => 0]
        );

        return $playlist->id;
    }

    private function syncPlaylistFolders(): void
    {
        $this->em->clear();

        $this->di->get(CheckFolderPlaylistsTask::class)->syncPlaylistFolders($this->getTestStation());

        $this->em->clear();
    }

    /**
     * @phpstan-impure
     */
    private function countFolderLinks(int $playlistId): int
    {
        return $this->em->getRepository(StationPlaylistFolder::class)->count(['playlist' => $playlistId]);
    }

    /**
     * @phpstan-impure
     */
    private function countPlaylistSongs(int $playlistId): int
    {
        return $this->em->getRepository(StationPlaylistMedia::class)->count(['playlist' => $playlistId]);
    }

    private function getMediaFilesystem(): ExtendedFilesystemInterface
    {
        return $this->di->get(StationFilesystems::class)->getMediaFilesystem($this->getTestStation());
    }
}
