<?php
namespace App\Controller\Stations\Files;

use App\Http\ResponseHelper;
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

    protected function _err(ResponseInterface $response, $code, $msg): ResponseInterface
    {
        return ResponseHelper::withJson(
            $response->withStatus($code),
            ['error' => ['code' => (int)$code, 'msg' => $msg]]
        );
    }
}
