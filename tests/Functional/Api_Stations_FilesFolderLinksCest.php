<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Repository\StationPlaylistFolderRepository;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistFolder;
use App\Entity\StationPlaylistMedia;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\StationFilesystems;
use FunctionalTester;

final class Api_Stations_FilesFolderLinksCest extends CestAbstract
{
    private const array TEST_FOLDERS = ['Archive', 'Imaging', 'Music', 'Musical', 'Show-A', 'Show_A'];

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
        $I->wantTo('Keep the playlist links of a folder that an underscore in a deleted folder name would match.');

        $this->getMediaFilesystem()->createDirectory('Show_A');
        $this->uploadTestSong('Show-A/test.mp3');
        $playlistId = $this->linkFolderToNewPlaylist('Show-A');

        $this->batch($I, 'delete', ['Show_A']);

        $I->assertSame(['Show-A'], $this->getFolderLinkPaths());
        $I->assertSame(1, $this->countPlaylistSongs($playlistId));
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
     * The station media directory outlives the test station, so remove what earlier runs left behind.
     */
    protected function clearTestFolders(): void
    {
        $fsMedia = $this->getMediaFilesystem();

        foreach (self::TEST_FOLDERS as $folder) {
            $fsMedia->deleteDirectory($folder);
        }
    }

    /**
     * @param string[] $dirs
     * @param array<string, string> $params
     */
    private function batch(FunctionalTester $I, string $action, array $dirs, array $params = []): void
    {
        $station = $this->getTestStation();

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut(
            '/api/station/' . $station->id . '/files/batch',
            [
                'do' => $action,
                'files' => [],
                'dirs' => $dirs,
                ...$params,
            ]
        );
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson(['success' => true]);

        $this->em->clear();
    }

    private function linkFolderToNewPlaylist(string $path): int
    {
        $station = $this->getTestStation();

        $playlist = new StationPlaylist($station);
        $playlist->name = 'Playlist for ' . $path;
        $this->em->persist($playlist);
        $this->em->flush();

        $this->di->get(StationPlaylistFolderRepository::class)->addPlaylistsToFolder(
            $station,
            $path,
            [$playlist->id => 0]
        );

        return $playlist->id;
    }

    /**
     * @return string[]
     */
    private function getFolderLinkPaths(): array
    {
        $paths = array_map(
            static fn(StationPlaylistFolder $folder) => $folder->path,
            $this->em->getRepository(StationPlaylistFolder::class)->findBy(['station' => $this->getTestStation()])
        );
        sort($paths);

        return $paths;
    }

    /**
     * @return string[]
     */
    private function getMediaPaths(): array
    {
        $paths = array_map(
            static fn(StationMedia $media) => $media->path,
            $this->em->getRepository(StationMedia::class)->findBy(
                ['storage_location' => $this->getTestStation()->media_storage_location]
            )
        );
        sort($paths);

        return $paths;
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
