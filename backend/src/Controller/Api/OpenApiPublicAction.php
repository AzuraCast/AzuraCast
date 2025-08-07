<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Container\EnvironmentAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

#[
    OA\Get(
        path: '/openapi.yml',
        operationId: 'getOpenApiSpec',
        summary: 'Returns the OpenAPI specification document for this installation.',
        security: [],
        tags: [OpenApi::TAG_PUBLIC_MISC],
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
class OpenApiPublicAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $yamlPath = $this->environment->getBaseDirectory() . '/web/static/openapi.yml';
        $yamlContents = new Filesystem()->readFile($yamlPath);

        return $this->writeApiWithLocalSite(
            $yamlContents,
            $request,
            $response
        );
    }

    protected function writeApiWithLocalSite(
        string $yamlContents,
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $localApiUrl = $request->getRouter()->named(
            'api:index:index',
            absolute: true
        );

        /** @var array $yaml */
        $yaml = Yaml::parse($yamlContents);

        array_unshift(
            $yaml['servers'],
            [
                'url' => $localApiUrl,
                'description' => 'This Server',
            ]
        );

        return $response->renderStringAsFile(
            Yaml::dump($yaml, PHP_INT_MAX),
            'text/x-yaml',
        );
    }
}
