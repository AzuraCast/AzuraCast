<?php
namespace App\Controller\Stations\Files;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;

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

    protected function _err(Response $response, $code, $msg): ResponseInterface
    {
        return $response
            ->withStatus($code)
            ->withJson(['error' => ['code' => (int)$code, 'msg' => $msg]]);
    }
}
