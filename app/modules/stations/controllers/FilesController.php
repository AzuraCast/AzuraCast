<?php
namespace Modules\Stations\Controllers;

use Entity\Station;


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
    protected $file = '.';
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

        /* TODO: CSRF protection
        if(!$_COOKIE['_sfm_xsrf'])
            setcookie('_sfm_xsrf',bin2hex(openssl_random_pseudo_bytes(16)));
        if($_POST) {
            if($_COOKIE['_sfm_xsrf'] !== $_POST['xsrf'] || !$_POST['xsrf'])
                err(403,"XSRF Failure");
        */

        $this->view->MAX_UPLOAD_SIZE = min($this->_asBytes(ini_get('post_max_size')), $this->_asBytes(ini_get('upload_max_filesize')));

        $csrf = $this->di->get('csrf');
        $this->view->CSRF = $csrf->generate('files');
    }

    protected function _asBytes($ini_v) {
        $ini_v = trim($ini_v);
        $s = array('g'=> 1<<30, 'm' => 1<<20, 'k' => 1<<10);
        return intval($ini_v) * ($s[strtolower(substr($ini_v,-1))] ?: 1);
    }

    public function indexAction()
    {}

    public function listAction()
    {
        if (is_dir($this->file_path))
        {
            $directory = $this->file_path;
            $result = array();
            $files = array_diff(scandir($directory), array('.','..'));

            foreach($files as $entry) if($entry !== basename(__FILE__))
            {
                $i = $directory . '/' . $entry;
                $stat = stat($i);
                $result[] = array(
                    'mtime' => $stat['mtime'],
                    'size' => $stat['size'],
                    'name' => basename($i),
                    'path' => preg_replace('@^\./@', '', $i),
                    'is_dir' => is_dir($i),
                    'is_deleteable' => (!is_dir($i) && is_writable($directory)) || (is_dir($i) && is_writable($directory) && $this->_is_recursively_deleteable($i)),
                    'is_readable' => is_readable($i),
                    'is_writable' => is_writable($i),
                    'is_executable' => is_executable($i),
                );
            }
        }
        else
        {
            return $this->_err(412,"Not a Directory");
        }

        return $this->response->setJsonContent(array('success' => true, 'is_writable' => is_writable($this->file_path), 'results' =>$result));
    }

    public function deleteAction()
    {
        return $this->_rmrf($this->file_path);
    }

    public function mkdirAction()
    {
        // don't allow actions outside root. we also filter out slashes to catch args like './../outside'
        $dir = $_POST['name'];
        $dir = str_replace('/', '', $dir);
        if(substr($dir, 0, 2) === '..')
            exit;

        @chdir($this->file_path);
        @mkdir($_POST['name']);

        return null;
    }

    public function uploadAction()
    {
        var_dump($_POST);
        var_dump($_FILES);
        var_dump($_FILES['file_data']['tmp_name']);
        var_dump(move_uploaded_file($_FILES['file_data']['tmp_name'], $this->file_path.'/'.$_FILES['file_data']['name']));

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