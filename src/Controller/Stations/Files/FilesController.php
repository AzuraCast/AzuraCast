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

        $playlists = $this->em->createQuery('SELECT sp.id, sp.name 
            FROM '.Entity\StationPlaylist::class.' sp 
            WHERE sp.station_id = :station_id AND sp.source = :source 
            ORDER BY sp.name ASC')
            ->setParameter('station_id', $station_id)
            ->setParameter('source', Entity\StationPlaylist::SOURCE_SONGS)
            ->getArrayResult();

        // Show available file space in the station directory.
        // TODO: This won't be applicable for stations that don't use local storage!
        $media_dir = $station->getRadioMediaDir();
        $space_free = disk_free_space($media_dir);
        $space_total = disk_total_space($media_dir);
        $space_used = $space_total - $space_free;
        
        $files_count = $this->em->createQuery('SELECT COUNT(sm.id) FROM '.Entity\StationMedia::class.' sm
            WHERE sm.station_id = :station_id')
            ->setParameter('station_id', $station_id)
            ->getSingleScalarResult();

        // Get list of custom fields.
        $custom_fields_raw = $this->em->createQuery('SELECT cf.id, cf.name FROM '.Entity\CustomField::class.' cf ORDER BY cf.name ASC')
            ->getArrayResult();

        $custom_fields = [];
        foreach($custom_fields_raw as $row) {
            $custom_fields['media_custom_'.$row['id']] = $row['name'];
        }

        return $request->getView()->renderToResponse($response, 'stations/files/index', [
            'playlists' => $playlists,
            'custom_fields' => $custom_fields,
            'space_free' => Utilities::bytes_to_text($space_free),
            'space_used' => Utilities::bytes_to_text($space_used),
            'space_total' => Utilities::bytes_to_text($space_total),
            'space_percent' => round(($space_used / $space_total) * 100),
            'files_count' => $files_count,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
            'max_upload_size' => min(
                $this->_asBytes(ini_get('post_max_size')),
                $this->_asBytes(ini_get('upload_max_filesize'))
            ),
        ]);
    }

    protected function _asBytes($ini_v)
    {
        $ini_v = trim($ini_v);
        $s = ['g' => 1 << 30, 'm' => 1 << 20, 'k' => 1 << 10];

        return (int)$ini_v * ($s[strtolower(substr($ini_v, -1))] ?: 1);
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
                    $media_in_dir = $this->em->createQuery('SELECT sm FROM '.Entity\StationMedia::class.' sm
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
        $file_path = $request->getAttribute('file_path');

        if (!is_dir($file_path)) {
            throw new \Azura\Exception(__('Path "%s" is not a folder.', $file_path));
        }

        $finder = new Finder();
        $finder->directories()->in($file_path)->depth(0)->sortByName();

        $directories = [];
        foreach ($finder as $directory) {
            $directories[] = [
                'name' => $directory->getFilename(),
                'path' => $directory->getRelativePathname()
            ];
        }

        return $response->withJson([
            'rows' => $directories
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
            return $this->_err($response, 403, sprintf('Directory "%s" was not created', $file_path . '/' . $dir));
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

        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $this->em->getRepository(Entity\StationMedia::class);

        /** @var Entity\Repository\StationPlaylistMediaRepository $playlists_media_repo */
        $playlists_media_repo = $this->em->getRepository(Entity\StationPlaylistMedia::class);

        try {
            $flow = new \App\Service\Flow($request, $response);
            $flow_response = $flow->process();

            if ($flow_response instanceof Response) {
                return $flow_response;
            }

            if (is_array($flow_response)) {
                $station = $request->getStation();

                $file_path = $request->getAttribute('file_path');

                $file = new \Azura\File(basename($flow_response['filename']), $file_path);
                $file->sanitizeName();

                $final_path = $file->getPath();

                $fs = $this->filesystem->getForStation($station);
                $fs->upload($flow_response['path'], $final_path);

                $station_media = $media_repo->getOrCreate($station, $final_path);
                $this->em->persist($station_media);

                // If the user is looking at a playlist's contents, add uploaded media to that playlist.
                if ($request->hasParam('searchPhrase')) {
                    $search_phrase = $request->getParam('searchPhrase');

                    if (substr($search_phrase, 0, 9) === 'playlist:') {
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

                $this->em->flush();

                return $response->withJson(['success' => true]);
            }
        } catch (\Exception | \Error $e) {
            return $this->_err($response, 500, $e->getMessage());
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

        return $response
            ->withHeader('Content-Type', mime_content_type($file_path))
            ->withHeader('Content-Length', filesize($file_path))
            ->withHeader('Content-Disposition', sprintf('attachment; filename=%s',
                strpos('MSIE', $_SERVER['HTTP_REFERER']) ? rawurlencode($filename) : "\"$filename\""))
            ->withBody(new \Slim\Http\Stream($fh));
    }
}
