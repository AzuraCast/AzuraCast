<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Controller\Api\Traits\HasLogViewer;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Repository\StationWebhookRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;

final class TestLogAction implements SingleActionInterface
{
    use HasLogViewer;

    public function __construct(
        private readonly StationWebhookRepository $webhookRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        /** @var string $path */
        $path = $params['path'];

        $this->webhookRepo->requireForStation($id, $request->getStation());

        $logPathPortion = 'webhook_test_' . $id;
        if (!str_contains($path, $logPathPortion)) {
            return $response
                ->withStatus(403)
                ->withJson(new Error(403, 'Invalid log path.'));
        }

        $tempPath = File::validateTempPath($path);

        return $this->streamLogToResponse(
            $request,
            $response,
            $tempPath
        );
    }
}
