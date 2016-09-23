<?php
namespace Modules\Stations\Controllers;

use Entity\Station;
use Entity\StationMedia;
use Entity\StationPlaylist;


/**
 * Class FilesController
 *
 * Uses components based on:
 * Simple PHP File Manager - Copyright John Campbell (jcampbell1)
 * License: MIT
 */
class FilesController extends BaseController
{
    protected $base_dir = NULL;
    protected $file = '';
    protected $file_path = NULL;

    public function preDispatch()
    {
        parent::preDispatch();

        $this->base_dir = realpath($this->station->radio_base_dir.'/media');
        $this->view->base_dir = $this->base_dir;

        if (!empty($_REQUEST['file']))
            $this->file = $_REQUEST['file'];

        $this->file_path = realpath($this->base_dir.'/'.$this->file);

        if ($this->file_path === false)
            return $this->_err(404,'File or Directory Not Found');
        if(substr($this->file_path, 0, strlen($this->base_dir)) !== $this->base_dir)
            return $this->_err(403,"Forbidden");

        $csrf = $this->di->get('csrf');
        $this->view->CSRF = $csrf->generate('files');

        if (!empty($_POST))
        {
            if (!$csrf->verify($_POST['xsrf'], 'files'))
                return $this->_err(403, 'XSRF Failure');
        }

        $this->view->MAX_UPLOAD_SIZE = min($this->_asBytes(ini_get('post_max_size')), $this->_asBytes(ini_get('upload_max_filesize')));
    }

    protected function _asBytes($ini_v) {
        $ini_v = trim($ini_v);
        $s = array('g'=> 1<<30, 'm' => 1<<20, 'k' => 1<<10);
        return intval($ini_v) * ($s[strtolower(substr($ini_v,-1))] ?: 1);
    }

    public function indexAction()
    {
        $playlists_raw = $this->em->createQuery('SELECT sp.id, sp.name FROM Entity\StationPlaylist sp WHERE sp.station_id = :station_id ORDER BY sp.name ASC')
            ->setParameter('station_id', $this->station->id)
            ->getArrayResult();

        $playlists = array();
        foreach($playlists_raw as $row)
            $playlists[$row['id']] = $row['name'];

        $this->view->playlists = $playlists;
    }

    public function editAction()
    {
        $media_id = (int)$this->getParam('id');
        $media = StationMedia::getRepository()->findOneBy(['station_id' => $this->station->id, 'id' => $media_id]);

        if (!($media instanceof StationMedia))
            throw new \Exception('Media not found.');

        if (empty($_POST))
            $this->storeReferrer('media_edit');

        $form_config = $this->current_module_config->forms->media->toArray();
        $form = new \App\Form($form_config);

        $form->populate($media->toArray());

        if (!empty($_POST) && $form->isValid())
        {
            $data = $form->getValues();

            $media->fromArray($data);
            $media->writeToFile();
            $media->save();

            $this->alert('<b>Media metadata updated!</b>', 'green');

            $default_url = $this->url->routeFromHere(['action' => 'index']);
            return $this->redirectToStoredReferrer('media_edit', $default_url);
        }

        return $this->renderForm($form, 'edit', 'Edit Media Metadata');
    }

