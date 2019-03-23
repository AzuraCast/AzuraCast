<?php
namespace App\Controller\Stations\Files;

use App\Entity;
use App\Http\Request;
use App\Http\Response;
use App\Radio\Filesystem;
use Psr\Http\Message\ResponseInterface;
use App\Utilities;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Finder\Finder;

class FilesController extends FilesControllerAbstract
{
    /** @var EntityManager */
    protected $em;

    /** @var Filesystem */
    protected $filesystem;

    /** @var array */
    protected $form_config;

    /**
     * FilesController constructor.
     * @param EntityManager $em
     * @param Filesystem $filesystem
     * @param array $form_config
     *
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityManager $em, Filesystem $filesystem, array $form_config)
    {
        $this->em = $em;
        $this->filesystem = $filesystem;
        $this->form_config = $form_config;
    }

    public function __invoke(Request $request, Response $response, $station_id): ResponseInterface
    {
        $station = $request->getStation();

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

        return $request->getView()->renderToResponse($response, 'stations/files/index', [
            'playlists' => $playlists,
            'custom_fields' => $custom_fields,
            'space_used' => $station->getStorageUsed(),
            'space_total' => $station->getStorageAvailable(),
            'space_percent' => $station->getStorageUsePercentage(),
            'files_count' => $files_count,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function renameAction(Request $request, Response $response, $station_id): ResponseInterface
    {
        $station = $request->getStation();
        $fs = $this->filesystem->getForStation($station);

        $path = $request->getAttribute('file');
        $path_full = $request->getAttribute('file_path');

        if (empty($path)) {
            throw new \Azura\Exception('File not specified.');
        }

        $form = new \AzuraForms\Form($this->form_config);
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

            $request->getSession()->flash('<b>' . __('File renamed!') . '</b>', 'green');

            $file_dir = (dirname($path) === '.') ? '' : dirname($path);

            return $response->withRedirect((string)$request->getRouter()->fromHere('stations:files:index').'#'.$file_dir);
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Rename File/Directory')
        ]);
    }

    public function listDirectoriesAction(Request $request, Response $response, $station_id): ResponseInterface
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

        $directories = array_filter(array_map(function($file) {
            if ('dir' !== $file['type']) {
                return null;
            }

            return [
                'name' => $file['basename'],
                'path' => $file['path'],
            ];
        }, $fs->listContents($file_path)));

        return $response->withJson([
            'rows' => array_values($directories)
        ]);
    }

    public function mkdirAction(Request $request, Response $response): ResponseInterface
    {
        try {
            $request->getSession()->getCsrf()->verify($request->getParam('csrf'), $this->csrf_namespace);
        } catch(\Azura\Exception\CsrfValidation $e) {
            return $this->_err($response, 403, 'CSRF Failure: '.$e->getMessage());
        }

        $file_path = $request->getAttribute('file_path');

        $station = $request->getStation();
        $fs = $this->filesystem->getForStation($station);

        $new_dir = $file_path.'/'.$_POST['name'];
        $dir_created = $fs->createDir($new_dir);
        if (!$dir_created) {
            return $this->_err($response, 403, sprintf('Directory "%s" was not created', $new_dir));
        }

        return $response->withJson(['success' => true]);
    }

    public function uploadAction(Request $request, Response $response): ResponseInterface
    {
        try {
            $request->getSession()->getCsrf()->verify($request->getParam('csrf'), $this->csrf_namespace);
        } catch(\Azura\Exception\CsrfValidation $e) {
            return $response->withStatus(403)
                ->withJson(['error' => ['code' => 403, 'msg' => 'CSRF Failure: '.$e->getMessage()]]);
        }

        $station = $request->getStation();

        if ($station->isStorageFull()) {
            throw new \App\Exception\OutOfSpace(__('This station is out of available storage space.'));
        }

        try {
            $flow = new \App\Service\Flow($request, $response, $station->getRadioTempDir());
            $flow_response = $flow->process();

            if ($flow_response instanceof Response) {
                return $flow_response;
            }
        } catch (\Exception | \Error $e) {
            return $this->_err($response, 500, $e->getMessage());
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
            if ($request->hasParam('searchPhrase')) {
                $search_phrase = $request->getParam('searchPhrase');

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

            return $response->withJson(['success' => true]);
        }

        return $response->withJson(['success' => false]);
    }

    public function downloadAction(Request $request, Response $response): ResponseInterface
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
        } catch(\Exception $e) {
            $file_mime = 'application/octet-stream';
        }

        return $response
            ->withNoCache()
            ->withHeader('Content-Type', $file_mime)
            ->withHeader('Content-Length', $file_meta['size'])
            ->withHeader('Content-Disposition', sprintf('attachment; filename=%s',
                strpos('MSIE', $_SERVER['HTTP_REFERER']) ? rawurlencode($filename) : "\"$filename\""))
            ->withHeader('X-Accel-Buffering', 'no')
            ->withBody(new \Slim\Http\Stream($fh));
    }
}
