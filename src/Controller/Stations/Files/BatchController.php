<?php
namespace App\Controller\Stations\Files;

use App\Entity;
use App\Flysystem\StationFilesystem;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Filesystem;
use Azura\Exception\CsrfValidationException;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Http\Message\ResponseInterface;

class BatchController extends FilesControllerAbstract
{
    /** @var EntityManager */
    protected $em;

    /** @var Entity\Repository\StationMediaRepository */
    protected $mediaRepo;

    /** @var Entity\Repository\StationPlaylistMediaRepository */
    protected $playlistMediaRepo;

    /** @var Filesystem */
    protected $filesystem;

    /**
     * @param EntityManager $em
     * @param Entity\Repository\StationMediaRepository $mediaRepo
     * @param Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo
     * @param Filesystem $filesystem
     */
    public function __construct(
        EntityManager $em,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo,
        Filesystem $filesystem
    ) {
        $this->em = $em;
        $this->mediaRepo = $mediaRepo;
        $this->playlistMediaRepo = $playlistMediaRepo;
        $this->filesystem = $filesystem;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        try {
            $request->getCsrf()->verify($request->getParam('csrf'), $this->csrf_namespace);
        } catch (CsrfValidationException $e) {
            return $response->withStatus(403)
                ->withJson(['error' => ['code' => 403, 'msg' => 'CSRF Failure: ' . $e->getMessage()]]);
        }

        $station = $request->getStation();
        $fs = $this->filesystem->getForStation($station);

        // Convert from pipe-separated files parameter into actual paths.
        $files_raw = $request->getParam('files');
        $files = [];

        foreach ($files_raw as $file) {
            $file_path = 'media://' . $file;

            if ($fs->has($file_path)) {
                $files[] = $file_path;
            }
        }

        $files_found = 0;
        $files_affected = 0;

        $response_record = null;
        $errors = [];

        [$action, $action_id] = explode('_', $request->getParam('do'));

        switch ($action) {
            case 'delete':
                // Remove the database entries of any music being removed.
                $music_files = $this->_getMusicFiles($fs, $files);
                $files_found = count($music_files);

                /** @var Entity\StationPlaylist[] $playlists */
                $affected_playlists = [];

                foreach ($music_files as $file) {
                    try {
                        /** @var Entity\StationMedia $media */
                        $media = $this->mediaRepo->findByPath($file['path'], $station);

                        $media_playlists = $this->playlistMediaRepo->clearPlaylistsFromMedia($media);

                        foreach ($media_playlists as $playlist_id => $playlist) {
                            if (!isset($affected_playlists[$playlist_id])) {
                                $affected_playlists[$playlist_id] = $playlist;
                            }
                        }

                        if ($media instanceof Entity\StationMedia) {
                            $this->em->remove($media);
                        }
                    } catch (Exception $e) {
                        $errors[] = $file . ': ' . $e->getMessage();
                    }

                    $files_affected++;
                }

                $this->em->flush();

                // Delete all selected files.
                foreach ($files as $file) {
                    $file_meta = $fs->getMetadata($file);

                    if ('dir' === $file_meta['type']) {
                        $fs->deleteDir($file);
                    } else {
                        $station->removeStorageUsed($file_meta['size']);

                        $fs->delete($file);
                    }
                }

                $this->em->persist($station);
                $this->em->flush($station);

                // Write new PLS playlist configuration.
                $backend = $request->getStationBackend();

                if ($backend instanceof Liquidsoap) {
                    foreach ($affected_playlists as $playlist) {
                        /** @var Entity\StationPlaylist $playlist */
                        $backend->writePlaylistFile($playlist);
                    }
                }
                break;

            case 'playlist':
                // Set playlists for selected files.
                $response_record = null;

                /** @var Entity\StationPlaylist[] $playlists */
                $affected_playlists = [];

                $playlists = [];
                $playlist_weights = [];

                foreach ($request->getParam('playlists') as $playlist_id) {
                    if ('new' === $playlist_id) {
                        $playlist = new Entity\StationPlaylist($station);
                        $playlist->setName($request->getParam('new_playlist_name'));

                        $this->em->persist($playlist);
                        $this->em->flush();

                        $response_record = [
                            'id' => $playlist->getId(),
                            'name' => $playlist->getName(),
                        ];

                        $affected_playlists[$playlist->getId()] = $playlist;
                        $playlists[] = $playlist;
                        $playlist_weights[$playlist->getId()] = 0;
                    } else {
                        $playlist = $this->em->getRepository(Entity\StationPlaylist::class)->findOneBy([
                            'station_id' => $station->getId(),
                            'id' => (int)$playlist_id,
                        ]);

                        if ($playlist instanceof Entity\StationPlaylist) {
                            $affected_playlists[$playlist->getId()] = $playlist;
                            $playlists[] = $playlist;
                            $playlist_weights[$playlist->getId()] = $this->playlistMediaRepo->getHighestSongWeight($playlist);
                        }
                    }
                }

                $music_files = $this->_getMusicFiles($fs, $files);
                $files_found = count($music_files);

                foreach ($music_files as $file) {
                    try {
                        $media = $this->mediaRepo->getOrCreate($station, $file['path']);

                        $media_playlists = $this->playlistMediaRepo->clearPlaylistsFromMedia($media);
                        foreach ($media_playlists as $playlist_id => $playlist) {
                            if (!isset($affected_playlists[$playlist_id])) {
                                $affected_playlists[$playlist_id] = $playlist;
                            }
                        }

                        foreach ($playlists as $playlist) {
                            $playlist_weights[$playlist->getId()]++;
                            $weight = $playlist_weights[$playlist->getId()];

                            $this->playlistMediaRepo->addMediaToPlaylist($media, $playlist, $weight);
                        }
                    } catch (Exception $e) {
                        $errors[] = $file . ': ' . $e->getMessage();
                    }

                    $files_affected++;
                }

                $this->em->flush();

                // Write new PLS playlist configuration.
                $backend = $request->getStationBackend();

                if ($backend instanceof Liquidsoap) {
                    foreach ($affected_playlists as $playlist) {
                        /** @var Entity\StationPlaylist $playlist */
                        $backend->writePlaylistFile($playlist);
                    }
                }
                break;

            case 'move':
                $music_files = $this->_getMusicFiles($fs, $files);
                $files_found = count($music_files);

                $directory_path = $request->getParam('directory');
                $directory_path_full = 'media://' . $directory_path;

                try {
                    // Verify that you're moving to a directory (if it's not the root dir).
                    if ('' !== $directory_path) {
                        $directory_path_meta = $fs->getMetadata($directory_path_full);
                        if ('dir' !== $directory_path_meta['type']) {
                            throw new \Azura\Exception(__('Path "%s" is not a folder.', $directory_path_full));
                        }
                    }

                    foreach ($music_files as $file) {
                        $media = $this->mediaRepo->getOrCreate($station, $file['path']);

                        $old_full_path = $media->getPathUri();

                        $newPath = ('' === $directory_path)
                            ? $file['basename']
                            : $directory_path . '/' . $file['basename'];
                        $media->setPath($newPath);

                        if (!$fs->rename($old_full_path, $media->getPath())) {
                            throw new \Azura\Exception(__('Could not move "%s" to "%s"', $old_full_path,
                                $media->getPath()));
                        }

                        $this->em->persist($media);
                        $this->em->flush($media);
                        $files_affected++;
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }

                $this->em->flush();
                break;
        }

        $this->em->clear(Entity\StationMedia::class);
        $this->em->clear(Entity\StationPlaylist::class);
        $this->em->clear(Entity\StationPlaylistMedia::class);

        return $response->withJson([
            'success' => true,
            'files_found' => $files_found,
            'files_affected' => $files_affected,
            'errors' => $errors,
            'record' => $response_record,
        ]);
    }

    protected function _getMusicFiles(StationFilesystem $fs, $path, $recursive = true)
    {
        if (is_array($path)) {
            $music_files = [];
            foreach ($path as $dir_file) {
                $music_files = array_merge($music_files, $this->_getMusicFiles($fs, $dir_file, $recursive));
            }

            return $music_files;
        }

        $path_meta = $fs->getMetadata($path);
        if ('file' === $path_meta['type']) {
            return [$path_meta];
        }

        return array_filter($fs->listContents($path, $recursive), function ($file) {
            return ('file' === $file['type']);
        });
    }
}
