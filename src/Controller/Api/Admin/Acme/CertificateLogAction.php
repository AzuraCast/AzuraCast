<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Acme;

use App\Controller\Api\Traits\HasLogViewer;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;

final class CertificateLogAction
{
    use HasLogViewer;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $path
    ): ResponseInterface {
        $tempPath = File::validateTempPath($path);

        return $this->streamLogToResponse(
            $request,
            $response,
            $tempPath
        );
    }
}
