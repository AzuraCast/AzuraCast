<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Console\Command\GenerateApiDocsCommand;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class OpenApiAction implements SingleActionInterface
{
    public function __construct(
        private readonly GenerateApiDocsCommand $apiDocsCommand
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $apiBaseUrl = str_replace(
            '/openapi.yml',
            '',
            $request->getRouter()->fromHere(absolute: true)
        );

        $yaml = $this->apiDocsCommand->generate(true, $apiBaseUrl)?->toYaml();

        return $response->renderStringAsFile(
            $yaml ?? '',
            'text/x-yaml',
        );
    }
}
