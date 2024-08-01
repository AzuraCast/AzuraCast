<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Entity\Api\Status;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class DeleteAction extends AbstractFileAction
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
        $fs->delete($path);

        return $response->withJson(Status::deleted());
    }
}
