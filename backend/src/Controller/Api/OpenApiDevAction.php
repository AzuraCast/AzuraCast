<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Console\Command\Dev\GenerateApiDocsCommand;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Version;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class OpenApiDevAction extends OpenApiPublicAction
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
        $openApi = $this->apiDocsCommand->generate($this->version->getVersion());

        if (null === $openApi) {
            throw new RuntimeException('Cannot generate OpenAPI!');
        }

        return $this->writeApiWithLocalSite(
            $openApi->toYaml(),
            $request,
            $response
        );
    }
}
