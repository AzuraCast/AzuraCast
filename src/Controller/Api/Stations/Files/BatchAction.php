<?php
namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\StationFilesystem;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Filesystem;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Http\Message\ResponseInterface;

class BatchAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManager $em,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo,
        Filesystem $filesystem
    ): ResponseInterface {
        $station = $request->getStation();
        $fs = $filesystem->getForStation($station);

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
        
        switch ($request->getParam('do')) {
            case 'delete':
                // Remove the database entries of any music being removed.
                $music_files = $this->_getMusicFiles($fs, $files);
                $files_found = count($music_files);

                /** @var Entity\StationPlaylist[] $playlists */
                $affected_playlists = [];

                foreach ($music_files as $file) {
                    try {
                        /** @var Entity\StationMedia $media */
                        $media = $mediaRepo->findByPath($file['path'], $station);

                        $media_playlists = $playlistMediaRepo->clearPlaylistsFromMedia($media);

                        foreach ($media_playlists as $playlist_id => $playlist) {
                            if (!isset($affected_playlists[$playlist_id])) {
                                $affected_playlists[$playlist_id] = $playlist;
                            }
                        }

                        if ($media instanceof Entity\StationMedia) {
                            $em->remove($media);
                        }
                    } catch (Exception $e) {
                        $errors[] = $file . ': ' . $e->getMessage();
                    }

                    $files_affected++;
                }

                $em->flush();

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

                $em->persist($station);
                $em->flush($station);

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

                        $em->persist($playlist);
                        $em->flush();

                        $response_record = [
                            'id' => $playlist->getId(),
                            'name' => $playlist->getName(),
                        ];

                        $affected_playlists[$playlist->getId()] = $playlist;
                        $playlists[] = $playlist;
                        $playlist_weights[$playlist->getId()] = 0;
                    } else {
                        $playlist = $em->getRepository(Entity\StationPlaylist::class)->findOneBy([
                            'station_id' => $station->getId(),
                            'id' => (int)$playlist_id,
                        ]);

                        if ($playlist instanceof Entity\StationPlaylist) {
                            $affected_playlists[$playlist->getId()] = $playlist;
                            $playlists[] = $playlist;
                            $playlist_weights[$playlist->getId()] = $playlistMediaRepo->getHighestSongWeight($playlist);
                        }
                    }
                }

                $music_files = $this->_getMusicFiles($fs, $files);
                $files_found = count($music_files);

                foreach ($music_files as $file) {
                    try {
                        $media = $mediaRepo->getOrCreate($station, $file['path']);

                        $media_playlists = $playlistMediaRepo->clearPlaylistsFromMedia($media);
                        foreach ($media_playlists as $playlist_id => $playlist) {
                            if (!isset($affected_playlists[$playlist_id])) {
                                $affected_playlists[$playlist_id] = $playlist;
                            }
                        }

                        foreach ($playlists as $playlist) {
                            $playlist_weights[$playlist->getId()]++;
                            $weight = $playlist_weights[$playlist->getId()];

                            $playlistMediaRepo->addMediaToPlaylist($media, $playlist, $weight);
                        }
                    } catch (Exception $e) {
                        $errors[] = $file . ': ' . $e->getMessage();
                    }

                    $files_affected++;
                }

                $em->flush();

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
                        $media = $mediaRepo->getOrCreate($station, $file['path']);

                        $old_full_path = $media->getPathUri();

                        $newPath = ('' === $directory_path)
                            ? $file['basename']
                            : $directory_path . '/' . $file['basename'];
                        $media->setPath($newPath);

                        if (!$fs->rename($old_full_path, $media->getPath())) {
                            throw new \Azura\Exception(__('Could not move "%s" to "%s"', $old_full_path,
                                $media->getPath()));
                        }

                        $em->persist($media);
                        $em->flush($media);
                        $files_affected++;
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }

                $em->flush();
                break;

            case 'queue':
                $music_files = $this->_getMusicFiles($fs, $files);
                $files_found = count($music_files);

                try {
                    foreach ($music_files as $file) {
                        $media = $mediaRepo->getOrCreate($station, $file['path']);

                        $newRequest = new Entity\StationRequest($station, $media);
                        $em->persist($newRequest);
                        $em->flush($newRequest);
                        $files_affected++;
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
                break;
        }

        $em->clear(Entity\StationMedia::class);
        $em->clear(Entity\StationPlaylist::class);
        $em->clear(Entity\StationPlaylistMedia::class);
        $em->clear(Entity\StationRequest::class);

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
