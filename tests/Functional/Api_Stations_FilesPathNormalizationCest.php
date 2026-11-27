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
use App\Utilities\File;
use FunctionalTester;

final class Api_Stations_FilesPathNormalizationCest extends CestAbstract
{
    private const array TEST_FOLDERS = ['Archive', 'Music'];

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
        $I->wantTo('Reject batch actions on paths that point to the base directory without deleting anything.');

        $this->uploadTestSong('Music/test.mp3');
        $this->linkFolderToNewPlaylist('Music');

        foreach (['', '/', '.', './/'] as $basePath) {
            foreach (['dirs', 'files'] as $param) {
                $body = ['do' => 'delete', 'files' => [], 'dirs' => []];
                $body[$param] = [$basePath];

                $I->haveHttpHeader('Content-Type', 'application/json');
                $I->sendPut('/api/station/' . $this->getTestStation()->id . '/files/batch', $body);
                $I->dontSeeResponseContainsJson(['success' => true]);

                $this->em->clear();

                $I->assertTrue($this->getMediaFilesystem()->fileExists('Music/test.mp3'));
                $I->assertSame(['Music/test.mp3'], $this->getMediaPaths());
                $I->assertSame(['Music'], $this->getFolderLinkPaths());
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
     * @param array<string, mixed> $params
     */
    private function batch(FunctionalTester $I, string $action, array $dirs, array $params = []): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut(
            '/api/station/' . $this->getTestStation()->id . '/files/batch',
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

    private function upload(FunctionalTester $I, string $currentDirectory): void
    {
        $songPath = File::generateTempPath('test.mp3');
        copy(self::TEST_SONG_PATH, $songPath);

        $I->sendPost(
            '/api/station/' . $this->getTestStation()->id . '/files/upload',
            ['currentDirectory' => $currentDirectory],
            [
                'file' => [
                    'name' => 'test.mp3',
                    'type' => 'audio/mpeg',
                    'error' => UPLOAD_ERR_OK,
                    'size' => filesize($songPath),
                    'tmp_name' => $songPath,
                ],
            ]
        );

        $this->em->clear();
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

    private function createPlaylist(string $name): int
    {
        $playlist = new StationPlaylist($this->getTestStation());
        $playlist->name = $name;
        $this->em->persist($playlist);
        $this->em->flush();

        return $playlist->id;
    }

    private function linkFolderToNewPlaylist(string $path): int
    {
        $playlistId = $this->createPlaylist('Playlist for ' . $path);

        $this->di->get(StationPlaylistFolderRepository::class)->addPlaylistsToFolder(
            $this->getTestStation(),
            $path,
            [$playlistId => 0]
        );

        return $playlistId;
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
    private function getPlaylistMediaPaths(int $playlistId): array
    {
        $paths = array_map(
            static fn(StationPlaylistMedia $spm) => $spm->media->path,
            $this->em->getRepository(StationPlaylistMedia::class)->findBy(['playlist' => $playlistId])
        );
        sort($paths);

        return $paths;
    }

    private function getMediaFilesystem(): ExtendedFilesystemInterface
    {
        return $this->di->get(StationFilesystems::class)->getMediaFilesystem($this->getTestStation());
    }
}
