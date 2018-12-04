<?php
namespace App\Controller\Stations\Files;

use App\Entity;
use App\Flysystem\StationFilesystem;
use App\Http\Request;
use App\Http\Response;
use App\Radio\Filesystem;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class BatchController extends FilesControllerAbstract
{
    /** @var EntityManager */
    protected $em;

    /** @var Filesystem */
    protected $filesystem;

    /**
     * BatchController constructor.
     * @param EntityManager $em
     * @param Filesystem $filesystem
     *
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityManager $em, Filesystem $filesystem)
    {
        $this->em = $em;
        $this->filesystem = $filesystem;
    }

    public function __invoke(Request $request, Response $response, $station_id): ResponseInterface
    {
        try {
            $request->getSession()->getCsrf()->verify($request->getParam('csrf'), $this->csrf_namespace);
        } catch(\Azura\Exception\CsrfValidation $e) {
            return $response->withStatus(403)
                ->withJson(['error' => ['code' => 403, 'msg' => 'CSRF Failure: '.$e->getMessage()]]);
        }

        $station = $request->getStation();
        $fs = $this->filesystem->getForStation($station);

        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $this->em->getRepository(Entity\StationMedia::class);

        /** @var Entity\Repository\StationPlaylistMediaRepository $playlists_media_repo */
        $playlists_media_repo = $this->em->getRepository(Entity\StationPlaylistMedia::class);

        // Convert from pipe-separated files parameter into actual paths.
        $files_raw = explode('|', $_POST['files']);
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

        list($action, $action_id) = explode('_', $_POST['do']);

        switch ($action) {
            case 'delete':
                // Remove the database entries of any music being removed.
                $music_files = $this->_getMusicFiles($fs, $files);
                $files_found = count($music_files);

                foreach ($music_files as $file) {
                    try {
                        $media = $media_repo->findOneBy([
                            'station_id' => $station->getId(),
                            'path' => $file['path'],
                        ]);

                        if ($media instanceof Entity\StationMedia) {
                            $this->em->remove($media);
                        }
                    } catch (\Exception $e) {
                        $errors[] = $file.': '.$e->getMessage();
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
                        $fs->delete($file);
                    }
                }
                break;

            case 'clear':
                $backend = $request->getStationBackend();

                // Clear all assigned playlists from the selected files.
                $music_files = $this->_getMusicFiles($fs, $files);
                $files_found = count($music_files);

                foreach ($music_files as $file) {
                    try {
                        $media = $media_repo->getOrCreate($station, $file['path']);

                        $playlists_media_repo->clearPlaylistsFromMedia($media);
                    } catch (\Exception $e) {
                        $errors[] = $file.': '.$e->getMessage();
                    }

                    $files_affected++;
                }

                $this->em->flush();

                // Write new PLS playlist configuration.
                $backend->write($station);
                break;

            // Add all selected files to a playlist.
            case 'playlist':
                $backend = $request->getStationBackend();

                if ($action_id === 'new') {
                    $playlist = new Entity\StationPlaylist($station);
                    $playlist->setName($_POST['name']);

                    $this->em->persist($playlist);
                    $this->em->flush();

                    $response_record = [
                        'id' => $playlist->getId(),
                        'name' => $playlist->getName(),
                    ];
                } else {
                    $playlist_id = (int)$action_id;
                    $playlist = $this->em->getRepository(Entity\StationPlaylist::class)->findOneBy([
                        'station_id' => $station->getId(),
                        'id' => $playlist_id
                    ]);

                    if (!($playlist instanceof Entity\StationPlaylist)) {
                        return $this->_err($response, 500, 'Playlist Not Found');
                    }
                }

                $music_files = $this->_getMusicFiles($fs, $files);

                $files_found = count($music_files);

                $weight = $playlists_media_repo->getHighestSongWeight($playlist);

                foreach ($music_files as $file) {
                    $weight++;
                    try {
                        $media = $media_repo->getOrCreate($station, $file['path']);
                        $weight = $playlists_media_repo->addMediaToPlaylist($media, $playlist, $weight);
                    } catch (\Exception $e) {
                        $errors[] = $file.': '.$e->getMessage();
                    }

                    $files_affected++;
                }

                $this->em->flush();

                // Reshuffle the playlist if needed.
                $playlists_media_repo->reshuffleMedia($playlist);

                // Write new PLS playlist configuration.
                $backend->write($station);
                break;

            case 'move':
                $music_files = $this->_getMusicFiles($fs, $files);
                $files_found = count($music_files);

                $directory_path = $request->getParsedBody()['directory'];
                $directory_path_full = 'media://'.$directory_path;

                foreach ($music_files as $file) {
                    try {
                        $directory_path_meta = $fs->getMetadata($directory_path_full);

                        if ('dir' !== $directory_path_meta['type']) {
                            throw new \Azura\Exception(__('Path "%s" is not a folder.', $directory_path_full));
                        }

                        $media = $media_repo->getOrCreate($station, $file['path']);

                        $old_full_path = $media->getFullPath();
                        $media->setPath($directory_path . DIRECTORY_SEPARATOR . basename($file));

                        if (!$fs->rename($old_full_path, $media->getPath())) {
                            throw new \Azura\Exception(__('Could not move "%s" to "%s"', $old_full_path, $media->getFullPath()));
                        }

                        $this->em->persist($media);
                        $this->em->flush($media);
                    } catch (\Exception $e) {
                        $errors[] = $file.': '.$e->getMessage();
                    }

                    $files_affected++;
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

        return array_filter($fs->listContents($path, $recursive), function($file) {
            return ('file' === $file['type']);
        });
    }
}
