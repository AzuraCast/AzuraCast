<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;

class GetLogAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $logPath = File::validateTempPath($path);

        return $this->streamLogToResponse($request, $response, $logPath, true);
    }
}
