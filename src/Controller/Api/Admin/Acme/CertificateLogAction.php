<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Acme;

use App\Controller\Api\Traits\HasLogViewer;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;

final class CertificateLogAction implements SingleActionInterface
{
    use HasLogViewer;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $path */
        $path = $params['path'];

        $tempPath = File::validateTempPath($path);

        return $this->streamLogToResponse(
            $request,
            $response,
            $tempPath
        );
    }
}
