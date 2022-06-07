<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Controller\Api\Traits\HasLogViewer;
use App\Entity;
use App\Entity\Repository\StationWebhookRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;

final class TestLogAction
{
    use HasLogViewer;

    public function __construct(
        private readonly StationWebhookRepository $webhookRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id,
        string $path
    ): ResponseInterface {
        $this->webhookRepo->requireForStation($id, $request->getStation());

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
