<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\StationPlaylist;
use App\Utilities\File;
use FunctionalTester;

final class Api_Stations_FilesPathNormalizationCest extends StationFilesCestAbstract
{
    protected function getTestFolders(): array
    {
        return ['Archive', 'Music'];
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function renameStoresNormalizedPaths(FunctionalTester $I): void
    {
        $I->wantTo('Store normalized paths when renaming a folder given with extra slashes.');

        $this->uploadTestSong('Music/pop/Sub/test.mp3');
        $this->linkFolderToNewPlaylist('Music/pop');
        $this->linkFolderToNewPlaylist('Music/pop/Sub');

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut(
            '/api/station/' . $this->getTestStation()->id . '/files/rename',
            ['file' => 'Music/pop/', 'newPath' => '/Music//Rock/']
        );
        $I->seeResponseCodeIsSuccessful();

        $this->em->clear();

        $I->assertSame(['Music/Rock/Sub/test.mp3'], $this->getMediaPaths());
        $I->assertSame(['Music/Rock', 'Music/Rock/Sub'], $this->getFolderLinkPaths());
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function moveStoresNormalizedPaths(FunctionalTester $I): void
    {
        $I->wantTo('Store normalized paths when moving a folder given with extra slashes.');

        $this->getMediaFilesystem()->createDirectory('Archive');
        $this->uploadTestSong('Music/pop/Sub/test.mp3');
        $this->linkFolderToNewPlaylist('Music/pop');
        $this->linkFolderToNewPlaylist('Music/pop/Sub');

        $this->batch($I, 'move', ['Music/pop/'], [
            'currentDirectory' => '/Music/',
            'directory' => 'Archive/',
        ]);

        $I->assertSame(['Archive/pop/Sub/test.mp3'], $this->getMediaPaths());
        $I->assertSame(['Archive/pop', 'Archive/pop/Sub'], $this->getFolderLinkPaths());
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function deleteRemovesLinksOfFolderGivenWithExtraSlashes(FunctionalTester $I): void
    {
        $I->wantTo('Remove the playlist links of a deleted folder given with extra slashes.');

        $this->uploadTestSong('Music/pop/Sub/test.mp3');
        $this->linkFolderToNewPlaylist('Music/pop');
        $this->linkFolderToNewPlaylist('Music/pop/Sub');

        $this->batch($I, 'delete', ['/Music/pop/']);

        $I->assertSame([], $this->getMediaPaths());
        $I->assertSame([], $this->getFolderLinkPaths());
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function batchRejectsPathsPointingToBaseDirectory(FunctionalTester $I): void
    {
        $I->wantTo('Reject batch actions on paths that point to the base directory without changing anything.');

        $this->getMediaFilesystem()->createDirectory('Archive');
        $this->uploadTestSong('Music/test.mp3');
        $this->linkFolderToNewPlaylist('Music');
        $playlistId = $this->createPlaylist('Base Directory Playlist');

        $actions = [
            'delete' => [],
            'move' => ['currentDirectory' => '', 'directory' => 'Archive'],
            'playlist' => ['playlists' => [$playlistId]],
        ];

        foreach ($actions as $action => $params) {
            foreach (['', '/', '.', './/'] as $basePath) {
                foreach (['dirs', 'files'] as $param) {
                    $body = ['do' => $action, 'files' => [], 'dirs' => [], ...$params];
                    $body[$param] = [$basePath];

                    $I->haveHttpHeader('Content-Type', 'application/json');
                    $I->sendPut('/api/station/' . $this->getTestStation()->id . '/files/batch', $body);
                    $I->seeResponseContainsJson([
                        'success' => false,
                        'message' => 'Batch actions cannot be applied to the base directory.',
                    ]);

                    $this->em->clear();

                    $I->assertTrue($this->getMediaFilesystem()->fileExists('Music/test.mp3'));
                    $I->assertSame(['Music/test.mp3'], $this->getMediaPaths());
                    $I->assertSame(['Music'], $this->getFolderLinkPaths());
                }
            }
        }
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function uploadStoresNormalizedPath(FunctionalTester $I): void
    {
        $I->wantTo('Store a normalized path for a file uploaded into a directory given with extra slashes.');

        $this->getMediaFilesystem()->createDirectory('Music/pop');
        $playlistId = $this->linkFolderToNewPlaylist('Music/pop');

        $this->upload($I, '/Music//pop/');
        $I->seeResponseCodeIsSuccessful();

        $I->assertSame(['Music/pop/test.mp3'], $this->getMediaPaths());
        $I->assertSame(['Music/pop/test.mp3'], $this->getPlaylistMediaPaths($playlistId));
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function uploadRejectsDirectoryOutsideMediaStorage(FunctionalTester $I): void
    {
        $I->wantTo('Reject an upload into a directory outside the media storage without creating a media record.');

        $this->upload($I, '../escape');
        $I->dontSeeResponseContainsJson(['success' => true]);

        $I->assertSame([], $this->getMediaPaths());
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function base64UploadStoresNormalizedPath(FunctionalTester $I): void
    {
        $I->wantTo('Store a normalized path for a base64 upload given with extra slashes.');

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost(
            '/api/station/' . $this->getTestStation()->id . '/files',
            [
                'path' => '/Music//pop/test.mp3',
                'file' => base64_encode((string)file_get_contents(self::TEST_SONG_PATH)),
            ]
        );
        $I->seeResponseCodeIsSuccessful();

        $this->em->clear();

        $I->assertSame(['Music/pop/test.mp3'], $this->getMediaPaths());
        $I->assertTrue($this->getMediaFilesystem()->fileExists('Music/pop/test.mp3'));
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function base64UploadOutsideMediaStorageLeavesNoTempFile(FunctionalTester $I): void
    {
        $I->wantTo('Reject a base64 upload outside the media storage without leaving its temp file behind.');

        $fileName = 'escape-leftover.mp3';
        $tempPath = $this->getTestStation()->getRadioTempDir() . '/' . File::sanitizeFileName($fileName);
        @unlink($tempPath);

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost(
            '/api/station/' . $this->getTestStation()->id . '/files',
            [
                'path' => '../' . $fileName,
                'file' => base64_encode((string)file_get_contents(self::TEST_SONG_PATH)),
            ]
        );
        $I->dontSeeResponseContainsJson(['success' => true]);

        $this->em->clear();

        $I->assertSame([], $this->getMediaPaths());
        $I->assertFileDoesNotExist($tempPath);
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function base64UploadRejectsPathsPointingToBaseDirectory(FunctionalTester $I): void
    {
        $I->wantTo('Reject base64 uploads to paths that point to the base directory.');

        $tempDir = $this->getTestStation()->getRadioTempDir();
        $tempFilesBefore = scandir($tempDir);

        foreach (['/', '.', 'a/..', './/'] as $basePath) {
            $I->haveHttpHeader('Content-Type', 'application/json');
            $I->sendPost(
                '/api/station/' . $this->getTestStation()->id . '/files',
                [
                    'path' => $basePath,
                    'file' => base64_encode((string)file_get_contents(self::TEST_SONG_PATH)),
                ]
            );
            $I->dontSeeResponseContainsJson(['success' => true]);

            $this->em->clear();

            $I->assertSame([], $this->getMediaPaths());
            $I->assertSame($tempFilesBefore, scandir($tempDir));
        }
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function editMediaStoresNormalizedPath(FunctionalTester $I): void
    {
        $I->wantTo('Store a normalized path when editing the path of a media file with extra slashes.');

        $media = $this->uploadTestSong('Music/pop/test.mp3');

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut(
            '/api/station/' . $this->getTestStation()->id . '/file/' . $media->id,
            ['path' => '/Music//pop/renamed.mp3']
        );
        $I->seeResponseCodeIsSuccessful();

        $this->em->clear();

        $I->assertSame(['Music/pop/renamed.mp3'], $this->getMediaPaths());
        $I->assertTrue($this->getMediaFilesystem()->fileExists('Music/pop/renamed.mp3'));
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function applyToStoresNormalizedFolderPath(FunctionalTester $I): void
    {
        $I->wantTo('Store a normalized folder path when applying a playlist to a folder given with extra slashes.');

        $this->uploadTestSong('Music/pop/test.mp3');
        $playlistId = $this->createPlaylist('Apply Base');

        $this->applyTo($I, $playlistId, ['/Music//./pop/'], false);
        $I->seeResponseContainsJson(['success' => true]);

        $I->assertSame(['Music/pop'], $this->getFolderLinkPaths());
        $I->assertSame(['Music/pop/test.mp3'], $this->getPlaylistMediaPaths($playlistId));
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function applyToNamesCopiedPlaylistAfterNormalizedPath(FunctionalTester $I): void
    {
        $I->wantTo('Name a playlist copied onto a folder given with backslashes after the normalized folder path.');

        $this->uploadTestSong('Music/pop/test.mp3');
        $playlistId = $this->createPlaylist('Apply Base');

        $this->applyTo($I, $playlistId, ['Music\\pop'], true);
        $I->seeResponseContainsJson(['success' => true]);

        $copy = $this->em->getRepository(StationPlaylist::class)->findOneBy([
            'station' => $this->getTestStation(),
            'name' => 'Apply Base - Music/pop',
        ]);
        $I->assertNotNull($copy);

        $I->assertSame(['Music/pop'], $this->getFolderLinkPaths());
        $I->assertSame(['Music/pop/test.mp3'], $this->getPlaylistMediaPaths($copy->id));
        $I->assertSame([], $this->getPlaylistMediaPaths($playlistId));
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function applyToRejectsFolderOutsideMediaStorage(FunctionalTester $I): void
    {
        $I->wantTo('Reject applying a playlist to a folder outside the media storage without linking or copying it.');

        $this->uploadTestSong('Music/pop/test.mp3');
        $playlistId = $this->createPlaylist('Apply Base');

        $this->applyTo($I, $playlistId, ['Music/pop', '../escape'], true);
        $I->dontSeeResponseContainsJson(['success' => true]);

        $I->assertSame([], $this->getFolderLinkPaths());
        $I->assertSame(
            ['Apply Base'],
            array_values(array_filter(
                $this->getPlaylistNames(),
                static fn(string $name) => str_starts_with($name, 'Apply Base')
            ))
        );
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function listFilesShowsMediaAndFolderPlaylistsOfDirectoryGivenWithExtraSlashes(FunctionalTester $I): void
    {
        $I->wantTo('List the media and folder playlists of a directory given with extra slashes.');

        $this->uploadTestSong('Music/test.mp3');
        $this->getMediaFilesystem()->createDirectory('Music/pop');
        $parentPlaylistId = $this->linkFolderToNewPlaylist('Music');
        $folderPlaylistId = $this->linkFolderToNewPlaylist('Music/pop');

        foreach (['Music/', '/Music', '//Music/./'] as $currentDirectory) {
            $rows = $this->listFiles($I, $currentDirectory);

            $I->assertSame(['Music/pop', 'Music/test.mp3'], array_column($rows, 'path'));
            $I->assertSame(['directory', 'media'], array_column($rows, 'type'));

            $playlistIds = array_column($rows[0]['dir']['playlists'], 'id');
            sort($playlistIds);
            $I->assertSame([$parentPlaylistId, $folderPlaylistId], $playlistIds);
        }
    }

    /**
     * @before login
     * @before clearTestFolders
     */
    public function searchInDirectoryGivenWithExtraSlashesFindsItsMedia(FunctionalTester $I): void
    {
        $I->wantTo('Search the media inside a directory given with extra slashes.');

        $this->uploadTestSong('Music/test.mp3');

        foreach (['Music/', '/Music', '//Music/./'] as $currentDirectory) {
            $rows = $this->listFiles($I, $currentDirectory, '*');

            $I->assertSame(['Music/test.mp3'], array_column($rows, 'path'));
        }
    }

    /**
     * @param string[] $directories
     */
    private function applyTo(FunctionalTester $I, int $playlistId, array $directories, bool $copyPlaylist): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut(
            '/api/station/' . $this->getTestStation()->id . '/playlist/' . $playlistId . '/apply-to',
            [
                'directories' => $directories,
                'copyPlaylist' => $copyPlaylist,
            ]
        );

        $this->em->clear();
    }

    /**
     * @return string[]
     */
    private function getPlaylistNames(): array
    {
        $names = array_map(
            static fn(StationPlaylist $playlist) => $playlist->name,
            $this->em->getRepository(StationPlaylist::class)->findBy(['station' => $this->getTestStation()])
        );
        sort($names);

        return $names;
    }
}