    public function listAction()
    {
        $result = array();

        if (is_dir($this->file_path))
        {
            $media_in_dir_raw = $this->em->createQuery('SELECT sm, sp FROM Entity\StationMedia sm LEFT JOIN sm.playlists sp WHERE sm.station_id = :station_id AND sm.path LIKE :path')
                ->setParameter('station_id', $this->station->id)
                ->setParameter('path', $this->file.'%')
                ->getArrayResult();

            $media_in_dir = array();
            foreach($media_in_dir_raw as $media_row)
            {
                $playlists = array();
                foreach($media_row['playlists'] as $playlist_row)
                    $playlists[] = $playlist_row['name'];

                $media_in_dir[$media_row['path']] = array(
                    'is_playable' => true,
                    'length' => $media_row['length'],
                    'length_text' => $media_row['length_text'],
                    'artist' => $media_row['artist'],
                    'title' => $media_row['title'],
                    'name' => $media_row['artist'].' - '.$media_row['title'],
                    'edit_url' => $this->url->routeFromHere(['action' => 'edit', 'id' => $media_row['id']]),
                    'playlists' => implode('<br>', $playlists),
                );
            }

            $directory = $this->file_path;

            $files = array_diff(scandir($directory), array('.', '..'));
            foreach ($files as $entry)
            {
                $i = $directory . '/' . $entry;
                $short = ltrim(str_replace($this->base_dir, '', $i), '/');

                if (is_dir($i))
                    $media = ['name' => 'Directory', 'playlists' => '', 'is_playable' => false];
                elseif (isset($media_in_dir[$short]))
                    $media = $media_in_dir[$short];
                else
                    $media = ['name' => 'File Not Processed', 'playlists' => '', 'is_playable' => false];

                $stat = stat($i);

                $max_length = 60;
                $shortname = basename($i);
                if (mb_strlen($shortname) > $max_length)
                    $shortname = mb_substr($shortname, 0, $max_length-15).'...'.mb_substr($shortname, -12);

                $result_row = array(
                    'mtime' => $stat['mtime'],
                    'size' => $stat['size'],
                    'name' => basename($i),
                    'text' => $shortname,
                    'path' => $short,
                    'is_dir' => is_dir($i),
                );

                foreach($media as $media_key => $media_val)
                    $result_row['media_'.$media_key] = $media_val;

                $result[] = $result_row;
            }
        }

        // Example from bootgrid docs:
        // current=1&rowCount=10&sort[sender]=asc&searchPhrase=&id=b0df282a-0d67-40e5-8558-c9e93b7befed

        // Apply sorting, limiting and searching.
        $search_phrase = trim($_REQUEST['searchPhrase']);

        if (!empty($search_phrase))
        {
            $result = array_filter($result, function($row) use($search_phrase) {
                $search_fields = array('media_name', 'text');

                foreach($search_fields as $field_name)
                {
                    if (stripos($row[$field_name], $search_phrase) !== false)
                        return true;
                }

                return false;
            });
        }

        $sort_by = array('is_dir', \SORT_DESC);

        if (!empty($_REQUEST['sort']))
        {
            foreach ($_REQUEST['sort'] as $sort_key => $sort_direction)
            {
                $sort_dir = (strtolower($sort_direction) == 'desc') ? \SORT_DESC : \SORT_ASC;

                $sort_by[] = $sort_key;
                $sort_by[] = $sort_dir;
            }
        }
        else
        {
            $sort_by[] = 'name';
            $sort_by[] = \SORT_ASC;
        }

        $result = \App\Utilities::arrayOrderBy($result, $sort_by);

        $num_results = count($result);

        $page = @$_REQUEST['current'] ?: 1;
        $row_count = @$_REQUEST['rowCount'] ?: 15;

        $offset_start = ($page - 1) * $row_count;
        $return_result = array_slice($result, $offset_start, $row_count);

        return $this->renderJson(array(
            'current' => $page,
            'rowCount' => $row_count,
            'total' => $num_results,
            'rows' => $return_result,
        ));
    }

