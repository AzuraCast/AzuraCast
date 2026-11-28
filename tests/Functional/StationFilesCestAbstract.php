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

abstract class StationFilesCestAbstract extends CestAbstract
{
    /**
     * The top-level media folders the tests create.
     *
     * @return string[]
     */
    abstract protected function getTestFolders(): array;

    /**
     * The station media directory outlives the test station, so remove what earlier runs left behind.
     */
    protected function clearTestFolders(): void
    {
        $fsMedia = $this->getMediaFilesystem();

        foreach ($this->getTestFolders() as $folder) {
            $fsMedia->deleteDirectory($folder);
        }
    }

    /**
     * @param string[] $dirs
     * @param array<string, mixed> $params
     */
    protected function batch(FunctionalTester $I, string $action, array $dirs, array $params = []): void
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

    /**
     * @return list<array{
     *  path: string,
     *  type: string,
     *  dir: array{playlists: list<array{id: int}>}
     * }>
     */
    protected function listFiles(FunctionalTester $I, string $currentDirectory, ?string $searchPhrase = null): array
    {
        $I->sendGet(
            '/api/station/' . $this->getTestStation()->id . '/files/list',
            [
                'currentDirectory' => $currentDirectory,
                'searchPhrase' => $searchPhrase,
                'flushCache' => 1,
            ]
        );
        $I->seeResponseCodeIsSuccessful();

        return json_decode($I->grabResponse(), true, flags: JSON_THROW_ON_ERROR);
    }

    protected function upload(FunctionalTester $I, string $currentDirectory): void
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

    protected function createPlaylist(string $name): int
    {
        $playlist = new StationPlaylist($this->getTestStation());
        $playlist->name = $name;
        $this->em->persist($playlist);
        $this->em->flush();

        return $playlist->id;
    }

    protected function linkFolderToNewPlaylist(string $path): int
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
    protected function getMediaPaths(): array
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
    protected function getFolderLinkPaths(): array
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
    protected function getPlaylistMediaPaths(int $playlistId): array
    {
        $paths = array_map(
            static fn(StationPlaylistMedia $spm) => $spm->media->path,
            $this->em->getRepository(StationPlaylistMedia::class)->findBy(['playlist' => $playlistId])
        );
        sort($paths);

        return $paths;
    }

    protected function getMediaFilesystem(): ExtendedFilesystemInterface
    {
        return $this->di->get(StationFilesystems::class)->getMediaFilesystem($this->getTestStation());
    }
}
