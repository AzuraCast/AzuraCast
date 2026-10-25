<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Repository\StationPlaylistFolderRepository;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistFolder;
use App\Entity\StationPlaylistMedia;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\StationFilesystems;
use App\Sync\Task\CheckFolderPlaylistsTask;
use FunctionalTester;

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

    private function countFolderLinks(int $playlistId): int
    {
        return $this->em->getRepository(StationPlaylistFolder::class)->count(['playlist' => $playlistId]);
    }

    private function countPlaylistSongs(int $playlistId): int
    {
        return $this->em->getRepository(StationPlaylistMedia::class)->count(['playlist' => $playlistId]);
    }

    private function getMediaFilesystem(): ExtendedFilesystemInterface
    {
        return $this->di->get(StationFilesystems::class)->getMediaFilesystem($this->getTestStation());
    }
}
