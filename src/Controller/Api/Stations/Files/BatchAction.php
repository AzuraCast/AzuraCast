<?php
namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\Filesystem;
use App\Flysystem\StationFilesystem;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\WritePlaylistFileMessage;
use App\Radio\Backend\Liquidsoap;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

class BatchAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo,
        Entity\Repository\StationPlaylistFolderRepository $playlistFolderRepo,
        Filesystem $filesystem,
        MessageBus $messageBus
    ): ResponseInterface {
        $station = $request->getStation();

        $fs = $filesystem->getForStation($station, false);

        // Convert from pipe-separated files parameter into actual paths.
        $files_raw = $request->getParam('files');
        $files = [];

        foreach ($files_raw as $file) {
            $file_path = Filesystem::PREFIX_MEDIA . '://' . $file;

            if ($fs->has($file_path)) {
                $files[] = $file_path;
            }
        }

        $files_found = 0;
        $files_affected = 0;

        $directories_found = 0;
        $directories_affected = 0;

        $response_record = null;
        $errors = [];

        switch ($request->getParam('do')) {
            case 'delete':
                // Remove the database entries of any music being removed.
                $music_files = $this->getMusicFiles($fs, $files);
                $files_found = count($music_files);

                /** @var Entity\StationPlaylist[] $affected_playlists */
                $affected_playlists = [];

                foreach ($music_files as $file) {
                    try {
                        /** @var Entity\StationMedia $media */
                        $media = $mediaRepo->findByPath($file['path'], $station);

                        if ($media instanceof Entity\StationMedia) {
                            $mediaPlaylists = $mediaRepo->remove($media);

                            foreach ($mediaPlaylists as $playlist_id => $playlist) {
                                if (!isset($affected_playlists[$playlist_id])) {
                                    $affected_playlists[$playlist_id] = $playlist;
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $errors[] = $file . ': ' . $e->getMessage();
                    }

                    $files_affected++;
                }

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
                $em->flush();

                // Write new PLS playlist configuration.
                $backend = $request->getStationBackend();

                if ($backend instanceof Liquidsoap) {
                    foreach ($affected_playlists as $playlist_id => $playlist_row) {
                        // Instruct the message queue to start a new "write playlist to file" task.
                        $message = new WritePlaylistFileMessage;
                        $message->playlist_id = $playlist_id;

                        $messageBus->dispatch($message);
                    }
                }
                break;

            case 'playlist':
                // Set playlists for selected files.
                $response_record = null;

                /** @var Entity\StationPlaylist[] $playlists */
                $playlists = [];
                $playlist_weights = [];
                $affected_playlists = [];

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

                        $affected_playlists[$playlist->getId()] = $playlist->getId();
                        $playlists[] = $playlist;
                        $playlist_weights[$playlist->getId()] = 0;
                    } else {
                        $playlist = $em->getRepository(Entity\StationPlaylist::class)->findOneBy([
                            'station_id' => $station->getId(),
                            'id' => (int)$playlist_id,
                        ]);

                        if ($playlist instanceof Entity\StationPlaylist) {
                            $affected_playlists[$playlist->getId()] = $playlist->getId();
                            $playlists[] = $playlist;
                            $playlist_weights[$playlist->getId()] = $playlistMediaRepo->getHighestSongWeight($playlist);
                        }
                    }
                }

                $music_files = $this->getMusicFiles($fs, $files);
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

                // Set the playlist auto-assignment of any selected folders.
                $directories = $this->getDirectories($fs, $files);
                $directories_found = count($directories);

                foreach ($directories as $dir) {
                    try {
                        $playlistFolderRepo->setPlaylistsForFolder($station, $playlists, $dir['path']);
                    } catch (Exception $e) {
                        $errors[] = $dir['path'] . ': ' . $e->getMessage();
                    }
                }

                $em->flush();

                // Write new PLS playlist configuration.
                $backend = $request->getStationBackend();

                if ($backend instanceof Liquidsoap) {
                    foreach ($affected_playlists as $playlist_id => $playlist_row) {
                        // Instruct the message queue to start a new "write playlist to file" task.
                        $message = new WritePlaylistFileMessage;
                        $message->playlist_id = $playlist_id;

                        $messageBus->dispatch($message);
                    }
                }
                break;

            case 'move':
                $music_files = $this->getMusicFiles($fs, $files);
                $files_found = count($music_files);

                $directory_path = $request->getParam('directory');
                $directory_path_full = Filesystem::PREFIX_MEDIA . '://' . $directory_path;

                try {
                    // Verify that you're moving to a directory (if it's not the root dir).
                    if ('' !== $directory_path) {
                        $directory_path_meta = $fs->getMetadata($directory_path_full);
                        if ('dir' !== $directory_path_meta['type']) {
                            throw new \App\Exception(__('Path "%s" is not a folder.', $directory_path_full));
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
                            throw new \App\Exception(__('Could not move "%s" to "%s"', $old_full_path,
                                $media->getPath()));
                        }

                        $em->persist($media);
                        $em->flush();
                        $files_affected++;
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }

                $em->flush();
                break;

            case 'queue':
                $music_files = $this->getMusicFiles($fs, $files);
                $files_found = count($music_files);

                foreach ($music_files as $file) {
                    try {
                        $media = $mediaRepo->getOrCreate($station, $file['path']);

                        $newRequest = new Entity\StationRequest($station, $media, $request->getIp(), true);
                        $em->persist($newRequest);
                        $em->flush();
                        $files_affected++;
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                }
                break;
        }

        if ($em->isOpen()) {
            $em->clear(Entity\StationMedia::class);
            $em->clear(Entity\StationPlaylist::class);
            $em->clear(Entity\StationPlaylistMedia::class);
            $em->clear(Entity\StationRequest::class);
        }

        return $response->withJson([
            'success' => empty($errors),
            'files_found' => $files_found,
            'files_affected' => $files_affected,
            'directories_found' => $directories_found,
            'directories_affected' => $directories_affected,
            'errors' => $errors,
            'record' => $response_record,
        ]);
    }

    protected function getMusicFiles(StationFilesystem $fs, array $files): array
    {
        $musicFiles = [];

        foreach ($files as $path) {
            $pathMeta = $fs->getMetadata($path);

            if ('file' === $pathMeta['type']) {
                $musicFiles[] = $pathMeta;
            } else {
                foreach ($fs->listContents($path, true) as $file) {
                    if ('file' === $file['type']) {
                        $musicFiles[] = $file;
                    }
                }
            }
        }

        return $musicFiles;
    }

    protected function getDirectories(StationFilesystem $fs, array $files): array
    {
        $directories = [];

        foreach ($files as $path) {
            $pathMeta = $fs->getMetadata($path);

            if ('dir' === $pathMeta['type']) {
                $directories[] = $pathMeta;
            }
        }

        return $directories;
    }
}
