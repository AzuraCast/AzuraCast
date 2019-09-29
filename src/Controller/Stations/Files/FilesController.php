<?php
namespace App\Controller\Stations\Files;

use App\Entity;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Filesystem;
use App\Service\Flow;
use App\Service\Ftp;
use Azura\Config;
use Azura\Exception\CsrfValidationException;
use Azura\Session\Flash;
use Doctrine\ORM\EntityManager;
use Error;
use Exception;
use Psr\Http\Message\ResponseInterface;

class FilesController extends FilesControllerAbstract
{
    /** @var EntityManager */
    protected $em;

    /** @var Entity\Repository\StationMediaRepository */
    protected $mediaRepo;

    /** @var Entity\Repository\StationPlaylistMediaRepository */
    protected $spmRepo;

    /** @var Filesystem */
    protected $filesystem;

    /** @var array */
    protected $form_config;

    /** @var Ftp */
    protected $ftp;

    /**
     * @param EntityManager $em
     * @param Entity\Repository\StationMediaRepository $mediaRepo
     * @param Entity\Repository\StationPlaylistMediaRepository $spmRepo
     * @param Filesystem $filesystem
     * @param Config $config
     * @param Ftp $ftp
     */
    public function __construct(
        EntityManager $em,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Entity\Repository\StationPlaylistMediaRepository $spmRepo,
        Filesystem $filesystem,
        Config $config,
        Ftp $ftp
    ) {
        $this->em = $em;
        $this->mediaRepo = $mediaRepo;
        $this->spmRepo = $spmRepo;
        $this->filesystem = $filesystem;
        $this->form_config = $config->get('forms/rename');
        $this->ftp = $ftp;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $playlists = $this->em->createQuery(/** @lang DQL */ 'SELECT sp.id, sp.name 
            FROM App\Entity\StationPlaylist sp 
            WHERE sp.station_id = :station_id AND sp.source = :source 
            ORDER BY sp.name ASC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('source', Entity\StationPlaylist::SOURCE_SONGS)
            ->getArrayResult();

        $files_count = $this->em->createQuery(/** @lang DQL */ 'SELECT COUNT(sm.id) FROM App\Entity\StationMedia sm
            WHERE sm.station_id = :station_id')
            ->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        // Get list of custom fields.
        $custom_fields_raw = $this->em->createQuery(/** @lang DQL */ 'SELECT cf.id, cf.name FROM App\Entity\CustomField cf ORDER BY cf.name ASC')
            ->getArrayResult();

        $custom_fields = [];
        foreach ($custom_fields_raw as $row) {
            $custom_fields['media_custom_' . $row['id']] = $row['name'];
        }

        return $request->getView()->renderToResponse($response, 'stations/files/index', [
            'ftp_info' => $this->ftp->getInfo(),
            'playlists' => $playlists,
            'custom_fields' => $custom_fields,
            'space_used' => $station->getStorageUsed(),
            'space_total' => $station->getStorageAvailable(),
            'space_percent' => $station->getStorageUsePercentage(),
            'files_count' => $files_count,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function renameAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $fs = $this->filesystem->getForStation($station);

        $path = $request->getAttribute('file');
        $path_full = $request->getAttribute('file_path');

        if (empty($path)) {
            throw new \Azura\Exception('File not specified.');
        }

        $form = new Form($this->form_config);
        $form->populate([
            'new_file' => $path,
        ]);

        if (!empty($_POST) && $form->isValid()) {
            $data = $form->getValues();

            // Detect rename.
            if ($data['new_file'] !== $path) {
                $new_path = $data['new_file'];
                $new_path_full = 'media://' . $new_path;

                // MountManager::rename's second argument is NOT the full URI >:(
                $fs->rename($path_full, $new_path);
                $path_meta = $fs->getMetadata($new_path_full);

                if ('dir' === $path_meta['type']) {
                    // Update the paths of all media contained within the directory.
                    $media_in_dir = $this->em->createQuery(/** @lang DQL */ 'SELECT sm FROM App\Entity\StationMedia sm
                        WHERE sm.station_id = :station_id AND sm.path LIKE :path')
                        ->setParameter('station_id', $station->getId())
                        ->setParameter('path', $path . '%')
                        ->execute();

                    foreach ($media_in_dir as $media_row) {
                        /** @var Entity\StationMedia $media_row */
                        $media_row->setPath(substr_replace($media_row->getPath(), $new_path, 0, strlen($path)));
                        $this->em->persist($media_row);
                    }

                    $this->em->flush();
                }

                $path = $new_path;
            }

            $request->getFlash()->addMessage('<b>' . __('File renamed!') . '</b>', Flash::SUCCESS);

            $file_dir = (dirname($path) === '.') ? '' : dirname($path);

            return $response->withRedirect((string)$request->getRouter()->fromHere('stations:files:index') . '#' . $file_dir);
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Rename File/Directory'),
        ]);
    }

    public function listDirectoriesAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $fs = $this->filesystem->getForStation($station);

        $file_path = $request->getAttribute('file_path');

        if (!empty($request->getAttribute('file'))) {
            $file_meta = $fs->getMetadata($file_path);

            if ('dir' !== $file_meta['type']) {
                throw new \Azura\Exception(__('Path "%s" is not a folder.', $file_path));
            }
        }

        $directories = array_filter(array_map(function ($file) {
            if ('dir' !== $file['type']) {
                return null;
            }

            return [
                'name' => $file['basename'],
                'path' => $file['path'],
            ];
        }, $fs->listContents($file_path)));

        return $response->withJson([
            'rows' => array_values($directories),
        ]);
    }

    public function mkdirAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $params = $request->getParams();

        try {
            $request->getCsrf()->verify($params['csrf'], $this->csrf_namespace);
        } catch (CsrfValidationException $e) {
            return $this->_err($response, 403, 'CSRF Failure: ' . $e->getMessage());
        }

        $file_path = $request->getAttribute('file_path');

        $station = $request->getStation();
        $fs = $this->filesystem->getForStation($station);

        $new_dir = $file_path . '/' . $params['name'];
        $dir_created = $fs->createDir($new_dir);
        if (!$dir_created) {
            return $this->_err($response, 403, sprintf('Directory "%s" was not created', $new_dir));
        }

        return $response->withJson(['success' => true]);
    }

    public function uploadAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $params = $request->getParams();

        try {
            $request->getCsrf()->verify($params['csrf'], $this->csrf_namespace);
        } catch (CsrfValidationException $e) {
            return $this->_err($response, 403, 'CSRF Failure: ' . $e->getMessage());
        }

        $station = $request->getStation();

        if ($station->isStorageFull()) {
            return $this->_err($response, 500, __('This station is out of available storage space.'));
        }

        try {
            $flow_response = Flow::process($request, $response, $station->getRadioTempDir());
            if ($flow_response instanceof ResponseInterface) {
                return $flow_response;
            }

            if (is_array($flow_response)) {
                $file = $request->getAttribute('file');
                $file_path = $request->getAttribute('file_path');

                $sanitized_name = $flow_response['filename'];

                $final_path = empty($file)
                    ? $file_path . $sanitized_name
                    : $file_path . '/' . $sanitized_name;

                $station_media = $this->mediaRepo->uploadFile($station, $flow_response['path'], $final_path);

                // If the user is looking at a playlist's contents, add uploaded media to that playlist.
                if (!empty($params['searchPhrase'])) {
                    $search_phrase = $params['searchPhrase'];

                    if (0 === strpos($search_phrase, 'playlist:')) {
                        $playlist_name = substr($search_phrase, 9);

                        $playlist = $this->em->getRepository(Entity\StationPlaylist::class)->findOneBy([
                            'station_id' => $station->getId(),
                            'name' => $playlist_name,
                        ]);

                        if ($playlist instanceof Entity\StationPlaylist) {
                            $this->spmRepo->addMediaToPlaylist($station_media, $playlist);
                            $this->em->flush();
                        }
                    }
                }

                $station->addStorageUsed($flow_response['size']);
                $this->em->flush();

                return $response->withJson(['success' => true]);
            }
        } catch (Exception | Error $e) {
            return $this->_err($response, 500, $e->getMessage());
        }

        return $response->withJson(['success' => false]);
    }

    public function downloadAction(ServerRequest $request, Response $response): ResponseInterface
    {
        set_time_limit(600);

        $station = $request->getStation();
        $file_path = $request->getAttribute('file_path');

        $fs = $this->filesystem->getForStation($station);

        $filename = basename($file_path);
        $fh = $fs->readStream($file_path);

        $file_meta = $fs->getMetadata($file_path);

        try {
            $file_mime = $fs->getMimetype($file_path);
        } catch (Exception $e) {
            $file_mime = 'application/octet-stream';
        }

        return $response->withFileDownload($fh, $filename, $file_mime)
            ->withHeader('Content-Length', $file_meta['size'])
            ->withHeader('X-Accel-Buffering', 'no');
    }
}
