<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Acme;

use App\Controller\SingleActionInterface;
use App\Entity\Api\TaskWithLog;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\GenerateAcmeCertificate;
use App\OpenApi;
use App\Utilities\File;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

#[
    OA\Put(
        path: '/admin/acme',
        operationId: 'putAdminGenerateAcmeCert',
        summary: 'Generate or renew ACME certificate.',
        tags: [OpenApi::TAG_ADMIN],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: TaskWithLog::class
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final readonly class GenerateCertificateAction implements SingleActionInterface
{
    public function __construct(
        private MessageBus $messageBus
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $tempFile = File::generateTempPath('acme_test.log');

        $message = new GenerateAcmeCertificate();
        $message->outputPath = $tempFile;

        $this->messageBus->dispatch($message);

        $router = $request->getRouter();
        return $response->withJson(
            new TaskWithLog($router->fromHere('api:admin:acme-log', [
                'path' => basename($tempFile),
            ]))
        );
    }
}