    public function batchAction()
    {
        $files_raw = explode('|', $_POST['files']);
        $files = array();

        foreach($files_raw as $file)
        {
            $file_path = $this->file_path.'/'.$file;
            if (file_exists($file_path))
                $files[] = $file_path;
        }

        $files_found = 0;
        $files_affected = 0;

        list($action, $action_id) = explode('_', $_POST['do']);

        switch($action)
        {
            case 'delete':
                // Remove the database entries of any music being removed.
                $music_files = $this->_getMusicFiles($files);
                $files_found = count($music_files);

                foreach($music_files as $file)
                {
                    $media = StationMedia::getOrCreate($this->station, $file);
                    $this->em->remove($media);

                    $files_affected++;
                }

                $this->em->flush();

                // Delete all selected files.
                foreach($files as $file)
                    $this->_rmrf($file);
            break;

            case 'clear':
                // Clear all assigned playlists from the selected files.
                $music_files = $this->_getMusicFiles($files);
                $files_found = count($music_files);

                foreach($music_files as $file)
                {
                    $media = StationMedia::getOrCreate($this->station, $file);
                    $media->playlists->clear();
                    $this->em->persist($media);

                    $files_affected++;
                }

                $this->em->flush();

                // Write new PLS playlist configuration.
                $this->station->getBackendAdapter()->write();
            break;

            // Add all selected files to a playlist.
            case 'playlist':
                $playlist_id = (int)$action_id;
                $playlist = StationPlaylist::getRepository()->findOneBy(['station_id' => $this->station->id, 'id' => $playlist_id]);

                if (!($playlist instanceof StationPlaylist))
                    return $this->_err(500, 'Playlist Not Found');

                $music_files = $this->_getMusicFiles($files);
                $files_found = count($music_files);

                foreach($music_files as $file)
                {
                    $media = StationMedia::getOrCreate($this->station, $file);

                    if (!$media->playlists->contains($playlist))
                        $media->playlists->add($playlist);

                    $this->em->persist($media);

                    $files_affected++;
                }

                $this->em->flush();

                // Write new PLS playlist configuration.
                $this->station->getBackendAdapter()->write();
            break;
        }

        return $this->renderJson(['success' => true, 'files_found' => $files_found, 'files_affected' => $files_affected]);
    }

    protected function _getMusicFiles($path)
    {
        if (is_array($path))
        {
            $music_files = array();
            foreach($path as $dir_file)
                $music_files = array_merge($music_files, $this->_getMusicFiles($dir_file));
            return $music_files;
        }

        if (is_dir($path))
        {
            $music_files = array();

            $files = array_diff(scandir($path), array('.','..'));
            foreach ($files as $file)
            {
                $file_path = $path . '/' . $file;
                if (is_dir($file_path))
                    $music_files = array_merge($music_files, $this->_getMusicFiles($file_path));
                else
                    $music_files[] = $file_path;
            }

            return $music_files;
        }
        else
        {
            return array($path);
        }
    }

    protected function _rmrf($dir)
    {
        if(is_dir($dir))
        {
            $files = array_diff(scandir($dir), array('.','..'));
            foreach ($files as $file)
                $this->_rmrf($dir.'/'.$file);

            rmdir($dir);
        }
        else
        {
            unlink($dir);
        }
    }

    public function mkdirAction()
    {
        // don't allow actions outside root. we also filter out slashes to catch args like './../outside'
        $dir = $_POST['name'];
        $dir = str_replace('/', '', $dir);
        if(substr($dir, 0, 2) === '..')
            exit;

        @mkdir($this->file_path.'/'.$dir);

        return $this->renderJson(['success' => true]);
    }

    public function uploadAction()
    {
        $this->doNotRender();

        var_dump($_POST);
        var_dump($_FILES);
        var_dump($_FILES['file_data']['tmp_name']);

        $upload_file_path = $this->file_path.'/'.$_FILES['file_data']['name'];
        var_dump(move_uploaded_file($_FILES['file_data']['tmp_name'], $upload_file_path));

        $station_media = StationMedia::getOrCreate($this->station, $upload_file_path);
        $station_media->save();
    }

    public function downloadAction()
    {
        $this->doNotRender();

        $filename = basename($this->file_path);
        header('Content-Type: ' . mime_content_type($this->file_path));
        header('Content-Length: '. filesize($this->file_path));

        header(sprintf('Content-Disposition: attachment; filename=%s',
            strpos('MSIE',$_SERVER['HTTP_REFERER']) ? rawurlencode($filename) : "\"$filename\"" ));

        ob_flush();
        readfile($this->file_path);
    }

    protected function _is_recursively_deleteable($d)
    {
        $stack = array($d);
        while($dir = array_pop($stack)) {
            if(!is_readable($dir) || !is_writable($dir))
                return false;
            $files = array_diff(scandir($dir), array('.','..'));
            foreach($files as $file) if(is_dir($file)) {
                $stack[] = "$dir/$file";
            }
        }
        return true;
    }

    protected function _err($code, $msg)
    {
        return $this->renderJson(array('error' => array('code'=>intval($code), 'msg' => $msg)));
    }
}