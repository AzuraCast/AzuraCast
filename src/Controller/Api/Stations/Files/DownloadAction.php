<?php
namespace App\Controller\Api\Stations\Files;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Filesystem;
use Exception;
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

        $filename = basename($file_path);
        $fh = $fs->readStream($file_path);

        $file_meta = $fs->getMetadata($file_path);

        try {
            $file_mime = $fs->getMimetype($file_path);
        } catch (Exception $e) {
            $file_mime = 'application/octet-stream';
        }

        return $response->withFileDownload($fh, $filename, $file_mime)
            ->withHeader('Content-Length', $file_meta['size'])
            ->withHeader('X-Accel-Buffering', 'no');
    }
}