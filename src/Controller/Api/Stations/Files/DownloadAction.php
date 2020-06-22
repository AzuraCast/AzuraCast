<?php
namespace App\Controller\Api\Stations\Files;

use App\Flysystem\Filesystem;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DownloadAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Filesystem $filesystem
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();
        $filePath = $request->getAttribute('file_path');

        $fs = $filesystem->getForStation($station);

        return $response->withFlysystemFile($fs, $filePath);
    }
}