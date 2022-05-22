<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Controller\Api\Traits\HasLogViewer;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;

final class TestLogAction extends AbstractWebhooksAction
{
    use HasLogViewer;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int $id,
        string $path
    ): ResponseInterface {
        $this->requireRecord($request->getStation(), $id);

        $logPathPortion = 'webhook_test_' . $id;
        if (!str_contains($path, $logPathPortion)) {
            return $response
                ->withStatus(403)
                ->withJson(new Entity\Api\Error(403, 'Invalid log path.'));
        }

        $tempPath = File::validateTempPath($path);

        return $this->streamLogToResponse(
            $request,
            $response,
            $tempPath
        );
    }
}
