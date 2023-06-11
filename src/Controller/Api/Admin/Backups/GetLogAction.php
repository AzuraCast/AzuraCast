<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Controller\Api\Traits\HasLogViewer;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;

final class GetLogAction implements SingleActionInterface
{
    use HasLogViewer;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $path */
        $path = $params['path'];

        $logPath = File::validateTempPath($path);

        return $this->streamLogToResponse($request, $response, $logPath);
    }
}
