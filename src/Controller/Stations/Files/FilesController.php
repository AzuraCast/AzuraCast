<?php
namespace App\Controller\Stations\Files;

use App\Entity;
use App\Form\Form;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use App\Radio\Filesystem;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FilesController extends FilesControllerAbstract
{
    /** @var EntityManager */
    protected $em;

    /** @var Filesystem */
    protected $filesystem;

    /** @var array */
    protected $form_config;

    /**
     * @param EntityManager $em
     * @param Filesystem $filesystem
     * @param Config $config
     */
    public function __construct(
        EntityManager $em,
        Filesystem $filesystem,
        Config $config
    ) {
        $this->em = $em;
        $this->filesystem = $filesystem;
        $this->form_config = $config->get('forms/rename');
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $station_id): ResponseInterface
    {
        $station = RequestHelper::getStation($request);

        $playlists = $this->em->createQuery(/** @lang DQL */'SELECT sp.id, sp.name 
            FROM App\Entity\StationPlaylist sp 
            WHERE sp.station_id = :station_id AND sp.source = :source 
            ORDER BY sp.name ASC')
            ->setParameter('station_id', $station_id)
            ->setParameter('source', Entity\StationPlaylist::SOURCE_SONGS)
            ->getArrayResult();

        $files_count = $this->em->createQuery(/** @lang DQL */'SELECT COUNT(sm.id) FROM App\Entity\StationMedia sm
            WHERE sm.station_id = :station_id')
            ->setParameter('station_id', $station_id)
            ->getSingleScalarResult();

        // Get list of custom fields.
        $custom_fields_raw = $this->em->createQuery(/** @lang DQL */'SELECT cf.id, cf.name FROM App\Entity\CustomField cf ORDER BY cf.name ASC')
            ->getArrayResult();

        $custom_fields = [];
        foreach($custom_fields_raw as $row) {
            $custom_fields['media_custom_'.$row['id']] = $row['name'];
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'stations/files/index', [
            'playlists' => $playlists,
            'custom_fields' => $custom_fields,
            'space_used' => $station->getStorageUsed(),
            'space_total' => $station->getStorageAvailable(),
            'space_percent' => $station->getStorageUsePercentage(),
            'files_count' => $files_count,
            'csrf' => RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function renameAction(ServerRequestInterface $request, ResponseInterface $response, $station_id): ResponseInterface
    {
        $station = RequestHelper::getStation($request);
        $fs = $this->filesystem->getForStation($station);

        $path = $request->getAttribute('file');
        $path_full = $request->getAttribute('file_path');

        if (empty($path)) {
            throw new \Azura\Exception('File not specified.');
        }

        $form = new Form($this->form_config);
        $form->populate([
            'new_file'  => $path,
        ]);

        if (!empty($_POST) && $form->isValid()) {
            $data = $form->getValues();

            // Detect rename.
            if ($data['new_file'] !== $path) {
                $new_path = $data['new_file'];
                $new_path_full = 'media://'.$new_path;

                // MountManager::rename's second argument is NOT the full URI >:(
                $fs->rename($path_full, $new_path);
                $path_meta = $fs->getMetadata($new_path_full);

                if ('dir' === $path_meta['type']) {
                    // Update the paths of all media contained within the directory.
                    $media_in_dir = $this->em->createQuery(/** @lang DQL */'SELECT sm FROM App\Entity\StationMedia sm
                        WHERE sm.station_id = :station_id AND sm.path LIKE :path')
                        ->setParameter('station_id', $station->getId())
                        ->setParameter('path', $path . '%')
                        ->execute();

                    foreach($media_in_dir as $media_row) {
                        /** @var Entity\StationMedia $media_row */
                        $media_row->setPath(substr_replace($media_row->getPath(), $new_path,0, strlen($path)));
                        $this->em->persist($media_row);
                    }

                    $this->em->flush();
                }

                $path = $new_path;
            }

            RequestHelper::getSession($request)->flash('<b>' . __('File renamed!') . '</b>', 'green');

            $file_dir = (dirname($path) === '.') ? '' : dirname($path);

            return ResponseHelper::withRedirect($response, (string)RequestHelper::getRouter($request)->fromHere('stations:files:index').'#'.$file_dir);
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Rename File/Directory')
        ]);
    }

    public function listDirectoriesAction(ServerRequestInterface $request, ResponseInterface $response, $station_id): ResponseInterface
    {
        $station = RequestHelper::getStation($request);
        $fs = $this->filesystem->getForStation($station);

        $file_path = $request->getAttribute('file_path');

        if (!empty($request->getAttribute('file'))) {
            $file_meta = $fs->getMetadata($file_path);

            if ('dir' !== $file_meta['type']) {
                throw new \Azura\Exception(__('Path "%s" is not a folder.', $file_path));
            }
        }

        $directories = array_filter(array_map(function($file) {
            if ('dir' !== $file['type']) {
                return null;
            }

            return [
                'name' => $file['basename'],
                'path' => $file['path'],
            ];
        }, $fs->listContents($file_path)));

        return ResponseHelper::withJson($response, [
            'rows' => array_values($directories)
        ]);
    }

    public function mkdirAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = RequestHelper::getParams($request);

        try {
            RequestHelper::getSession($request)->getCsrf()->verify($params['csrf'], $this->csrf_namespace);
        } catch(\Azura\Exception\CsrfValidation $e) {
            return $this->_err($response, 403, 'CSRF Failure: '.$e->getMessage());
        }

        $file_path = $request->getAttribute('file_path');

        $station = RequestHelper::getStation($request);
        $fs = $this->filesystem->getForStation($station);

        $new_dir = $file_path.'/'.$params['name'];
        $dir_created = $fs->createDir($new_dir);
        if (!$dir_created) {
            return $this->_err($response, 403, sprintf('Directory "%s" was not created', $new_dir));
        }

        return ResponseHelper::withJson($response, ['success' => true]);
    }

    public function uploadAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = RequestHelper::getParams($request);

        try {
            RequestHelper::getSession($request)->getCsrf()->verify($params['csrf'], $this->csrf_namespace);
        } catch(\Azura\Exception\CsrfValidation $e) {
            return $this->_err($response, 403, 'CSRF Failure: '.$e->getMessage());
        }

        $station = RequestHelper::getStation($request);

        if ($station->isStorageFull()) {
            return $this->_err($response, 500, __('This station is out of available storage space.'));
        }

        try {
            $flow_response = \App\Service\Flow::process($request, $response, $station->getRadioTempDir());
            if ($flow_response instanceof ResponseInterface) {
                return $flow_response;
            }

            if (is_array($flow_response)) {
                /** @var Entity\Repository\StationMediaRepository $media_repo */
                $media_repo = $this->em->getRepository(Entity\StationMedia::class);

                /** @var Entity\Repository\StationPlaylistMediaRepository $playlists_media_repo */
                $playlists_media_repo = $this->em->getRepository(Entity\StationPlaylistMedia::class);

                $file = $request->getAttribute('file');
                $file_path = $request->getAttribute('file_path');

                $sanitized_name = $flow_response['filename'];

                $final_path = empty($file)
                    ? $file_path.$sanitized_name
                    : $file_path.'/'.$sanitized_name;

                $station_media = $media_repo->uploadFile($station, $flow_response['path'], $final_path);

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
                            $playlists_media_repo->addMediaToPlaylist($station_media, $playlist);
                            $this->em->flush();

                            $playlists_media_repo->reshuffleMedia($playlist);
                        }
                    }
                }

                $station->addStorageUsed($flow_response['size']);
                $this->em->flush();

                return ResponseHelper::withJson($response, ['success' => true]);
            }
        } catch (\Exception | \Error $e) {
            return $this->_err($response, 500, $e->getMessage());
        }

        return ResponseHelper::withJson($response, ['success' => false]);
    }

    public function downloadAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        set_time_limit(600);

        $station = RequestHelper::getStation($request);
        $file_path = $request->getAttribute('file_path');

        $fs = $this->filesystem->getForStation($station);

        $filename = basename($file_path);
        $fh = $fs->readStream($file_path);

        $file_meta = $fs->getMetadata($file_path);

        try {
            $file_mime = $fs->getMimetype($file_path);
        } catch(\Exception $e) {
            $file_mime = 'application/octet-stream';
        }

        return ResponseHelper::withNoCache($response)
            ->withHeader('Content-Type', $file_mime)
            ->withHeader('Content-Length', $file_meta['size'])
            ->withHeader('Content-Disposition', sprintf('attachment; filename=%s',
                strpos('MSIE', $_SERVER['HTTP_REFERER']) ? rawurlencode($filename) : "\"$filename\""))
            ->withHeader('X-Accel-Buffering', 'no')
            ->withBody(new \Slim\Psr7\Stream($fh));
    }
}
