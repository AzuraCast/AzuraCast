<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Flysystem\ExtendedFilesystemInterface;
use Psr\Http\Message\ResponseInterface;

final class DownloadAction extends AbstractFileAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $path
    ): ResponseInterface {
        [$path, $fs] = $this->getFile($path);

        /** @var ExtendedFilesystemInterface $fs */
        return $response
            ->withNoCache()
            ->streamFilesystemFile($fs, $path);
    }
}
