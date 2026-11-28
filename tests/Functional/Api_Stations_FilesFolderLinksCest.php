<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\StationPlaylistMedia;
use FunctionalTester;

final class Api_Stations_FilesFolderLinksCest extends StationFilesCestAbstract
{
    protected function getTestFolders(): array
    {
        return [
            'Archive',
            'Imaging',
            'Music',
            'Musical',
            'Show%',
            'Show-A',
            'Show_A',
            'show_a',
        ];
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function deleteFolderKeepsLinksOfFolderSharingItsNameAsPrefix(FunctionalTester $I): void
    {
        $I->wantTo('Keep the playlist links of a folder whose name starts with the name of a deleted folder.');

        $this->getMediaFilesystem()->createDirectory('Imaging/sendung');
        $this->uploadTestSong('Imaging/Sendungen/Show/test.mp3');
        $playlistId = $this->linkFolderToNewPlaylist('Imaging/Sendungen/Show');

        $this->batch($I, 'delete', ['Imaging/sendung']);

        $I->assertSame(['Imaging/Sendungen/Show'], $this->getFolderLinkPaths());
        $I->assertSame(1, $this->countPlaylistSongs($playlistId));
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function deleteFolderKeepsLinksOfFolderDifferingInCase(FunctionalTester $I): void
    {
        $I->wantTo('Keep the playlist links of a folder whose name differs from a deleted folder only in case.');

        $this->getMediaFilesystem()->createDirectory('Music/pop');
        $this->uploadTestSong('Music/Pop/test.mp3');
        $playlistId = $this->linkFolderToNewPlaylist('Music/Pop');

        $this->batch($I, 'delete', ['Music/pop']);

        $I->assertSame(['Music/Pop'], $this->getFolderLinkPaths());
        $I->assertSame(1, $this->countPlaylistSongs($playlistId));
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function deleteFolderKeepsLinksOfFolderMatchingItsWildcards(FunctionalTester $I): void
    {
        $I->wantTo('Keep the playlist links of a folder that wildcards in a deleted folder name would match.');

        $this->getMediaFilesystem()->createDirectory('Show_A');
        $this->getMediaFilesystem()->createDirectory('Show%');
        $this->uploadTestSong('Show-A/Sub/test.mp3');
        $playlistId = $this->linkFolderToNewPlaylist('Show-A/Sub');

        foreach (['Show_A', 'Show%'] as $deletedFolder) {
            $this->batch($I, 'delete', [$deletedFolder]);

            $I->assertSame(['Show-A/Sub'], $this->getFolderLinkPaths());
            $I->assertSame(1, $this->countPlaylistSongs($playlistId));
        }
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function deleteFolderRemovesLinksOfItselfAndItsSubfolders(FunctionalTester $I): void
    {
        $I->wantTo('Remove the playlist links of a deleted folder and of the folders below it.');

        $this->uploadTestSong('Music/Rock/test.mp3');
        $this->getMediaFilesystem()->createDirectory('Musical');
        $this->linkFolderToNewPlaylist('Music');
        $this->linkFolderToNewPlaylist('Music/Rock');
        $this->linkFolderToNewPlaylist('Musical');

        $this->batch($I, 'delete', ['Music']);

        $I->assertSame(['Musical'], $this->getFolderLinkPaths());
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function renameFolderLeavesFoldersSharingItsNameAsPrefixOrDifferingInCase(FunctionalTester $I): void
    {
        $I->wantTo('Rename only the records of the renamed folder and the folders below it.');

        $this->uploadTestSong('Music/pop/Sub/test.mp3');
        $this->uploadTestSong('Music/Pop/other.mp3');
        $this->uploadTestSong('Music/popular/other.mp3');
        $this->linkFolderToNewPlaylist('Music/pop');
        $this->linkFolderToNewPlaylist('Music/pop/Sub');
        $this->linkFolderToNewPlaylist('Music/Pop');
        $this->linkFolderToNewPlaylist('Music/popular');

        $station = $this->getTestStation();

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut(
            '/api/station/' . $station->id . '/files/rename',
            ['file' => 'Music/pop', 'newPath' => 'Music/Rock']
        );
        $I->seeResponseCodeIsSuccessful();

        $this->em->clear();

        $I->assertSame(
            ['Music/Pop', 'Music/Rock', 'Music/Rock/Sub', 'Music/popular'],
            $this->getFolderLinkPaths()
        );
        $I->assertSame(
            ['Music/Pop/other.mp3', 'Music/Rock/Sub/test.mp3', 'Music/popular/other.mp3'],
            $this->getMediaPaths()
        );
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function moveFolderLeavesFoldersSharingItsNameAsPrefixOrDifferingInCase(FunctionalTester $I): void
    {
        $I->wantTo('Move only the records of the moved folder and the folders below it.');

        $this->getMediaFilesystem()->createDirectory('Archive');
        $this->uploadTestSong('Music/pop/test.mp3');
        $this->uploadTestSong('Music/Pop/other.mp3');
        $this->uploadTestSong('Music/popular/other.mp3');
        $this->linkFolderToNewPlaylist('Music/pop');
        $this->linkFolderToNewPlaylist('Music/Pop');
        $this->linkFolderToNewPlaylist('Music/popular');

        $this->batch($I, 'move', ['Music/pop'], [
            'currentDirectory' => 'Music',
            'directory' => 'Archive',
        ]);

        $I->assertSame(
            ['Archive/pop', 'Music/Pop', 'Music/popular'],
            $this->getFolderLinkPaths()
        );
        $I->assertSame(
            ['Archive/pop/test.mp3', 'Music/Pop/other.mp3', 'Music/popular/other.mp3'],
            $this->getMediaPaths()
        );
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function moveFolderLeavesFoldersMatchingItsWildcards(FunctionalTester $I): void
    {
        $I->wantTo('Move only the records of the moved folder, not of a folder that its wildcards would match.');

        $this->getMediaFilesystem()->createDirectory('Archive');
        $this->uploadTestSong('Show_A/test.mp3');
        $this->uploadTestSong('Show%/test.mp3');
        $this->uploadTestSong('Show-A/Sub/test.mp3');
        $this->linkFolderToNewPlaylist('Show_A');
        $this->linkFolderToNewPlaylist('Show%');
        $this->linkFolderToNewPlaylist('Show-A/Sub');

        foreach (['Show_A', 'Show%'] as $movedFolder) {
            $this->batch($I, 'move', [$movedFolder], [
                'currentDirectory' => '',
                'directory' => 'Archive',
            ]);
        }

        $I->assertSame(
            ['Archive/Show%', 'Archive/Show_A', 'Show-A/Sub'],
            $this->getFolderLinkPaths()
        );
        $I->assertSame(
            ['Archive/Show%/test.mp3', 'Archive/Show_A/test.mp3', 'Show-A/Sub/test.mp3'],
            $this->getMediaPaths()
        );
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function setPlaylistsForFolderAddsOnlyMediaInsideIt(FunctionalTester $I): void
    {
        $I->wantTo('Add only the media inside a folder to its playlists, not that of similarly named folders.');

        $this->getMediaFilesystem()->createDirectory('Show_A');
        $this->uploadTestSong('Music/pop/test.mp3');
        $this->uploadTestSong('Music/Pop/other.mp3');
        $this->uploadTestSong('Show-A/test.mp3');
        $playlistId = $this->createPlaylist('Folder Playlist');

        $this->batch($I, 'playlist', ['Music/pop', 'Show_A'], [
            'playlists' => [$playlistId],
        ]);

        $I->assertSame(['Music/pop/test.mp3'], $this->getPlaylistMediaPaths($playlistId));
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function setPlaylistsForFolderKeepsLinksOfFolderDifferingInCase(FunctionalTester $I): void
    {
        $I->wantTo('Set the playlists of a folder without removing those of a folder differing only in case.');

        $this->getMediaFilesystem()->createDirectory('Music/pop');
        $this->uploadTestSong('Music/Pop/test.mp3');
        $linkedPlaylistId = $this->linkFolderToNewPlaylist('Music/Pop');
        $newPlaylistId = $this->createPlaylist('Folder Playlist');

        $this->batch($I, 'playlist', ['Music/pop'], [
            'playlists' => [$newPlaylistId],
        ]);

        $I->assertSame(['Music/Pop', 'Music/pop'], $this->getFolderLinkPaths());
        $I->assertSame(1, $this->countPlaylistSongs($linkedPlaylistId));
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function setPlaylistsForFolderLinksPlaylistOfFolderDifferingInCase(FunctionalTester $I): void
    {
        $I->wantTo('Link a playlist to a folder even if a folder differing only in case already has it.');

        $this->getMediaFilesystem()->createDirectory('Music/Pop');
        $this->uploadTestSong('Music/pop/test.mp3');
        $playlistId = $this->linkFolderToNewPlaylist('Music/Pop');

        $this->batch($I, 'playlist', ['Music/pop'], [
            'playlists' => [$playlistId],
        ]);

        $I->assertSame(['Music/Pop', 'Music/pop'], $this->getFolderLinkPaths());
        $I->assertSame(['Music/pop/test.mp3'], $this->getPlaylistMediaPaths($playlistId));
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function uploadAddsFileOnlyToPlaylistsOfItsFolders(FunctionalTester $I): void
    {
        $I->wantTo('Add an uploaded file to the playlists of its folder and parents, not of similarly named folders.');

        $parentPlaylistId = $this->linkFolderToNewPlaylist('Music/pop');
        $differingParentPlaylistId = $this->linkFolderToNewPlaylist('Music/Pop');
        $differingFolderPlaylistId = $this->linkFolderToNewPlaylist('Music/pop/sub');

        $this->upload($I, 'Music/pop/Sub');
        $I->seeResponseCodeIsSuccessful();

        $I->assertSame(['Music/pop/Sub/test.mp3'], $this->getPlaylistMediaPaths($parentPlaylistId));
        $I->assertSame([], $this->getPlaylistMediaPaths($differingParentPlaylistId));
        $I->assertSame([], $this->getPlaylistMediaPaths($differingFolderPlaylistId));
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function searchInFolderListsOnlyMediaInsideIt(FunctionalTester $I): void
    {
        $I->wantTo('Search only the media inside the current folder, not that of similarly named folders.');

        $this->uploadTestSong('Show_A/test.mp3');
        $this->uploadTestSong('Show-A/test.mp3');
        $this->uploadTestSong('show_a/other.mp3');

        $rows = $this->listFiles($I, 'Show_A', '*');

        $I->assertSame(['Show_A/test.mp3'], array_column($rows, 'path'));
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function listFolderShowsOnlyPlaylistsOfItsParents(FunctionalTester $I): void
    {
        $I->wantTo('List the playlists of the current folder on its subfolders, not of folders differing in case.');

        $this->getMediaFilesystem()->createDirectory('Music/pop/Sub');
        $this->getMediaFilesystem()->createDirectory('Music/Pop');
        $parentPlaylistId = $this->linkFolderToNewPlaylist('Music/pop');
        $this->linkFolderToNewPlaylist('Music/Pop');

        $rows = $this->listFiles($I, 'Music/pop');

        $I->assertSame(['Music/pop/Sub'], array_column($rows, 'path'));
        $I->assertSame([$parentPlaylistId], array_column($rows[0]['dir']['playlists'], 'id'));
    }

    /**
     * @phpstan-impure
     */
    private function countPlaylistSongs(int $playlistId): int
    {
        return $this->em->getRepository(StationPlaylistMedia::class)->count(['playlist' => $playlistId]);
    }
}
