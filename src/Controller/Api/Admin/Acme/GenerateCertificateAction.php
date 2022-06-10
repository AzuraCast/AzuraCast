<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Acme;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\GenerateAcmeCertificate;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

final class GenerateCertificateAction
{
    public function __construct(
        private readonly MessageBus $messageBus
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $tempFile = File::generateTempPath('acme_test.log');

        $message = new GenerateAcmeCertificate();
        $message->outputPath = $tempFile;

        $this->messageBus->dispatch($message);

        $router = $request->getRouter();
        return $response->withJson(
            [
                'success' => true,
                'links' => [
                    'log' => (string)$router->fromHere('api:admin:acme-log', [
                        'path' => basename($tempFile),
                    ]),
                ],
            ]
        );
    }
}
