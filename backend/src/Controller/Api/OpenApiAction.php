<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Console\Command\Dev\GenerateApiDocsCommand;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Version;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/openapi.yml',
        operationId: 'getOpenApiSpec',
        summary: 'Returns the OpenAPI specification document for this installation.',
        security: [],
        tags: [OpenApi::TAG_MISC],
        responses: [
            new OpenApi\Response\SuccessWithDownload(
                description: 'Success',
                content: new OA\MediaType(
                    mediaType: 'text/x-yaml',
                    schema: new OA\Schema(
                        description: 'The OpenAPI specification document for this installation.',
                        type: 'string',
                        format: 'binary'
                    )
                )
            ),
        ]
    )
]
final class OpenApiAction implements SingleActionInterface
{
    public function __construct(
        private readonly Version $version,
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

        $yaml = $this->apiDocsCommand->generate($this->version->getVersion(), $apiBaseUrl)?->toYaml();

        return $response->renderStringAsFile(
            $yaml ?? '',
            'text/x-yaml',
        );
    }
}
