<?php
namespace App\Controller\Api\Stations\Files;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Filesystem;
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
        $file_path = $request->getAttribute('file_path');

        $fs = $filesystem->getForStation($station);

        return $response->withFlysystemFile($fs, $file_path);
    }
}