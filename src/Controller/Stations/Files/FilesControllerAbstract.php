<?php
namespace App\Controller\Stations\Files;

use App\Flysystem\StationFilesystem;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Class FilesControllerAbstract
 *
 * Uses components based on:
 * Simple PHP File Manager - Copyright John Campbell (jcampbell1)
 * License: MIT
 */
abstract class FilesControllerAbstract
{
    /** @var string */
    protected $csrf_namespace = 'stations_files';

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
        print_r($path_meta);
        exit;

        if (!is_dir($path)) {
            return [$path];
        }

        $finder = new Finder();
        $finder = $finder->files()->in($path);

        if (!$recursive) {
            $finder = $finder->depth('== 0');
        }

        $music_files = [];
        foreach($finder as $file) {
            $music_files[] = $file->getPathname();
        }
        return $music_files;
    }

    protected function _is_recursively_deleteable($d)
    {
        $stack = [$d];
        while ($dir = array_pop($stack)) {
            if (!is_readable($dir) || !is_writable($dir)) {
                return false;
            }
            $files = array_diff(scandir($dir, \SCANDIR_SORT_NONE), ['.', '..']);
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $stack[] = "$dir/$file";
                }
            }
        }

        return true;
    }

    protected function _asBytes($ini_v)
    {
        $ini_v = trim($ini_v);
        $s = ['g' => 1 << 30, 'm' => 1 << 20, 'k' => 1 << 10];

        return (int)$ini_v * ($s[strtolower(substr($ini_v, -1))] ?: 1);
    }

    protected function _err(Response $response, $code, $msg): ResponseInterface
    {
        return $response
            ->withStatus($code)
            ->withJson(['error' => ['code' => (int)$code, 'msg' => $msg]]);
    }
}
