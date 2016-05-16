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

    public function listAction()
    {
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
                    'name' => $media_row['artist'].' - '.$media_row['title'].' ('.$media_row['length_text'].')',
                    'playlists' => $playlists,
                );
            }

            $directory = $this->file_path;
            $result = array();
            $files = array_diff(scandir($directory), array('.', '..'));

            foreach ($files as $entry)
            {
                if ($entry !== basename(__FILE__))
                {
                    $i = $directory . '/' . $entry;
                    $short = ltrim(str_replace($this->base_dir, '', $i), '/');

                    if (is_dir($i))
                        $media = ['name' => 'Directory', 'playlists' => []];
                    elseif (isset($media_in_dir[$short]))
                        $media = $media_in_dir[$short];
                    else
                        $media = ['name' => 'File Not Processed', 'playlists' => []];

                    $stat = stat($i);

                    $max_length = 60;
                    $shortname = basename($i);
                    if (strlen($shortname) > $max_length)
                        $shortname = substr($shortname, 0, $max_length-15).'...'.substr($shortname, -12);

                    $result[] = array(
                        'mtime' => $stat['mtime'],
                        'size' => $stat['size'],
                        'name' => basename($i),
                        'text' => $shortname,
                        'path' => $short,
                        'is_dir' => is_dir($i),
                        'media' => $media,
                    );
                }
                else
                {
                    return $this->_err(412, "Not a Directory");
                }
            }
        }

        return $this->response->setJsonContent(array('success' => true, 'results' => $result));
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

        $action = $_POST['do'];
        switch($action)
        {
            case 'delete':
                // Delete all selected files.
                foreach($files as $file)
                    $this->_rmrf($file);
            break;

            case 'clear':
                // Clear all assigned playlists from the selected files.
                $music_files = $this->_getMusicFiles($files);

                foreach($music_files as $file)
                {
                    $media = StationMedia::getOrCreate($this->station, $file);
                    $media->playlists->clear();
                    $this->em->persist($media);
                }

                $this->em->flush();
            break;

            default:
                // Add all selected files to a playlist.
                if (substr($action, 0, 8) == 'playlist')
                {
                    $action_parts = explode('_', $action);
                    $playlist_id = (int)$action_parts[1];

                    $playlist = StationPlaylist::getRepository()->findOneBy(['station_id' => $this->station->id, 'id' => $playlist_id]);

                    if (!($playlist instanceof StationPlaylist))
                        return $this->_err(500, 'Playlist Not Found');

                    $music_files = $this->_getMusicFiles($files);

                    foreach($music_files as $file)
                    {
                        $media = StationMedia::getOrCreate($this->station, $file);

                        if (!$media->playlists->contains($playlist))
                            $media->playlists->add($playlist);

                        $this->em->persist($media);
                    }

                    $this->em->flush();
                }
            break;
        }
        
        return $this->response->setJsonContent(['success' => true]);
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

        return $this->response->setJsonContent(['success' => true]);
    }

    public function uploadAction()
    {
        var_dump($_POST);
        var_dump($_FILES);
        var_dump($_FILES['file_data']['tmp_name']);

        $upload_file_path = $this->file_path.'/'.$_FILES['file_data']['name'];
        var_dump(move_uploaded_file($_FILES['file_data']['tmp_name'], $upload_file_path));

        $station_media = StationMedia::getOrCreate($this->station, $upload_file_path);
        $station_media->save();

        return null;
    }

    public function downloadAction()
    {
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
        return $this->response->setJsonContent(array('error' => array('code'=>intval($code), 'msg' => $msg)));
    }
}