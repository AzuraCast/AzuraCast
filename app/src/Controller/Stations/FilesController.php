<?php
namespace Controller\Stations;

use Entity;
use App\Flash;
use App\Http\Request;
use App\Http\Response;
use App\Mvc\View;
use App\Url;
use App\Utilities;
use AzuraCast\Radio\Backend\BackendAbstract;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class FilesController
 *
 * Uses components based on:
 * Simple PHP File Manager - Copyright John Campbell (jcampbell1)
 * License: MIT
 */
class FilesController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /** @var Url */
    protected $url;

    /** @var array */
    protected $edit_form_config;

    /** @var array */
    protected $rename_form_config;

    /** @var Entity\Repository\StationMediaRepository */
    protected $media_repo;

    /**
     * FilesController constructor.
     * @param EntityManager $em
     * @param Flash $flash
     * @param Url $url
     * @param array $edit_form_config
     * @param array $rename_form_config
     */
    public function __construct(EntityManager $em, Flash $flash, Url $url, array $edit_form_config, array $rename_form_config)
    {
        $this->em = $em;
        $this->flash = $flash;
        $this->url = $url;
        $this->edit_form_config = $edit_form_config;
        $this->rename_form_config = $rename_form_config;

        $this->media_repo = $this->em->getRepository(Entity\StationMedia::class);
    }

    public function indexAction(Request $request, Response $response, $station_id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $playlists = $this->em->createQuery('SELECT sp.id, sp.name FROM Entity\StationPlaylist sp WHERE sp.station_id = :station_id ORDER BY sp.name ASC')
            ->setParameter('station_id', $station_id)
            ->getArrayResult();

        // Show available file space in the station directory.
        $media_dir = $station->getRadioMediaDir();
        $space_free = disk_free_space($media_dir);
        $space_total = disk_total_space($media_dir);
        $space_used = $space_total - $space_free;

        /** @var View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/files/index', [
            'playlists' => $playlists,
            'space_free' => Utilities::bytes_to_text($space_free),
            'space_used' => Utilities::bytes_to_text($space_used),
            'space_total' => Utilities::bytes_to_text($space_total),
            'space_percent' => round(($space_used / $space_total) * 100),
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $media_id): Response
    {
        $media = $this->em->getRepository(Entity\StationMedia::class)->findOneBy([
            'station_id' => $station_id,
            'id' => $media_id
        ]);

        if (!($media instanceof Entity\StationMedia)) {
            throw new \Exception('Media not found.');
        }

        $form = new \App\Form($this->edit_form_config);
        $form->populate($this->media_repo->toArray($media));

        if (!empty($_POST) && $form->isValid()) {
            $data = $form->getValues();
            unset($data['length']);

            // Detect rename.
            if ($data['path'] !== $media->getPath()) {
                list($data['path'], $path_full) = $this->_filterPath($data['path']);
                rename($media->getFullPath(), $path_full);
            }

            $this->media_repo->fromArray($media, $data);

            // Handle uploaded artwork files.
            $files = $request->getUploadedFiles();
            if (!empty($files['art'])) {
                $file = $files['art'];

                /** @var UploadedFileInterface $file */
                if ($file->getError() === UPLOAD_ERR_OK) {
                    $art_resource = imagecreatefromstring($file->getStream()->getContents());
                    $media->setArt($art_resource);
                } else if ($file->getError() !== UPLOAD_ERR_NO_FILE) {
                    throw new \App\Exception('Error ' . $file->getError() . ' in uploaded file!');
                }
            }

            if ($media->writeToFile()) {
                $media->setSong($this->em->getRepository(Entity\Song::class)->getOrCreate([
                    'title' => $media->getTitle(),
                    'artist' => $media->getArtist(),
                ]));
            }

            $this->em->persist($media);
            $this->em->flush();

            $this->flash->alert('<b>' . _('Media metadata updated!') . '</b>', 'green');

            $file_dir = (dirname($media->getPath()) === '.') ? '' : dirname($media->getPath());
            return $response->redirectToRoute('stations:files:index', ['station' => $station_id], 302, '#'.$file_dir);
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => _('Edit Media Metadata')
        ]);
    }

    public function renameAction(Request $request, Response $response, $station_id, $path): Response
    {
        $path = base64_decode($path);
        list($path, $path_full) = $this->_filterPath($path);

        $form = new \App\Form($this->rename_form_config);

        $form->populate(['path' => $path]);

        if (!empty($_POST) && $form->isValid()) {
            $data = $form->getValues();

            // Detect rename.
            if ($data['path'] !== $path) {
                list($new_path, $new_path_full) = $this->_filterPath($data['path']);
                rename($path_full, $new_path_full);

                if (is_dir($new_path_full)) {
                    // Update the paths of all media contained within the directory.
                    $media_in_dir = $this->em->createQuery('SELECT sm FROM Entity\StationMedia sm
                        WHERE sm.station_id = :station_id AND sm.path LIKE :path')
                        ->setParameter('station_id', $this->station->getId())
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

            $this->flash->alert('<b>' . _('File renamed!') . '</b>', 'green');

            $file_dir = (dirname($path) === '.') ? '' : dirname($path);
            return $response->redirectToRoute('stations:files:index', ['station' => $station_id], 302, '#'.$file_dir);
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => _('Rename File/Directory')
        ]);
    }

    public function listAction(Request $request, Response $response, $station_id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $result = [];

        $file = $request->getAttribute('file');
        $file_path = $request->getAttribute('file_path');

        if (is_dir($file_path)) {
            $media_in_dir_raw = $this->em->createQuery('SELECT 
              partial sm.{id, unique_id, path, length, length_text, artist, title, album}, partial sp.{id, name}
              FROM Entity\StationMedia sm 
              LEFT JOIN sm.playlists sp 
              WHERE sm.station_id = :station_id 
              AND sm.path LIKE :path')
                ->setParameter('station_id', $station_id)
                ->setParameter('path', $file . '%')
                ->getArrayResult();

            $media_in_dir = [];
            foreach ($media_in_dir_raw as $media_row) {
                $playlists = [];
                foreach ($media_row['playlists'] as $playlist_row) {
                    $playlists[] = $playlist_row['name'];
                }

                $media_in_dir[$media_row['path']] = [
                    'is_playable' => ($media_row['length'] !== 0),
                    'length' => $media_row['length'],
                    'length_text' => $media_row['length_text'],
                    'artist' => $media_row['artist'],
                    'title' => $media_row['title'],
                    'album' => $media_row['album'],
                    'name' => $media_row['artist'] . ' - ' . $media_row['title'],
                    'art' => $this->url->named('api:media:art', ['station' => $station_id, 'media_id' => $media_row['unique_id']]),
                    'edit_url' => $this->url->named('stations:files:edit', ['station' => $station_id, 'id' => $media_row['id']]),
                    'play_url' => $this->url->named('stations:files:download', ['station' => $station_id]) . '?file=' . urlencode($media_row['path']),
                    'playlists' => $playlists,
                ];
            }

            // Search all recursive files
            if (!empty($_REQUEST['searchPhrase'])) {
                $files = $this->_getMusicFiles($file_path);
            } else {
                $files = array_diff(scandir($file_path), ['.', '..']);
                foreach($files as &$file)
                    $file = $file_path.'/'.$file;

                unset($file);
            }

            foreach ($files as $i) {
                $short = ltrim(str_replace($station->getRadioMediaDir(), '', $i), '/');

                if (is_dir($i)) {
                    $media = ['name' => _('Directory'), 'playlists' => [], 'is_playable' => false];
                } elseif (isset($media_in_dir[$short])) {
                    $media = $media_in_dir[$short];
                } else {
                    $media = ['name' => _('File Not Processed'), 'playlists' => [], 'is_playable' => false];
                }

                $stat = stat($i);

                $max_length = 60;
                $shortname = basename($i);
                if (mb_strlen($shortname) > $max_length) {
                    $shortname = mb_substr($shortname, 0, $max_length - 15) . '...' . mb_substr($shortname, -12);
                }

                $result_row = [
                    'mtime' => $stat['mtime'],
                    'size' => $stat['size'],
                    'name' => $short,
                    'path' => $short,
                    'text' => $shortname,
                    'is_dir' => is_dir($i),
                    'rename_url' => $this->url->named('stations:files:rename', ['station' => $station_id, 'path' => base64_encode($short)]),
                ];

                foreach ($media as $media_key => $media_val) {
                    $result_row['media_' . $media_key] = $media_val;
                }

                $result[] = $result_row;
            }
        }

        // Example from bootgrid docs:
        // current=1&rowCount=10&sort[sender]=asc&searchPhrase=&id=b0df282a-0d67-40e5-8558-c9e93b7befed

        // Apply sorting, limiting and searching.
        $search_phrase = trim($_REQUEST['searchPhrase']);

        if (!empty($search_phrase)) {
            $result = array_filter($result, function ($row) use ($search_phrase) {
                if (substr($search_phrase, 0, 9) === 'playlist:') {
                    $playlist_name = substr($search_phrase, 9);
                    return in_array($playlist_name, $row['media_playlists']);
                }

                $search_fields = ['media_name', 'text'];

                foreach ($search_fields as $field_name) {
                    if (stripos($row[$field_name], $search_phrase) !== false) {
                        return true;
                    }
                }

                return false;
            });
        }

        $sort_by = ['is_dir', \SORT_DESC];

        if (!empty($_REQUEST['sort'])) {
            foreach ($_REQUEST['sort'] as $sort_key => $sort_direction) {
                $sort_dir = (strtolower($sort_direction) === 'desc') ? \SORT_DESC : \SORT_ASC;

                $sort_by[] = $sort_key;
                $sort_by[] = $sort_dir;
            }
        } else {
            $sort_by[] = 'name';
            $sort_by[] = \SORT_ASC;
        }

        $result = \App\Utilities::array_order_by($result, $sort_by);

        $num_results = count($result);

        $page = @$_REQUEST['current'] ?: 1;
        $row_count = @$_REQUEST['rowCount'] ?: 15;

        if ($row_count == -1) {
            $row_count = $num_results;
        }

        $offset_start = ($page - 1) * $row_count;
        if ($offset_start >= $num_results) {
            $page = floor($num_results / $row_count);
            $offset_start = ($page - 1) * $row_count;
        }

        $return_result = array_slice($result, $offset_start, $row_count);

        return $response->withJson([
            'current' => $page,
            'rowCount' => $row_count,
            'total' => $num_results,
            'rows' => $return_result,
        ]);
    }

    public function batchAction(Request $request, Response $response): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $base_dir = $station->getRadioMediaDir();

        $files_raw = explode('|', $_POST['files']);
        $files = [];

        foreach ($files_raw as $file) {
            $file_path = $base_dir . '/' . $file;
            if (file_exists($file_path)) {
                $files[] = $file_path;
            }
        }

        $files_found = 0;
        $files_affected = 0;

        $response_record = null;

        list($action, $action_id) = explode('_', $_POST['do']);

        switch ($action) {
            case 'delete':
                // Remove the database entries of any music being removed.
                $music_files = $this->_getMusicFiles($files);
                $files_found = count($music_files);

                foreach ($music_files as $i => $file) {
                    try {
                        $media = $this->media_repo->getOrCreate($station, $file);
                        $this->em->remove($media);
                    } catch (\Exception $e) {
                        @unlink($file);
                    }

                    $files_affected++;
                }

                $this->em->flush();

                // Delete all selected files.
                foreach ($files as $file) {
                    \App\Utilities::rmdir_recursive($file);
                }
                break;

            case 'clear':
                /** @var BackendAbstract $backend */
                $backend = $request->getAttribute('station_backend');

                // Clear all assigned playlists from the selected files.
                $music_files = $this->_getMusicFiles($files);
                $files_found = count($music_files);

                foreach ($music_files as $file) {
                    try {
                        $media = $this->media_repo->getOrCreate($station, $file);
                        $media->getPlaylists()->clear();
                        $this->em->persist($media);
                    } catch (\Exception $e) { }

                    $files_affected++;
                }

                $this->em->flush();

                // Write new PLS playlist configuration.
                $backend->write();
                break;

            // Add all selected files to a playlist.
            case 'playlist':
                /** @var BackendAbstract $backend */
                $backend = $request->getAttribute('station_backend');

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

                $music_files = $this->_getMusicFiles($files);
                $files_found = count($music_files);

                foreach ($music_files as $file) {
                    try {
                        $media = $this->media_repo->getOrCreate($station, $file);

                        if (!$media->getPlaylists()->contains($playlist)) {
                            $media->getPlaylists()->add($playlist);
                        }

                        $this->em->persist($media);
                    } catch (\Exception $e) {
                    }

                    $files_affected++;
                }

                $this->em->flush();

                // Write new PLS playlist configuration.
                $backend->write();
                break;
        }

        return $response->withJson([
            'success' => true,
            'files_found' => $files_found,
            'files_affected' => $files_affected,
            'record' => $response_record,
        ]);
    }

    public function mkdirAction(Request $request, Response $response): Response
    {
        $file_path = $request->getAttribute('file_path');

        // don't allow actions outside root. we also filter out slashes to catch args like './../outside'
        $dir = $_POST['name'];
        $dir = str_replace('/', '', $dir);
        if (substr($dir, 0, 2) === '..') {
            return $this->_err($response, 403, 'Cannot create directory: ..');
        }

        @mkdir($file_path . '/' . $dir);

        return $response->withJson(['success' => true]);
    }

    public function uploadAction(Request $request, Response $response): Response
    {
        try {
            $flow = new \App\Service\Flow($request, $response);
            $flow_response = $flow->process();

            if ($flow_response instanceof Response) {
                return $flow_response;
            }

            if (is_array($flow_response)) {
                /** @var Entity\Station $station */
                $station = $request->getAttribute('station');

                $file_path = $request->getAttribute('file_path');

                $file = new \App\File(basename($flow_response['filename']), $file_path);
                $file->sanitizeName();

                $final_path = $file->getPath();
                rename($flow_response['path'], $final_path);

                $station_media = $this->media_repo->getOrCreate($station, $final_path);

                $this->em->persist($station_media);
                $this->em->flush();

                return $response->withJson(['success' => true]);
            }
        } catch (\Exception $e) {
            return $this->_err($response, 500, $e->getMessage());
        }
    }

    public function downloadAction(Request $request, Response $response): Response
    {
        set_time_limit(600);

        $file_path = $request->getAttribute('file_path');

        $filename = basename($file_path);
        $fh = fopen($file_path, 'rb');

        return $response
            ->withHeader('Content-Type', mime_content_type($file_path))
            ->withHeader('Content-Length', filesize($file_path))
            ->withHeader('Content-Disposition', sprintf('attachment; filename=%s',
                strpos('MSIE', $_SERVER['HTTP_REFERER']) ? rawurlencode($filename) : "\"$filename\""))
            ->withBody(new \Slim\Http\Stream($fh));
    }

    protected function _getMusicFiles($path)
    {
        if (is_array($path)) {
            $music_files = [];
            foreach ($path as $dir_file) {
                $music_files = array_merge($music_files, $this->_getMusicFiles($dir_file));
            }

            return $music_files;
        }

        if (is_dir($path)) {
            $music_files = [];

            $files = array_diff(scandir($path), ['.', '..']);
            foreach ($files as $file) {
                $file_path = $path . '/' . $file;
                if (is_dir($file_path)) {
                    $music_files = array_merge($music_files, $this->_getMusicFiles($file_path));
                } else {
                    $music_files[] = $file_path;
                }
            }

            return $music_files;
        } else {
            return [$path];
        }
    }

    protected function _filterPath($path)
    {
        $path = str_replace(['../', './'], ['', ''], $path);
        $path = trim($path, '/');

        $base_path = $this->station->getRadioMediaDir();
        $dir_path = $base_path.DIRECTORY_SEPARATOR.dirname($path);
        $full_path = $base_path.DIRECTORY_SEPARATOR.$path;

        if ($real_path = realpath($dir_path)) {
            if (substr($full_path, 0, strlen($base_path)) !== $base_path) {
                throw new \Exception('New location not inside station media directory.');
            }
        } else {
            throw new \Exception('Parent directory could not be resolved.');
        }

        return [$path, $full_path];
    }

    protected function _is_recursively_deleteable($d)
    {
        $stack = [$d];
        while ($dir = array_pop($stack)) {
            if (!is_readable($dir) || !is_writable($dir)) {
                return false;
            }
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $stack[] = "$dir/$file";
                }
            }
        }

        return true;
    }

    protected function _err(Response $response, $code, $msg)
    {
        return $response
            ->withStatus($code)
            ->withJson(['error' => ['code' => (int)$code, 'msg' => $msg]]);
    }
}