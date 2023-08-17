<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Flysystem\ExtendedFilesystemInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class DownloadAction extends AbstractFileAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $path */
        $path = $params['path'];

        [$path, $fs] = $this->getFile($path);

        /** @var ExtendedFilesystemInterface $fs */
        return $response
            ->withNoCache()
            ->streamFilesystemFile($fs, $path);
    }
}
